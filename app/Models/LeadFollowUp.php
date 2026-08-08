<?php

namespace App\Models;

use App\Enums\FollowUpType;
use Database\Factories\LeadFollowUpFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadFollowUp extends Model
{
    /** @use HasFactory<LeadFollowUpFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'notes',
        'scheduled_at',
        'completed_at',
        'outcome',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => FollowUpType::class,
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Lead, LeadFollowUp> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return BelongsTo<User, LeadFollowUp> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param  Builder<LeadFollowUp>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereNull('completed_at');
    }

    /** @param  Builder<LeadFollowUp>  $query */
    public function scopeCompleted(Builder $query): void
    {
        $query->whereNotNull('completed_at');
    }

    /**
     * Scheduled for today or earlier and still not done.
     *
     * @param  Builder<LeadFollowUp>  $query
     */
    public function scopeDue(Builder $query, ?int $withinDays = null): void
    {
        $query->open()
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now()->addDays($withinDays ?? 0)->endOfDay());
    }

    /** @param  Builder<LeadFollowUp>  $query */
    public function scopeForUser(Builder $query, int|string|null $userId): void
    {
        if (blank($userId)) {
            return;
        }

        $query->where('user_id', (int) $userId);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isComplete(): bool
    {
        return $this->completed_at !== null;
    }

    public function isOverdue(): bool
    {
        return ! $this->isComplete()
            && $this->scheduled_at !== null
            && $this->scheduled_at->isPast();
    }
}
