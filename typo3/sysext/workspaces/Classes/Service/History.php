<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @author Oliver Hader <oliver.hader@typo3.org>
 * @package Workspaces
 * @subpackage Service
 */
class Tx_Workspaces_Service_History implements t3lib_Singleton {
	/**
	 * @var array
	 */
	protected $backendUserNames;

	/**
	 * @var array
	 */
	protected $historyObjects = array();

	/**
	 * @var t3lib_diff
	 */
	protected $differencesObject;

	/**
	 * Creates this object.
	 */
	public function __construct() {
		require_once PATH_typo3 . 'class.show_rechis.inc';
		$this->backendUserNames = t3lib_BEfunc::getUserNames();
	}

	/**
	 * Gets the editing history of a record.
	 *
	 * @param string $table Name of the table
	 * @param integer $id Uid of the record
	 * @return array Record history entries
	 */
	public function getHistory($table, $id) {
		$history = array();
		$i = 0;

		foreach ($this->getHistoryObject($table, $id)->changeLog as $entry) {
			if ($i++ > 20) {
				break;
			}

			$history[] = $this->getHistoryEntry($entry);
		}

		return $history;
	}

	/**
	 * Gets the human readable representation of one
	 * record history entry.
	 *
	 * @param array $entry Record history entry
	 * @return array
	 * @see getHistory
	 */
	protected function getHistoryEntry(array $entry) {
		if (!empty($entry['action'])) {
			$differences = $entry['action'];
		} else {
			$differences = implode(
				'<br/>',
				$this->getDifferences($entry)
			);
		}

		return array(
			'datetime' => htmlspecialchars(t3lib_BEfunc::datetime($entry['tstamp'])),
			'user' => htmlspecialchars($this->getUserName($entry['user'])),
			'differences' => $differences,
		);
	}

	/**
	 * Gets the differences between two record versions out
	 * of one record history entry.
	 *
	 * @param array $entry Record history entry
	 * @return array
	 */
	protected function getDifferences(array $entry) {
		$differences = array();
		$tableName = $entry['tablename'];

		if (is_array($entry['newRecord'])) {
			$fields = array_keys($entry['newRecord']);

			foreach ($fields as $field) {
				t3lib_div::loadTCA($tableName);

				if (!empty($GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type']) && $GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type'] !== 'passthrough') {

						// Create diff-result:
					$fieldDifferences = $this->getDifferencesObject()->makeDiffDisplay(
						t3lib_BEfunc::getProcessedValue($tableName, $field, $entry['oldRecord'][$field], 0, TRUE),
						t3lib_BEfunc::getProcessedValue($tableName ,$field, $entry['newRecord'][$field], 0, TRUE)
					);

					$differences[] = nl2br($fieldDifferences);
				}
			}
		}

		return $differences;
	}

	/**
	 * Gets the username of a backend user.
	 *
	 * @param string $user
	 * @return string
	 */
	protected function getUserName($user) {
		$userName = 'unknown';

		if (!empty($this->backendUserNames[$user]['username'])) {
			$userName = $this->backendUserNames[$user]['username'];
		}

		return $userName;
	}

	/**
	 * Gets an instance of the record history service.
	 *
	 * @param string $table Name of the table
	 * @param integer $id Uid of the record
	 * @return recordHistory
	 */
	protected function getHistoryObject($table, $id) {
		if (!isset($this->historyObjects[$table][$id])) {
			/** @var $historyObject recordHistory */
			$historyObject = t3lib_div::makeInstance('recordHistory');
			$historyObject->element = $table . ':' . $id;
			$historyObject->createChangeLog();

			$this->historyObjects[$table][$id] = $historyObject;
		}

		return $this->historyObjects[$table][$id];
	}

	/**
	 * Gets an instance of the record differences utility.
	 *
	 * @return t3lib_diff
	 */
	protected function getDifferencesObject() {
		if (!isset($this->differencesObject)) {
			$this->differencesObject = t3lib_div::makeInstance('t3lib_diff');
		}

		return $this->differencesObject;
	}
}
?>