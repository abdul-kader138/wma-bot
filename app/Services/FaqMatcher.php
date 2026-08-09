<?php

namespace App\Services;

use App\Models\Faq;
use App\Models\Setting;
use Illuminate\Support\Collection;

class FaqMatcher
{
    private const FUZZY_THRESHOLD = 0.5;

    /** Below this length, skip the "needle contains input" reverse check to avoid short/generic messages over-matching. */
    private const MIN_LENGTH_FOR_REVERSE_CONTAINS = 8;

    /** similar_text() percentage two canonicalized words must clear to count as a phonetic match. */
    private const PHONETIC_SIMILARITY_THRESHOLD = 72.0;

    /** Bengali consonants -> rough Latin sound. Used to bridge Bengali-script FAQs with Banglish (Latin-typed) queries. */
    private const BN_CONSONANTS = [
        'ক' => 'k', 'খ' => 'k', 'গ' => 'g', 'ঘ' => 'g', 'ঙ' => 'n',
        'চ' => 'c', 'ছ' => 'c', 'জ' => 'j', 'ঝ' => 'j', 'ঞ' => 'n',
        'ট' => 't', 'ঠ' => 't', 'ড' => 'd', 'ঢ' => 'd', 'ণ' => 'n',
        'ত' => 't', 'থ' => 't', 'দ' => 'd', 'ধ' => 'd', 'ন' => 'n',
        'প' => 'p', 'ফ' => 'p', 'ব' => 'b', 'ভ' => 'b', 'ম' => 'm',
        'য' => 'j', 'র' => 'r', 'ল' => 'l',
        'শ' => 's', 'ষ' => 's', 'স' => 's', 'হ' => 'h',
        'ড়' => 'r', 'ঢ়' => 'r', 'য়' => 'y', 'ৎ' => 't',
    ];

    /** Bengali vowel signs (kar) -> Latin sound. */
    private const BN_VOWEL_SIGNS = [
        'া' => 'a', 'ি' => 'i', 'ী' => 'i', 'ু' => 'u', 'ূ' => 'u',
        'ৃ' => 'ri', 'ে' => 'e', 'ৈ' => 'oi', 'ো' => 'o', 'ৌ' => 'ou',
    ];

    /** Bengali independent vowels -> Latin sound. */
    private const BN_INDEPENDENT_VOWELS = [
        'অ' => 'o', 'আ' => 'a', 'ই' => 'i', 'ঈ' => 'i', 'উ' => 'u', 'ঊ' => 'u',
        'ঋ' => 'ri', 'এ' => 'e', 'ঐ' => 'oi', 'ও' => 'o', 'ঔ' => 'ou',
    ];

    /** Nasalization/aspiration marks -> Latin sound (chandrabindu is dropped, it has no Latin equivalent). */
    private const BN_DIACRITICS = [
        'ং' => 'n', 'ঃ' => 'h', 'ঁ' => '',
    ];

    private const BN_HASANT = "\u{09CD}";

