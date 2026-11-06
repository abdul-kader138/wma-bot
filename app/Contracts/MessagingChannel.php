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

    /**
     * Same as sendText, but with persistent "change service" and "close conversation"
     * quick replies/buttons attached — so a customer mid-way through one service can
     * switch to another, or end the chat, with a single tap in any language, without
     * needing to type a keyword.
     */
    public function sendTextWithQuickActions(string $to, string $body, string $lang): void;

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
