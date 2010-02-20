<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Example class showing how one could make XML export of page content elements in TYPO3
 *
 * $Id: xmlversionLib.inc 5634 2009-06-26 13:25:08Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   96: class user_xmlversion
 *  107:     function main_xmlversion($content,$conf)
 *  137:     function getContentResult($table)
 *
 * TOTAL FUNCTIONS: 2
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */
/**
 * Class that creates the current page and content element records as an XML structure using the library "t3lib_xml"
 * It is demonstrated in use in the testsite package on page "59"
 * The static template "plugin.alt.xml" is used to trigger this XML creation as well. That template contains this set of TypoScript lines which triggers the XML creation and disables all regular HTML headers
 *
 * ## Set up page/type number:
 * alt_xml >
 * alt_xml = PAGE
 * alt_xml {
 *   typeNum=96
 *   config.disableAllHeaderCode = 1
 *   config.additionalHeaders = Content-type: text/xml
 *
 *   ## Includes the newsLib:
 *   includeLibs.alt_xml = media/scripts/xmlversionLib.inc
 *
 *   ## Inserting the USER cObject for XML rendering
 *   10 = USER
 *   10 {
 *     userFunc = user_xmlversion->main_xmlversion
 *   }
 * }
 *
 * @package TYPO3
 * @subpackage tslib
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
class user_xmlversion {
	var $cObj;		// The backReference to the mother cObj object set at call time


	/**
	 * Main function, called from TypoScript
	 *
	 * @param	string		Empty, ignore.
	 * @param	array		TypoScript properties for this content object/function call
	 * @return	string		XML content
	 */
	function main_xmlversion($content,$conf)	{
		$xmlObj = t3lib_div::makeInstance('t3lib_xml', 'typo3_page');
		$xmlObj->XMLdebug=0;
		$xmlObj->setRecFields('pages','doktype,title,alias,hidden,starttime,endtime,fe_group,url,target,no_cache,shortcut,keywords,description,abstract,author,author_email,newUntil,lastUpdated,cache_timeout');
		$xmlObj->setRecFields('tt_content','CType,header,header_link,bodytext,image,imagewidth,imageorient,media,records,colPos,starttime,endtime,fe_group');

			// Creating top level object
		$xmlObj->renderHeader();

			// Add page information
		$xmlObj->addRecord('pages',$GLOBALS['TSFE']->page);

			// Add page content information
		$xmlObj->newLevel('content_records',1);
		$xmlObj->renderRecords('pages',$this->getContentResult('pages'));
		$xmlObj->renderRecords('tt_content',$this->getContentResult('tt_content'));
		$xmlObj->newLevel('content_records');

		$xmlObj->renderFooter();

		return $xmlObj->getResult();
	}

	/**
	 * Selects all records from $table having the current page id as PID (records belonging to that page)
	 *
	 * @param	string		A tablename found in $TCA
	 * @return	pointer		A database resource pointer
	 */
	function getContentResult($table) {
		global $TCA;
		if ($TCA[$table])	{
			$orderBy = $TCA[$table]['ctrl']['sortby'] ? 'ORDER BY '.$TCA[$table]['ctrl']['sortby'] : $TCA[$table]['ctrl']['default_sortby'];
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'pid='.intval($GLOBALS['TSFE']->id).$this->cObj->enableFields($table), '', $GLOBALS['TYPO3_DB']->stripOrderBy($orderBy));
			return $res;
		}
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['media/scripts/xmlversionLib.inc'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['media/scripts/xmlversionLib.inc']);
}
?>