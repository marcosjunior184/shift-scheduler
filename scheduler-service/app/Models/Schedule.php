<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use App\Models\Schedule;

class Schedule extends Model
{

    use HasFactory;

    const MAX_SHIFT_DURATION_HOURS = 12;

    /**
     * Assignable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'date',
        'start_time',
        'end_time',
        'employee_id',
        'assigned_role'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'employee_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'assigned_role');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    // SCOPES

    /**
     * Scope query to only include schedules for a specific date.
     */
    public function scopeForDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Scope query to only include schedules for a date range.
     */
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope query to only include schedules for specific employee.
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope query to only include schedules for active employees.
     */
    public function scopeActiveEmployees($query)
    {
        return $query->whereHas('employee', function($query) {
            $query->active();
        });
    }

    // HELPER METHODS

    /**
     * Calculate shift duration in hours.
     */
    public function getDurationAttribute()
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        
        return $start->diffInHours($end);
    }

    public function getFormattedStartTimeAttribute()
    {
        return $this->start_time ? date('H:i', strtotime($this->start_time)) : null;
    }

    public function getFormattedEndTimeAttribute()
    {
        return $this->end_time ? date('H:i', strtotime($this->end_time)) : null;
    }

}