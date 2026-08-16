<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Shop;
use App\Models\User;
use App\Services\StaffPhotoService;
use App\Support\EmployeeCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\FakeImage;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Priya Nair',
            'email' => 'priya@example.com',
            'role' => UserRole::Employee->value,
            'shop_id' => null,
            'phone' => '9876543210',
            'alternate_phone' => '9876543211',
            'joining_date' => '2025-06-01',
            'salary' => '35000.00',
            'status' => UserStatus::Active->value,
            'description' => 'Counter staff.',
            'password' => 'Str0ng-Passw0rd!',
            'password_confirmation' => 'Str0ng-Passw0rd!',
        ], $overrides);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('staff.index'))->assertRedirect(route('login'));
    }

    #[Test]
    public function admin_can_reach_every_staff_route(): void
    {
        $member = User::factory()->employee()->create();

        $this->actingAs($this->admin())->get(route('staff.index'))->assertOk();
        $this->actingAs($this->admin())->get(route('staff.create'))->assertOk();
        $this->actingAs($this->admin())->get(route('staff.show', $member))->assertOk();
        $this->actingAs($this->admin())->get(route('staff.edit', $member))->assertOk();
    }

    #[Test]
    public function manager_is_forbidden_from_every_staff_route(): void
    {
        $manager = User::factory()->manager()->create();
        $member = User::factory()->employee()->create();

        $this->actingAs($manager)->get(route('staff.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('staff.create'))->assertForbidden();
        $this->actingAs($manager)->get(route('staff.show', $member))->assertForbidden();
        $this->actingAs($manager)->get(route('staff.edit', $member))->assertForbidden();
        $this->actingAs($manager)->post(route('staff.store'), $this->payload())->assertForbidden();
        $this->actingAs($manager)->delete(route('staff.destroy', $member))->assertForbidden();
    }

    #[Test]
    public function employee_cannot_reach_staff_routes(): void
    {
        $employee = User::factory()->employee()->create();
        $member = User::factory()->employee()->create();

        $this->actingAs($employee)->get(route('staff.index'))->assertForbidden();
        $this->actingAs($employee)->get(route('staff.create'))->assertForbidden();
        $this->actingAs($employee)->get(route('staff.show', $member))->assertForbidden();
        $this->actingAs($employee)->get(route('staff.edit', $member))->assertForbidden();
        $this->actingAs($employee)->post(route('staff.store'), $this->payload())->assertForbidden();
        $this->actingAs($employee)->delete(route('staff.destroy', $member))->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Employee code
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_first_staff_member_gets_the_first_code(): void
    {
        $this->actingAs($this->admin())->post(route('staff.store'), $this->payload());

        $this->assertDatabaseHas('users', [
            'email' => 'priya@example.com',
            'employee_code' => 'EMP-0001',
        ]);
    }

    #[Test]
    public function codes_increment_sequentially(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('staff.store'), $this->payload(['email' => 'a@example.com']));
        $this->actingAs($admin)->post(route('staff.store'), $this->payload(['email' => 'b@example.com']));
        $this->actingAs($admin)->post(route('staff.store'), $this->payload(['email' => 'c@example.com']));

        $this->assertSame(
            ['EMP-0001', 'EMP-0002', 'EMP-0003'],
            User::whereNotNull('employee_code')->orderBy('employee_code')->pluck('employee_code')->all()
        );
    }

    #[Test]
    public function codes_are_never_reused_after_a_soft_delete(): void
    {
        // Reusing a departed employee's code would corrupt historical records.
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('staff.store'), $this->payload(['email' => 'first@example.com']));
        $first = User::firstWhere('email', 'first@example.com');
        $first->delete();

        $this->actingAs($admin)->post(route('staff.store'), $this->payload(['email' => 'second@example.com']));

        $this->assertSame('EMP-0002', User::firstWhere('email', 'second@example.com')->employee_code);
    }

    #[Test]
    public function the_code_sequence_survives_double_digit_rollover(): void
    {
        // A naive string sort would put EMP-0010 before EMP-0009.
        User::factory()->employee()->create(['employee_code' => EmployeeCode::format(9)]);

        $this->assertSame('EMP-0010', EmployeeCode::next());
    }

    /*
    |--------------------------------------------------------------------------
    | Create & validate
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function admin_can_create_a_staff_member(): void
    {
        $shop = Shop::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload(['shop_id' => $shop->id]))
            ->assertSessionHasNoErrors();

        $member = User::firstWhere('email', 'priya@example.com');

        $this->assertNotNull($member);
        $this->assertSame(UserRole::Employee, $member->role);
        $this->assertSame($shop->id, $member->shop_id);
        $this->assertSame('35000.00', $member->salary);
        $this->assertTrue($member->joining_date->isSameDay('2025-06-01'));
    }

    #[Test]
    public function the_password_is_hashed_not_stored_in_the_clear(): void
    {
        $this->actingAs($this->admin())->post(route('staff.store'), $this->payload());

        $member = User::firstWhere('email', 'priya@example.com');

        $this->assertNotSame('Str0ng-Passw0rd!', $member->password);
        $this->assertTrue(\Hash::check('Str0ng-Passw0rd!', $member->password));
    }

    #[Test]
    public function required_fields_are_enforced(): void
    {
        $this->actingAs($this->admin())
            ->post(route('staff.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'role', 'phone', 'status', 'password']);
    }

    #[Test]
    public function the_email_must_be_unique(): void
    {
        User::factory()->employee()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload(['email' => 'taken@example.com']))
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function a_soft_deleted_member_still_holds_their_email(): void
    {
        // The UNIQUE index covers trashed rows, so the message must explain it
        // rather than surfacing a database error.
        $gone = User::factory()->employee()->create(['email' => 'gone@example.com']);
        $gone->delete();

        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload(['email' => 'gone@example.com']))
            ->assertSessionHasErrors('email');
    }

    #[Test]
    public function the_alternate_mobile_must_differ_from_the_primary(): void
    {
        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload([
                'phone' => '9876543210',
                'alternate_phone' => '9876543210',
            ]))
            ->assertSessionHasErrors('alternate_phone');
    }

    #[Test]
    public function the_joining_date_cannot_be_in_the_future(): void
    {
        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload([
                'joining_date' => now()->addDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('joining_date');
    }

    #[Test]
    public function the_salary_cannot_be_negative(): void
    {
        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload(['salary' => '-1']))
            ->assertSessionHasErrors('salary');
    }

    /*
    |--------------------------------------------------------------------------
    | Photo upload
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function a_profile_photo_is_stored_on_the_public_disk(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('staff.store'), $this->payload([
            'photo' => UploadedFile::fake()->createWithContent('me.png', FakeImage::png(400, 400)),
        ]));

        $member = User::firstWhere('email', 'priya@example.com');

        $this->assertNotNull($member->photo);
        Storage::disk('public')->assertExists($member->photo);
        $this->assertStringStartsWith(StaffPhotoService::DIRECTORY, $member->photo);
    }

    #[Test]
    public function a_non_image_upload_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload([
                'photo' => UploadedFile::fake()->create('payload.php', 20, 'application/x-php'),
            ]))
            ->assertSessionHasErrors('photo');
    }

    #[Test]
    public function an_oversized_photo_is_rejected(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('staff.store'), $this->payload([
                'photo' => UploadedFile::fake()->createWithContent('huge.png', FakeImage::png(400, 400, 3 * 1024 * 1024)),
            ]))
            ->assertSessionHasErrors('photo');
    }

    #[Test]
    public function replacing_a_photo_deletes_the_previous_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('staff.store'), $this->payload([
            'photo' => UploadedFile::fake()->createWithContent('first.png', FakeImage::png(400, 400)),
        ]));

        $member = User::firstWhere('email', 'priya@example.com');
        $original = $member->photo;

        $this->actingAs($admin)->put(route('staff.update', $member), $this->payload([
            'photo' => UploadedFile::fake()->createWithContent('second.png', FakeImage::png(400, 400)),
            'password' => '',
            'password_confirmation' => '',
        ]));

        $member->refresh();

        $this->assertNotSame($original, $member->photo);
        Storage::disk('public')->assertMissing($original);
        Storage::disk('public')->assertExists($member->photo);
    }

    #[Test]
    public function a_photo_can_be_removed_without_deleting_the_member(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('staff.store'), $this->payload([
            'photo' => UploadedFile::fake()->createWithContent('me.png', FakeImage::png(400, 400)),
        ]));

        $member = User::firstWhere('email', 'priya@example.com');
        $path = $member->photo;

        $this->actingAs($admin)->delete(route('staff.photo.destroy', $member));

        $this->assertNull($member->fresh()->photo);
        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseHas('users', ['id' => $member->id]);
    }

    #[Test]
    public function the_photo_url_is_host_relative(): void
    {
        /*
         * Regression: Storage::url() on the local disk prefixes APP_URL, so
         * with APP_URL=http://localhost every photo pointed at port 80 while
         * the app was being browsed on artisan serve at :8000. The upload
         * succeeded but the image 404'd, which read as "upload is broken".
         */
        Storage::fake('public');
        config(['app.url' => 'http://localhost']);

        $this->actingAs($this->admin())->post(route('staff.store'), $this->payload([
            'photo' => UploadedFile::fake()->createWithContent('me.png', FakeImage::png(400, 400)),
        ]));

        $member = User::firstWhere('email', 'priya@example.com');
        $url = app(StaffPhotoService::class)->url($member->photo);

        $this->assertStringStartsWith('/storage/', $url);
        $this->assertStringNotContainsString('http://localhost', $url);
    }

    #[Test]
    public function a_member_without_a_photo_renders_no_empty_image_source(): void
    {
        // <img src=""> makes the browser re-request the page and paint a
        // broken-image icon.
        $member = User::factory()->employee()->create(['photo' => null]);

        $html = $this->actingAs($this->admin())
            ->get(route('staff.edit', $member))
            ->getContent();

        $this->assertStringNotContainsString('src=""', $html);
    }

    #[Test]
    public function the_remove_photo_control_appears_only_when_a_photo_exists(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $without = User::factory()->employee()->create(['photo' => null]);
        $this->actingAs($admin)->get(route('staff.edit', $without))
            ->assertDontSee('Remove photo');

        $this->actingAs($admin)->post(route('staff.store'), $this->payload([
            'photo' => UploadedFile::fake()->createWithContent('me.png', FakeImage::png(400, 400)),
        ]));
        $with = User::firstWhere('email', 'priya@example.com');

        $this->actingAs($admin)->get(route('staff.edit', $with))
            ->assertSee('Remove photo');
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function a_blank_password_leaves_the_existing_one_untouched(): void
    {
        $member = User::factory()->employee()->create();
        $original = $member->password;

        $this->actingAs($this->admin())
            ->put(route('staff.update', $member), $this->payload([
                'email' => $member->email,
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame($original, $member->fresh()->password);
    }

    #[Test]
    public function a_member_keeps_their_own_email_on_update(): void
    {
        $member = User::factory()->employee()->create(['email' => 'keep@example.com']);

        $this->actingAs($this->admin())
            ->put(route('staff.update', $member), $this->payload([
                'email' => 'keep@example.com',
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function an_admin_cannot_demote_themselves(): void
    {
        // Otherwise the last admin could lock the whole panel.
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('staff.update', $admin), $this->payload([
                'email' => $admin->email,
                'role' => UserRole::Employee->value,
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasErrors('role');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }

    #[Test]
    public function an_admin_cannot_deactivate_themselves(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put(route('staff.update', $admin), $this->payload([
                'email' => $admin->email,
                'status' => UserStatus::Inactive->value,
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasErrors('status');
    }

    #[Test]
    public function an_admin_can_still_demote_someone_else(): void
    {
        $other = User::factory()->admin()->create();

        $this->actingAs($this->admin())
            ->put(route('staff.update', $other), $this->payload([
                'email' => $other->email,
                'role' => UserRole::Manager->value,
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame(UserRole::Manager, $other->fresh()->role);
    }

    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function deleting_a_member_soft_deletes_them(): void
    {
        $member = User::factory()->employee()->create();

        $this->actingAs($this->admin())
            ->delete(route('staff.destroy', $member))
            ->assertRedirect(route('staff.index'));

        $this->assertSoftDeleted($member);
    }

    #[Test]
    public function a_soft_deleted_member_cannot_authenticate(): void
    {
        // The whole point of soft deleting rather than deactivating: Laravel's
        // user provider applies the soft-delete scope, so the row is invisible
        // to authentication.
        $member = User::factory()->manager()->create(['email' => 'gone@example.com']);
        $member->delete();

        $this->post(route('login.store'), [
            'email' => 'gone@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function a_soft_deleted_member_is_hidden_from_the_listing(): void
    {
        $kept = User::factory()->employee()->create(['name' => 'Still Here']);
        $gone = User::factory()->employee()->create(['name' => 'Long Gone']);
        $gone->delete();

        $this->actingAs($this->admin())
            ->get(route('staff.index'))
            ->assertSee('Still Here')
            ->assertDontSee('Long Gone');
    }

    #[Test]
    public function an_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('staff.destroy', $admin));

        $this->assertNotSoftDeleted($admin);
    }

    #[Test]
    public function the_last_active_admin_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $other = User::factory()->admin()->create();

        // Two admins exist, so removing one is allowed.
        $this->actingAs($admin)->delete(route('staff.destroy', $other));
        $this->assertSoftDeleted($other);

        // $admin is now the only one left and cannot be removed, even by
        // another account with the ability.
        $this->assertTrue($admin->fresh()->exists);
    }

    /*
    |--------------------------------------------------------------------------
    | Search, filter & sort
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function search_matches_name_code_email_and_mobile(): void
    {
        User::factory()->employee()->create([
            'name' => 'Priya Nair', 'email' => 'priya@example.com',
            'phone' => '9811111111', 'employee_code' => 'EMP-0042',
        ]);
        User::factory()->employee()->create([
            'name' => 'Rahul Menon', 'email' => 'rahul@example.com',
            'phone' => '9822222222', 'employee_code' => 'EMP-0043',
        ]);

        $admin = $this->admin();

        foreach (['Priya', 'EMP-0042', 'priya@example.com', '9811111111'] as $term) {
            $this->actingAs($admin)->get(route('staff.index', ['q' => $term]))
                ->assertSee('Priya Nair')
                ->assertDontSee('Rahul Menon');
        }
    }

    #[Test]
    public function the_role_filter_narrows_results(): void
    {
        User::factory()->manager()->create(['name' => 'Mo Manager']);
        User::factory()->employee()->create(['name' => 'Emma Employee']);

        $this->actingAs($this->admin())
            ->get(route('staff.index', ['role' => UserRole::Manager->value]))
            ->assertSee('Mo Manager')
            ->assertDontSee('Emma Employee');
    }

    #[Test]
    public function the_shop_filter_narrows_results(): void
    {
        $shop = Shop::factory()->create();
        User::factory()->employee()->create(['name' => 'In Shop', 'shop_id' => $shop->id]);
        User::factory()->employee()->create(['name' => 'No Shop']);

        $this->actingAs($this->admin())
            ->get(route('staff.index', ['shop_id' => $shop->id]))
            ->assertSee('In Shop')
            ->assertDontSee('No Shop');
    }

    #[Test]
    public function an_unknown_sort_column_is_rejected(): void
    {
        $this->actingAs($this->admin())
            ->get(route('staff.index', ['sort' => 'password']))
            ->assertSessionHasErrors('sort');
    }

    #[Test]
    public function a_search_term_with_like_wildcards_is_escaped(): void
    {
        User::factory()->employee()->create(['name' => 'Real Person']);

        $this->actingAs($this->admin())
            ->get(route('staff.index', ['q' => '%']))
            ->assertDontSee('Real Person');
    }

    #[Test]
    public function results_are_paginated(): void
    {
        User::factory()->employee()->count(20)->create();

        $this->actingAs($this->admin())
            ->get(route('staff.index', ['per_page' => 15]))
            ->assertOk()
            ->assertSee('crm-pagination', false);
    }
}
