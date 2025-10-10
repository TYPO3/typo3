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

namespace TYPO3\CMS\Fluid\ViewHelpers\Page;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to add various content in the head section of the current page using PageRenderer.
 *
 * ```
 *    <f:page.headerData>
 *       <link rel="preload" href="/fonts/myfont.woff2" as="font" type="font/woff2" crossorigin="anonymous">
 *       <link rel="dns-prefetch" href="//example-cdn.com">
 *       <link rel="preconnect" href="https://example-cdn.com">
 *    </f:page.headerData>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-page-headerdata
 */
final class HeaderDataViewHelper extends AbstractViewHelper
{
    public function __construct(private readonly PageRenderer $pageRenderer) {}

    public function render(): string
    {
        $this->pageRenderer->addHeaderData($this->renderChildren());
        return '';
    }
}
