<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * $Id$  *
 */

class tx_rtehtmlarea_parse_html {
	var $content;
	var $modData;

	/**
	 * document template object
	 *
	 * @var template
	 */
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
		global $LANG;

		$this->content .= $this->main_parse_html($this->modData['openKeys']);

			// if no HTTP input conversion is configured, the input was uft-8 (urlencoded).
		$fromCharSet = 'utf-8';
			// if conversion was done, the input is encoded in mbstring.internal_encoding
		if (in_array('mbstring', get_loaded_extensions()) && ini_get('mbstring.encoding_translation')) {
			$fromCharSet = strToLower(ini_get('mbstring.internal_encoding'));
		}

		$clientInfo = t3lib_div::clientInfo();
			// the charset of the content element, possibly overidden by forceCharset
		$toCharSet = t3lib_div::_GP('charset')?t3lib_div::_GP('charset'):'iso-8859-1';
			// IE wants it back in utf-8
		if ( $clientInfo['BROWSER']= 'msie') {
			$toCharSet = 'utf-8';
		} elseif ($clientInfo['SYSTEM'] = 'win') {
				// if the client is windows the input may contain windows-1252 characters;
			if (strToLower($toCharSet) == 'iso-8859-1') {
				$toCharSet = 'Windows-1252';
			}
		}
			// convert to requested charset
		$this->content = $LANG->csConvObj->conv($this->content, $fromCharSet, $toCharSet);
		header('Content-Type: text/plain; charset='.$toCharSet);
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
			$HTMLparserConfig = $thisConfig['enableWordClean.']['HTMLparser.'];
			if (is_array($HTMLparserConfig)) {
				$this->keepSpanTagsWithId($HTMLparserConfig);
				$HTMLparserConfig = $HTMLParser->HTMLparserConfig($HTMLparserConfig);
			}
		}
		if (is_array($HTMLparserConfig)) {
			$html = $HTMLParser->HTMLcleaner($html, $HTMLparserConfig[0], $HTMLparserConfig[1], $HTMLparserConfig[2], $HTMLparserConfig[3]);
		}

		if (is_array ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey][$this->prefixId]['cleanPastedContent'])) {
			foreach  ($TYPO3_CONF_VARS['EXTCONF'][$this->extKey][$this->prefixId]['cleanPastedContent'] as $classRef) {
				$hookObj = t3lib_div::getUserObj($classRef);
				if (method_exists($hookObj, 'cleanPastedContent_afterCleanWord')) {
					$html = $hookObj->cleanPastedContent_afterCleanWord($html, $thisConfig);
				}
			}
		}
		return $html;
	}
	/**
	 * Modify incoming HTMLparser config in an attempt to keep span tags with id
	 * Such tags are used by the RTE in order to restore the cursor position when the cleaning operation is completed.
	 *
	 * @param	array		$HTMLparserConfig: incoming HTMLParser configuration (wil be modified)
	 * @return	void
	 */
	protected function keepSpanTagsWithId(&$HTMLparserConfig) {
			// Allow span tag
		if (isset($HTMLparserConfig['allowTags'])) {
			if (!t3lib_div::inList($HTMLparserConfig['allowTags'], 'span')) {
				$HTMLparserConfig['allowTags'] .= ',span';
			}
		} else {
			$HTMLparserConfig['allowTags'] = 'span';
		}
			// Allow attributes on span tags
		if (isset($HTMLparserConfig['noAttrib']) && t3lib_div::inList($HTMLparserConfig['noAttrib'], 'span')) {
			$HTMLparserConfig['noAttrib'] = t3lib_div::rmFromList('span', $HTMLparserConfig['noAttrib']);
		}
			// Do not remove span tags
		if (isset($HTMLparserConfig['removeTags']) && t3lib_div::inList($HTMLparserConfig['removeTags'], 'span')) {
			$HTMLparserConfig['removeTags'] = t3lib_div::rmFromList('span', $HTMLparserConfig['removeTags']);
		}
			// Review the tags array
		if (is_array($HTMLparserConfig['tags.'])) {
				// Allow span tag
			if (isset($HTMLparserConfig['tags.']['span']) && !$HTMLparserConfig['tags.']['span']) {
				$HTMLparserConfig['tags.']['span'] = 1;
			}
			if (is_array($HTMLparserConfig['tags.']['span.'])) {
				if (isset($HTMLparserConfig['tags.']['span.']['allowedAttribs'])) {
					if (!$HTMLparserConfig['tags.']['span.']['allowedAttribs']) {
						$HTMLparserConfig['tags.']['span.']['allowedAttribs'] = 'id';
					} else if (!t3lib_div::inList($HTMLparserConfig['tags.']['span.']['allowedAttribs'], 'id')) {
						$HTMLparserConfig['tags.']['span.']['allowedAttribs'] .= ',id';
					}
				}
				if (isset($HTMLparserConfig['tags.']['span.']['fixAttrib.']['id.']['unset'])) {
					unset($HTMLparserConfig['tags.']['span.']['fixAttrib.']['id.']['unset']);
				}
			}
		}
	}
}
if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod6/class.tx_rtehtmlarea_parse_html.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod6/class.tx_rtehtmlarea_parse_html.php']);
}
?>