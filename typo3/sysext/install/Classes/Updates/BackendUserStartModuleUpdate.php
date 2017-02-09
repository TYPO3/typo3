<?php
namespace TYPO3\CMS\Install\Updates;

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
 * Update backend user setting startModule if set to "help_aboutmodules"
 */
class BackendUserStartModuleUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update backend user setting "startModule"';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $statement = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users')
            ->select(['uid', 'uc'], 'be_users', []);
        $needsExecution = false;
        while ($backendUser = $statement->fetch()) {
            if ($backendUser['uc'] !== null) {
                $userConfig = unserialize($backendUser['uc'], ['allowed_classes' => false]);
                if ($userConfig['startModule'] === 'help_aboutmodules'
                    || $userConfig['startModule'] === 'help_AboutmodulesAboutmodules'
                ) {
                    $needsExecution = true;
                    break;
                }
            }
        }
        if ($needsExecution) {
            $description = 'The backend user setting startModule is changed for the extension aboutmodules. Update all'
                . ' backend users that use ext:aboutmodules as startModule.';
        }
        return $needsExecution;
    }

    /**
     * Performs the database update if backend user's startmodule is
     * "help_aboutmodules" or "help_AboutmodulesAboutmodules"
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        $statement = $queryBuilder->select('uid', 'uc')->from('be_users')->execute();
        while ($backendUser = $statement->fetch()) {
            if ($backendUser['uc'] !== null) {
                $userConfig = unserialize($backendUser['uc'], ['allowed_classes' => false]);
                if ($userConfig['startModule'] === 'help_aboutmodules'
                    || $userConfig['startModule'] === 'help_AboutmodulesAboutmodules'
                ) {
                    $userConfig['startModule'] = 'help_AboutAboutmodules';
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
                    $queryBuilder->update('be_users')
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($backendUser['uid'], \PDO::PARAM_INT)
                            )
                        )
                        // Manual quoting and false as third parameter to have the final
                        // value in $databaseQueries and not a statement placeholder
                        ->set('uc', serialize($userConfig));
                    $databaseQueries[] = $queryBuilder->getSQL();
                    $queryBuilder->execute();
                }
            }
        }
        return true;
    }
}
