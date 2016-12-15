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

        $db = $this->getDatabaseConnection();
        foreach ($this->tableFieldArray as $table => $field) {
            $count = $db->exec_SELECTcountRows(
                '*',
                $table,
                $db->listQuery($field, 'file_list', $table)
            );
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
        $db = $this->getDatabaseConnection();
        foreach ($this->tableFieldArray as $table => $field) {
            $rows = $db->exec_SELECTgetRows(
                'uid,' . $field,
                $table,
                $db->listQuery($field, 'file_list', $table)
            );
            if (empty($rows)) {
                continue;
            }
            foreach ($rows as $row) {
                $moduleList = explode(',', $row[$field]);
                $moduleList = array_combine($moduleList, $moduleList);
                $moduleList['file_list'] = 'file_FilelistList';
                unset($moduleList['file']);
                $db->exec_UPDATEquery(
                    $table,
                    'uid=' . (int)$row['uid'],
                    [
                        $field => implode(',', $moduleList),
                    ]
                );
                $databaseQueries[] = $db->debug_lastBuiltQuery;
            }
        }
        $this->markWizardAsDone();

        return true;
    }
}
