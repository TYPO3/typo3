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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        if (TYPO3_MODE !== 'BE') {
            return;
        }
        $beUser = $this->getBeUser();
        if ($beUser && !empty($beUser->user)) {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextHelp');
            $pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', BackendUtility::getModuleUrl('help_CshmanualCshmanual', [
                'tx_cshmanual_help_cshmanualcshmanual' => [
                    'controller' => 'Help',
                    'action' => 'detail'
                ]
            ]));
        }
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBeUser()
    {
        return isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : null;
    }
}
