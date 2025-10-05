<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of the roles.
     */
    public function index(): JsonResponse
    {
        try {
            $roles = Role::all();
            return response()->json([
                'success' => true,
                'data' => $roles
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve roles',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create a created role.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'role_name' => 'required|string|max:100|unique:roles',
            'role_description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $role = Role::create($validator->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Role created successfully',
                'data' => $role
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create role',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $role = Role::find($id);
        
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'role_name' => 'sometimes|required|string|max:100|unique:roles,role_name,' . $id,
            'role_description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], Response::Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $role->update($validator->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully',
                'data' => $role
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update role',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified role.
     */
    public function destroy($id): JsonResponse
    {
        $role = Role::find($id);
        
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role not found'
            ], Response::HTTP_NOT_FOUND);
        }

        try {
            // Check if role is being used by any staff
            if ($role->staff()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete role. It is assigned to staff members.'
                ], Response::Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $role->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete role',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}