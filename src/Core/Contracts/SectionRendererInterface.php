<?php

declare(strict_types=1);

namespace ContentPulse\Core\Contracts;

use ContentPulse\Core\DTO\Section;

interface SectionRendererInterface
{
    /**
     * Render a single normalized section to the target format (HTML, WP blocks, etc.).
     */
    public function render(Section $section): string;

    /**
     * Render multiple sections to the target format.
     *
     * @param  Section[]  $sections
     */
    public function renderAll(array $sections): string;

    /**
     * Check whether this renderer supports the given section type.
     */
    public function supports(string $sectionType): bool;
}
