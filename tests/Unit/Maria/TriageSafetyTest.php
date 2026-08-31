<?php

namespace Tests\Unit\Maria;

use App\Models\Setting;
use App\Services\Maria\Support\SecretRedactor;
use App\Services\Maria\Support\TriageExclusions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriageSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_secret_redactor_scrubs_common_secret_shapes(): void
    {
        $in = 'Here is the key sk-ant-api03-abcDEF123456ghijkl and password: hunter2xyz '
            .'plus card 4111 1111 1111 1111 and Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9';
        $out = SecretRedactor::scrub($in);

        $this->assertStringNotContainsString('sk-ant-api03-abcDEF123456ghijkl', $out);
        $this->assertStringNotContainsString('hunter2xyz', $out);
        $this->assertStringNotContainsString('4111 1111 1111 1111', $out);
        $this->assertStringNotContainsString('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9', $out);
        $this->assertStringContainsString('[redacted]', $out);
        // Ordinary words survive.
        $this->assertStringContainsString('Here is the key', $out);
    }

    public function test_secret_redactor_leaves_plain_text_untouched(): void
    {
        $text = 'Please confirm the launch date and send the updated author bio by Friday.';
        $this->assertSame($text, SecretRedactor::scrub($text));
    }

    public function test_exclusions_match_by_address_and_domain(): void
    {
        Setting::set('maria_triage_excluded_senders', "legal@firm.example\nmybank.example", 'maria');
        Setting::set('maria_triage_excluded_labels', 'Attorney-Client', 'maria');
        $ex = new TriageExclusions;

        $this->assertTrue($ex->excludes('Jane Roe <legal@firm.example>'));
        $this->assertTrue($ex->excludes('statements@mybank.example'));
        $this->assertTrue($ex->excludes('someone@else.example', ['INBOX', 'attorney-client']));
        $this->assertFalse($ex->excludes('elena@aldergate.example', ['INBOX']));
    }

    public function test_no_exclusions_configured_means_nothing_is_filtered(): void
    {
        $ex = new TriageExclusions;

        $this->assertFalse($ex->hasAny());
        $this->assertFalse($ex->excludes('anyone@anywhere.example', ['SECRET']));
    }
}
