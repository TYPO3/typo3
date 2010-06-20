<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Backend User Administration Module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  129: class localPageTree extends t3lib_browseTree
 *  140:     function localPageTree($BE_USER,$WEBMOUNTS='')
 *  154:     function ext_permsC()
 *  165:     function wrapTitle($str,$row)
 *  177:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  188:     function wrapIcon($icon,$row)
 *  201:     function initializePositionSaving()
 *
 *
 *  222: class printAllPageTree extends localPageTree
 *  231:     function ext_permsC()
 *  243:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  254:     function wrapIcon($icon,$row)
 *
 *
 *  279: class printAllPageTree_perms extends printAllPageTree
 *  288:     function printTree($treeArr='',$printPath=0)
 *  331:     function ext_printPerms($int)
 *  349:     function ext_groupPerms($row,$firstGroup)
 *
 *
 *  377: class localFolderTree extends t3lib_folderTree
 *  388:     function localFolderTree($BE_USER,$FILEMOUNTS='')
 *  403:     function wrapTitle($str,$row)
 *  415:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *  426:     function wrapIcon($icon,$row)
 *  439:     function initializePositionSaving()
 *
 *
 *  463: class printAllFolderTree extends localFolderTree
 *  475:     function PM_ATagWrap($icon,$cmd,$bMark='')
 *
 *
 *  497: class local_beUserAuth extends t3lib_beUserAuth
 *  509:     function returnWebmounts($pClause='')
 *  533:     function ext_non_readAccessPages()
 *  556:     function user_where_clause()
 *  568:     function ext_printOverview($uInfo,$compareFlags,$printTrees=0)
 *  838:     function ext_getReadableButNonmounted()
 *  873:     function ext_printPerms($int)
 *  891:     function ext_groupPerms($row,$firstGroup)
 *  907:     function ext_compileUserInfoForHash($filter=NULL)
 * 1007:     function ext_uniqueAndSortList($list)
 * 1021:     function ext_ksortArrayRecursive(&$arr)
 * 1034:     function ext_workspaceMembership()
 *
 *
 * 1088: class SC_mod_tools_be_user_index
 * 1100:     function init()
 * 1128:     function menuConfig()
 * 1149:     function main()
 * 1185:     function printContent()
 *
 *              SECTION: OTHER FUNCTIONS:
 * 1207:     function compareUsers($compareFlags)
 * 1394:     function linkUser($str,$rec)
 * 1405:     function elementLinks($table,$row)
 * 1436:     function initUsers()
 * 1456:     function localPath($str)
 * 1468:     function switchUser($switchUser)
 *
 * TOTAL FUNCTIONS: 39
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once (PATH_typo3.'class.alt_menu_functions.inc');

$GLOBALS['LANG']->includeLLFile('EXT:beuser/mod/locallang.xml');

$BE_USER->modAccess($MCONF,1);






/**
 * Base Extension class for printing a page tree (non-browsable though)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
 class localPageTree extends t3lib_browseTree {
	var $expandFirst=0;
	var $expandAll=0;

	/**
	 * Local backend user (not the GLOBALS[] backend user!!)
	 *
	 * @var t3lib_beUserAuth
	 */
	var $BE_USER;

	/**
	 * Constructor for the local page tree.
	 *
	 * @param	object		Local backend user (not the GLOBALS[] backend user!!)
	 * @param	array		Webmounts for the backend user.
	 * @return	void
	 */
	function localPageTree($BE_USER,$WEBMOUNTS='')	{
		$this->init();

		$this->BE_USER = $BE_USER;
		$this->MOUNTS = $WEBMOUNTS;
		$this->clause = $this->ext_permsC();	// Notice, this clause does NOT filter out un-readable pages. This is the POINT since this class is ONLY used for the main overview where ALL is shown! Otherwise "AND '.$this->BE_USER->getPagePermsClause(1).'" should be added.
		$this->orderByFields = 'sorting';
	}

	/**
	 * Return select permissions.
	 *
	 * @return	string		WHERE query part.
	 */
	function ext_permsC()	{
		return '';
	}

	/**
	 * Wraps the title.
	 *
	 * @param	string		[See parent]
	 * @param	array		[See parent]
	 * @return	string
	 */
	function wrapTitle($str,$row)	{
		return $str;
	}

	/**
	 * Wraps the plus/minus icon - in this case we just return blank which means we STRIP AWAY the plus/minus icon!
	 *
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @return	string
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		return '';
	}

	/**
	 * Wrapping the icon of the element/page. Normally a click menu is wrapped around the icon, but in this case only a title parameter is set.
	 *
	 * @param	string		Icon image tag.
	 * @param	array		Row.
	 * @return	string		Icon with title attribute added.
	 */
	function wrapIcon($icon,$row)	{
			// Add title attribute to input icon tag
		$title = '['.$row['uid'].'] '.t3lib_BEfunc::getRecordPath($row['uid'],'',15);
		$theIcon = $this->addTagAttributes($icon,($this->titleAttrib ? $this->titleAttrib.'="'.htmlspecialchars($title).'"' : '').' border="0"');

		return $theIcon;
	}

	/**
	 * This will make sure that no position data is acquired from the BE_USER uc variable.
	 *
	 * @return	void
	 */
	function initializePositionSaving()	{
		$this->stored=array();
	}
}










/**
 * Extension class for printing a page tree: All pages of a mount point.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class printAllPageTree extends localPageTree {
	var $expandFirst=1;
	var $expandAll=1;

	/**
	 * Return select permissions.
	 *
	 * @return	string		WHERE query part.
	 */
	function ext_permsC()	{
		return ' AND '.$this->BE_USER->getPagePermsClause(1);
	}

	/**
	 * Returns the plus/minus icon.
	 *
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @return	string
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		return $icon;
	}

	/**
	 * Wrapping the icon of the element/page. Normally a click menu is wrapped around the icon, but in this case only a title parameter is set.
	 *
	 * @param	string		Icon image tag.
	 * @param	array		Row.
	 * @return	string		Icon with title attribute added.
	 */
	function wrapIcon($icon,$row)	{
			// Add title attribute to input icon tag
		$title = '['.$row['uid'].']';
		$theIcon = $this->addTagAttributes($icon,($this->titleAttrib ? $this->titleAttrib.'="'.htmlspecialchars($title).'"' : '').' border="0"');

		return $theIcon;
	}
}










