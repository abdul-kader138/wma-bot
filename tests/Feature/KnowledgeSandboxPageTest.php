<?php

namespace Tests\Feature;

use App\Filament\Pages\KnowledgeSandbox;
use App\Models\Faq;
use App\Models\User;
use App\Models\WhatsAppAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class KnowledgeSandboxPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();

        Permission::create(['name' => 'page_KnowledgeSandbox', 'guard_name' => 'web']);
        $role = Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $role->givePermissionTo(Permission::all());
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        WhatsAppAccount::create([
            'name' => 'Test', 'phone_number_id' => 'pid', 'access_token' => 'tok',
            'api_version' => 'v22.0', 'is_active' => true, 'is_default' => true,
        ]);
    }

    public function test_page_renders_before_running_a_test(): void
    {
        Livewire::test(KnowledgeSandbox::class)
            ->assertOk()
            ->assertSee('Type a question above');
    }

    public function test_running_a_test_renders_faq_match_and_knowledge_results_without_erroring(): void
    {
        Faq::create([
            'whatsapp_account_id' => WhatsAppAccount::first()->id, 'service' => null,
            'question' => ['en' => 'What is the price?'], 'keywords' => ['price', 'cost', 'how much'],
            'answer' => ['en' => 'It costs €50.'], 'is_active' => true,
        ]);

        Livewire::test(KnowledgeSandbox::class)
            ->fillForm(['question' => 'how much does it cost?'])
            ->call('run')
            ->assertOk()
            ->assertSee('It costs €50.');
    }

    public function test_running_a_test_with_no_faq_match_still_renders_cleanly(): void
    {
        Livewire::test(KnowledgeSandbox::class)
            ->fillForm(['question' => 'completely unrelated question'])
            ->call('run')
            ->assertOk()
            ->assertSee('No FAQ cleared the threshold');
    }
}
