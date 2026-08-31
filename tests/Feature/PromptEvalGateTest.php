<?php

namespace Tests\Feature;

use App\Filament\Resources\PromptVersionResource;
use App\Models\MariaEvalRun;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PromptEvalGateTest extends TestCase
{
    use RefreshDatabase;

    private function activatePayload(string $content): array
    {
        return PromptVersionResource::prepareData([
            'prompt_type' => 'maria_system',
            'version' => '3.0',
            'content' => $content,
            'is_active' => true,
        ]);
    }

    public function test_activation_is_unrestricted_when_the_gate_is_off(): void
    {
        Setting::set('maria_eval_gate_enabled', false, 'maria');

        $data = $this->activatePayload('BRAND NEW UNTESTED PROMPT');

        $this->assertTrue($data['is_active']);
        $this->assertArrayHasKey('content_hash', $data);
    }

    public function test_gate_blocks_activation_without_a_passing_eval_for_that_exact_text(): void
    {
        Setting::set('maria_eval_gate_enabled', true, 'maria');
        Setting::set('maria_eval_min_pass_rate', 90, 'maria');

        $this->expectException(ValidationException::class);
        $this->activatePayload('A PROMPT THAT WAS NEVER EVALUATED');
    }

    public function test_gate_blocks_when_the_recorded_eval_is_below_the_threshold(): void
    {
        Setting::set('maria_eval_gate_enabled', true, 'maria');
        Setting::set('maria_eval_min_pass_rate', 90, 'maria');
        $content = 'A MEDIOCRE PROMPT';

        MariaEvalRun::create([
            'prompt_type' => 'maria_system', 'prompt_version' => '3.0',
            'prompt_content_hash' => hash('sha256', Str::of($content)->trim()->toString()),
            'total' => 10, 'passed' => 7, 'failed' => 3, 'pass_rate' => 70.0,
        ]);

        $this->expectException(ValidationException::class);
        $this->activatePayload($content);
    }

    public function test_gate_allows_activation_with_a_recent_passing_eval(): void
    {
        Setting::set('maria_eval_gate_enabled', true, 'maria');
        Setting::set('maria_eval_min_pass_rate', 90, 'maria');
        $content = 'A WELL-TESTED PROMPT';

        MariaEvalRun::create([
            'prompt_type' => 'maria_system', 'prompt_version' => '3.0',
            'prompt_content_hash' => hash('sha256', Str::of($content)->trim()->toString()),
            'total' => 10, 'passed' => 10, 'failed' => 0, 'pass_rate' => 100.0,
        ]);

        $data = $this->activatePayload($content);

        $this->assertTrue($data['is_active']);
    }

    public function test_gate_ignores_a_stale_passing_eval(): void
    {
        Setting::set('maria_eval_gate_enabled', true, 'maria');
        $content = 'A PROMPT LAST TESTED TOO LONG AGO';

        $run = MariaEvalRun::create([
            'prompt_type' => 'maria_system', 'prompt_version' => '3.0',
            'prompt_content_hash' => hash('sha256', Str::of($content)->trim()->toString()),
            'total' => 10, 'passed' => 10, 'failed' => 0, 'pass_rate' => 100.0,
        ]);
        $run->forceFill(['created_at' => now()->subDays(45)])->saveQuietly();

        $this->expectException(ValidationException::class);
        $this->activatePayload($content);
    }
}
