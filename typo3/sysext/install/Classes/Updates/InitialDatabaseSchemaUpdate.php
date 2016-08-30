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
 * Contains the update class to create tables, fields and keys to comply to the database schema
 */
class InitialDatabaseSchemaUpdate extends AbstractDatabaseSchemaUpdate
{
    /**
     * Constructor function.
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = 'Update database schema: Create tables and fields';
    }

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool TRUE if an update is needed, FALSE otherwise
     */
    public function checkForUpdate(&$description)
    {
        $description = 'There are tables or fields in the database which need to be created.<br /><br />' .
        'You have to run this update wizard before you can run any other update wizard to make sure all needed tables and fields are present.';

        $databaseDifferences = $this->getDatabaseDifferences();
        $updateSuggestions = $this->schemaMigrationService->getUpdateSuggestions($databaseDifferences);

        return isset($updateSuggestions['create_table']) || isset($updateSuggestions['add']);
    }

    /**
     * Second step: Show tables, fields and keys to be created
     *
     * @param string $inputPrefix input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
     * @return string HTML output
     */
    public function getUserInput($inputPrefix)
    {
        $result = '';

        $databaseDifferences = $this->getDatabaseDifferences();
        $updateSuggestions = $this->schemaMigrationService->getUpdateSuggestions($databaseDifferences);

        if (isset($updateSuggestions['create_table'])) {
            $list = '
				<p>
					Add the following tables:
				</p>
				<fieldset>
					<ol class="t3-install-form-label-after">%s</ol>
				</fieldset>';
            $item = '
				<li class="labelAfter">
					<label><strong>%1$s</strong></label>
				</li>';

            $items = [];
            foreach ($databaseDifferences['extra'] as $tableName => $difference) {
                if ($difference['whole_table'] == 1) {
                    $items[] = sprintf($item, $tableName);
                }
            }
            $result .= sprintf($list, implode('', $items));
        }

        if (isset($updateSuggestions['add'])) {
            $fieldsList = '
				<p>
					Add the following fields to tables:
				</p>
				<fieldset>
					<ol class="t3-install-form-label-after">%s</ol>
				</fieldset>';
            $keysList = '
				<p>
					Add the following keys to tables:
				</p>
				<fieldset>
					<ol class="t3-install-form-label-after">%s</ol>
				</fieldset>';
            $item = '
				<li class="labelAfter">
					<label><strong>%1$s</strong>: %2$s</label>
				</li>';

            $fieldItems = [];
            $keyItems = [];
            foreach ($databaseDifferences['extra'] as $tableName => $difference) {
                if ($difference['whole_table'] != 1) {
                    if ($difference['fields']) {
                        $fieldNames = [];
                        foreach ($difference['fields'] as $fieldName => $sql) {
                            $fieldNames[] = $fieldName;
                        }
                        $fieldItems[] = sprintf($item, $tableName, implode(', ', $fieldNames));
                    }
                    if ($difference['keys']) {
                        $keyNames = [];
                        foreach ($difference['keys'] as $keyName => $sql) {
                            $keyNames[] = $keyName;
                        }
                        $keyItems[] = sprintf($item, $tableName, implode(', ', $keyNames));
                    }
                }
            }
            if (!empty($fieldItems)) {
                $result .= sprintf($fieldsList, implode('', $fieldItems));
            }
            if (!empty($keyItems)) {
                $result .= sprintf($keysList, implode('', $keyItems));
            }
        }

        return $result;
    }

    /**
     * Performs the database update.
     *
     * @param array &$dbQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool TRUE on success, FALSE on error
     */
    public function performUpdate(array &$dbQueries, &$customMessages)
    {

        // First perform all add update statements to database
        $databaseDifferences = $this->getDatabaseDifferences();
        $updateStatements = $this->schemaMigrationService->getUpdateSuggestions($databaseDifferences);

        $db = $this->getDatabaseConnection();
        foreach ((array)$updateStatements['create_table'] as $query) {
            $db->admin_query($query);
            $dbQueries[] = $query;
            if ($db->sql_error()) {
                $customMessages = 'SQL-ERROR: ' . htmlspecialchars($db->sql_error());
                return false;
            }
        }

        foreach ((array)$updateStatements['add'] as $query) {
            $db->admin_query($query);
            $dbQueries[] = $query;
            if ($db->sql_error()) {
                $customMessages = 'SQL-ERROR: ' . htmlspecialchars($db->sql_error());
                return false;
            }
        }

        return true;
    }
}
