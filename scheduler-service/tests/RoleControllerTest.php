<?php

namespace Tests;

use App\Models\Role;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Test retrieving all roles.
     */
    public function test_can_get_all_roles()
    {
        // Create test roles
        Role::factory()->count(3)->create();

        $this->get('/api/roles')
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'role_name',
                        'role_description',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    /**
     * Test creating a new role.
     */
    public function test_can_create_role()
    {
        $roleData = [
            'role_name' => 'test_role',
            'role_description' => 'Test role description'
        ];

        $this->post('/api/roles', $roleData)
            ->seeStatusCode(201)
            ->seeJson([
                'success' => true,
                'message' => 'Role created successfully'
            ])
            ->seeInDatabase('roles', $roleData);
    }

    /**
     * Test creating a duplicate role (should fail).
     */
    public function test_cannot_create_duplicate_role()
    {
        Role::create([
            'role_name' => 'existing_role',
            'role_description' => 'Existing role'
        ]);

        $roleData = [
            'role_name' => 'existing_role',
            'role_description' => 'Duplicate role'
        ];

        $this->post('/api/roles', $roleData)
            ->seeStatusCode(422);
    }

    /**
     * Test creating a role with invalid data (should fail).
     */
    public function test_cannot_create_role_with_invalid_data()
    {
        $roleData = [
            'role_name' => '', // Invalid: required field
            'role_description' => 'Test role description'
        ];

        $this->post('/api/roles', $roleData)
            ->seeStatusCode(422);
    }

    /**
     * Test updating an existing role.
     */
    public function test_can_update_role()
    {
        $role = Role::create([
            'role_name' => 'old_role',
            'role_description' => 'Old description'
        ]);

        $updateData = [
            'role_name' => 'updated_role',
            'role_description' => 'Updated description'
        ];

        $this->put("/api/roles/{$role->id}", $updateData)
            ->seeStatusCode(200)
            ->seeJson([
                'success' => true,
                'message' => 'Role updated successfully'
            ])
            ->seeInDatabase('roles', array_merge(['id' => $role->id], $updateData));
    }

    /**
     * Test updating a non-existent role (should fail).
     */
    public function test_cannot_update_nonexistent_role()
    {
        $updateData = [
            'role_name' => 'nonexistent_role',
            'role_description' => 'Nonexistent description'
        ];

        $this->put("/api/roles/9999", $updateData) // Assuming 9999 does not exist
            ->seeStatusCode(422)
            ->seeJson([
                'success' => false,
                'message' => 'Role not found'
            ]);
    }

    /**
     * Test updating a role with invalid data (should fail).
     */
    public function test_cannot_update_role_with_invalid_data()
    {
        $role = Role::create([
            'role_name' => 'valid_role',
            'role_description' => 'Valid description'
        ]);

        $updateData = [
            'role_name' => '', // Invalid: required field
            'role_description' => 'Updated description'
        ];

        $this->put("/api/roles/{$role->id}", $updateData)
            ->seeStatusCode(422);
    }

    /**
     * Test deleting an existing role.
     */
    public function test_can_delete_role()
    {
        $role = Role::create([
            'role_name' => 'deletable_role',
            'role_description' => 'Deletable description'
        ]);

        $this->delete("/api/roles/{$role->id}")
            ->seeStatusCode(200)
            ->seeJson([
                'success' => true,
                'message' => 'Role deleted successfully'
            ])
            ->notSeeInDatabase('roles', ['id' => $role->id]);
    }

    /**
     * Test deleting a non-existent role (should fail).
     */
    public function test_cannot_delete_nonexistent_role()
    {
        $this->delete("/api/roles/9999") // Assuming 9999 does not exist
            ->seeStatusCode(404)
            ->seeJson([
                'success' => false,
                'message' => 'Role not found'
            ]);
    }
}
