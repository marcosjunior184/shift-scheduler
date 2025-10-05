<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{

    use HasFactory;

    /**
     * Assignable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'role_name', 
        'role_description'
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class, 'role_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'assigned_role');
    }
}