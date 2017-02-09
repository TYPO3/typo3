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
 * Upgrade wizard which goes through all users and groups and set the "replaceFile" permission if "writeFile" is set
 */
class FilesReplacePermissionUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Set the "Files:replace" permission for all BE user/groups with "Files:write" set';

    /**
     * @var array
     */
    protected $tablesToProcess = ['be_users', 'be_groups'];

    /**
     * Checks whether updates are required.
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $needsExecution = false;

        foreach ($this->tablesToProcess as $table) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $numberOfUpgradeRows = $queryBuilder->count('uid')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->like(
                        'file_permissions',
                        $queryBuilder->createNamedParameter('%writeFile%', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->notLike(
                        'file_permissions',
                        $queryBuilder->createNamedParameter('%replaceFile%', \PDO::PARAM_STR)
                    )
                )
                ->execute()
                ->fetchColumn(0);
            if ($numberOfUpgradeRows > 0) {
                $needsExecution = true;
                break;
            }
        }

        if ($needsExecution) {
            $description = 'A new file permission was introduced regarding replacing files.'
                . ' This update sets "Files:replace" for all BE users/groups with the permission "Files:write".';
        }

        return $needsExecution;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        foreach ($this->tablesToProcess as $table) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $statement = $queryBuilder->select('uid', 'file_permissions')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->like(
                        'file_permissions',
                        $queryBuilder->createNamedParameter('%writeFile%', \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->notLike(
                        'file_permissions',
                        $queryBuilder->createNamedParameter('%replaceFile%', \PDO::PARAM_STR)
                    )
                )
                ->execute();
            while ($record = $statement->fetch()) {
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->update($table)
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT)
                        )
                    )
                    // Manual quoting to have the final value in $databaseQueries and not a statement placeholder
                    ->set('file_permissions', $record['file_permissions'] . ',replaceFile');
                $databaseQueries[] = $queryBuilder->getSQL();
                $queryBuilder->execute();
            }
        }
        $this->markWizardAsDone();
        return true;
    }
}
