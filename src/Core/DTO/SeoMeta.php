<?php

declare(strict_types=1);

namespace ContentPulse\Core\DTO;

final class SeoMeta
{
    public function __construct(
        public readonly ?string $metaTitle = null,
        public readonly ?string $metaDescription = null,
        public readonly ?array $metaKeywords = null,
        public readonly ?string $ogTitle = null,
        public readonly ?string $ogDescription = null,
        public readonly ?string $twitterTitle = null,
        public readonly ?string $twitterDescription = null,
        public readonly ?string $metaRobots = null,
        public readonly ?string $canonicalUrl = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            metaKeywords: $data['meta_keywords'] ?? null,
            ogTitle: $data['og_title'] ?? null,
            ogDescription: $data['og_description'] ?? null,
            twitterTitle: $data['twitter_title'] ?? null,
            twitterDescription: $data['twitter_description'] ?? null,
            metaRobots: $data['meta_robots'] ?? null,
            canonicalUrl: $data['canonical_url'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
            'meta_keywords' => $this->metaKeywords,
            'og_title' => $this->ogTitle,
            'og_description' => $this->ogDescription,
            'twitter_title' => $this->twitterTitle,
            'twitter_description' => $this->twitterDescription,
            'meta_robots' => $this->metaRobots,
            'canonical_url' => $this->canonicalUrl,
        ], fn ($v) => $v !== null);
    }

    /**
     * Render meta tags as HTML string for <head>.
     */
    public function toHtml(string $siteUrl = ''): string
    {
        $tags = [];

        if ($this->metaTitle) {
            $tags[] = '<title>'.htmlspecialchars($this->metaTitle, ENT_QUOTES, 'UTF-8').'</title>';
        }
        if ($this->metaDescription) {
            $tags[] = '<meta name="description" content="'.htmlspecialchars($this->metaDescription, ENT_QUOTES, 'UTF-8').'">';
        }
        if ($this->metaKeywords) {
            $tags[] = '<meta name="keywords" content="'.htmlspecialchars(implode(', ', $this->metaKeywords), ENT_QUOTES, 'UTF-8').'">';
        }
        if ($this->metaRobots) {
            $tags[] = '<meta name="robots" content="'.htmlspecialchars($this->metaRobots, ENT_QUOTES, 'UTF-8').'">';
        }
        if ($this->ogTitle) {
            $tags[] = '<meta property="og:title" content="'.htmlspecialchars($this->ogTitle, ENT_QUOTES, 'UTF-8').'">';
        }
        if ($this->ogDescription) {
            $tags[] = '<meta property="og:description" content="'.htmlspecialchars($this->ogDescription, ENT_QUOTES, 'UTF-8').'">';
        }
        if ($this->twitterTitle) {
            $tags[] = '<meta name="twitter:title" content="'.htmlspecialchars($this->twitterTitle, ENT_QUOTES, 'UTF-8').'">';
        }
        if ($this->twitterDescription) {
            $tags[] = '<meta name="twitter:description" content="'.htmlspecialchars($this->twitterDescription, ENT_QUOTES, 'UTF-8').'">';
        }
        if ($this->canonicalUrl) {
            $tags[] = '<link rel="canonical" href="'.htmlspecialchars($this->canonicalUrl, ENT_QUOTES, 'UTF-8').'">';
        }

        return implode("\n", $tags);
    }
}
