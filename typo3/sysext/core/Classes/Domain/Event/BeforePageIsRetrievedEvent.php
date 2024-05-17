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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Page;

/**
 * Event which is fired before a page (id) is being resolved from PageRepository.
 *
 * Allows to change the corresponding page ID, e.g. to resolve a different page
 * with custom overlaying, or to fully resolve the page on your own.
 */
final class BeforePageIsRetrievedEvent
{
    private ?Page $page = null;

    public function __construct(
        private int $pageId,
        private bool $skipGroupAccessCheck,
        private readonly Context $context,
    ) {}

    public function getPage(): ?Page
    {
        return $this->page;
    }

    public function setPage(Page $page): void
    {
        $this->page = $page;
    }

    public function hasPage(): bool
    {
        return $this->page !== null;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function setPageId(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function skipGroupAccessCheck(): void
    {
        $this->skipGroupAccessCheck = true;
    }

    public function respectGroupAccessCheck(): void
    {
        $this->skipGroupAccessCheck = false;
    }

    public function isGroupAccessCheckSkipped(): bool
    {
        return $this->skipGroupAccessCheck;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
