<?php

namespace Tests;

use App\Models\Role;
use App\Models\Staff;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class StaffControllerTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Set Up Role instance for tests.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a role for testing
        $this->role = Role::firstOrCreate([
            'role_name' => 'server'
        ], [
            'role_description' => 'Server role'
        ]);

    }

    /**
     * Test creating a new staff member.
     */
    public function test_can_create_staff()
    {
        $role = Role::find(1);
        $staffData = [
            'name' => 'Test Staff',
            'email' => 'test@restaurant.com',
            'phone_number' => '+1234567890',
            'role_id' => $role->id,
            'start_date' => '2024-01-15'
        ];

        $this->post('/api/staff', $staffData)
            ->seeStatusCode(201)
            ->seeJson([
                'success' => true,
                'message' => 'Staff member created successfully'
            ]);
    }

    /**
     * Test creating a staff member with missing required fields (should fail).
     */
    public function test_cannot_create_staff_with_duplicate_email()
    {
        Staff::create([
            'name' => 'Existing Staff',
            'email' => 'existing@restaurant.com',
            'role_id' => $this->role->id,
            'start_date' => '2024-01-15'
        ]);

        $staffData = [
            'name' => 'New Staff',
            'email' => 'existing@restaurant.com', // Duplicate email
            'role_id' => $this->role->id,
            'start_date' => '2024-01-15'
        ];

        $this->post('/api/staff', $staffData)
            ->seeStatusCode(422);
    }
    
    /**
     * Test retrieving staff members.
     */
    public function test_can_get_staff()
    {

        Staff::factory()->count(3)->create();

        $this->get('/api/staff')
            ->seeStatusCode(200)
            ->seeJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'role_id',
                        'start_date',
                        'end_date'
                    ]
                ]
            ]);
    }

    /**
     * Test updating a staff member.
     */
    public function test_can_update_staff()
    {
        $staff = Staff::factory()->create([
            'role_id' => $this->role->id
        ]);

        $updateData = [ 'name' => 'Updated Name'];

        $this->put("/api/staff/{$staff->id}", $updateData)
            ->seeStatusCode(200)
            ->seeJson([
                'success' => true,
                'message' => 'Staff member updated successfully',
            ])
            ->seeInDatabase('staff', [
                'id' => $staff->id,
                'name' => 'Updated Name'
            ]);
    }

    /**
     * Test updating a non-existent staff member (should fail).
     */
    public function test_cannot_update_nonexistent_staff()
    {
        $updateData = [
            'name' => 'Updated Name'
        ];

        $this->put("/api/staff/9999", $updateData) // Assuming 9999 does not exist
            ->seeStatusCode(422)
            ->seeJson([
                'success' => false,
                'message' => 'Staff member not found'
            ]);
    }

    /**
     * Test updating a staff member with invalid data (should fail).
     */
    public function test_cannot_update_staff_with_invalid_data()
    {
        $staff = Staff::factory()->create([
            'role_id' => $this->role->id
        ]);

        $updateData = [
            'email' => 'not-an-email'
        ];

        $this->put("/api/staff/{$staff->id}", $updateData)
            ->seeStatusCode(422);
    }

    /**
     * Test deleting a staff member.
     */
    public function test_can_delete_staff()
    {
        $staff = Staff::factory()->create([
            'role_id' => $this->role->id
        ]);

        $this->delete("/api/staff/{$staff->id}")
            ->seeStatusCode(200)
            ->seeJson([
                'success' => true,
                'message' => 'Staff member deleted successfully'
            ])
            ->notSeeInDatabase('staff', [
                'id' => $staff->id
            ]);
    }

    /**
     * Test deleting a non-existent staff member (should fail).
     */
    public function test_cannot_delete_nonexistent_staff()
    {
        $this->delete("/api/staff/9999") // Assuming 9999 does not exist
            ->seeStatusCode(422)
            ->seeJson([
                'success' => false,
                'message' => 'Staff member not found'
            ]);
    }

    /**
     * Test terminating a staff member.
     */
    public function test_can_terminate_staff()
    {
        $staff = Staff::factory()->create([
            'role_id' => $this->role->id,
            'end_date' => null
        ]);

        $terminateData = [
            'end_date' => '2024-12-31'
        ];

        $this->put("/api/staff/{$staff->id}/terminate", $terminateData)
            ->seeStatusCode(200)
            ->seeJson([
                'success' => true,
                'message' => 'Staff member terminated successfully',
            ])
            ->seeInDatabase('staff', [
                'id' => $staff->id,
                'end_date' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * Test terminating a non-existent staff member (should fail).
     */
    public function test_cannot_terminate_nonexistent_staff()
    {
        $terminateData = [
            'end_date' => '2024-12-31'
        ];

        $this->put("/api/staff/9999/terminate", $terminateData) // Assuming 9999 does not exist
            ->seeStatusCode(422)
            ->seeJson([
                'success' => false,
                'message' => 'Staff member not found'
            ]);
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        // Disable foreign key constraints before dropping tables
        if (\DB::connection()->getDriverName() === 'sqlite') {
            \DB::statement('PRAGMA foreign_keys = OFF');
        }
        
        parent::tearDown();
    }
            
}