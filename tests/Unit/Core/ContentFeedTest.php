<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Core;

use ContentPulse\Core\DTO\ContentFeed;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ContentFeedTest extends TestCase
{
    #[Test]
    public function it_creates_from_paginated_api_response(): void
    {
        $response = [
            'data' => [
                ['id' => 1, 'slug' => 'first', 'title' => 'First', 'body' => []],
                ['id' => 2, 'slug' => 'second', 'title' => 'Second', 'body' => []],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 3,
                'per_page' => 2,
                'total' => 5,
            ],
        ];

        $feed = ContentFeed::fromApiResponse($response);

        $this->assertCount(2, $feed->items);
        $this->assertSame(1, $feed->getCurrentPage());
        $this->assertSame(3, $feed->getLastPage());
        $this->assertSame(5, $feed->getTotal());
        $this->assertTrue($feed->hasMorePages());
    }

    #[Test]
    public function it_detects_last_page(): void
    {
        $response = [
            'data' => [
                ['id' => 1, 'slug' => 'only', 'title' => 'Only', 'body' => []],
            ],
            'meta' => [
                'current_page' => 1,
                'last_page' => 1,
                'total' => 1,
            ],
        ];

        $feed = ContentFeed::fromApiResponse($response);

        $this->assertFalse($feed->hasMorePages());
    }

    #[Test]
    public function it_handles_flat_pagination_keys(): void
    {
        $response = [
            'data' => [],
            'current_page' => 2,
            'last_page' => 5,
            'per_page' => 10,
            'total' => 50,
        ];

        $feed = ContentFeed::fromApiResponse($response);

        $this->assertSame(2, $feed->getCurrentPage());
        $this->assertSame(5, $feed->getLastPage());
    }
}
