<?php

namespace Tests;

use App\Models\Role;
use App\Models\Staff;
use App\Models\Schedule as Schedule;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Mockery;

class ScheduleControllerTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * Set Up Role and Staff instances for tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure roles and a staff member exist
        $this->role = Role::firstOrCreate(['role_name' => 'server'], ['role_description' => 'Server role']);
        $this->staff = Staff::create([
            'name' => 'Test Staff',
            'email' => 'staff@example.com',
            'phone_number' => '+1000000000',
            'role_id' => $this->role->id,
            'start_date' => '2024-01-01'
        ]);
    }

    /**
     * Test index returns schedules successfully.
     */
    public function test_index_returns_schedules()
    {
        // Create some schedules
        Schedule::factory()->count(2)->create([
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $this->get('/api/schedules')
            ->seeStatusCode(200)
            ->seeJson(['success' => true])
            ->seeJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'start_time',
                        'end_time',
                        'employee_id',
                        'assigned_role',
                        'employee' => [
                            'id',
                            'name',
                            'phone_number',
                            'email',
                            'role_id',
                            'start_date',
                            'end_date',
                            'created_at',
                            'updated_at',
                            'role' => [
                                'id',
                                'role_name',
                                'role_description',
                                'created_at',
                                'updated_at'
                            ]
                        ],
                        'role' => [
                            'id',
                            'role_name',
                            'role_description',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]
            ]);
    }

    /**
     * Test index returns empty list when no schedules exist.
     */
    public function test_index_returns_no_schedules()
    {
        $this->get('/api/schedules')
            ->seeStatusCode(200)
            ->seeJson(['success' => true, 'data' => []])
            ->seeJsonStructure(['success', 'data']);
    }

    /**
     * Test creating a schedule successfully.
     */
    public function test_store_creates_schedule()
    {
        $payload = [
            // Use a fixed date to keep tests deterministic
            "shifts" => [
                [
                    'date' => date('Y-m-d', strtotime('+1 day')),
                    'start_time' => '09:00',
                    'end_time' => '13:00',
                    'employee_id' => $this->staff->id,
                    'assigned_role' => $this->role->id
                ]
            ]
        ];

        $this->post('/api/schedules', $payload)
            ->seeStatusCode(201)
            ->seeJson(['success' => true, 'message' => 'Schedule created successfully']);
    }

    /**
     * Test creating a schedule which contains a conflict (should fail).
     */
    public function test_store_detects_conflict()
    {
        // Create an existing schedule for conflict
        $date = date('Y-m-d', strtotime('+1 day'));
        Schedule::create([
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '14:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        // Overlapping schedule
        $payload = [
            'date' => $date,
            'start_time' => '11:00',
            'end_time' => '12:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ];

        $this->post('/api/schedules', $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false])
            ->seeJsonStructure(['success', 'message']);
    }   

    /**
     * Test creating a schedule with invalid data (should fail).
     */
    public function test_store_rejects_invalid_data()
    {
        $payload = [
            'date' => 'invalid-date',
            'start_time' => '09:00',
            'end_time' => '08:00', 
            'employee_id' => 9999, // non-existent staff
            'assigned_role' => 9999 // non-existent role
        ];

        $this->post('/api/schedules', $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false, 'message' => 'Validation failed'])
            ->seeJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * Test creating a schedule with excessive duration (should fail).
     */
    public function test_store_rejects_excessive_duration()
    {
        $payload = [
            'date' => date('Y-m-d', strtotime('+1 day')),
            'start_time' => '08:00',
            'end_time' => '22:00', // 12 hours, exceeds max 10 hours
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ];

        $this->post('/api/schedules', $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false, 'message' => 'Validation failed'])
            ->seeJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * Test creating a schedule with a past date (should fail).
     */
    public function test_store_rejects_past_date()
    {
        $payload = [
            'date' => '2020-01-01', 
            'start_time' => '09:00',
            'end_time' => '13:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ];

        $this->post('/api/schedules', $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false, 'message' => 'Validation failed'])
            ->seeJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * Test updating a schedule which contains a conflict (should fail).
     */
    public function text_update_rejects_conflict()
    {
        // Create an existing schedule for conflict
        $date = date('Y-m-d', strtotime('+1 day'));
        $existingSchedule = Schedule::create([
            'date' => $date,
            'start_time' => '10:00',
            'end_time' => '14:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        // Create a schedule to update
        $scheduleToUpdate = Schedule::create([
            'date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        // Attempt to update to a conflicting time
        $payload = [
            'date' => $date,
            'start_time' => '11:00',
            'end_time' => '12:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ];

        $this->put("/api/schedules/{$scheduleToUpdate->id}", $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false])
            ->seeJsonStructure(['success', 'message']);
    }

    /**
     * Test updating a schedule with invalid data (should fail).
     */
    function test_update_rejects_invalid_data()
    {
        // Create a schedule to update
        $scheduleToUpdate = Schedule::create([
            'date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $payload = [
            'date' => 'invalid-date',
            'start_time' => '09:00',
            'end_time' => '08:00',
            'employee_id' => 9999, // non-existent staff
            'assigned_role' => 9999 // non-existent role
        ];

        $this->put("/api/schedules/{$scheduleToUpdate->id}", $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false, 'message' => 'Validation failed'])
            ->seeJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * Test updating a schedule with excessive duration (should fail).
     */
    function test_update_rejects_excessive_duration()
    {
        // Create a schedule to update
        $scheduleToUpdate = Schedule::create([
            'date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $payload = [
            'date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '08:00',
            'end_time' => '22:00', 
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ];

        $this->put("/api/schedules/{$scheduleToUpdate->id}", $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false, 'message' => 'Validation failed'])
            ->seeJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * Test updating a schedule with a past date (should fail).
     */
    public function test_update_rejects_past_date()
    {
        // Create a schedule to update
        $scheduleToUpdate = Schedule::create([
            'date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $payload = [
            'date' => '2020-01-01', // past date
            'start_time' => '09:00',
            'end_time' => '13:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ];

        $this->put("/api/schedules/{$scheduleToUpdate->id}", $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false, 'message' => 'Validation failed'])
            ->seeJsonStructure(['success', 'message', 'errors']);
    }

    /**
     * Test updating a non-existing schedule (should fail).
     */
    public function test_update_rejects_nonexistent_schedule()
    {
        $payload = [
            'date' => date('Y-m-d', strtotime('+1 day')),
            'start_time' => '09:00',
            'end_time' => '13:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ];

        $this->put("/api/schedules/9999", $payload) // Assuming 9999 does not exist
            ->seeStatusCode(422)
            ->seeJson(['success' => false, 'message' => 'Schedule not found']);
    }

    /**
     * Test deleting a schedule.
     */
    public function test_delete_removes_schedule()
    {
        // Create a schedule to delete
        $schedule = Schedule::create([
            'date' => date('Y-m-d', strtotime('+2 days')),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $this->delete("/api/schedules/{$schedule->id}")
            ->seeStatusCode(200)
            ->seeJson(['success' => true, 'message' => 'Schedule deleted successfully']);

        // Verify it's deleted
        $this->notSeeInDatabase('schedules', ['id' => $schedule->id]);
    }

    /**
     * Test deleting a non-existent schedule (should fail).
     */
    public function test_delete_rejects_nonexistent_schedule()
    {
        $this->delete("/api/schedules/9999") // Assuming 9999 does not exist
            ->seeStatusCode(422)
            ->seeJson(['success' => false, 'message' => 'Schedule not found']);
    }

    /**
     * Test delete handles exceptions gracefully and doesn't delete the entry.
     * 
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function test_delete_rejects_on_exception()
    {
        // Mock Schedule model to throw exception on delete
        // Create a partial mock of an existing instance

        $mock= Mockery::mock('override', 'alias:\App\Models\Schedule');

        $mock->shouldReceive('find')->andReturnSelf($mock);
        $mock->shouldReceive('delete')->andThrow(new \Exception('DB error'));

        $this->app->instance('App\Models\Schedule', $mock);

        $this->delete("/api/schedules/1") // ID doesn't matter due to mocking
            ->seeStatusCode(500)
            ->seeJson(['success' => false, 'message' => 'Failed to delete schedule']);

        Mockery::close();
    }

    /**
     * Test bulk update of schedules via updateMultiple endpoint.
     */
    public function test_updateMultiple_updates_schedules_successfully()
    {
        // Create two schedules to update
        $date1 = date('Y-m-d', strtotime('+2 days'));
        $date2 = date('Y-m-d', strtotime('+3 days'));

        $s1 = Schedule::create([
            'date' => $date1,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $s2 = Schedule::create([
            'date' => $date2,
            'start_time' => '12:00',
            'end_time' => '16:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        // Prepare update payload (non-conflicting changes)
        $payload = [
            'shifts' => [
                [
                    'id' => $s1->id,
                    'date' => $date1,
                    'start_time' => '08:00',
                    'end_time' => '10:00',
                    'employee_id' => $this->staff->id,
                    'assigned_role' => $this->role->id
                ],
                [
                    'id' => $s2->id,
                    'date' => $date2,
                    'start_time' => '13:00',
                    'end_time' => '17:00',
                    'employee_id' => $this->staff->id,
                    'assigned_role' => $this->role->id
                ]
            ]
        ];

        $this->put('/api/schedules', $payload)
            ->seeStatusCode(200)
            ->seeJson(['success' => true, 'message' => 'Schedules updated successfully'])
            ->seeJsonStructure(['success', 'message', 'data']);

        // Verify changes in database
        $this->seeInDatabase('schedules', ['id' => $s1->id, 'start_time' => '08:00', 'end_time' => '10:00']);
        $this->seeInDatabase('schedules', ['id' => $s2->id, 'start_time' => '13:00', 'end_time' => '17:00']);
    }

    /**
     * Test bulk update rejects conflicting updates (should fail).
     */
    public function test_updateMultiple_rejects_conflicting_updates()
    {
        // Create two schedules to update
        $date1 = date('Y-m-d', strtotime('+2 days'));
        $date2 = date('Y-m-d', strtotime('+2 days'));

        $s1 = Schedule::create([
            'date' => $date1,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $s2 = Schedule::create([
            'date' => $date2,
            'start_time' => '12:00',
            'end_time' => '16:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        // Prepare update payload with conflicting changes
        $payload = [
            'shifts' => [
                [
                    'id' => $s1->id,
                    'date' => $date1,
                    'start_time' => '10:00', // Overlaps with s2's original time
                    'end_time' => '14:00',
                    'employee_id' => $this->staff->id,
                    'assigned_role' => $this->role->id
                ],
                [
                    'id' => $s2->id,
                    'date' => $date2,
                    'start_time' => '13:00',
                    'end_time' => '17:00',
                    'employee_id' => $this->staff->id,
                    'assigned_role' => $this->role->id
                ]
            ]
        ];

        $this->put('/api/schedules', $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false])
            ->seeJsonStructure(['success', 'message', 'errors']);

        // Verify no changes in database
        $this->seeInDatabase('schedules', ['id' => $s1->id, 'start_time' => '09:00', 'end_time' => '11:00']);
        $this->seeInDatabase('schedules', ['id' => $s2->id, 'start_time' => '12:00', 'end_time' => '16:00']);
    }

    /**
     * Test bulk delete via destroyMultiple endpoint.
     */
    public function test_destroyMultiple_deletes_schedules_successfully()
    {
        $date1 = date('Y-m-d', strtotime('+2 days'));
        $date2 = date('Y-m-d', strtotime('+3 days'));

        $s1 = Schedule::create([
            'date' => $date1,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $s2 = Schedule::create([
            'date' => $date2,
            'start_time' => '12:00',
            'end_time' => '16:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $payload = ['shifts' => [ ['id' => $s1->id], ['id' => $s2->id] ]];

        $this->delete('/api/schedules', $payload)
            ->seeStatusCode(200)
            ->seeJson(['success' => true, 'message' => 'Schedules deleted successfully']);

        $this->notSeeInDatabase('schedules', ['id' => $s1->id]);
        $this->notSeeInDatabase('schedules', ['id' => $s2->id]);
    }

    public function test_delete_rejects_nonexistent_schedules_in_bulk()
    {
        $date1 = date('Y-m-d', strtotime('+2 days'));

        $s1 = Schedule::create([
            'date' => $date1,
            'start_time' => '09:00',
            'end_time' => '11:00',
            'employee_id' => $this->staff->id,
            'assigned_role' => $this->role->id
        ]);

        $payload = ['shifts' => [ ['id' => $s1->id], ['id' => 8888] ]]; // Assuming these IDs do not exist

        $this->delete('/api/schedules', $payload)
            ->seeStatusCode(422)
            ->seeJson(['success' => false])
            ->seeJsonStructure(['success', 'message', 'errors']);

        $this->seeInDatabase('schedules', ['id' => $s1->id]);
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
