<?php
namespace TYPO3\CMS\Workspaces\ExtDirect;

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
 * ExtDirect toolbar menu
 * @deprecated since TYPO3 CMS 7, this file will be removed in TYPO3 CMS 8, as the AJAX functionality is now done via plain AJAX
 */
class ToolbarMenu
{
    /**
     * Toggle workspace preview mode
     *
     * @param $parameter
     * @return array
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, not in use anymore
     */
    public function toggleWorkspacePreviewMode($parameter)
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
        $newState = $GLOBALS['BE_USER']->user['workspace_preview'] ? '0' : '1';
        $GLOBALS['BE_USER']->setWorkspacePreview($newState);
        return ['newWorkspacePreviewState' => $newState];
    }

    /**
     * Set workspace
     *
     * @param $parameter
     * @return array
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, not in use anymore
     */
    public function setWorkspace($parameter)
    {
        \TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
        $workspaceId = (int)$parameter->workSpaceId;
        $pageId = (int)$parameter->pageId;

        $GLOBALS['BE_USER']->setWorkspace($workspaceId);

        while ($pageId) {
            $page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL('pages', $pageId, '*',
                ' AND pages.t3ver_wsid IN (0, ' . $workspaceId . ')');
            if ($page) {
                if ($GLOBALS['BE_USER']->doesUserHaveAccess($page, 1)) {
                    break;
                }
            } else {
                $page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pageId);
            }
            $pageId = $page['pid'];
        }

        return [
            'title' => \TYPO3\CMS\Workspaces\Service\WorkspaceService::getWorkspaceTitle($workspaceId),
            'id' => $workspaceId,
            'page' => (isset($page['uid']) && ($parameter->pageId == $page['uid'])) ? null : (int)$page['uid']
        ];
    }
}
