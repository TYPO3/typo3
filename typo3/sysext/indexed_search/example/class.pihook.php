<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Index search frontend example hook
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   59: class tx_indexedsearch_pihook
 *   72:     function initialize_postProc()
 *   95:     function prepareResultRowTemplateData_postProc($tmplContent, $row, $headerOnly)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



/**
 * Index search frontend - EXAMPLE hook for alternative searching / display etc.
 * Hooks are configured in ext_localconf.php as key => hook-reference pairs in $TYPO3_CONF_VARS['EXTCONF']['indexed_search']['pi1_hooks']. See example in ext_localconf.php for "indexed_search"
 * Each hook must have an entry, the key must match the hook-key in class.tx_indexed_search.php and generally the key equals the function name in the hook object (a convension used)
 *
 * @package TYPO3
 * @subpackage tx_indexedsearch
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class tx_indexedsearch_pihook {

	var $pObj;		// Is set to a reference to the parent object, "pi1/class.indexedsearch.php"

	/**
	 * EXAMPLE of how you can post process the initialized values in the frontend plugin.
	 * The example reverses the order of elements in the ranking selector box. You can modify other values like this or add / remove items.
	 *
	 * This hook is activated by this key / value pair in ext_localconf.php
	 * 		'initialize_postProc' => 'EXT:indexed_search/example/class.pihook.php:&tx_indexedsearch_pihook',
	 *
	 * @return	void
	 */
	function initialize_postProc()	{
		$this->pObj->optValues['order'] = array_reverse($this->pObj->optValues['order']);
	}

	/**
	 * Providing an alternative search algorithm!
	 *
	 * @param	array		Array of search words
	 * @return	array		Array of first row, result rows, count
	 */
#	function getResultRows($sWArr)	{

#	}

	/**
	 * Example of how the content displayed in the result rows can be post processed before rendered into HTML.
	 * This example simply shows how the description field is wrapped in italics and the path is hidden by setting it blank.
	 *
	 * @param	array		Template Content (generated from result row) being processed.
	 * @param	array		Result row
	 * @param	boolean		If set, the result row is a sub-row.
	 * @return	array		Template Content returned.
	 */
	function prepareResultRowTemplateData_postProc($tmplContent, $row, $headerOnly)	{
		$tmplContent['description'] = '<em>'.$tmplContent['description'].'</em>';
		$tmplContent['path'] = '';

		return $tmplContent;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/indexed_search/example/class.pihook.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/indexed_search/example/class.pihook.php']);
}

?>