    public function match(string $text, ?string $service, ?int $whatsappAccountId = null): ?Faq
    {
        $normalized = $this->normalize($text);
        if ($normalized === '') {
            return null;
        }

        $candidates = $this->candidates($service, $whatsappAccountId);

        foreach ($candidates as $faq) {
            foreach ($this->triggerTexts($faq) as $trigger) {
                $needle = $this->normalize($trigger);
                if ($needle === '') {
                    continue;
                }

                if (str_contains($normalized, $needle)) {
                    return $faq;
                }

                if (mb_strlen($normalized) >= self::MIN_LENGTH_FOR_REVERSE_CONTAINS
                    && str_contains($needle, $normalized)) {
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

        $threshold = (float) (Setting::get('faq_confidence_threshold') ?? self::FUZZY_THRESHOLD);

        return $bestScore >= $threshold ? $best : null;
    }

    /** Question text (all languages) plus trigger phrases — anything the user might literally type. */
    private function triggerTexts(Faq $faq): array
    {
        return array_merge($faq->questionVariants(), $faq->keywords ?? []);
    }

    protected function candidates(?string $service, ?int $whatsappAccountId = null): Collection
    {
        return Faq::query()
            ->where('is_active', true)
            ->where('whatsapp_account_id', $whatsappAccountId)
            ->where(fn ($q) => $q->whereNull('service')->orWhere('service', $service))
            ->get();
    }

    private function overlapScore(array $words, Faq $faq): float
    {
        $targetWords = [];
        foreach ($this->triggerTexts($faq) as $text) {
            $targetWords = array_merge($targetWords, $this->words($this->normalize($text)));
        }
        $targetWords = array_unique($targetWords);

        if (empty($words) || empty($targetWords)) {
            return 0.0;
        }

        $intersection = array_intersect($words, $targetWords);

        // Overlap coefficient (intersection / smaller set), not Jaccard (intersection / union):
        // a short user message shouldn't be penalized just because the FAQ's combined
        // question + trigger phrases are much longer.
        $exactScore = count($intersection) / min(count($words), count($targetWords));

        // Bridges Bengali-script FAQs with Banglish (Latin-typed) queries, e.g. "patenta"
        // matching a stored "পাতেন্তে" — plain word equality above can never catch that
        // since the two scripts share no codepoints.
        return max($exactScore, $this->phoneticOverlapScore($words, $targetWords));
    }

    private function phoneticOverlapScore(array $words, array $targetWords): float
    {
        $wordKeys   = array_values(array_unique(array_map(fn ($w) => $this->phoneticKey($w), $words)));
        $targetKeys = array_values(array_unique(array_map(fn ($w) => $this->phoneticKey($w), $targetWords)));

        if (empty($wordKeys) || empty($targetKeys)) {
            return 0.0;
        }

        $matchedTargets = [];
        $matches        = 0;

        foreach ($wordKeys as $wordKey) {
            if ($wordKey === '') {
                continue;
            }

            foreach ($targetKeys as $i => $targetKey) {
                if (isset($matchedTargets[$i]) || $targetKey === '') {
                    continue;
                }

                similar_text($wordKey, $targetKey, $percent);

                if ($percent >= self::PHONETIC_SIMILARITY_THRESHOLD) {
                    $matchedTargets[$i] = true;
                    $matches++;
                    break;
                }
            }
        }

        return $matches / min(count($wordKeys), count($targetKeys));
    }

    /**
     * A rough, script-independent "sound" for a word: Bengali-script words are transliterated
     * to Latin first, then both Bengali-derived and native Latin/Banglish words are run through
     * the same canonicalization so equivalent spellings converge (e.g. "পাতেন্তে" and "patenta"
     * both reduce to "patent").
     */
    private function phoneticKey(string $word): string
    {
        $latin = $this->hasBengaliScript($word) ? $this->transliterateBengali($word) : $word;

        return $this->canonicalizeLatin($latin);
    }

    private function hasBengaliScript(string $text): bool
    {
        return (bool) preg_match('/\p{Bengali}/u', $text);
    }

    private function transliterateBengali(string $word): string
    {
        $chars = mb_str_split($word);
        $count = count($chars);
        $out   = '';

        for ($i = 0; $i < $count; $i++) {
            $char = $chars[$i];

            if (isset(self::BN_CONSONANTS[$char])) {
                $out .= self::BN_CONSONANTS[$char];
                $next = $chars[$i + 1] ?? null;

                if ($next === self::BN_HASANT) {
                    $i++; // swallow the hasant: no inherent vowel, consonant cluster continues
                } elseif ($next !== null && (isset(self::BN_VOWEL_SIGNS[$next]) || isset(self::BN_DIACRITICS[$next]))) {
                    // an explicit vowel sign/diacritic follows and will be appended on the next iteration
                } else {
                    $out .= 'o'; // bare consonant carries its inherent vowel
                }

                continue;
            }

            if (isset(self::BN_VOWEL_SIGNS[$char])) {
                $out .= self::BN_VOWEL_SIGNS[$char];

                continue;
            }

            if (isset(self::BN_INDEPENDENT_VOWELS[$char])) {
                $out .= self::BN_INDEPENDENT_VOWELS[$char];

                continue;
            }

            if (isset(self::BN_DIACRITICS[$char])) {
                $out .= self::BN_DIACRITICS[$char];

                continue;
            }

            if ($char === self::BN_HASANT) {
                continue; // stray hasant with no preceding consonant, drop it
            }

            $out .= $char; // non-Bengali character (digits, stray Latin), keep as-is
        }

        return $out;
    }

    private function canonicalizeLatin(string $text): string
    {
        $text = mb_strtolower($text);

        // Collapse spellings that are ambiguous between transliterated Bengali and how people
        // actually type Banglish, so both sides converge on the same rough key.
        $text = strtr($text, [
            'bh' => 'b', 'dh' => 'd', 'gh' => 'g', 'kh' => 'k', 'th' => 't',
            'chh' => 'c', 'ph' => 'f', 'sh' => 's', 'ch' => 'c',
            'oi' => 'i', 'ou' => 'u', 'aa' => 'a', 'ee' => 'i', 'oo' => 'u',
            'v' => 'b', 'w' => 'b', 'z' => 'j', 'y' => 'i',
        ]);

        // Collapse runs of the same letter (e.g. "patenna" vs "patena").
        $text = (string) preg_replace('/(.)\1+/u', '$1', $text);

        // The trailing vowel is the least stable part of a transliterated/typed word; drop it.
        $trimmed = rtrim($text, 'aeiou');

        return $trimmed !== '' ? $trimmed : $text;
    }

    /** Words of 3+ characters, to keep short stop words from diluting the overlap score. */
    private function words(string $normalized): array
    {
        return array_values(array_filter(explode(' ', $normalized), fn ($w) => mb_strlen($w) > 2));
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower($text);
        // \p{M} keeps combining marks (Bengali vowel signs/hasant are Mn/Mc, not \p{L}) so
        // Bengali words survive intact instead of being shredded into bare consonants.
        $text = preg_replace('/[^\p{L}\p{N}\p{M}\s]/u', ' ', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }
}
