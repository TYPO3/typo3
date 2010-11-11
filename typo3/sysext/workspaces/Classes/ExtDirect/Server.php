<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Steffen Kamper (steffen@typo3.org)
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

class tx_Workspaces_ExtDirect_Server extends tx_Workspaces_ExtDirect_AbstractHandler {

	/**
	 * Get List of workspace changes
	 *
	 * @param object $parameter
	 * @return array $data
	 */
	public function getWorkspaceInfos($parameter) {

			// To avoid too much work we use -1 to indicate that every page is relevant
		$pageId = $parameter->id > 0 ? $parameter->id : -1;

		$wslibObj = t3lib_div::makeInstance('tx_Workspaces_Service_Workspaces');
		$versions = $wslibObj->selectVersionsInWorkspace($this->getCurrentWorkspace(), 0, -99, $pageId, $parameter->depth);

		$workspacesService = t3lib_div::makeInstance('tx_Workspaces_Service_GridData');
		$data = $workspacesService->generateGridListFromVersions($versions, $parameter);
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
		if ($currentWorkspace != tx_Workspaces_Service_Workspaces::SELECT_ALL_WORKSPACES) {
			$stagesService = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
			$data = $stagesService->getStagesForWSUser();
		} else {
			$data = array(
				'total' => 0,
				'data' => array()
			);
		}
		return $data;
	}

	/**
	 * Fetch futher information to current selected worspace record.
	 *
	 * @param object $parameter
	 * @return array $data
	 *
	 * @author Michael Klapper <michael.klapper@aoemedia.de>
	 * @author Sonja Scholz <ss@cabag.ch>
	 */
	public function getRowDetails($parameter) {
		global $TCA;
		$diffReturnOutput = '';
		$liveRecordOutput = '';

		$t3lib_diff = t3lib_div::makeInstance('t3lib_diff');
		$stagesService = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');

		$liveRecord = t3lib_BEfunc::getRecord($parameter->table, $parameter->t3ver_oid);
		$versionRecord = t3lib_BEfunc::getRecord($parameter->table, $parameter->uid);

		$stagePosition = $stagesService->getPositionOfCurrentStage($parameter->stage);

		$fieldsOfRecords = array_keys($liveRecord);
			// get field list from TCA configuration, if available
		if ($TCA[$parameter->table]) {
			t3lib_div::loadTCA($parameter->table);
			if ($TCA[$parameter->table]['interface']['showRecordFieldList']) {
				$fieldsOfRecords = $TCA[$parameter->table]['interface']['showRecordFieldList'];
				$fieldsOfRecords = t3lib_div::trimExplode(',',$fieldsOfRecords,1);
			}
		}

		foreach ($fieldsOfRecords as $fieldName) {
				// call diff class only if there is a difference
			if (strcmp($liveRecord[$fieldName],$versionRecord[$fieldName]) !== 0) {
					// Select the human readable values before diff
				$liveRecord[$fieldName] = t3lib_BEfunc::getProcessedValue($parameter->table,$fieldName,$liveRecord[$fieldName]);
				$versionRecord[$fieldName] = t3lib_BEfunc::getProcessedValue($parameter->table,$fieldName,$versionRecord[$fieldName]);
					// call diff class to get diff
				$diffReturnOutput .= $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($parameter->table, $fieldName)).''.$t3lib_diff->makeDiffDisplay($liveRecord[$fieldName],$versionRecord[$fieldName]).'<br />';
				$liveRecordOutput .= $GLOBALS['LANG']->sL(t3lib_BEfunc::getItemLabel($parameter->table, $fieldName)).''.$liveRecord[$fieldName].'<br />';
			}
		}

		$commentsForRecord = $this->getCommentsForRecord($parameter->uid, $parameter->table);

		return array(
			'total' => 1,
			'data' => array(
				array(
					'diff' => $diffReturnOutput,
					'live_record' => $liveRecordOutput,
					'path_Live' => $parameter->path_Live,
					'label_Stage' => $parameter->label_Stage,
					'stage_position' => $stagePosition['position'],
					'stage_count' => $stagePosition['count'],
					'comments' => $commentsForRecord
				)
			)
		);
	}

	/**
	 * Gets an array with all sys_log entries and their comments for the given record uid and table
	 *
	 * @param integer $uid uid of changed element to search for in log
	 * @return string $table table name
	 *
	 * @author Sonja Scholz <ss@cabag.ch>
	 */
	public function getCommentsForRecord($uid, $table) {
		$stagesService = t3lib_div::makeInstance('Tx_Workspaces_Service_Stages');
		$sysLogReturnArray = array();

		$sysLogRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'log_data,tstamp,userid',
				'sys_log',
				'action=6 and details_nr=30
				AND tablename='.$GLOBALS['TYPO3_DB']->fullQuoteStr($table,'sys_log').'
				AND recuid='.intval($uid),
				'',
				'tstamp DESC'
		);

		foreach($sysLogRows as $sysLogRow)	{
			$sysLogEntry = array();
			$data = unserialize($sysLogRow['log_data']);
			$beUserRecord = t3lib_BEfunc::getRecord('be_users', $sysLogRow['userid']);

			$sysLogEntry['stage_title'] = $stagesService->getStageTitle($data['stage']);
			$sysLogEntry['user_uid'] = $sysLogRow['userid'];
			$sysLogEntry['user_username'] = is_array($beUserRecord) ? $beUserRecord['username'] : '';
			$sysLogEntry['tstamp'] = t3lib_BEfunc::datetime($sysLogRow['tstamp']);
			$sysLogEntry['user_comment'] = $data['comment'];

			$sysLogReturnArray[] = $sysLogEntry;
		}

		return $sysLogReturnArray;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/Server.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/ExtDirect/Server.php']);
}