/**
 * Extension class for printing a page tree: Printing all pages, with permissions.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class printAllPageTree_perms extends printAllPageTree {

	/**
	 * Print the tree of pages.
	 *
	 * @param	array		The tree items
	 * @param	boolean		If set, the path of the pages in the tree is printed (only done for pages outside of mounts).
	 * @return	string		HTML content.
	 */
	function printTree($treeArr='',$printPath=0)	{
		$titleLen=intval($this->BE_USER->uc['titleLen']);

		$be_user_Array = t3lib_BEfunc::getUserNames();
		$be_group_Array = t3lib_BEfunc::getGroupNames();
		$lines=array();
		$lines[]='<tr class="bgColor5">
			<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('pageTitle', true) . '</strong></td>
			' . ($printPath?'<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('path', true) . '</strong></td>':'') . '
			<td nowrap="nowrap" colspan="2"><strong>' . $GLOBALS['LANG']->getLL('user', true) . '</strong></td>
			<td nowrap="nowrap" colspan="2"><strong>' . $GLOBALS['LANG']->getLL('group', true) . ' &nbsp;</strong></td>
			<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('everybody', true) . ' &nbsp;</strong></td>
			<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('thisUser', true) . ' &nbsp;</strong></td>
			<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('mainGroup', true) . '</strong></td>
		</tr>';

		if (!is_array($treeArr)) {
			$treeArr = $this->tree;
		}
		foreach ($treeArr as $v) {
			$col1 = ' bgcolor="'.t3lib_div::modifyHtmlColor($GLOBALS['SOBE']->doc->bgColor4,+10,+10,+10).'"';
			$row = $v['row'];
			$title = htmlspecialchars(t3lib_div::fixed_lgd_cs($row['title'],$this->BE_USER->uc['titleLen']));
			$lines[]='<tr class="bgColor4">
				<td nowrap="nowrap">'.$v['HTML'].$title.' &nbsp;</td>
				'.($printPath?'<td nowrap="nowrap">'.htmlspecialchars(t3lib_BEfunc::getRecordPath ($row['pid'],'',15)).' &nbsp;</td>':'').'
				<td nowrap="nowrap"'.$col1.'>'.$be_user_Array[$row['perms_userid']]['username'].' &nbsp;</td>
				<td nowrap="nowrap"'.$col1.'>'.$this->ext_printPerms($row['perms_user']).' &nbsp;</td>
				<td nowrap="nowrap">'.$be_group_Array[$row['perms_groupid']]['title'].' &nbsp;</td>
				<td nowrap="nowrap">'.$this->ext_printPerms($row['perms_group']).' &nbsp;</td>
				<td nowrap="nowrap" align="center" '.$col1.'>'.$this->ext_printPerms($row['perms_everybody']).' &nbsp;</td>
				<td nowrap="nowrap" align="center">' . ($row['editlock'] ? t3lib_iconWorks::getSpriteIcon('status-warning-in-use', array('title' => $GLOBALS['LANG']->getLL('editLock', true))) : $this->ext_printPerms($this->BE_USER->calcPerms($row))) . ' &nbsp;</td>
				<td nowrap="nowrap" align="center">'.$this->ext_printPerms($this->ext_groupPerms($row,$be_group_Array[$this->BE_USER->firstMainGroup])).' &nbsp;</td>
			</tr>';
		}
		return '<table border="0" cellpadding="0" cellspacing="0">'.implode('',$lines).'</table>';
	}

	/**
	 * Print a set of permissions
	 *
	 * @param	integer		The permissions integer.
	 * @return	string		HTML formatted.
	 */
	function ext_printPerms($int)	{
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
	function ext_groupPerms($row,$firstGroup)	{
		if (is_array($row))	{
			$out=intval($row['perms_everybody']);
			if ($row['perms_groupid'] && $firstGroup['uid']==$row['perms_groupid'])	{
				$out|= intval($row['perms_group']);
			}
			return $out;
		}
	}
}











/**
 * Base Extension class for printing a folder tree (non-browsable though)
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
 class localFolderTree extends t3lib_folderTree {
	var $expandFirst=0;
	var $expandAll=0;

	/**
	 * Local backend user (not the GLOBALS[] backend user!!)
	 *
	 * @var t3lib_beUserAuth
	 */
	var $BE_USER;

	/**
	 * Constructor for the local folder tree.
	 *
	 * @param	object		Local backend user (not the GLOBALS[] backend user!!)
	 * @param	array		Filemounts for the backend user.
	 * @return	void
	 */
	function localFolderTree($BE_USER,$FILEMOUNTS='')	{
		$this->init();

		$this->BE_USER = $BE_USER;
		$this->MOUNTS = $FILEMOUNTS;
		$this->clause = '';	// Notice, this clause does NOT filter out un-readable pages. This is the POINT since this class is ONLY used for the main overview where ALL is shown! Otherwise "AND '.$this->BE_USER->getPagePermsClause(1).'" should be added.
	}

	/**
	 * Wraps the title.
	 *
	 * @param	string		[See parent]
	 * @param	array		[See parent]
	 * @return	string
	 */
	function wrapTitle($str,$row)	{
		return $str;
	}

	/**
	 * Wraps the plus/minus icon - in this case we just return blank which means we STRIP AWAY the plus/minus icon!
	 *
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @return	string
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		return '';
	}

	/**
	 * Wrapping the icon of the element/page. Normally a click menu is wrapped around the icon, but in this case only a title parameter is set.
	 *
	 * @param	string		Icon image tag.
	 * @param	array		Row.
	 * @return	string		Icon with title attribute added.
	 */
	function wrapIcon($icon,$row)	{
			// Add title attribute to input icon tag
		$title = $GLOBALS['SOBE']->localPath($row['path']);
		$theIcon = $this->addTagAttributes($icon,($this->titleAttrib ? $this->titleAttrib.'="'.htmlspecialchars($title).'"' : ''));

		return $theIcon;
	}

	/**
	 * This will make sure that no position data is acquired from the BE_USER uc variable.
	 *
	 * @return	void
	 */
	function initializePositionSaving()	{
		$this->stored=array();
	}
}













/**
 * Extension class for printing a folder tree: All folders
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class printAllFolderTree extends localFolderTree {
	var $expandFirst=1;
	var $expandAll=1;

	/**
	 * Wraps the plus/minus icon - in this case we just return blank which means we STRIP AWAY the plus/minus icon!
	 *
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @param	string		[See parent]
	 * @return	string
	 */
	function PM_ATagWrap($icon,$cmd,$bMark='')	{
		return $icon;
	}
}











