<?php

namespace App\Services\Maria\Support;

use App\Models\Setting;

/**
 * Reads the admin-configured lists of senders and Gmail labels that Maria's
 * email workflows must never look at, and decides whether a given message is
 * covered. Both settings are newline/comma separated and default to empty.
 */
class TriageExclusions
{
    /** @var list<string> */
    private array $senders;

    /** @var list<string> */
    private array $labels;

    public function __construct()
    {
        $this->senders = self::list(Setting::get('maria_triage_excluded_senders', ''));
        $this->labels = self::list(Setting::get('maria_triage_excluded_labels', ''));
    }

    /**
     * @param  string|null  $from  raw From header, e.g. `Jane Roe <jane@bank.example>`
     * @param  list<string>  $labelIds
     */
    public function excludes(?string $from, array $labelIds = []): bool
    {
        if (array_intersect(array_map('strtolower', $labelIds), $this->labels) !== []) {
            return true;
        }

        $address = self::address($from);
        if ($address === '') {
            return false;
        }

        $domain = str_contains($address, '@') ? substr($address, strrpos($address, '@') + 1) : '';

        foreach ($this->senders as $entry) {
            if ($entry === $address || ($entry !== '' && $entry === $domain)) {
                return true;
            }
        }

        return false;
    }

    public function hasAny(): bool
    {
        return $this->senders !== [] || $this->labels !== [];
    }

    private static function address(?string $from): string
    {
        if (! $from) {
            return '';
        }
        if (preg_match('/<([^>]+)>/', $from, $m)) {
            return strtolower(trim($m[1]));
        }

        return strtolower(trim($from));
    }

    /** @return list<string> */
    private static function list(mixed $raw): array
    {
        return array_values(array_filter(array_map(
            fn ($v) => strtolower(trim((string) $v)),
            preg_split('/[\r\n,]+/', (string) $raw) ?: []
        )));
    }
}
