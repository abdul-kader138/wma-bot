<?php

namespace App\Services\Maria\Support;

/**
 * Maps an AssistantProfile language code to an English language name for use in
 * prompts, so Maria answers the owner in their configured working language.
 */
class Language
{
    private const NAMES = ['en' => 'English', 'it' => 'Italian', 'bn' => 'Bengali'];

    public static function name(?string $code): ?string
    {
        return self::NAMES[$code] ?? null;
    }

    /** Clause to append to a structured-workflow instruction (empty for the default). */
    public static function instructionClause(?string $code): string
    {
        $name = self::name($code);

        return $name === null || $name === 'English'
            ? ''
            : " Write every human-readable value in {$name}.";
    }

    /** Prefix for a chat message so replies come back in the owner's language (empty for the default). */
    public static function chatDirective(?string $code): string
    {
        $name = self::name($code);

        return $name === null || $name === 'English'
            ? ''
            : "(Always respond to the owner in {$name}.)\n\n";
    }
}
