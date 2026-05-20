# ContentPulse PHP SDK

[![CI](https://github.com/contentpulseio/contentpulse-php-sdk/actions/workflows/ci.yml/badge.svg)](https://github.com/contentpulseio/contentpulse-php-sdk/actions/workflows/ci.yml)

Official PHP SDK for [ContentPulse.io](https://contentpulse.io) - content rendering, API client, and publishing utilities.

## Requirements

- PHP 8.2+

## Installation

```bash
composer require contentpulse/contentpulse-php
```

## Quick Start

### Create a client

The only required argument is your API key. When `baseUrl` is omitted, the SDK
resolves it (in order) from:

1. The `CONTENTPULSE_BASE_URL` PHP constant (if defined)
2. The `CONTENTPULSE_BASE_URL` environment variable
3. The default `https://contentpulse.io`

```php
use ContentPulse\Http\ContentPulseClient;

// Minimal: uses CONTENTPULSE_BASE_URL (env/constant) or defaults to https://contentpulse.io
$client = new ContentPulseClient(apiKey: 'your-api-key');

// Or override explicitly:
$client = new ContentPulseClient(
    apiKey: 'your-api-key',
    baseUrl: 'https://contentpulse.io',
);
```

The SDK performs a **single** HTTP request per call - it does not loop,
retry, or `usleep`. Callers are expected to apply their own retry policy
when needed.

### Resolve WordPress endpoints (SDK-managed)

```php
use ContentPulse\WordPress\Support\ContentPulseEndpointResolver;

$apiBaseUrl = ContentPulseEndpointResolver::resolveApiBaseUrlFromEnvironment();
$appBaseUrl = ContentPulseEndpointResolver::resolveAppBaseUrlFromEnvironment();
$publishEndpoint = ContentPulseEndpointResolver::buildPublishWordPressEndpoint($apiBaseUrl, $contentId);
$contentUrl = ContentPulseEndpointResolver::buildContentUrl($appBaseUrl, $contentId);
```

When no overrides are provided, the resolver auto-selects sensible defaults:
- Local/dev: `http://contentpulse.test:8080` and `http://app.contentpulse.test:5173` (when `contentpulse.test` is resolvable)
- Otherwise: `https://contentpulse.io` and `https://app.contentpulse.io`

Overrides (highest to lowest precedence): explicit argument → PHP constant (`CONTENTPULSE_API_URL`, `CONTENTPULSE_APP_URL`) → environment variable.

### Fetch content feed

```php
use ContentPulse\Core\DTO\ContentFilters;

$feed = $client->getContentFeed(new ContentFilters(
    websiteId: 1,
    perPage: 20,
));
foreach ($feed->items as $item) {
    // $item contains content metadata and structure
}
```

### Render content to HTML

```php
use ContentPulse\Rendering\HtmlRenderer;

$renderer = new HtmlRenderer();
$html = $renderer->renderAll($content->sections);
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
use ContentPulse\Rendering\HtmlRenderer;

$builder = new PublishPayloadBuilder(new HtmlRenderer());
$payload = $builder->buildForWordPress($content);
// or
$payload = $builder->buildForShopify($content);
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
