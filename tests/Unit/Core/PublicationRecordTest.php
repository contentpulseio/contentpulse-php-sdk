<?php

declare(strict_types=1);

namespace ContentPulse\Tests\Unit\Core;

use ContentPulse\Core\DTO\PublicationRecord;
use ContentPulse\Core\DTO\PublishResult;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class PublicationRecordTest extends TestCase
{
    #[Test]
    public function it_creates_from_successful_publish_result(): void
    {
        $result = PublishResult::success('wordpress', '42', 'https://example.com/post/42');
        $record = PublicationRecord::fromPublishResult(10, $result);

        $this->assertSame(10, $record->contentId);
        $this->assertSame('wordpress', $record->platform);
        $this->assertSame('42', $record->remoteId);
        $this->assertSame('https://example.com/post/42', $record->remoteUrl);
        $this->assertSame('synced', $record->status);
        $this->assertNotNull($record->lastSyncedAt);
        $this->assertNull($record->errorMessage);
    }

    #[Test]
    public function it_creates_from_failed_publish_result(): void
    {
        $result = PublishResult::failure('shopify', 'API rate limit exceeded');
        $record = PublicationRecord::fromPublishResult(20, $result);

        $this->assertSame(20, $record->contentId);
        $this->assertSame('shopify', $record->platform);
        $this->assertSame('failed', $record->status);
        $this->assertSame('API rate limit exceeded', $record->errorMessage);
    }

    #[Test]
    public function it_converts_to_array_matching_db_schema(): void
    {
        $record = new PublicationRecord(
            contentId: 5,
            platform: 'wordpress',
            remoteId: '100',
            remoteUrl: 'https://example.com/post/100',
            status: 'synced',
        );

        $array = $record->toArray();

        $this->assertArrayHasKey('content_id', $array);
        $this->assertArrayHasKey('platform', $array);
        $this->assertArrayHasKey('remote_id', $array);
        $this->assertArrayHasKey('remote_url', $array);
        $this->assertArrayHasKey('status', $array);
        $this->assertSame(5, $array['content_id']);
    }

    #[Test]
    public function report_payload_filters_null_values(): void
    {
        $record = new PublicationRecord(
            contentId: 1,
            platform: 'wordpress',
            status: 'pending',
        );

        $payload = $record->toReportPayload();

        $this->assertArrayNotHasKey('remote_id', $payload);
        $this->assertArrayNotHasKey('remote_url', $payload);
        $this->assertArrayNotHasKey('error_message', $payload);
        $this->assertArrayHasKey('content_id', $payload);
        $this->assertArrayHasKey('platform', $payload);
    }
}
