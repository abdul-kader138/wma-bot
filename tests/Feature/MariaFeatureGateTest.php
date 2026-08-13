<?php

namespace Tests\Feature;

use App\Filament\Pages\MariaDashboard;
use App\Filament\Resources\MariaProjectResource;
use App\Models\Setting;
use App\Models\User;
use App\Services\Maria\MariaAccess;
use App\Services\Maria\MariaPermissionVisibility;
use Database\Seeders\ShieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MariaFeatureGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_setting_hides_and_denies_maria_even_for_super_admin(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::create(['name' => 'super_admin', 'guard_name' => 'web']));
        Setting::set('maria_assistant_enabled', false, 'maria');
        $this->actingAs($admin);

        $this->assertFalse(MariaAccess::allowed($admin));
        $this->assertFalse(MariaDashboard::canAccess());
        $this->assertFalse(MariaProjectResource::shouldRegisterNavigation());
        $this->get('/admin/maria-dashboard')->assertForbidden();
    }

    public function test_enabled_maria_requires_role_permission_and_registers_navigation_when_granted(): void
    {
        Setting::set('maria_assistant_enabled', true, 'maria');
        $permission = Permission::create(['name' => MariaAccess::PERMISSION, 'guard_name' => 'web']);
        $role = Role::create(['name' => 'assistant_user', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        $this->assertFalse(MariaAccess::allowed($user));
        $this->assertFalse(MariaDashboard::canAccess());

        $role->givePermissionTo($permission);
        $user->unsetRelation('roles');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue(MariaAccess::allowed($user));
        $this->assertTrue(MariaDashboard::canAccess());
        $this->assertTrue(MariaProjectResource::shouldRegisterNavigation());
    }

    public function test_custom_permission_is_enabled_for_the_role_permission_screen(): void
    {
        Setting::set('maria_assistant_enabled', true, 'maria');
        MariaPermissionVisibility::apply();
        $this->assertTrue(config('filament-shield.entities.custom_permissions'));
        $this->seed(ShieldSeeder::class);
        $this->assertDatabaseHas('permissions', ['name' => MariaAccess::PERMISSION, 'guard_name' => 'web']);
    }

    public function test_role_permission_entities_follow_global_maria_setting_without_deleting_assignments(): void
    {
        $permission = Permission::create(['name' => MariaAccess::PERMISSION, 'guard_name' => 'web']);
        $role = Role::create(['name' => 'maria_role', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        Setting::set('maria_assistant_enabled', false, 'maria');
        MariaPermissionVisibility::apply();
        $this->assertFalse(config('filament-shield.entities.custom_permissions'));
        $this->assertContains('MariaProjectResource', config('filament-shield.exclude.resources'));
        $this->assertContains('MariaDashboard', config('filament-shield.exclude.pages'));
        $this->assertTrue($role->fresh()->hasPermissionTo(MariaAccess::PERMISSION));

        Setting::set('maria_assistant_enabled', true, 'maria');
        MariaPermissionVisibility::apply();
        $this->assertTrue(config('filament-shield.entities.custom_permissions'));
        $this->assertNotContains('MariaProjectResource', config('filament-shield.exclude.resources'));
        $this->assertNotContains('MariaDashboard', config('filament-shield.exclude.pages'));
        $this->assertTrue($role->fresh()->hasPermissionTo(MariaAccess::PERMISSION));
    }
}
