<?php
namespace TYPO3\CMS\Rtehtmlarea;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
abstract class RteHtmlAreaApi {

	protected $extensionKey;

	// The key of the extension that is extending htmlArea RTE
	protected $pluginName;

	// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile;

	// Path to the localization file for this script, relative to the extension dir
	protected $relativePathToSkin;

	// Path to the skin (css) file that should be added to the RTE skin when the registered plugin is enabled, relative to the extension dir
	protected $relativePathToPluginDirectory;

	// Path to the directory containing the plugin, relative to the extension dir (should end with slash /)
	protected $htmlAreaRTE;

	// Reference to the invoking object
	protected $rteExtensionKey;

	// The extension key of the RTE
	protected $thisConfig;

	// Reference to RTE PageTSConfig
	protected $toolbar;

	// Refrence to RTE toolbar array
	protected $LOCAL_LANG;

	// Frontend language array
	protected $pluginButtons = '';

	// The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
	protected $pluginLabels = '';

	// The comma-separated list of label names that the registered plugin is adding to the htmlArea RTE toolbar
	protected $pluginAddsButtons = TRUE;

	// Boolean indicating whether the plugin is adding buttons or not
	protected $convertToolbarForHtmlAreaArray = array();

	// The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
	protected $requiresClassesConfiguration = FALSE;

	// TRUE if the registered plugin requires the PageTSConfig Classes configuration
	protected $requiresSynchronousLoad = FALSE;

	// TRUE if the plugin must be loaded synchronously
	protected $requiredPlugins = '';

	// The comma-separated list of names of prerequisite plugins
	/**
	 * Returns TRUE if the plugin is available and correctly initialized
	 *
	 * @param 	object		Reference to parent object, which is an instance of the htmlArea RTE
	 * @return 	boolean		TRUE if this plugin object should be made available in the current environment and is correctly initialized
	 */
	public function main($parentObject) {
		global $TYPO3_CONF_VARS, $LANG, $TSFE;
		$this->htmlAreaRTE = $parentObject;
		$this->rteExtensionKey = &$this->htmlAreaRTE->ID;
		$this->thisConfig = &$this->htmlAreaRTE->thisConfig;
		$this->toolbar = &$this->htmlAreaRTE->toolbar;
		$this->LOCAL_LANG = &$this->htmlAreaRTE->LOCAL_LANG;
		// Set the value of this boolean based on the initial value of $this->pluginButtons
		$this->pluginAddsButtons = !empty($this->pluginButtons);
		// Check if the plugin should be disabled in frontend
		if ($this->htmlAreaRTE->is_FE() && $TYPO3_CONF_VARS['EXTCONF'][$this->rteExtensionKey]['plugins'][$this->pluginName]['disableInFE']) {
			return FALSE;
		}
		// Localization array must be initialized here
		if ($this->relativePathToLocallangFile) {
			if ($this->htmlAreaRTE->is_FE()) {
				$this->LOCAL_LANG = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($this->LOCAL_LANG, \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile('EXT:' . $this->extensionKey . '/' . $this->relativePathToLocallangFile, $this->htmlAreaRTE->language));
			} else {
				$LANG->includeLLFile('EXT:' . $this->extensionKey . '/' . $this->relativePathToLocallangFile);
			}
		}
		return TRUE;
	}

	/**
	 * Returns a modified toolbar order string
	 *
	 * @return 	string		a modified tollbar order list
	 */
	public function addButtonsToToolbar() {
		//Add only buttons not yet in the default toolbar order
		$addButtons = implode(',', array_diff(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->pluginButtons, 1), \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->htmlAreaRTE->defaultToolbarOrder, 1)));
		return ($addButtons ? 'bar,' . $addButtons . ',linebreak,' : '') . $this->htmlAreaRTE->defaultToolbarOrder;
	}

	/**
	 * Returns the path to the skin component (button icons) that should be added to linked stylesheets
	 *
	 * @return 	string		path to the skin (css) file
	 */
	public function getPathToSkin() {
		global $TYPO3_CONF_VARS;
		if ($TYPO3_CONF_VARS['EXTCONF'][$this->rteExtensionKey]['plugins'][$this->pluginName]['addIconsToSkin']) {
			return $this->relativePathToSkin;
		} else {
			return '';
		}
	}

	/**
	 * Return JS configuration of the htmlArea plugins registered by the extension
	 *
	 * @param 	integer		Relative id of the RTE editing area in the form
	 * @return 	string		JS configuration for registered plugins
	 */
	public function buildJavascriptConfiguration($RTEcounter) {
		global $TSFE, $LANG;
		$registerRTEinJavascriptString = '';
		$pluginButtons = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->pluginButtons, 1);
		foreach ($pluginButtons as $button) {
			if (in_array($button, $this->toolbar)) {
				if (!is_array($this->thisConfig['buttons.']) || !is_array($this->thisConfig['buttons.'][($button . '.')])) {
					$registerRTEinJavascriptString .= '
			RTEarea[' . $RTEcounter . '].buttons.' . $button . ' = new Object();';
				}
			}
		}
		return $registerRTEinJavascriptString;
	}

	/**
	 * Returns the extension key
	 *
	 * @return 	string		the extension key
	 */
	public function getExtensionKey() {
		return $this->extensionKey;
	}

	/**
	 * Returns the path to the plugin directory, if any
	 *
	 * @return 	string		the full path to the plugin directory
	 */
	public function getPathToPluginDirectory() {
		return $this->relativePathToPluginDirectory ? $this->htmlAreaRTE->httpTypo3Path . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath($this->extensionKey) . $this->relativePathToPluginDirectory : '';
	}

	/**
	 * Returns a boolean indicating whether the plugin adds buttons or not to the toolbar
	 *
	 * @return 	boolean
	 */
	public function addsButtons() {
		return $this->pluginAddsButtons;
	}

	/**
	 * Returns the list of buttons implemented by the plugin
	 *
	 * @return 	string		the list of buttons implemented by the plugin
	 */
	public function getPluginButtons() {
		return $this->pluginButtons;
	}

	/**
	 * Returns the list of toolbar labels implemented by the plugin
	 *
	 * @return 	string		the list of labels implemented by the plugin
	 */
	public function getPluginLabels() {
		return $this->pluginLabels;
	}

	/**
	 * Returns the conversion array from TYPO3 button names to htmlArea button names
	 *
	 * @return 	array		the conversion array from TYPO3 button names to htmlArea button names
	 */
	public function getConvertToolbarForHtmlAreaArray() {
		return $this->convertToolbarForHtmlAreaArray;
	}

	/**
	 * Returns TRUE if the extension requires the PageTSConfig Classes configuration
	 *
	 * @return 	boolean		TRUE if the extension requires the PageTSConfig Classes configuration
	 */
	public function requiresClassesConfiguration() {
		return $this->requiresClassesConfiguration;
	}

	/**
	 * Returns TRUE if the plugin requires synchronous load
	 *
	 * @return 	boolean		TRUE if the plugin requires synchronous load
	 */
	public function requiresSynchronousLoad() {
		return $this->requiresSynchronousLoad;
	}

	/**
	 * Sets the plugin to require synchronous load or not
	 *
	 * @param 	boolean		$value: the boolean value to set
	 * @return 	void
	 */
	public function setSynchronousLoad($value = TRUE) {
		$this->requiresSynchronousLoad = $value;
	}

	/**
	 * Returns the list of plugins required by the plugin
	 *
	 * @return 	string		the list of plugins required by the plugin
	 */
	public function getRequiredPlugins() {
		return $this->requiredPlugins;
	}

}


?>