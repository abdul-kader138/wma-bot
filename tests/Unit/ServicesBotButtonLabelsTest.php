<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Meta caps button/quick-reply titles at 20 characters on WhatsApp (interactive
 * buttons) and Messenger/Instagram (quick replies) alike. A label over that limit
 * doesn't get rejected until the Send API call actually runs — this test catches it
 * at config-edit time instead of in production (see rate_up_button/rate_down_button/
 * human_handover_button in config/services_bot.php, sent via
 * MessagingChannel::sendTextWithQuickActions()).
 */
class ServicesBotButtonLabelsTest extends TestCase
{
    public function test_quick_action_button_labels_fit_metas_20_character_limit(): void
    {
        foreach (['rate_up_button', 'rate_down_button', 'human_handover_button'] as $key) {
            foreach (config("services_bot.replies.{$key}") as $lang => $label) {
                $this->assertLessThanOrEqual(
                    20,
                    mb_strlen($label),
                    "services_bot.replies.{$key}.{$lang} (\"{$label}\") exceeds Meta's 20-character button title limit."
                );
            }
        }
    }
}
