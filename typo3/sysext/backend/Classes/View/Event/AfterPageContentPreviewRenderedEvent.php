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
use TYPO3\CMS\Core\Domain\RecordInterface;

/**
 * Use this Event to modify a custom preview for a content type in the
 * Page Module after PageContentPreviewRenderingEvent and content preview rendering is executed.
 */
final class AfterPageContentPreviewRenderedEvent
{
    public function __construct(
        private readonly string $table,
        private readonly string $recordType,
        private readonly RecordInterface $record,
        private readonly PageLayoutContext $context,
        private string $previewContent,
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecordType(): string
    {
        return $this->recordType;
    }

    public function getRecord(): RecordInterface
    {
        return $this->record;
    }

    public function getPageLayoutContext(): PageLayoutContext
    {
        return $this->context;
    }

    public function getPreviewContent(): string
    {
        return $this->previewContent;
    }

    public function setPreviewContent(string $content): void
    {
        $this->previewContent = $content;
    }
}
