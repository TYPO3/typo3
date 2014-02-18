<?php
namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Framework;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
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
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * DataHandler Actions
 */
class ActionService {

	/**
	 * @var DataHandler
	 */
	protected $dataHandler;

	/**
	 * @param DataHandler $dataHandler
	 */
	public function __construct(DataHandler $dataHandler) {
		$this->setDataHandler($dataHandler);
	}

	/**
	 * @param DataHandler $dataHandler
	 */
	public function setDataHandler(DataHandler $dataHandler) {
		$this->dataHandler = $dataHandler;
	}

	/**
	 * @return DataHandler
	 */
	public function getDataHander() {
		return $this->dataHandler;
	}

	/**
	 * @param string $tableName
	 * @param integer $pageId
	 * @param array $recordData
	 * @return array
	 */
	public function createNewRecord($tableName, $pageId, array $recordData) {
		return $this->createNewRecords($pageId, array($tableName => $recordData));
	}

	/**
	 * @param integer $pageId
	 * @param array $tableRecordData
	 * @return array
	 */
	public function createNewRecords($pageId, array $tableRecordData) {
		$dataMap = array();
		$newTableIds = array();
		$currentUid = NULL;
		foreach ($tableRecordData as $tableName => $recordData) {
			$recordData = $this->resolvePreviousUid($recordData, $currentUid);
			$recordData['pid'] = $pageId;
			$currentUid = uniqid('NEW');
			$newTableIds[$tableName][] = $currentUid;
			$dataMap[$tableName][$currentUid] = $recordData;
		}
		$this->dataHandler->start($dataMap, array());
		$this->dataHandler->process_datamap();

		foreach ($newTableIds as $tableName => &$ids) {
			foreach ($ids as &$id) {
				if (!empty($this->dataHandler->substNEWwithIDs[$id])) {
					$id = $this->dataHandler->substNEWwithIDs[$id];
				}
			}
		}

		return $newTableIds;
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param array $recordData
	 * @param NULL|array $deleteTableRecordIds
	 */
	public function modifyRecord($tableName, $uid, array $recordData, array $deleteTableRecordIds = NULL) {
		$dataMap = array(
			$tableName => array(
				$uid => $recordData,
			),
		);
		$commandMap = array();
		if (!empty($deleteTableRecordIds)) {
			foreach ($deleteTableRecordIds as $tableName => $recordIds) {
				foreach ($recordIds as $recordId) {
					$commandMap[$tableName][$recordId]['delete'] = TRUE;
				}
			}
		}
		$this->dataHandler->start($dataMap, $commandMap);
		$this->dataHandler->process_datamap();
		if (!empty($commandMap)) {
			$this->dataHandler->process_cmdmap();
		}
	}

	/**
	 * @param integer $pageId
	 * @param array $tableRecordData
	 */
	public function modifyRecords($pageId, array $tableRecordData) {
		$dataMap = array();
		$currentUid = NULL;
		foreach ($tableRecordData as $tableName => $recordData) {
			if (empty($recordData['uid'])) {
				continue;
			}
			$recordData = $this->resolvePreviousUid($recordData, $currentUid);
			$currentUid = $recordData['uid'];
			if ($recordData['uid'] === '__NEW') {
				$recordData['pid'] = $pageId;
				$currentUid = uniqid('NEW');
			}
			unset($recordData['uid']);
			$dataMap[$tableName][$currentUid] = $recordData;
		}
		$this->dataHandler->start($dataMap, array());
		$this->dataHandler->process_datamap();
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 */
	public function deleteRecord($tableName, $uid) {
		$this->deleteRecords(
			array(
				$tableName => array($uid),
			)
		);
	}

	/**
	 * @param array $tableRecordIds
	 */
	public function deleteRecords(array $tableRecordIds) {
		$commandMap = array();
		foreach ($tableRecordIds as $tableName => $ids) {
			foreach ($ids as $uid) {
				$commandMap[$tableName][$uid] = array(
					'delete' => TRUE,
				);
			}
		}
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param integer $pageId
	 * @return array
	 */
	public function copyRecord($tableName, $uid, $pageId) {
		$commandMap = array(
			$tableName => array(
				$uid => array(
					'copy' => $pageId,
				),
			),
		);
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
		return $this->dataHandler->copyMappingArray;
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param integer $pageId
	 */
	public function moveRecord($tableName, $uid, $pageId) {
		$commandMap = array(
			$tableName => array(
				$uid => array(
					'move' => $pageId,
				),
			),
		);
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param integer $languageId
	 * @return array
	 */
	public function localizeRecord($tableName, $uid, $languageId) {
		$commandMap = array(
			$tableName => array(
				$uid => array(
					'localize' => $languageId,
				),
			),
		);
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
		return $this->dataHandler->copyMappingArray;
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param string $fieldName
	 * @param array $referenceIds
	 */
	public function modifyReferences($tableName, $uid, $fieldName, array $referenceIds) {
		$dataMap = array(
			$tableName => array(
				$uid => array(
					$fieldName => implode(',', $referenceIds),
				),
			)
		);
		$this->dataHandler->start($dataMap, array());
		$this->dataHandler->process_datamap();
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param string $fieldName
	 * @param integer $referenceId
	 */
	public function addReference($tableName, $uid, $fieldName, $referenceId) {
		$recordValues = $this->getRecordValues($tableName, $uid, $fieldName);

		if (!in_array($referenceId, $recordValues)) {
			$recordValues[] = $referenceId;
		}

		$this->modifyReferences($tableName, $uid, $fieldName, $recordValues);
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param string $fieldName
	 * @param integer $referenceId
	 */
	public function deleteReference($tableName, $uid, $fieldName, $referenceId) {
		$recordValues = $this->getRecordValues($tableName, $uid, $fieldName);

		if (($index = array_search($referenceId, $recordValues)) !== FALSE) {
			unset($recordValues[$index]);
		}

		$this->modifyReferences($tableName, $uid, $fieldName, $recordValues);
	}

	/**
	 * @param array $recordData
	 * @param NULL|int $previousUid
	 * @return array
	 */
	protected function resolvePreviousUid(array $recordData, $previousUid) {
		if ($previousUid === NULL) {
			return $recordData;
		}
		foreach ($recordData as $fieldName => $fieldValue) {
			if (strpos($fieldValue, '__previousUid') === FALSE) {
				continue;
			}
			$recordData[$fieldName] = str_replace('__previousUid', $previousUid, $fieldValue);
		}
		return $recordData;
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param string $fieldName
	 * @return array
	 */
	protected function getRecordValues($tableName, $uid, $fieldName) {
		$recordValues = array();

		$recordValue = $this->getRecordValue($tableName, $uid, $fieldName);
		if (!empty($recordValue)) {
			$recordValues = explode(',', $recordValues);
		}

		return $recordValues;
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param string $fieldName
	 * @return bool|string|NULL
	 */
	protected function getRecordValue($tableName, $uid, $fieldName) {
		$recordValue = FALSE;

		$record = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			$fieldName, $tableName, 'uid=' . (int)$uid
		);

		if (isset($record[$fieldName])) {
			$recordValue = $record[$fieldName];
		}

		return $recordValue;
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
