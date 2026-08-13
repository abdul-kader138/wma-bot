<?php

namespace App\Services\Maria;

use App\Models\Claim;
use App\Models\User;

class ClaimsVerificationService
{
    /** @return array{allowed:bool,matches:array,blocked_claims:array} */
    public function verify(User $owner, array $claimTexts, string $brand): array
    {
        $today = now($owner->assistantProfile?->timezone ?? config('app.timezone'))->toDateString();
        $matches = [];
        $blocked = [];
        $candidates = Claim::where('user_id', $owner->id)
            ->where('status', 'verified')
            ->where(fn ($query) => $query->whereNull('recheck_at')->orWhereDate('recheck_at', '>=', $today))
            ->get()
            ->filter(function (Claim $claim) use ($brand) {
                $brands = $claim->permitted_brands ?? [];

                return $brands === [] || in_array($brand, $brands, true);
            })
            ->keyBy(fn (Claim $claim) => $this->normalize($claim->claim_text));

        foreach ($claimTexts as $text) {
            $normalized = $this->normalize($text);
            $claim = $candidates->get($normalized);

            if ($claim) {
                $matches[] = ['text' => $text, 'claim_id' => $claim->id, 'source_url' => $claim->source_url];
            } else {
                $blocked[] = $text;
            }
        }

        return ['allowed' => $blocked === [], 'matches' => $matches, 'blocked_claims' => $blocked];
    }

    private function normalize(string $text): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', $text)));
    }
}
