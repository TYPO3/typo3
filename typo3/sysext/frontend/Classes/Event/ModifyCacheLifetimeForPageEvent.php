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

use TYPO3\CMS\Core\Context\Context;

/**
 * Event to allow listeners to modify the amount of seconds that a generated frontend page
 * should be cached in the "pages" cache when initially generated.
 */
final class ModifyCacheLifetimeForPageEvent
{
    public function __construct(
        private int $cacheLifetime,
        private readonly int $pageId,
        private readonly array $pageRecord,
        private readonly array $renderingInstructions,
        private readonly Context $context
    ) {}

    public function setCacheLifetime(int $cacheLifetime): void
    {
        $this->cacheLifetime = $cacheLifetime;
    }

    public function getCacheLifetime(): int
    {
        return $this->cacheLifetime;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function getPageRecord(): array
    {
        return $this->pageRecord;
    }

    public function getRenderingInstructions(): array
    {
        return $this->renderingInstructions;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
