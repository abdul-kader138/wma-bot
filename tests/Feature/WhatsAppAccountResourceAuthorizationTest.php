<?php

namespace Tests\Feature;

use App\Filament\Resources\WhatsAppAccountResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppAccountResourceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_with_permission_can_view_whatsapp_accounts(): void
    {
        $permission = Permission::create(['name' => 'view_any_whats::app::account', 'guard_name' => 'web']);
        $role       = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->givePermissionTo($permission);

        $user = User::factory()->create();
        $user->assignRole('super_admin');

        $this->actingAs($user);

        $this->assertTrue(WhatsAppAccountResource::canViewAny());
    }

    public function test_role_without_permission_cannot_view_whatsapp_accounts(): void
    {
        Permission::create(['name' => 'view_any_whats::app::account', 'guard_name' => 'web']);
        Role::create(['name' => 'operator', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('operator');

        $this->actingAs($user);

        $this->assertFalse(WhatsAppAccountResource::canViewAny());
    }
}
