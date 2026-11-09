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
     * Same as sendText, but with the 3 quick-reply buttons WhatsApp's cap allows
     * attached: 👍 Helpful / 👎 Not helpful / 🙋 Talk to us (ids rate_up/rate_down/
     * human). "Change service" and "close conversation" no longer get their own
     * buttons — a one-line typed-keyword hint is appended to the body instead, so
     * that functionality survives without needing a 4th button.
     */
    public function sendTextWithQuickActions(string $to, string $body, string $lang): void;

    public function sendLanguageList(string $to): void;

    public function sendServiceButtons(string $to, string $lang): void;

    /**
     * Lists active CatalogueEntry rows visible under one service (see
     * CatalogueEntry::listForService()) as a tappable list/quick-reply message.
     * Selecting one returns reply_id "cat_{entry id}", which HandleIncomingMessage
     * resolves back to that entry rather than passing it to Claude.
     */
    public function sendCatalogueList(string $to, string $lang, int $serviceId): void;

    /**
     * Parse one platform-native webhook payload into the canonical shape the rest of
     * the app understands: ['message_id' => ?string, 'phone' => string, 'text' => ?string,
     * 'reply_id' => ?string]. Returns null when the payload carries no actionable message
     * (e.g. a delivery receipt).
     */
    public function parseIncoming(array $value): ?array;
}
