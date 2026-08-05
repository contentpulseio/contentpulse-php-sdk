<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Media;

use ContentPulse\Media\ImageReferenceRewriter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ImageReferenceRewriterTest extends TestCase
{
    #[Test]
    public function it_rewrites_chart_images_and_preserves_matching_local_urls(): void
    {
        $rewriter = new ImageReferenceRewriter;
        $sections = [[
            'type' => 'chart',
            'data' => [
                'stat_group_id' => 'ai-adoption',
                'image_path' => 'https://contentpulse.io/storage/content/42/charts/ai.png?v=2',
                'image_url' => 'https://contentpulse.io/storage/content/42/charts/ai.png?v=2',
                'image_variants' => ['small' => ['url' => 'https://contentpulse.io/storage/content/42/charts/ai-small.png?v=2']],
            ],
        ]];
        $existing = [[
            'type' => 'chart',
            'data' => [
                'stat_group_id' => 'ai-adoption',
                'image_path' => 'media/blog/stable-chart.png',
                'image_url' => 'media/blog/stable-chart.png',
                'image_variants' => ['small' => ['url' => 'media/blog/stable-chart-small.png']],
            ],
        ]];
        $seen = [];

        $rewritten = $rewriter->rewriteChartSections($sections, function (string $upstream, ?string $current) use (&$seen): string {
            $seen[] = [$upstream, $current];

            return $current ?? 'media/blog/'.sha1($upstream).'.png';
        }, $existing);

        $this->assertSame('media/blog/stable-chart.png', $rewritten[0]['data']['image_url']);
        $this->assertSame('media/blog/stable-chart.png', $rewritten[0]['data']['image_path']);
        $this->assertSame('media/blog/stable-chart-small.png', $rewritten[0]['data']['image_variants']['small']['url']);
        $this->assertCount(3, $seen);
    }

    #[Test]
    public function it_rewrites_only_image_sources_in_html(): void
    {
        $rewriter = new ImageReferenceRewriter;
        $html = '<p><a href="https://contentpulse.io/resources/x">Read</a><img src="/storage/content/42/charts/a.png" alt="A"><source data-src="https://contentpulse.io/storage/content/42/charts/b.png"></p>';

        $rewritten = $rewriter->rewriteHtml($html, static fn (string $url): string => '/storage/media/blog/'.sha1($url).'.png');

        $this->assertStringContainsString('href="https://contentpulse.io/resources/x"', $rewritten);
        $this->assertStringContainsString('src="/storage/media/blog/'.sha1('/storage/content/42/charts/a.png').'.png"', $rewritten);
        $this->assertStringContainsString('data-src="/storage/media/blog/'.sha1('https://contentpulse.io/storage/content/42/charts/b.png').'.png"', $rewritten);
    }

    #[Test]
    public function it_extracts_chart_and_html_image_urls(): void
    {
        $rewriter = new ImageReferenceRewriter;
        $sections = [[
            'type' => 'chart',
            'data' => [
                'image_url' => '/storage/content/42/charts/a.png',
                'image_variants' => ['small' => ['url' => '/storage/content/42/charts/a-small.png']],
            ],
        ]];

        $this->assertSame([
            '/storage/content/42/charts/a.png',
            '/storage/content/42/charts/a-small.png',
        ], $rewriter->chartImageUrls($sections));
        $this->assertSame(['/storage/content/42/charts/a.png'], $rewriter->htmlImageUrls('<img src="/storage/content/42/charts/a.png">'));
    }
}
