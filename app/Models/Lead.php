<?php

namespace App\Models;

use App\Enums\LeadPriority;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reference',
        'name',
        'company',
        'email',
        'phone',
        'alternate_phone',
        'city',
        'source',
        'status',
        'priority',
        'value',
        'shop_id',
        'assigned_to',
        'created_by',
        'next_follow_up_at',
        'last_contacted_at',
        'description',
        'lost_reason',
        'closed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'source' => LeadSource::class,
            'priority' => LeadPriority::class,
            'value' => 'decimal:2',
            'next_follow_up_at' => 'date',
            'last_contacted_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => LeadStatus::New->value,
        'priority' => LeadPriority::Medium->value,
    ];

    /**
     * Columns the listing may be ordered by.
     *
     * Whitelisted because `sort` arrives from the query string and reaches
     * an ORDER BY.
     *
     * @var list<string>
     */
    public const SORTABLE = [
        'reference', 'name', 'company', 'status', 'priority',
        'value', 'next_follow_up_at', 'created_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** @return BelongsTo<User, Lead> */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /** @return BelongsTo<User, Lead> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<Shop, Lead> */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /** @return HasMany<LeadFollowUp> */
    public function followUps(): HasMany
    {
        return $this->hasMany(LeadFollowUp::class)->latest('scheduled_at');
    }

    /** @return HasMany<LeadFollowUp> */
    public function openFollowUps(): HasMany
    {
        return $this->hasMany(LeadFollowUp::class)->whereNull('completed_at');
    }

    /**
     * Logged phone calls, newest first.
     *
     * Separate from followUps(): a follow-up is a planned action on any
     * channel, a call detail is the record of one call that happened.
     *
     * @return HasMany<CallDetail>
     */
    public function callDetails(): HasMany
    {
        return $this->hasMany(CallDetail::class)->latestFirst();
    }

    /**
     * The single most recently logged call.
     *
     * A `latestOfMany` relation rather than `callDetails()->first()` so it
     * can be eager loaded as one aggregated query on the listing page,
     * instead of one extra query per row.
     *
     * @return HasOne<CallDetail>
     */
    public function latestCall(): HasOne
    {
        return $this->hasOne(CallDetail::class)->latestOfMany(['called_date', 'called_time']);
    }

    /** @return HasOne<Quotation> */
    public function quotation(): HasOne
    {
        return $this->hasOne(Quotation::class);
    }

    /** @return HasMany<LeadActivity> */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param  Builder<Lead>  $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // Escape LIKE wildcards so a literal % or _ is not read as a pattern.
        $escaped = '%'.addcslashes($term, '%_\\').'%';

        $query->where(function (Builder $query) use ($escaped) {
            $query->where('reference', 'like', $escaped)
                ->orWhere('name', 'like', $escaped)
                ->orWhere('company', 'like', $escaped)
                ->orWhere('email', 'like', $escaped)
                ->orWhere('phone', 'like', $escaped)
                ->orWhere('city', 'like', $escaped);
        });
    }

    /** @param  Builder<Lead>  $query */
    public function scopeStatus(Builder $query, LeadStatus|string|null $status): void
    {
        if (blank($status)) {
            return;
        }

        $query->where('status', $status instanceof LeadStatus ? $status : LeadStatus::from($status));
    }

    /** @param  Builder<Lead>  $query */
    public function scopePriority(Builder $query, LeadPriority|string|null $priority): void
    {
        if (blank($priority)) {
            return;
        }

        $query->where('priority', $priority instanceof LeadPriority ? $priority : LeadPriority::from($priority));
    }

    /** @param  Builder<Lead>  $query */
    public function scopeSource(Builder $query, LeadSource|string|null $source): void
    {
        if (blank($source)) {
            return;
        }

        $query->where('source', $source instanceof LeadSource ? $source : LeadSource::from($source));
    }

    /** @param  Builder<Lead>  $query */
    public function scopeAssignedTo(Builder $query, int|string|null $userId): void
    {
        if (blank($userId)) {
            return;
        }

        $query->where('assigned_to', (int) $userId);
    }

    /** @param  Builder<Lead>  $query */
    public function scopeForShop(Builder $query, int|string|null $shopId): void
    {
        if (blank($shopId)) {
            return;
        }

        $query->where('shop_id', (int) $shopId);
    }

    /** @param  Builder<Lead>  $query */
    public function scopeOpen(Builder $query): void
    {
        $query->whereIn('status', array_column(LeadStatus::open(), 'value'));
    }

    /**
     * Leads whose next follow-up is today or overdue.
     *
     * @param  Builder<Lead>  $query
     */
    public function scopeDueForFollowUp(Builder $query, ?int $withinDays = null): void
    {
        $query->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', today()->addDays($withinDays ?? 0))
            ->open();
    }

    /** @param  Builder<Lead>  $query */
    public function scopeSorted(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'created_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null
            && $this->assigned_to !== null
            && (int) $this->assigned_to === $user->id;
    }

    public function isOverdue(): bool
    {
        return $this->next_follow_up_at !== null
            && $this->status->isOpen()
            && $this->next_follow_up_at->isPast();
    }

    /**
     * Whole days until the next follow-up. Negative when overdue.
     */
    public function daysUntilFollowUp(): ?int
    {
        return $this->next_follow_up_at
            ? (int) today()->diffInDays($this->next_follow_up_at, false)
            : null;
    }
}
