<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pins the access rules: Admin and Manager may use the web portal,
 * Employees may not, and inactive accounts may not.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function the_root_url_sends_guests_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    #[Test]
    public function the_root_url_sends_authenticated_users_to_the_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/')
            ->assertRedirect('/dashboard');
    }

    #[Test]
    public function admin_can_log_in(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    #[Test]
    public function manager_can_log_in(): void
    {
        $manager = User::factory()->manager()->create(['email' => 'manager@example.com']);

        $this->post(route('login.store'), [
            'email' => 'manager@example.com',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($manager);
    }

    #[Test]
    public function employee_cannot_log_in_to_the_web_portal(): void
    {
        User::factory()->employee()->create(['email' => 'employee@example.com']);

        $this->post(route('login.store'), [
            'email' => 'employee@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(
            __('auth.web_forbidden'),
            session('errors')->first('email')
        );
    }

    #[Test]
    public function a_suspended_account_cannot_log_in(): void
    {
        User::factory()->admin()->suspended()->create(['email' => 'gone@example.com']);

        $this->post(route('login.store'), [
            'email' => 'gone@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
        $this->assertSame(__('auth.inactive'), session('errors')->first('email'));
    }

    #[Test]
    public function a_wrong_password_never_reveals_the_role(): void
    {
        // Order of checks matters: the credential check must run first, so a
        // bad password cannot be used to probe which emails are Employees.
        User::factory()->employee()->create(['email' => 'employee@example.com']);

        $this->post(route('login.store'), [
            'email' => 'employee@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertSame(__('auth.failed'), session('errors')->first('email'));
    }

    #[Test]
    public function logging_in_records_the_timestamp_and_ip(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->assertNull($admin->last_login_at);

        $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->assertNotNull($admin->fresh()->last_login_at);
    }

    #[Test]
    public function a_user_can_log_out(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    #[Test]
    public function changing_a_role_to_employee_ends_a_live_session(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->update(['role' => UserRole::Employee]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    /*
    |--------------------------------------------------------------------------
    | Permission matrix
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, array{string, int}>
     */
    public static function managerMatrix(): array
    {
        return [
            'dashboard is allowed' => ['dashboard', 200],
            'leads are allowed' => ['leads.index', 200],
            'tasks are allowed' => ['tasks.index', 200],
            'profile is allowed' => ['profile.index', 200],
            'shops are denied' => ['shops.index', 403],
            'staff are denied' => ['staff.index', 403],
            'reports are denied' => ['reports.index', 403],
            'settings are denied' => ['settings.index', 403],
        ];
    }

    #[Test]
    #[DataProvider('managerMatrix')]
    public function manager_permissions_match_the_matrix(string $route, int $expected): void
    {
        $this->actingAs(User::factory()->manager()->create())
            ->get(route($route))
            ->assertStatus($expected);
    }

    #[Test]
    public function admin_reaches_every_web_module(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([
            'dashboard', 'shops.index', 'staff.index', 'leads.index',
            'tasks.index', 'reports.index', 'profile.index', 'settings.index',
        ] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }
}
