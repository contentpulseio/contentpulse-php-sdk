<?php

declare(strict_types=1);

namespace ContentPulse\Rendering;

use ContentPulse\Core\DTO\Section;

/**
 * Normalizes varying content body JSON shapes into canonical Section DTOs.
 *
 * Handles these known input shapes from the ContentPulse generation pipeline:
 *   Shape A: { "type": "heading", "content": "...", "attributes": {...} }
 *   Shape B: { "type": "heading", "data": "..." }
 *   Shape C: { "type": "heading", "data": { "content": "...", "level": 3 } }
 */
class SectionNormalizer
{
    /**
     * Normalize an array of raw section data into Section DTOs.
     *
     * @param  array<int, array<string, mixed>>  $rawSections
     * @return Section[]
     */
    public function normalize(array $rawSections): array
    {
        return array_values(array_filter(
            array_map(fn (array $raw) => $this->normalizeOne($raw), $rawSections),
        ));
    }

    /**
     * Normalize a single raw section entry.
     *
     * @param  array<string, mixed>  $raw
     */
    public function normalizeOne(array $raw): ?Section
    {
        $type = $raw['type'] ?? null;
        if ($type === null) {
            return null;
        }

        // Shape A: already canonical
        if (array_key_exists('content', $raw)) {
            return new Section(
                type: $type,
                content: $raw['content'] ?? '',
                attributes: $raw['attributes'] ?? [],
            );
        }

        // Shape B or C: "data" key
        if (array_key_exists('data', $raw)) {
            $data = $raw['data'];

            // Shape C: data is an associative array with "content" inside
            if (is_array($data) && array_key_exists('content', $data)) {
                $content = $data['content'];
                $attributes = array_diff_key($data, ['content' => true]);

                return new Section(
                    type: $type,
                    content: $content,
                    attributes: $attributes,
                );
            }

            // Shape B: data is the content itself (string or array)
            return new Section(
                type: $type,
                content: $data,
                attributes: [],
            );
        }

        // Fallback: extract content from whatever keys remain
        $reserved = ['type'];
        $otherKeys = array_diff_key($raw, array_flip($reserved));

        return new Section(
            type: $type,
            content: '',
            attributes: $otherKeys,
        );
    }
}
