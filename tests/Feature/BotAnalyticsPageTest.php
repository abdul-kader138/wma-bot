<?php

namespace Tests\Feature;

use App\Filament\Pages\BotAnalytics;
use App\Models\BotAnswerEvent;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BotAnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    private WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'page_BotAnalytics', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        $this->account = WhatsAppAccount::create([
            'name' => 'Test', 'phone_number_id' => 'pid', 'access_token' => 'tok',
            'api_version' => 'v22.0', 'is_active' => true, 'is_default' => true,
        ]);
    }

    public function test_renders_the_empty_state_with_no_events(): void
    {
        Livewire::test(BotAnalytics::class)
            ->assertOk()
            ->assertSee('No weak answers in this window');
    }

    public function test_renders_stat_cards_and_weak_answers_table_with_events(): void
    {
        BotAnswerEvent::create([
            'whatsapp_account_id' => $this->account->id, 'conversation_id' => 1,
            'wa_phone' => '393000000001', 'platform' => 'whatsapp', 'service' => 'ticket', 'language' => 'en',
            'question' => 'is there a discount for groups?', 'answer' => '…',
            'match_source' => 'none', 'chunk_count' => 0,
        ]);

        BotAnswerEvent::create([
            'whatsapp_account_id' => $this->account->id, 'conversation_id' => 2,
            'wa_phone' => '393000000002', 'platform' => 'messenger', 'service' => 'ticket', 'language' => 'en',
            'question' => 'what documents do I need?', 'answer' => 'You need a valid ID.',
            'match_source' => 'faq', 'chunk_count' => 0, 'rating' => -1, 'rated_at' => now(),
        ]);

        Livewire::test(BotAnalytics::class)
            ->assertOk()
            ->assertSee('is there a discount for groups?')
            ->assertSee('what documents do I need?')
            ->assertDontSee('No weak answers in this window');
    }

    public function test_changing_the_window_updates_without_erroring(): void
    {
        Livewire::test(BotAnalytics::class)
            ->set('days', 7)
            ->assertOk()
            ->set('days', 90)
            ->assertOk();
    }
}
