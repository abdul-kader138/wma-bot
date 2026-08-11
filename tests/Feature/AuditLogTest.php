<?php

namespace Tests\Feature;

use App\Filament\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_document_activity_is_recorded_with_request_context(): void
    {
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('panel_user');

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10', 'HTTP_USER_AGENT' => 'Audit test browser'])
            ->postJson("/panel-api/documents/{$user->id}/files/%2F", [
                'name' => 'Reports', 'type' => 'folder',
            ])
            ->assertOk();

        $log = AuditLog::sole();
        $this->assertSame($user->id, $log->actor_id);
        $this->assertSame($user->id, $log->owner_id);
        $this->assertSame('documents', $log->category);
        $this->assertSame('create_folder', $log->action);
        $this->assertSame('/Reports', $log->subject_path);
        $this->assertSame('203.0.113.10', $log->ip_address);
        $this->assertSame('Audit test browser', $log->user_agent);
    }

    public function test_only_super_admin_can_open_audit_log_screen(): void
    {
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'panel_user', 'guard_name' => 'web']);

        $user = User::factory()->create();
        $user->assignRole('panel_user');
        $this->actingAs($user)->get(AuditLogResource::getUrl())->assertForbidden();

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->actingAs($admin)->get(AuditLogResource::getUrl())->assertOk();
    }
}
