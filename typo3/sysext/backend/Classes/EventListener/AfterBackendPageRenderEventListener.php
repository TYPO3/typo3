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

namespace TYPO3\CMS\Backend\EventListener;

use TYPO3\CMS\Backend\Search\LiveSearch\DatabaseRecordProvider;
use TYPO3\CMS\Backend\Search\LiveSearch\PageRecordProvider;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Adds custom renderers for the backend live search
 */
final class AfterBackendPageRenderEventListener
{
    public function __construct(private readonly PageRenderer $pageRenderer) {}

    public function __invoke(): void
    {
        $javaScriptRenderer = $this->pageRenderer->getJavaScriptRenderer();
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/live-search/result-types/default-result-type.js', 'registerType')
                ->invoke(null, DatabaseRecordProvider::class)
        );
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/live-search/result-types/page-result-type.js', 'registerRenderer')
                ->invoke(null, PageRecordProvider::class)
        );
    }
}
