<?php

namespace Tests\Unit\Maria;

use App\Jobs\Maria\ReconcileStuckActions;
use App\Models\ActionReconciliation;
use App\Models\AssistantAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileStuckActionsTest extends TestCase
{
    use RefreshDatabase;

    private function action(User $owner, string $status, \DateTimeInterface $updatedAt): AssistantAction
    {
        $action = AssistantAction::create([
            'user_id' => $owner->id, 'tool_name' => 'google_email_send',
            'validated_input' => ['to' => 'a@example.com'], 'content_hash' => hash('sha256', 'x'),
            'idempotency_key' => hash('sha256', uniqid('', true)), 'status' => $status,
        ]);
        $action->timestamps = false;
        $action->forceFill(['updated_at' => $updatedAt])->save();

        return $action->refresh();
    }

    public function test_flags_an_action_stuck_in_executing_past_the_timeout(): void
    {
        $owner = User::factory()->create();
        $stuck = $this->action($owner, 'executing', now()->subMinutes(15));

        app(ReconcileStuckActions::class)->handle(app(\App\Services\Maria\AssistantActionService::class));

        $this->assertSame('failed', $stuck->fresh()->status);
        $this->assertDatabaseHas('action_reconciliations', [
            'assistant_action_id' => $stuck->id, 'status' => 'pending',
        ]);
    }

    public function test_does_not_touch_an_action_still_within_the_timeout_window(): void
    {
        $owner = User::factory()->create();
        $recent = $this->action($owner, 'executing', now()->subMinutes(2));

        app(ReconcileStuckActions::class)->handle(app(\App\Services\Maria\AssistantActionService::class));

        $this->assertSame('executing', $recent->fresh()->status);
        $this->assertDatabaseCount('action_reconciliations', 0);
    }

    public function test_does_not_double_flag_an_action_that_already_has_a_reconciliation(): void
    {
        $owner = User::factory()->create();
        $stuck = $this->action($owner, 'executing', now()->subMinutes(20));
        ActionReconciliation::create([
            'user_id' => $owner->id, 'assistant_action_id' => $stuck->id,
            'provider' => 'google', 'status' => 'pending', 'reason' => 'Existing manual flag.',
        ]);

        app(ReconcileStuckActions::class)->handle(app(\App\Services\Maria\AssistantActionService::class));

        $this->assertSame('executing', $stuck->fresh()->status, 'Should not re-process an action that already has a reconciliation record.');
        $this->assertDatabaseCount('action_reconciliations', 1);
    }

    public function test_does_not_touch_completed_actions(): void
    {
        $owner = User::factory()->create();
        $completed = $this->action($owner, 'completed', now()->subMinutes(30));

        app(ReconcileStuckActions::class)->handle(app(\App\Services\Maria\AssistantActionService::class));

        $this->assertSame('completed', $completed->fresh()->status);
        $this->assertDatabaseCount('action_reconciliations', 0);
    }
}
