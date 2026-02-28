<?php

declare(strict_types=1);

namespace ContentPulse\Core\Contracts;

use ContentPulse\Core\DTO\ContentItem;
use ContentPulse\Core\DTO\PublishResult;

interface PublisherInterface
{
    /**
     * Publish (create or update) a content item on the target platform.
     *
     * @param  array<string, mixed>  $options  Platform-specific options
     *
     * @throws \ContentPulse\Core\Exceptions\PublishException
     */
    public function publish(ContentItem $content, array $options = []): PublishResult;

    /**
     * Delete a previously published content item from the target platform.
     *
     * @throws \ContentPulse\Core\Exceptions\PublishException
     */
    public function unpublish(string $externalId): bool;

    /**
     * Return the platform identifier (e.g. "wordpress", "shopify").
     */
    public function getPlatform(): string;
}
