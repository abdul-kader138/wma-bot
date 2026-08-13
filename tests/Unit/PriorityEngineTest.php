<?php

namespace Tests\Unit;

use App\Services\Maria\PriorityEngine;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PriorityEngineTest extends TestCase
{
    public function test_it_calculates_and_retains_auditable_components(): void
    {
        $result = app(PriorityEngine::class)->score([
            'mission' => 5, 'relationship' => 4, 'urgency' => 3,
            'strategic' => 5, 'effort' => 2, 'risk' => 1,
        ], 'Supports the active mission goal.');

        $this->assertSame(14, $result['score']);
        $this->assertSame(5, $result['scores']['mission']);
        $this->assertFalse($result['requires_review']);
    }

    public function test_protected_category_forces_review_regardless_of_score(): void
    {
        $result = app(PriorityEngine::class)->score([
            'mission' => 1, 'relationship' => 1, 'urgency' => 1,
            'strategic' => 1, 'effort' => 5, 'risk' => 1,
        ], 'Contract deadline.', ['categories' => ['contract']]);

        $this->assertTrue($result['requires_review']);
        $this->assertContains('mandatory_review:contract', $result['override_reasons']);
    }

    public function test_component_outside_one_to_five_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(PriorityEngine::class)->score([
            'mission' => 6, 'relationship' => 1, 'urgency' => 1,
            'strategic' => 1, 'effort' => 1, 'risk' => 1,
        ], 'Invalid');
    }
}
