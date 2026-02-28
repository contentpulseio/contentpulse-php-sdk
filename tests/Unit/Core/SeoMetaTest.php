<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Core;

use ContentPulse\Core\DTO\SeoMeta;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SeoMetaTest extends TestCase
{
    #[Test]
    public function it_creates_from_array(): void
    {
        $data = [
            'meta_title' => 'Test Title',
            'meta_description' => 'Test Description',
            'meta_keywords' => ['php', 'sdk'],
            'og_title' => 'OG Title',
        ];

        $seo = SeoMeta::fromArray($data);

        $this->assertSame('Test Title', $seo->metaTitle);
        $this->assertSame('Test Description', $seo->metaDescription);
        $this->assertSame(['php', 'sdk'], $seo->metaKeywords);
        $this->assertSame('OG Title', $seo->ogTitle);
        $this->assertNull($seo->twitterTitle);
    }

    #[Test]
    public function it_renders_html_meta_tags(): void
    {
        $seo = new SeoMeta(
            metaTitle: 'Page Title',
            metaDescription: 'Page description here.',
            ogTitle: 'OG Page Title',
        );

        $html = $seo->toHtml();

        $this->assertStringContainsString('<title>Page Title</title>', $html);
        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('property="og:title"', $html);
    }

    #[Test]
    public function it_filters_nulls_in_to_array(): void
    {
        $seo = new SeoMeta(metaTitle: 'Only Title');

        $array = $seo->toArray();

        $this->assertArrayHasKey('meta_title', $array);
        $this->assertArrayNotHasKey('meta_description', $array);
        $this->assertArrayNotHasKey('og_title', $array);
    }

    #[Test]
    public function it_escapes_html_in_meta_tags(): void
    {
        $seo = new SeoMeta(metaTitle: 'Title with "quotes" & <tags>');

        $html = $seo->toHtml();

        $this->assertStringNotContainsString('<tags>', $html);
        $this->assertStringContainsString('&amp;', $html);
    }
}
