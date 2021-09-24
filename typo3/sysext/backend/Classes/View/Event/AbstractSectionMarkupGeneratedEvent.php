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
use TYPO3\CMS\Backend\View\PageLayoutView;

abstract class AbstractSectionMarkupGeneratedEvent implements StoppableEventInterface
{
    /**
     * @deprectaed will be removed in v12
     */
    private PageLayoutView $pageLayoutView;

    /**
     * @deprecated will be removed in v12
     */
    private int $languageId;

    private array $columnConfig;
    private PageLayoutContext $pageLayoutContext;
    private array $records;

    private string $content = '';
    private bool $stopRendering = false;

    public function __construct(
        PageLayoutView $pageLayoutView,
        int $languageId,
        array $columnConfig,
        PageLayoutContext $pageLayoutContext,
        array $records
    ) {
        $this->pageLayoutView = $pageLayoutView;
        $this->languageId = $languageId;
        $this->columnConfig = $columnConfig;
        $this->pageLayoutContext = $pageLayoutContext;
        $this->records = $records;
    }

    /**
     * @return PageLayoutView
     * @deprecated will be removed in v12
     */
    public function getPageLayoutView(): PageLayoutView
    {
        trigger_error(
            __METHOD__ . ' is deprecated and will be removed in TYPO3 v12. Use the PageLayoutContext instead.',
            E_USER_DEPRECATED
        );

        return $this->pageLayoutView;
    }

    /**
     * @return int
     * @deprecated will be removed in v12
     */
    public function getLanguageId(): int
    {
        trigger_error(
            __METHOD__ . ' is deprecated and will be removed in TYPO3 v12. Fetch the language via the PageLayoutContext instead.',
            E_USER_DEPRECATED
        );

        return $this->languageId;
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
