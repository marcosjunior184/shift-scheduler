<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    Schema::create('schedules', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            
            // Date of the shift (e.g., 2025-09-15)
            $table->date('date');
            
            $table->foreignId('schedule_id')
                  ->constrained('schedules')
                  ->onDelete('cascade');
                  
            // Shift start and end times
            $table->time('start_time'); 
            $table->time('end_time');   
            
            // Foreign key to staff table
            $table->foreignId('employee_id')
                  ->constrained('staff')
                  ->onDelete('cascade');
            
            // Assigned role for this shift (can be different from staff's main role)
            $table->foreignId('assigned_role')
                  ->nullable()
                  ->constrained('roles')
                  ->onDelete('set null');
            
            $table->timestamps(); // created_at and updated_at
            
            // Add indexes for better performance
            $table->index('date');
            $table->index('start_time');
            $table->index('end_time');
            $table->index('employee_id');
            $table->index('assigned_role');
            
            // Prevent duplicate schedules for same employee on same date
            $table->unique(['employee_id', 'date']);
            
            // Composite index for date range queries
            $table->index(['date', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    Schema::dropIfExists('schedules');
    }
};
