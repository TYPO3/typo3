<?php
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

namespace TYPO3\CMS\Install\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update backend user setting startModule if set to "file_list"
 */
class FileListIsStartModuleUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update filelist user setting "startModule"';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $needsExecution = false;

        $statement = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users')
            ->select(['uid', 'uc'], 'be_users');
        while ($backendUser = $statement->fetch()) {
            if ($backendUser['uc'] !== null) {
                $userConfig = unserialize($backendUser['uc'], ['allowed_classes' => false]);
                if ($userConfig['startModule'] === 'file_list') {
                    $needsExecution = true;
                    break;
                }
            }
        }

        if ($needsExecution) {
            $description = 'The backend user setting startModule is changed for the extension filelist.'
               . ' Update all backend users that use ext:filelist as startModule.';
        }

        return $needsExecution;
    }

    /**
     * Performs the database update if backend user's startmodule is file_list
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('be_users');
        $statement = $queryBuilder->select('uid', 'uc')->from('be_users')->execute();
        while ($backendUser = $statement->fetch()) {
            if ($backendUser['uc'] !== null) {
                $userConfig = unserialize($backendUser['uc'], ['allowed_classes' => false]);
                if ($userConfig['startModule'] === 'file_list') {
                    $userConfig['startModule'] = 'file_FilelistList';
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                        ->getQueryBuilderForTable('be_users');
                    $queryBuilder->update('be_users')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($backendUser['uid'], \PDO::PARAM_INT)
                            )
                        )
                        ->set('uc', serialize($userConfig));
                    $databaseQueries[] = $queryBuilder->getSQL();
                    $queryBuilder->execute();
                }
            }
        }
        $this->markWizardAsDone();
        return true;
    }
}
