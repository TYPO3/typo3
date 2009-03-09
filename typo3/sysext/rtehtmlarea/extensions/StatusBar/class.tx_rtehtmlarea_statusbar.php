<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * TYPO3 SVN ID: $Id$
 *
 */

require_once(t3lib_extMgm::extPath('rtehtmlarea').'class.tx_rtehtmlareaapi.php');

class tx_rtehtmlarea_statusbar extends tx_rtehtmlareaapi {

	protected $extensionKey = 'rtehtmlarea';	// The key of the extension that is extending htmlArea RTE
	protected $pluginName = 'StatusBar';		// The name of the plugin registered by the extension
	protected $relativePathToLocallangFile = '';	// Path to this main locallang file of the extension relative to the extension dir.
	protected $relativePathToSkin = '';		// Path to the skin (css) file relative to the extension dir.
	protected $htmlAreaRTE;				// Reference to the invoking object
	protected $thisConfig;				// Reference to RTE PageTSConfig
	protected $toolbar;				// Reference to RTE toolbar array
	protected $LOCAL_LANG; 				// Frontend language array

	public function main($parentObject) {
		return parent::main($parentObject) && trim($this->thisConfig['showStatusBar']);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/StatusBar/class.tx_rtehtmlarea_statusbar.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/extensions/StatusBar/class.tx_rtehtmlarea_statusbar.php']);
}
?>