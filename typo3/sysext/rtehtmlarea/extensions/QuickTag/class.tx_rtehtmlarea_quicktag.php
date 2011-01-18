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
 * CharacterMap plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id$
 *
 */
class tx_rtehtmlarea_quicktag extends tx_rtehtmlarea_api {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'QuickTag';		// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/QuickTag/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	protected $pluginButtons = 'inserttag';
	protected $convertToolbarForHtmlAreaArray = array (
		'inserttag'	=> 'InsertTag',
		);
	protected $requiredPlugins = 'TYPO3Color';	// The comma-separated list of names of prerequisite plugins

	public function main($parentObject) {
		$available = parent::main($parentObject);
		if ($this->thisConfig['disableSelectColor'] && $this->htmlAreaRTE->client['browser'] != 'gecko') {
			$this->requiredPlugins = 'DefaultColor';
		}
		return $available;
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

		$registerRTEinJavascriptString = '';
			// Deprecated inserttag button configuration
		if (in_array('inserttag', $this->toolbar) && trim($this->thisConfig['hideTags'])) {
			if (!is_array($this->thisConfig['buttons.']['inserttag.'])) {
				$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.inserttag = new Object();
			RTEarea['.$RTEcounter.'].buttons.inserttag.denyTags = "'.implode(',', t3lib_div::trimExplode(',', $this->thisConfig['hideTags'], 1)).'";';
			} elseif (!$this->thisConfig['buttons.']['inserttag.']['denyTags']) {
				$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.inserttag.denyTags = "'.implode(',', t3lib_div::trimExplode(',', $this->thisConfig['hideTags'], 1)).'";';
			}
		}
		return $registerRTEinJavascriptString;
	}

} // end of class

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/QuickTag/class.tx_rtehtmlarea_quicktag.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/QuickTag/class.tx_rtehtmlarea_quicktag.php']);
}

?>