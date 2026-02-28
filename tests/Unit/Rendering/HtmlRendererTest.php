<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Rendering;

use ContentPulse\Core\DTO\Section;
use ContentPulse\Rendering\HtmlRenderer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class HtmlRendererTest extends TestCase
{
    private HtmlRenderer $renderer;

    protected function setUp(): void
    {
        $this->renderer = new HtmlRenderer;
    }

    #[Test]
    public function it_renders_headings_with_anchors(): void
    {
        $section = new Section('heading', 'Hello World', ['level' => 2]);

        $html = $this->renderer->render($section);

        $this->assertStringContainsString('<h2 id="hello-world">Hello World</h2>', $html);
    }

    #[Test]
    public function it_renders_h3_shorthand(): void
    {
        $section = new Section('h3', 'Sub Heading');

        $html = $this->renderer->render($section);

        $this->assertStringContainsString('<h3 id="sub-heading">Sub Heading</h3>', $html);
    }

    #[Test]
    public function it_renders_paragraphs(): void
    {
        $section = new Section('paragraph', 'Some text content.');

        $html = $this->renderer->render($section);

        $this->assertStringContainsString('<p>Some text content.</p>', $html);
    }

    #[Test]
    public function it_renders_unordered_lists(): void
    {
        $section = new Section('list', ['Item 1', 'Item 2', 'Item 3']);

        $html = $this->renderer->render($section);

        $this->assertStringContainsString('<ul>', $html);
        $this->assertStringContainsString('<li>Item 1</li>', $html);
        $this->assertStringContainsString('<li>Item 2</li>', $html);
    }

    #[Test]
    public function it_renders_ordered_lists(): void
    {
        $section = new Section('list', ['Step A', 'Step B'], ['ordered' => true]);

        $html = $this->renderer->render($section);

        $this->assertStringContainsString('<ol>', $html);
        $this->assertStringContainsString('<li>Step A</li>', $html);
    }

    #[Test]
    public function it_renders_faq_with_schema(): void
    {
        $section = new Section('faq', [
            ['question' => 'What?', 'answer' => 'This.'],
        ]);

        $html = $this->renderer->render($section);

        $this->assertStringContainsString('<h3>What?</h3>', $html);
        $this->assertStringContainsString('<p>This.</p>', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('FAQPage', $html);
    }

    #[Test]
    public function it_renders_images(): void
    {
        $section = new Section('image', '', ['url' => 'https://example.com/img.jpg', 'alt' => 'Photo']);

        $html = $this->renderer->render($section);

        $this->assertStringContainsString('<img src="https://example.com/img.jpg" alt="Photo">', $html);
    }

    #[Test]
    public function it_renders_callout(): void
    {
        $section = new Section('callout', 'Important notice.', ['type' => 'warning']);

        $html = $this->renderer->render($section);

        $this->assertStringContainsString('callout-warning', $html);
        $this->assertStringContainsString('Important notice.', $html);
    }

    #[Test]
    public function it_extracts_toc_from_sections(): void
    {
        $sections = [
            new Section('heading', 'Introduction', ['level' => 2]),
            new Section('paragraph', 'Some text.'),
            new Section('h3', 'Details'),
            new Section('heading', 'Conclusion', ['level' => 2]),
        ];

        $toc = $this->renderer->extractToc($sections);

        $this->assertCount(3, $toc);
        $this->assertSame(2, $toc[0]['level']);
        $this->assertSame('Introduction', $toc[0]['text']);
        $this->assertSame('introduction', $toc[0]['anchor']);
        $this->assertSame(3, $toc[1]['level']);
    }

    #[Test]
    public function it_renders_all_sections(): void
    {
        $sections = [
            new Section('heading', 'Title', ['level' => 2]),
            new Section('paragraph', 'Body text.'),
        ];

        $html = $this->renderer->renderAll($sections);

        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('<p>Body text.</p>', $html);
    }

    #[Test]
    public function it_reports_supported_types(): void
    {
        $this->assertTrue($this->renderer->supports('heading'));
        $this->assertTrue($this->renderer->supports('faq'));
        $this->assertFalse($this->renderer->supports('unknown_custom_type'));
    }
}
