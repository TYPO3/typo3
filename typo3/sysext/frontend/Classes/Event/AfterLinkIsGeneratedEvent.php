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

namespace TYPO3\CMS\Frontend\Event;

use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;

/**
 * Generic event to modify any kind of link generation with typolink(). This is processed by all
 * frontend-related links.
 *
 * If a link could not be generated, a "UnableToLinkException" could be thrown by an Event Listener.
 */
final class AfterLinkIsGeneratedEvent
{
    public function __construct(
        private LinkResultInterface $linkResult,
        private readonly ContentObjectRenderer $contentObjectRenderer,
        private readonly array $linkInstructions,
    ) {}

    /**
     * Update a link when a part was modified by an Event Listener.
     */
    public function setLinkResult(LinkResultInterface $linkResult): void
    {
        $this->linkResult = $linkResult;
    }

    public function getLinkResult(): LinkResultInterface
    {
        return $this->linkResult;
    }

    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->contentObjectRenderer;
    }

    /**
     * Returns the original instructions / $linkConfiguration that were used to build the link
     */
    public function getLinkInstructions(): array
    {
        return $this->linkInstructions;
    }
}
