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

namespace TYPO3\CMS\Core\Domain\Event;

use TYPO3\CMS\Core\Context\LanguageAspect;

/**
 * Event which is fired before a single page or a list of
 * pages are about to be translated (or tried to be localized).
 */
final class BeforePageLanguageOverlayEvent
{
    public function __construct(
        private array $pageInput,
        private array $pageIds,
        private LanguageAspect $languageAspect
    ) {}

    public function getPageInput(): array
    {
        return $this->pageInput;
    }

    public function setPageInput(array $pageInput): void
    {
        $this->pageInput = $pageInput;
    }

    public function getPageIds(): array
    {
        return $this->pageIds;
    }

    public function setPageIds(array $pageIds): void
    {
        $this->pageIds = array_map('intval', $pageIds);
    }

    public function getLanguageAspect(): LanguageAspect
    {
        return $this->languageAspect;
    }

    public function setLanguageAspect(LanguageAspect $languageAspect): void
    {
        $this->languageAspect = $languageAspect;
    }
}
