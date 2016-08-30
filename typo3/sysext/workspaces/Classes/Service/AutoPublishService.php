<?php
namespace TYPO3\CMS\Workspaces\Service;

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
 * Automatic publishing of workspaces.
 */
class AutoPublishService
{
    /**
     * This method is called by the Scheduler task that triggers
     * the autopublication process
     * It searches for workspaces whose publication date is in the past
     * and publishes them
     *
     * @return void
     */
    public function autoPublishWorkspaces()
    {
        // Temporarily set admin rights
        // @todo once workspaces are cleaned up a better solution should be implemented
        $currentAdminStatus = $GLOBALS['BE_USER']->user['admin'];
        $GLOBALS['BE_USER']->user['admin'] = 1;
        // Select all workspaces that needs to be published / unpublished:
        $workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'uid,swap_modes,publish_time,unpublish_time',
            'sys_workspace',
            'pid=0
				AND
				((publish_time!=0 AND publish_time<=' . (int)$GLOBALS['EXEC_TIME'] . ')
				OR (publish_time=0 AND unpublish_time!=0 AND unpublish_time<=' . (int)$GLOBALS['EXEC_TIME'] . '))' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_workspace')
        );
        $workspaceService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        foreach ($workspaces as $rec) {
            // First, clear start/end time so it doesn't get select once again:
            $fieldArray = $rec['publish_time'] != 0 ? ['publish_time' => 0] : ['unpublish_time' => 0];
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_workspace', 'uid=' . (int)$rec['uid'], $fieldArray);
            // Get CMD array:
            $cmd = $workspaceService->getCmdArrayForPublishWS($rec['uid'], $rec['swap_modes'] == 1);
            // $rec['swap_modes']==1 means that auto-publishing will swap versions, not just publish and empty the workspace.
            // Execute CMD array:
            $tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $tce->stripslashes_values = 0;
            $tce->start([], $cmd);
            $tce->process_cmdmap();
        }
        // Restore admin status
        $GLOBALS['BE_USER']->user['admin'] = $currentAdminStatus;
    }
}
