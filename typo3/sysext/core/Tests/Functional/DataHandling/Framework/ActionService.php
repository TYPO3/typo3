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
		$previousTableName = NULL;
		$previousUid = NULL;
		foreach ($tableRecordData as $tableName => $recordData) {
			$recordData = $this->resolvePreviousUid($recordData, $currentUid);
			if (!isset($recordData['pid'])) {
				$recordData['pid'] = $pageId;
			}
			$currentUid = uniqid('NEW');
			$newTableIds[$tableName][] = $currentUid;
			$dataMap[$tableName][$currentUid] = $recordData;
			if ($previousTableName !== NULL && $previousUid !== NULL) {
				$dataMap[$previousTableName][$previousUid] = $this->resolveNextUid(
					$dataMap[$previousTableName][$previousUid],
					$currentUid
				);
			}
			$previousTableName = $tableName;
			$previousUid = $currentUid;
		}
		$this->createDataHandler();
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
		$this->createDataHandler();
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
		$previousTableName = NULL;
		$previousUid = NULL;
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
			if ($previousTableName !== NULL && $previousUid !== NULL) {
				$dataMap[$previousTableName][$previousUid] = $this->resolveNextUid(
					$dataMap[$previousTableName][$previousUid],
					$currentUid
				);
			}
			$previousTableName = $tableName;
			$previousUid = $currentUid;
		}
		$this->createDataHandler();
		$this->dataHandler->start($dataMap, array());
		$this->dataHandler->process_datamap();
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @return array
	 */
	public function deleteRecord($tableName, $uid) {
		return $this->deleteRecords(
			array(
				$tableName => array($uid),
			)
		);
	}

	/**
	 * @param array $tableRecordIds
	 * @return array
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
		$this->createDataHandler();
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
		// Deleting workspace records is actually a copy(!)
		return $this->dataHandler->copyMappingArray;
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 */
	public function clearWorkspaceRecord($tableName, $uid) {
		$this->clearWorkspaceRecords(
			array(
				$tableName => array($uid),
			)
		);
	}

	/**
	 * @param array $tableRecordIds
	 */
	public function clearWorkspaceRecords(array $tableRecordIds) {
		$commandMap = array();
		foreach ($tableRecordIds as $tableName => $ids) {
			foreach ($ids as $uid) {
				$commandMap[$tableName][$uid] = array(
					'version' => array(
						'action' => 'clearWSID',
					)
				);
			}
		}
		$this->createDataHandler();
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param integer $pageId
	 * @param NULL|array $recordData
	 * @return array
	 */
	public function copyRecord($tableName, $uid, $pageId, array $recordData = NULL) {
		$commandMap = array(
			$tableName => array(
				$uid => array(
					'copy' => $pageId,
				),
			),
		);
		if ($recordData !== NULL) {
			$commandMap[$tableName][$uid]['copy'] = array(
				'action' => 'paste',
				'target' => $pageId,
				'update' => $recordData,
			);
		}
		$this->createDataHandler();
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
		return $this->dataHandler->copyMappingArray;
	}

	/**
	 * @param string $tableName
	 * @param integer $uid
	 * @param integer $pageId
	 * @param NULL|array $recordData
	 */
	public function moveRecord($tableName, $uid, $pageId, array $recordData = NULL) {
		$commandMap = array(
			$tableName => array(
				$uid => array(
					'move' => $pageId,
				),
			),
		);
		if ($recordData !== NULL) {
			$commandMap[$tableName][$uid]['move'] = array(
				'action' => 'paste',
				'target' => $pageId,
				'update' => $recordData,
			);
		}
		$this->createDataHandler();
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
		$this->createDataHandler();
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
		$this->createDataHandler();
		$this->dataHandler->start($dataMap, array());
		$this->dataHandler->process_datamap();
	}

	/**
	 * @param string $tableName
	 * @param int $liveUid
	 * @param bool $throwException
	 */
	public function publishRecord($tableName, $liveUid, $throwException = TRUE) {
		$this->publishRecords(array($tableName => array($liveUid)), $throwException);
	}

	/**
	 * @param array $tableLiveUids
	 * @param bool $throwException
	 * @throws \TYPO3\CMS\Core\Tests\Exception
	 */
	public function publishRecords(array $tableLiveUids, $throwException = TRUE) {
		$commandMap = array();
		foreach ($tableLiveUids as $tableName => $liveUids) {
			foreach ($liveUids as $liveUid) {
				$versionedUid = $this->getVersionedId($tableName, $liveUid);
				if (empty($versionedUid)) {
					if ($throwException) {
						throw new \TYPO3\CMS\Core\Tests\Exception('Versioned UID could not be determined');
					} else {
						continue;
					}
				}

				$commandMap[$tableName][$liveUid] = array(
					'version' => array(
						'action' => 'swap',
						'swapWith' => $versionedUid,
						'notificationAlternativeRecipients' => array(),
					),
				);
			}
		}
		$this->createDataHandler();
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
	}

	/**
	 * @param int $workspaceId
	 */
	public function publishWorkspace($workspaceId) {
		$commandMap = $this->getWorkspaceService()->getCmdArrayForPublishWS($workspaceId, FALSE);
		$this->createDataHandler();
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
	}

	/**
	 * @param int $workspaceId
	 */
	public function swapWorkspace($workspaceId) {
		$commandMap = $this->getWorkspaceService()->getCmdArrayForPublishWS($workspaceId, TRUE);
		$this->createDataHandler();
		$this->dataHandler->start(array(), $commandMap);
		$this->dataHandler->process_cmdmap();
	}

	/**
	 * @param array $recordData
	 * @param NULL|string|int $previousUid
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
	 * @param array $recordData
	 * @param NULL|string|int $nextUid
	 * @return array
	 */
	protected function resolveNextUid(array $recordData, $nextUid) {
		if ($nextUid === NULL) {
			return $recordData;
		}
		foreach ($recordData as $fieldName => $fieldValue) {
			if (strpos($fieldValue, '__nextUid') === FALSE) {
				continue;
			}
			$recordData[$fieldName] = str_replace('__nextUid', $nextUid, $fieldValue);
		}
		return $recordData;
	}

	/**
	 * @param string $tableName
	 * @param int $liveUid
	 * @param bool $useDeleteClause
	 * @return NULL|int
	 */
	protected function getVersionedId($tableName, $liveUid, $useDeleteClause = FALSE) {
		$versionedId = NULL;
		$liveUid = (int)$liveUid;
		$workspaceId = (int)$this->getBackendUser()->workspace;
		$row = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
			'uid',
			$tableName,
			'pid=-1 AND t3ver_oid=' . $liveUid . ' AND t3ver_wsid=' . $workspaceId .
			($useDeleteClause ? \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($tableName) : '')
		);
		if (!empty($row['uid'])) {
			$versionedId = (int)$row['uid'];
		}
		return $versionedId;
	}

	/**
	 * @return \TYPO3\CMS\Core\DataHandling\DataHandler
	 */
	protected function createDataHandler() {
		$dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\DataHandling\\DataHandler'
		);
		$this->dataHandler = $dataHandler;
		return $dataHandler;
	}

	/**
	 * @return \TYPO3\CMS\Workspaces\Service\WorkspaceService
	 */
	protected function getWorkspaceService() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Workspaces\\Service\\WorkspaceService'
		);
	}

	/**
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
