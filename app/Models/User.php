<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /*
    |--------------------------------------------------------------------------
    | Sanctum
    |--------------------------------------------------------------------------
    | When the mobile API is built, install sanctum and add the trait:
    |
    |   composer require laravel/sanctum
    |   use Laravel\Sanctum\HasApiTokens;
    |   use HasFactory, Notifiable, HasApiTokens;
    |
    | Nothing else in this model has to change — abilitiesFor() below already
    | returns the token abilities to mint.
    */

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
        'photo',
        'password',
        'status',
        'device_token',
    ];

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
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
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
