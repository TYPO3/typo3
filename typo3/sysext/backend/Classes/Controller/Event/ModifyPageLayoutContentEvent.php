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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;

/**
 * Listeners to this Event will be able to modify the header and footer content of the page module
 */
final class ModifyPageLayoutContentEvent
{
    private string $headerContent = '';
    private string $footerContent = '';

    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ModuleTemplate $moduleTemplate
    ) {}

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getModuleTemplate(): ModuleTemplate
    {
        return $this->moduleTemplate;
    }

    /**
     * Set content for the header. Can also be used to e.g. reorder existing content.
     * IMPORTANT: This overwrites existing content from previous listeners!
     */
    public function setHeaderContent(string $content): void
    {
        $this->headerContent = $content;
    }

    /**
     * Add additional content to the header
     */
    public function addHeaderContent(string $content): void
    {
        $this->headerContent .= $content;
    }

    public function getHeaderContent(): string
    {
        return $this->headerContent;
    }

    /**
     * Set content for the footer. Can also be used to e.g. reorder existing content.
     * IMPORTANT: This overwrites existing content from previous listeners!
     */
    public function setFooterContent(string $content): void
    {
        $this->footerContent = $content;
    }

    /**
     * Add additional content to the footer
     */
    public function addFooterContent(string $content): void
    {
        $this->footerContent .= $content;
    }

    public function getFooterContent(): string
    {
        return $this->footerContent;
    }
}
