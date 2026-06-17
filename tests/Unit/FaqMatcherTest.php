<?php

namespace Tests\Unit;

use App\Models\Faq;
use App\Services\FaqMatcher;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class FaqMatcherTest extends TestCase
{
    private function matcher(array $faqs): FaqMatcher
    {
        $matcher = $this->getMockBuilder(FaqMatcher::class)
            ->onlyMethods(['candidates'])
            ->getMock();

        $matcher->method('candidates')
            ->willReturn(new Collection(array_map(function (array $data) {
                $faq           = new Faq();
                $faq->question = $data['question'];
                $faq->keywords = $data['keywords'];
                $faq->answer   = $data['answer'] ?? ['en' => 'Test answer'];
                $faq->is_active = true;

                return $faq;
            }, $faqs)));

        return $matcher;
    }

    public function test_keyword_match_returns_faq(): void
    {
        $matcher = $this->matcher([[
            'question' => 'What is the price?',
            'keywords' => ['price', 'cost', 'how much'],
        ]]);

        $result = $matcher->match('how much does it cost?', null);

        $this->assertNotNull($result);
        $this->assertSame('What is the price?', $result->question);
    }

    public function test_fuzzy_match_returns_faq_for_paraphrased_question(): void
    {
        $matcher = $this->matcher([[
            'question' => 'What documents are required for immigration?',
            'keywords' => ['documents', 'required', 'immigration'],
        ]]);

        // "documents required immigration" overlaps well enough with the keyword set
        $result = $matcher->match('which documents do I need for immigration', null);

        $this->assertNotNull($result);
    }

    public function test_no_match_returns_null(): void
    {
        $matcher = $this->matcher([[
            'question' => 'What is the price?',
            'keywords' => ['price', 'cost'],
        ]]);

        $result = $matcher->match('hello, I need to book a ticket', null);

        $this->assertNull($result);
    }

    public function test_empty_input_returns_null(): void
    {
        $matcher = $this->matcher([[
            'question' => 'What is the price?',
            'keywords' => ['price'],
        ]]);

        $this->assertNull($matcher->match('', null));
        $this->assertNull($matcher->match('   ', null));
    }

    public function test_case_insensitive_keyword_match(): void
    {
        $matcher = $this->matcher([[
            'question' => 'Opening hours?',
            'keywords' => ['opening hours', 'schedule'],
        ]]);

        $result = $matcher->match('WHAT ARE YOUR OPENING HOURS?', null);

        $this->assertNotNull($result);
    }
}
