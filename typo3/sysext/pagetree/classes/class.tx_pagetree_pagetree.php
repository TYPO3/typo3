<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Bodo Eichstaedt <bodo.eichstaedt@wmdb.de>
*  			Susanne Moog <s.moog@neusta.de>
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
 * pagetree implementation for the backend
 *
 * $Id: $
 *
 * @author	Susanne Moog
 * @package TYPO3
 */
class tx_pagetree_Pagetree extends tx_pagetree_AbstractTree {
	protected $contextMenuMapping = array(
		'view' => 'canBeViewed',
		'edit' => 'canBeEdited',
		'new' => 'canCreateNewPages',
		'info' => 'canShowInfo',
		'copy' => 'canBeCopied',
		'cut' => 'canBeCut',
		'paste' => 'canBePasted',
		'move_wizard' => 'canBeMoved',
		'new_wizard' => 'dsfdsfdsf',
		'mount_as_treeroot' => 'canBeTemporaryMountPoint',
		'hide' => 'canBeDisabled',
		'delete' => 'canBeRemoved',
		'history' => 'canShowHistory',
	);

	/**
	 * initializes the backendtreehelper and calls a method to set a class
	 * variable which holds the ts config for the context menu
	 *
	 */
	public function __construct() {
		$this->backendTreeHelper = t3lib_div::makeInstance('tx_contextmenu_Contextmenu');
		$this->fetchTsConfigForContextMenuItems();
	}

	/**
	 * Gets the clause to filter the page tree elements by.
	 *
	 * @param int $id - The page ID
	 */
	protected function getFilterClause($id)	{
		return ' AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1)
			. t3lib_BEfunc::deleteClause($this->table)
			. t3lib_BEfunc::versioningPlaceholderClause($this->table);
	}

	/**
	 * Checks if the user may create pages below the given page
	 *
	 * @param array $row The targets parent page
	 */
	public  function canCreate($row) {
		return $GLOBALS['BE_USER']->doesUserHaveAccess($row,8);
	}

	/**
	 * Checks if the user has editing rights
	 *
	 * @param array $row The result row for the corresponding page
	 */
	public  function canEdit($row) {
		return $GLOBALS['BE_USER']->doesUserHaveAccess($row,2);
	}

	/**
	 * Checks if the user has the right to delete the page
	 *
	 * @param array $row The result row for the corresponding page
	 */
	public  function canRemove($row)	{
		return $GLOBALS['BE_USER']->doesUserHaveAccess($row,4);
	}

	/**
	 *
	 *
	 * @param int $id
	 * @param string $workspacePreview
	 */
	public static function getViewLink($id, $workspacePreview) {
//		$viewScriptPreviewEnabled  = '/' . TYPO3_mainDir . 'mod/user/ws/wsol_preview.php?id=';
//		$viewScriptPreviewDisabled = '/index.php?id=';
//
//			// check alternate Domains
//		$rootLine = t3lib_BEfunc::BEgetRootLine($id);
//		if ($rootLine) {
//			$parts = parse_url(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
//			if (t3lib_BEfunc::getDomainStartPage($parts['host'], $parts['path'])) {
//				$preUrl_temp = t3lib_BEfunc::firstDomainRecord($rootLine);
//			}
//		}
//		$preUrl = ($preUrl_temp ? (t3lib_div::getIndpEnv('TYPO3_SSL') ?
//			'https://' : 'http://') . $preUrl_temp : '' . '..');
//
//			// Look if a fixed preview language should be added:
//		$viewLanguageOrder = $GLOBALS['BE_USER']->getTSConfigVal('options.view.languageOrder');
//		if (strlen($viewLanguageOrder))	{
//			$suffix = '';
//
//				// Find allowed languages (if none, all are allowed!)
//			if (!$GLOBALS['BE_USER']->user['admin'] &&
//				strlen($GLOBALS['BE_USER']->groupData['allowed_languages'])) {
//				$allowed_languages = array_flip(explode(',', $GLOBALS['BE_USER']->groupData['allowed_languages']));
//			}
//
//				// Traverse the view order, match first occurrence
//			$lOrder = t3lib_div::intExplode(',',$viewLanguageOrder);
//			foreach($lOrder as $langUid)	{
//				if (is_array($allowed_languages) && count($allowed_languages)) {
//					if (isset($allowed_languages[$langUid])) {	// Choose if set.
//						$suffix = '&L='.$langUid;
//						break;
//					}
//				} else {	// All allowed since no lang. are listed.
//					$suffix = '&L='.$langUid;
//					break;
//				}
//			}
//
//				// Add it:
//			$addGetVars.= $suffix;
//		}
//
//		$urlPreviewEnabled  = $preUrl . $viewScriptPreviewEnabled . $id . $addGetVars;
//		$urlPreviewDisabled = $preUrl . $viewScriptPreviewDisabled . $id . $addGetVars;
//
//		if ($workspacePreview) {
//			return $urlPreviewEnabled;
//		} else {
//			return $urlPreviewDisabled;
//		}

//		$javascriptLink = t3lib_BEfunc::viewOnClick($id);
//		debug($javascriptLink);

		return 'http://linux-schmie.de/wp-content/uploads/2010/07/Baustelle.png';
	}

