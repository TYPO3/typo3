<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Html\Event;

/**
 * Event that is fired before RteHtmlParser modified the HTML input from the database to the RTE editor
 * (for example transforming linebreaks)
 */
final class BeforeTransformTextForRichTextEditorEvent
{
    public function __construct(
        private string $htmlContent,
        private readonly string $initialHtmlContent,
        private readonly array $processingConfiguration
    ) {}

    public function getHtmlContent(): string
    {
        return $this->htmlContent;
    }

    public function setHtmlContent(string $htmlContent): void
    {
        $this->htmlContent = $htmlContent;
    }

    public function getInitialHtmlContent(): string
    {
        return $this->initialHtmlContent;
    }

    public function getProcessingConfiguration(): array
    {
        return $this->processingConfiguration;
    }
}
