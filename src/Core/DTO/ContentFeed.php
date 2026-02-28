<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

final class ContentFeed
{
    /**
     * @param  ContentItem[]  $items
     * @param  array<string, mixed>  $meta  Pagination metadata (current_page, last_page, per_page, total, etc.)
     */
    public function __construct(
        public readonly array $items,
        public readonly array $meta = [],
    ) {}

    /**
     * Build from a paginated API response.
     *
     * @param  array<string, mixed>  $response
     */
    public static function fromApiResponse(array $response): self
    {
        $data = $response['data'] ?? [];
        $items = array_map(
            fn (array $item) => ContentItem::fromApiResponse($item),
            $data,
        );

        $meta = $response['meta'] ?? [];
        if (empty($meta)) {
            $meta = array_filter([
                'current_page' => $response['current_page'] ?? null,
                'last_page' => $response['last_page'] ?? null,
                'per_page' => $response['per_page'] ?? null,
                'total' => $response['total'] ?? null,
            ], fn ($v) => $v !== null);
        }

        return new self(items: $items, meta: $meta);
    }

    public function getCurrentPage(): int
    {
        return (int) ($this->meta['current_page'] ?? 1);
    }

    public function getLastPage(): int
    {
        return (int) ($this->meta['last_page'] ?? 1);
    }

    public function getTotal(): int
    {
        return (int) ($this->meta['total'] ?? count($this->items));
    }

    public function hasMorePages(): bool
    {
        return $this->getCurrentPage() < $this->getLastPage();
    }
}
