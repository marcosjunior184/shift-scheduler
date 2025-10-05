<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition()
    {
        return [
            'role_name' => $this->faker->unique()->jobTitle,
            'role_description' => $this->faker->sentence,
        ];
    }
    // STATE MODIFIERS

    /**
     * Set for a specific role name.
     */
    public function roleName(string $roleName): Factory
    {
        return $this->state(function (array $attributes) use ($roleName) {
            return [
                'role_name' => $roleName,
            ];
        });
    }

    /**
     * Set for a specific role Description.
     */
    public function roleDescription(string $roleDesc): Factory
    {
        return $this->state(function (array $attributes) use ($roleDesc) {
            return [
                'role_description' => $roleDesc,
            ];
        });
    }
}
