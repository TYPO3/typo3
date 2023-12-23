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

namespace TYPO3\CMS\Frontend\ContentObject\Event;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Listeners are able to enrich the final source collection result
 */
final class ModifyImageSourceCollectionEvent
{
    public function __construct(
        private string $sourceCollection,
        private readonly string $fullSourceCollection,
        private readonly array $sourceConfiguration,
        private readonly array $sourceRenderConfiguration,
        private readonly ContentObjectRenderer $contentObjectRenderer
    ) {}

    public function setSourceCollection(string $sourceCollection): void
    {
        $this->sourceCollection = $sourceCollection;
    }

    public function getSourceCollection(): string
    {
        return $this->sourceCollection;
    }

    public function getFullSourceCollection(): string
    {
        return $this->fullSourceCollection;
    }

    public function getSourceConfiguration(): array
    {
        return $this->sourceConfiguration;
    }

    public function getSourceRenderConfiguration(): array
    {
        return $this->sourceRenderConfiguration;
    }

    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->contentObjectRenderer;
    }
}
