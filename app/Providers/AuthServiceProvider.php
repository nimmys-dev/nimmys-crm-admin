<?php

namespace App\Providers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Turn config/permissions.php into Gates.
     *
     * Every ability named in the matrix becomes a real Gate, so `@can`,
     * Gate::allows(), $user->can() and the `can:` route middleware all
     * resolve from that one file. Adding a module means editing config,
     * never this class.
     */
    public function boot(): void
    {
        foreach ($this->abilities() as $ability) {
            Gate::define($ability, function (User $user) use ($ability) {
                // An inactive or suspended account holds no abilities, whatever
                // its role. This makes deactivation take effect immediately
                // rather than at next login.
                if (! $user->isActive()) {
                    return false;
                }

                $surface = str_starts_with($ability, 'mobile.') ? 'mobile' : 'web';

                return in_array($ability, $user->abilitiesFor($surface), true);
            });
        }

        $this->defineLeadModuleGates();
    }

    /**
     * Lead module abilities.
     *
     * Defined here rather than in config/permissions.php because they combine
     * the role matrix with a per-user flag, which a flat list cannot express.
     * The Lead module is not built yet; these are the contract it will use.
     */
    protected function defineLeadModuleGates(): void
    {
        // Can this user reach the Lead module at all?
        Gate::define('leads.access', fn (User $user) => $user->canAccessLeadModule());

        // Admins and Managers manage every lead; a flagged Employee may
        // create their own.
        Gate::define('leads.create', fn (User $user) => $user->canAccessLeadModule());

        /*
         * Any active user admitted to the Lead module may read, edit and
         * assign leads in the full pipeline. Deletion remains restricted to
         * users with leads.manage.
         */
        Gate::define('leads.view', function (User $user, mixed $lead = null) {
            return $user->canAccessLeadModule();
        });

        Gate::define('leads.update', function (User $user, mixed $lead = null) {
            return $user->canAccessLeadModule();
        });

        // Deletion stays with leads.manage. Assignment is available to every
        // user who has been granted Lead module access.
        Gate::define('leads.delete', fn (User $user) => $user->can('leads.manage'));
        Gate::define('leads.assign', fn (User $user) => $user->canAccessLeadModule());
        Gate::define('leads.changeOwner', fn (User $user) => $user->can('leads.manage'));
    }

    /**
     * Ownership check.
     *
     * Written before the Lead model existed and completed now that it does:
     * ownership is the `assigned_to` column, which Lead::isOwnedBy()
     * encapsulates. Still tolerant of a null record, since the gates accept
     * one for menu-level "could this user ever do this" questions.
     */
    protected static function owns(User $user, mixed $lead): bool
    {
        return $lead instanceof Lead && $lead->isOwnedBy($user);
    }

    /**
     * Every distinct ability across both surfaces.
     *
     * @return array<int, string>
     */
    protected function abilities(): array
    {
        $matrix = config('permissions', []);

        return collect(['web', 'mobile'])
            ->flatMap(fn (string $surface) => collect($matrix[$surface] ?? [])->flatten())
            ->reject(fn (string $ability) => $ability === '*')
            // web_abilities also carries entries that only Admin's '*' grants.
            ->merge($matrix['web_abilities'] ?? [])
            ->unique()
            ->values()
            ->all();
    }
}
