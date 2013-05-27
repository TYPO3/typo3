<?php
namespace TYPO3\CMS\Workspaces\Service;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
 * Service for history
 *
 * @author Oliver Hader <oliver.hader@typo3.org>
 */
class HistoryService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $backendUserNames;

	/**
	 * @var array
	 */
	protected $historyObjects = array();

	/**
	 * @var \TYPO3\CMS\Core\Utility\DiffUtility
	 */
	protected $differencesObject;

	/**
	 * Creates this object.
	 */
	public function __construct() {
		require_once PATH_typo3 . 'class.show_rechis.inc';
		$this->backendUserNames = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames();
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
			$differences = implode('<br/>', $this->getDifferences($entry));
		}
		return array(
			'datetime' => htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::datetime($entry['tstamp'])),
			'user' => htmlspecialchars($this->getUserName($entry['user'])),
			'differences' => $differences
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
				if (!empty($GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type']) && $GLOBALS['TCA'][$tableName]['columns'][$field]['config']['type'] !== 'passthrough') {
					// Create diff-result:
					$fieldDifferences = $this->getDifferencesObject()->makeDiffDisplay(\TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($tableName, $field, $entry['oldRecord'][$field], 0, TRUE), \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValue($tableName, $field, $entry['newRecord'][$field], 0, TRUE));
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
	 * @return \TYPO3\CMS\Backend\History\RecordHistory
	 */
	protected function getHistoryObject($table, $id) {
		if (!isset($this->historyObjects[$table][$id])) {
			/** @var $historyObject \TYPO3\CMS\Backend\History\RecordHistory */
			$historyObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\History\\RecordHistory');
			$historyObject->element = $table . ':' . $id;
			$historyObject->createChangeLog();
			$this->historyObjects[$table][$id] = $historyObject;
		}
		return $this->historyObjects[$table][$id];
	}

	/**
	 * Gets an instance of the record differences utility.
	 *
	 * @return \TYPO3\CMS\Core\Utility\DiffUtility
	 */
	protected function getDifferencesObject() {
		if (!isset($this->differencesObject)) {
			$this->differencesObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\DiffUtility');
		}
		return $this->differencesObject;
	}

}


?>