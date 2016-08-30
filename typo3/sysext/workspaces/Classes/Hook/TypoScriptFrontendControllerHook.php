<?php
namespace TYPO3\CMS\Workspaces\Hook;

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
 * Frontend hooks
 */
class TypoScriptFrontendControllerHook
{
    /**
     * @param array $params
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj
     * @return void
     */
    public function hook_eofe($params, $pObj)
    {
        // 2 means preview of a non-live workspace
        if ($pObj->fePreview !== 2) {
            return;
        }

        if (empty($this->getBackendUserAuthentication()->getSessionData('workspaces.backend_domain'))) {
            $backendDomain = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        } else {
            $backendDomain = $this->getBackendUserAuthentication()->getSessionData('workspaces.backend_domain');
        }

        $previewParts = $this->getTypoScriptFrontendController()->cObj->cObjGetSingle('FLUIDTEMPLATE', [
            'file' => 'EXT:workspaces/Resources/Private/Templates/Preview/Preview.html',
            'variables.' => [
                'backendDomain' => 'TEXT',
                'backendDomain.' => ['value' => $backendDomain]
            ]
        ]);
        $this->getTypoScriptFrontendController()->content = str_ireplace('</body>', $previewParts . '</body>', $this->getTypoScriptFrontendController()->content);
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     */
    protected function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
