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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Tcemain service
 */
class DataHandlerHook
{
    /**
     * In case a sys_workspace_stage record is deleted we do a hard reset
     * for all existing records in that stage to avoid that any of these end up
     * as orphan records.
     *
     * @param string $command
     * @param string $table
     * @param string $id
     * @param string $value
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tcemain
     * @return void
     */
    public function processCmdmap_postProcess($command, $table, $id, $value, \TYPO3\CMS\Core\DataHandling\DataHandler $tcemain)
    {
        if ($command === 'delete') {
            if ($table === \TYPO3\CMS\Workspaces\Service\StagesService::TABLE_STAGE) {
                $this->resetStageOfElements($id);
            } elseif ($table === \TYPO3\CMS\Workspaces\Service\WorkspaceService::TABLE_WORKSPACE) {
                $this->flushWorkspaceElements($id);
            }
        }
    }

    /**
     * hook that is called AFTER all commands of the commandmap was
     * executed
     *
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tcemainObj reference to the main tcemain object
     * @return void
     */
    public function processCmdmap_afterFinish(\TYPO3\CMS\Core\DataHandling\DataHandler $tcemainObj)
    {
        $this->flushWorkspaceCacheEntriesByWorkspaceId($tcemainObj->BE_USER->workspace);
    }

    /**
     * In case a sys_workspace_stage record is deleted we do a hard reset
     * for all existing records in that stage to avoid that any of these end up
     * as orphan records.
     *
     * @param int $stageId Elements with this stage are resetted
     * @return void
     */
    protected function resetStageOfElements($stageId)
    {
        $fields = ['t3ver_stage' => \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_EDIT_ID];
        foreach ($this->getTcaTables() as $tcaTable) {
            if (BackendUtility::isTableWorkspaceEnabled($tcaTable)) {
                $where = 't3ver_stage = ' . (int)$stageId;
                $where .= ' AND t3ver_wsid > 0 AND pid=-1';
                $where .= BackendUtility::deleteClause($tcaTable);
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery($tcaTable, $where, $fields);
            }
        }
    }

    /**
     * Flushes elements of a particular workspace to avoid orphan records.
     *
     * @param int $workspaceId The workspace to be flushed
     * @return void
     */
    protected function flushWorkspaceElements($workspaceId)
    {
        $command = [];
        foreach ($this->getTcaTables() as $tcaTable) {
            if (BackendUtility::isTableWorkspaceEnabled($tcaTable)) {
                $where = '1=1';
                $where .= BackendUtility::getWorkspaceWhereClause($tcaTable, $workspaceId);
                $where .= BackendUtility::deleteClause($tcaTable);
                $records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', $tcaTable, $where, '', '', '', 'uid');
                if (is_array($records)) {
                    foreach ($records as $recordId => $_) {
                        $command[$tcaTable][$recordId]['version']['action'] = 'flush';
                    }
                }
            }
        }
        if (!empty($command)) {
            $tceMain = $this->getTceMain();
            $tceMain->start([], $command);
            $tceMain->process_cmdmap();
        }
    }

    /**
     * Gets all defined TCA tables.
     *
     * @return array
     */
    protected function getTcaTables()
    {
        return array_keys($GLOBALS['TCA']);
    }

    /**
     * @return \TYPO3\CMS\Core\DataHandling\DataHandler
     */
    protected function getTceMain()
    {
        $tceMain = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
        $tceMain->stripslashes_values = 0;
        return $tceMain;
    }

    /**
     * Flushes the workspace cache for current workspace and for the virtual "all workspaces" too.
     *
     * @param int $workspaceId The workspace to be flushed in cache
     * @return void
     */
    protected function flushWorkspaceCacheEntriesByWorkspaceId($workspaceId)
    {
        $workspacesCache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('workspaces_cache');
        $workspacesCache->flushByTag($workspaceId);
        $workspacesCache->flushByTag(\TYPO3\CMS\Workspaces\Service\WorkspaceService::SELECT_ALL_WORKSPACES);
    }
}
