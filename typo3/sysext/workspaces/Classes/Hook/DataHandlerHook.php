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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\StagesService;

/**
 * DataHandler service
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
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     * @return void
     */
    public function processCmdmap_postProcess($command, $table, $id, $value, \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler)
    {
        if ($command === 'delete') {
            if ($table === StagesService::TABLE_STAGE) {
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
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler reference to the main DataHandler object
     * @return void
     */
    public function processCmdmap_afterFinish(\TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler)
    {
        $this->flushWorkspaceCacheEntriesByWorkspaceId($dataHandler->BE_USER->workspace);
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
        foreach ($this->getTcaTables() as $tcaTable) {
            if (BackendUtility::isTableWorkspaceEnabled($tcaTable)) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tcaTable);

                $queryBuilder
                    ->update($tcaTable)
                    ->set('t3ver_stage', StagesService::STAGE_EDIT_ID)
                    ->where(
                        $queryBuilder->expr()->eq(
                            't3ver_stage',
                            $queryBuilder->createNamedParameter($stageId, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'pid',
                            $queryBuilder->createNamedParameter(-1, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->gt(
                            't3ver_wsid',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
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
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($tcaTable);
                $queryBuilder->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                    ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class, $workspaceId, false));

                $result = $queryBuilder
                    ->select('uid')
                    ->from($tcaTable)
                    ->orderBy('uid')
                    ->execute();

                while (($recordId = $result->fetchColumn()) !== false) {
                    $command[$tcaTable][$recordId]['version']['action'] = 'flush';
                }
            }
        }
        if (!empty($command)) {
            $dataHandler = $this->getDataHandler();
            $dataHandler->start([], $command);
            $dataHandler->process_cmdmap();
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
    protected function getDataHandler()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
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
