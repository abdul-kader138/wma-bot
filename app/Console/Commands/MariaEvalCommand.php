<?php

namespace App\Console\Commands;

use App\Models\AssistantProfile;
use App\Models\MariaEvalRun;
use App\Models\MariaProject;
use App\Models\MariaTask;
use App\Models\PromptVersion;
use App\Models\User;
use App\Services\Maria\Eval\EvalRunner;
use App\Services\Maria\PromptResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MariaEvalCommand extends Command
{
    protected $signature = 'maria:eval
        {--live : Actually call the model (costs tokens). Without this the command only lists the cases.}
        {--filter= : Only run cases whose name or category contains this string}
        {--file= : Path to a cases file (default: resources/evals/maria.php)}
        {--prompt-version= : Evaluate this PromptVersion (by "version" string) instead of the active prompt}
        {--json= : Write the full result report to this JSON path}
        {--no-record : Do not save the run to maria_eval_runs (used for the activation gate)}';

    protected $description = 'Run the Maria behavioural eval suite against the chat agent and report pass/fail.';

    public function handle(EvalRunner $runner, PromptResolver $prompts): int
    {
        $path = $this->option('file') ?: resource_path('evals/maria.php');
        if (! is_file($path)) {
            $this->error("Cases file not found: {$path}");

            return self::FAILURE;
        }

        /** @var array<int,array<string,mixed>> $cases */
        $cases = require $path;
        if ($filter = $this->option('filter')) {
            $cases = array_values(array_filter(
                $cases,
                fn ($c) => str_contains(($c['name'] ?? '').' '.($c['category'] ?? ''), $filter)
            ));
        }

        $this->info(count($cases).' eval case(s) loaded from '.$path);

        $prompt = $this->resolvePrompt($prompts);
        if ($prompt === null) {
            return self::FAILURE;
        }
        $this->line("Prompt under test: {$prompt['version']}");

        if (! $this->option('live')) {
            foreach ($cases as $c) {
                $this->line("  · [{$c['category']}] {$c['name']}");
            }
            $this->comment('Dry run. Re-run with --live to execute against the model.');

            return self::SUCCESS;
        }

        DB::beginTransaction();
        $results = [];
        try {
            $owner = $this->seedOwner();
            foreach ($cases as $case) {
                $results[] = $runner->run($case, $owner, $prompt);
            }
        } finally {
            DB::rollBack();
        }

        return $this->report($results, $prompt);
    }

    /** @return array{version:string, content:string}|null */
    private function resolvePrompt(PromptResolver $prompts): ?array
    {
        $versionName = $this->option('prompt-version');
        if (! $versionName) {
            return $prompts->active();
        }

        $version = PromptVersion::query()
            ->where('prompt_type', 'maria_system')
            ->where('version', $versionName)
            ->first();

        if (! $version) {
            $this->error("No maria_system PromptVersion with version \"{$versionName}\".");

            return null;
        }

        return ['version' => $version->version, 'content' => $version->content];
    }

    private function seedOwner(): User
    {
        $owner = User::create([
            'name' => 'Maria Eval Owner',
            'email' => 'maria-eval+'.uniqid().'@example.invalid',
            'password' => bcrypt(Str::random(32)),
        ]);
        AssistantProfile::create([
            'user_id' => $owner->id, 'timezone' => 'Europe/Rome', 'is_active' => true,
            'enabled_workflows' => ['morning_brief', 'evening_review', 'deadline_monitor'],
        ]);
        $project = MariaProject::create([
            'user_id' => $owner->id, 'domain' => 'BKS', 'name' => 'Autumn book launch',
            'desired_outcome' => 'Final manuscript approved', 'owner_name' => $owner->name,
            'next_action' => 'Review the marked manuscript', 'next_action_at' => now(),
            'stage' => 'active', 'status' => 'scheduled',
        ]);
        MariaTask::create([
            'user_id' => $owner->id, 'project_id' => $project->id, 'task' => 'Review the marked manuscript',
            'owner_name' => $owner->name, 'status' => 'open', 'source' => 'user',
        ]);
        MariaTask::create([
            'user_id' => $owner->id, 'task' => 'Reply to Sr. Angela about the retreat',
            'owner_name' => $owner->name, 'status' => 'open', 'source' => 'user', 'due_at' => now(),
        ]);

        return $owner;
    }

    /**
     * @param  array<int,array<string,mixed>>  $results
     * @param  array{version:string, content:string}  $prompt
     */
    private function report(array $results, array $prompt): int
    {
        $rows = [];
        $failed = 0;
        foreach ($results as $r) {
            $ok = $r['passed'] ?? false;
            $failed += $ok ? 0 : 1;
            $detail = $r['error']
                ?? implode(' | ', array_map(
                    fn ($c) => ($c['passed'] ? '✓' : '✗').' '.$c['type'].': '.$c['detail'],
                    array_filter($r['checks'], fn ($c) => ! $c['passed']) ?: $r['checks']
                ));
            $rows[] = [$ok ? 'PASS' : 'FAIL', $r['category'], $r['name'], Str::limit($detail, 80)];
        }

        $this->table(['', 'Category', 'Case', 'Detail'], $rows);

        $total = count($results);
        $passed = $total - $failed;
        $passRate = $total > 0 ? round($passed / $total * 100, 1) : 0.0;
        $this->line('');
        $this->{$failed === 0 ? 'info' : 'error'}("{$passed}/{$total} cases passed ({$passRate}%).");

        if (! $this->option('no-record')) {
            MariaEvalRun::create([
                'prompt_type' => 'maria_system',
                'prompt_version' => $prompt['version'],
                'prompt_content_hash' => hash('sha256', Str::of($prompt['content'])->trim()->toString()),
                'total' => $total, 'passed' => $passed, 'failed' => $failed, 'pass_rate' => $passRate,
                'results' => $results,
            ]);
            $this->line('Recorded to maria_eval_runs.');
        }

        if ($jsonPath = $this->option('json')) {
            file_put_contents($jsonPath, json_encode([
                'generated_at' => now()->toIso8601String(),
                'prompt_version' => $prompt['version'],
                'total' => $total, 'passed' => $passed, 'failed' => $failed, 'pass_rate' => $passRate,
                'results' => $results,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $this->line("Report written to {$jsonPath}");
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
