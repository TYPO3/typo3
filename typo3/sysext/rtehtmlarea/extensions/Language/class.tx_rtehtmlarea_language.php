<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Language plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id$
 *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlareaapi.php');

class tx_rtehtmlarea_language extends tx_rtehtmlareaapi {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'Language';		// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = 'extensions/Language/locallang.xml';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/Language/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	protected $pluginButtons = 'lefttoright,righttoleft,language,showlanguagemarks';
	protected $convertToolbarForHtmlAreaArray = array (
		'lefttoright'			=> 'LeftToRight',
		'righttoleft'			=> 'RightToLeft',
		'language'			=> 'Language',
		'showlanguagemarks'		=> 'ShowLanguageMarks',
		);

	public function main($parentObject) {
		if (!t3lib_extMgm::isLoaded('static_info_tables')) {
			$this->pluginButtons = t3lib_div::rmFromList('language', $this->pluginButtons);
		} else {
			require_once(t3lib_extMgm::extPath('static_info_tables').'class.tx_staticinfotables_div.php');
		}
		return parent::main($parentObject);
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return string		JS configuration for registered plugins
	 *
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.'].buttons.button-id.property = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		global $TSFE, $LANG;
		
		$registerRTEinJavascriptString = '';
		if (in_array('language', $this->toolbar)) {
			if (!is_array( $this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.']['language.'])) {
				$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.language = new Object();';
			}
			$prefixLabelWithCode = !$this->thisConfig['buttons.']['language.']['prefixLabelWithCode'] ? false : true;
			$postfixLabelWithCode = !$this->thisConfig['buttons.']['language.']['postfixLabelWithCode'] ? false : true;
			$languageCodes = t3lib_div::trimExplode(',', $this->thisConfig['buttons.']['language.']['items'] ? $this->thisConfig['buttons.']['language.']['items'] : 'en', 1);
			$labelsArray = $this->getStaticInfoName(implode(',', $languageCodes));
			if ($this->htmlAreaRTE->is_FE()) {
				$first = $GLOBALS['TSFE']->getLLL('No language mark',$this->LOCAL_LANG);
			} else {
				$first = $GLOBALS['LANG']->getLL('No language mark');
			}
				// Generating the JavaScript options
			$languageOptions = '{
			"'. $first.'" : "none"';
			foreach ($languageCodes as $index => $code) {
				$label = ($prefixLabelWithCode ? ($code . ' - ') : '') . $labelsArray[$index] . ($postfixLabelWithCode ? (' - ' . $code) : '');
				$label = (!$this->htmlAreaRTE->is_FE() && $this->htmlAreaRTE->TCEform->inline->isAjaxCall) ? $GLOBALS['LANG']->csConvObj->utf8_encode($label, $GLOBALS['LANG']->charSet) : $label;
				$languageOptions .= ',
			"' . $label . '" : "' . $code . '"';
			}
			$languageOptions .= '};';

			$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.language.dropDownOptions = '. $languageOptions;
		}
		return $registerRTEinJavascriptString;
	}
	
	/**
	 * Getting the name of a language
	 * We assume that the Static Info Tables are in 
	 *
	 * @param	string		$code: the ISO alpha-2 code of a language; or a comma-separated list of such
	 * @param	boolean		$local: local name only - if set local title is returned
	 * @return	array		names of the language(s) in the current language
	 */
	function getStaticInfoName($code, $local=FALSE) {
		$table = 'static_languages';
		$lang = tx_staticinfotables_div::getCurrentLanguage();
		if (!t3lib_extMgm::isLoaded('static_info_tables_'.strtolower($lang))) {
			$lang = '';
		}
		$codeArray = t3lib_div::trimExplode(',', $code);
		$namesArray = array();
		foreach ($codeArray as $isoCode){
			$isoCodeArray = t3lib_div::trimExplode( '_', $isoCode, 1);
			$name = tx_staticinfotables_div::getTitleFromIsoCode($table, $isoCodeArray, $lang, $local);
			if (!$name && $lang != 'EN') {
					// use the default English name if there is not text in another language
				$name = tx_staticinfotables_div::getTitleFromIsoCode($table, $isoCodeArray, '', $local);
			}
			if ($this->htmlAreaRTE->is_FE()) {
				$namesArray[] = $GLOBALS['TSFE']->csConvObj->conv($name, 'utf-8', $this->htmlAreaRTE->OutputCharset);
			} else {
				$namesArray[] = $GLOBALS['LANG']->csConvObj->conv($name, 'utf-8', $this->htmlAreaRTE->OutputCharset);
			}
		}
		return $namesArray;
	}

} // end of class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/Language/class.tx_rtehtmlarea_language.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/Language/class.tx_rtehtmlarea_language.php']);
}

?>