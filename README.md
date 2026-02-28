# ContentPulse PHP SDK

[![CI](https://github.com/contentpulseio/contentpulse-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/contentpulseio/contentpulse-php-sdk/actions/workflows/ci.yml)

Official PHP SDK for [ContentPulse.io](https://contentpulse.io) — content rendering, API client, and publishing utilities.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require contentpulse/contentpulse-php
```

## Quick Start

### Create a client

```php
use ContentPulse\Http\ContentPulseClient;

$client = new ContentPulseClient(
    apiUrl: 'https://api.contentpulse.io/v1',
    apiKey: 'your-api-key'
);
```

### Fetch content feed

```php
$feed = $client->getContentFeed(websiteId: 1, limit: 20);
foreach ($feed->items as $item) {
    // $item contains content metadata and structure
}
```

### Render content to HTML

```php
use ContentPulse\Rendering\HtmlRenderer;

$renderer = new HtmlRenderer();
$html = $renderer->render($content->structure);
```

### Section normalizer

```php
use ContentPulse\Rendering\SectionNormalizer;

$normalizer = new SectionNormalizer();
$normalized = $normalizer->normalize($rawSections);
```

### SEO meta tags

```php
use ContentPulse\Core\DTO\SeoMeta;

$seo = SeoMeta::fromArray($content->seo_metadata);
$html = $seo->toHtml(); // Renders <title>, meta description, og:*, twitter:*
```

### Publish payloads

Build platform-specific payloads for WordPress or Shopify:

```php
use ContentPulse\Publishing\PublishPayloadBuilder;

$builder = new PublishPayloadBuilder();
$payload = $builder->forWordPress($content);
// or
$payload = $builder->forShopify($content);
```

## Architecture

| Module | Purpose |
|--------|---------|
| `Core/Contracts/` | Interfaces for API client, renderers, and publishers |
| `Core/DTO/` | Data transfer objects (CompatibilityInfo, PublicationRecord, SeoMeta, PublishResult) |
| `Core/Exceptions/` | Exception hierarchy for API and validation errors |
| `Http/` | REST API client for ContentPulse endpoints |
| `Rendering/` | HtmlRenderer, SectionNormalizer, SEO meta renderer |
| `Publishing/` | Publish payload builder for WordPress, Shopify, and custom platforms |

## Supported Section Types

The HtmlRenderer supports all section types produced by the ContentPulse content generation pipeline:

**Fixed sections:** titles, hero, cta, seo_keywords, references

**Content sections:** content, content_seo, conclusion

**Component sections:** steps, table, grid, checklist, faq, dos_donts, alert, quote, pros_cons, summary_box, definition, key_stats, tip_box, comparison_card, comparison_table, timeline, accordion, testimonial, code_snippet

## Testing

```bash
composer test
```

Or directly:

```bash
./vendor/bin/phpunit
```

## License

MIT
