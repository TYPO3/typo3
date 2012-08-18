<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
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
 * ExtDirect server
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage ExtDirect
 */
class Tx_Workspaces_ExtDirect_Server extends Tx_Workspaces_ExtDirect_AbstractHandler {
	/**
	 * @var Tx_Workspaces_Service_GridData
	 */
	protected $gridDataService;

	/**
	 * @var Tx_Workspaces_Service_Stages
	 */
	protected $stagesService;

	/**
	 * Checks integrity of elements before peforming actions on them.
	 *
	 * @param stdClass $parameters
	 * @return array
	 */
	public function checkIntegrity(stdClass $parameters) {
		$integrity = $this->createIntegrityService(
			$this->getAffectedElements($parameters)
		);

		$integrity->check();

		$response = array(
			'result' => $integrity->getStatusRepresentation(),
		);

		return $response;
	}

	/**
	 * Get List of workspace changes
	 *
	 * @param object $parameter
	 * @return array $data
	 */
	public function getWorkspaceInfos($parameter) {
			// To avoid too much work we use -1 to indicate that every page is relevant
		$pageId = $parameter->id > 0 ? $parameter->id : -1;

		if (!isset($parameter->language) || !t3lib_utility_Math::canBeInterpretedAsInteger($parameter->language)) {
			$parameter->language = NULL;
		}

		$versions = $this->getWorkspaceService()->selectVersionsInWorkspace(
			$this->getCurrentWorkspace(),
			0,
			-99,
			$pageId,
			$parameter->depth,
			'tables_select',
			$parameter->language
		);

		$data = $this->getGridDataService()->generateGridListFromVersions($versions, $parameter, $this->getCurrentWorkspace());
		return $data;
	}

	/**
	 * Get List of available workspace actions
	 *
	 * @param object $parameter
	 * @return array $data
	 */
	public function getStageActions($parameter) {
		$currentWorkspace = $this->getCurrentWorkspace();
		$stages = array();
		if ($currentWorkspace != Tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES) {
			$stages = $this->getStagesService()->getStagesForWSUser();
		}

		$data = array(
			'total' => count($stages),
			'data' => $stages
		);
		return $data;
	}

