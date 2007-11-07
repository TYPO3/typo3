<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * API for extending htmlArea RTE
 *
 * @author Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * TYPO3 CVS ID: $Id$
 *
 */

require_once(PATH_t3lib.'class.t3lib_div.php');

abstract class tx_rtehtmlareaapi {
	
	protected $extensionKey;				// The key of the extension that is extending htmlArea RTE
	protected $relativePathToLocallangFile;			// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin;				// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;					// Reference to the invoking object
	protected $thisConfig;					// Reference to RTE PageTSConfig
	protected $toolbar;					// Refrence to RTE toolbar array
	protected $LOCAL_LANG; 					// Frontend language array
	protected $pluginButtons = '';				// The comma-seperated list of button names that the extension id adding to the htmlArea RTE tollbar
	protected $pluginLabels = '';				// The comma-seperated list of label names that the extension id adding to the htmlArea RTE tollbar
	protected $convertToolbarForHtmlAreaArray = array();	// The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	protected $requiresClassesConfiguration = false;	// True if the extension requires the PageTSConfig Classes configuration
	
	/**
	 * Returns true if the plugin is available and correctly initialized
	 *
	 * @param	object		Reference to parent object, which is an instance of the htmlArea RTE
	 *
	 * @return	boolean		true if this plugin object should be made available in the current environment and is correctly initialized
	 */
	public function main($parentObject) {
		global $TYPO3_CONF_VARS, $LANG;
		
		$this->htmlAreaRTE =& $parentObject;
		$this->thisConfig =& $this->htmlAreaRTE->thisConfig;
		$this->toolbar =& $this->htmlAreaRTE->toolbar;
		
			// Check if the plugin should be disabled in frontend
		if ($this->htmlAreaRTE->is_FE() && is_array($TYPO3_CONF_VARS['EXTCONF'][$this->extensionKey]) && $TYPO3_CONF_VARS['EXTCONF'][$this->extensionKey]['disableInFE']) {
			return false;
		}
		
			// Localization array must be initialized here
		if ($this->htmlAreaRTE->is_FE()) {
			$this->LOCAL_LANG = t3lib_div::readLLfile('EXT:' . $this->extensionKey . '/' . $this->relativePathToLocallangFile, $this->htmlAreaRTE->language);
		} else {
			$LANG->includeLLFile('EXT:' . $this->extensionKey . '/' . $this->relativePathToLocallangFile);
		}
		return true;
	}
	
	/**
	 * Returns a modified toolbar order string
	 *
	 * @return	string		a modified tollbar order list
	 */
	public function addButtonsToToolbar() {
			//Add only buttons not yet in the default toolbar order
		$addButtons = implode(',', array_diff(t3lib_div::trimExplode(',', $this->pluginButtons, 1), t3lib_div::trimExplode(',', $this->htmlAreaRTE->defaultToolbarOrder, 1)));
		return (($addButtons ? ('bar,'  . $addButtons . ',linebreak,') : '')  . $this->htmlAreaRTE->defaultToolbarOrder);
	}
	
	/**
	 * Returns the path to the skin component (button icons) that should be added to linked stylesheets
	 *
	 * @return	string		path to the skin (css) file
	 */
	public function getPathToSkin() {
		global $TYPO3_CONF_VARS;
		if (is_array($TYPO3_CONF_VARS['EXTCONF'][$this->extensionKey]) && $TYPO3_CONF_VARS['EXTCONF'][$this->extensionKey]['addIconsToSkin']) {
			return $this->relativePathToSkin;
		} else {
			return '';
		}
	}
	
	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param	integer		Relative id of the RTE editing area in the form
	 *
	 * @return	string		JS configuration for registered plugins
	 * 
	 * The returned string will be a set of JS instructions defining the configuration that will be provided to the plugin(s)
	 * Each of the instructions should be of the form:
	 * 	RTEarea['.$RTEcounter.']["buttons"]["button-id"]["property"] = "value";
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		global $TSFE, $LANG;
		
		$registerRTEinJavascriptString = '';
		$pluginButtons = t3lib_div::trimExplode(',', $this->pluginButtons, 1);
		foreach ($pluginButtons as $button) {
			if (in_array($button, $this->toolbar)) {
				if (!is_array( $this->thisConfig['buttons.']) || !is_array( $this->thisConfig['buttons.'][$button.'.'])) {
					$registerRTEinJavascriptString .= '
			RTEarea['.$RTEcounter.']["buttons"]["'. $button .'"] = new Object();';
				}
			}
		}
		return $registerRTEinJavascriptString;
	}
	
	/**
	 * Returns the extension key
	 *
	 * @return	string		the extension key
	 */
	public function getExtensionKey() {
		return $this->extensionKey;
	}
	
	/**
	 * Returns the list of buttons implemented by the plugin
	 *
	 * @return	string		the list of buttons implemented by the plugin
	 */
	public function getPluginButtons() {
		return $this->pluginButtons;
	}
	
	/**
	 * Returns the list of toolbar labels implemented by the plugin
	 *
	 * @return	string		the list of labels implemented by the plugin
	 */
	public function getPluginLabels() {
		return $this->pluginLabels;
	}
	
	/**
	 * Returns the conversion array from TYPO3 button names to htmlArea button names
	 *
	 * @return	array		the conversion array from TYPO3 button names to htmlArea button names
	 */
	public function getConvertToolbarForHtmlAreaArray() {
		return $this->convertToolbarForHtmlAreaArray;
	}
	
	/**
	 * Returns true if the extension requires the PageTSConfig Classes configuration
	 *
	 * @return	boolean		true if the extension requires the PageTSConfig Classes configuration
	 */
	public function requiresClassesConfiguration() {
		return $this->requiresClassesConfiguration;
	}

} // end of class

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/class.tx_rtehtmlareaapi.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/class.tx_rtehtmlareaapi.php']);
}

?>