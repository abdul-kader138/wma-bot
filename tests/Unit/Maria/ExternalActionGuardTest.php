<?php

namespace Tests\Unit\Maria;

use App\Models\AssistantProfile;
use App\Models\Setting;
use App\Models\User;
use App\Services\Maria\ExternalActionGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ExternalActionGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_global_switch_fails_safe_and_blocks_when_no_setting_exists(): void
    {
        $owner = User::factory()->create();

        // No 'maria_external_actions_enabled' row at all — the switch must be
        // treated as OFF, not ON.
        $this->expectException(ValidationException::class);
        app(ExternalActionGuard::class)->assertEnabled($owner);
    }

    public function test_global_switch_blocks_when_stored_as_a_falsey_string(): void
    {
        $owner = User::factory()->create();
        Setting::set('maria_external_actions_enabled', 'false', 'maria_safety');

        $this->expectException(ValidationException::class);
        app(ExternalActionGuard::class)->assertEnabled($owner);
    }

    public function test_global_emergency_switch_blocks_every_owner(): void
    {
        $owner = User::factory()->create();
        Setting::set('maria_external_actions_enabled', false, 'maria_safety');

        $this->expectException(ValidationException::class);
        app(ExternalActionGuard::class)->assertEnabled($owner);
    }

    public function test_global_switch_off_blocks_even_a_profile_with_external_actions_enabled(): void
    {
        $owner = User::factory()->create();
        AssistantProfile::create(['user_id' => $owner->id, 'external_actions_enabled' => true]);
        Setting::set('maria_external_actions_enabled', false, 'maria_safety');

        $this->expectException(ValidationException::class);
        app(ExternalActionGuard::class)->assertEnabled($owner);
    }

    public function test_per_profile_switch_blocks_only_that_owner(): void
    {
        $blocked = User::factory()->create();
        $allowed = User::factory()->create();
        AssistantProfile::create(['user_id' => $blocked->id, 'external_actions_enabled' => false]);
        AssistantProfile::create(['user_id' => $allowed->id, 'external_actions_enabled' => true]);
        Setting::set('maria_external_actions_enabled', true, 'maria_safety');

        $this->expectException(ValidationException::class);
        app(ExternalActionGuard::class)->assertEnabled($blocked);
    }

    public function test_per_profile_switch_does_not_block_a_different_owner(): void
    {
        $blocked = User::factory()->create();
        $allowed = User::factory()->create();
        AssistantProfile::create(['user_id' => $blocked->id, 'external_actions_enabled' => false]);
        AssistantProfile::create(['user_id' => $allowed->id, 'external_actions_enabled' => true]);
        Setting::set('maria_external_actions_enabled', true, 'maria_safety');

        $this->expectNotToPerformAssertions();
        app(ExternalActionGuard::class)->assertEnabled($allowed);
    }

    public function test_owner_without_a_profile_is_allowed_when_global_switch_is_on(): void
    {
        $owner = User::factory()->create();
        Setting::set('maria_external_actions_enabled', true, 'maria_safety');

        $this->expectNotToPerformAssertions();
        app(ExternalActionGuard::class)->assertEnabled($owner);
    }
}
