/***************************************************************
*  Copyright notice
*
*  (c) 2010 Stefan Galinski <stefan.galinski@gmail.com>
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
 * Set's the height of the backend in relation to the visible area. This resizes
 * the module menu and the content of the TYPO3 viewport e.g. if you open firebug that
 * itself takes some height from the bottom.
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.BackendSizeManager = function() {
	var resizeBackend = function() {
		var viewportHeight = document.viewport.getHeight();
		var topHeight = Ext.get('typo3-topbar').getHeight();

		var consoleHeight = 0;
		var debugConsole = Ext.get('typo3-debug-console');
		if (debugConsole.isVisible()) {
			consoleHeight = debugConsole.getHeight() +
				Ext.get('typo3-debug-console-xsplit').getHeight()
		}

		var styles = {
			height: (viewportHeight - topHeight - consoleHeight) + 'px'
		};

		Ext.get('typo3-side-menu').setStyle(styles);
		Ext.get('content').setStyle(styles);
	};

	Ext.EventManager.onWindowResize(resizeBackend);
	Ext.onReady(function() {
		TYPO3.Backend.addListener('resize', resizeBackend);
		resizeBackend();
	});
}();
