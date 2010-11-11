<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Sonja Scholz  (ss@cabag.ch)
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
 * Library with Workspace related functionality
 *
 * @author	Sonja Scholz <ss@cabag.ch>
 */

class Tx_Workspaces_Service_Stages {
	const TABLE_STAGE = 'sys_workspace_stage';
		// if a record is in the "ready to publish" stage STAGE_PUBLISH_ID the nextStage is STAGE_PUBLISH_EXECUTE_ID, this id wont be saved at any time in db
	const STAGE_PUBLISH_EXECUTE_ID = -20;
		// ready to publish stage
	const STAGE_PUBLISH_ID = -10;
	const STAGE_EDIT_ID = 0;

	/** Current workspace id */
	private $workspaceId = NULL;

	/** path to locallang file */
	private $pathToLocallang = 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xml';

		// local caches to avoid that workspace stages, groups etc need to be read from the DB every time
	protected $workspaceStageCache = array();
	protected $workspaceStageAllowedCache = array();
	protected $fetchGroupsCache = array();
	/**
	 * Getter for current workspace id
	 *
	 * @return int current workspace id
	 */
	public function getWorkspaceId() {
		if ($this->workspaceId == NULL) {
			$this->setWorkspaceId($GLOBALS['BE_USER']->workspace);
		}
		return $this->workspaceId;
	}

	/**
	 * Setter for current workspace id
	 *
	 * @param int current workspace id
	 */
	private function setWorkspaceId($wsid) {
		$this->workspaceId = $wsid;
	}

	/**
	 * constructor for workspace library
	 *
	 * @param int current workspace id
	 */
	public function __construct() {
		$this->setWorkspaceId($GLOBALS['BE_USER']->workspace);
	}

	/**
	 * Building an array with all stage ids and titles related to the given workspace
	 *
	 * @return array id and title of the stages
	 */
	public function getStagesForWS() {

		$stages = array();

		if (isset($this->workspaceStageCache[$this->getWorkspaceId()])) {
			$stages = $this->workspaceStageCache[$this->getWorkspaceId()];
		} else {
			$stages[] = array(
				'uid' => self::STAGE_EDIT_ID,
				'title' => $GLOBALS['LANG']->sL($this->pathToLocallang . ':actionSendToStage') . ' "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_user_ws.xml:stage_editing') . '"'
			);

			$workspaceRec = t3lib_BEfunc::getRecord('sys_workspace', $this->getWorkspaceId());
			if ($workspaceRec['custom_stages'] > 0) {
					// Get all stage records for this workspace
				$workspaceStageRecs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					self::TABLE_STAGE,
						'parentid=' . $this->getWorkspaceId() . ' AND parenttable="sys_workspace" AND deleted = 0',
					'',
					'sorting',
					'',
					'uid'
				);
				foreach($workspaceStageRecs as $stage) {
					$stages[] = $stage;
				}
			}

			$stages[] = array(
				'uid' => self::STAGE_PUBLISH_ID,
				'title' => $GLOBALS['LANG']->sL($this->pathToLocallang . ':actionSendToStage') . ' "' . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xml:stage_ready_to_publish') . '"'
			);

