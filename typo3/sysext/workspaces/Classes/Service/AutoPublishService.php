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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_workspace');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $result = $queryBuilder
            ->select('uid', 'swap_modes', 'publish_time', 'unpublish_time')
            ->from('sys_workspace')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                ),
                $queryBuilder->orWhere(
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->neq(
                            'publish_time',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->lte(
                            'publish_time',
                            $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                        )
                    ),
                    $queryBuilder->andWhere(
                        $queryBuilder->expr()->eq(
                            'publish_time',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->neq(
                            'unpublish_time',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->lte(
                            'unpublish_time',
                            $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                        )
                    )
                )
            )
            ->execute();

        $workspaceService = GeneralUtility::makeInstance(\TYPO3\CMS\Workspaces\Service\WorkspaceService::class);
        while ($rec = $result->fetch()) {
            // First, clear start/end time so it doesn't get select once again:
            $fieldArray = $rec['publish_time'] != 0
                ? ['publish_time' => 0]
                : ['unpublish_time' => 0];

            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('sys_workspace')
                ->update(
                    'sys_workspace',
                    $fieldArray,
                    ['uid' => (int)$rec['uid']]
                );

            // Get CMD array:
            $cmd = $workspaceService->getCmdArrayForPublishWS($rec['uid'], $rec['swap_modes'] == 1);
            // $rec['swap_modes']==1 means that auto-publishing will swap versions, not just publish and empty the workspace.
            // Execute CMD array:
            $tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
            $tce->start([], $cmd);
            $tce->process_cmdmap();
        }
        // Restore admin status
        $GLOBALS['BE_USER']->user['admin'] = $currentAdminStatus;
    }
}
