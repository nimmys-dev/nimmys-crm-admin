<?php

namespace App\Models;

use App\Enums\ShopStatus;
use Database\Factories\ShopFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shop extends Model
{
    /** @use HasFactory<ShopFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'manager_id',
        'email',
        'phone',
        'address_line',
        'city',
        'state',
        'postal_code',
        'country',
        'opened_on',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShopStatus::class,
            'opened_on' => 'date',
        ];
    }

    /**
     * Columns the index listing may be ordered by.
     *
     * Whitelisted deliberately: `sort` arrives from the query string and is
     * interpolated into an ORDER BY, so anything not on this list is refused.
     *
     * @var list<string>
     */
    public const SORTABLE = ['code', 'name', 'city', 'status', 'created_at'];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** @return BelongsTo<User, Shop> */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /** @return HasMany<User> */
    public function staff(): HasMany
    {
        return $this->hasMany(User::class, 'shop_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Free-text search across the fields a user would actually type.
     *
     * @param  Builder<Shop>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // Escape LIKE wildcards so a literal % or _ is not treated as a pattern.
        $escaped = '%'.addcslashes($term, '%_\\').'%';

        $query->where(function (Builder $query) use ($escaped) {
            $query->where('code', 'like', $escaped)
                ->orWhere('name', 'like', $escaped)
                ->orWhere('email', 'like', $escaped)
                ->orWhere('phone', 'like', $escaped)
                ->orWhere('city', 'like', $escaped);
        });
    }

    /** @param  Builder<Shop>  $query */
    public function scopeStatus(Builder $query, ShopStatus|string|null $status): void
    {
        if (blank($status)) {
            return;
        }

        $query->where('status', $status instanceof ShopStatus ? $status : ShopStatus::from($status));
    }

    /** @param  Builder<Shop>  $query */
    public function scopeCity(Builder $query, ?string $city): void
    {
        if (blank($city)) {
            return;
        }

        $query->where('city', $city);
    }

    /** @param  Builder<Shop>  $query */
    public function scopeManagedBy(Builder $query, ?int $managerId): void
    {
        if (blank($managerId)) {
            return;
        }

        $query->where('manager_id', $managerId);
    }

    /**
     * Order by a whitelisted column, falling back to name ascending.
     *
     * @param  Builder<Shop>  $query
     */
    public function scopeSorted(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'name';
        $direction = strtolower((string) $direction) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($column, $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function fullAddress(): string
    {
        return collect([
            $this->address_line,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ])->filter()->implode(', ');
    }

    /**
     * Distinct cities present in the data, for the filter dropdown.
     *
     * @return array<string, string>
     */
    public static function cityOptions(): array
    {
        return static::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city', 'city')
            ->all();
    }
}
