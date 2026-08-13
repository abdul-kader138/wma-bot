<?php

namespace App\Services\Maria;

use App\Models\ContentItem;
use App\Models\WorkflowRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ContentPackageService
{
    public function __construct(
        private readonly StructuredWorkflowAgent $agent,
        private readonly PromptResolver $prompts,
        private readonly ClaimsVerificationService $claims,
    ) {}

    public function generate(ContentItem $item): ContentItem
    {
        if ($item->status === 'draft' && filled($item->master_draft)) {
            return $item;
        }

        $verification = $this->claims->verify($item->user, $item->core_claims ?? [], $item->brand);
        if (! $verification['allowed']) {
            $item->update(['status' => 'blocked_claims', 'claim_verification' => $verification]);
            throw ValidationException::withMessages(['core_claims' => 'Unverified, expired, or brand-restricted claims: '.implode('; ', $verification['blocked_claims'])]);
        }

        $run = WorkflowRun::create([
            'run_id' => (string) Str::uuid(), 'user_id' => $item->user_id,
            'workflow_type' => 'content_package', 'status' => 'running', 'started_at' => now(),
            'input_references' => ['content_item_id' => $item->id, 'claim_ids' => collect($verification['matches'])->pluck('claim_id')->all()],
        ]);

        try {
            $prompt = $this->prompts->active('content_package');
            $input = [
                'brand' => $item->brand, 'content_pillar' => $item->content_pillar, 'audience' => $item->audience,
                'source_idea' => $item->source_idea, 'source_url' => $item->source_url,
                'verified_claims' => $verification['matches'],
                'rules' => ['draft_only', 'preserve_brand_separation', 'attribute_sources', 'no_new_factual_claims', 'no_automatic_publishing'],
            ];
            $result = $this->agent->run(
                $prompt['content'], 'Create one content package using only this trusted JSON: '.json_encode($input, JSON_THROW_ON_ERROR),
                'save_content_package', 'Return a complete draft-only multi-channel content package.', $this->schema(),
            );

            return DB::transaction(function () use ($item, $run, $prompt, $result, $verification) {
                $data = $result['data'];
                $item->update([
                    'workflow_run_id' => $run->id, 'master_draft' => $data['linkedin_authority_post'],
                    'derivatives' => $data, 'claim_verification' => $verification, 'status' => 'draft', 'generated_at' => now(),
                ]);
                $run->update([
                    'status' => 'completed', 'structured_output' => ['content_item_id' => $item->id],
                    'prompt_version' => $prompt['version'], 'input_tokens' => $result['usage']['input_tokens'],
                    'output_tokens' => $result['usage']['output_tokens'], 'estimated_manual_minutes' => 120, 'finished_at' => now(),
                ]);

                return $item->refresh();
            });
        } catch (Throwable $error) {
            $run->update(['status' => 'failed', 'error' => mb_substr($error->getMessage(), 0, 5000), 'finished_at' => now()]);
            throw $error;
        }
    }

    public static function sourceHash(string $idea): string
    {
        return hash('sha256', mb_strtolower(trim(preg_replace('/\s+/', ' ', $idea))));
    }

    private function schema(): array
    {
        $string = ['type' => 'string'];

        return ['type' => 'object', 'properties' => [
            'linkedin_authority_post' => $string, 'story_or_quotation_post' => $string,
            'podcast_outline' => ['type' => 'array', 'items' => $string],
            'video_scripts' => ['type' => 'array', 'minItems' => 3, 'maxItems' => 3, 'items' => $string],
            'newsletter_section' => $string, 'comment_angles' => ['type' => 'array', 'items' => $string],
            'relationship_outreach_angle' => $string, 'attributions' => ['type' => 'array', 'items' => $string],
        ], 'required' => ['linkedin_authority_post', 'story_or_quotation_post', 'podcast_outline', 'video_scripts', 'newsletter_section', 'comment_angles', 'relationship_outreach_angle', 'attributions']];
    }
}
