<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Revised for TYPO3 3.6 August/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * Maintained by René Fritz
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   70: class t3lib_browseTree extends t3lib_treeView 
 *   78:     function init($clause='')	
 *  104:     function getTitleAttrib(&$row) 
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
 
require_once (PATH_t3lib.'class.t3lib_treeview.php');













/**
 * Extension class for the t3lib_treeView class, specially made for browsing pages
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @see class t3lib_treeView
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_browseTree extends t3lib_treeView {

	/**
	 * Initialize
	 * 
	 * @param	string		Additional clause for selecting pages.
	 * @return	void		
	 */
	function init($clause='')	{
		parent::init(' AND NOT deleted AND '.$GLOBALS['BE_USER']->getPagePermsClause(1).' '.$clause.' ORDER BY sorting');


		$this->BE_USER = $GLOBALS['BE_USER'];
		$this->titleAttrib = t3lib_BEfunc::titleAttrib();
		$this->backPath = $GLOBALS['BACK_PATH'];

		$this->table='pages';
		$this->treeName='browsePages';
		$this->domIdPrefix = 'pages';
		$this->iconName = '';
		$this->title = $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		$this->MOUNTS = $GLOBALS['WEBMOUNTS'];

		if (t3lib_extMgm::isLoaded('cms'))	{
			$this->fieldArray=array_merge($this->fieldArray,array('doktype','php_tree_stop','hidden','starttime','endtime','fe_group','module','extendToSubpages'));
		}
	}

	/**
	 * Creates title attribute for pages.
	 * 
	 * @param	array		The table row.
	 * @return	string		
	 */
	function getTitleAttrib(&$row) {
		return t3lib_BEfunc::titleAttribForPages($row,'1=1 '.$this->clause,0);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_browsetree.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_browsetree.php']);
}
?>