	/**
	 * Shows the page (unhide)
	 *
	 * @param int $id The page Id
	 */
	public  function show($id) {
		$data['pages'][$id]['hidden'] = 0;
		$this->processTceCmdAndDataMap(array(), $data);
	}

	/**
	 * Hides the page
	 *
	 * @param int $id The page Id
	 */
	public  function disable($id)	{
		$data['pages'][$id]['hidden'] = 1;
		$this->processTceCmdAndDataMap(array(), $data);
	}

	/**
	 * Delete the page
	 *
	 * @param int $id The page Id
	 */
	public  function remove($id) {
		$cmd['pages'][$id]['delete'] = 1;
		$this->processTceCmdAndDataMap($cmd);
	}

	/**
	 * Restore the page ("undelete")
	 *
	 * @param int $id
	 */
	public  function restore($id) {
		$cmd['pages'][$id]['undelete'] = 1;
		$this->processTceCmdAndDataMap($cmd);
	}

	/**
	 * Copies a page. Use a negative target ID to specify a sibling target, else a parent is used
	 *
	 * @param int $sourceId The element to copy
	 * @param int $targetId The element to copy into (if you use a negative value: the element to copy after)
	 */
	public function copy($sourceId, $targetId) {
		$cmd['pages'][$sourceId]['copy'] = $targetId;
		$returnValue = $this->processTceCmdAndDataMap($cmd);
		return $returnValue['copy']['pages'][$sourceId];
	}

	/**
	 * Moves a page. Use a negative target ID to specify a sibling target, else a parent is used
	 *
	 * @param int $sourceId The element to move
	 * @param int $targetId The element to move into (if you use a negative value: the element to copy after)
	 */
	public function move($sourceId, $targetId) {
		$cmd['pages'][$sourceId]['move'] = $targetId;
		$this->processTceCmdAndDataMap($cmd);
	}

	/**
	 * Creates a page of the given doktype
	 *
	 * @param int $parentId The ID of the parent page
	 * @param int $doktype The doktype for the new page
	 */
	public function create($parentId, $targetId, $doktype) {
		$placeholder = 'NEW12345';
		$data['pages'][$placeholder] = array(
			'pid' => $parentId,
			'doktype' => $doktype,
			'title' => '[Default Title]'
		);
		$tceResultArr = $this->processTceCmdAndDataMap(array(), $data);
		if($parentId != $targetId) {
			$this->move($tceResultArr['new'][$placeholder], $targetId);
		}
		return $tceResultArr['new'][$placeholder];
	}

	/**
	 * fetches the tsconfig options for the context menu
	 *
	 */
	public function fetchTsConfigForContextMenuItems() {
		$this->contextMenuConfiguration = t3lib_div::trimExplode(',',$GLOBALS['BE_USER']->getTSConfigVal('options.contextMenu.pageTree.disableItems'),1);
	}

