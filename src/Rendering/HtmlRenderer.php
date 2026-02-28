<?php

declare(strict_types=1);

namespace ContentPulse\Rendering;

use ContentPulse\Core\Contracts\SectionRendererInterface;
use ContentPulse\Core\DTO\Section;

/**
 * Renders normalized Section DTOs to clean semantic HTML.
 *
 * Mirrors the block-type coverage of the ContentPulse WordPressBlockMapper
 * but produces standard HTML suitable for any website.
 */
class HtmlRenderer implements SectionRendererInterface
{
    public function render(Section $section): string
    {
        return match ($section->type) {
            'heading', 'h2', 'h3', 'h4' => $this->renderHeading($section),
            'paragraph', 'content' => $this->renderParagraph($section),
            'hero', 'cover' => $this->renderHero($section),
            'list', 'checklist' => $this->renderList($section),
            'quote', 'alert' => $this->renderQuote($section),
            'table', 'stats' => $this->renderTable($section),
            'image' => $this->renderImage($section),
            'faq' => $this->renderFaq($section),
            'code', 'code_snippet' => $this->renderCode($section),
            'separator' => '<hr>'."\n",
            'callout', 'tip_box' => $this->renderCallout($section),
            'steps' => $this->renderSteps($section),
            'dos_donts' => $this->renderDosDonts($section),
            'pros_cons' => $this->renderProsCons($section),
            'summary_box' => $this->renderSummaryBox($section),
            'accordion' => $this->renderAccordion($section),
            'testimonial' => $this->renderTestimonial($section),
            default => $this->renderParagraph($section),
        };
    }

    public function renderAll(array $sections): string
    {
        return implode('', array_map(fn (Section $s) => $this->render($s), $sections));
    }

    public function supports(string $sectionType): bool
    {
        return in_array($sectionType, [
            'heading', 'h2', 'h3', 'h4', 'paragraph', 'content', 'hero', 'cover',
            'list', 'checklist', 'quote', 'alert', 'table', 'stats', 'image',
            'faq', 'code', 'code_snippet', 'separator', 'callout', 'tip_box',
            'steps', 'dos_donts', 'pros_cons', 'summary_box', 'accordion', 'testimonial',
        ], true);
    }

    /**
     * Extract a table-of-contents from heading sections.
     *
     * @param  Section[]  $sections
     * @return array<int, array{level: int, text: string, anchor: string}>
     */
    public function extractToc(array $sections): array
    {
        $toc = [];
        foreach ($sections as $section) {
            if (in_array($section->type, ['heading', 'h2', 'h3', 'h4'], true) && is_string($section->content)) {
                $level = match ($section->type) {
                    'h3' => 3,
                    'h4' => 4,
                    default => (int) ($section->attributes['level'] ?? 2),
                };
                $toc[] = [
                    'level' => $level,
                    'text' => strip_tags($section->content),
                    'anchor' => $this->slugify($section->content),
                ];
            }
        }

        return $toc;
    }

    private function renderHeading(Section $section): string
    {
        $level = match ($section->type) {
            'h3' => 3,
            'h4' => 4,
            default => (int) ($section->attributes['level'] ?? 2),
        };
        $text = is_string($section->content) ? $section->content : '';
        $anchor = $this->slugify($text);

        return "<h{$level} id=\"{$anchor}\">{$text}</h{$level}>\n";
    }

