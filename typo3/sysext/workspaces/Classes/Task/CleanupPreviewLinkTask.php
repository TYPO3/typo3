<?php
namespace TYPO3\CMS\Workspaces\Task;

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

/**
 * This class provides a task to cleanup ol preview links.
 */
class CleanupPreviewLinkTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Cleanup old preview links.
     * endtime < $GLOBALS['EXEC_TIME']
     *
     * @return bool
     */
    public function execute()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_preview');
        $queryBuilder
            ->delete('sys_preview')
            ->where(
                $queryBuilder->expr()->lt(
                    'endtime',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'], \PDO::PARAM_INT)
                )
            )
            ->execute();

        return true;
    }
}
