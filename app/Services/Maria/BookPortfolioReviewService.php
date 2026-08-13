<?php

namespace App\Services\Maria;

use App\Models\AssistantBrief;
use App\Models\AssistantProfile;
use App\Models\Book;
use App\Models\WorkflowRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class BookPortfolioReviewService
{
    public function __construct(private readonly StructuredWorkflowAgent $agent, private readonly PromptResolver $prompts) {}

    public function generate(AssistantProfile $profile, ?string $weekDate = null): AssistantBrief
    {
        $week = $weekDate ? Carbon::parse($weekDate, $profile->timezone)->startOfWeek() : now($profile->timezone)->startOfWeek();
        $briefDate = $week->toDateString();
        $existing = AssistantBrief::where('user_id', $profile->user_id)->where('type', 'book_portfolio')->whereDate('brief_date', $briefDate)->first();
        if ($existing) {
            return $existing;
        }

        $run = WorkflowRun::create([
            'run_id' => (string) Str::uuid(), 'user_id' => $profile->user_id,
            'workflow_type' => 'book_portfolio_review', 'status' => 'running', 'started_at' => now(),
        ]);

        try {
            $books = Book::where('user_id', $profile->user_id)->where('status', 'active')->orderBy('milestone_due_at')->get();
            $portfolio = $books->map(fn (Book $book) => [
                'id' => $book->id, 'exact_title' => $book->exact_title, 'subtitle' => $book->subtitle,
                'credits' => $book->credits, 'edition' => $book->edition, 'stage' => $book->stage,
                'milestone' => $book->current_milestone, 'owner' => $book->milestone_owner,
                'date' => $book->milestone_due_at?->toDateString(), 'blocker' => $book->blocker,
                'contributors' => $book->contributors, 'publication_target' => $book->publication_target?->toDateString(),
                'marketing_status' => $book->marketing_status, 'next_action' => $book->next_action,
            ])->all();
            $prompt = $this->prompts->active('book_portfolio_review');
            $result = $this->agent->run(
                $prompt['content'], 'Review this book portfolio JSON. Preserve every exact title, edition, owner, and date. Do not infer missing facts. Rank only three actions supported by the records: '.json_encode($portfolio, JSON_THROW_ON_ERROR),
                'save_book_portfolio_review', 'Return the weekly book portfolio review.', $this->schema(),
            );

            return DB::transaction(function () use ($profile, $briefDate, $run, $books, $prompt, $result) {
                $run->update([
                    'status' => 'completed', 'input_references' => ['book_ids' => $books->pluck('id')->all()],
                    'structured_output' => $result['data'], 'prompt_version' => $prompt['version'],
                    'input_tokens' => $result['usage']['input_tokens'], 'output_tokens' => $result['usage']['output_tokens'],
                    'estimated_manual_minutes' => 45, 'finished_at' => now(),
                ]);

                return AssistantBrief::create([
                    'user_id' => $profile->user_id, 'workflow_run_id' => $run->id,
                    'type' => 'book_portfolio', 'brief_date' => $briefDate, 'content' => $result['data'],
                ]);
            });
        } catch (Throwable $error) {
            $run->update(['status' => 'failed', 'error' => mb_substr($error->getMessage(), 0, 5000), 'finished_at' => now()]);
            throw $error;
        }
    }

    private function schema(): array
    {
        return ['type' => 'object', 'properties' => [
            'week' => ['type' => 'string'], 'books' => ['type' => 'array', 'items' => ['type' => 'object']],
            'highest_value_actions' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 3, 'items' => ['type' => 'object']],
            'source_gaps' => ['type' => 'array', 'items' => ['type' => 'string']],
        ], 'required' => ['week', 'books', 'highest_value_actions', 'source_gaps']];
    }
}
