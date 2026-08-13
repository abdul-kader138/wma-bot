<?php

namespace App\Services\Maria;

use App\Models\AssistantProfile;
use App\Models\MariaContact;
use App\Models\RelationshipRecommendation;
use App\Models\WorkflowRun;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DailyFiveService
{
    public function generate(AssistantProfile $profile, ?string $date = null): Collection
    {
        $date ??= now($profile->timezone)->toDateString();
        $existing = RelationshipRecommendation::where('user_id', $profile->user_id)
            ->whereDate('recommendation_date', $date)->with('contact')->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $run = WorkflowRun::create([
            'run_id' => (string) Str::uuid(), 'user_id' => $profile->user_id,
            'workflow_type' => 'daily_five_relationships', 'status' => 'running', 'started_at' => now(),
        ]);

        $contacts = MariaContact::where('user_id', $profile->user_id)
            ->whereNotNull('verification_source')
            ->whereNotIn('stage', ['partner', 'dormant'])
            ->get()->map(fn (MariaContact $contact) => ['contact' => $contact, 'score' => $this->score($contact)])
            ->sortByDesc('score')->take(5)->values();

        $recommendations = DB::transaction(function () use ($contacts, $date, $profile, $run) {
            $created = $contacts->map(function (array $candidate) use ($date, $profile, $run) {
                /** @var MariaContact $contact */
                $contact = $candidate['contact'];
                $organization = $contact->organization ? " at {$contact->organization}" : '';

                return RelationshipRecommendation::create([
                    'user_id' => $profile->user_id, 'maria_contact_id' => $contact->id,
                    'workflow_run_id' => $run->id, 'recommendation_date' => $date,
                    'score' => $candidate['score'], 'relevance' => $contact->why_matters ?: "Relevant {$contact->tier} relationship{$organization}.",
                    'warm_path' => $contact->warm_path,
                    'suggested_comment' => "Comment thoughtfully on {$contact->full_name}'s latest relevant post; add one concrete insight and no sales pitch.",
                    'connection_note' => "Hello {$contact->full_name}, I value your work{$organization} and would be glad to stay connected around our shared interests.",
                    'follow_up' => 'If there is no response, review once after seven days; do not automate outreach.',
                    'recommended_stage' => $this->nextStage($contact->stage),
                    'next_action_at' => now($profile->timezone)->addDay()->startOfDay()->utc(),
                ]);
            });

            $run->update([
                'status' => 'completed', 'input_references' => ['contact_ids' => $contacts->pluck('contact.id')->all()],
                'structured_output' => ['recommendation_ids' => $created->pluck('id')->all()],
                'estimated_manual_minutes' => $created->count() * 8, 'finished_at' => now(),
            ]);

            return $created;
        });

        $recommendations->each->load('contact');

        return $recommendations;
    }

    private function score(MariaContact $contact): int
    {
        $tier = ['A' => 40, 'B' => 25, 'C' => 10][$contact->tier] ?? 5;
        $due = $contact->follow_up_at?->isPast() ? 30 : ($contact->follow_up_at?->isBefore(now()->addWeek()) ? 20 : 0);
        $stale = $contact->last_interaction_at ? min(20, $contact->last_interaction_at->diffInDays(now())) : 20;
        $warm = filled($contact->warm_path) ? 10 : 0;

        return $tier + $due + $stale + $warm;
    }

    private function nextStage(string $stage): string
    {
        return ['research' => 'engage', 'engage' => 'connect', 'connect' => 'conversation', 'conversation' => 'meeting', 'meeting' => 'opportunity', 'opportunity' => 'partner'][$stage] ?? $stage;
    }
}
