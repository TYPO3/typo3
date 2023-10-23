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

namespace TYPO3\CMS\Backend\Controller\Event;

use TYPO3\CMS\Core\View\ViewInterface;

/**
 * This event triggers after a page has been rendered.
 *
 * Listeners may update the page content string with a modified
 * version if appropriate.
 */
final class AfterBackendPageRenderEvent
{
    public function __construct(private string $content, private readonly ViewInterface $view) {}

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    public function getView(): ViewInterface
    {
        return $this->view;
    }
}
