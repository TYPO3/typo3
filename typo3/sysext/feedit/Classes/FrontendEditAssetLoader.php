<?php
namespace TYPO3\CMS\Feedit;

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

use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FrontendEditAssetLoader
 */
class FrontendEditAssetLoader
{
    /**
     * @param EditDocumentController $controller
     */
    public function attachAssets(EditDocumentController $controller)
    {
        if ((int)GeneralUtility::_GP('feEdit') === 1) {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            // We have to load some locallang strings and push them into TYPO3.LLL if this request was
            // triggered by feedit. Originally, this object is fed by BackendController which is not
            // called here. This block of code is intended to be removed at a later point again.
            $lang = $this->getLanguageService();
            $coreLabels = [
                'csh_tooltip_loading' => $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:csh_tooltip_loading')
            ];
            $generatedLabels = [];
            $generatedLabels['core'] = $coreLabels;
            $code = 'TYPO3.LLL = ' . json_encode($generatedLabels) . ';';
            $filePath = 'typo3temp/assets/js/backend-' . sha1($code) . '.js';
            if (!file_exists(PATH_site . $filePath)) {
                // writeFileToTypo3tempDir() returns NULL on success (please double-read!)
                $error = GeneralUtility::writeFileToTypo3tempDir(PATH_site . $filePath, $code);
                if ($error !== null) {
                    throw new \RuntimeException('Locallang JS file could not be written to ' . $filePath . '. Reason: ' . $error, 1446118286);
                }
            }
            $pageRenderer->addJsFile('../' . $filePath);
        }
    }

    /**
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
