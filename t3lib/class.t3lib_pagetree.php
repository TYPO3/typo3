<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * generate a page-tree. OBS: remember $clause
 *
 * $Id$
 * Revised for TYPO3 3.6 August/2003 by Kasper Skaarhoj
 * Maintained by René Fritz
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   78: class t3lib_pageTree extends t3lib_treeView
 *   91:     function init($clause='')	
 *  106:     function expandNext($id)	
 *  117:     function wrapIcon($icon,$row)	
 *  131:     function PMicon($row,$a,$c,$nextCount,$exp)	
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
 
 













require_once (PATH_t3lib."class.t3lib_treeview.php");

/**
 * Class for generating a page tree.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_pageTree extends t3lib_treeView{
	var $makeHTML=1;

	var $clause=' AND NOT deleted';
	var $db;
	var $fieldArray = Array('uid','title','doktype','php_tree_stop');
	var $defaultList = 'uid,pid,tstamp,sorting,deleted,perms_userid,perms_groupid,perms_user,perms_group,perms_everybody,crdate,cruser_id';
	var $setRecs = 0;
	
	/**
	 * @param	[type]		$clause: ...
	 * @return	[type]		...
	 */
	function init($clause='')	{
		parent::init($clause);
		if (t3lib_extMgm::isLoaded('cms'))	{
			$this->fieldArray=array_merge($this->fieldArray,array('hidden','starttime','endtime','fe_group','module','extendToSubpages'));
		}
		$this->table='pages';
		$this->treeName='pages';
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$id: ...
	 * @return	[type]		...
	 */
	function expandNext($id)	{
		return 1;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$icon: ...
	 * @param	[type]		$row: ...
	 * @return	[type]		...
	 */
 	function wrapIcon($icon,$row)	{
		return $icon;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$row: ...
	 * @param	[type]		$a: ...
	 * @param	[type]		$c: ...
	 * @param	[type]		$nextCount: ...
	 * @param	[type]		$exp: ...
	 * @return	[type]		...
	 */
	function PMicon($row,$a,$c,$nextCount,$exp)	{
		$PM = 'join';
		$BTM = ($a==$c)?'bottom':'';
		$icon = '<img src="'.$this->backPath.'t3lib/gfx/ol/'.$PM.$BTM.'.gif" width="18" height="16" align="top" alt="" />';
		return $icon;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_pagetree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_pagetree.php']);
}
?>