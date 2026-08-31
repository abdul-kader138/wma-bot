<?php

namespace Tests\Unit\Maria;

use App\Models\Setting;
use App\Services\Maria\ClaudeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaudeClientTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_the_primary_model_and_does_not_touch_the_fallback_on_success(): void
    {
        Setting::set('claude_model', 'claude-primary', 'claude');
        Setting::set('claude_fallback_model', 'claude-backup', 'claude');
        Http::fake(['https://api.anthropic.com/*' => Http::response(['content' => [['type' => 'text', 'text' => 'ok']]])]);

        app(ClaudeClient::class)->messages(['messages' => [['role' => 'user', 'content' => 'hi']]]);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request->data()['model'] === 'claude-primary');
    }

    public function test_it_fails_over_to_the_backup_model_on_a_provider_error(): void
    {
        Setting::set('claude_model', 'claude-primary', 'claude');
        Setting::set('claude_fallback_model', 'claude-backup', 'claude');
        Http::fake(['https://api.anthropic.com/*' => Http::sequence()
            ->push(['error' => 'overloaded'], 529)
            ->push(['error' => 'overloaded'], 529)
            ->push(['error' => 'overloaded'], 529)   // primary: initial + 2 transient retries
            ->push(['content' => [['type' => 'text', 'text' => 'recovered']]]), // backup succeeds
        ]);

        $result = app(ClaudeClient::class)->messages(['messages' => [['role' => 'user', 'content' => 'hi']]]);

        $this->assertSame('recovered', $result['content'][0]['text']);
        Http::assertSent(fn ($request) => $request->data()['model'] === 'claude-backup');
    }

    public function test_it_fails_over_on_an_unknown_model_404(): void
    {
        Setting::set('claude_model', 'claude-typo', 'claude');
        Setting::set('claude_fallback_model', 'claude-backup', 'claude');
        Http::fake(['https://api.anthropic.com/*' => Http::sequence()
            ->push(['error' => ['type' => 'not_found_error']], 404)
            ->push(['content' => [['type' => 'text', 'text' => 'ok']]]),
        ]);

        $result = app(ClaudeClient::class)->messages(['messages' => [['role' => 'user', 'content' => 'hi']]]);

        $this->assertSame('ok', $result['content'][0]['text']);
    }

    public function test_it_throws_when_no_fallback_is_configured(): void
    {
        Setting::set('claude_model', 'claude-primary', 'claude');
        Setting::set('claude_fallback_model', '', 'claude');
        Http::fake(['https://api.anthropic.com/*' => Http::response(['error' => 'nope'], 500)]);

        $this->expectException(RequestException::class);
        app(ClaudeClient::class)->messages(['messages' => [['role' => 'user', 'content' => 'hi']]]);
    }

    public function test_a_client_400_is_not_failed_over(): void
    {
        Setting::set('claude_model', 'claude-primary', 'claude');
        Setting::set('claude_fallback_model', 'claude-backup', 'claude');
        Http::fake(['https://api.anthropic.com/*' => Http::response(['error' => 'bad request'], 400)]);

        try {
            app(ClaudeClient::class)->messages(['messages' => [['role' => 'user', 'content' => 'hi']]]);
            $this->fail('expected a RequestException');
        } catch (RequestException $e) {
            $this->assertSame(400, $e->response->status());
        }

        Http::assertSent(fn ($request) => $request->data()['model'] === 'claude-primary');
        Http::assertNotSent(fn ($request) => $request->data()['model'] === 'claude-backup');
    }
}
