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

namespace TYPO3\CMS\Impexp\Hook;

use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class adds import export related JavaScript to the backend
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class BackendControllerHook
{
    /**
     * Adds ImportExport-specific JavaScript
     *
     * @param array $configuration
     * @param BackendController $backendController
     */
    public function addJavaScript(array $configuration, BackendController $backendController): void
    {
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineSetting('ImportExport', 'exportModuleUrl', (string)$uriBuilder->buildUriFromRoute('tx_impexp_export'));
        $pageRenderer->addInlineSetting('ImportExport', 'importModuleUrl', (string)$uriBuilder->buildUriFromRoute('tx_impexp_import'));
    }
}
