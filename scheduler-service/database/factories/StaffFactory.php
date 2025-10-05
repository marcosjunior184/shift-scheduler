<?php

namespace Database\Factories;

use App\Models\Staff;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class StaffFactory extends Factory
{
    /**
     * Ccorresponding model.
     *
     * @var string
     */
    protected $model = Staff::class;

    /**
     * Model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        // Get or create a role
        $role = Role::factory()->create();


        $startDate = $date = $this->faker->date('Y-m-d');
        
        return [
            'name' => $this->faker->name(),
            'phone_number' => $this->generatePhoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'role_id' => $role->id,
            'start_date' => $startDate,
            'end_date' => null,
        ];
    }

    // STATE MODIFIERS


    /**
     * Assign for a specific role.
     */
    public function withRole(Role $role): Factory
    {
        return $this->state(function (array $attributes) use ($role) {
            return [
                'role_id' => $role->id,
            ];
        });
    }

    /**
     * Assign for a role by name.
     */
    public function role(string $roleName): Factory
    {
        return $this->state(function (array $attributes) use ($roleName) {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                $role = Role::factory()->create(['name' => $roleName]);
            }
            
            return [
                'role_id' => $role->id,
            ];
        });
    }

    /**
     * Set for a specific start date.
     */
    public function startDate(string $date): Factory
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'start_date' => $date,
            ];
        });
    }

    /**
     * Set for a specific end date.
     */
    public function endDate(string $date): Factory
    {
        return $this->state(function (array $attributes) use ($date) {
            return [
                'end_date' => $date,
            ];
        });
    }

    /**
     * Set specific employment period.
     */
    public function employmentPeriod(string $startDate, string $endDate): Factory
    {
        return $this->state(function (array $attributes) use ($startDate, $endDate) {
            return [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        });
    }

    // HELPERS

    /**
     * Generate a phone number.
     */
    private function generatePhoneNumber(): string
    {
        return $this->faker->numerify('(###) ###-####');
    }

}