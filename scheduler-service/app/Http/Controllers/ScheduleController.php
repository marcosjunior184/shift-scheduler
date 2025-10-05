<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
            
            $groupedSchedules = $this->groupSchedulesByRole($schedules);

            return response()->json([
                'success' => true,
                'data' => $groupedSchedules
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
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'employee_id' => 'required|exists:staff,id',
            'assigned_role' => 'nullable|exists:roles,id'
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
            
            if (Schedule::where('employee_id', $data['employee_id'])->whereDate('date', $data['date'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduling conflict: Employee already has a shift on this date'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            // Check for compflicting shifts
            $conflict = Schedule::where('employee_id', $data['employee_id'])
                ->where('date', $data['date'])
                ->where(function($query) use ($data) {
                    // Check for start_time in between another shift
                    $query->whereBetween('start_time', [$data['start_time'], $data['end_time']])
                            // Check for end_time in between another shift
                          ->orWhereBetween('end_time', [$data['start_time'], $data['end_time']])
                          // Check for another shift encompassing the new shift
                          ->orWhere(function($q) use ($data) {
                              $q->where('start_time', '<=', $data['start_time'])
                                ->where('end_time', '>=', $data['end_time']);
                          });
                })
                ->exists();
            
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Scheduling conflict: Employee already has a shift during this time'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
            
            $schedule = Schedule::create($data);
            
            return response()->json([
                'success' => true,
                'message' => 'Schedule created successfully',
                'data' => $schedule->load(['employee.role', 'role'])
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create schedule',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // /**
    //  * Get a specified schedule.
    //  */
    // public function show($id): JsonResponse
    // {
    //     try {
    //         $schedule = Schedule::with(['employee.role', 'role'])->find($id);
            
    //         if (!$schedule) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Schedule not found'
    //             ], ScheduleResponse::HTTP_NOT_FOUND);
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'data' => $schedule
    //         ], Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Failed to retrieve schedule',
    //             'error' => $e->getMessage()
    //         ], Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

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
            ], ScheduleResponse::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'start_time' => 'sometimes|required|date_format:H:i',
            'end_time' => 'sometimes|required|date_format:H:i|after:start_time',
            'employee_id' => 'sometimes|required|exists:staff,id',
            'assigned_role' => 'nullable|exists:roles,id'
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
            
            if (isset($data['employee_id']) || isset($data['date']) || 
                isset($data['start_time']) || isset($data['end_time'])) {

                // Get updated field
                $employeeId = $data['employee_id'] ?? $schedule->employee_id;
                $date = $data['date'] ?? $schedule->date;
                $startTime = $data['start_time'] ?? $schedule->start_time;
                $endTime = $data['end_time'] ?? $schedule->end_time;
                
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
                'data' => $schedule->load(['employee.role', 'role'])
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
     * Remove the specified schedule.
     */
    public function destroy($id): JsonResponse
    {
        $schedule = Schedule::find($id);
        
        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], ScheduleResponse::HTTP_NOT_FOUND);
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

            $unassignedRole = 

            $roleName = $schedule->role ? $schedule->role->role_name : 'Unassigned';
            
            if (!isset($grouped[$roleName])) {
                $grouped[$roleName] = [];
            }
            
            $grouped[$roleName][] = [
                'id' => $schedule->id,
                'employee' => $schedule->employee,
                'role' => $schedule->role->role_name,
                'shift' => $schedule->start_time . ' - ' . $schedule->end_time,
                'duration' => $schedule->duration . ' hours',
                'date' => $schedule->date,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ];
        }

        return $grouped;
    }
}
