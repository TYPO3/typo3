<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * Content parsing for htmlArea RTE
 *
 * @author	Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * $Id$  *
 */

require_once (PATH_t3lib.'class.t3lib_parsehtml.php');

class tx_rtehtmlarea_parse_html {
	var $content;
	var $modData;
	var $doc;
	var $extKey = 'rtehtmlarea';
	var $prefixId = 'TYPO3HtmlParser';

	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$BACK_PATH,$MCONF;
		
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='';
		
		$this->modData = $BE_USER->getModuleData($MCONF['name'],'ses');
		if (t3lib_div::_GP('OC_key'))	{
			$parts = explode('|',t3lib_div::_GP('OC_key'));
			$this->modData['openKeys'][$parts[1]] = $parts[0]=='O' ? 1 : 0;
			$BE_USER->pushModuleData($MCONF['name'],$this->modData);
		}
	}
	
	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{

		$this->content='';
		$this->content.=$this->main_parse_html($this->modData['openKeys']);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		echo $this->content;
	}
	
	/**
	 * Rich Text Editor (RTE) html parser
	 * 
	 * @param	[type]		$openKeys: ...
	 * @return	[type]		...
	 */
	function main_parse_html($openKeys)	{
		global $BE_USER, $TYPO3_CONF_VARS;
		
		$editorNo = t3lib_div::_GP('editorNo');
		$html = t3lib_div::_GP('content');
		
		$RTEtsConfigParts = explode(':',t3lib_div::_GP('RTEtsConfigParams'));
		$RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		
		$HTMLParser = t3lib_div::makeInstance('t3lib_parsehtml');
		if (is_array($thisConfig['enableWordClean.'])) {
			$HTMLparserConfig = is_array($thisConfig['enableWordClean.']['HTMLparser.'])  ? $HTMLParser->HTMLparserConfig($thisConfig['enableWordClean.']['HTMLparser.']) : '';
		}
		if (is_array($HTMLparserConfig)) {
			$html = $HTMLParser->HTMLcleaner($html, $HTMLparserConfig[0], $HTMLparserConfig[1], $HTMLparserConfig[2], $HTMLparserConfig[3]);
		}
		
		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey][$this->prefixId]['cleanPastedContent'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey][$this->prefixId]['cleanPastedContent'] as $classRef) {
				$hookObj = &t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'cleanPastedContent_afterCleanWord')) {
					$html = $hookObj->cleanPastedContent_afterCleanWord($html, $thisConfig);
				}
			}
		}
		
		$content = '
		var editor = RTEarea[' . $editorNo . ']["editor"];
		var html = ' . t3lib_div::quoteJSvalue($html, true) . ';
		editor.setHTML(html);';
		
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod6/class.tx_rtehtmlarea_parse_html.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod6/class.tx_rtehtmlarea_parse_html.php']);
}

?>