			$stages[] = array(
				'uid' => self::STAGE_PUBLISH_EXECUTE_ID,
				'title' => $GLOBALS['LANG']->sL($this->pathToLocallang . ':publish_execute_action_option')
			);
			$this->workspaceStageCache[$this->getWorkspaceId()] = $stages;
		}

		return $stages;
	}

	/**
	 * Returns an array of stages, the user is allowed to send to
	 * @return array id and title of stages
	 */
	public function getStagesForWSUser() {

			// initiate return array of stages with edit stage
		$stagesForWSUserData = array();

			// get all stages for the current workspace
		$workspaceStageRecs = $this->getStagesForWS();
		if (is_array($workspaceStageRecs) && !empty($workspaceStageRecs)) {
				// go through custom stages records
			foreach ($workspaceStageRecs as $workspaceStageRec) {
					// check if the user has permissions to the custom stage
				if ($GLOBALS['BE_USER']->workspaceCheckStageForCurrent($this->encodeStageUid($workspaceStageRec['uid']))) {
						// yes, so add to return array
					$stagesForWSUserData[] = array(
						'uid' => $this->encodeStageUid($workspaceStageRec['uid']),
						'title' => $GLOBALS['LANG']->sL($this->pathToLocallang . ':actionSendToStage') . ' "' . $workspaceStageRec['title'] . '"');
				} else if ($workspaceStageRec['uid'] == self::STAGE_PUBLISH_EXECUTE_ID) {
						if ($GLOBALS['BE_USER']->workspacePublishAccess($this->getWorkspaceId())) {
							$stagesForWSUserData[] = $workspaceStageRec;
						}
				}
			}
		}
		return $stagesForWSUserData;
	}

	/**
	 * Check if given workspace has custom staging activated
	 *
	 * @return bool true or false
	 */
	public function checkCustomStagingForWS() {
		$workspaceRec = t3lib_BEfunc::getRecord('sys_workspace', $this->getWorkspaceId());
		return $workspaceRec['custom_stages'] > 0;
	}

	/**
	 * converts from versioning stage id to stage record UID
	 *
	 * @return int UID of the database record
	 */
	public function resolveStageUid($ver_stage) {
		return $ver_stage;
		//return $ver_stage - $this->raiseStageIdAmount;
	}

	/**
	 * converts from stage record UID to versioning stage id
	 *
	 * @param int UID of the stage database record
	 * @return int versioning stage id
	 */
	public function encodeStageUid($stage_uid) {
		return $stage_uid;
		//return $stage_uid + $this->raiseStageIdAmount;
	}

	public function getStageTitle($ver_stage) {
		global $LANG;
		$stageTitle = '';

		switch ($ver_stage) {
			case self::STAGE_PUBLISH_EXECUTE_ID:
				$stageTitle = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_user_ws.xml:stage_publish');
				break;
			case self::STAGE_PUBLISH_ID:
				$stageTitle = $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xml:stage_ready_to_publish');
				break;
			case self::STAGE_EDIT_ID:
				$stageTitle = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_user_ws.xml:stage_editing');
				break;
			default:
				$stageTitle = $this->getPropertyOfCurrentWorkspaceStage($ver_stage, 'title');

				if ($stageTitle == null) {
					$stageTitle = $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.getStageTitle.stageNotFound');
				}
				break;
		}

		return $stageTitle;
	}

	/**
	 * @param  $stageid
	 * @return array
	 */
	public function getStageRecord($stageid) {
		return t3lib_BEfunc::getRecord('sys_workspace_stage', $this->resolveStageUid($stageid));
	}

	/**
	 * Get next stage in process for given stage id
	 *
	 * @param int			stageid
	 * @return int			id
	 */
	public function getNextStage($stageid) {

		if (!t3lib_div::testInt($stageid)) {
			throw new InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.stageId.integer'));
		}

		$nextStage = FALSE;

		$workspaceStageRecs = $this->getStagesForWS();
		if (is_array($workspaceStageRecs) && !empty($workspaceStageRecs)) {
			reset($workspaceStageRecs);
			while (!is_null($workspaceStageRecKey = key($workspaceStageRecs))) {
				$workspaceStageRec = current($workspaceStageRecs);
				if ($workspaceStageRec['uid'] == $this->resolveStageUid($stageid)) {
					$nextStage = next($workspaceStageRecs);
					break;
				}
				next($workspaceStageRecs);
			}
		} else {
			// @todo consider to throw an exception here
		}

		if ($nextStage === FALSE) {
			$nextStage[] = array(
				'uid' => self::STAGE_EDIT_ID,
				'title' => $GLOBALS['LANG']->sL($this->pathToLocallang . ':actionSendToStage') . ' "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_user_ws.xml:stage_editing') . '"'
			);
		}

		return $nextStage;
	}

	/**
	 * Recursive function to get all next stages for a record depending on user permissions
	 *
	 * @param	array	next stages
	 * @param	int		stage id
	 * @param	int		current stage id of the record
	 * @return	array	next stages
	 */
	public function getNextStages(array &$nextStageArray, $stageId) {
			// Current stage is "Ready to publish" - there is no next stage
		if ($stageId == self::STAGE_PUBLISH_ID) {
			return $nextStageArray;
		} else {
			$nextStageRecord = $this->getNextStage($stageId);
			if (empty($nextStageRecord) || !is_array($nextStageRecord)) {
					// There is no next stage
				return $nextStageArray;
			} else {
					// Check if the user has the permission to for the current stage
					// If this next stage record is the first next stage after the current the user
					// has always the needed permission
				if ($this->isStageAllowedForUser($stageId)) {
					$nextStageArray[] = $nextStageRecord;
					return $this->getNextStages($nextStageArray, $nextStageRecord['uid']);
				} else {
						// He hasn't - return given next stage array
					return $nextStageArray;
				}
			}
		}
	}

	/**
	 * Get next stage in process for given stage id
	 *
	 * @param int			workspace id
	 * @param int			stageid
	 * @return int			id
	 */
	public function getPrevStage($stageid) {

		if (!t3lib_div::testInt($stageid)) {
			throw new InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.stageId.integer'));
		}

		$prevStage = FALSE;
		$workspaceStageRecs = $this->getStagesForWS();
		if (is_array($workspaceStageRecs) && !empty($workspaceStageRecs)) {
			end($workspaceStageRecs);
			while (!is_null($workspaceStageRecKey = key($workspaceStageRecs))) {
				$workspaceStageRec = current($workspaceStageRecs);
				if ($workspaceStageRec['uid'] == $this->resolveStageUid($stageid)) {
					$prevStage = prev($workspaceStageRecs);
					break;
				}
				prev($workspaceStageRecs);
			}
		} else {
			// @todo consider to throw an exception here
		}
		return $prevStage;
	}

	/**
	 * Recursive function to get all prev stages for a record depending on user permissions
	 *
	 * @param	array	prev stages
	 * @param	int		workspace id
	 * @param	int		stage id
	 * @param	int		current stage id of the record
	 * @return	array	prev stages
	 */
	public function getPrevStages(array &$prevStageArray, $stageId) {
			// Current stage is "Editing" - there is no prev stage
		if ($stageId != self::STAGE_EDIT_ID) {
			$prevStageRecord = $this->getPrevStage($stageId);

			if (!empty($prevStageRecord) && is_array($prevStageRecord)) {
					// Check if the user has the permission to switch to that stage
					// If this prev stage record is the first previous stage before the current
					// the user has always the needed permission
				if ($this->isStageAllowedForUser($stageId)) {
					$prevStageArray[] = $prevStageRecord;
					$prevStageArray = $this->getPrevStages($prevStageArray, $prevStageRecord['uid']);

				}
			}
		}
		return $prevStageArray;
	}

	/**
	 * Get array of all responsilbe be_users for a stage
	 *
	 * @param	int	stage id
	 * @return	array be_users with e-mail and name
	 */
	public function getResponsibleBeUser($stageId) {
		$workspaceRec = t3lib_BEfunc::getRecord('sys_workspace', $this->getWorkspaceId());
		$recipientArray = array();

		switch ($stageId) {
			case self::STAGE_PUBLISH_EXECUTE_ID:
			case self::STAGE_PUBLISH_ID:
				$userList = $this->getResponsibleUser($workspaceRec['adminusers']);
				break;
			case self::STAGE_EDIT_ID:
				$userList = $this->getResponsibleUser($workspaceRec['members']);
				break;
			default:
				$responsible_persons = $this->getPropertyOfCurrentWorkspaceStage($stageId, 'responsible_persons');
				$userList = $this->getResponsibleUser($responsible_persons);
				break;
		}

		$userRecords = t3lib_BEfunc::getUserNames('username, uid, email, realName',
				'AND uid IN (' . $userList . ')');

		if (!empty($userRecords) && is_array($userRecords)) {
			foreach ($userRecords as $userUid => $userRecord) {
				$recipientArray[$userUid] = $userRecord['email'] . ' ( ' . $userRecord['realName'] . ' )';
			}
		}
		return $recipientArray;
	}

	/**
	 * Get uids of all responsilbe persons for a stage
	 *
	 * @param	string	responsible_persion value from stage record
	 * @return	string	uid list of responsible be_users
	 */
	public function getResponsibleUser($stageRespValue) {
		$stageValuesArray = array();
		$stageValuesArray = t3lib_div::trimExplode(',', $stageRespValue);

		$beuserUidArray = array();
		$begroupUidArray = array();
		$allBeUserArray = array();
		$begroupUidList = array();

		foreach ($stageValuesArray as $key => $uidvalue) {
			if (strstr($uidvalue, 'be_users') !== FALSE) { // Current value is a uid of a be_user record
				$beuserUidArray[] = str_replace('be_users_', '', $uidvalue);
			} elseif (strstr($uidvalue, 'be_groups') !== FALSE) {
				$begroupUidArray[] = str_replace('be_groups_', '', $uidvalue);
			} else {
				$beuserUidArray[] = $uidvalue;
			}
		}
		if (!empty($begroupUidArray)) {
			$allBeUserArray = t3lib_befunc::getUserNames();

			$begroupUidList = implode(',', $begroupUidArray);

			$this->userGroups = array();
			$begroupUidArray = $this->fetchGroups($begroupUidList);

			foreach ($begroupUidArray as $groupkey => $groupData) {
				foreach ($allBeUserArray as $useruid => $userdata) {
					if (t3lib_div::inList($userdata['usergroup'], $groupData['uid'])) {
						$beuserUidArray[] = $useruid;
					}
				}
			}
		}

		array_unique($beuserUidArray);
		return implode(',', $beuserUidArray);
	}


	/**
	 * @param  $grList
	 * @param string $idList
	 * @return array
	 */
	private function fetchGroups($grList, $idList = '') {

		$cacheKey = md5($grList . $idList);
		$groupList = array();
		if (isset($this->fetchGroupsCache[$cacheKey])) {
			$groupList = $this->fetchGroupsCache[$cacheKey];
		} else {
			if ($idList === '') {
					// we're at the beginning of the recursion and therefore we need to reset the userGroups member
				$this->userGroups = array();
			}
			$groupList = $this->fetchGroupsRecursive($grList);
			$this->fetchGroupsCache[$cacheKey] = $groupList;
		}
		return $groupList;
	}

	/**
	 * @param  array 	$groups
	 * @return void
	 */
	private function fetchGroupsFromDB(array $groups) {
		$whereSQL = 'deleted=0 AND hidden=0 AND pid=0 AND uid IN (' . implode(',', $groups) . ') ';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'be_groups', $whereSQL);

			// The userGroups array is filled
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$this->userGroups[$row['uid']] = $row;
		}
	}

	/**
	 * @param  $grList
	 * @param string $idList
	 * @return array
	 */
	private function fetchGroupsRecursive($grList, $idList = '') {

		$requiredGroups = t3lib_div::intExplode(',', $grList, TRUE);
		$existingGroups = array_keys($this->userGroups);
		$missingGroups = array_diff($include_staticArr, $existingGroups);
		if (count($groups) > 0) {
			$this->fetchGroupsFromDB($missingGroups);
		}

			// Traversing records in the correct order
		foreach ($requiredGroups as $uid) { // traversing list
				// Get row:
			$row = $this->userGroups[$uid];
			if (is_array($row) && !t3lib_div::inList($idList, $uid)) { // Must be an array and $uid should not be in the idList, because then it is somewhere previously in the grouplist
					// If the localconf.php option isset the user of the sub- sub- groups will also be used
				if ($GLOBALS['TYPO3_CONF_VARS']['BE']['customStageShowRecipientRecursive'] == 1) {
						// Include sub groups
					if (trim($row['subgroup'])) {
							// Make integer list
						$theList = implode(',', t3lib_div::intExplode(',', $row['subgroup']));
							// Get the subarray
						$subbarray = $this->fetchGroups($theList, $idList . ',' . $uid);
						list($subUid, $subArray) = each($subbarray);
							// Merge the subarray to the already existing userGroups array
						$this->userGroups[$subUid] = $subArray;
					}
				}
			}
		}
		return $this->userGroups;
	}

	/**
	 * Gets a property of a workspaces stage.
	 *
	 * @param integer $stageId
	 * @param string $property
	 * @return string
	 */
	public function getPropertyOfCurrentWorkspaceStage($stageId, $property) {
		$result = NULL;

		if (!t3lib_div::testInt($stageId)) {
			throw new InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.stageId.integer'));
		}

		if ($stageId != self::STAGE_PUBLISH_ID && $stageId != self::STAGE_EDIT_ID) {
			$stageId = $this->resolveStageUid($stageId);
		}

		$workspaceStage = t3lib_BEfunc::getRecord(self::TABLE_STAGE, $stageId);

		if (is_array($workspaceStage) && isset($workspaceStage[$property])) {
			$result = $workspaceStage[$property];
		}

		return $result;
	}

	/**
	 * Gets the position of the given workspace in the hole process f.e. 3 means step 3 of 20, by which 1 is edit and 20 is ready to publish
	 *
	 * @param integer $stageId
	 * @return array position => 3, count => 20
	 */
	public function getPositionOfCurrentStage($stageId) {
		$stagesOfWS = $this->getStagesForWS();
		$countOfStages = count($stagesOfWS);

		switch ($stageId) {
			case self::STAGE_PUBLISH_ID:
					$position = $countOfStages;
				break;
			case self::STAGE_EDIT_ID:
					$position = 1;
				break;
			default:
				$position = 1;
				foreach ($stagesOfWS as $key => $stageInfoArray) {
					$position++;
					if ($stageId == $stageInfoArray['uid']) {
						break;
					}
				}
				break;
		}
		return array('position' => $position, 'count' => $countOfStages);
	}

	/**
	 * Check if the user has access to the previous stage, relative to the given stage
	 *
	 * @param  integer $stageId
	 * @return bool
	 */
	public function isPrevStageAllowedForUser($stageId) {
		$isAllowed = FALSE;
		try {
			$prevStage = $this->getPrevStage($stageId);
				// if there's no prev-stage the stageIds match,
				// otherwise we've to check if the user is permitted to use the stage
			if (!empty($prevStage) && $prevStage['uid'] != $stageId) {
				$isAllowed = $this->isStageAllowedForUser($prevStage['uid']);
			}
		} catch (Exception $e) {
			// Exception raised - we're not allowed to go this way
		}

		return $isAllowed;
	}

	/**
	 * Check if the user has access to the next stage, relative to the given stage
	 *
	 * @param  integer $stageId
	 * @return bool
	 */
	public function isNextStageAllowedForUser($stageId) {
		$isAllowed = FALSE;
		try {
			$nextStage = $this->getNextStage($stageId);
				// if there's no next-stage the stageIds match,
				// otherwise we've to check if the user is permitted to use the stage
			if (!empty($nextStage) && $nextStage['uid'] != $stageId) {
				$isAllowed = $this->isStageAllowedForUser($nextStage['uid']);
			}
		} catch (Exception $e) {
			// Exception raised - we're not allowed to go this way
		}

		return $isAllowed;
	}

	/**
	 * @param  $stageId
	 * @return bool
	 */
	protected function isStageAllowedForUser($stageId) {
		$cacheKey = $this->getWorkspaceId() . '_' . $stageId;
		$isAllowed = FALSE;
 		if (isset($this->workspaceStageAllowedCache[$cacheKey])) {
			 $isAllowed = $this->workspaceStageAllowedCache[$cacheKey];
		 } else {
			 $isAllowed = $GLOBALS['BE_USER']->workspaceCheckStageForCurrent($stageId);
			 $this->workspaceStageAllowedCache[$cacheKey] = $isAllowed;
		 }
		return $isAllowed;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Stages.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Service/Stages.php']);
}
?>