	/**
	 * Checks if the page is allowed to have subpages
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canHaveSubpages($row) {
		return $this->getDoktypeDependentConfigOptions($row['doktype'], 'canHaveSubpages');
	}

	/**
	 * Checks if the page can be disabled
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBeDisabled($row) {
		if($this->canEdit($row) && !in_array('hide', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Checks if the page is allowed to show info
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canShowInfo() {
		if(!in_array('info', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Checks if the page is allowed to be a temporary mountpoint
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBeTemporaryMountPoint() {
		if(!in_array('mount_as_treeroot', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Checks if the page is allowed to can be cut
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBeCut() {
		if(!in_array('cut', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Checks if the page is allowed to be pasted
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBePasted() {
		if(!in_array('paste', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Checks if the page is allowed to show history
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canShowHistory() {
		if(!in_array('history', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Checks if the page is allowed to be edited
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBeEdited($row) {
		if($this->canEdit($row)
			&& $this->getDoktypeDependentConfigOptions($row['doktype'], 'canBeEdited')
			&& !in_array('edit', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Checks if the page is allowed to be moved
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBeMoved($row) {
		if($this->canEdit($row)
			&& $this->getDoktypeDependentConfigOptions($row['doktype'], 'canBeEdited')
			&& !in_array('move_wizard', $this->contextMenuConfiguration)){
			return true;
		} else {
			return false;
		};
	}

	/**
	 * Checks if the page is allowed to be copied
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBeCopied($row) {
		if($this->canEdit($row)
			&& $this->getDoktypeDependentConfigOptions($row['doktype'], 'canBeCopied')
			&& !in_array('copy', $this->contextMenuConfiguration)){
			return true;
		} else {
			return false;
		};
	}

	/**
	 * Checks if there can be new pages created
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canCreateNewPages($row) {
		return $this->canCreate($row) && !in_array('new', $this->contextMenuConfiguration) ? true : false;
	}

	/**
	 * Checks if the page is allowed to be removed
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBeRemoved($row) {
		if($this->canRemove($row)
			&& $this->getDoktypeDependentConfigOptions($row['doktype'], 'canBeDeleted')
			&& !in_array('delete', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Checks if the page is allowed to be viewed
	 *
	 * @param array $row The page result row to check
	 * @param boolean true if allowed
	 */
	public function canBeViewed($row) {
		if($this->getDoktypeDependentConfigOptions($row['doktype'], 'canBeViewed')
			&& !in_array('view', $this->contextMenuConfiguration)) {
				return true;
		} else {
				return false;
		}
	}

	/**
	 * Gets configuration options for the clickmenu depending on the current doctype
	 * For Example: No "view" link for sysfolders
	 *
	 * Possible actions are: isViewable, isDeletable, isEditable,isMovable,
	 * canHoldSubpages, canBeCopied
	 *
	 * @param int $doktype The doctype to check
	 * @param string $action The action to check
	 * @return boolean true if the action is allowed for the doctype, else false
	 */
	public function getDoktypeDependentConfigOptions($doktype, $action) {
		//@TODO method was dropped (replacement needed)
//		return t3lib_pageSelect::pageTypeHasCapability($doktype, $action);
	}

	/**
	 * Process TCEMAIN Commands and Datamaps
	 *
	 * @param array $cmd - The command array - processed by process_cmdmap()
	 * @param array $data - The data array - processed by process_datamap()
	 */
	protected function processTceCmdAndDataMap(array $cmd, array $data=array()) {
		/** @var $tce t3lib_TCEmain */
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values = 0;
		$tce->start($data,$cmd);
		if($cmd) {
			$tce->process_cmdmap();
			$returnValues['copy'] = $tce->copyMappingArray_merged;
		} else if ($data) {
			$tce->process_datamap();
			$returnValues['new'] = $tce->substNEWwithIDs;
		}
		return $returnValues;
	}

	/**
	 * returns the pagetree mounts for the current user
	 * @return array
	 */
	public function getTreeMounts() {
		$records = array();
		$webmountIds = $GLOBALS['BE_USER']->returnWebmounts();
		if(!empty($webmountIds)){
			foreach ($webmountIds as $webmount) {
				if ($webmount == 0) {
					$records[] = $this->addRootPageInformation();
				} else {
					$record = t3lib_BEfunc::getRecordWSOL(
						$this->table,
						$webmount,
						$fields = '*',
						$where = '',
						$useDeleteClause = TRUE,
						$GLOBALS['BE_USER']->uc['currentPageTreeLanguage']
					);
					$this->addMetaInformationToPage($record['uid'], $record);
					$records[] = $record;
				}
			}
		}
		return $records;
	}

