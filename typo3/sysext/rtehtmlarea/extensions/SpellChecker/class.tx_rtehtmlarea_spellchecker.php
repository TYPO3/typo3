<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Spell Checker plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 */
class tx_rtehtmlarea_spellchecker extends tx_rtehtmlarea_api {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'SpellChecker';		// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/SpellChecker/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	protected $pluginButtons = 'spellcheck';
	protected $convertToolbarForHtmlAreaArray = array (
		'spellcheck'	=> 'SpellCheck',
		);
	protected $spellCheckerModes = array('ultra', 'fast', 'normal', 'bad-spellers');

	public function main($parentObject) {

		return parent::main($parentObject)
			&& t3lib_extMgm::isLoaded('static_info_tables')
			&& !in_array($this->htmlAreaRTE->language, t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$this->htmlAreaRTE->ID]['plugins'][$pluginName]['noSpellCheckLanguages']))
			&& ($this->htmlAreaRTE->contentCharset == 'iso-8859-1' || $this->htmlAreaRTE->contentCharset == 'utf-8');
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
	 * 	RTEarea['.$RTEcounter.']["buttons"]["button-id"]["property"] = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		$button = 'spellcheck';

			// Set the SpellChecker mode
		$spellCheckerMode = isset($GLOBALS['BE_USER']->userTS['options.']['HTMLAreaPspellMode']) ? trim($GLOBALS['BE_USER']->userTS['options.']['HTMLAreaPspellMode']) : 'normal';
		if (!in_array($spellCheckerMode, $this->spellCheckerModes)) {
			$spellCheckerMode = 'normal';
		}
			// Set the use of personal dictionary
		$enablePersonalDicts = $this->thisConfig['buttons.'][$button.'.']['enablePersonalDictionaries'] ? ((isset($GLOBALS['BE_USER']->userTS['options.']['enablePersonalDicts']) && $GLOBALS['BE_USER']->userTS['options.']['enablePersonalDicts']) ? true : false) : false;
		if (t3lib_utility_PhpOptions::isSafeModeEnabled() || $this->htmlAreaRTE->is_FE()) {
			$enablePersonalDicts = false;
		}

		$registerRTEinJavascriptString = '';
		if (in_array($button, $this->toolbar)) {
			if (!is_array( $this->thisConfig['buttons.']) || !is_array( $this->thisConfig['buttons.'][$button.'.'])) {
					$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .' = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.contentTypo3Language = "' . $this->htmlAreaRTE->contentTypo3Language .'";
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.contentISOLanguage = "' . $this->htmlAreaRTE->contentISOLanguage .'";
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.contentCharset = "' . $this->htmlAreaRTE->contentCharset .'";
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.spellCheckerMode = "' . $spellCheckerMode .'";
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.enablePersonalDicts = ' . ($enablePersonalDicts ? 'true' : 'false') .';';
			$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.path = "' . (($this->htmlAreaRTE->is_FE() || $this->htmlAreaRTE->isFrontendEditActive()) ? ($GLOBALS['TSFE']->absRefPrefix ? $GLOBALS['TSFE']->absRefPrefix : '') . 'index.php?eID=rtehtmlarea_spellchecker' : $this->htmlAreaRTE->backPath . 'ajax.php?ajaxID=rtehtmlarea::spellchecker') . '";';
		}
		return $registerRTEinJavascriptString;
	}
}
if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/SpellChecker/class.tx_rtehtmlarea_spellchecker.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/SpellChecker/class.tx_rtehtmlarea_spellchecker.php']);
}
?>