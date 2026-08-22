<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'title',
        'assigned_to',
        'approved_by',
        'task_type',

        // Daily
        'start_time',
        'end_time',

        // Weekly
        'week_start_day',
        'week_end_day',

        // Monthly
        'monthly_start_date',
        'monthly_end_date',

        // Quarterly
        'quarter',
        'quarter_start_date',
        'quarter_end_date',

        'description',
        'status',
    ];

    protected $casts = [
        'monthly_start_date' => 'date',
        'monthly_end_date' => 'date',
        'quarter_start_date' => 'date',
        'quarter_end_date' => 'date',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}