	/**
	 * Fetch futher information to current selected worspace record.
	 *
	 * @param object $parameter
	 * @return array $data
	 */
	public function getRowDetails($parameter) {
		$diffReturnArray = array();
		$liveReturnArray = array();

		/** @var $t3lib_diff t3lib_diff */
		$t3lib_diff = t3lib_div::makeInstance('t3lib_diff');

		/** @var $parseObj t3lib_parsehtml_proc */
		$parseObj = t3lib_div::makeInstance('t3lib_parsehtml_proc');

		$liveRecord = t3lib_BEfunc::getRecord($parameter->table, $parameter->t3ver_oid);
		$versionRecord = t3lib_BEfunc::getRecord($parameter->table, $parameter->uid);
		$icon_Live = t3lib_iconWorks::mapRecordTypeToSpriteIconClass($parameter->table, $liveRecord);
		$icon_Workspace = t3lib_iconWorks::mapRecordTypeToSpriteIconClass($parameter->table, $versionRecord);
		$stagePosition = $this->getStagesService()->getPositionOfCurrentStage($parameter->stage);

		$fieldsOfRecords = array_keys($liveRecord);

		// get field list from TCA configuration, if available
		t3lib_div::loadTCA($parameter->table);
		if ($GLOBALS['TCA'][$parameter->table]) {
			if ($GLOBALS['TCA'][$parameter->table]['interface']['showRecordFieldList']) {
				$fieldsOfRecords = $GLOBALS['TCA'][$parameter->table]['interface']['showRecordFieldList'];
				$fieldsOfRecords = t3lib_div::trimExplode(',', $fieldsOfRecords, 1);
			}
		}

		foreach ($fieldsOfRecords as $fieldName) {
				// check for exclude fields
			if ($GLOBALS['BE_USER']->isAdmin() || ($GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['exclude'] == 0) || t3lib_div::inList($GLOBALS['BE_USER']->groupData['non_exclude_fields'], $parameter->table . ':' . $fieldName)) {
					// call diff class only if there is a difference
				if (strcmp($liveRecord[$fieldName], $versionRecord[$fieldName]) !== 0) {
						// Select the human readable values before diff
					$liveRecord[$fieldName] = t3lib_BEfunc::getProcessedValue($parameter->table, $fieldName, $liveRecord[$fieldName], 0, 1);
					$versionRecord[$fieldName] = t3lib_BEfunc::getProcessedValue($parameter->table, $fieldName, $versionRecord[$fieldName], 0, 1);

						// Get the field's label. If not available, use the field name
					$fieldTitle = $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($parameter->table, $fieldName));
					if (empty($fieldTitle)) {
						$fieldTitle = $fieldName;
					}

					if ($GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['config']['type'] == 'group' && $GLOBALS['TCA'][$parameter->table]['columns'][$fieldName]['config']['internal_type'] == 'file') {
						$versionThumb = t3lib_BEfunc::thumbCode($versionRecord, $parameter->table, $fieldName, '');
						$liveThumb = t3lib_BEfunc::thumbCode($liveRecord, $parameter->table, $fieldName, '');

						$diffReturnArray[] = array(
							'field' => $fieldName,
							'label' => $fieldTitle,
							'content' => $versionThumb
						);
						$liveReturnArray[] = array(
							'field' => $fieldName,
							'label' => $fieldTitle,
							'content' => $liveThumb
						);
					} else {
						$diffReturnArray[] = array(
							'field' => $fieldName,
							'label' => $fieldTitle,
							'content' => $t3lib_diff->makeDiffDisplay($liveRecord[$fieldName], $versionRecord[$fieldName]) // call diff class to get diff
						);
						$liveReturnArray[] = array(
							'field' => $fieldName,
							'label' => $fieldTitle,
							'content' => $parseObj->TS_images_rte($liveRecord[$fieldName])
						);
					}
				}
			}
		}
			// Hook for modifying the difference and live arrays
			// (this may be used by custom or dynamically-defined fields)
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['modifyDifferenceArray'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['modifyDifferenceArray'] as $className) {
				$hookObject = &t3lib_div::getUserObj($className);
				$hookObject->modifyDifferenceArray($parameter, $diffReturnArray, $liveReturnArray, $t3lib_diff);
			}
		}

		$commentsForRecord = $this->getCommentsForRecord($parameter->uid, $parameter->table);

		return array(
			'total' => 1,
			'data' => array(
				array(
					'diff' => $diffReturnArray,
					'live_record' => $liveReturnArray,
					'path_Live' => $parameter->path_Live,
					'label_Stage' => $parameter->label_Stage,
					'stage_position' => $stagePosition['position'],
					'stage_count' => $stagePosition['count'],
					'comments' => $commentsForRecord,
					'icon_Live' => $icon_Live,
					'icon_Workspace' => $icon_Workspace
				)
			)
		);
	}

	/**
	 * Gets an array with all sys_log entries and their comments for the given record uid and table
	 *
	 * @param integer $uid uid of changed element to search for in log
	 * @param string $table Name of the record's table
	 * @return array
	 */
	public function getCommentsForRecord($uid, $table) {
		$sysLogReturnArray = array();

		$sysLogRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'log_data,tstamp,userid',
				'sys_log',
				'action=6 and details_nr=30
				AND tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table, 'sys_log') . '
				AND recuid=' . intval($uid),
				'',
				'tstamp DESC'
		);

		foreach ($sysLogRows as $sysLogRow) {
			$sysLogEntry = array();
			$data = unserialize($sysLogRow['log_data']);
			$beUserRecord = t3lib_BEfunc::getRecord('be_users', $sysLogRow['userid']);

			$sysLogEntry['stage_title'] = $this->getStagesService()->getStageTitle($data['stage']);
			$sysLogEntry['user_uid'] = $sysLogRow['userid'];
			$sysLogEntry['user_username'] = is_array($beUserRecord) ? $beUserRecord['username'] : '';
			$sysLogEntry['tstamp'] = t3lib_BEfunc::datetime($sysLogRow['tstamp']);
			$sysLogEntry['user_comment'] = $data['comment'];

			$sysLogReturnArray[] = $sysLogEntry;
		}

		return $sysLogReturnArray;
	}

	/**
	 * Gets all available system languages.
	 *
	 * @return array
	 */
	public function getSystemLanguages() {
		$systemLanguages = array(
			array(
				'uid' => 'all',
				'title' => Tx_Extbase_Utility_Localization::translate('language.allLanguages', 'workspaces'),
				'cls' => t3lib_iconWorks::getSpriteIconClasses('empty-empty'),
			)
		);

		foreach ($this->getGridDataService()->getSystemLanguages() as $id => $systemLanguage) {
			if ($id < 0) {
				continue;
			}

			$systemLanguages[] = array(
				'uid' => $id,
				'title' => htmlspecialchars($systemLanguage['title']),
				'cls' => t3lib_iconWorks::getSpriteIconClasses($systemLanguage['flagIcon']),
			);
		}

		$result = array(
			'total' => count($systemLanguages),
			'data' => $systemLanguages,
		);

		return $result;
	}

	/**
	 * Gets the Grid Data Service.
	 *
	 * @return Tx_Workspaces_Service_GridData
	 */
	protected function getGridDataService() {
		if (!isset($this->gridDataService)) {
			$this->gridDataService = t3lib_div::makeInstance('Tx_Workspaces_Service_GridData');
		}
		return $this->gridDataService;
	}

	/**
	 * Gets the Stages Service.
	 *
	 * @return Tx_Workspaces_Service_Stages
	 */
	protected function getStagesService() {
		if (!isset($this->stagesService)) {
			$this->stagesService = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
		}
		return $this->stagesService;
	}
}
?>