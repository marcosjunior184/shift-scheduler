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
        Schema::create('staff', function (Blueprint $table) {
            $table->id(); 
            $table->string('name', 200); 
            $table->string('phone_number', 20)->nullable(); 
            $table->string('email', 150)->unique(); 
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict'); 
            $table->date('start_date'); 
            $table->date('end_date')->nullable(); 
            $table->timestamps(); 

            // Add indexes
            $table->index('role_id');
            $table->index('start_date');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
