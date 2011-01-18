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
 * TYPO3 Image plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * TYPO3 SVN ID: $Id$
 *
 */
class tx_rtehtmlarea_typo3image extends tx_rtehtmlarea_api {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'TYPO3Image';		// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin  = 'extensions/TYPO3Image/skin/htmlarea.css';	// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	protected $pluginButtons = 'image';
	protected $convertToolbarForHtmlAreaArray = array (
		'image'	=> 'InsertImage',
		);

	public function main($parentObject) {
			// Check if this should be enabled based on Page TSConfig
		return parent::main($parentObject) && !$this->thisConfig['disableTYPO3Browsers']
				&& !(is_array( $this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['image.']) && is_array($this->thisConfig['buttons.']['image.']['TYPO3Browser.']) && $this->thisConfig['buttons.']['image.']['TYPO3Browser.']['disabled']);
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return 	string		JS configuration for registered plugins, in this case, JS configuration of block elements
	 *
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.']["buttons"]["button-id"]["property"] = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {

		$registerRTEinJavascriptString = '';
		$button = 'image';
		if (in_array($button, $this->toolbar)) {
			if (!is_array( $this->thisConfig['buttons.']) || !is_array( $this->thisConfig['buttons.'][$button.'.'])) {
					$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"]["'. $button .'"] = new Object();';
			}
			$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.'].buttons.'. $button .'.pathImageModule = "' . $this->htmlAreaRTE->extHttpPath . 'mod4/select_image.php";';
		}
		return $registerRTEinJavascriptString;
	}
}
if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/TYPO3Image/class.tx_rtehtmlarea_typo3image.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/TYPO3Image/class.tx_rtehtmlarea_typo3image.php']);
}
?>