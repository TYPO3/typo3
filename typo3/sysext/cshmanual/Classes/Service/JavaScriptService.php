<?php
namespace TYPO3\CMS\Cshmanual\Service;

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

/**
 * JavaScript Service adding JS code to each backend page
 */
class JavaScriptService
{
    /**
     * Include the JS for the Context Sensitive Help
     *
     * @param string $title the title of the page
     * @param \TYPO3\CMS\Backend\Template\DocumentTemplate $documentTemplateObject
     */
    public function addJavaScript($title, $documentTemplateObject)
    {
        if (TYPO3_MODE === 'BE' && is_object($GLOBALS['BE_USER'])) {
            $pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextHelp');
            $pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('help_CshmanualCshmanual', array(
                'tx_cshmanual_help_cshmanualcshmanual' => array(
                    'controller' => 'Help',
                    'action' => 'detail'
                )
            )));
        }
    }
}
