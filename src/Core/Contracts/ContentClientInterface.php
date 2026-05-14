<?php

declare(strict_types=1);

namespace ContentPulse\Core\Contracts;

use ContentPulse\Core\DTO\ContentFeed;
use ContentPulse\Core\DTO\ContentFilters;
use ContentPulse\Core\DTO\ContentItem;
use ContentPulse\Core\Exceptions\ApiException;
use ContentPulse\Core\Exceptions\AuthenticationException;
use ContentPulse\Core\Exceptions\NotFoundException;

interface ContentClientInterface
{
    /**
     * Retrieve a paginated feed of published content.
     *
     * @throws ApiException
     * @throws AuthenticationException
     */
    public function getContentFeed(?ContentFilters $filters = null): ContentFeed;

    /**
     * Retrieve a single content item by slug.
     *
     * @throws ApiException
     * @throws NotFoundException
     */
    public function getContentBySlug(string $slug): ContentItem;

    /**
     * Retrieve a single content item by ID.
     *
     * @throws ApiException
     * @throws NotFoundException
     */
    public function getContentById(int $id): ContentItem;
}