    private function renderParagraph(Section $section): string
    {
        $text = is_string($section->content) ? $section->content : '';
        $paragraphs = preg_split('/\n{2,}/', trim($text));
        $html = '';
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p !== '') {
                $html .= '<p>'.nl2br($p)."</p>\n";
            }
        }

        return $html;
    }

    private function renderHero(Section $section): string
    {
        $imageUrl = $section->attributes['image_url'] ?? '';
        $text = is_string($section->content) ? $section->content : '';
        $style = $imageUrl ? " style=\"background-image:url('{$imageUrl}')\"" : '';

        return "<section class=\"hero\"{$style}><div class=\"hero-content\"><p>{$text}</p></div></section>\n";
    }

    private function renderList(Section $section): string
    {
        $items = is_array($section->content) ? $section->content : array_filter(explode("\n", (string) $section->content));
        $ordered = (bool) ($section->attributes['ordered'] ?? false);
        $tag = $ordered ? 'ol' : 'ul';

        $html = "<{$tag}>\n";
        foreach ($items as $item) {
            $item = is_string($item) ? ltrim($item, '- *0123456789.') : (string) $item;
            $item = trim($item);
            if ($item !== '') {
                $html .= "  <li>{$item}</li>\n";
            }
        }
        $html .= "</{$tag}>\n";

        return $html;
    }

    private function renderQuote(Section $section): string
    {
        $text = is_string($section->content) ? $section->content : '';
        $citation = $section->attributes['citation'] ?? '';
        $cite = $citation ? "\n  <cite>{$citation}</cite>" : '';

        return "<blockquote>\n  <p>{$text}</p>{$cite}\n</blockquote>\n";
    }

    private function renderTable(Section $section): string
    {
        $content = $section->content;
        if (is_string($content)) {
            $rows = array_filter(explode("\n", $content));
            $content = array_map(fn ($row) => array_map('trim', explode('|', trim($row, '|'))), $rows);
        }

        if (! is_array($content) || empty($content)) {
            return '';
        }

        $header = array_shift($content);
        $html = "<table>\n<thead><tr>";
        foreach ($header as $cell) {
            $html .= '<th>'.trim((string) $cell).'</th>';
        }
        $html .= "</tr></thead>\n<tbody>\n";

        foreach ($content as $row) {
            if (! is_array($row)) {
                continue;
            }
            $isSeparator = ! empty(array_filter($row, fn ($c) => preg_match('/^[-:]+$/', trim((string) $c))));
            if ($isSeparator) {
                continue;
            }
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>'.trim((string) $cell).'</td>';
            }
            $html .= "</tr>\n";
        }
        $html .= "</tbody>\n</table>\n";

        return $html;
    }

    private function renderImage(Section $section): string
    {
        $url = $section->attributes['url'] ?? (is_string($section->content) ? $section->content : '');
        $alt = htmlspecialchars((string) ($section->attributes['alt'] ?? ''), ENT_QUOTES, 'UTF-8');
        $caption = $section->attributes['caption'] ?? '';
        $captionHtml = $caption ? "<figcaption>{$caption}</figcaption>" : '';

        return "<figure><img src=\"{$url}\" alt=\"{$alt}\">{$captionHtml}</figure>\n";
    }

    private function renderFaq(Section $section): string
    {
        $items = is_array($section->content) ? $section->content : [];
        $html = "<div class=\"faq\">\n";

        foreach ($items as $item) {
            $q = htmlspecialchars((string) ($item['question'] ?? ''), ENT_QUOTES, 'UTF-8');
            $a = (string) ($item['answer'] ?? '');
            $html .= "  <div class=\"faq-item\">\n    <h3>{$q}</h3>\n    <p>{$a}</p>\n  </div>\n";
        }
        $html .= "</div>\n";

        if (! empty($items) && ($section->attributes['schema'] ?? true)) {
            $html .= $this->buildFaqSchema($items);
        }

        return $html;
    }

    private function buildFaqSchema(array $items): string
    {
        $entities = array_map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item['question'] ?? '',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => strip_tags((string) ($item['answer'] ?? '')),
            ],
        ], $items);

        $schema = json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return "<script type=\"application/ld+json\">\n{$schema}\n</script>\n";
    }

    private function renderCode(Section $section): string
    {
        $text = is_string($section->content) ? $section->content : '';
        $lang = $section->attributes['language'] ?? '';
        $langAttr = $lang ? " class=\"language-{$lang}\"" : '';

        return '<pre><code'.$langAttr.'>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8')."</code></pre>\n";
    }

    private function renderCallout(Section $section): string
    {
        $type = $section->attributes['type'] ?? 'info';
        $text = is_string($section->content) ? $section->content : '';

        return "<div class=\"callout callout-{$type}\">\n  <p>{$text}</p>\n</div>\n";
    }

    private function renderSteps(Section $section): string
    {
        $items = is_array($section->content) ? $section->content : [];
        $html = "<ol class=\"steps\">\n";
        foreach ($items as $item) {
            $title = is_array($item) ? ($item['title'] ?? '') : '';
            $desc = is_array($item) ? ($item['description'] ?? '') : (string) $item;
            $text = $title ? "<strong>{$title}</strong> {$desc}" : $desc;
            $html .= "  <li>{$text}</li>\n";
        }
        $html .= "</ol>\n";

        return $html;
    }

    private function renderDosDonts(Section $section): string
    {
        $content = is_array($section->content) ? $section->content : [];
        $dos = $content['dos'] ?? [];
        $donts = $content['donts'] ?? [];

        $html = "<div class=\"dos-donts\">\n";
        $html .= "  <div class=\"dos\">\n    <h3>Do</h3>\n    <ul>\n";
        foreach ($dos as $d) {
            $html .= "      <li>{$d}</li>\n";
        }
        $html .= "    </ul>\n  </div>\n";
        $html .= "  <div class=\"donts\">\n    <h3>Don't</h3>\n    <ul>\n";
        foreach ($donts as $d) {
            $html .= "      <li>{$d}</li>\n";
        }
        $html .= "    </ul>\n  </div>\n</div>\n";

        return $html;
    }

    private function renderProsCons(Section $section): string
    {
        $content = is_array($section->content) ? $section->content : [];
        $pros = $content['pros'] ?? [];
        $cons = $content['cons'] ?? [];
        $verdict = $content['verdict'] ?? '';

        $html = "<div class=\"pros-cons\">\n";
        $html .= "  <div class=\"pros\">\n    <h3>Pros</h3>\n    <ul>\n";
        foreach ($pros as $p) {
            $html .= "      <li>{$p}</li>\n";
        }
        $html .= "    </ul>\n  </div>\n";
        $html .= "  <div class=\"cons\">\n    <h3>Cons</h3>\n    <ul>\n";
        foreach ($cons as $c) {
            $html .= "      <li>{$c}</li>\n";
        }
        $html .= "    </ul>\n  </div>\n";
        if ($verdict) {
            $html .= "  <p class=\"verdict\"><strong>Verdict:</strong> {$verdict}</p>\n";
        }
        $html .= "</div>\n";

        return $html;
    }

    private function renderSummaryBox(Section $section): string
    {
        $content = is_array($section->content) ? $section->content : [];
        $points = $content['points'] ?? $content;

        $html = "<div class=\"summary-box\">\n  <h3>Key Takeaways</h3>\n  <ul>\n";
        foreach ($points as $p) {
            $html .= "    <li>{$p}</li>\n";
        }
        $html .= "  </ul>\n</div>\n";

        return $html;
    }

    private function renderAccordion(Section $section): string
    {
        $content = is_array($section->content) ? $section->content : [];
        $items = $content['items'] ?? $content;

        $html = '';
        foreach ($items as $item) {
            $heading = is_array($item) ? ($item['heading'] ?? '') : '';
            $body = is_array($item) ? ($item['content'] ?? '') : (string) $item;
            $html .= "<details>\n  <summary>{$heading}</summary>\n  <p>{$body}</p>\n</details>\n";
        }

        return $html;
    }

    private function renderTestimonial(Section $section): string
    {
        $content = is_array($section->content) ? $section->content : [];
        $quote = is_string($section->content) ? $section->content : ($content['quote'] ?? '');
        $author = $section->attributes['author'] ?? ($content['author'] ?? '');
        $role = $section->attributes['role'] ?? ($content['role'] ?? '');
        $company = $section->attributes['company'] ?? ($content['company'] ?? '');

        $cite = implode(', ', array_filter([$author, $role, $company]));
        $citeHtml = $cite ? "\n  <cite>{$cite}</cite>" : '';

        return "<figure class=\"testimonial\">\n  <blockquote><p>{$quote}</p></blockquote>{$citeHtml}\n</figure>\n";
    }

    private function slugify(string $text): string
    {
        $text = strip_tags($text);
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9\s-]/', '', $text) ?? '';
        $text = preg_replace('/[\s-]+/', '-', $text) ?? '';

        return trim($text, '-');
    }
}
