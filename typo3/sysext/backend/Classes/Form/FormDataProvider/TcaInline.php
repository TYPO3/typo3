<?php
namespace TYPO3\CMS\Backend\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Resolve and prepare inline data.
 *
 * @todo: This class is currently only a stub and lots of data preparation is still done in render containers
 */
class TcaInline extends AbstractItemProvider implements FormDataProviderInterface {

	/**
	 * Resolve inline fields
	 *
	 * @param array $result
	 * @return array
	 */
	public function addData(array $result) {
		$result = $this->addInlineExpandCollapseState($result);
		$result = $this->addInlineFirstPid($result);

		foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
			if (empty($fieldConfig['config']['type']) || $fieldConfig['config']['type'] !== 'inline') {
				continue;
			}

			$result = $this->resolveConnectedRecordUids($result, $fieldName);
		}

		return $result;
	}

	/**
	 * Add expand / collapse state of inline items for this parent table / uid combination
	 *
	 * @param array $result Incoming result
	 * @return array Modified result
	 */
	protected function addInlineExpandCollapseState(array $result) {
		$inlineView = unserialize($this->getBackendUser()->uc['inlineView']);
		if (!is_array($inlineView)) {
			$inlineView = [];
		}
		if ($result['command'] !== 'new') {
			$table = $result['tableName'];
			$uid = $result['databaseRow']['uid'];
			if (!empty($inlineView[$table][$uid])) {
				$inlineView = $inlineView[$table][$uid];
			}
		}
		$result['inlineExpandCollapseStateArray'] = $inlineView;
		return $result;
	}

	/**
	 * The "entry" pid for inline records. Nested inline records can potentially hang around on different
	 * pid's, but the entry pid is needed for AJAX calls, so that they would know where the action takes place on the page structure.
	 *
	 * @param array $result Incoming result
	 * @return array Modified result
	 * @todo: Find out when and if this is different from 'effectivePid'
	 */
	protected function addInlineFirstPid(array $result) {
		if (is_null($result['inlineFirstPid'])) {
			$table = $result['tableName'];
			$row = $result['databaseRow'];
			// If the parent is a page, use the uid(!) of the (new?) page as pid for the child records:
			if ($table == 'pages') {
				$liveVersionId = BackendUtility::getLiveVersionIdOfRecord('pages', $row['uid']);
				$pid = is_null($liveVersionId) ? $row['uid'] : $liveVersionId;
			} elseif ($row['pid'] < 0) {
				$prevRec = BackendUtility::getRecord($table, abs($row['pid']));
				$pid = $prevRec['pid'];
			} else {
				$pid = $row['pid'];
			}
			$result['inlineFirstPid'] = (int)$pid;
		}
		return $result;
	}

	/**
	 * Use RelationHandler to resolve connected uids
	 *
	 * @param array $result Result array
	 * @param string $fieldName Current handle field name
	 * @return array Modified item array
	 */
	protected function resolveConnectedRecordUids(array $result, $fieldName) {
		$localTable = $result['tableName'];
		$localUid = $result['databaseRow']['uid'];
		$localTca = $result['processedTca']['columns'][$fieldName];
		$localFieldcontent = $result['databaseRow'][$fieldName];
		$directlyConnectedIds = GeneralUtility::trimExplode(',', $localFieldcontent);

		if (empty($localTca['config']['MM'])) {
			$localUid = $this->getLiveDefaultId($localTable, $localUid);
		}
		/** @var RelationHandler $relationHandler */
		$relationHandler = GeneralUtility::makeInstance(RelationHandler::class);
		$relationHandler->registerNonTableValues = (bool)$localTca['config']['allowedIdValues'];
		$relationHandler->start($localFieldcontent, $localTca['config']['foreign_table'], $localTca['config']['MM'], $localUid, $localTable, $localTca['config']);
		$foreignRecordUids = $relationHandler->getValueArray();

		$resolvedForeignRecordUids = [];
		foreach ($foreignRecordUids as $aForeignRecordUid) {
			if ($localTca['config']['MM'] || $localTca['config']['foreign_field']) {
				$resolvedForeignRecordUids[] = $aForeignRecordUid;
			} else {
				foreach ($directlyConnectedIds as $id) {
					if ((int)$aForeignRecordUid === (int)$id) {
						$resolvedForeignRecordUids[] = $aForeignRecordUid;
					}
				}
			}
		}

		$result['databaseRow'][$fieldName] = implode(',', $resolvedForeignRecordUids);

		return $result;
	}

	/**
	 * Gets the record uid of the live default record. If already
	 * pointing to the live record, the submitted record uid is returned.
	 *
	 * @param string $tableName
	 * @param int $uid
	 * @return int
	 */
	protected function getLiveDefaultId($tableName, $uid) {
		$liveDefaultId = BackendUtility::getLiveVersionIdOfRecord($tableName, $uid);
		if ($liveDefaultId === NULL) {
			$liveDefaultId = $uid;
		}
		return $liveDefaultId;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}

}
