<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

/**
 * Normalized representation of a single content section.
 *
 * The generation pipeline may produce sections in different shapes:
 *   - { type, content, attributes }
 *   - { type, data }
 *   - { type, data: { content, ...attributes } }
 *
 * SectionNormalizer reduces all shapes to this canonical form.
 */
final class Section
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public readonly string $type,
        public readonly string|array $content,
        public readonly array $attributes = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'] ?? 'paragraph',
            content: $data['content'] ?? '',
            attributes: $data['attributes'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'content' => $this->content,
            'attributes' => $this->attributes,
        ];
    }
}
