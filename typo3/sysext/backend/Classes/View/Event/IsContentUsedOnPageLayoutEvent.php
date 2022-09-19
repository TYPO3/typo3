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

namespace TYPO3\CMS\Backend\View\Event;

use TYPO3\CMS\Backend\View\PageLayoutContext;

/**
 * Use this Event to identify whether a content element is used.
 */
final class IsContentUsedOnPageLayoutEvent
{
    public function __construct(
        private readonly array $record,
        private bool $used,
        private PageLayoutContext $context
    ) {
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function isRecordUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $isUsed): void
    {
        $this->used = $isUsed;
    }

    public function getKnownColumnPositionNumbers(): array
    {
        return $this->context->getBackendLayout()->getColumnPositionNumbers();
    }

    public function getPageLayoutContext(): PageLayoutContext
    {
        return $this->context;
    }
}
