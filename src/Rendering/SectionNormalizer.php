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
 *   Shape D: pipeline { "type": "content_seo"|"content_backlink"|..., "data": { "title", "paragraphs", ... } }
 */
class SectionNormalizer
{
    /**
     * Section types that use title + paragraphs pipeline payloads.
     *
     * @var list<string>
     */
    private const STRUCTURED_PARAGRAPH_TYPES = [
        'content',
        'content_seo',
        'content_backlink',
        'conclusion',
        'hero',
    ];

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
        if ($type === null || ! is_string($type) || $type === '') {
            return null;
        }

        // Meta sections that should not be rendered as body content.
        if (in_array($type, ['titles', 'seo_keywords', 'citation_sources'], true)) {
            return null;
        }

        // Shape A: already canonical
        if (array_key_exists('content', $raw) && ! array_key_exists('data', $raw)) {
            return new Section(
                type: $type,
                content: $raw['content'] ?? '',
                attributes: is_array($raw['attributes'] ?? null) ? $raw['attributes'] : [],
            );
        }

        // Shape B / C / D: "data" key
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

            // Shape D: pipeline structured paragraph sections
            if (is_array($data) && in_array($type, self::STRUCTURED_PARAGRAPH_TYPES, true)) {
                return $this->normalizeStructuredParagraphSection($type, $data);
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

    /**
     * @param  array<string, mixed>  $data
     */
    private function normalizeStructuredParagraphSection(string $type, array $data): Section
    {
        $title = trim((string) ($data['title'] ?? ''));
        $paragraphs = [];

        if (is_array($data['paragraphs'] ?? null)) {
            foreach ($data['paragraphs'] as $paragraph) {
                $text = trim((string) $paragraph);
                if ($text !== '') {
                    $paragraphs[] = $text;
                }
            }
        } elseif (is_string($data['description'] ?? null) && trim($data['description']) !== '') {
            $paragraphs[] = trim($data['description']);
        }

        // Hero never duplicates the page title in body.
        if ($type === 'hero') {
            $title = '';
        }

        $content = implode("\n\n", $paragraphs);
        if ($title !== '') {
            $content = $title.($content !== '' ? "\n\n".$content : '');
        }

        $attributes = [];
        if (is_array($data['internal_seo'] ?? null)) {
            $attributes['internal_seo'] = $data['internal_seo'];
        }
        if (is_array($data['backlink_exchange'] ?? null)) {
            $attributes['backlink_exchange'] = $data['backlink_exchange'];
        }
        if (is_array($data['strong_keywords'] ?? null)) {
            $attributes['strong_keywords'] = $data['strong_keywords'];
        }

        // Emit as paragraph so HtmlRenderer produces <p> blocks; title is
        // included as plain leading text when present (callers that need an
        // <h2> should pre-process via ContentPulse rendered_html).
        return new Section(
            type: 'content',
            content: $content,
            attributes: $attributes,
        );
    }
}
