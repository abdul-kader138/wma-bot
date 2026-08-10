<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WhatsAppAccountResourceMultiChannelTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        foreach (['view_any_whats::app::account', 'create_whats::app::account'] as $name) {
            Permission::create(['name' => $name, 'guard_name' => 'web']);
        }
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_create_page_renders_with_the_new_platform_field(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get('/admin/whats-app-accounts/create');

        $response->assertOk();
        $response->assertSee('Platform');
    }

    public function test_index_page_renders_with_mixed_platform_accounts(): void
    {
        $this->actingAsSuperAdmin();

        WhatsAppAccount::create([
            'name' => 'WA', 'phone_number_id' => 'p1', 'access_token' => 'x',
            'api_version' => 'v22.0', 'is_active' => true,
        ]);
        WhatsAppAccount::create([
            'name' => 'Messenger', 'platform' => 'messenger', 'external_id' => 'page-1',
            'access_token' => 'x', 'api_version' => 'v22.0', 'is_active' => true,
        ]);

        $response = $this->get('/admin/whats-app-accounts');

        $response->assertOk();
        $response->assertSee('WA');
        $response->assertSee('Messenger');
    }

    public function test_messenger_account_persists_correctly_via_model(): void
    {
        $account = WhatsAppAccount::create([
            'name' => 'Test Messenger',
            'platform' => 'messenger',
            'external_id' => 'page-999',
            'access_token' => 'tok',
            'api_version' => 'v22.0',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('whatsapp_accounts', [
            'id' => $account->id,
            'name' => 'Test Messenger',
            'platform' => 'messenger',
            'external_id' => 'page-999',
            'phone_number_id' => null,
        ]);
    }
}
