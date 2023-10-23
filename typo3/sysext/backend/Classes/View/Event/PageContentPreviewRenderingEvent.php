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

use Psr\EventDispatcher\StoppableEventInterface;
use TYPO3\CMS\Backend\View\PageLayoutContext;

/**
 * Use this Event to have a custom preview for a content type in the Page Module
 */
final class PageContentPreviewRenderingEvent implements StoppableEventInterface
{
    private ?string $content = null;

    public function __construct(
        private readonly string $table,
        private array $record,
        private readonly PageLayoutContext $context
    ) {}

    public function getTable(): string
    {
        return $this->table;
    }

    public function getRecord(): array
    {
        return $this->record;
    }

    public function setRecord(array $record): void
    {
        $this->record = $record;
    }

    public function getPageLayoutContext(): PageLayoutContext
    {
        return $this->context;
    }

    public function getPreviewContent(): ?string
    {
        return $this->content;
    }

    public function setPreviewContent(string $content): void
    {
        $this->content = $content;
    }

    public function isPropagationStopped(): bool
    {
        return $this->content !== null;
    }
}
