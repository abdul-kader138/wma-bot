<?php

namespace App\Services\Maria;

use App\Models\Claim;
use App\Models\User;

class ClaimsVerificationService
{
    /** @return array{allowed:bool,matches:array,blocked_claims:array} */
    public function verify(User $owner, array $claimTexts, string $brand): array
    {
        $matches = [];
        $blocked = [];

        foreach ($claimTexts as $text) {
            $normalized = $this->normalize($text);
            $claim = Claim::where('user_id', $owner->id)->get()->first(function (Claim $candidate) use ($normalized, $brand) {
                $brands = $candidate->permitted_brands ?? [];

                return $this->normalize($candidate->claim_text) === $normalized
                    && $candidate->status === 'verified'
                    && ($candidate->recheck_at === null || $candidate->recheck_at->isFuture())
                    && ($brands === [] || in_array($brand, $brands, true));
            });

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
