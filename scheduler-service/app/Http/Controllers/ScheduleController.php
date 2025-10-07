<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Response;

class ScheduleController extends Controller
{
    /**
     * Get all schedules with possibility to filter by:
     *  - start/end date
     *  - date 
     *  - employee
     *  - role
     *  - active_only (only active employees)
     * 
     * JsonResponse is a collectino of schedules grouped by role ordered by date and start time.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Schedule::with(['employee.role', 'role']);
            
            // Filter by date range
            if ($request->has(['start_date', 'end_date'])) {
                $query->forDateRange($request->start_date, $request->end_date);
            }
            
            // Filter by specific date
            if ($request->has('date')) {
                $query->forDate($request->date);
            }
            
            // Filter by employee
            if ($request->has('employee_id')) {
                $query->forEmployee($request->employee_id);
            }
            
            // Filter by role
            if ($request->has('role_id')) {
                $query->where('assigned_role', $request->role_id);
            }
            
            // Only active employees
            if ($request->has('active_only') && $request->active_only === 'true') {
                $query->activeEmployees();
            }
            
            $schedules = $query->orderBy('date')->orderBy('start_time')->get();
            
            //$groupedSchedules = $this->groupSchedulesByRole($schedules);

            return response()->json([
                'success' => true,
                'data' => $schedules
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve schedules',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a new schedule schedule.
     */
    public function store(Request $request): JsonResponse
    {
        // NOTE: allow start_time to be later than end time to accomodate overnight shifts
        // e.g., start_time = 22:00, end_time = 06:
        $validator = Validator::make($request->all(), [
            'shifts' => 'required|array|min:1',
            'shifts.*.date' => 'required|date|after_or_equal:today',
            'shifts.*.start_time' => 'required|date_format:H:i',
            'shifts.*.end_time' => 'required|date_format:H:i',
            'shifts.*.employee_id' => 'required|exists:staff,id',
            'shifts.*.assigned_role' => 'required|exists:roles,id'
        ]);

        $validator->after(function ($validator) use ($request) {

            $shifts = $request->input('shifts', []);

        foreach ($shifts as $index => $shift) {
            if (!isset($shift['start_time'], $shift['end_time'], $shift['date'])) {
                continue;
            }
            
            try {
                $start = Carbon::parse($shift['start_time']);
                $end = Carbon::parse($shift['end_time']);
                
                // Handle overnight shifts (if end time is before start time, it's next day)
                if ($end->lt($start)) {
                    $end->addDay();
                }
                
                $durationInHours = $end->diffInHours($start);
                
                if ($durationInHours > Schedule::MAX_SHIFT_DURATION_HOURS) {
                    $validator->errors()->add(
                        "shifts.$index.end_time", 
                        'Shift duration cannot exceed '.Schedule::MAX_SHIFT_DURATION_HOURS.' hours.'
                    );
                }
            } catch (\Exception $e) {
                $validator->errors()->add(
                    "shifts.$index.time", 
                    'Invalid time format provided.'
                );
            }
        }
        });


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }


        try {
            $data = $validator->validated();
            $shifts = $data['shifts']; // Access the validated shifts array
            
            $createdSchedules = [];
            $conflicts = [];

            DB::beginTransaction();

            foreach ($shifts as $index => $shiftData) {
                // Check for existing shift on same date for the same employee
                $conflict = Schedule::where('employee_id', $shiftData['employee_id'])
                    ->where('date', Carbon::parse($shiftData['date'])->format('Y-m-d H:i:s'))
                    ->where(function($query) use ($shiftData) {
                        // Check for start_time in between another shift
                        $query->whereBetween('start_time', [$shiftData['start_time'], $shiftData['end_time']])
                                // Check for end_time in between another shift
                              ->orWhereBetween('end_time', [$shiftData['start_time'], $shiftData['end_time']])
                              // Check for another shift encompassing the new shift
                              ->orWhere(function($q) use ($shiftData) {
                                  $q->where('start_time', '<=', $shiftData['start_time'])
                                    ->where('end_time', '>=', $shiftData['end_time']);
                              });
                    })
                    ->exists();

                
                if ($conflict) {
                    $conflicts[] = [
                        'index' => $index,
                        'message' => 'Scheduling conflict: Employee already has a shift during this time'
                    ];
                    continue; // Skip creation for this shift
                }
                
                $createdSchedules[] = Schedule::create($shiftData);
            }
            // Check for existing shift on same date for the same employee



            if (!empty($conflicts)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduling conflicts:' . count($conflicts),
                    'errors' => $conflicts
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Schedule created successfully',
                'data' => $createdSchedules
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to create schedule',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Update the specified schedule.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $schedule = Schedule::find($id);
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // NOTE: allow start_time to be later than end time to accomodate overnight shifts
        // e.g., start_time = 22:00, end_time = 06:
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'employee_id' => 'required|exists:staff,id',
            'assigned_role' => 'required|exists:roles,id'
        ]);

