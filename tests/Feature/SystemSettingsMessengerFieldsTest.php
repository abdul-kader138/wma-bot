<?php

namespace Tests\Feature;

use App\Filament\Pages\SystemSettings;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemSettingsMessengerFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_has_messenger_and_instagram_verify_token_fields(): void
    {
        Permission::create(['name' => 'page_SystemSettings', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        Livewire::test(SystemSettings::class)
            ->assertFormFieldExists('messenger_verify_token')
            ->assertFormFieldExists('instagram_verify_token')
            ->fillForm([
                'messenger_verify_token' => 'my-messenger-secret',
                'instagram_verify_token' => 'my-instagram-secret',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('my-messenger-secret', Setting::get('messenger_verify_token'));
        $this->assertSame('my-instagram-secret', Setting::get('instagram_verify_token'));
    }
}
