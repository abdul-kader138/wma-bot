<?php

namespace Tests\Unit;

use App\Models\Conversation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConversationContactIdentifierTest extends TestCase
{
    #[DataProvider('platforms')]
    public function test_contact_identifier_type_is_platform_specific(string $platform, string $expected): void
    {
        $conversation = new Conversation(['platform' => $platform]);

        $this->assertSame($expected, $conversation->contactIdentifierType());
    }

    public static function platforms(): array
    {
        return [
            ['whatsapp', 'WhatsApp phone number'],
            ['messenger', 'Messenger user ID (PSID)'],
            ['instagram', 'Instagram user ID (IGSID)'],
        ];
    }
}
