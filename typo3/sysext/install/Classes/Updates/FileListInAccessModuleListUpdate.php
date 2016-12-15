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
 * Update module access to the file list module
 */
class FileListInAccessModuleListUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update module access to file list module';

    /**
     * @var array
     */
    protected $tableFieldArray = [
        'be_groups' => 'groupMods',
        'be_users' => 'userMods',
    ];

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }

        $description = 'The module name of the file list module has been changed. Update the access list of all backend groups and users where this module is available.';

        foreach ($this->tableFieldArray as $table => $field) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $count = $queryBuilder->count('*')
                ->from($table)
                ->where(
                    $queryBuilder->expr()->inSet($field, $queryBuilder->expr()->literal('file_list'))
                )
                ->execute()
                ->fetchColumn(0);
            if ($count > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Performs the database update for module access to file_list
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom messages
     *
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        foreach ($this->tableFieldArray as $table => $field) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable($table);
            $queryBuilder->getRestrictions()->removeAll();
            $statement = $queryBuilder->select('uid', $field)
                ->from($table)
                ->where(
                    $queryBuilder->expr()->inSet($field, $queryBuilder->expr()->literal('file_list'))
                )
                ->execute();
            while ($row = $statement->fetch()) {
                $moduleList = explode(',', $row[$field]);
                $moduleList = array_combine($moduleList, $moduleList);
                $moduleList['file_list'] = 'file_FilelistList';
                unset($moduleList['file']);
                $updateQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable($table);
                $updateQueryBuilder->update($table)
                    ->where(
                        $updateQueryBuilder->expr()->eq(
                            'uid',
                            $updateQueryBuilder->createNamedParameter($row['uid'], \PDO::PARAM_INT)
                        )
                    )
                    ->set($field, implode(',', $moduleList));
                $databaseQueries[] = $updateQueryBuilder->getSQL();
                $updateQueryBuilder->execute();
            }
        }
        $this->markWizardAsDone();

        return true;
    }
}
