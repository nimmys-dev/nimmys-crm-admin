<?php

namespace App\Providers;

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
