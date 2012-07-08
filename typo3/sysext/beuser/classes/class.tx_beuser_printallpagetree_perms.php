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
 * Extension class for printing a page tree: Printing all pages, with permissions.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tx_beuser
 */
class tx_beuser_printAllPageTree_perms extends tx_beuser_printAllPageTree {

	/**
	 * Print the tree of pages.
	 *
	 * @param	array		The tree items
	 * @param	boolean		If set, the path of the pages in the tree is printed (only done for pages outside of mounts).
	 * @return	string		HTML content.
	 */
	function printTree($treeArr='', $printPath=0) {
		$titleLen=intval($this->BE_USER->uc['titleLen']);

		$be_user_Array = t3lib_BEfunc::getUserNames();
		$be_group_Array = t3lib_BEfunc::getGroupNames();
		$lines=array();
		$lines[]='<tr class="bgColor5">
			<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('pageTitle', TRUE) . '</strong></td>
			' . ($printPath?'<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('path', TRUE) . '</strong></td>':'') . '
			<td nowrap="nowrap" colspan="2"><strong>' . $GLOBALS['LANG']->getLL('user', TRUE) . '</strong></td>
			<td nowrap="nowrap" colspan="2"><strong>' . $GLOBALS['LANG']->getLL('group', TRUE) . ' &nbsp;</strong></td>
			<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('everybody', TRUE) . ' &nbsp;</strong></td>
			<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('thisUser', TRUE) . ' &nbsp;</strong></td>
			<td nowrap="nowrap"><strong>' . $GLOBALS['LANG']->getLL('mainGroup', TRUE) . '</strong></td>
		</tr>';

		if (!is_array($treeArr)) {
			$treeArr = $this->tree;
		}
		foreach ($treeArr as $v) {
			$col1 = ' bgcolor="'.t3lib_div::modifyHtmlColor($GLOBALS['SOBE']->doc->bgColor4, +10, +10, +10).'"';
			$row = $v['row'];
			$title = htmlspecialchars(t3lib_div::fixed_lgd_cs($row['title'], $this->BE_USER->uc['titleLen']));
			$lines[]='<tr class="bgColor4">
				<td nowrap="nowrap">'.$v['HTML'].$title.' &nbsp;</td>
				'.($printPath?'<td nowrap="nowrap">'.htmlspecialchars(t3lib_BEfunc::getRecordPath ($row['pid'], '', 15)).' &nbsp;</td>':'').'
				<td nowrap="nowrap"'.$col1.'>'.$be_user_Array[$row['perms_userid']]['username'].' &nbsp;</td>
				<td nowrap="nowrap"'.$col1.'>'.$this->ext_printPerms($row['perms_user']).' &nbsp;</td>
				<td nowrap="nowrap">'.$be_group_Array[$row['perms_groupid']]['title'].' &nbsp;</td>
				<td nowrap="nowrap">'.$this->ext_printPerms($row['perms_group']).' &nbsp;</td>
				<td nowrap="nowrap" align="center" '.$col1.'>'.$this->ext_printPerms($row['perms_everybody']).' &nbsp;</td>
				<td nowrap="nowrap" align="center">' . ($row['editlock'] ? t3lib_iconWorks::getSpriteIcon('status-warning-in-use', array('title' => $GLOBALS['LANG']->getLL('editLock', TRUE))) : $this->ext_printPerms($this->BE_USER->calcPerms($row))) . ' &nbsp;</td>
				<td nowrap="nowrap" align="center">'.$this->ext_printPerms($this->ext_groupPerms($row, $be_group_Array[$this->BE_USER->firstMainGroup])).' &nbsp;</td>
			</tr>';
		}
		return '<table border="0" cellpadding="0" cellspacing="0">'.implode('', $lines).'</table>';
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
}
?>