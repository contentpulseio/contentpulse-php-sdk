<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

final class ContentFilters
{
    public function __construct(
        public readonly ?int $page = null,
        public readonly ?int $perPage = null,
        public readonly ?string $status = null,
        public readonly ?string $contentType = null,
        public readonly ?string $locale = null,
        public readonly ?int $websiteId = null,
        public readonly ?string $search = null,
        public readonly ?string $sortBy = null,
        public readonly ?string $sortDirection = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toQueryParams(): array
    {
        return array_filter([
            'page' => $this->page,
            'per_page' => $this->perPage,
            'status' => $this->status,
            'content_type' => $this->contentType,
            'locale' => $this->locale,
            'website_id' => $this->websiteId,
            'search' => $this->search,
            'sort_by' => $this->sortBy,
            'sort_direction' => $this->sortDirection,
        ], fn ($v) => $v !== null);
    }
}
