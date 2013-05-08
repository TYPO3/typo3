/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Ingo Renner <ingo@typo3.org>
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


var ToolbarManager = Class.create({

	/**
	 * hides all toolbar item menus except for the which was clicked
	 */
	hideOthers: function(toolbarItem) {
		var sibling = toolbarItem.next();

			// check whether it is a toolbar item with menu
		if (sibling.hasClassName('toolbar-item-menu')) {
			this.hideAll();
				// show toolbarItem
			toolbarItem.addClassName('toolbar-item-active');
		}
	},

	/**
	 * Hide all expanded toolbar menus
	 */
	hideAll: function() {
		$$('#typo3-toolbar a.toolbar-item + .toolbar-item-menu').invoke('hide');
		$$('#typo3-toolbar a.toolbar-item').each(function(element) {
			element.removeClassName('toolbar-item-active');
		});
	},

	/**
	 * refreshs positioning of all submenus
	 */
	refreshAll: function() {
	},

	/**
	 * positions a toolbar item (has to have .toolbar-item-menu)
	 * @param elementId The parent element ofthe menu to be positioned
	 */
	positionMenu: function(elementId) {
	}

});

var TYPO3BackendToolbarManager = new ToolbarManager();


