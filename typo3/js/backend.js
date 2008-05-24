/***************************************************************
*  Copyright notice
*
*  (c) 2007 - 2008 Ingo Renner <ingo@typo3.org>
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
 * general backend javascript functions
 */

/**
 * jump the backend to a module
 */
function jump(url, modName, mainModName) {
		// clear information about which entry in nav. tree that might have been highlighted.
	top.fsMod.navFrameHighlightedID = new Array();

	if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav) {
		top.content.nav_frame.refresh_nav();
	}

	top.nextLoadModuleUrl = url;
	top.goToModule(modName);
}

/**
 * shortcut manager to delegate the action of creating shortcuts to the new
 * backend.php shortcut menu or the old shortcut frame depending on what is available
 */
var ShortcutManager = {

	/**
	 * central entry point to create a shortcut, delegates the call to correct endpoint
	 */
	createShortcut: function(confirmQuestion, backPath, moduleName, url) {
		if(confirm(confirmQuestion)) {
			if(typeof TYPO3BackendShortcutMenu != 'undefined') {
					// backend.php
				TYPO3BackendShortcutMenu.createShortcut('', moduleName, url);
			}

			if(top.shortcutFrame) {
					// alt_main.php
				var location = backPath + 'alt_shortcut.php?modName=' + moduleName + '&URL=' + url;
				shortcutFrame.location.href = location;
			}
		}
	}
}



