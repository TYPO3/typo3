/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


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
	 * @param elementId The parent element of the menu to be positioned
	 */
	positionMenu: function(elementId) {
	}

});

var TYPO3BackendToolbarManager = new ToolbarManager();


