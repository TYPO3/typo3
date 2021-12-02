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

abstract class AbstractSectionMarkupGeneratedEvent implements StoppableEventInterface
{
    private array $columnConfig;
    private PageLayoutContext $pageLayoutContext;
    private array $records;

    private string $content = '';
    private bool $stopRendering = false;

    public function __construct(array $columnConfig, PageLayoutContext $pageLayoutContext, array $records)
    {
        $this->columnConfig = $columnConfig;
        $this->pageLayoutContext = $pageLayoutContext;
        $this->records = $records;
    }

    public function getColumnConfig(): array
    {
        return $this->columnConfig;
    }

    public function getPageLayoutContext(): PageLayoutContext
    {
        return $this->pageLayoutContext;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function setContent(string $content = ''): void
    {
        $this->content = $content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Prevent other listeners from being called if rendering is stopped by listener.
     */
    public function isPropagationStopped(): bool
    {
        return $this->stopRendering;
    }

    public function setStopRendering(bool $stopRendering): void
    {
        $this->stopRendering = $stopRendering;
    }
}
