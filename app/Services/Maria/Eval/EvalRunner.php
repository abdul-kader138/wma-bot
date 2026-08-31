<?php

namespace App\Services\Maria\Eval;

use App\Models\User;
use App\Services\Maria\MariaAgent;
use Throwable;

/**
 * Runs one eval case against the real Maria chat agent and scores its checks.
 * A case is an array:
 *   [
 *     'name' => string,
 *     'category' => string,
 *     'message' => string,           // what the "owner" says
 *     'history' => array,            // optional prior turns
 *     'checks' => [[type, arg], ...] // assertions from Checks::run()
 *   ]
 */
class EvalRunner
{
    public function __construct(private readonly MariaAgent $agent) {}

    /**
     * @param  array{version:string, content:string}|null  $promptOverride
     * @return array{name:string, category:string, passed:bool, error:?string, checks:array<int,array{type:string,passed:bool,detail:string}>, reply:string}
     */
    public function run(array $case, User $owner, ?array $promptOverride = null): array
    {
        $name = (string) ($case['name'] ?? 'unnamed');
        $category = (string) ($case['category'] ?? 'general');

        try {
            $result = $this->agent->handle(
                $owner,
                (string) ($case['message'] ?? ''),
                (array) ($case['history'] ?? []),
                $promptOverride,
            );
        } catch (Throwable $e) {
            return [
                'name' => $name, 'category' => $category, 'passed' => false,
                'error' => $e->getMessage(), 'checks' => [], 'reply' => '',
            ];
        }

        $bag = [
            'text' => (string) $result['text'],
            'tools' => array_values(array_unique(array_column($result['tool_calls'], 'name'))),
            'data' => [],
        ];

        $checks = [];
        $allPassed = true;
        foreach ((array) ($case['checks'] ?? []) as $spec) {
            $type = (string) ($spec[0] ?? '');
            $outcome = Checks::run($type, $spec[1] ?? null, $bag);
            $checks[] = ['type' => $type, 'passed' => $outcome['passed'], 'detail' => $outcome['detail']];
            $allPassed = $allPassed && $outcome['passed'];
        }

        return [
            'name' => $name, 'category' => $category, 'passed' => $allPassed && $checks !== [],
            'error' => $checks === [] ? 'case has no checks' : null,
            'checks' => $checks, 'reply' => $bag['text'],
        ];
    }
}
