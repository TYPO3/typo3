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

error_reporting (E_ALL ^ E_NOTICE);
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
require_once (PATH_t3lib.'class.t3lib_parsehtml.php');
//$LANG->includeLLFile('EXT:rtehtmlarea/mod6/locallang.xml');


class tx_rtehtmlarea_parse_html {
	var $content;
	var $modData;
	var $siteUrl;
	var $doc;
	var $ID = 'rtehtmlarea';

	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->siteUrl = t3lib_div::getIndpEnv("TYPO3_SITE_URL");
				// get the http-path to typo3:
		$this->httpTypo3Path = substr( substr( t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen( t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') ) ), 0, -1 );
		if (strlen($this->httpTypo3Path) == 1) {
			$this->httpTypo3Path = "/";
		} else {
			$this->httpTypo3Path .= "/";
		}
			// Get the path to this extension:
		$this->extHttpPath = $this->httpTypo3Path . t3lib_extMgm::siteRelPath($this->ID);

		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->JScode='';

		$this->modData = $BE_USER->getModuleData("rtehtmlarea_parse_html.php","ses");
		if (t3lib_div::_GP("OC_key"))	{
			$parts = explode("|",t3lib_div::_GP("OC_key"));
			$this->modData["openKeys"][$parts[1]] = $parts[0]=="O" ? 1 : 0;
			$BE_USER->pushModuleData("rtehtmlarea_parse_html.php",$this->modData);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->main_parse_html($this->modData["openKeys"]);
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
	 * Function imported from class.t3lib_div.php
	 * See http://bugs.typo3.org/view.php?id=277
	 * NOTE: chr(10) and chr(13) also need to be escaped for textarea elements
	 */
	function quoteJSvalue($value, $inScriptTags = false)    {
		$value = addcslashes($value, '\''.chr(10).chr(13));
		if (!$inScriptTags)    {
			$value = htmlspecialchars($value);
		}
		return '\''.$value.'\'';
	}
	
	/**
	 * Rich Text Editor (RTE) html parser
	 * 
	 * @param	[type]		$openKeys: ...
	 * @return	[type]		...
	 */
	function main_parse_html($openKeys)	{
		global $SOBE,$LANG,$BACK_PATH;
		
		$editorNo = t3lib_div::_GP("editorNo");
		$html = t3lib_div::_GP("content");
		
		$RTEtsConfigParts = explode(":",t3lib_div::_GP("RTEtsConfigParams"));
		$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		
		$HTMLParser = t3lib_div::makeInstance('t3lib_parsehtml');
		if (is_array($thisConfig['enableWordClean.'])) {
			$HTMLparserConfig = is_array($thisConfig['enableWordClean.']['HTMLparser.'])  ? $HTMLParser->HTMLparserConfig($thisConfig['enableWordClean.']['HTMLparser.']) : '';
		}
		if (is_array($HTMLparserConfig)) {
			$html = $HTMLParser->HTMLcleaner($html, $HTMLparserConfig[0], $HTMLparserConfig[1], $HTMLparserConfig[2], $HTMLparserConfig[3]);
		}
		
		$content = '
		var editor = RTEarea[' . $editorNo . ']["editor"];
		var html = ' . $this->quoteJSvalue($html, true) . ';
		editor.setHTML(html);';
		
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod6/parse_html.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod6/parse_html.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('tx_rtehtmlarea_parse_html');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
