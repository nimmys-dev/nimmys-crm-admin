<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Task extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'title',
        'assigned_to',
        'approved_by',
        'task_type',
        'repeat_mode',

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
        'remarks',
        'yearly_start_date',
        'yearly_end_date'
    ];

    protected $casts = [
        'monthly_start_date' => 'date',
        'monthly_end_date' => 'date',
        'quarter_start_date' => 'date',
        'quarter_end_date' => 'date',
        'yearly_start_date' => 'date',
        'yearly_end_date' => 'date',
        'repeat_mode' => 'boolean',
    ];

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }


    public function updateAutomaticStatus(): void
    {
        // Don't overwrite final statuses
        if (in_array($this->status, [
            'completed',
            'approved',
            'closed',
        ])) {
            return;
        }

        $now = now();

        $start = null;
        $end = null;

        switch ($this->task_type) {

            case 'daily':

                $start = Carbon::today()
                    ->setTimeFromTimeString($this->start_time);

                $end = Carbon::today()
                    ->setTimeFromTimeString($this->end_time);

                break;


            case 'weekly':

                // If you have actual weekly start/end dates,
                // use those here.
                break;


            case 'monthly':

                $start = $this->monthly_start_date
                    ? Carbon::parse($this->monthly_start_date)->startOfDay()
                    : null;

                $end = $this->monthly_end_date
                    ? Carbon::parse($this->monthly_end_date)->endOfDay()
                    : null;

                break;


            case 'quarterly':

                $start = $this->quarter_start_date
                    ? Carbon::parse($this->quarter_start_date)->startOfDay()
                    : null;

                $end = $this->quarter_end_date
                    ? Carbon::parse($this->quarter_end_date)->endOfDay()
                    : null;

                break;
        }


        if (!$start || !$end) {
            return;
        }


        if ($now->lt($start)) {

            $this->updateQuietly([
                'status' => 'upcoming',
            ]);

        } elseif ($now->between($start, $end)) {

            $this->updateQuietly([
                'status' => 'ongoing',
            ]);

        } elseif ($now->gt($end)) {

            $this->updateQuietly([
                'status' => 'overdue',
            ]);
        }
    }

    public function quarters(): HasMany
    {
        return $this->hasMany(TaskQuarter::class);
    }
}