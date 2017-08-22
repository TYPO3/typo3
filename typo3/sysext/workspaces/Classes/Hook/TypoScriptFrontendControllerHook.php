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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Frontend hooks
 */
class TypoScriptFrontendControllerHook
{
    /**
     * Renders a message at the bottom of the HTML page, can be modified via
     *
     *   config.disablePreviewNotification = 1 (to disable the additional info text)
     *
     * and
     *
     *   config.message_preview_workspace = This is not the online version but the version of "%s" workspace (ID: %s).
     *
     * via TypoScript.
     *
     * @param array $params
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj
     * @return string
     */
    public function renderPreviewInfo(array $params, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $pObj)
    {
        // 2 means preview of a non-live workspace
        if ($pObj->fePreview !== 2) {
            return '';
        }

        if (empty($this->getBackendUserAuthentication()->getSessionData('workspaces.backend_domain'))) {
            $backendDomain = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        } else {
            $backendDomain = $this->getBackendUserAuthentication()->getSessionData('workspaces.backend_domain');
        }

        $content = $pObj->cObj->cObjGetSingle('FLUIDTEMPLATE', [
            'file' => 'EXT:workspaces/Resources/Private/Templates/Preview/Preview.html',
            'variables.' => [
                'backendDomain' => 'TEXT',
                'backendDomain.' => ['value' => $backendDomain]
            ]
        ]);

        if (!isset($pObj->config['config']['disablePreviewNotification']) || (int)$pObj->config['config']['disablePreviewNotification'] !== 1) {
            // get the title of the current workspace
            $currentWorkspaceId = $pObj->whichWorkspace();
            $currentWorkspaceTitle = $this->getWorkspaceTitle($currentWorkspaceId);
            $currentWorkspaceTitle = htmlspecialchars($currentWorkspaceTitle);
            if ($pObj->config['config']['message_preview_workspace']) {
                $content .= sprintf(
                    $pObj->config['config']['message_preview_workspace'],
                    $currentWorkspaceTitle,
                    $currentWorkspaceId ?? -1
                );
            } else {
                $text = LocalizationUtility::translate(
                    'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:previewText',
                    'workspaces',
                    [$currentWorkspaceTitle, $currentWorkspaceId ?? -1]
                );
                if ($pObj->doWorkspacePreview()) {
                    $urlForStoppingPreview = GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'index.php?ADMCMD_prev=LOGOUT&returnUrl=' . rawurlencode(GeneralUtility::getIndpEnv('REQUEST_URI'));
                    $text .= '<br><a style="color: #000;" href="' . $urlForStoppingPreview . '">Stop preview</a>';
                }
                $content .= '<div id="typo3-previewInfo" style="position: absolute; top: 20px; right: 20px; border: 2px solid #000; padding: 5px; background: #f00; font: 1em Verdana; color: #000; font-weight: bold; z-index: 10001">' . $text . '</div>';
            }
        }
        return $content;
    }

    /**
     * Fetches the title of the workspace
     *
     * @param $workspaceId
     * @return string the title of the workspace
     */
    protected function getWorkspaceTitle(int $workspaceId): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_workspace');
        $title = $queryBuilder
            ->select('title')
            ->from('sys_workspace')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($workspaceId, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
        return $title !== false ? $title : '';
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
