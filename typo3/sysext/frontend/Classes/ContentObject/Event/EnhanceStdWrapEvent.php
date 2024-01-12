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
 * Listeners to this Event are able to modify the stdWrap processing, enhancing the functionality and
 * manipulating the final result / content. This is the parent Event, which allows the corresponding
 * listeners to be called on each step, see child Events:
 *
 * @see BeforeStdWrapFunctionsInitializedEvent
 * @see AfterStdWrapFunctionsInitializedEvent
 * @see BeforeStdWrapFunctionsExecutedEvent
 * @see AfterStdWrapFunctionsExecutedEvent
 *
 * Note: The class is declared abstract to prevent it from being dispatched directly.
 *       Only child classes are to be dispatched to prevent duplicate executions.
 */
abstract class EnhanceStdWrapEvent
{
    public function __construct(
        private ?string $content,
        private readonly array $configuration,
        private readonly ContentObjectRenderer $contentObjectRenderer
    ) {}

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    public function getContentObjectRenderer(): ContentObjectRenderer
    {
        return $this->contentObjectRenderer;
    }
}