        $validator->after(function ($validator) use ($request) {
            
            if (!$validator->errors()->any() && $request->has(['start_time', 'end_time', 'date'])) {
                $start = Carbon::parse($request->start_time);
                $end = Carbon::parse($request->end_time);
                $date = Carbon::parse($request->date);

                if ($date->isPast()) {
                    $validator->errors()->add(
                        'date', 
                        'Date cannot be in the past.'
                    );
                }
                
                // Handle overnight shifts (if end time is before start time, it's next day)
                if ($end->lt($start)) {
                    $end->addDay();
                }
                
                $durationInHours = $end->diffInHours($start);
                
                if ($durationInHours > Schedule::MAX_SHIFT_DURATION_HOURS) {
                    $validator->errors()->add(
                        'end_time', 
                        'Shift duration cannot exceed '.Schedule::MAX_SHIFT_DURATION_HOURS.' hours.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $validator->validated();
            
            if (isset($data['employee_id']) || isset($data['date']) || 
                isset($data['start_time']) || isset($data['end_time'])) {

                // Get updated field
                $employeeId = $data['employee_id'];
                $date = $data['date'];
                $startTime = $data['start_time'];
                $endTime = $data['end_time'];
                
                // Check for existing shift on same date for the same employee
                $conflict = Schedule::where('employee_id', $employeeId)
                    ->where('date', $date)
                    ->where('id', '!=', $id)
                    ->where(function($query) use ($startTime, $endTime) {
                        $query->whereBetween('start_time', [$startTime, $endTime])
                              ->orWhereBetween('end_time', [$startTime, $endTime])
                              ->orWhere(function($q) use ($startTime, $endTime) {
                                  $q->where('start_time', '<=', $startTime)
                                    ->where('end_time', '>=', $endTime);
                              });
                    })
                    ->exists();
                
                if ($conflict) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Scheduling conflict: Employee already has a shift during this time'
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                }
            }
            
            $schedule->update($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Schedule updated successfully',
                'data' => $schedule
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Update multiple schedules in one request. Accepts `shifts` array where each item must include `id` and fields to update.
     */
    public function updateMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shifts' => 'required|array|min:1',
            'shifts.*.id' => 'required|exists:schedules,id',
            'shifts.*.date' => 'required|date',
            'shifts.*.start_time' => 'required|date_format:H:i',
            'shifts.*.end_time' => 'required|date_format:H:i',
            'shifts.*.employee_id' => 'required|exists:staff,id',
            'shifts.*.assigned_role' => 'required|exists:roles,id'
        ]);

        $validator->after(function ($validator) use ($request) {
            $shifts = $request->input('shifts', []);
            foreach ($shifts as $index => $shift) {
                if (!isset($shift['start_time'], $shift['end_time'], $shift['date'])) {
                    continue;
                }
                try {
                    $start = Carbon::parse($shift['start_time']);
                    $end = Carbon::parse($shift['end_time']);
                    
                    // Handle overnight shifts (if end time is before start time, it's next day)
                    if ($end->lt($start)) {
                        $end->addDay();
                    }
                    
                    // Calculate duration
                    $durationInHours = $end->diffInHours($start);
                    
                    // Check if date is in the past
                    if ($durationInHours > Schedule::MAX_SHIFT_DURATION_HOURS) {
                        $validator->errors()->add(
                            "shifts.$index.end_time", 
                            'Shift duration cannot exceed '.Schedule::MAX_SHIFT_DURATION_HOURS.' hours.'
                        );
                    }
                } catch (\Exception $e) {
                    $validator->errors()->add(
                        "shifts.$index.time", 
                        'Invalid time format provided.'
                    );
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $validator->validated();
            $shifts = $data['shifts'];

            $updatedSchedules = [];
            $conflicts = [];

            DB::beginTransaction();

            foreach ($shifts as $index => $shiftData) {
                $schedule = Schedule::find($shiftData['id']);
                if (!$schedule) {
                    $conflicts[] = ['index' => $index, 'message' => 'Schedule not found for id '.$shiftData['id']];
                    continue;
                }

                // Get updated field
                $employeeId = $shiftData['employee_id'] ?? $schedule->employee_id;
                $date = $shiftData['date'] ?? $schedule->date;
                $startTime = $shiftData['start_time'] ?? $schedule->start_time;
                $endTime = $shiftData['end_time'] ?? $schedule->end_time;

                // Check for existing shift on same date for the same employee
                $conflict = Schedule::where('employee_id', $employeeId)
                    ->where('date', Carbon::parse($date)->format('Y-m-d H:i:s'))
                    ->where('id', '!=', $schedule->id)
                    ->where(function($query) use ($startTime, $endTime) {
                        $query->whereBetween('start_time', [$startTime, $endTime])
                              ->orWhereBetween('end_time', [$startTime, $endTime])
                              ->orWhere(function($q) use ($startTime, $endTime) {
                                  $q->where('start_time', '<=', $startTime)
                                    ->where('end_time', '>=', $endTime);
                              });
                    })->exists();


                if ($conflict) {
                    $conflicts[] = [
                        'index' => $index, 
                        'message' => 'Scheduling conflict: Employee already has a shift during this time'
                    ];
                    continue;
                }


                $schedule->update($shiftData);
                $updatedSchedules[] = $shiftData;
            }

            if (!empty($conflicts)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduling conflicts: '.count($conflicts).' shifts could not be updated.',
                    'errors' => $conflicts
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedules updated successfully',
                'data' => $updatedSchedules
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedules',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Bulk remove.
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'shifts' => 'required|array|min:1',
            'shifts.*.id' => 'required|exists:schedules,id',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $data = $validator->validated();
            $deletedSchedules = [];
            $errors = [];
            DB::beginTransaction();

            foreach ($data['shifts'] as $shift) {
                $schedule = Schedule::find($shift['id']);
                if (!$schedule) {
                    $errors[] = 'Schedule not found for id '.$shift['id'];
                    continue;
                }
                $deletedSchedules[] = $schedule->id;
                $schedule->delete();
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Some schedules could not be deleted.',
                    'errors' => $errors
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Schedules deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedules',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Remove the specified schedule.
     */
    public function destroy($id): JsonResponse
    {
        $schedule = Schedule::find($id);
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $schedule->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Schedule deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete schedule',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // HELPER METHODS

    private function groupSchedulesByRole ($schedules) : array
    {
        $grouped = [];

        foreach ($schedules as $schedule) {

            // $unassignedRole = 

            $roleName = $schedule->role ? $schedule->role->role_name : 'Unassigned';
            
            if (!isset($grouped[$roleName])) {
                $grouped[$roleName] = [];
            }
            
            $grouped[$roleName][] = [
                'id' => $schedule->id,
                'employee' => $schedule->employee,
                'role_id' => $schedule->role->id,
                'shift' => $schedule->start_time . ' - ' . $schedule->end_time,
                'duration' => $schedule->duration . ' hours',
                'date' => $schedule->date,
                'start_time' => $schedule->formatted_start_time,
                'end_time' => $schedule->formatted_end_time,
            ];
        }

        return $grouped;
    }
}
