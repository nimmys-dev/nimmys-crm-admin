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


    // public function updateAutomaticStatus(): void
    // {
    //     // Don't overwrite final statuses
    //     if (in_array($this->status, [
    //         'completed',
    //         'approved',
    //         'closed',
    //     ])) {
    //         return;
    //     }

    //     $now = now();

    //     $start = null;
    //     $end = null;

    //     switch ($this->task_type) {

    //         case 'daily':

    //             $start = Carbon::today()
    //                 ->setTimeFromTimeString($this->start_time);

    //             $end = Carbon::today()
    //                 ->setTimeFromTimeString($this->end_time);

    //             break;


    //         case 'weekly':

    //             // If you have actual weekly start/end dates,
    //             // use those here.
    //             break;


    //         case 'monthly':

    //             $start = $this->monthly_start_date
    //                 ? Carbon::parse($this->monthly_start_date)->startOfDay()
    //                 : null;

    //             $end = $this->monthly_end_date
    //                 ? Carbon::parse($this->monthly_end_date)->endOfDay()
    //                 : null;

    //             break;


    //         case 'quarterly':

    //             $start = $this->quarter_start_date
    //                 ? Carbon::parse($this->quarter_start_date)->startOfDay()
    //                 : null;

    //             $end = $this->quarter_end_date
    //                 ? Carbon::parse($this->quarter_end_date)->endOfDay()
    //                 : null;

    //             break;
    //     }


    //     if (!$start || !$end) {
    //         return;
    //     }


    //     if ($now->lt($start)) {

    //         $this->updateQuietly([
    //             'status' => 'upcoming',
    //         ]);

    //     } elseif ($now->between($start, $end)) {

    //         $this->updateQuietly([
    //             'status' => 'ongoing',
    //         ]);

    //     } elseif ($now->gt($end)) {

    //         $this->updateQuietly([
    //             'status' => 'overdue',
    //         ]);
    //     }
    // }

    // public function updateAutomaticStatus(): void
    // {
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Approval Pending should NOT be changed automatically
    //     |--------------------------------------------------------------------------
    //     */

    //     if ($this->status === 'approval_pending') {
    //         return;
    //     }

    //     $now = now();

    //     $start = null;
    //     $end = null;

    //     switch ($this->task_type) {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Daily
    //         |--------------------------------------------------------------------------
    //         */

    //         case 'daily':

    //             if (!$this->start_time || !$this->end_time) {
    //                 return;
    //             }

    //             $start = Carbon::today()
    //                 ->setTimeFromTimeString($this->start_time);

    //             $end = Carbon::today()
    //                 ->setTimeFromTimeString($this->end_time);

    //             break;


    //         /*
    //         |--------------------------------------------------------------------------
    //         | Monthly
    //         |--------------------------------------------------------------------------
    //         */

    //         case 'monthly':

    //             if (!$this->monthly_start_date || !$this->monthly_end_date) {
    //                 return;
    //             }

    //             $start = Carbon::parse($this->monthly_start_date)
    //                 ->startOfDay();

    //             $end = Carbon::parse($this->monthly_end_date)
    //                 ->endOfDay();

    //             break;


    //         /*
    //         |--------------------------------------------------------------------------
    //         | Quarterly
    //         |--------------------------------------------------------------------------
    //         */

    //         case 'quarterly':

    //             if (!$this->quarter_start_date || !$this->quarter_end_date) {
    //                 return;
    //             }

    //             $start = Carbon::parse($this->quarter_start_date)
    //                 ->startOfDay();

    //             $end = Carbon::parse($this->quarter_end_date)
    //                 ->endOfDay();

    //             break;


    //         /*
    //         |--------------------------------------------------------------------------
    //         | Yearly
    //         |--------------------------------------------------------------------------
    //         */

    //         case 'yearly':

    //             if (!$this->yearly_start_date || !$this->yearly_end_date) {
    //                 return;
    //             }

    //             $start = Carbon::parse($this->yearly_start_date)
    //                 ->startOfDay();

    //             $end = Carbon::parse($this->yearly_end_date)
    //                 ->endOfDay();

    //             break;
    //     }

    //     if (!$start || !$end) {
    //         return;
    //     }


    //     /*
    //     |--------------------------------------------------------------------------
    //     | Automatically update status
    //     |--------------------------------------------------------------------------
    //     */

    //     if ($now->lt($start)) {

    //         // Start time/date not reached
    //         $newStatus = 'upcoming';

    //     } elseif ($now->lt($start)) {

    //         // Keep pending until start date/time
    //         $newStatus = 'pending';

    //     } elseif ($now->between($start, $end)) {

    //         // Currently running
    //         $newStatus = 'ongoing';

    //     } else {

    //         // End time/date passed
    //         $newStatus = 'overdue';
    //     }


    //     if ($this->status !== $newStatus) {
    //         $this->updateQuietly([
    //             'status' => $newStatus,
    //         ]);
    //     }
    // }

    // public function updateAutomaticStatus(): void
    // {
    //     /*
    //     |--------------------------------------------------------------------------
    //     | Approval Pending should NOT be changed automatically
    //     |--------------------------------------------------------------------------
    //     */

    //     if ($this->status === 'approval_pending') {
    //         return;
    //     }

    //     $now = now();

    //     $start = null;
    //     $end = null;

    //     switch ($this->task_type) {

    //         /*
    //         |--------------------------------------------------------------------------
    //         | Daily
    //         |--------------------------------------------------------------------------
    //         */

    //         case 'daily':

    //             if (!$this->start_time || !$this->end_time) {
    //                 return;
    //             }

    //             $start = Carbon::today()
    //                 ->setTimeFromTimeString($this->start_time);

    //             $end = Carbon::today()
    //                 ->setTimeFromTimeString($this->end_time);

    //             break;


    //         /*
    //         |--------------------------------------------------------------------------
    //         | Monthly
    //         |--------------------------------------------------------------------------
    //         */

    //         case 'monthly':

    //             if (!$this->monthly_start_date || !$this->monthly_end_date) {
    //                 return;
    //             }

    //             $start = Carbon::parse($this->monthly_start_date)
    //                 ->startOfDay();

    //             $end = Carbon::parse($this->monthly_end_date)
    //                 ->endOfDay();

    //             break;


    //         /*
    //         |--------------------------------------------------------------------------
    //         | Quarterly
    //         |--------------------------------------------------------------------------
    //         */

    //         case 'quarterly':

    //             if (!$this->quarter_start_date || !$this->quarter_end_date) {
    //                 return;
    //             }

    //             $start = Carbon::parse($this->quarter_start_date)
    //                 ->startOfDay();

    //             $end = Carbon::parse($this->quarter_end_date)
    //                 ->endOfDay();

    //             break;


    //         /*
    //         |--------------------------------------------------------------------------
    //         | Yearly
    //         |--------------------------------------------------------------------------
    //         */

    //         case 'yearly':

    //             if (!$this->yearly_start_date || !$this->yearly_end_date) {
    //                 return;
    //             }

    //             $start = Carbon::parse($this->yearly_start_date)
    //                 ->startOfDay();

    //             $end = Carbon::parse($this->yearly_end_date)
    //                 ->endOfDay();

    //             break;
    //     }

    //     if (!$start || !$end) {
    //         return;
    //     }


    //     /*
    //     |--------------------------------------------------------------------------
    //     | Automatically update status
    //     |--------------------------------------------------------------------------
    //     */

    //     $today = Carbon::today();

    //     if ($now->lt($start)) {

    //         // Future date
    //         if ($start->isAfter($today->endOfDay())) {

    //             $newStatus = 'upcoming';

    //         } else {

    //             // Today but start time not reached
    //             $newStatus = 'pending';
    //         }

    //     } elseif ($now->between($start, $end)) {

    //         // Start time reached
    //         $newStatus = 'ongoing';

    //     } else {

    //         // End time passed
    //         $newStatus = 'overdue';
    //     }

    //     if ($this->status !== $newStatus) {
    //         $this->updateQuietly([
    //             'status' => $newStatus,
    //         ]);
    //     }
    // }

    public function updateAutomaticStatus(): void
    {
        

        if (in_array($this->status, [
            'completed',
            'approval_pending',
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

                if (!$this->start_time || !$this->end_time) {
                    return;
                }

                $start = Carbon::today()
                    ->setTimeFromTimeString($this->start_time);

                $end = Carbon::today()
                    ->setTimeFromTimeString($this->end_time);

                break;

            case 'monthly':

                if (!$this->monthly_start_date || !$this->monthly_end_date) {
                    return;
                }

                $start = Carbon::parse($this->monthly_start_date)
                    ->startOfDay();

                $end = Carbon::parse($this->monthly_end_date)
                    ->endOfDay();

                break;

            case 'quarterly':

                if (!$this->quarter_start_date || !$this->quarter_end_date) {
                    return;
                }

                $start = Carbon::parse($this->quarter_start_date)
                    ->startOfDay();

                $end = Carbon::parse($this->quarter_end_date)
                    ->endOfDay();

                break;

            case 'yearly':

                if (!$this->yearly_start_date || !$this->yearly_end_date) {
                    return;
                }

                $start = Carbon::parse($this->yearly_start_date)
                    ->startOfDay();

                $end = Carbon::parse($this->yearly_end_date)
                    ->endOfDay();

                break;

            default:
                return;
        }

        if (!$start || !$end) {
            return;
        }

        $today = Carbon::today();

        if ($now->lt($start)) {

            // Future date
            if ($start->isAfter($today->copy()->endOfDay())) {

                $newStatus = 'upcoming';

            } else {

                // Today but start time not reached
                $newStatus = 'pending';
            }

        } elseif ($now->between($start, $end)) {

            // Task is currently running
            $newStatus = 'ongoing';

        } else {

            // Task end date/time has passed
            $newStatus = 'overdue';
        }


        if ($this->status !== $newStatus) {

            $this->updateQuietly([
                'status' => $newStatus,
            ]);
        }
    }


   
    public function quarters(): HasMany
    {
        return $this->hasMany(TaskQuarter::class);
    }
}