<?php

namespace Tests\Unit\Maria;

use App\Models\AssistantChannelIdentity;
use App\Models\AssistantProfile;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Services\AuditLogger;
use App\Services\Maria\ConversationMemoryService;
use App\Services\Maria\MariaAgent;
use App\Services\Maria\MariaConversationService;
use App\Services\Maria\Support\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LanguageDirectiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_helper_only_speaks_up_for_a_non_default_language(): void
    {
        $this->assertSame('', Language::chatDirective('en'));
        $this->assertSame('', Language::chatDirective(null));
        $this->assertStringContainsString('Italian', Language::chatDirective('it'));
        $this->assertSame('', Language::instructionClause('en'));
        $this->assertStringContainsString('Bengali', Language::instructionClause('bn'));
    }

    public function test_chat_prepends_the_language_directive_for_an_italian_profile(): void
    {
        $user = User::factory()->create();
        $profile = AssistantProfile::create(['user_id' => $user->id, 'language' => 'it']);
        $account = WhatsAppAccount::create(['name' => 'M', 'platform' => 'whatsapp', 'phone_number_id' => 'p', 'external_id' => 'p']);
        $identity = AssistantChannelIdentity::create([
            'assistant_profile_id' => $profile->id, 'whatsapp_account_id' => $account->id,
            'platform' => 'whatsapp', 'external_identifier' => '3900', 'verified_at' => now(),
        ]);

        $agent = Mockery::mock(MariaAgent::class);
        $agent->shouldReceive('handle')->once()
            ->withArgs(fn (User $o, string $m, array $h) => str_starts_with($m, '(Always respond to the owner in Italian.)')
                && str_contains($m, 'Che cosa devo fare?'))
            ->andReturn(['text' => 'Ecco. Status: Completed', 'prompt_version' => 'v1', 'tool_calls' => [], 'usage' => ['input_tokens' => 1, 'output_tokens' => 1]]);

        $reply = (new MariaConversationService($agent, app(AuditLogger::class), app(ConversationMemoryService::class)))
            ->handle($identity, 'Che cosa devo fare?');

        $this->assertSame('Ecco. Status: Completed', $reply);
    }
}
