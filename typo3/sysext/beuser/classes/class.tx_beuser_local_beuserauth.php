<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Extension class of beuserauth class.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class tx_beuser_local_beUserAuth extends t3lib_beUserAuth {
	var $ext_pageIdsFromMounts='';					// List of mounted page ids (from browsetree class when selecting mountpoints)
	var $ext_non_readAccessPageArray=array();		// Storage for non-readable webmounts, see returnWebmounts()

	/**
	 * Returns an array of the webmounts for the user, with non-readable webmounts filtered out.
	 * If there are non-readable webmounts they are registered in $this->ext_non_readAccessPageArray
	 * (Extending function in parent class)
	 *
	 * @param	string		alternative select clause (default is getPagePermsClause(1)). For instance to make sure that ALL webmounts are selected regardless of whether the user has read access or not, you can set this to "1=1".
	 * @return	array		Webmounts id's
	 */
	function returnWebmounts($pClause='') {

		// Get array of webmounts:
		$webmounts = (string)($this->groupData['webmounts'])!='' ? explode(',', $this->groupData['webmounts']) : Array();

		// Get select clause:
		$pClause=$pClause?$pClause:$this->getPagePermsClause(1);

		// Traverse mounts, check if they are readable:
		foreach ($webmounts as $k => $id) {
			$rec=t3lib_BEfunc::getRecord('pages', $id, '*', ' AND '.$pClause);
			if (!is_array($rec)) {
				$this->ext_non_readAccessPageArray[$id]=t3lib_BEfunc::getRecord('pages', $id);
				unset($webmounts[$k]);
			}
		}
		return $webmounts;
	}

	/**
	 * Based on the content of ->ext_non_readAccessPageArray (see returnWebmounts()) it generates visually formatted information about these non-readable mounts.
	 *
	 * @return	string		HTML content showing which DB-mounts were not accessible for the user
	 */
	function ext_non_readAccessPages() {
		$lines=array();

		foreach ($this->ext_non_readAccessPageArray as $pA) {
			if ($pA) {
				$lines[] = t3lib_BEfunc::getRecordPath($pA['uid'], '', 15);
			}
		}
		if (count($lines)) {
			return '<table bgcolor="red" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td align="center"><font color="white"><strong>' . $GLOBALS['LANG']->getLL('noReadAccess', TRUE) . '</strong></font></td>
				</tr>
				<tr>
					<td>'.implode('</td></tr><tr><td>', $lines).'</td>
				</tr>
			</table>';
		}
	}

	/**
	 * This returns the where-clause needed to select the user with respect flags like deleted, hidden, starttime, endtime
	 *
	 * @return	string
	 */
	function user_where_clause() {
		return  'AND pid=0 ';
	}

	/**
	 * Creates the overview information based on which analysis topics were selected.
	 *
	 * @param	array		Array of analysis topics
	 * @param	array		Array of the selected analysis topics (from session variable somewhere)
	 * @param	boolean		If set, the full trees of pages/folders are printed.
	 * @return	array		Array with accumulated HTML content.
	 */
	function ext_printOverview($uInfo, $compareFlags, $printTrees=0) {
		// Prepare for file storages and db-mount
		if ($printTrees)	{	// ... this is if we see the detailed view for a user:
			// Page tree object:
			$pagetree = t3lib_div::makeInstance(!$this->isAdmin() ? 'tx_beuser_printAllPageTree_perms' : 'tx_beuser_printAllPageTree', $this, $this->returnWebmounts());	// Here, only readable webmounts are returned (1=1)
			$pagetree->addField('perms_user', 1);
			$pagetree->addField('perms_group', 1);
			$pagetree->addField('perms_everybody', 1);
			$pagetree->addField('perms_userid', 1);
			$pagetree->addField('perms_groupid', 1);
			$pagetree->addField('editlock', 1);

			// Folder tree object:
			$foldertree = t3lib_div::makeInstance('tx_beuser_printAllFolderTree', $this);
		} else {
			// Page tree object:
			$pagetree = t3lib_div::makeInstance('tx_beuser_localPageTree', $this, $this->returnWebmounts('1=1'));	// Here, ALL webmounts are returned (1=1)

			// Folder tree object:
			$foldertree = t3lib_div::makeInstance('tx_beuser_localFolderTree', $this);
		}

		// Names for modules:
		$modNames = array(
			'web' => 'Web',
			'web_layout' => 'Page',
			'web_modules' => 'Modules',
			'web_info' => 'Info',
			'web_perms' => 'Access',
			'web_func' => 'Func',
			'web_list' => 'List',
			'web_ts' => 'Template',
			'file' => 'File',
			'file_list' => 'List',
			'file_images' => 'Images',
			'doc' => 'Doc.',
			'help' => 'Help',
			'help_about' => 'About',
			'help_quick' => 'User manual',
			'help_welcome' => 'Welcome',
			'user' => 'User',
			'user_setup' => 'Setup',
			'user_task' => 'Task center'
		);

		// Traverse the enabled analysis topics:
		$out=array();
		foreach ($uInfo as $k => $v) {
			if ($compareFlags[$k]) {
				switch($k) {
					case 'filemounts':
					case 'filestorages':
						$out[$k] = $foldertree->getBrowsableTree();
						break;
					case 'webmounts':
						// Print webmounts:
						$pagetree->addSelfId=1;
						$out[$k] = $this->ext_non_readAccessPages();	// Add HTML for non-readable webmounts (only shown when viewing details of a user - in overview/comparison ALL mounts are shown)
						$out[$k].= $pagetree->getBrowsableTree();		// Add HTML for readable webmounts.
						$this->ext_pageIdsFromMounts=implode(',', array_unique($pagetree->ids));		// List of mounted page ids
						break;
					case 'tempPath':
						$out[$k] = $GLOBALS['SOBE']->localPath($v);
						break;
					case 'pagetypes_select':
						$pageTypes = explode(',', $v);
						foreach ($pageTypes as &$vv) {
							$vv = $GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('pages', 'doktype', $vv));
						}
						unset($vv);
						$out[$k] = implode('<br />', $pageTypes);
						break;
					case 'tables_select':
					case 'tables_modify':
						$tables = explode(',', $v);
						foreach ($tables as &$vv) {
							if ($vv) {
								$vv = '<span class="nobr">'.t3lib_iconWorks::getSpriteIconForRecord($vv, array()).$GLOBALS['LANG']->sL($GLOBALS['TCA'][$vv]['ctrl']['title']).'</span>';
							}
						}
						unset($vv);
						$out[$k] = implode('<br />', $tables);
						break;
					case 'non_exclude_fields':
						$nef = explode(',', $v);
						$table='';
						$pout=array();
						foreach ($nef as $vv) {
							if ($vv) {
								list($thisTable,$field) = explode(':', $vv);
								if ($thisTable!=$table) {
									$table=$thisTable;
									t3lib_div::loadTCA($table);
									$pout[]='<span class="nobr">'.t3lib_iconWorks::getSpriteIconForRecord($table, array()).$GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']).'</span>';
								}
								if ($GLOBALS['TCA'][$table]['columns'][$field]) {
									$pout[]='<span class="nobr"> - '.rtrim($GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['columns'][$field]['label']), ':').'</span>';
								}
							}
						}
						$out[$k] = implode('<br />', $pout);
						break;
					case 'groupList':
					case 'firstMainGroup':
						$uGroups = explode(',', $v);
						$table='';
						$pout=array();
						foreach ($uGroups as $vv) {
							if ($vv) {
								$uGRow = t3lib_BEfunc::getRecord('be_groups', $vv);
								$title = t3lib_BEfunc::getRecordTitle('be_groups', $uGRow);
								$pout[] = '<tr><td nowrap="nowrap">' . t3lib_iconWorks::getSpriteIconForRecord('be_groups', $uGRow) .
									'&nbsp;' . htmlspecialchars($title) . '&nbsp;&nbsp;</td><td width="1%" nowrap="nowrap">' .
									$GLOBALS['SOBE']->elementLinks('be_groups', $uGRow) . '</td></tr>';
							}
						}
						$out[$k] = '<table border="0" cellpadding="0" cellspacing="0" width="100%">'.implode('', $pout).'</table>';
						break;
					case 'modules':
						$mods = explode(',', $v);
						$mainMod='';
						$pout=array();
						foreach ($mods as $vv) {
							if ($vv) {
								list($thisMod,$subMod) = explode('_', $vv);
								if ($thisMod!=$mainMod) {
									$mainMod=$thisMod;
									$pout[]='<span class="nobr">'.($modNames[$mainMod]?$modNames[$mainMod]:$mainMod).'</span>';
								}
								if ($subMod) {
									$pout[]='<span class="nobr"> - '.($modNames[$mainMod.'_'.$subMod]?$modNames[$mainMod.'_'.$subMod]:$mainMod.'_'.$subMod).'</span>';
								}
							}
						}
						$out[$k] = implode('<br />', $pout);
						break;
					case 'userTS':

						$tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');	// Defined global here!
						$tmpl->tt_track = 0;	// Do not log time-performance information

						$tmpl->fixedLgd=0;
						$tmpl->linkObjects=0;
						$tmpl->bType='';
						$tmpl->ext_expandAllNotes=1;
						$tmpl->ext_noPMicons=1;
						$out[$k] = $tmpl->ext_getObjTree($v, '', '', '', '', '1');
						break;
					case 'userTS_hl':
						$tsparser = t3lib_div::makeInstance('t3lib_TSparser');
						$tsparser->lineNumberOffset=0;
						$out[$k] = $tsparser->doSyntaxHighlight($v, 0, 1);
						break;
					case 'explicit_allowdeny':

						// Explode and flip values:
						$nef = array_flip(explode(',', $v));
						$pout = array();

						$theTypes = t3lib_BEfunc::getExplicitAuthFieldValues();

						// Icons:
						$icons = array(
							'ALLOW' => t3lib_iconWorks::getSpriteIcon('status-dialog-ok'),
							'DENY'  => t3lib_iconWorks::getSpriteIcon('status-dialog-error'),
						);

						// Traverse types:
						foreach ($theTypes as $tableFieldKey => $theTypeArrays) {
							if (is_array($theTypeArrays['items'])) {
								$pout[] = '<strong>'.$theTypeArrays['tableFieldLabel'].'</strong>';
								// Traverse options for this field:
								foreach ($theTypeArrays['items'] as $itemValue => $itemContent) {
									$v = $tableFieldKey.':'.$itemValue.':'.$itemContent[0];
									if (isset($nef[$v])) {
										unset($nef[$v]);
										$pout[] = $icons[$itemContent[0]].'['.$itemContent[2].'] '.$itemContent[1];
									} else {
										$pout[] = '<em style="color: #666666;">'.$icons[($itemContent[0]=='ALLOW' ? 'DENY' : 'ALLOW')].'['.$itemContent[2].'] '.$itemContent[1].'</em>';
									}
								}
								$pout[] = '';
							}
						}

						// Add remaining:
						if (count($nef)) {
							$pout = array_merge($pout, array_keys($nef));
						}

						// Implode for display:
						$out[$k] = implode('<br />', $pout);
						break;
					case 'allowed_languages':

						// Explode and flip values:
						$nef = array_flip(explode(',', $v));
						$pout = array();

						// Get languages:
						$items = t3lib_BEfunc::getSystemLanguages();

						// Traverse values:
						foreach ($items as $iCfg) {
							if (isset($nef[$iCfg[1]])) {
								unset($nef[$iCfg[1]]);
								if (strpos($iCfg[2], '.gif') === FALSE) {
									$icon = t3lib_iconWorks::getSpriteIcon($iCfg[2]) . '&nbsp;';
								} elseif (strlen($iCfg[2])) {
									$icon = '<img '.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'], 'gfx/'.$iCfg[2]).' class="absmiddle" style="margin-right: 5px;" alt="" />';
								} else {
									$icon = '';
								}
								$pout[] = $icon.$iCfg[0];
							}
						}

						// Add remaining:
						if (count($nef)) {
							$pout = array_merge($pout, array_keys($nef));
						}

						// Implode for display:
						$out[$k] = implode('<br />', $pout);
						break;
					case 'workspace_perms':
						$out[$k] = implode('<br/>', explode(', ', t3lib_BEfunc::getProcessedValue('be_users', 'workspace_perms', $v)));
						break;
					case 'workspace_membership':
						$out[$k] = implode('<br/>', $this->ext_workspaceMembership());
						break;
					case 'custom_options':

						// Explode and flip values:
						$nef = array_flip(explode(',', $v));
						$pout = array();

						// Initialize:
						$customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];
						if (is_array($customOptions)) {
							foreach ($customOptions as $coKey => $coValue) {
								if (is_array($coValue['items'])) {
									// Traverse items:
									foreach ($coValue['items'] as $itemKey => $itemCfg) {
										$v = $coKey.':'.$itemKey;
										if (isset($nef[$v])) {
											unset($nef[$v]);
											$pout[] = $GLOBALS['LANG']->sl($coValue['header']).' / '.$GLOBALS['LANG']->sl($itemCfg[0]);
										}
									}
								}
							}
						}

						// Add remaining:
						if (count($nef)) {
							$pout = array_merge($pout, array_keys($nef));
						}

						// Implode for display:
						$out[$k] = implode('<br />', $pout);
						break;
				}
			}
		}
		return $out;
	}

	/**
	 * Get HTML code for the pages which were mounted, but NOT readable!
	 *
	 * @return	string		HTML code.
	 */
	function ext_getReadableButNonmounted() {

		// List of page id mounts which ARE mounted (and should therefore not be selected)
		if (!$this->ext_pageIdsFromMounts) {
			$this->ext_pageIdsFromMounts=0;
		}

		// User and group names:
		$be_user_Array = t3lib_BEfunc::getUserNames();
		$be_group_Array = t3lib_BEfunc::getGroupNames();

		// Create query:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'pid,uid,title,doktype,perms_user,perms_group,perms_everybody,perms_userid,perms_groupid'.(t3lib_extMgm::isLoaded('cms')?',media,layout,hidden,starttime,endtime,fe_group,extendToSubpages':''),
			'pages',
			'uid NOT IN ('.$this->ext_pageIdsFromMounts.') AND '.$this->getPagePermsClause(1).t3lib_BEfunc::deleteClause('pages')
		);
		$dat = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$dat[] = array(
				'row'=>$row,
				'HTML'=>t3lib_iconWorks::getSpriteIconForRecord('pages', $row, array('title' => '[' . $row['uid'] . ']'))
			);
		}
		$pp = t3lib_div::makeInstance('tx_beuser_printAllPageTree_perms', $this);
		return $pp->printTree($dat, 1);
	}

	/**
	 * Print a set of permissions
	 *
	 * @param	integer		The permissions integer.
	 * @return	string		HTML formatted.
	 */
	function ext_printPerms($int) {
		$str='';
		$str.= (($int&1)?'*':'<font color="red">x</font>');
		$str.= (($int&16)?'*':'<font color="red">x</font>');
		$str.= (($int&2)?'*':'<font color="red">x</font>');
		$str.= (($int&4)?'*':'<font color="red">x</font>');
		$str.= (($int&8)?'*':'<font color="red">x</font>');

		return '<strong style="color:green;">'.$str.'</strong>';
	}

	/**
	 * returns the permissions for a group based of the perms_groupid of $row. If the $row[perms_groupid] equals the $firstGroup[uid] then the function returns perms_everybody OR'ed with perms_group, else just perms_everybody
	 *
	 * @param	array		Page record.
	 * @param	array		First-group record.
	 * @return	integer		Permissions.
	 */
	function ext_groupPerms($row, $firstGroup) {
		if (is_array($row)) {
			$out=intval($row['perms_everybody']);
			if ($row['perms_groupid'] && $firstGroup['uid']==$row['perms_groupid']) {
				$out|= intval($row['perms_group']);
			}
			return $out;
		}
	}

	/**
	 * Creates uInfo array for the user.
	 *
	 * @param	array		Might contain array where keys/values indicate whether to render a certain value
	 * @return	array		Array with the information of the user for each analysis topic.
	 */
	function ext_compileUserInfoForHash($filter=NULL) {
		$uInfo=array();
		$renderAll = !is_array($filter);

		// Filemounts:
		if ($renderAll || $filter['filemounts']) {
			$uInfo['filemounts'] = $this->ext_uniqueAndSortList(implode(',', array_keys($this->groupData['filemounts'])));
		}

		// DBmounts:
		if ($renderAll || $filter['webmounts']) {
			$uInfo['webmounts'] = $this->ext_uniqueAndSortList($this->groupData['webmounts']);
		}

		// Sharing Upload Folder
		if ($renderAll || $filter['tempPath']) {
			$fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
			$fileProcessor->init($this->groupData['filemounts'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
			$uInfo['tempPath'] = $fileProcessor->findTempFolder();	// The closest TEMP-path is found
		}

		// First Main Group:
		if ($renderAll || $filter['firstMainGroup']) {
			$uInfo['firstMainGroup'] = $this->firstMainGroup;
		}

		// Group List:
		if ($renderAll || $filter['groupList']) {
			$uInfo['groupList'] = $this->groupList;	// This gives a list that shows in which order the groups are processed. This may result in a list of groups which is similar to that of another user regarding which group but not the order of groups. For now, I believe it's most usefull to let separate orders of groups appear as different group settings for a user.
		}

		// Page Types:
		if ($renderAll || $filter['pagetypes_select']) {
			$uInfo['pagetypes_select'] = $this->ext_uniqueAndSortList($this->groupData['pagetypes_select']);
		}

		// Tables select:
		if ($renderAll || $filter['tables_select']) {
			$uInfo['tables_select'] = $this->ext_uniqueAndSortList($this->groupData['tables_select'].','.$this->groupData['tables_modify']);
		}

		// Tables modify:
		if ($renderAll || $filter['tables_modify']) {
			$uInfo['tables_modify'] = $this->ext_uniqueAndSortList($this->groupData['tables_modify']);
		}

		// Non-exclude fields:
		if ($renderAll || $filter['non_exclude_fields']) {
			$uInfo['non_exclude_fields'] = $this->ext_uniqueAndSortList($this->groupData['non_exclude_fields']);
		}

		// Explicit Allow/Deny:
		if ($renderAll || $filter['explicit_allowdeny']) {
			$uInfo['explicit_allowdeny'] = $this->ext_uniqueAndSortList($this->groupData['explicit_allowdeny']);
		}

		// Limit to languages:
		if ($renderAll || $filter['allowed_languages']) {
			$uInfo['allowed_languages'] = $this->ext_uniqueAndSortList($this->groupData['allowed_languages']);
		}

		// Workspace permissions
		if ($renderAll || $filter['workspace_perms']) {
			$uInfo['workspace_perms'] = $this->ext_uniqueAndSortList($this->groupData['workspace_perms']);
		}

		// Workspace membership
		if ($renderAll || $filter['workspace_membership']) {
			$uInfo['workspace_membership'] = $this->ext_workspaceMembership();
		}

		// Custom options:
		if ($renderAll || $filter['custom_options']) {
			$uInfo['custom_options'] = $this->ext_uniqueAndSortList($this->groupData['custom_options']);
		}

		// Modules:
		if ($renderAll || $filter['modules']) {
			$uInfo['modules'] = $this->ext_uniqueAndSortList($this->groupData['modules']);
		}

		// User TS:
		$this->ext_ksortArrayRecursive($this->userTS);
		if ($renderAll || $filter['userTS']) {
			$uInfo['userTS'] = $this->userTS;
		}

		if ($renderAll || $filter['userTS_hl']) {
			$uInfo['userTS_hl'] = $this->userTS_text;
		}

		return $uInfo;
	}

	/**
	 * Sorts a commalist of values and removes duplicates.
	 *
	 * @param	string		Commalist.
	 * @return	string		Sorted, unique commalist.
	 */
	function ext_uniqueAndSortList($list) {
		$uList=t3lib_div::trimExplode(',', $list, 1);
		sort($uList);
		$uList=array_unique($uList);
		$uList=implode(',', $uList);
		return $uList;
	}

	/**
	 * Key sort input array recursively.
	 *
	 * @param	array		Multidimensional array (value by reference!)
	 * @return	void
	 */
	function ext_ksortArrayRecursive(&$arr) {
		krsort($arr);
		foreach ($arr as &$v) {
			if (is_array($v)) {
				$this->ext_ksortArrayRecursive($v);
			}
		}
		unset($v);
	}

	/**
	 * Returns all workspaces that are accessible for the BE_USER
	 *
	 * @return	array	with key / value pairs of available workspaces (filtered by BE_USER check)
	 */
	function ext_workspaceMembership() {
		// Create accessible workspace arrays:
		$options = array();
		if ($this->checkWorkspace(array('uid' => 0))) {
			$options[0] = '0: ' . $GLOBALS['LANG']->getLL('live', TRUE);
		}

		// Add custom workspaces (selecting all, filtering by BE_USER check):
		if (t3lib_extMgm::isLoaded('workspaces')) {
			$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,adminusers,members,reviewers,db_mountpoints', 'sys_workspace', 'pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'), '', 'title');
			if (count($workspaces)) {
				foreach ($workspaces as $rec) {
					if ($this->checkWorkspace($rec)) {
						$options[$rec['uid']] = $rec['uid'].': '.htmlspecialchars($rec['title']);

						// Check if all mount points are accessible, otherwise show error:
						if (trim($rec['db_mountpoints'])!=='') {
							$mountPoints = t3lib_div::intExplode(',', $this->workspaceRec['db_mountpoints'], 1);
							foreach ($mountPoints as $mpId) {
								if (!$this->isInWebMount($mpId, '1=1')) {
									$options[$rec['uid']].= '<br> \- ' . $GLOBALS['LANG']->getLL('notAccessible', TRUE) . ' ' . $mpId;
								}
							}
						}
					}
				}
			}
		}

		return $options;
	}
}
?>