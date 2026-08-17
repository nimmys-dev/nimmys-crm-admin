<?php

namespace App\Models;

use App\Enums\CallStatus;
use App\Enums\FollowUpUrgency;
use Database\Factories\CallDetailFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * One logged phone call against a lead.
 *
 * Named CallDetail to match the module vocabulary, with the table named
 * lead_call_details to keep it grouped with the other lead-owned tables.
 */
class CallDetail extends Model
{
    /** @use HasFactory<CallDetailFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'lead_call_details';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'lead_id',
        'call_status',
        'remarks',
        'called_by',
        'called_date',
        'called_time',
        'next_followup_date',
        'duration',
        'interest',
        'reason',
        'is_item_sold',
        'invoice_number',
        'invoice_file_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'call_status' => CallStatus::class,
            'called_date' => 'date',
            'next_followup_date' => 'date',
            'duration' => 'integer',
            'interest' => 'boolean',
            'is_item_sold' => 'boolean',
        ];
    }

    /**
     * Columns the call history may be ordered by.
     *
     * @var list<string>
     */
    public const SORTABLE = ['called_date', 'call_status', 'next_followup_date', 'created_at'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** @return BelongsTo<Lead, CallDetail> */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /** @return BelongsTo<User, CallDetail> */
    public function caller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Newest call first. Date and time are separate columns, so both are
     * needed — ordering by date alone shuffles same-day calls.
     *
     * @param  Builder<CallDetail>  $query
     */
    public function scopeLatestFirst(Builder $query): void
    {
        $query->orderByDesc('called_date')->orderByDesc('called_time');
    }

    /** @param  Builder<CallDetail>  $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // Escape LIKE wildcards so a literal % or _ is not read as a pattern.
        $escaped = '%'.addcslashes($term, '%_\\').'%';

        $query->where(function (Builder $query) use ($escaped) {
            $query->where('remarks', 'like', $escaped)
                ->orWhere('reason', 'like', $escaped)
                ->orWhere('invoice_number', 'like', $escaped)
                ->orWhereHas('caller', fn (Builder $q) => $q->where('name', 'like', $escaped));
        });
    }

    /** @param  Builder<CallDetail>  $query */
    public function scopeStatus(Builder $query, CallStatus|string|null $status): void
    {
        if (blank($status)) {
            return;
        }

        $query->where('call_status', $status instanceof CallStatus ? $status : CallStatus::from($status));
    }

    /** @param  Builder<CallDetail>  $query */
    public function scopeCalledBy(Builder $query, int|string|null $userId): void
    {
        if (blank($userId)) {
            return;
        }

        $query->where('called_by', (int) $userId);
    }

    /**
     * Calls with a follow-up still ahead of, or on, a given day.
     *
     * @param  Builder<CallDetail>  $query
     */
    public function scopePendingFollowUp(Builder $query, ?int $withinDays = null): void
    {
        $query->whereNotNull('next_followup_date')
            ->where('next_followup_date', '<=', today()->addDays($withinDays ?? 0));
    }

    /** @param  Builder<CallDetail>  $query */
    public function scopeOnDate(Builder $query, Carbon $date): void
    {
        $query->whereDate('called_date', $date);
    }

    /** @param  Builder<CallDetail>  $query */
    public function scopeSorted(Builder $query, ?string $column, ?string $direction): void
    {
        if (! in_array($column, self::SORTABLE, true)) {
            $query->latestFirst();

            return;
        }

        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isAnswered(): bool
    {
        return $this->call_status === CallStatus::Answered;
    }

    public function isNotAnswered(): bool
    {
        return $this->call_status === CallStatus::NotAnswered;
    }

    public function isInterested(): bool
    {
        return (bool) $this->interest;
    }

    public function isItemSold(): bool
    {
        return (bool) $this->is_item_sold;
    }

    public function hasInvoice(): bool
    {
        return filled($this->invoice_number) || filled($this->invoice_file_path);
    }

    public function invoiceUrl(): ?string
    {
        if (! $this->invoice_file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->invoice_file_path);
    }

    /**
     * The two stored columns combined, for display and sorting in PHP.
     */
    public function calledAt(): Carbon
    {
        return Carbon::parse(
            $this->called_date->toDateString().' '.$this->called_time
        );
    }

    /**
     * How pressing this call's follow-up is, or null if none is scheduled.
     */
    public function followUpUrgency(): ?FollowUpUrgency
    {
        return FollowUpUrgency::forDate($this->next_followup_date);
    }

    /**
     * Duration as "2m 35s", or null when it was not recorded.
     */
    public function durationForHumans(): ?string
    {
        if ($this->duration === null) {
            return null;
        }

        $minutes = intdiv($this->duration, 60);
        $seconds = $this->duration % 60;

        return $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
    }

    public function wasLoggedBy(?User $user): bool
    {
        return $user !== null
            && $this->called_by !== null
            && (int) $this->called_by === $user->id;
    }
}
