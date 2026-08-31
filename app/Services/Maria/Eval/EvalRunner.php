<?php

namespace App\Services\Maria\Eval;

use App\Models\User;
use App\Services\Maria\MariaAgent;
use App\Services\Maria\StructuredWorkflowAgent;
use Throwable;

/**
 * Runs one eval case and scores its checks. Two kinds:
 *
 *  chat (default):
 *    ['name','category','message', 'history'?, 'checks'=>[[type,arg],...]]
 *    -> exercises MariaAgent; checks see text + tools.
 *
 *  workflow:
 *    ['name','category','kind'=>'workflow','instruction','schema', 'system'?, 'checks']
 *    -> exercises StructuredWorkflowAgent; checks see the validated `data`.
 */
class EvalRunner
{
    public function __construct(
        private readonly MariaAgent $agent,
        private readonly StructuredWorkflowAgent $workflowAgent,
    ) {}

    /**
     * @param  array{version:string, content:string}|null  $promptOverride
     * @return array{name:string, category:string, passed:bool, error:?string, checks:array<int,array{type:string,passed:bool,detail:string}>, reply:string}
     */
    public function run(array $case, User $owner, ?array $promptOverride = null): array
    {
        $name = (string) ($case['name'] ?? 'unnamed');
        $category = (string) ($case['category'] ?? 'general');

        try {
            $bag = ($case['kind'] ?? 'chat') === 'workflow'
                ? $this->runWorkflow($case, $promptOverride)
                : $this->runChat($case, $owner, $promptOverride);
        } catch (Throwable $e) {
            return [
                'name' => $name, 'category' => $category, 'passed' => false,
                'error' => $e->getMessage(), 'checks' => [], 'reply' => '',
            ];
        }

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
            'checks' => $checks,
            'reply' => $bag['text'] !== '' ? $bag['text'] : (string) json_encode($bag['data']),
        ];
    }

    /**
     * @param  array{version:string, content:string}|null  $promptOverride
     * @return array{text:string, tools:list<string>, data:array<mixed>}
     */
    private function runChat(array $case, User $owner, ?array $promptOverride): array
    {
        $result = $this->agent->handle(
            $owner,
            (string) ($case['message'] ?? ''),
            (array) ($case['history'] ?? []),
            $promptOverride,
        );

        return [
            'text' => (string) $result['text'],
            'tools' => array_values(array_unique(array_column($result['tool_calls'], 'name'))),
            'data' => [],
        ];
    }

    /**
     * @param  array{version:string, content:string}|null  $promptOverride
     * @return array{text:string, tools:list<string>, data:array<mixed>}
     */
    private function runWorkflow(array $case, ?array $promptOverride): array
    {
        $system = $case['system'] ?? ($promptOverride['content'] ?? config('maria.system_prompt'));

        $result = $this->workflowAgent->run(
            (string) $system,
            (string) ($case['instruction'] ?? ''),
            'save_eval_output',
            'Return the structured result for this eval case.',
            (array) ($case['schema'] ?? ['type' => 'object']),
        );

        return ['text' => '', 'tools' => [], 'data' => $result['data']];
    }
}
