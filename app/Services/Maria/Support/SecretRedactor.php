<?php

namespace App\Services\Maria\Support;

/**
 * Best-effort scrub of obvious secrets and high-risk numbers from free text
 * before it is handed to the model (email snippets, meeting notes). It is a
 * safety net, not a guarantee — the primary control is still not connecting
 * sensitive mailboxes and excluding sensitive senders.
 */
class SecretRedactor
{
    private const PATTERNS = [
        // Bearer / auth headers
        '/\b(bearer\s+)[A-Za-z0-9._\-]{20,}/i',
        // Common API key prefixes (Anthropic, OpenAI, Stripe, Google, GitHub, Slack, AWS)
        '/\b(sk-ant-|sk-|rk_live_|rk_test_|pk_live_|AIza|ghp_|gho_|xox[baprs]-|AKIA)[A-Za-z0-9\-_]{10,}/',
        // "password: value" / "secret = value" / "token: value"
        '/\b(pass(word)?|secret|token|api[_-]?key|client[_-]?secret)\b\s*[:=]\s*\S+/i',
        // 13–19 digit card-like numbers (optionally space/dash grouped)
        '/\b(?:\d[ -]?){13,19}\b/',
        // Long opaque base64/hex blobs (JWTs, refresh tokens)
        '/\b[A-Za-z0-9+\/_-]{40,}={0,2}\b/',
    ];

    public static function scrub(?string $text): string
    {
        $text = (string) $text;
        if ($text === '') {
            return '';
        }

        foreach (self::PATTERNS as $pattern) {
            $text = (string) preg_replace_callback($pattern, function (array $m): string {
                // Keep a leading label / keyword group where the pattern captured one.
                $prefix = isset($m[1]) && ! ctype_digit(trim($m[1])) ? rtrim($m[1]).' ' : '';

                return $prefix.'[redacted]';
            }, $text);
        }

        return $text;
    }
}
