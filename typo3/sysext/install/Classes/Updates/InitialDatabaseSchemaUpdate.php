<?php
namespace TYPO3\CMS\Install\Updates;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Stephan Großberndt <stephan@grossberndt.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Contains the update class to create tables, fields and keys to comply to the database schema
 *
 * @author Stephan Großberndt <stephan@grossberndt.de>
 */
class InitialDatabaseSchemaUpdate extends AbstractDatabaseSchemaUpdate {

	/**
	 * Constructor function.
	 */
	public function __construct() {
		parent::__construct();
		$this->title = 'Update database schema: Create tables and fields';
	}

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description The description for the update
	 * @return bool TRUE if an update is needed, FALSE otherwise
	 */
	public function checkForUpdate(&$description) {
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
	public function getUserInput($inputPrefix) {
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

			$items = array();
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

			$fieldItems = array();
			$keyItems = array();
			foreach ($databaseDifferences['extra'] as $tableName => $difference) {
				if ($difference['whole_table'] != 1) {
					if ($difference['fields']) {
						$fieldNames = array();
						foreach ($difference['fields'] as $fieldName => $sql) {
							$fieldNames[] = $fieldName;
						}
						$fieldItems[] = sprintf($item, $tableName, implode(', ', $fieldNames));
					}
					if ($difference['keys']) {
						$keyNames = array();
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
	public function performUpdate(array &$dbQueries, &$customMessages) {

		// First perform all add update statements to database
		$databaseDifferences = $this->getDatabaseDifferences();
		$updateStatements = $this->schemaMigrationService->getUpdateSuggestions($databaseDifferences);

		foreach ((array)$updateStatements['create_table'] as $query) {
			$GLOBALS['TYPO3_DB']->admin_query($query);
			$dbQueries[] = $query;
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
				return FALSE;
			}
		}

		foreach ((array)$updateStatements['add'] as $query) {
			$GLOBALS['TYPO3_DB']->admin_query($query);
			$dbQueries[] = $query;
			if ($GLOBALS['TYPO3_DB']->sql_error()) {
				$customMessages = 'SQL-ERROR: ' . htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
				return FALSE;
			}
		}

		return TRUE;
	}
}
