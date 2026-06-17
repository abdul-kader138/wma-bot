<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Collection;

class FaqMatcher
{
    private const FUZZY_THRESHOLD = 0.5;

    public function match(string $text, ?string $service): ?Faq
    {
        $normalized = $this->normalize($text);
        if ($normalized === '') {
            return null;
        }

        $candidates = $this->candidates($service);

        foreach ($candidates as $faq) {
            foreach ($faq->keywords as $keyword) {
                $needle = $this->normalize($keyword);
                if ($needle !== '' && str_contains($normalized, $needle)) {
                    return $faq;
                }
            }
        }

        $words     = $this->words($normalized);
        $best      = null;
        $bestScore = 0.0;

        foreach ($candidates as $faq) {
            $score = $this->overlapScore($words, $faq);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best      = $faq;
            }
        }

        return $bestScore >= self::FUZZY_THRESHOLD ? $best : null;
    }

    protected function candidates(?string $service): Collection
    {
        return Faq::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('service')->orWhere('service', $service))
            ->get();
    }

    private function overlapScore(array $words, Faq $faq): float
    {
        $targetWords = $this->words($this->normalize($faq->question));
        foreach ($faq->keywords as $keyword) {
            $targetWords = array_merge($targetWords, $this->words($this->normalize($keyword)));
        }
        $targetWords = array_unique($targetWords);

        if (empty($words) || empty($targetWords)) {
            return 0.0;
        }

        $intersection = array_intersect($words, $targetWords);
        $union        = array_unique(array_merge($words, $targetWords));

        return count($intersection) / count($union);
    }

    /** Words of 3+ characters, to keep short stop words from diluting the overlap score. */
    private function words(string $normalized): array
    {
        return array_values(array_filter(explode(' ', $normalized), fn ($w) => mb_strlen($w) > 2));
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
