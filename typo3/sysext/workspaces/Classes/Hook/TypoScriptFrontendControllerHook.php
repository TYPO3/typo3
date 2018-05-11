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

        $content = '';

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
                $styles = [];
                $styles[] = 'position: fixed';
                $styles[] = 'top: 15px';
                $styles[] = 'right: 15px';
                $styles[] = 'padding: 8px 18px';
                $styles[] = 'background: #fff3cd';
                $styles[] = 'border: 1px solid #ffeeba';
                $styles[] = 'font-family: sans-serif';
                $styles[] = 'font-size: 14px';
                $styles[] = 'font-weight: bold';
                $styles[] = 'color: #856404';
                $styles[] = 'z-index: 20000';
                $styles[] = 'user-select: none';
                $styles[] = 'pointer-events:none';
                $styles[] = 'text-align: center';
                $styles[] = 'border-radius: 2px';
                $content .= '<div id="typo3-preview-info" style="' . implode(';', $styles) . '">' . $text . '</div>';
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
