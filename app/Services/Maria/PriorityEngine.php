<?php

namespace App\Services\Maria;

use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class PriorityEngine
{
    private const COMPONENTS = ['mission', 'relationship', 'urgency', 'strategic', 'effort', 'risk'];

    /** @return array{score:int, scores:array, requires_review:bool, override_reasons:array, reason:string} */
    public function score(array $scores, string $reason, array $context = []): array
    {
        foreach (self::COMPONENTS as $component) {
            $value = $scores[$component] ?? null;
            if (! is_int($value) || $value < 1 || $value > 5) {
                throw ValidationException::withMessages([
                    "scores.{$component}" => "{$component} must be an integer from 1 to 5.",
                ]);
            }
        }

        $total = $scores['mission'] + $scores['relationship'] + $scores['urgency']
            + $scores['strategic'] - $scores['effort'] - $scores['risk'];
        $overrides = $this->overrideReasons($context);

        return [
            'score' => $total,
            'scores' => $scores,
            'requires_review' => $scores['risk'] >= 4 || $overrides !== [],
            'override_reasons' => $overrides,
            'reason' => trim($reason),
        ];
    }

    private function overrideReasons(array $context): array
    {
        $reasons = [];
        $categories = array_map('strtolower', (array) ($context['categories'] ?? []));
        $mandatory = ['health', 'court', 'contract', 'finance', 'travel', 'public_commitment'];

        foreach (array_intersect($mandatory, $categories) as $category) {
            $reasons[] = "mandatory_review:{$category}";
        }

        $deadline = $context['deadline_at'] ?? null;
        if ($deadline instanceof CarbonInterface && $deadline->isPast()) {
            $reasons[] = 'overdue_deadline';
        }

        return array_values(array_unique($reasons));
    }
}