/**
 * Extension class of beuserauth class.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class local_beUserAuth extends t3lib_beUserAuth {
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
	function returnWebmounts($pClause='')	{

			// Get array of webmounts:
		$webmounts = (string)($this->groupData['webmounts'])!='' ? explode(',',$this->groupData['webmounts']) : Array();

			// Get select clause:
		$pClause=$pClause?$pClause:$this->getPagePermsClause(1);

			// Traverse mounts, check if they are readable:
		foreach ($webmounts as $k => $id)	{
			$rec=t3lib_BEfunc::getRecord('pages',$id,'*',' AND '.$pClause);
			if (!is_array($rec))	{
				$this->ext_non_readAccessPageArray[$id]=t3lib_BEfunc::getRecord('pages',$id);
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
	function ext_non_readAccessPages()	{
		$lines=array();

		foreach ($this->ext_non_readAccessPageArray as $pA) {
			if ($pA) {
				$lines[] = t3lib_BEfunc::getRecordPath($pA['uid'],'',15);
			}
		}
		if (count($lines)) {
			return '<table bgcolor="red" border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td align="center"><font color="white"><strong>' . $GLOBALS['LANG']->getLL('noReadAccess', true) . '</strong></font></td>
				</tr>
				<tr>
					<td>'.implode('</td></tr><tr><td>',$lines).'</td>
				</tr>
			</table>';
		}
	}

	/**
	 * This returns the where-clause needed to select the user with respect flags like deleted, hidden, starttime, endtime
	 *
	 * @return	string
	 */
	function user_where_clause()	{
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
	function ext_printOverview($uInfo,$compareFlags,$printTrees=0)	{
			// Prepare for filemount and db-mount
		if ($printTrees)	{	// ... this is if we see the detailed view for a user:
				// Page tree object:
			$pagetree = t3lib_div::makeInstance(!$this->isAdmin() ? 'printAllPageTree_perms' : 'printAllPageTree', $this, $this->returnWebmounts());	// Here, only readable webmounts are returned (1=1)
			$pagetree->addField('perms_user',1);
			$pagetree->addField('perms_group',1);
			$pagetree->addField('perms_everybody',1);
			$pagetree->addField('perms_userid',1);
			$pagetree->addField('perms_groupid',1);
			$pagetree->addField('editlock',1);

				// Folder tree object:
			$foldertree = t3lib_div::makeInstance('printAllFolderTree', $this, $this->returnFilemounts());
		} else {
				// Page tree object:
			$pagetree = t3lib_div::makeInstance('localPageTree', $this, $this->returnWebmounts('1=1'));	// Here, ALL webmounts are returned (1=1)

				// Folder tree object:
			$foldertree = t3lib_div::makeInstance('localFolderTree', $this, $this->returnFilemounts());
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
		foreach ($uInfo as $k => $v)	{
			if ($compareFlags[$k])	{
				switch($k)	{
					case 'filemounts':
						$out[$k] = $foldertree->getBrowsableTree();
					break;
					case 'webmounts':
							// Print webmounts:
						$pagetree->addSelfId=1;
						$out[$k] = $this->ext_non_readAccessPages();	// Add HTML for non-readable webmounts (only shown when viewing details of a user - in overview/comparison ALL mounts are shown)
						$out[$k].= $pagetree->getBrowsableTree();		// Add HTML for readable webmounts.
						$this->ext_pageIdsFromMounts=implode(',',array_unique($pagetree->ids));		// List of mounted page ids
					break;
					case 'tempPath':
						$out[$k] = $GLOBALS['SOBE']->localPath($v);
					break;
					case 'pagetypes_select':
						$pageTypes = explode(',',$v);
						foreach ($pageTypes as &$vv) {
							$vv = $GLOBALS['LANG']->sL(t3lib_BEfunc::getLabelFromItemlist('pages','doktype',$vv));
						}
						$out[$k] = implode('<br />',$pageTypes);
					break;
					case 'tables_select':
					case 'tables_modify':
						$tables = explode(',',$v);
						foreach ($tables as &$vv) {
							if ($vv) {
								$vv = '<span class="nobr">'.t3lib_iconWorks::getSpriteIconForRecord($vv,array()).$GLOBALS['LANG']->sL($GLOBALS['TCA'][$vv]['ctrl']['title']).'</span>';
							}
						}
						$out[$k] = implode('<br />',$tables);
					break;
					case 'non_exclude_fields':
						$nef = explode(',',$v);
						$table='';
						$pout=array();
						foreach ($nef as $vv) {
							if ($vv) {
								list($thisTable,$field) = explode(':',$vv);
								if ($thisTable!=$table)	{
									$table=$thisTable;
									t3lib_div::loadTCA($table);
									$pout[]='<span class="nobr">'.t3lib_iconWorks::getSpriteIconForRecord($table,array()).$GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']).'</span>';
								}
								if ($GLOBALS['TCA'][$table]['columns'][$field])	{
									$pout[]='<span class="nobr"> - '.rtrim($GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['columns'][$field]['label']), ':').'</span>';
								}
							}
						}
						$out[$k] = implode('<br />',$pout);
					break;
					case 'groupList':
					case 'firstMainGroup':
						$uGroups = explode(',',$v);
						$table='';
						$pout=array();
						foreach ($uGroups as $vv) {
							if ($vv) {
								$uGRow = t3lib_BEfunc::getRecord('be_groups',$vv);
								$pout[]='<tr><td nowrap="nowrap">'.t3lib_iconWorks::getSpriteIconForRecord('be_groups',$uGRow).'&nbsp;'.htmlspecialchars($uGRow['title']).'&nbsp;&nbsp;</td><td width=1% nowrap="nowrap">'.$GLOBALS['SOBE']->elementLinks('be_groups',$uGRow).'</td></tr>';
							}
						}
						$out[$k] = '<table border="0" cellpadding="0" cellspacing="0" width="100%">'.implode('',$pout).'</table>';
					break;
					case 'modules':
						$mods = explode(',',$v);
						$mainMod='';
						$pout=array();
						foreach ($mods as $vv) {
							if ($vv) {
								list($thisMod,$subMod) = explode('_',$vv);
								if ($thisMod!=$mainMod)	{
									$mainMod=$thisMod;
									$pout[]='<span class="nobr">'.($modNames[$mainMod]?$modNames[$mainMod]:$mainMod).'</span>';
								}
								if ($subMod)	{
									$pout[]='<span class="nobr"> - '.($modNames[$mainMod.'_'.$subMod]?$modNames[$mainMod.'_'.$subMod]:$mainMod.'_'.$subMod).'</span>';
								}
							}
						}
						$out[$k] = implode('<br />',$pout);
					break;
					case 'userTS':

						$tmpl = t3lib_div::makeInstance('t3lib_tsparser_ext');	// Defined global here!
						$tmpl->tt_track = 0;	// Do not log time-performance information

						$tmpl->fixedLgd=0;
						$tmpl->linkObjects=0;
						$tmpl->bType='';
						$tmpl->ext_expandAllNotes=1;
						$tmpl->ext_noPMicons=1;
						$out[$k] = $tmpl->ext_getObjTree($v,'','','','','1');
					break;
					case 'userTS_hl':
						$tsparser = t3lib_div::makeInstance('t3lib_TSparser');
						$tsparser->lineNumberOffset=0;
						$out[$k] = $tsparser->doSyntaxHighlight($v,0,1);
					break;
					case 'explicit_allowdeny':

							// Explode and flip values:
						$nef = array_flip(explode(',',$v));
						$pout = array();

						$theTypes = t3lib_BEfunc::getExplicitAuthFieldValues();

								// Icons:
						$icons = array(
							'ALLOW' => t3lib_iconWorks::getSpriteIcon('status-dialog-ok'),
							'DENY'  => t3lib_iconWorks::getSpriteIcon('status-dialog-error'),
						);

							// Traverse types:
						foreach ($theTypes as $tableFieldKey => $theTypeArrays)	{
							if (is_array($theTypeArrays['items']))	{
								$pout[] = '<strong>'.$theTypeArrays['tableFieldLabel'].'</strong>';
									// Traverse options for this field:
								foreach ($theTypeArrays['items'] as $itemValue => $itemContent)	{
									$v = $tableFieldKey.':'.$itemValue.':'.$itemContent[0];
									if (isset($nef[$v]))	{
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
						if (count($nef))	{
							$pout = array_merge($pout, array_keys($nef));
						}

							// Implode for display:
						$out[$k] = implode('<br />',$pout);
					break;
					case 'allowed_languages':

							// Explode and flip values:
						$nef = array_flip(explode(',',$v));
						$pout = array();

							// Get languages:
						$items = t3lib_BEfunc::getSystemLanguages();

							// Traverse values:
						foreach ($items as $iCfg)	{
							if (isset($nef[$iCfg[1]]))	{
								unset($nef[$iCfg[1]]);
								if (strlen($iCfg[2]))	{
									$icon = '<img '.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/'.$iCfg[2]).' class="absmiddle" style="margin-right: 5px;" alt="" />';
								} else {
									$icon = '';
								}
								$pout[] = $icon.$iCfg[0];
							}
						}

							// Add remaining:
						if (count($nef))	{
							$pout = array_merge($pout, array_keys($nef));
						}

							// Implode for display:
						$out[$k] = implode('<br />',$pout);
					break;
					case 'workspace_perms':
						$out[$k] = implode('<br/>',explode(', ',t3lib_BEfunc::getProcessedValue('be_users','workspace_perms',$v)));
					break;
					case 'workspace_membership':
						$out[$k] = implode('<br/>',$this->ext_workspaceMembership());
					break;
					case 'custom_options':

							// Explode and flip values:
						$nef = array_flip(explode(',',$v));
						$pout = array();

							// Initialize:
						$customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];
						if (is_array($customOptions))	{
							foreach ($customOptions as $coKey => $coValue) {
								if (is_array($coValue['items']))	{
										// Traverse items:
									foreach ($coValue['items'] as $itemKey => $itemCfg)	{
										$v = $coKey.':'.$itemKey;
										if (isset($nef[$v]))	{
											unset($nef[$v]);
											$pout[] = $GLOBALS['LANG']->sl($coValue['header']).' / '.$GLOBALS['LANG']->sl($itemCfg[0]);
										}
									}
								}
							}
						}

							// Add remaining:
						if (count($nef))	{
							$pout = array_merge($pout, array_keys($nef));
						}

							// Implode for display:
						$out[$k] = implode('<br />',$pout);
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
	function ext_getReadableButNonmounted()	{

			// List of page id mounts which ARE mounted (and should therefore not be selected)
		if (!$this->ext_pageIdsFromMounts)	{
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
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$dat[] = array(
				'row'=>$row,
				'HTML'=>t3lib_iconWorks::getSpriteIconForRecord('pages',$row,array('title'=>'['.$row['uid'].']'))
			);
		}
		$pp = t3lib_div::makeInstance('printAllPageTree_perms', $this);
		return $pp->printTree($dat,1);
	}

	/**
	 * Print a set of permissions
	 *
	 * @param	integer		The permissions integer.
	 * @return	string		HTML formatted.
	 */
	function ext_printPerms($int)	{
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
	function ext_groupPerms($row,$firstGroup)	{
		if (is_array($row))	{
			$out=intval($row['perms_everybody']);
			if ($row['perms_groupid'] && $firstGroup['uid']==$row['perms_groupid'])	{
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
	function ext_compileUserInfoForHash($filter=NULL)	{
		$uInfo=array();
		$renderAll = !is_array($filter);

			// Filemounts:
		if ($renderAll || $filter['filemounts'])	{
			$uInfo['filemounts'] = $this->ext_uniqueAndSortList(implode(',',array_keys($this->groupData['filemounts'])));
		}

			// DBmounts:
		if ($renderAll || $filter['webmounts'])	{
			$uInfo['webmounts'] = $this->ext_uniqueAndSortList($this->groupData['webmounts']);
		}

			// Sharing Upload Folder
		if ($renderAll || $filter['tempPath'])	{
			$fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
			$fileProcessor->init($this->groupData['filemounts'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
			$uInfo['tempPath'] = $fileProcessor->findTempFolder();	// The closest TEMP-path is found
		}

			// First Main Group:
		if ($renderAll || $filter['firstMainGroup'])	{
			$uInfo['firstMainGroup'] = $this->firstMainGroup;
		}

			// Group List:
		if ($renderAll || $filter['groupList'])	{
			$uInfo['groupList'] = $this->groupList;	// This gives a list that shows in which order the groups are processed. This may result in a list of groups which is similar to that of another user regarding which group but not the order of groups. For now, I believe it's most usefull to let separate orders of groups appear as different group settings for a user.
		}

			// Page Types:
		if ($renderAll || $filter['pagetypes_select'])	{
			$uInfo['pagetypes_select'] = $this->ext_uniqueAndSortList($this->groupData['pagetypes_select']);
		}

			// Tables select:
		if ($renderAll || $filter['tables_select'])	{
			$uInfo['tables_select'] = $this->ext_uniqueAndSortList($this->groupData['tables_select'].','.$this->groupData['tables_modify']);
		}

			// Tables modify:
		if ($renderAll || $filter['tables_modify'])	{
			$uInfo['tables_modify'] = $this->ext_uniqueAndSortList($this->groupData['tables_modify']);
		}

			// Non-exclude fields:
		if ($renderAll || $filter['non_exclude_fields'])	{
			$uInfo['non_exclude_fields'] = $this->ext_uniqueAndSortList($this->groupData['non_exclude_fields']);
		}

			// Explicit Allow/Deny:
		if ($renderAll || $filter['explicit_allowdeny'])	{
			$uInfo['explicit_allowdeny'] = $this->ext_uniqueAndSortList($this->groupData['explicit_allowdeny']);
		}

			// Limit to languages:
		if ($renderAll || $filter['allowed_languages'])	{
			$uInfo['allowed_languages'] = $this->ext_uniqueAndSortList($this->groupData['allowed_languages']);
		}

			// Workspace permissions
		if ($renderAll || $filter['workspace_perms'])	{
			$uInfo['workspace_perms'] = $this->ext_uniqueAndSortList($this->groupData['workspace_perms']);
		}

			// Workspace membership
		if ($renderAll || $filter['workspace_membership'])	{
			$uInfo['workspace_membership'] = $this->ext_workspaceMembership();
		}

			// Custom options:
		if ($renderAll || $filter['custom_options'])	{
			$uInfo['custom_options'] = $this->ext_uniqueAndSortList($this->groupData['custom_options']);
		}

			// Modules:
		if ($renderAll || $filter['modules'])	{
			$uInfo['modules'] = $this->ext_uniqueAndSortList($this->groupData['modules']);
		}

			// User TS:
		$this->ext_ksortArrayRecursive($this->userTS);
		if ($renderAll || $filter['userTS'])	{
			$uInfo['userTS'] = $this->userTS;
		}

		if ($renderAll || $filter['userTS_hl'])	{
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
	function ext_uniqueAndSortList($list)	{
		$uList=t3lib_div::trimExplode(',',$list,1);
		sort($uList);
		$uList=array_unique($uList);
		$uList=implode(',',$uList);
		return $uList;
	}

	/**
	 * Key sort input array recursively.
	 *
	 * @param	array		Multidimensional array (value by reference!)
	 * @return	void
	 */
	function ext_ksortArrayRecursive(&$arr)	{
		krsort($arr);
		foreach ($arr as &$v) {
			if (is_array($v)) {
				$this->ext_ksortArrayRecursive($v);
			}
		}
	}

	/**
	 * Returns all workspaces that are accessible for the BE_USER
	 *
	 * @return	array	with key / value pairs of available workspaces (filtered by BE_USER check)
	 */
	function ext_workspaceMembership()	{
			// Create accessible workspace arrays:
		$options = array();
		if ($this->checkWorkspace(array('uid' => 0)))	{
			$options[0] = '0: ' . $GLOBALS['LANG']->getLL('live', true);
		}
		if ($this->checkWorkspace(array('uid' => -1)))	{
			$options[-1] = '-1: ' . $GLOBALS['LANG']->getLL('defaultDraft', true);
		}

			// Add custom workspaces (selecting all, filtering by BE_USER check):
		$workspaces = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,title,adminusers,members,reviewers,db_mountpoints','sys_workspace','pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'),'','title');
		if (count($workspaces))	{
			foreach ($workspaces as $rec)	{
				if ($this->checkWorkspace($rec))	{
					$options[$rec['uid']] = $rec['uid'].': '.$rec['title'];

						// Check if all mount points are accessible, otherwise show error:
					if (trim($rec['db_mountpoints'])!=='')	{
						$mountPoints = t3lib_div::intExplode(',',$this->workspaceRec['db_mountpoints'],1);
						foreach ($mountPoints as $mpId)	{
							if (!$this->isInWebMount($mpId,'1=1'))	{
								$options[$rec['uid']].= '<br> \- ' . $GLOBALS['LANG']->getLL('notAccessible', true) . ' ' . $mpId;
							}
						}
					}
				}
			}
		}

		return $options;
	}
}












/**
 * Main script class
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class SC_mod_tools_be_user_index {
	var $MCONF=array();
	var $MOD_MENU=array();
	var $MOD_SETTINGS=array();

	/**
	 * document emplate object
	 *
	 * @var noDoc
	 */
	var $doc;

	var $include_once=array();
	var $content;


	/**
	 * Basic initialization of the class
	 *
	 * @return	void
	 */
	function init()	{
		$this->MCONF = $GLOBALS['MCONF'];

		$this->menuConfig();
		$this->switchUser(t3lib_div::_GP('SwitchUser'));


		// **************************
		// Initializing
		// **************************
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/beuser.html');
		$this->doc->form = '<form action="" method="post">';

				// JavaScript
		$this->doc->JScode = $this->doc->wrapScriptTags('
			script_ended = 0;
			function jumpToUrl(URL)	{	//
				window.location.href = URL;
			}
		' . $this->doc->redirectUrls());
	}

	/**
	 * Initialization of the module menu configuration
	 *
	 * @return	void
	 */
	function menuConfig()	{
		// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved.
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'function' => array(
				'compare' => $GLOBALS['LANG']->getLL('compareUserSettings', true),
				'whoisonline' => $GLOBALS['LANG']->getLL('listUsersOnline', true)
			)
		);
			// CLEAN SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name'], 'ses');
	}

	/**
	 * This functions builds the content of the page
	 *
	 * @return	void
	 */
	function main()	{
		$this->content='';

		$this->content.=$this->doc->header($GLOBALS['LANG']->getLL('backendUserAdministration', true));
		$this->content.=$this->doc->spacer(5);

		switch($this->MOD_SETTINGS['function'])	{
			case 'compare':
				if (t3lib_div::_GP('ads'))	{
					$compareFlags = t3lib_div::_GP('compareFlags');
					$GLOBALS['BE_USER']->pushModuleData('tools_beuser/index.php/compare',$compareFlags);
				} else {
					$compareFlags = $GLOBALS['BE_USER']->getModuleData('tools_beuser/index.php/compare','ses');
				}
				$this->content.=$this->compareUsers($compareFlags);
			break;
			case 'whoisonline':
				$this->content.=$this->whoIsOnline();
			break;
		}
			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		//$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['FUNC_MENU'] = t3lib_BEfunc::getFuncMenu(0,'SET[function]',$this->MOD_SETTINGS['function'],$this->MOD_MENU['function']);
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage('Backend User Administration');
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Prints the content of the page
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{

		$buttons = array(
			'csh' => '',
			'shortcut' => '',
			'save' => ''
		);
			// CSH
		//$buttons['csh'] = t3lib_BEfunc::cshItem('_MOD_web_func', '', $GLOBALS['BACK_PATH']);

			// Shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut())	{
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('be_user_uid,compareFlags','function', $this->MCONF['name']);
		}

		return $buttons;
	}





	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/

	/**
	 * Compares the users with the given flags
	 *
	 * @param	array		options that should be taking into account to compare the users
	 * @return	string		the content
	 */
	function compareUsers($compareFlags)	{
			// Menu:
		$options = array(
			'filemounts' => $GLOBALS['LANG']->getLL('filemounts', true),
			'webmounts' => $GLOBALS['LANG']->getLL('webmounts', true),
			'tempPath' => $GLOBALS['LANG']->getLL('defaultUploadPath', true),
			'firstMainGroup' => $GLOBALS['LANG']->getLL('mainUserGroup', true),
			'groupList' => $GLOBALS['LANG']->getLL('memberOfGroups', true),
			'pagetypes_select' => $GLOBALS['LANG']->getLL('pageTypesAccess', true),
			'tables_select' => $GLOBALS['LANG']->getLL('selectTables', true),
			'tables_modify' => $GLOBALS['LANG']->getLL('modifyTables', true),
			'non_exclude_fields' => $GLOBALS['LANG']->getLL('nonExcludeFields', true),
			'explicit_allowdeny' => $GLOBALS['LANG']->getLL('explicitAllowDeny', true),
			'allowed_languages' => $GLOBALS['LANG']->getLL('limitToLanguages', true),
			'workspace_perms' => $GLOBALS['LANG']->getLL('workspacePermissions', true),
			'workspace_membership' => $GLOBALS['LANG']->getLL('workspaceMembership', true),
			'custom_options' => $GLOBALS['LANG']->getLL('customOptions', true),
			'modules' => $GLOBALS['LANG']->getLL('modules', true),
			'userTS' => $GLOBALS['LANG']->getLL('tsconfig', true),
			'userTS_hl' => $GLOBALS['LANG']->getLL('tsconfigHL', true),
		);

		$be_user_uid = t3lib_div::_GP('be_user_uid');
		if ($be_user_uid)	{
				// This is used to test with other users. Development ONLY!
			$tempBE_USER = t3lib_div::makeInstance('local_beUserAuth');	// New backend user object
			$tempBE_USER->userTS_dontGetCached=1;
			$tempBE_USER->OS = TYPO3_OS;
			$tempBE_USER->setBeUserByUid($be_user_uid);
			$tempBE_USER->fetchGroupData();

			$uInfo = $tempBE_USER->ext_compileUserInfoForHash();
			$uInfo_dat = $tempBE_USER->ext_printOverview($uInfo,$options,1);

			$lines=array();
			foreach ($options as $kk => $vv) {
				if ($kk=='modules')	{
					$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
					$loadModules->load($GLOBALS['TBE_MODULES'],$tempBE_USER);
					$alt_menuObj = t3lib_div::makeInstance('alt_menu_functions');
					$uInfo_dat[$kk] = $alt_menuObj->topMenu($loadModules->modules,1,$GLOBALS['BACK_PATH']);
				}
				$lines[]='<tr class="bgColor4">
					<td nowrap="nowrap" valign="top">'.$vv.':&nbsp;&nbsp;</td>
					<td>'.$uInfo_dat[$kk].'&nbsp;</td>
				</tr>';

				if ($kk=='webmounts' && !$tempBE_USER->isAdmin())	{
					$lines[]='<tr class="bgColor4">
						<td nowrap="nowrap" valign="top">' . $GLOBALS['LANG']->getLL('nonMountedReadablePages', true) . '&nbsp;&nbsp;</td>
						<td>'.$tempBE_USER->ext_getReadableButNonmounted().'&nbsp;</td>
					</tr>';
				}
			}

			$email = htmlspecialchars($tempBE_USER->user['email']);
			$realname = htmlspecialchars($tempBE_USER->user['realName']);
			$outTable = '<table border="0" cellpadding="1" cellspacing="1"><tr class="bgColor5"><td>'.t3lib_iconWorks::getSpriteIconForRecord('be_users',$tempBE_USER->user,array('title'=>$tempBE_USER->user['uid'])).htmlspecialchars($tempBE_USER->user['username']).'</td>';
			$outTable.= '<td>'.($realname?$realname.', ':'').($email ? '<a href="mailto:'.$email.'">'.$email.'</a>' : '').'</td>';
			$outTable.= '<td>'.$this->elementLinks('be_users',$tempBE_USER->user).'</td></tr></table>';
			$outTable.= '<strong><a href="'.htmlspecialchars($this->MCONF['_']).'">' . $GLOBALS['LANG']->getLL('backToOverview', true) . '</a></strong><br />';

			$outTable.= '<br /><table border="0" cellpadding="2" cellspacing="1">'.implode('',$lines).'</table>';
			$content.= $this->doc->section($GLOBALS['LANG']->getLL('userInfo', true),$outTable,0,1);
		} else {
			$menu = array(0 => array());
			$rowCounter = 0;
			$columnCounter = 0;
			$itemsPerColumn = ceil(count($options) / 3);
			foreach ($options as $kk => $vv) {
				if ($rowCounter == $itemsPerColumn)	{
					$rowCounter = 0;
					$columnCounter++;
					$menu[$columnCounter] = array();
				}
				$rowCounter++;
				$menu[$columnCounter][]='<input type="checkbox" class="checkbox" value="1" name="compareFlags['.$kk.']" id="checkCompare_'.$kk.'"'.($compareFlags[$kk]?' checked="checked"':'').'> <label for="checkCompare_'.$kk.'">'.htmlspecialchars($vv).'</label>';
			}
			$outCode = '<p>' . $GLOBALS['LANG']->getLL('groupBy', true) . '</p>';
			$outCode .= '<table border="0" cellpadding="3" cellspacing="1" class="compare-checklist valign-top"><tr>';
			foreach ($menu as $column)	{
				$outCode .= '<td>' . implode('<br />', $column) . '</td>';
			}
			$outCode .= '</tr></table>';
			$outCode.='<br /><input type="submit" name="ads" value="' . $GLOBALS['LANG']->getLL('update', true) . '">';
			$content = $this->doc->section($GLOBALS['LANG']->getLL('groupAndCompareUsers', true),$outCode,0,1);


				// Traverse all users
			$users = t3lib_BEfunc::getUserNames();
			$comparation=array();
			$counter=0;


			$offset=0;
			$numberAtTime=1000;
			$tooManyUsers='';

			foreach ($users as $r) {
				if ($counter>=$offset)	{
						// This is used to test with other users. Development ONLY!
					$tempBE_USER = t3lib_div::makeInstance('local_beUserAuth');	// New backend user object
					/* @var $tempBE_USER local_beUserAuth */
					$tempBE_USER->OS = TYPO3_OS;
					$tempBE_USER->setBeUserByUid($r['uid']);
					$tempBE_USER->fetchGroupData();

						// Making group data
					$md5pre='';
					$menu=array();
					$uInfo = $tempBE_USER->ext_compileUserInfoForHash((array)$compareFlags);
					foreach ($options as $kk => $vv) {
						if ($compareFlags[$kk])	{
							$md5pre.=serialize($uInfo[$kk]).'|';
						}
					}
						// setting md5:
					$md5=md5($md5pre);
					if (!isset($comparation[$md5]))	{
						$comparation[$md5]=$tempBE_USER->ext_printOverview($uInfo,$compareFlags);
						$comparation[$md5]['users']=array();
					}
					$comparation[$md5]['users'][]=$tempBE_USER->user;
					unset($tempBE_USER);
				}
				$counter++;
				if ($counter>=($numberAtTime+$offset)) {
					$tooManyUsers=$GLOBALS['LANG']->getLL('tooManyUsers', true) . ' ' . count($users) . '. ' . $GLOBALS['LANG']->getLL('canOnlyDisplay', true) . ' ' . $numberAtTime . '.';
					break;
				}
			}

				// Print the groups:
			$allGroups=array();
				// Header:
			$allCells = array();

			$link_createNewUser='<a href="#" onclick="'.htmlspecialchars(t3lib_BEfunc::editOnClick('&edit[be_users][0]=new',$this->doc->backPath,-1)).'" title="' . $GLOBALS['LANG']->getLL('newUser', true) . '">'.
					t3lib_iconWorks::getSpriteIcon('actions-document-new') . 
				'</a>';

			$allCells['USERS'] = '<table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td><strong>' . $GLOBALS['LANG']->getLL('usernames', TRUE) . '</strong></td><td width="12">' . $link_createNewUser . '</td></tr></table>';

			foreach ($options as $kk => $vv) {
				if ($compareFlags[$kk])	{
					$allCells[$kk] = '<strong>'.$vv.':</strong>';
				}
			}
			$allGroups[]=$allCells;

			foreach ($comparation as $dat) {
				$allCells = array();

				$curUid = $GLOBALS['BE_USER']->user['uid'];
				$uListArr=array();

				foreach ($dat['users'] as $uDat) {
					$uItem = '<tr><td width="130">' . t3lib_iconWorks::getSpriteIconForRecord('be_users',$uDat,array('title'=> $uDat['uid'] )) . $this->linkUser($uDat['username'],$uDat) . '&nbsp;&nbsp;</td><td nowrap="nowrap">' . $this->elementLinks('be_users',$uDat);
					if ($curUid != $uDat['uid'] && !$uDat['disable'] && ($uDat['starttime'] == 0 ||
						$uDat['starttime'] < $GLOBALS['EXEC_TIME']) && ($uDat['endtime'] == 0 ||
						$uDat['endtime'] > $GLOBALS['EXEC_TIME'])) {
						$uItem .= '<a href="' . t3lib_div::linkThisScript(array('SwitchUser'=>$uDat['uid'])) . '" target="_top" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('switchUserTo', true) . ' ' . $uDat['username']) . ' ' . $GLOBALS['LANG']->getLL('changeToMode', TRUE) . '">' . 
								t3lib_iconWorks::getSpriteIcon('actions-system-backend-user-switch') .
							'</a>'.
							'<a href="' . t3lib_div::linkThisScript(array('SwitchUser'=>$uDat['uid'], 'switchBackUser' => 1)) . '" target="_top" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('switchUserTo', true) . ' ' . $uDat['username']) . ' ' . $GLOBALS['LANG']->getLL('switchBackMode', TRUE) . '">' . 
								t3lib_iconWorks::getSpriteIcon('actions-system-backend-user-emulate') .
							'</a>';
					}
					$uItem .= '</td></tr>';
					$uListArr[] = $uItem;
				}
				$allCells['USERS'] = '<table border="0" cellspacing="0" cellpadding="0" width="100%">'.implode('',$uListArr).'</table>';

				foreach ($options as $kk => $vv) {
					if ($compareFlags[$kk])	{
						$allCells[$kk] = $dat[$kk];
					}
				}
				$allGroups[]=$allCells;
			}

				// Make table
			$outTable='';
			$TDparams=' nowrap="nowrap" class="bgColor5" valign="top"';
			$i = 0;
			foreach ($allGroups as $allCells) {
				$outTable.='<tr><td'.$TDparams.'>'.implode('</td><td'.$TDparams.'>',$allCells).'</td></tr>';
				$TDparams=' nowrap="nowrap" class="'.($i++ % 2 == 0 ? 'bgColor4' : 'bgColor6').'" valign="top"';
			}
			$outTable='<table border="0" cellpadding="2" cellspacing="2">' . $outTable . '</table>';
			$outTable .= '<br /><br />' . $GLOBALS['LANG']->getLL('cachedGrouplistsUpdated', true);
			$outTable.=$tooManyUsers?'<br /><br /><strong><span class="typo3-red">' . $tooManyUsers . '</span></strong>':'';
			$content.= $this->doc->spacer(10);
			$content.= $this->doc->section($GLOBALS['LANG']->getLL('result', true),$outTable,0,1);
		}
		return $content;
	}


	/**
	 * Creates a HTML anchor to the user record
	 *
	 * @param	string		the string used to identify the user (inside the <a>...</a>)
	 * @param	array		the BE user record to link
	 * @return	string		the HTML anchor
	 */
	function linkUser($str,$rec)	{
		return '<a href="'.htmlspecialchars($this->MCONF['_']).'&be_user_uid='.$rec['uid'].'">' . htmlspecialchars($str) . '</a>';
	}


	/**
	 * Builds a list of all links for a specific element (here: BE user) and returns it for print.
	 *
	 * @param	string		the db table that should be used
	 * @param	array		the BE user record to use
	 * @return	string		a HTML formatted list of the link
	 */
	function elementLinks($table,$row)	{
			// Info:
		$cells[]='<a href="#" onclick="top.launchView(\'' . $table . '\', \'' . $row['uid'] . '\',\'' . $GLOBALS['BACK_PATH'] . '\'); return false;" title="' . $GLOBALS['LANG']->getLL('showInformation', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-info') .
			'</a>';

			// Edit:
		$params='&edit[' . $table . '][' . $row['uid'] . ']=edit';
		$cells[]='<a href="#" onclick="' . t3lib_BEfunc::editOnClick($params,$GLOBALS['BACK_PATH'],'') . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:edit', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-document-open') .
			'</a>';

			// Hide:
		$hiddenField = $GLOBALS['TCA'][$table]['ctrl']['enablecolumns']['disabled'];
		if ($row[$hiddenField])	{
			$params='&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=0';
			$cells[]='<a href="' . $this->doc->issueCommand($params) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:enable', TRUE) . '">' .
				t3lib_iconWorks::getSpriteIcon('actions-edit-unhide') .
			'</a>';
		} else {
			$params='&data[' . $table . '][' . $row['uid'] . '][' . $hiddenField . ']=1';
			$cells[]='<a href="' . $this->doc->issueCommand($params) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xml:disable', true) . '">' . 
				t3lib_iconWorks::getSpriteIcon('actions-edit-hide') .
			'</a>';
		}

			// Delete
		$params='&cmd[' . $table . '][' . $row['uid'] . '][delete]=1';
		$cells[]='<a href="' . $this->doc->issueCommand($params) . '" onclick="return confirm(unescape(\'' . rawurlencode($GLOBALS['LANG']->getLL('sureToDelete', TRUE)) . '\'));" title="' . $GLOBALS['LANG']->getLL('delete', TRUE) . '">' . 
				t3lib_iconWorks::getSpriteIcon('actions-edit-delete') .
			'</a>';

		return implode('',$cells);
	}


	/**
	 * Inits all BE-users available, for development ONLY!
	 *
	 * @return	void
	 */
	function initUsers()	{
			// Initializing all users in order to generate the usergroup_cached_list
		$users = t3lib_BEfunc::getUserNames();

			// This is used to test with other users. Development ONLY!
		foreach ($users as $r) {
			$tempBE_USER = t3lib_div::makeInstance('local_beUserAuth');	// New backend user object
			/* @var $tempBE_USER local_beUserAuth */
			$tempBE_USER->OS = TYPO3_OS;
			$tempBE_USER->setBeUserByUid($r['uid']);
			$tempBE_USER->fetchGroupData();
		}
	}

	/**
	 * Returns the local path for this string (removes the PATH_site if it is included)
	 *
	 * @param	string		the path that will be checked
	 * @return	string		the local path
	 */
	function localPath($str)	{
		if (substr($str,0,strlen(PATH_site))==PATH_site)	{
			return substr($str,strlen(PATH_site));
		} else {
			return $str;
		}
	}

	/**
	 * Switches to a given user (SU-mode) and then redirects to the start page of the backend to refresh the navigation etc.
	 *
	 * @param	array		BE-user record that will be switched to
	 * @return	void
	 */
	function switchUser($switchUser)	{
		$uRec=t3lib_BEfunc::getRecord('be_users',$switchUser);
		if (is_array($uRec) && $GLOBALS['BE_USER']->isAdmin())	{
			$updateData['ses_userid'] = $uRec['uid'];
				// user switchback
			if (t3lib_div::_GP('switchBackUser'))	{
				$updateData['ses_backuserid'] = intval($GLOBALS['BE_USER']->user['uid']);
			}
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_sessions', 'ses_id='.$GLOBALS['TYPO3_DB']->fullQuoteStr($GLOBALS['BE_USER']->id, 'be_sessions').' AND ses_name=\'be_typo_user\' AND ses_userid='.intval($GLOBALS['BE_USER']->user['uid']),$updateData);

			$redirectUrl = $GLOBALS['BACK_PATH'] . 'index.php' . ($GLOBALS['TYPO3_CONF_VARS']['BE']['interfaces'] ? '' : '?commandLI=1');
			t3lib_utility_Http::redirect($redirectUrl);
		}
	}

	/***************************
	 *
	 * "WHO IS ONLINE" FUNCTIONS:
	 *
	 ***************************/

	/**
	 * @author Martin Kutschker
	 */
	function whoIsOnline()	{
		$select_fields = 'ses_id, ses_tstamp, ses_iplock, u.uid,u.username, u.admin, u.realName, u.disable, u.starttime, u.endtime, u.deleted, bu.uid AS bu_uid,bu.username AS bu_username, bu.realName AS bu_realName';
		$from_table = 'be_sessions INNER JOIN be_users u ON ses_userid=u.uid LEFT OUTER JOIN be_users bu ON ses_backuserid=bu.uid';
		$where_clause = '';
		$orderBy = 'u.username';

		if (t3lib_div::testInt($GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout']))	{
			$where_clause .= 'ses_tstamp+' . $GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] . ' > ' . $GLOBALS['EXEC_TIME'];
		} else {
			$timeout = intval($GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout']);
			if ($timeout > 0)	{
				$where_clause .= 'ses_tstamp+' . $timeout . ' > ' . $GLOBALS['EXEC_TIME'];
			}
		}
			// Fetch active sessions of other users from storage:
		$sessions = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($select_fields,$from_table,$where_clause,'',$orderBy);
			// Process and visualized each active session as a table row:
		if (is_array($sessions)) {
			foreach ($sessions as $session) {
				$ip = $session['ses_iplock'];
				$hostName = '';
				if ($session['ses_iplock'] == '[DISABLED]' || $GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] == 0) {
					$ip = '-';
				} elseif ($GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP'] == 4) {
					$hostName = ' title="' . @gethostbyaddr($session['ses_iplock']) . '"';
				} else {
					$ip .= str_repeat('.*', 4-$GLOBALS['TYPO3_CONF_VARS']['BE']['lockIP']);
				}
				$outTable .= '
					<tr class="bgColor4" height="17" valign="top">' .
						'<td nowrap="nowrap">' .
							date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'].' '.$GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $session['ses_tstamp']) .
						'</td>' .
						'<td nowrap="nowrap">' .
							'<span'.$hostName.'>'.$ip.'</span>' .
						'</td>' .
						'<td width="130">' .
							t3lib_iconWorks::getSpriteIconForRecord('be_users',$session,array('title'=>$session['uid'])).htmlspecialchars($session['username']).'&nbsp;' .
						'</td>' .
						'<td nowrap="nowrap">'.htmlspecialchars($session['realName']).'&nbsp;&nbsp;</td>' .
						'<td nowrap="nowrap">'.$this->elementLinks('be_users',$session).'</td>' .
						'<td nowrap="nowrap" valign="top">'.($session['bu_username'] ? '&nbsp;SU from: ' : '').htmlspecialchars($session['bu_username']).'&nbsp;</td>' .
						'<td nowrap="nowrap" valign="top">&nbsp;'.htmlspecialchars($session['bu_realName']).'</td>' .
					'</tr>';
			}
		}
			// Wrap <table> tag around the rows:
		$outTable = '
		<table border="0" cellpadding="2" cellspacing="2">
			<tr class="bgColor5">
				<td valign="top"><strong>' . $GLOBALS['LANG']->getLL('timestamp', true) . '</strong></td>
				<td valign="top"><strong>' . $GLOBALS['LANG']->getLL('host', true) . '</strong></td>
				<td valign="top" colspan="5"><strong>' . $GLOBALS['LANG']->getLL('username', true) . '</strong></td>
			</tr>'.$outTable.'
		</table>';

		$content.= $this->doc->section($GLOBALS['LANG']->getLL('whoIsOnline', true),$outTable,0,1);
		return $content;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/beuser/mod/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/beuser/mod/index.php']);
}


// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_be_user_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
