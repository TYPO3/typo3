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
        $description = 'A new file permission was introduced regarding replacing files.' .
            ' This update sets "Files:replace" for all BE users/groups with the permission "Files:write".';
        $updateNeeded = false;
        $db = $this->getDatabaseConnection();

        // Fetch user records where the writeFile is set and replaceFile is not
        $notMigratedRowsCount = $db->exec_SELECTcountRows(
            'uid',
            'be_users',
            $this->getWhereClause()
        );
        if ($notMigratedRowsCount > 0) {
            $updateNeeded = true;
        }

        if (!$updateNeeded) {
            // Fetch group records where the writeFile is set and replaceFile is not
            $notMigratedRowsCount = $db->exec_SELECTcountRows(
                'uid',
                'be_groups',
                $this->getWhereClause()
            );
            if ($notMigratedRowsCount > 0) {
                $updateNeeded = true;
            }
        }
        return $updateNeeded;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool Whether everything went smoothly or not
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {
        $db = $this->getDatabaseConnection();

        // Iterate over users and groups table to perform permission updates
        $tablesToProcess = ['be_groups', 'be_users'];
        foreach ($tablesToProcess as $table) {
            $records = $this->getRecordsFromTable($table);
            foreach ($records as $singleRecord) {
                $updateArray = [
                    'file_permissions' => $singleRecord['file_permissions'] . ',replaceFile'
                ];
                $db->exec_UPDATEquery($table, 'uid=' . (int)$singleRecord['uid'], $updateArray);
                // Get last executed query
                $dbQueries[] = str_replace(chr(10), ' ', $db->debug_lastBuiltQuery);
                // Check for errors
                if ($db->sql_error()) {
                    $customMessages = 'SQL-ERROR: ' . htmlspecialchars($db->sql_error());
                    return false;
                }
            }
        }
        $this->markWizardAsDone();
        return true;
    }

    /**
     * Retrieve every record which needs to be processed
     *
     * @param string $table
     * @return array
     */
    protected function getRecordsFromTable($table)
    {
        $fields = implode(',', ['uid', 'file_permissions']);
        $records = $this->getDatabaseConnection()->exec_SELECTgetRows($fields, $table, $this->getWhereClause());
        return $records;
    }

    /**
     * Returns the where clause for database requests
     *
     * @return string
     */
    protected function getWhereClause()
    {
        return 'file_permissions LIKE \'%writeFile%\' AND file_permissions NOT LIKE \'%replaceFile%\'';
    }
}
