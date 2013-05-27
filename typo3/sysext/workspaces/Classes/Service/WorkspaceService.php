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
 * Workspace service
 *
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 */
class WorkspaceService implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $pageCache = array();

	const TABLE_WORKSPACE = 'sys_workspace';
	const SELECT_ALL_WORKSPACES = -98;
	const LIVE_WORKSPACE_ID = 0;
	/**
	 * retrieves the available workspaces from the database and checks whether
	 * they're available to the current BE user
	 *
	 * @return 	array	array of worspaces available to the current user
	 */
	public function getAvailableWorkspaces() {
		$availableWorkspaces = array();
		// add default workspaces
		if ($GLOBALS['BE_USER']->checkWorkspace(array('uid' => (string) self::LIVE_WORKSPACE_ID))) {
			$availableWorkspaces[self::LIVE_WORKSPACE_ID] = self::getWorkspaceTitle(self::LIVE_WORKSPACE_ID);
		}
		// add custom workspaces (selecting all, filtering by BE_USER check):
		$customWorkspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, title, adminusers, members', 'sys_workspace', 'pid = 0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('sys_workspace'), '', 'title');
		if (count($customWorkspaces)) {
			foreach ($customWorkspaces as $workspace) {
				if ($GLOBALS['BE_USER']->checkWorkspace($workspace)) {
					$availableWorkspaces[$workspace['uid']] = $workspace['title'];
				}
			}
		}
		return $availableWorkspaces;
	}

	/**
	 * Gets the current workspace ID.
	 *
	 * @return integer The current workspace ID
	 */
	public function getCurrentWorkspace() {
		$workspaceId = $GLOBALS['BE_USER']->workspace;
		if ($GLOBALS['BE_USER']->isAdmin()) {
			$activeId = $GLOBALS['BE_USER']->getSessionData('tx_workspace_activeWorkspace');
			$workspaceId = $activeId !== NULL ? $activeId : $workspaceId;
		}
		return $workspaceId;
	}

	/**
	 * Find the title for the requested workspace.
	 *
	 * @param integer $wsId
	 * @return string
	 */
	static public function getWorkspaceTitle($wsId) {
		$title = FALSE;
		switch ($wsId) {
		case self::LIVE_WORKSPACE_ID:
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xml:shortcut_onlineWS');
			break;
		default:
			$labelField = $GLOBALS['TCA']['sys_workspace']['ctrl']['label'];
			$wsRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $wsId, 'uid,' . $labelField);
			if (is_array($wsRecord)) {
				$title = $wsRecord[$labelField];
			}
		}
		if ($title === FALSE) {
			throw new \InvalidArgumentException('No such workspace defined');
		}
		return $title;
	}

	/**
	 * Building tcemain CMD-array for swapping all versions in a workspace.
	 *
	 * @param 	integer		Real workspace ID, cannot be ONLINE (zero).
	 * @param 	boolean		If set, then the currently online versions are swapped into the workspace in exchange for the offline versions. Otherwise the workspace is emptied.
	 * @param 	integer		$pageId: ...
	 * @param 	integer		$language Select specific language only
	 * @return 	array		Command array for tcemain
	 */
	public function getCmdArrayForPublishWS($wsid, $doSwap, $pageId = 0, $language = NULL) {
		$wsid = intval($wsid);
		$cmd = array();
		if ($wsid >= -1 && $wsid !== 0) {
			// Define stage to select:
			$stage = -99;
			if ($wsid > 0) {
				$workspaceRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('sys_workspace', $wsid);
				if ($workspaceRec['publish_access'] & 1) {
					$stage = \TYPO3\CMS\Workspaces\Service\StagesService::STAGE_PUBLISH_ID;
				}
			}
			// Select all versions to swap:
			$versions = $this->selectVersionsInWorkspace($wsid, 0, $stage, $pageId ? $pageId : -1, 0, 'tables_modify', $language);
			// Traverse the selection to build CMD array:
			foreach ($versions as $table => $records) {
				foreach ($records as $rec) {
					// Build the cmd Array:
					$cmd[$table][$rec['t3ver_oid']]['version'] = array('action' => 'swap', 'swapWith' => $rec['uid'], 'swapIntoWS' => $doSwap ? 1 : 0);
				}
			}
		}
		return $cmd;
	}

	/**
	 * Building tcemain CMD-array for releasing all versions in a workspace.
	 *
	 * @param 	integer		Real workspace ID, cannot be ONLINE (zero).
	 * @param 	boolean		Run Flush (TRUE) or ClearWSID (FALSE) command
	 * @param 	integer		$pageId: ...
	 * @param 	integer		$language Select specific language only
	 * @return 	array		Command array for tcemain
	 */
	public function getCmdArrayForFlushWS($wsid, $flush = TRUE, $pageId = 0, $language = NULL) {
		$wsid = intval($wsid);
		$cmd = array();
		if ($wsid >= -1 && $wsid !== 0) {
			// Define stage to select:
			$stage = -99;
			// Select all versions to swap:
			$versions = $this->selectVersionsInWorkspace($wsid, 0, $stage, $pageId ? $pageId : -1, 0, 'tables_modify', $language);
			// Traverse the selection to build CMD array:
			foreach ($versions as $table => $records) {
				foreach ($records as $rec) {
					// Build the cmd Array:
					$cmd[$table][$rec['uid']]['version'] = array('action' => $flush ? 'flush' : 'clearWSID');
				}
			}
		}
		return $cmd;
	}

	/**
	 * Select all records from workspace pending for publishing
	 * Used from backend to display workspace overview
	 * User for auto-publishing for selecting versions for publication
	 *
	 * @param 	integer		Workspace ID. If -99, will select ALL versions from ANY workspace. If -98 will select all but ONLINE. >=-1 will select from the actual workspace
	 * @param 	integer		Lifecycle filter: 1 = select all drafts (never-published), 2 = select all published one or more times (archive/multiple), anything else selects all.
	 * @param 	integer		Stage filter: -99 means no filtering, otherwise it will be used to select only elements with that stage. For publishing, that would be "10
	 * @param 	integer		Page id: Live page for which to find versions in workspace!
	 * @param 	integer		Recursion Level - select versions recursive - parameter is only relevant if $pageId != -1
	 * @param 	string		How to collect records for "listing" or "modify" these tables. Support the permissions of each type of record, see \TYPO3\CMS\Core\Authentication\BackendUserAuthentication::check.
	 * @param 	integer		$language Select specific language only
	 * @return 	array		Array of all records uids etc. First key is table name, second key incremental integer. Records are associative arrays with uid and t3ver_oidfields. The pid of the online record is found as "livepid" the pid of the offline record is found in "wspid
	 */
	public function selectVersionsInWorkspace($wsid, $filter = 0, $stage = -99, $pageId = -1, $recursionLevel = 0, $selectionType = 'tables_select', $language = NULL) {
		$wsid = intval($wsid);
		$filter = intval($filter);
		$output = array();
		// Contains either nothing or a list with live-uids
		if ($pageId != -1 && $recursionLevel > 0) {
			$pageList = $this->getTreeUids($pageId, $wsid, $recursionLevel);
		} elseif ($pageId != -1) {
			$pageList = $pageId;
		} else {
			$pageList = '';
			// check if person may only see a "virtual" page-root
			$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
			$mountPoints = array_unique($mountPoints);
			if (!in_array(0, $mountPoints)) {
				$tempPageIds = array();
				foreach ($mountPoints as $mountPoint) {
					$tempPageIds[] = $this->getTreeUids($mountPoint, $wsid, $recursionLevel);
				}
				$pageList = implode(',', $tempPageIds);
			}
		}
		// Traversing all tables supporting versioning:
		foreach ($GLOBALS['TCA'] as $table => $cfg) {
			// we do not collect records from tables without permissions on them.
			if (!$GLOBALS['BE_USER']->check($selectionType, $table)) {
				continue;
			}
			if ($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) {
				$recs = $this->selectAllVersionsFromPages($table, $pageList, $wsid, $filter, $stage, $language);
				if (intval($GLOBALS['TCA'][$table]['ctrl']['versioningWS']) === 2) {
					$moveRecs = $this->getMoveToPlaceHolderFromPages($table, $pageList, $wsid, $filter, $stage);
					$recs = array_merge($recs, $moveRecs);
				}
				$recs = $this->filterPermittedElements($recs, $table);
				if (count($recs)) {
					$output[$table] = $recs;
				}
			}
		}
		return $output;
	}

	/**
	 * Find all versionized elements except moved records.
	 *
	 * @param string $table
	 * @param string $pageList
	 * @param integer $wsid
	 * @param integer $filter
	 * @param integer $stage
	 * @param integer $language
	 * @return array
	 */
	protected function selectAllVersionsFromPages($table, $pageList, $wsid, $filter, $stage, $language = NULL) {
		$isTableLocalizable = \TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table);
		$languageParentField = '';
		// If table is not localizable, but localized reocrds shall
		// be collected, an empty result array needs to be returned:
		if ($isTableLocalizable === FALSE && $language > 0) {
			return array();
		} elseif ($isTableLocalizable) {
			$languageParentField = 'A.' . $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] . ', ';
		}
		$fields = 'A.uid, A.t3ver_oid, A.t3ver_stage, ' . $languageParentField . 'B.pid AS wspid, B.pid AS livepid';
		if ($isTableLocalizable) {
			$fields .= ', A.' . $GLOBALS['TCA'][$table]['ctrl']['languageField'];
		}
		$from = $table . ' A,' . $table . ' B';
		// Table A is the offline version and pid=-1 defines offline
		$where = 'A.pid=-1 AND A.t3ver_state!=4';
		if ($pageList) {
			$pidField = $table === 'pages' ? 'uid' : 'pid';
			$pidConstraint = strstr($pageList, ',') ? ' IN (' . $pageList . ')' : '=' . $pageList;
			$where .= ' AND B.' . $pidField . $pidConstraint;
		}
		if ($isTableLocalizable && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($language)) {
			$where .= ' AND A.' . $GLOBALS['TCA'][$table]['ctrl']['languageField'] . '=' . $language;
		}
		// For "real" workspace numbers, select by that.
		// If = -98, select all that are NOT online (zero).
		// Anything else below -1 will not select on the wsid and therefore select all!
		if ($wsid > self::SELECT_ALL_WORKSPACES) {
			$where .= ' AND A.t3ver_wsid=' . $wsid;
		} elseif ($wsid === self::SELECT_ALL_WORKSPACES) {
			$where .= ' AND A.t3ver_wsid!=0';
		}
		// lifecycle filter:
		// 1 = select all drafts (never-published),
		// 2 = select all published one or more times (archive/multiple)
		if ($filter === 1 || $filter === 2) {
			$where .= ' AND A.t3ver_count ' . ($filter === 1 ? '= 0' : '> 0');
		}
		if ($stage != -99) {
			$where .= ' AND A.t3ver_stage=' . intval($stage);
		}
		// Table B (online) must have PID >= 0 to signify being online.
		$where .= ' AND B.pid>=0';
		// ... and finally the join between the two tables.
		$where .= ' AND A.t3ver_oid=B.uid';
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, 'A');
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, 'B');
		// Select all records from this table in the database from the workspace
		// This joins the online version with the offline version as tables A and B
		// Order by UID, mostly to have a sorting in the backend overview module which doesn't "jump around" when swapping.
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $from, $where, '', 'B.uid');
		return is_array($res) ? $res : array();
	}

	/**
	 * Find all moved records at their new position.
	 *
	 * @param string $table
	 * @param string $pageList
	 * @param integer $wsid
	 * @param integer $filter
	 * @param integer $stage
	 * @return array
	 */
	protected function getMoveToPlaceHolderFromPages($table, $pageList, $wsid, $filter, $stage) {
		// Aliases:
		// A - moveTo placeholder
		// B - online record
		// C - moveFrom placeholder
		$fields = 'A.pid AS wspid, B.uid AS t3ver_oid, C.uid AS uid, B.pid AS livepid';
		$from = $table . ' A, ' . $table . ' B,' . $table . ' C';
		$where = 'A.t3ver_state=3 AND B.pid>0 AND B.t3ver_state=0 AND B.t3ver_wsid=0 AND C.pid=-1 AND C.t3ver_state=4';
		if ($wsid > self::SELECT_ALL_WORKSPACES) {
			$where .= ' AND A.t3ver_wsid=' . $wsid . ' AND C.t3ver_wsid=' . $wsid;
		} elseif ($wsid === self::SELECT_ALL_WORKSPACES) {
			$where .= ' AND A.t3ver_wsid!=0 AND C.t3ver_wsid!=0 ';
		}
		// lifecycle filter:
		// 1 = select all drafts (never-published),
		// 2 = select all published one or more times (archive/multiple)
		if ($filter === 1 || $filter === 2) {
			$where .= ' AND C.t3ver_count ' . ($filter === 1 ? '= 0' : '> 0');
		}
		if ($stage != -99) {
			$where .= ' AND C.t3ver_stage=' . intval($stage);
		}
		if ($pageList) {
			$pidField = $table === 'pages' ? 'B.uid' : 'A.pid';
			$pidConstraint = strstr($pageList, ',') ? ' IN (' . $pageList . ')' : '=' . $pageList;
			$where .= ' AND ' . $pidField . $pidConstraint;
		}
		$where .= ' AND A.t3ver_move_id = B.uid AND B.uid = C.t3ver_oid';
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, 'A');
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, 'B');
		$where .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause($table, 'C');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $from, $where, '', 'A.uid');
		return is_array($res) ? $res : array();
	}

	/**
	 * Find all page uids recursive starting from a specific page
	 *
	 * @param 	 integer	$pageId
	 * @param 	 integer	$wsid
	 * @param 	 integer	$recursionLevel
	 * @return 	string	Comma sep. uid list
	 */
	protected function getTreeUids($pageId, $wsid, $recursionLevel) {
		// Reusing existing functionality with the drawback that
		// mount points are not covered yet
		$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		/** @var $searchObj \TYPO3\CMS\Core\Database\QueryView */
		$searchObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Database\\QueryView');
		if ($pageId > 0) {
			$pageList = $searchObj->getTreeList($pageId, $recursionLevel, 0, $perms_clause);
		} else {
			$mountPoints = $GLOBALS['BE_USER']->uc['pageTree_temporaryMountPoint'];
			if (!is_array($mountPoints) || empty($mountPoints)) {
				$mountPoints = array_map('intval', $GLOBALS['BE_USER']->returnWebmounts());
				$mountPoints = array_unique($mountPoints);
			}
			$newList = array();
			foreach ($mountPoints as $mountPoint) {
				$newList[] = $searchObj->getTreeList($mountPoint, $recursionLevel, 0, $perms_clause);
			}
			$pageList = implode(',', $newList);
		}
		unset($searchObj);
		if (intval($GLOBALS['TCA']['pages']['ctrl']['versioningWS']) === 2 && $pageList) {
			// Remove the "subbranch" if a page was moved away
			$movedAwayPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, pid, t3ver_move_id', 'pages', 't3ver_move_id IN (' . $pageList . ') AND t3ver_wsid=' . intval($wsid) . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages'), '', 'uid', '', 't3ver_move_id');
			$pageIds = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $pageList, TRUE);
			// move all pages away
			$newList = array_diff($pageIds, array_keys($movedAwayPages));
			// keep current page in the list
			$newList[] = $pageId;
			// move back in if still connected to the "remaining" pages
			do {
				$changed = FALSE;
				foreach ($movedAwayPages as $uid => $rec) {
					if (in_array($rec['pid'], $newList) && !in_array($uid, $newList)) {
						$newList[] = $uid;
						$changed = TRUE;
					}
				}
			} while ($changed);
			$pageList = implode(',', $newList);
			// In case moving pages is enabled we need to replace all move-to pointer with their origin
			$pages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, t3ver_move_id', 'pages', 'uid IN (' . $pageList . ')' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages'), '', 'uid', '', 'uid');
			$newList = array();
			$pageIds = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $pageList, TRUE);
			if (!in_array($pageId, $pageIds)) {
				$pageIds[] = $pageId;
			}
			foreach ($pageIds as $pageId) {
				if (intval($pages[$pageId]['t3ver_move_id']) > 0) {
					$newList[] = intval($pages[$pageId]['t3ver_move_id']);
				} else {
					$newList[] = $pageId;
				}
			}
			$pageList = implode(',', $newList);
		}
		return $pageList;
	}

	/**
	 * Remove all records which are not permitted for the user
	 *
	 * @param array $recs
	 * @param string $table
	 * @return array
	 */
	protected function filterPermittedElements($recs, $table) {
		$checkField = $table == 'pages' ? 'uid' : 'wspid';
		$permittedElements = array();
		if (is_array($recs)) {
			foreach ($recs as $rec) {
				$page = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $rec[$checkField], 'uid,pid,perms_userid,perms_user,perms_groupid,perms_group,perms_everybody');
				if ($GLOBALS['BE_USER']->doesUserHaveAccess($page, 1) && $this->isLanguageAccessibleForCurrentUser($table, $rec)) {
					$permittedElements[] = $rec;
				}
			}
		}
		return $permittedElements;
	}

	/**
	 * Check current be users language access on given record.
	 *
	 * @param string $table Name of the table
	 * @param array $record Record row to be checked
	 * @return boolean
	 */
	protected function isLanguageAccessibleForCurrentUser($table, array $record) {
		$languageUid = 0;
		if (\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table)) {
			$languageUid = $record[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
		} else {
			return TRUE;
		}
		return $GLOBALS['BE_USER']->checkLanguageAccess($languageUid);
	}

	/**
	 * Trivial check to see if the user already migrated his workspaces
	 * to the new style (either manually or with the migrator scripts)
	 *
	 * @return bool
	 */
	static public function isOldStyleWorkspaceUsed() {
		$oldStyleWorkspaceIsUsed = FALSE;
		$cacheKey = 'workspace-oldstyleworkspace-notused';
		$cacheResult = $GLOBALS['BE_USER']->getSessionData($cacheKey);
		if (!$cacheResult) {
			$where = 'adminusers != "" AND adminusers NOT LIKE "%be_users%" AND adminusers NOT LIKE "%be_groups%" AND deleted=0';
			$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'sys_workspace', $where);
			$oldStyleWorkspaceIsUsed = $count > 0;
			$GLOBALS['BE_USER']->setAndSaveSessionData($cacheKey, !$oldStyleWorkspaceIsUsed);
		} else {
			$oldStyleWorkspaceIsUsed = !$cacheResult;
		}
		return $oldStyleWorkspaceIsUsed;
	}

	/**
	 * Determine whether a specific page is new and not yet available in the LIVE workspace
	 *
	 * @static
	 * @param integer $id Primary key of the page to check
	 * @param integer $language Language for which to check the page
	 * @return boolean
	 */
	static public function isNewPage($id, $language = 0) {
		$isNewPage = FALSE;
		// If the language is not default, check state of overlay
		if ($language > 0) {
			$whereClause = 'pid = ' . intval($id);
			$whereClause .= ' AND ' . $GLOBALS['TCA']['pages_language_overlay']['ctrl']['languageField'] . ' = ' . intval($language);
			$whereClause .= ' AND t3ver_wsid = ' . intval($GLOBALS['BE_USER']->workspace);
			$whereClause .= \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('pages_language_overlay');
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('t3ver_state', 'pages_language_overlay', $whereClause);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$isNewPage = (int) $row['t3ver_state'] === 1;
			}
		} else {
			$rec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $id, 't3ver_state');
			if (is_array($rec)) {
				$isNewPage = (int) $rec['t3ver_state'] === 1;
			}
		}
		return $isNewPage;
	}

	/**
	 * Generates a view link for a page.
	 *
	 * @static
	 * @param string $table Table to be used
	 * @param integer $uid Uid of the version(!) record
	 * @param array $liveRecord Optional live record data
	 * @param array $versionRecord Optional version record data
	 * @return string
	 */
	static public function viewSingleRecord($table, $uid, array $liveRecord = NULL, array $versionRecord = NULL) {
		$viewUrl = '';

		if ($table == 'pages') {
			$viewUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick(\TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionIdOfRecord('pages', $uid));
		} elseif ($table === 'pages_language_overlay' || $table === 'tt_content') {
			if ($liveRecord === NULL) {
				$liveRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionOfRecord($table, $uid);
			}
			if ($versionRecord === NULL) {
				$versionRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $uid);
			}

			$additionalParameters = '';
			$languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'];
			if ($versionRecord[$languageField] > 0) {
				$additionalParameters .= '&L=' . $versionRecord[$languageField];
			}

			$viewUrl = \TYPO3\CMS\Backend\Utility\BackendUtility::viewOnClick($liveRecord['pid'], '', '', '', '', $additionalParameters);
		} else {
			if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['viewSingleRecord'])) {
				$_params = array(
					'table' => $table,
					'uid' => $uid,
					'record' => $liveRecord,
					'liveRecord' => $liveRecord,
					'versionRecord' => $versionRecord,
				);
				$_funcRef = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['workspaces']['viewSingleRecord'];
				$null = NULL;
				$viewUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $_params, $null);
			}
		}

		return $viewUrl;
	}

	/**
	 * Determine whether this page for the current
	 *
	 * @param integer $pageUid
	 * @param integer $workspaceUid
	 * @return boolean
	 */
	public function canCreatePreviewLink($pageUid, $workspaceUid) {
		$result = TRUE;
		if ($pageUid > 0 && $workspaceUid > 0) {
			$pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pageUid);
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('pages', $pageRecord, $workspaceUid);
			if (!\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['FE']['content_doktypes'], $pageRecord['doktype'])) {
				$result = FALSE;
			}
		} else {
			$result = FALSE;
		}
		return $result;
	}

	/**
	 * Generates a workspace preview link.
	 *
	 * @param integer $uid The ID of the record to be linked
	 * @return string the full domain including the protocol http:// or https://, but without the trailing '/'
	 */
	public function generateWorkspacePreviewLink($uid) {
		$previewObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Version\\Hook\\PreviewHook');
		$timeToLiveHours = $previewObject->getPreviewLinkLifetime();
		$previewKeyword = $previewObject->compilePreviewKeyword('', $GLOBALS['BE_USER']->user['uid'], $timeToLiveHours * 3600, $this->getCurrentWorkspace());
		$linkParams = array(
			'ADMCMD_prev' => $previewKeyword,
			'id' => $uid
		);
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getViewDomain($uid) . '/index.php?' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeArrayForUrl('', $linkParams);
	}

	/**
	 * Generates a workspace splitted preview link.
	 *
	 * @param integer $uid The ID of the record to be linked
	 * @param boolean $addDomain Parameter to decide if domain should be added to the generated link, FALSE per default
	 * @return string the preview link without the trailing '/'
	 */
	public function generateWorkspaceSplittedPreviewLink($uid, $addDomain = FALSE) {
		// In case a $pageUid is submitted we need to make sure it points to a live-page
		if ($uid > 0) {
			$uid = $this->getLivePageUid($uid);
		}
		$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerException');
		/** @var $uriBuilder \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder */
		$uriBuilder = $objectManager->create('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		// This seems to be very harsh to set this directly to "/typo3 but the viewOnClick also
		// has /index.php as fixed value here and dealing with the backPath is very error-prone
		// @todo make sure this would work in local extension installation too
		$backPath = '/' . TYPO3_mainDir;
		$redirect = $backPath . 'index.php?redirect_url=';
		// @todo why do we need these additional params? the URIBuilder should add the controller, but he doesn't :(
		$additionalParams = '&tx_workspaces_web_workspacesworkspaces%5Bcontroller%5D=Preview&M=web_WorkspacesWorkspaces&id=';
		$viewScript = $backPath . $uriBuilder->setArguments(array('tx_workspaces_web_workspacesworkspaces' => array('previewWS' => $GLOBALS['BE_USER']->workspace)))->uriFor('index', array(), 'TYPO3\\CMS\\Workspaces\\Controller\\PreviewController', 'workspaces', 'web_workspacesworkspaces') . $additionalParams;
		if ($addDomain === TRUE) {
			return \TYPO3\CMS\Backend\Utility\BackendUtility::getViewDomain($uid) . $redirect . urlencode($viewScript) . $uid;
		} else {
			return $viewScript;
		}
	}

	/**
	 * Find the Live-Uid for a given page,
	 * the results are cached at run-time to avoid too many database-queries
	 *
	 * @throws \InvalidArgumentException
	 * @param integer $uid
	 * @return integer
	 */
	public function getLivePageUid($uid) {
		if (!isset($this->pageCache[$uid])) {
			$pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $uid);
			if (is_array($pageRecord)) {
				$this->pageCache[$uid] = $pageRecord['t3ver_oid'] ? $pageRecord['t3ver_oid'] : $uid;
			} else {
				throw new \InvalidArgumentException('uid is supposed to point to an existing page - given value was:' . $uid, 1290628113);
			}
		}
		return $this->pageCache[$uid];
	}

}


?>