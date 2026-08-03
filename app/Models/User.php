<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'shop_id',
        'employee_code',
        'name',
        'role',
        'email',
        'phone',
        'alternate_phone',
        'photo',
        'password',
        'joining_date',
        'salary',
        'description',
        'status',
        'device_token',
    ];

    /**
     * Columns the staff listing may be ordered by.
     *
     * Whitelisted because `sort` arrives from the query string and reaches
     * an ORDER BY.
     *
     * @var list<string>
     */
    public const SORTABLE = ['employee_code', 'name', 'email', 'role', 'status', 'joining_date', 'created_at'];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'joining_date' => 'date',
            'salary' => 'decimal:2',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** @return BelongsTo<Shop, User> */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    /**
     * The shop this user runs, as opposed to the one they belong to.
     *
     * @return HasOne<Shop>
     */
    public function managedShop(): HasOne
    {
        return $this->hasOne(Shop::class, 'manager_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Role helpers
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isManager(): bool
    {
        return $this->role === UserRole::Manager;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    /**
     * @param  UserRole|string|array<int, UserRole|string>  $roles
     */
    public function hasRole(UserRole|string|array $roles): bool
    {
        $roles = collect(is_array($roles) ? $roles : [$roles])
            ->map(fn ($role) => $role instanceof UserRole ? $role : UserRole::tryFrom($role))
            ->filter();

        return $roles->contains($this->role);
    }

    /*
    |--------------------------------------------------------------------------
    | Access rules
    |--------------------------------------------------------------------------
    */

    public function isActive(): bool
    {
        return $this->status->canAuthenticate();
    }

    /**
     * Both conditions must hold: the role is web-capable AND the account is
     * active. Checked at login and again by middleware on every request, so a
     * user deactivated mid-session loses access immediately.
     */
    public function canAccessWeb(): bool
    {
        return $this->isActive() && $this->role->canAccessWeb();
    }

    public function canAccessMobile(): bool
    {
        return $this->isActive() && $this->role->canAccessMobile();
    }

    /**
     * Abilities for a surface, resolved from config/permissions.php.
     * Expands Admin's '*' into the full web ability list.
     *
     * @return array<int, string>
     */
    public function abilitiesFor(string $surface = 'web'): array
    {
        $abilities = config("permissions.{$surface}.{$this->role->value}", []);

        if (in_array('*', $abilities, true)) {
            return $surface === 'web'
                ? config('permissions.web_abilities', [])
                : config("permissions.mobile.{$this->role->value}", []);
        }

        return $abilities;
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::url($this->photo) : null;
    }

    public function initials(): string
    {
        return str($this->name)->trim()->substr(0, 1)->upper()->toString();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param  Builder<User>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', UserStatus::Active);
    }

    /** @param  Builder<User>  $query */
    public function scopeRole(Builder $query, UserRole $role): void
    {
        $query->where('role', $role);
    }

    /** @param  Builder<User>  $query */
    public function scopeForShop(Builder $query, ?int $shopId): void
    {
        $query->where('shop_id', $shopId);
    }

    /**
     * Free-text search across the fields an admin would actually type.
     *
     * @param  Builder<User>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // Escape LIKE wildcards so a literal % or _ is not read as a pattern.
        $escaped = '%'.addcslashes($term, '%_\\').'%';

        $query->where(function (Builder $query) use ($escaped) {
            $query->where('employee_code', 'like', $escaped)
                ->orWhere('name', 'like', $escaped)
                ->orWhere('email', 'like', $escaped)
                ->orWhere('phone', 'like', $escaped)
                ->orWhere('alternate_phone', 'like', $escaped);
        });
    }

    /** @param  Builder<User>  $query */
    public function scopeFilterRole(Builder $query, UserRole|string|null $role): void
    {
        if (blank($role)) {
            return;
        }

        $query->where('role', $role instanceof UserRole ? $role : UserRole::from($role));
    }

    /** @param  Builder<User>  $query */
    public function scopeFilterStatus(Builder $query, UserStatus|string|null $status): void
    {
        if (blank($status)) {
            return;
        }

        $query->where('status', $status instanceof UserStatus ? $status : UserStatus::from($status));
    }

    /** @param  Builder<User>  $query */
    public function scopeFilterShop(Builder $query, int|string|null $shopId): void
    {
        if (blank($shopId)) {
            return;
        }

        $query->where('shop_id', (int) $shopId);
    }

    /**
     * Order by a whitelisted column, falling back to newest first.
     *
     * @param  Builder<User>  $query
     */
    public function scopeSorted(Builder $query, ?string $column, ?string $direction): void
    {
        $column = in_array($column, self::SORTABLE, true) ? $column : 'created_at';
        $direction = strtolower((string) $direction) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($column, $direction);
    }

    /*
    |--------------------------------------------------------------------------
    | Login bookkeeping
    |--------------------------------------------------------------------------
    */

    public function recordLogin(?string $ip): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ])->saveQuietly();
    }
}
