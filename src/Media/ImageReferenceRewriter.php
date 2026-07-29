<?php

declare(strict_types=1);

namespace ContentPulse\Media;

/**
 * Rewrites image references embedded in ContentPulse article payloads.
 *
 * The class deliberately does not download files itself. Consumers supply the
 * localizer callback so framework-specific storage (Laravel disks, S3, or a
 * plain PHP directory) remains the owner of the downloaded asset.
 */
final class ImageReferenceRewriter
{
    /**
     * Rewrite chart image URLs in structured ContentPulse body sections.
     *
     * @param  array<int, mixed>  $sections
     * @param  callable(string, ?string): ?string  $localize
     * @param  array<int, mixed>  $existingSections
     * @return array<int, mixed>
     */
    public function rewriteChartSections(array $sections, callable $localize, array $existingSections = []): array
    {
        foreach ($sections as $index => $section) {
            if (! is_array($section) || ($section['type'] ?? null) !== 'chart') {
                continue;
            }

            $data = is_array($section['data'] ?? null) ? $section['data'] : [];
            $existing = $this->matchingChartData($section, $existingSections, $index);

            if (isset($data['image_url']) && is_string($data['image_url']) && $data['image_url'] !== '') {
                $data['image_url'] = $localize($data['image_url'], $existing['image_url'] ?? null);
            }

            if (isset($data['image_variants']) && is_array($data['image_variants'])) {
                $existingVariants = is_array($existing['image_variants'] ?? null) ? $existing['image_variants'] : [];
                $data['image_variants'] = $this->rewriteImageMap($data['image_variants'], $localize, $existingVariants);
            }

            $sections[$index]['data'] = $data;
        }

        return $sections;
    }

    /**
     * Rewrite <img> and lazy-image source attributes in server-rendered HTML.
     *
     * @param  callable(string, ?string): ?string  $localize
     */
    public function rewriteHtml(string $html, callable $localize): string
    {
        return (string) preg_replace_callback(
            '/<(?:img|source)\b[^>]*>/i',
            static function (array $tag) use ($localize): string {
                return (string) preg_replace_callback(
                    '/\b(src|data-src)\s*=\s*([\"\'])(.*?)\2/is',
                    static function (array $attribute) use ($localize): string {
                        $url = trim($attribute[3]);
                        if ($url === '' || str_starts_with($url, 'data:')) {
                            return $attribute[0];
                        }

                        $rewritten = $localize($url, null);

                        return is_string($rewritten) && $rewritten !== ''
                            ? $attribute[1].'='.$attribute[2].$rewritten.$attribute[2]
                            : $attribute[0];
                    },
                    $tag[0],
                );
            },
            $html,
        );
    }

    /**
     * @return list<string>
     */
    public function chartImageUrls(array $sections): array
    {
        $urls = [];

        foreach ($sections as $section) {
            if (! is_array($section) || ($section['type'] ?? null) !== 'chart') {
                continue;
            }

            $data = is_array($section['data'] ?? null) ? $section['data'] : [];
            if (is_string($data['image_url'] ?? null) && $data['image_url'] !== '') {
                $urls[] = $data['image_url'];
            }

            foreach ($this->imageMapUrls(is_array($data['image_variants'] ?? null) ? $data['image_variants'] : []) as $url) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    /**
     * @return list<string>
     */
    public function htmlImageUrls(string $html): array
    {
        preg_match_all('/<(?:img|source)\b[^>]*\b(?:src|data-src)\s*=\s*([\"\'])(.*?)\1/is', $html, $matches);

        return array_values(array_unique(array_filter(
            array_map(static fn (string $url): string => trim($url), $matches[2] ?? []),
            static fn (string $url): bool => $url !== '' && ! str_starts_with($url, 'data:'),
        )));
    }

    /**
     * @param  array<string, mixed>  $images
     * @param  callable(string, ?string): ?string  $localize
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function rewriteImageMap(array $images, callable $localize, array $existing): array
    {
        foreach ($images as $key => $variant) {
            $previous = $existing[$key] ?? null;
            $existingUrl = $this->variantUrl($previous);

            if (is_string($variant) && $variant !== '') {
                $images[$key] = $localize($variant, $existingUrl);
            } elseif (is_array($variant) && isset($variant['url']) && is_string($variant['url']) && $variant['url'] !== '') {
                $variant['url'] = $localize($variant['url'], $existingUrl);
                $images[$key] = $variant;
            }
        }

        return $images;
    }

    /**
     * @param  array<int, mixed>  $sections
     * @return array<string, mixed>
     */
    private function matchingChartData(array $section, array $sections, int $index): array
    {
        $groupId = trim((string) ($section['data']['stat_group_id'] ?? ''));

        foreach ($sections as $existing) {
            if (! is_array($existing) || ($existing['type'] ?? null) !== 'chart') {
                continue;
            }

            $data = is_array($existing['data'] ?? null) ? $existing['data'] : [];
            if ($groupId !== '' && $groupId === trim((string) ($data['stat_group_id'] ?? ''))) {
                return $data;
            }
        }

        $fallback = $sections[$index]['data'] ?? [];

        return is_array($fallback) ? $fallback : [];
    }

    private function variantUrl(mixed $variant): ?string
    {
        if (is_string($variant) && $variant !== '') {
            return $variant;
        }

        return is_array($variant) && is_string($variant['url'] ?? null) && $variant['url'] !== ''
            ? $variant['url']
            : null;
    }

    /**
     * @param  array<string, mixed>  $images
     * @return list<string>
     */
    private function imageMapUrls(array $images): array
    {
        $urls = [];
        foreach ($images as $variant) {
            $url = $this->variantUrl($variant);
            if ($url !== null) {
                $urls[] = $url;
            }
        }

        return $urls;
    }
}
