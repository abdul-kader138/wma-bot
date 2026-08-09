<?php

namespace App\Contracts;

/**
 * A messaging platform (WhatsApp, Messenger, Instagram, ...) that HandleIncomingMessage
 * can send through and parse incoming webhook payloads from. Every method here mirrors
 * WhatsAppClient's existing public API exactly — this contract formalizes what already
 * exists rather than changing it, so a future MessengerChannel/InstagramChannel has a
 * concrete shape to implement against.
 */
interface MessagingChannel
{
    public function sendText(string $to, string $body): void;

    public function sendLanguageList(string $to): void;

    public function sendServiceButtons(string $to, string $lang): void;

    /**
     * Parse one platform-native webhook payload into the canonical shape the rest of
     * the app understands: ['message_id' => ?string, 'phone' => string, 'text' => ?string,
     * 'reply_id' => ?string]. Returns null when the payload carries no actionable message
     * (e.g. a delivery receipt).
     */
    public function parseIncoming(array $value): ?array;
}
