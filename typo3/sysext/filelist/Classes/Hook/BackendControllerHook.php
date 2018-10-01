<?php
namespace TYPO3\CMS\Filelist\Hook;

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

use TYPO3\CMS\Backend\Controller\BackendController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class adds Filelist related JavaScript to the backend
 * @internal this is a concrete TYPO3 hook implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class BackendControllerHook
{
    /**
     * Adds Filelist JavaScript used e.g. by context menu
     *
     * @param array $configuration
     * @param BackendController $backendController
     */
    public function addJavaScript(array $configuration, BackendController $backendController)
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $pageRenderer->addInlineSetting('FileRename', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('file_rename'));
        $pageRenderer->addInlineSetting('FileEdit', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('file_edit'));
        $pageRenderer->addInlineSetting('FileUpload', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('file_upload'));
        $pageRenderer->addInlineSetting('FileCreate', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('file_newfolder'));
        $pageRenderer->addInlineSetting('FileCommit', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('tce_file'));
    }
}
