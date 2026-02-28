<?php

declare(strict_types=1);

namespace ContentPulse\Core\Contracts;

use ContentPulse\Core\DTO\ContentFeed;
use ContentPulse\Core\DTO\ContentFilters;
use ContentPulse\Core\DTO\ContentItem;

interface ContentClientInterface
{
    /**
     * Retrieve a paginated feed of published content.
     *
     * @throws \ContentPulse\Core\Exceptions\ApiException
     * @throws \ContentPulse\Core\Exceptions\AuthenticationException
     */
    public function getContentFeed(?ContentFilters $filters = null): ContentFeed;

    /**
     * Retrieve a single content item by slug.
     *
     * @throws \ContentPulse\Core\Exceptions\ApiException
     * @throws \ContentPulse\Core\Exceptions\NotFoundException
     */
    public function getContentBySlug(string $slug): ContentItem;

    /**
     * Retrieve a single content item by ID.
     *
     * @throws \ContentPulse\Core\Exceptions\ApiException
     * @throws \ContentPulse\Core\Exceptions\NotFoundException
     */
    public function getContentById(int $id): ContentItem;
}
