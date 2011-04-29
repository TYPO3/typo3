<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * EditorMode plugin for htmlArea RTE
 *
 * @author Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 */
class tx_rtehtmlarea_editormode extends tx_rtehtmlarea_api {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'EditorMode';		// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = 'extensions/EditorMode/skin/htmlarea.css';		// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	protected $pluginButtons = 'chMode';
	protected $convertToolbarForHtmlAreaArray = array (
		'chMode'	=> 'TextMode',
		);
		// Requiring itself so that it is always loaded.
	protected $requiredPlugins = 'EditorMode';

	public function main($parentObject) {
		parent::main($parentObject);
			// Do not disable this plugin even if the chMode button is disabled
		$this->pluginAddsButtons = FALSE;
		return true;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/EditorMode/class.tx_rtehtmlarea_editormode.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/EditorMode/class.tx_rtehtmlarea_editormode.php']);
}
?>