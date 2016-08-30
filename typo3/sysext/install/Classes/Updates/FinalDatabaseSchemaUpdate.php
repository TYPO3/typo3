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
 * Contains the update class to create and alter tables, fields and keys to comply to the database schema
 */
class FinalDatabaseSchemaUpdate extends AbstractDatabaseSchemaUpdate
{
    /**
     * Constructor function.
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = 'Update database schema: Modify tables and fields';
    }

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool TRUE if an update is needed, FALSE otherwise
     */
    public function checkForUpdate(&$description)
    {
        $contextService = $this->objectManager->get(\TYPO3\CMS\Install\Service\ContextService::class);
        $description = 'There are tables or fields in the database which need to be changed.<br /><br />' .
        'This update wizard can be run only when there are no other update wizards left to make sure they have all needed fields unchanged.<br /><br />' .
        'If you want to apply changes selectively, <a href="Install.php?install[action]=importantActions&amp;install[context]=' . $contextService->getContextString() . '&amp;install[controller]=tool">go to Database Analyzer</a>.';

        $databaseDifferences = $this->getDatabaseDifferences();
        $updateSuggestions = $this->schemaMigrationService->getUpdateSuggestions($databaseDifferences);

        return isset($updateSuggestions['change']);
    }

    /**
     * Second step: Show tables, fields and keys to create or update
     *
     * @param string $inputPrefix input prefix, all names of form fields have to start with this. Append custom name in [ ... ]
     * @return string HTML output
     */
    public function getUserInput($inputPrefix)
    {
        $result = '';

        $databaseDifferences = $this->getDatabaseDifferences();
        $updateSuggestions = $this->schemaMigrationService->getUpdateSuggestions($databaseDifferences);

        if (!isset($updateSuggestions['change'])) {
            return $result;
        }

        $fieldsList = '
			<p>
				Change the following fields in tables:
			</p>
			<fieldset>
				<ol class="t3-install-form-label-after">%s</ol>
			</fieldset>';
        $keysList = '
			<p>
				Change the following keys in tables:
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
        foreach ($databaseDifferences['diff'] as $tableName => $difference) {
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
        if (!empty($fieldItems)) {
            $result .= sprintf($fieldsList, implode('', $fieldItems));
        }
        if (!empty($keyItems)) {
            $result .= sprintf($keysList, implode('', $keyItems));
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
        $customMessagesArray = [];
        foreach ((array)$updateStatements['change'] as $query) {
            $db->admin_query($query);
            $dbQueries[] = $query;
            if ($db->sql_error()) {
                $customMessagesArray[] = 'SQL-ERROR: ' . htmlspecialchars($db->sql_error());
            }
        }

        if (!empty($customMessagesArray)) {
            $customMessages = 'Update process not fully processed. This can happen because of dependencies of table fields and ' .
                'indexes. Please repeat this step! Following errors occurred:' . LF . LF . implode(LF, $customMessagesArray);
        }

        return empty($customMessagesArray);
    }
}
