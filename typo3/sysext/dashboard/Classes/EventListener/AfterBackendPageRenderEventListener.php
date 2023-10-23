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

namespace TYPO3\CMS\Dashboard\EventListener;

use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Adds custom CSS needed for EXT:dashboard in the "outer" backend scaffold
 */
final class AfterBackendPageRenderEventListener
{
    public function __construct(private readonly PageRenderer $pageRenderer) {}

    public function __invoke(): void
    {
        $this->pageRenderer->addCssFile('EXT:dashboard/Resources/Public/Css/Modal/style.css');
    }
}
