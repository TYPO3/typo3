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
use TYPO3\CMS\Backend\View\PageLayoutView;

abstract class AbstractSectionMarkupGeneratedEvent implements StoppableEventInterface
{
    /**
     * @var array
     */
    private $columnConfig = [];

    /**
     * @var string
     */
    private $content = '';

    /**
     * @var PageLayoutView
     */
    private $pageLayoutView;

    /**
     * @var int
     */
    private $languageId;

    /**
     * @var bool
     */
    private $stopRendering = false;

    public function __construct(PageLayoutView $pageLayoutView, int $languageId, array $columnConfig)
    {
        $this->pageLayoutView = $pageLayoutView;
        $this->languageId = $languageId;
        $this->columnConfig = $columnConfig;
    }

    public function getPageLayoutView(): PageLayoutView
    {
        return $this->pageLayoutView;
    }

    public function getLanguageId(): int
    {
        return $this->languageId;
    }

    public function getColumnConfig(): array
    {
        return $this->columnConfig;
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
