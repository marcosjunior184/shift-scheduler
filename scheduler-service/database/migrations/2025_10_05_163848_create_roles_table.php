<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('role_name', 100)->unique(); // Role name with uniqueness constraint
            $table->text('role_description')->nullable(); // Role description, optional
            $table->timestamps(); // created_at and updated_at timestamps
        });


        $this->seedDefaultRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }

    /**
     * Seed default roles into the roles table.
     *
     * @return void
     */
    private function seedDefaultRoles()
    {
        // This will run when the migration is executed
        $defaultRoles = [
            [
                'role_name' => 'manager',
                'role_description' => 'Restaurant manager with overall operational responsibility, staff management, and financial oversight'
            ],
            [
                'role_name' => 'cook', 
                'role_description' => 'Prepares and cooks menu items, maintains food quality standards, and manages kitchen station'
            ],
            [
                'role_name' => 'server',
                'role_description' => 'Takes customer orders, serves food and beverages, provides excellent customer service'
            ],
            [
                'role_name' => 'kitchen_staff',
                'role_description' => 'Supports kitchen operations including food prep, cleaning, stocking, and assisting cooks'
            ]
        ];

        foreach ($defaultRoles as $role) {
            // Avoid duplicate insert when migrations are re-run during tests
            $exists = DB::table('roles')->where('role_name', $role['role_name'])->exists();
            if (! $exists) {
                DB::table('roles')->insert([
                    'role_name' => $role['role_name'],
                    'role_description' => $role['role_description'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            }
        }
    }
};
