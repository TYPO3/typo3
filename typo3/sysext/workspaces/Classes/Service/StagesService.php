<?php
namespace TYPO3\CMS\Workspaces\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * Stages service
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class StagesService {

	const TABLE_STAGE = 'sys_workspace_stage';
	// if a record is in the "ready to publish" stage STAGE_PUBLISH_ID the nextStage is STAGE_PUBLISH_EXECUTE_ID, this id wont be saved at any time in db
	const STAGE_PUBLISH_EXECUTE_ID = -20;
	// ready to publish stage
	const STAGE_PUBLISH_ID = -10;
	const STAGE_EDIT_ID = 0;
	const MODE_NOTIFY_SOMEONE = 0;
	const MODE_NOTIFY_ALL = 1;
	const MODE_NOTIFY_ALL_STRICT = 2;
	/**
	 * Current workspace ID
	 *
	 * @var integer
	 */
	private $workspaceId = NULL;

	/**
	 * Path to the locallang file
	 *
	 * @var string
	 */
	private $pathToLocallang = 'LLL:EXT:workspaces/Resources/Private/Language/locallang.xml';

	/**
	 * Local cache to reduce number of database queries for stages, groups, etc.
	 *
	 * @var array
	 */
	protected $workspaceStageCache = array();

	/**
	 * @var array
	 */
	protected $workspaceStageAllowedCache = array();

	/**
	 * @var array
	 */
	protected $fetchGroupsCache = array();

	/**
	 * @var array
	 */
	protected $userGroups = array();

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
	 */
	public function __construct() {
		$this->setWorkspaceId($GLOBALS['BE_USER']->workspace);
	}

	/**
	 * Find the highest possible "previous" stage for all $byTableName
	 *
	 * @param array $workspaceItems
	 * @param array $byTableName
	 * @return array Current and next highest possible stage
	 * @author Michael Klapper <development@morphodo.com>
	 */
	public function getPreviousStageForElementCollection(
		$workspaceItems,
		array $byTableName = array('tt_content', 'pages', 'pages_language_overlay')
	) {
		$currentStage = array();
		$previousStage = array();
		$usedStages = array();
		$found = FALSE;
		$availableStagesForWS = array_reverse($this->getStagesForWS());
		$availableStagesForWSUser = $this->getStagesForWSUser();
		$byTableName = array_flip($byTableName);
		foreach ($workspaceItems as $tableName => $items) {
			if (!array_key_exists($tableName, $byTableName)) {
				continue;
			}
			foreach ($items as $item) {
				$usedStages[$item['t3ver_stage']] = TRUE;
			}
		}
		foreach ($availableStagesForWS as $stage) {
			if (isset($usedStages[$stage['uid']])) {
				$currentStage = $stage;
				$previousStage = $this->getPrevStage($stage['uid']);
				break;
			}
		}
		foreach ($availableStagesForWSUser as $userWS) {
			if ($previousStage['uid'] == $userWS['uid']) {
				$found = TRUE;
				break;
			}
		}
		if ($found === FALSE) {
			$previousStage = array();
		}
		return array(
			$currentStage,
			$previousStage
		);
	}

	/**
	 * Retrieve the next stage based on the lowest stage given in the $workspaceItems record array.
	 *
	 * @param array $workspaceItems
	 * @param array $byTableName
	 * @return array Current and next possible stage.
	 * @author Michael Klapper <development@morphodo.com>
	 */
	public function getNextStageForElementCollection(
		$workspaceItems,
		array $byTableName = array('tt_content', 'pages', 'pages_language_overlay')
	) {
		$currentStage = array();
		$usedStages = array();
		$nextStage = array();
		$availableStagesForWS = $this->getStagesForWS();
		$availableStagesForWSUser = $this->getStagesForWSUser();
		$byTableName = array_flip($byTableName);
		$found = FALSE;
		foreach ($workspaceItems as $tableName => $items) {
			if (!array_key_exists($tableName, $byTableName)) {
				continue;
			}
			foreach ($items as $item) {
				$usedStages[$item['t3ver_stage']] = TRUE;
			}
		}
		foreach ($availableStagesForWS as $stage) {
			if (isset($usedStages[$stage['uid']])) {
				$currentStage = $stage;
				$nextStage = $this->getNextStage($stage['uid']);
				break;
			}
		}
		foreach ($availableStagesForWSUser as $userWS) {
			if ($nextStage['uid'] == $userWS['uid']) {
				$found = TRUE;
				break;
			}
		}
		if ($found === FALSE) {
			$nextStage = array();
		}
		return array(
			$currentStage,
			$nextStage
		);
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
				'title' => $GLOBALS['LANG']->sL(($this->pathToLocallang . ':actionSendToStage')) . ' "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_user_ws.xml:stage_editing') . '"'
			);
			$workspaceRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $this->getWorkspaceId());
			if ($workspaceRec['custom_stages'] > 0) {
				// Get all stage records for this workspace
				$workspaceStageRecs = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', self::TABLE_STAGE, 'parentid=' . $this->getWorkspaceId() . ' AND parenttable=' . $GLOBALS['TYPO3_DB']->fullQuoteStr('sys_workspace', self::TABLE_STAGE) . ' AND deleted=0', '', 'sorting', '', 'uid');
				foreach ($workspaceStageRecs as $stage) {
					$stage['title'] = $GLOBALS['LANG']->sL(($this->pathToLocallang . ':actionSendToStage')) . ' "' . $stage['title'] . '"';
					$stages[] = $stage;
				}
			}
			$stages[] = array(
				'uid' => self::STAGE_PUBLISH_ID,
				'title' => $GLOBALS['LANG']->sL(($this->pathToLocallang . ':actionSendToStage')) . ' "' . $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xml:stage_ready_to_publish') . '"'
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
	 *
	 * @return array id and title of stages
	 */
	public function getStagesForWSUser() {
		$stagesForWSUserData = array();
		$allowedStages = array();
		$orderedAllowedStages = array();
		$workspaceStageRecs = $this->getStagesForWS();
		if (is_array($workspaceStageRecs) && !empty($workspaceStageRecs)) {
			if ($GLOBALS['BE_USER']->isAdmin()) {
				$orderedAllowedStages = $workspaceStageRecs;
			} else {
				foreach ($workspaceStageRecs as $workspaceStageRec) {
					if ($workspaceStageRec['uid'] === self::STAGE_EDIT_ID) {
						$allowedStages[self::STAGE_EDIT_ID] = $workspaceStageRec;
						$stagesForWSUserData[$workspaceStageRec['uid']] = $workspaceStageRec;
					} elseif ($this->isStageAllowedForUser($workspaceStageRec['uid'])) {
						$stagesForWSUserData[$workspaceStageRec['uid']] = $workspaceStageRec;
					} elseif ($workspaceStageRec['uid'] == self::STAGE_PUBLISH_EXECUTE_ID && $GLOBALS['BE_USER']->workspacePublishAccess($this->getWorkspaceId())) {
						$allowedStages[] = $workspaceStageRec;
						$stagesForWSUserData[$workspaceStageRec['uid']] = $workspaceStageRec;
					}
				}
				foreach ($stagesForWSUserData as $allowedStage) {
					$nextStage = $this->getNextStage($allowedStage['uid']);
					$prevStage = $this->getPrevStage($allowedStage['uid']);
					if (isset($nextStage['uid'])) {
						$allowedStages[$nextStage['uid']] = $nextStage;
					}
					if (isset($prevStage['uid'])) {
						$allowedStages[$prevStage['uid']] = $prevStage;
					}
				}
				$orderedAllowedStages = array_values($allowedStages);
			}
		}
		return $orderedAllowedStages;
	}

	/**
	 * Check if given workspace has custom staging activated
	 *
	 * @return bool TRUE or FALSE
	 */
	public function checkCustomStagingForWS() {
		$workspaceRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $this->getWorkspaceId());
		return $workspaceRec['custom_stages'] > 0;
	}

	/**
	 * Gets the title of a stage.
	 *
	 * @param integer $ver_stage
	 * @return string
	 */
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
			if ($stageTitle == NULL) {
				$stageTitle = $GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.getStageTitle.stageNotFound');
			}
			break;
		}
		return $stageTitle;
	}

	/**
	 * Gets a particular stage record.
	 *
	 * @param integer $stageid
	 * @return array
	 */
	public function getStageRecord($stageid) {
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace_stage', $stageid);
	}

	/**
	 * Gets next stage in process for given stage id
	 *
	 * @param integer $stageid Id of the stage to fetch the next one for
	 * @return integer The next stage Id
	 */
	public function getNextStage($stageId) {
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($stageId)) {
			throw new \InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.stageId.integer'), 1291109987);
		}
		$nextStage = FALSE;
		$workspaceStageRecs = $this->getStagesForWS();
		if (is_array($workspaceStageRecs) && !empty($workspaceStageRecs)) {
			reset($workspaceStageRecs);
			while (!is_null(($workspaceStageRecKey = key($workspaceStageRecs)))) {
				$workspaceStageRec = current($workspaceStageRecs);
				if ($workspaceStageRec['uid'] == $stageId) {
					$nextStage = next($workspaceStageRecs);
					break;
				}
				next($workspaceStageRecs);
			}
		} else {

		}
		if ($nextStage === FALSE) {
			$nextStage[] = array(
				'uid' => self::STAGE_EDIT_ID,
				'title' => $GLOBALS['LANG']->sL(($this->pathToLocallang . ':actionSendToStage')) . ' "' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_user_ws.xml:stage_editing') . '"'
			);
		}
		return $nextStage;
	}

	/**
	 * Recursive function to get all next stages for a record depending on user permissions
	 *
	 * @param 	array	next stages
	 * @param 	int		current stage id of the record
	 * @return 	array	next stages
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
	 * @param int			stageid
	 * @return int			id
	 */
	public function getPrevStage($stageid) {
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($stageid)) {
			throw new \InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.stageId.integer'));
		}
		$prevStage = FALSE;
		$workspaceStageRecs = $this->getStagesForWS();
		if (is_array($workspaceStageRecs) && !empty($workspaceStageRecs)) {
			end($workspaceStageRecs);
			while (!is_null(($workspaceStageRecKey = key($workspaceStageRecs)))) {
				$workspaceStageRec = current($workspaceStageRecs);
				if ($workspaceStageRec['uid'] == $stageid) {
					$prevStage = prev($workspaceStageRecs);
					break;
				}
				prev($workspaceStageRecs);
			}
		} else {

		}
		return $prevStage;
	}

	/**
	 * Recursive function to get all prev stages for a record depending on user permissions
	 *
	 * @param 	array	prev stages
	 * @param 	int		current stage id of the record
	 * @return 	array	prev stages
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
	 * @param 	int	stage id
	 * @param 	boolean if field notification_defaults should be selected instead of responsible users
	 * @return 	array be_users with e-mail and name
	 */
	public function getResponsibleBeUser($stageId, $selectDefaultUserField = FALSE) {
		$workspaceRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $this->getWorkspaceId());
		$recipientArray = array();
		switch ($stageId) {
		case self::STAGE_PUBLISH_EXECUTE_ID:

		case self::STAGE_PUBLISH_ID:
			if ($selectDefaultUserField == FALSE) {
				$userList = $this->getResponsibleUser($workspaceRec['adminusers']);
			} else {
				$notification_default_user = $workspaceRec['publish_notification_defaults'];
				$userList = $this->getResponsibleUser($notification_default_user);
			}
			break;
		case self::STAGE_EDIT_ID:
			if ($selectDefaultUserField == FALSE) {
				$userList = $this->getResponsibleUser($workspaceRec['members']);
			} else {
				$notification_default_user = $workspaceRec['edit_notification_defaults'];
				$userList = $this->getResponsibleUser($notification_default_user);
			}
			break;
		default:
			if ($selectDefaultUserField == FALSE) {
				$responsible_persons = $this->getPropertyOfCurrentWorkspaceStage($stageId, 'responsible_persons');
				$userList = $this->getResponsibleUser($responsible_persons);
			} else {
				$notification_default_user = $this->getPropertyOfCurrentWorkspaceStage($stageId, 'notification_defaults');
				$userList = $this->getResponsibleUser($notification_default_user);
			}
			break;
		}
		if (!empty($userList)) {
			$userRecords = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames('username, uid, email, realName', 'AND uid IN (' . $userList . ')');
		}
		if (!empty($userRecords) && is_array($userRecords)) {
			foreach ($userRecords as $userUid => $userRecord) {
				$recipientArray[$userUid] = $userRecord;
			}
		}
		return $recipientArray;
	}

	/**
	 * Get uids of all responsilbe persons for a stage
	 *
	 * @param 	string	responsible_persion value from stage record
	 * @return 	string	uid list of responsible be_users
	 */
	public function getResponsibleUser($stageRespValue) {
		$stageValuesArray = array();
		$stageValuesArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $stageRespValue);
		$beuserUidArray = array();
		$begroupUidArray = array();
		$allBeUserArray = array();
		$begroupUidList = array();
		foreach ($stageValuesArray as $key => $uidvalue) {
			if (strstr($uidvalue, 'be_users') !== FALSE) {
				// Current value is a uid of a be_user record
				$beuserUidArray[] = str_replace('be_users_', '', $uidvalue);
			} elseif (strstr($uidvalue, 'be_groups') !== FALSE) {
				$begroupUidArray[] = str_replace('be_groups_', '', $uidvalue);
			} else {
				$beuserUidArray[] = $uidvalue;
			}
		}
		if (!empty($begroupUidArray)) {
			$allBeUserArray = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames();
			$begroupUidList = implode(',', $begroupUidArray);
			$this->userGroups = array();
			$begroupUidArray = $this->fetchGroups($begroupUidList);
			foreach ($begroupUidArray as $groupkey => $groupData) {
				foreach ($allBeUserArray as $useruid => $userdata) {
					if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($userdata['usergroup_cached_list'], $groupData['uid'])) {
						$beuserUidArray[] = $useruid;
					}
				}
			}
		}
		array_unique($beuserUidArray);
		return implode(',', $beuserUidArray);
	}

	/**
	 * @param $grList
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
	 * @param array 	$groups
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
	 * Fetches particular groups recursively.
	 *
	 * @param $grList
	 * @param string $idList
	 * @return array
	 */
	private function fetchGroupsRecursive($grList, $idList = '') {
		$requiredGroups = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $grList, TRUE);
		$existingGroups = array_keys($this->userGroups);
		$missingGroups = array_diff($requiredGroups, $existingGroups);
		if (count($missingGroups) > 0) {
			$this->fetchGroupsFromDB($missingGroups);
		}
		// Traversing records in the correct order
		foreach ($requiredGroups as $uid) {
			// traversing list
			// Get row:
			$row = $this->userGroups[$uid];
			if (is_array($row) && !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($idList, $uid)) {
				// Must be an array and $uid should not be in the idList, because then it is somewhere previously in the grouplist
				// If the localconf.php option isset the user of the sub- sub- groups will also be used
				if ($GLOBALS['TYPO3_CONF_VARS']['BE']['customStageShowRecipientRecursive'] == 1) {
					// Include sub groups
					if (trim($row['subgroup'])) {
						// Make integer list
						$theList = implode(',', \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $row['subgroup']));
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
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($stageId)) {
			throw new \InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.stageId.integer'));
		}
		$workspaceStage = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord(self::TABLE_STAGE, $stageId);
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
	 * @param integer $stageId
	 * @return bool
	 */
	public function isPrevStageAllowedForUser($stageId) {
		$isAllowed = FALSE;
		try {
			$prevStage = $this->getPrevStage($stageId);
			// if there's no prev-stage the stageIds match,
			// otherwise we've to check if the user is permitted to use the stage
			if (!empty($prevStage) && $prevStage['uid'] != $stageId) {
				// if the current stage is allowed for the user, the user is also allowed to send to prev
				$isAllowed = $this->isStageAllowedForUser($stageId);
			}
		} catch (\Exception $e) {

		}
		return $isAllowed;
	}

	/**
	 * Check if the user has access to the next stage, relative to the given stage
	 *
	 * @param integer $stageId
	 * @return bool
	 */
	public function isNextStageAllowedForUser($stageId) {
		$isAllowed = FALSE;
		try {
			$nextStage = $this->getNextStage($stageId);
			// if there's no next-stage the stageIds match,
			// otherwise we've to check if the user is permitted to use the stage
			if (!empty($nextStage) && $nextStage['uid'] != $stageId) {
				// if the current stage is allowed for the user, the user is also allowed to send to next
				$isAllowed = $this->isStageAllowedForUser($stageId);
			}
		} catch (\Exception $e) {

		}
		return $isAllowed;
	}

	/**
	 * @param $stageId
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

	/**
	 * Determines whether a stage Id is valid.
	 *
	 * @param integer $stageId The stage Id to be checked
	 * @return boolean
	 */
	public function isValid($stageId) {
		$isValid = FALSE;
		$stages = $this->getStagesForWS();
		foreach ($stages as $stage) {
			if ($stage['uid'] == $stageId) {
				$isValid = TRUE;
				break;
			}
		}
		return $isValid;
	}

	/**
	 * Returns the notification mode from stage configuration
	 *
	 * Return values:
	 * 0 = notify someone / old way / default setting
	 * 1 = notify all responsible users (some users checked per default and you're not allowed to uncheck them)
	 * 2 = notify all responsible users (all users are checked and nothing can be changed during stage change)
	 *
	 * @param integer stage id to return the notification mode for
	 * @return integer
	 */
	public function getNotificationMode($stageId) {
		if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($stageId)) {
			throw new \InvalidArgumentException($GLOBALS['LANG']->sL('LLL:EXT:workspaces/Resources/Private/Language/locallang.xml:error.stageId.integer'));
		}
		switch ($stageId) {
		case self::STAGE_PUBLISH_EXECUTE_ID:

		case self::STAGE_PUBLISH_ID:
			$workspaceRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $this->getWorkspaceId());
			return $workspaceRecord['publish_notification_mode'];
			break;
		case self::STAGE_EDIT_ID:
			$workspaceRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $this->getWorkspaceId());
			return $workspaceRecord['edit_notification_mode'];
			break;
		default:
			$workspaceStage = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord(self::TABLE_STAGE, $stageId);
			if (is_array($workspaceStage) && isset($workspaceStage['notification_mode'])) {
				return $workspaceStage['notification_mode'];
			}
			break;
		}
	}

}


?>