<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\Role;
use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class ScheduleFactory extends Factory
{
    /**
     * Corresponding model.
     *
     * @var string
     */
    protected $model = Schedule::class;


    /**
     * Model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // Get or create related models
        $employee = Staff::factory()->create();
        $role = Role::factory()->create();

        $date = $this->faker->date('Y-m-d');
        
        // Generate random shift times
        $shiftStart = $this->faker->randomElement(['06:00', '07:00', '08:00', '09:00', '10:00', '14:00', '16:00']);
        $startTime = Carbon::parse($shiftStart);
        $endTime = $startTime->copy()->addHours($this->faker->numberBetween(4, 9));

        return [
            'date' => $date,
            'start_time' => $startTime->format('H:i:s'),
            'end_time' => $endTime->format('H:i:s'),
            'employee_id' => $employee->id,
            'assigned_role' => $role->id,
        ];
    }

    // STATE MODIFIERS

    /**
     * Set for a specific date.
     */
    public function forDate(string $date): Factory
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'date' => $date,
            ];
        });
    }

    /**
     * Assign for a specific employee.
     */
    public function forEmployee(Staff $employee): Factory
    {
        return $this->state(function (array $attributes) use ($employee) {
            return [
                'employee_id' => $employee->id,
            ];
        });
    }


    /**
     * Assign for a specific role.
     */
    public function forRole(Role $role): Factory
    {
        return $this->state(function (array $attributes) use ($role) {
            return [
                'assigned_role' => $role->id,
            ];
        });
    }
}