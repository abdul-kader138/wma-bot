<?php

namespace Tests\Feature;

use App\Filament\Resources\MariaProjectResource;
use App\Models\AssistantChannelIdentity;
use App\Models\AssistantProfile;
use App\Models\ConnectorAccount;
use App\Models\MariaProject;
use App\Models\Setting;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\Maria\ApprovalService;
use App\Services\Maria\AssistantActionService;
use App\Services\Maria\AssistantIdentityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MariaFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_connector_tokens_are_encrypted_at_rest_and_hidden_from_serialized_output(): void
    {
        $user = User::factory()->create();

        $connector = ConnectorAccount::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_account_id' => 'google-123',
            'email' => 'owner@example.com',
            'access_token' => 'plain-access-token',
            'refresh_token' => 'plain-refresh-token',
            'scopes' => ['gmail.readonly'],
        ]);

        $stored = DB::table('connector_accounts')->where('id', $connector->id)->first();

        $this->assertNotSame('plain-access-token', $stored->access_token);
        $this->assertNotSame('plain-refresh-token', $stored->refresh_token);
        $this->assertSame('plain-access-token', $connector->fresh()->access_token);
        $this->assertArrayNotHasKey('access_token', $connector->toArray());
        $this->assertArrayNotHasKey('refresh_token', $connector->toArray());
    }

    public function test_only_active_verified_channel_identity_resolves_to_owner(): void
    {
        $user = User::factory()->create();
        Setting::set('maria_assistant_enabled', true, 'maria');
        $user->givePermissionTo(Permission::firstOrCreate(['name' => 'access_maria_assistant', 'guard_name' => 'web']));
        $profile = AssistantProfile::create(['user_id' => $user->id]);
        $account = WhatsAppAccount::create([
            'name' => 'Maria',
            'platform' => 'whatsapp',
            'phone_number_id' => 'phone-id-1',
            'external_id' => 'phone-id-1',
        ]);

        $identity = AssistantChannelIdentity::create([
            'assistant_profile_id' => $profile->id,
            'whatsapp_account_id' => $account->id,
            'platform' => 'whatsapp',
            'external_identifier' => '49123456789',
        ]);

        $resolver = app(AssistantIdentityResolver::class);

        $this->assertNull($resolver->resolve('whatsapp', '49123456789', $account->id));

        $identity->update(['verified_at' => now()]);

        $this->assertTrue($resolver->resolve('whatsapp', '49123456789', $account->id)->is($user));

        $profile->update(['is_active' => false]);

        $this->assertNull($resolver->resolve('whatsapp', '49123456789', $account->id));
    }

    public function test_approval_is_bound_to_exact_canonical_content(): void
    {
        $user = User::factory()->create();
        $service = app(ApprovalService::class);

        $approval = $service->request(
            owner: $user,
            actionType: 'send',
            proposedAction: 'Send email to Maria',
            content: ['subject' => 'Hello', 'to' => 'maria@example.com'],
        );

        $approved = $service->approve(
            $approval,
            $user,
            ['to' => 'maria@example.com', 'subject' => 'Hello'],
        );

        $this->assertSame('approved', $approved->decision);
        $this->assertTrue($approved->decidedBy->is($user));
    }

    public function test_changed_content_cannot_use_an_existing_approval(): void
    {
        $user = User::factory()->create();
        $service = app(ApprovalService::class);
        $approval = $service->request(
            $user,
            'send',
            'Send email',
            ['to' => 'right@example.com', 'body' => 'Approved body'],
        );

        $this->expectException(ValidationException::class);

        $service->approve(
            $approval,
            $user,
            ['to' => 'wrong@example.com', 'body' => 'Approved body'],
        );
    }

    public function test_expired_approval_cannot_be_used(): void
    {
        $user = User::factory()->create();
        $service = app(ApprovalService::class);
        $content = ['to' => 'maria@example.com'];
        $approval = $service->request(
            owner: $user,
            actionType: 'send',
            proposedAction: 'Send email',
            content: $content,
            expiresAt: now()->subMinute(),
        );

        try {
            $service->approve($approval, $user, $content);
            $this->fail('Expected an expired approval to be rejected.');
        } catch (ValidationException) {
            $this->assertSame('expired', $approval->fresh()->decision);
        }
    }

    public function test_action_reservation_is_idempotent_and_requires_matching_approval(): void
    {
        $user = User::factory()->create();
        $approvals = app(ApprovalService::class);
        $actions = app(AssistantActionService::class);
        $input = ['to' => 'maria@example.com', 'body' => 'Hello'];
        $approval = $approvals->request($user, 'send', 'Send email', $input);
        $approval = $approvals->approve($approval, $user, $input);

        $first = $actions->reserve($user, 'send_email', $input, approval: $approval);
        $second = $actions->reserve($user, 'send_email', $input, approval: $approval);

        $this->assertTrue($first->is($second));
        $this->assertDatabaseCount('assistant_actions', 1);
    }

    public function test_action_rejects_approval_for_different_content(): void
    {
        $user = User::factory()->create();
        $approvals = app(ApprovalService::class);
        $actions = app(AssistantActionService::class);
        $approvedInput = ['to' => 'maria@example.com'];
        $approval = $approvals->request($user, 'send', 'Send email', $approvedInput);
        $approval = $approvals->approve($approval, $user, $approvedInput);

        $this->expectException(ValidationException::class);

        $actions->reserve(
            $user,
            'send_email',
            ['to' => 'different@example.com'],
            approval: $approval,
        );
    }

    public function test_maria_resource_query_is_scoped_to_authenticated_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        Role::create(['name' => 'panel_user', 'guard_name' => 'web']);
        $owner->assignRole('panel_user');
        $this->actingAs($owner);

        MariaProject::create($this->projectData($owner, 'Visible project'));
        MariaProject::create($this->projectData($other, 'Hidden project'));

        $this->assertSame(['Visible project'], MariaProjectResource::getEloquentQuery()->pluck('name')->all());
        $this->assertTrue($owner->can('viewAny', MariaProject::class));
        $this->assertFalse($owner->can('view', MariaProject::where('user_id', $other->id)->first()));
    }

    private function projectData(User $owner, string $name): array
    {
        return [
            'user_id' => $owner->id, 'domain' => 'PER', 'name' => $name,
            'desired_outcome' => 'Complete the project', 'owner_name' => $owner->name,
            'next_action' => 'Take the next step', 'next_action_at' => now()->addDay(),
        ];
    }
}