	/**
	 * Updates the given field with a new text value, may be used to inline update
	 * the title field in the new page tree
	 *
	 * @param int $pageId
	 * @param string $newString
	 * @param string $field
	 */
	public function updateTextInputField($pageId, $newString, $field) {
		$data[$this->table][$pageId][$field] = $newString;
		$this->processTceCmdAndDataMap(array(), $data);
	}

	/**
	 * Helper function for fetching ts config options with "options.pagetree.[key]"
	 *
	 * @param string $optionName the string to fetch
	 * @param string the ts config value
	 */
	public function getTsConfigOptionForPagetree($optionName) {
		return $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.' . $optionName);
	}

	/**
	 * Fetches domain records from site root
	 * Needed for "displayDomainNameWithTitle"
	 *
	 * @param int $pageId The page id of the page with the website_root flag
	 * @param string returns the domain
	 */
	public function getDomainRecordFromSiteRoot($pageId) {
		$domainRecord = t3lib_BEfunc::getRecordsByField('sys_domain', 'pid', $pageId, ' AND redirectTo=\'\' AND hidden=0', '', 'sorting');
		if (is_array($domainRecord)) {
			reset($domainRecord);
			$firstDomainRecord = current($domainRecord);
			return rtrim($firstDomainRecord['domainName'], '/');
		}
	}

	/**
	 * Fetches a page result record for given fields
	 *
	 * @param int $pageId The page id to fetch infos for
	 * @param array $fields a field array with the fields to fetch
	 */
	public function getPageInformationForGivenFields($pageId, $fields){
		return t3lib_BEfunc::getRecordWSOL(
			'pages',
			$pageId,
			implode(',', $fields),
			$where = '',
			$useDeleteClause = TRUE,
			$GLOBALS['BE_USER']->uc['currentPageTreeLanguage']
		);
	}

	/**
	 * Adds meta data to a page
	 * Data:
	 * - if it has sub-pages
	 * - if the user may edit/create/delete the page
	 * - if the page may be viewed, deleted, edited, moved, copied, or can contain subpages
	 *
	 * @param int $id The ID of the page
	 * @param array $row the SQL result row with the db related information for the element
	 */
	public function addMetaInformationToPage($id, &$row) {
		$numRows = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
			'uid',
			$this->table,
			'pid = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($id, $this->table)
		);

		if ($numRows) {
			$row['_subpages'] = $numRows;
		}

		$row['_actions'] = array(
			'canBeEdited' => $this->canBeEdited($row),
			'canCreateNewPages' => $this->canCreateNewPages($row),
			'canBeRemoved' => $this->canBeRemoved($row),
			'canBeViewed' => $this->canBeViewed($row),
			'canBeMoved' => $this->canBeMoved($row),
			'canHaveSubpages' => $this->canHaveSubpages($row),
			'canBeCopied' => $this->canBeCopied($row),
			'canBeDisabled' => $this->canBeDisabled($row),
			'canShowInfo' => $this->canShowInfo(),
			'canBeCut' => $this->canBeCut(),
			'canBePasted' => $this->canBePasted(),
			'canShowHistory' => $this->canShowHistory(),
			'canBeTemporaryMountPoint' => $this->canBeTemporaryMountPoint()
		);

		foreach ($row['_actions'] as $action => $state) {
			if (!$state) {
				unset($row['_actions'][$action]);
			}
		}
		$actions = $row['_actions'];

		$localContextMenuMapping = array_flip($this->contextMenuMapping);

		$availableActions = array();
		foreach($localContextMenuMapping as $contextActionKey => $contextActionValue) {
			if(array_key_exists($contextActionKey, $actions)) {
				$availableActions[$contextActionValue] = $contextActionKey;
			}
		}

		if(in_array('canBePasted', $availableActions)){
			$availableActions[] = 'canBePastedAfter';
			$availableActions[] = 'canBePastedInto';
		}
		if(in_array('canBeDisabled', $availableActions)){
			$availableActions[] = 'canBeEnabled';
		}

		$availableAndConfiguredOptions = array_values($this->backendTreeHelper->getTsConfigActions($availableActions));

		$row['_actions'] = $availableAndConfiguredOptions;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_pagetree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/class.tx_pagetree_pagetree.php']);
}

?>