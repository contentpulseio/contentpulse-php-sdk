<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Rendering;

use ContentPulse\Core\DTO\Section;
use ContentPulse\Rendering\SectionNormalizer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SectionNormalizerTest extends TestCase
{
    private SectionNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new SectionNormalizer;
    }

    #[Test]
    public function it_normalizes_shape_a_with_content_and_attributes(): void
    {
        $raw = [
            ['type' => 'heading', 'content' => 'Hello World', 'attributes' => ['level' => 2]],
            ['type' => 'paragraph', 'content' => 'Some text here.'],
        ];

        $result = $this->normalizer->normalize($raw);

        $this->assertCount(2, $result);
        $this->assertInstanceOf(Section::class, $result[0]);
        $this->assertSame('heading', $result[0]->type);
        $this->assertSame('Hello World', $result[0]->content);
        $this->assertSame(['level' => 2], $result[0]->attributes);
        $this->assertSame('paragraph', $result[1]->type);
        $this->assertSame('Some text here.', $result[1]->content);
    }

    #[Test]
    public function it_normalizes_shape_b_with_data_as_string(): void
    {
        $raw = [
            ['type' => 'paragraph', 'data' => 'Simple text content.'],
        ];

        $result = $this->normalizer->normalize($raw);

        $this->assertCount(1, $result);
        $this->assertSame('paragraph', $result[0]->type);
        $this->assertSame('Simple text content.', $result[0]->content);
        $this->assertSame([], $result[0]->attributes);
    }

    #[Test]
    public function it_normalizes_shape_c_with_data_containing_content(): void
    {
        $raw = [
            ['type' => 'heading', 'data' => ['content' => 'Title Text', 'level' => 3]],
        ];

        $result = $this->normalizer->normalize($raw);

        $this->assertCount(1, $result);
        $this->assertSame('heading', $result[0]->type);
        $this->assertSame('Title Text', $result[0]->content);
        $this->assertSame(['level' => 3], $result[0]->attributes);
    }

    #[Test]
    public function it_skips_entries_without_type(): void
    {
        $raw = [
            ['content' => 'No type specified'],
            ['type' => 'paragraph', 'content' => 'Valid entry'],
        ];

        $result = $this->normalizer->normalize($raw);

        $this->assertCount(1, $result);
        $this->assertSame('paragraph', $result[0]->type);
    }

    #[Test]
    public function it_handles_array_content_in_faq_sections(): void
    {
        $raw = [
            [
                'type' => 'faq',
                'content' => [
                    ['question' => 'What is it?', 'answer' => 'A thing.'],
                    ['question' => 'How?', 'answer' => 'Like this.'],
                ],
            ],
        ];

        $result = $this->normalizer->normalize($raw);

        $this->assertCount(1, $result);
        $this->assertSame('faq', $result[0]->type);
        $this->assertIsArray($result[0]->content);
        $this->assertCount(2, $result[0]->content);
    }

    #[Test]
    public function it_handles_empty_input(): void
    {
        $result = $this->normalizer->normalize([]);

        $this->assertSame([], $result);
    }
}
