<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class StaffController extends Controller
{

    /**
     * Get all staffs with possibility to filter by:
     *  - role 
     *  - active/not active status 
     * 
     * JsonResponse is a collectino of Staff objects with their associated Role.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Staff::with('role');
            
            // Filter by active status
            if ($request->has('active')) {
                if ($request->active === 'true') {
                    $query->active();
                } elseif ($request->active === 'false') {
                    $query->former();
                }
            }
            
            // Filter by role
            if ($request->has('role_id')) {
                $query->where('role_id', $request->role_id);
            }
            
            $staff = $query->get();
            
            return response()->json([
                'success' => true,
                'data' => $staff
            ],  Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve staff',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add a new staff.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:200',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'required|email|max:150|unique:staff',
            'role_id' => 'required|exists:roles,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $staff = Staff::create($validator->validated());

            $freshStaff = Staff::find($staff->id);

            return response()->json([
                'success' => true,
                'message' => 'Staff member created successfully',
                'data' => $staff->load('role')
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create staff member',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get a specified staff member.
     */
    public function show($id): JsonResponse
    {
        try {
            $staff = Staff::with(['role', 'schedules'])->find($id);
            
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff member not found'
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'success' => true,
                'data' => $staff
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve staff member',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified staff member.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $staff = Staff::find($id);
        
        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:200',
            'phone_number' => 'nullable|string|max:20',
            'email' => 'sometimes|required|email|max:150|unique:staff,email,' . $id,
            'role_id' => 'sometimes|required|exists:roles,id',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'nullable|date|after:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $staff->update($validator->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Staff member updated successfully',
                'data' => $staff->load('role')
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update staff member',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified staff member.
     */
    public function destroy($id): JsonResponse
    {
        $staff = Staff::find($id);
        
        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            // Check if staff has schedules
            if ($staff->schedules()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete staff member. They have existing schedules.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $staff->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Staff member deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete staff member',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Terminate a staff member (set end date).
     */
    public function terminate($id): JsonResponse
    {
        $staff = Staff::find($id);
        
        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff member not found'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            $staff->update([
                'end_date' => now()->toDateString()
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Staff member terminated successfully',
                'data' => $staff->load('role')
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to terminate staff member',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}