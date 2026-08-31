<?php

namespace App\Services\Maria;

use RuntimeException;

/**
 * Thrown when a Maria Claude call is refused because the shared per-minute
 * Anthropic budget (the same one the public WhatsApp bot spends against — there
 * is a single API key) is exhausted. Callers should treat this as transient:
 * retry the workflow later, or tell the chat user to try again shortly. It must
 * never be reported as a Maria failure or an invented result.
 */
class ClaudeRateLimited extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct("Maria is at the shared Claude request limit; retry in {$retryAfterSeconds}s.");
    }
}
