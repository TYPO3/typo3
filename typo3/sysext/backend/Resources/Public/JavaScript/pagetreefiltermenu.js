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

/**
 * class to handle the pagetree filter menu
 */
var PagetreeFilterMenu = Class.create({

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		Ext.onReady(function() {
			Ext.get('tree-toolbar-filter-item').on('click', this.toggleMenu);
		}, this);
	},

	/**
	 * toggles the visibility of the filter
	 */
	toggleMenu: function(event) {
		var toolbarItem = Ext.get('tree-toolbar-filter-item');
		var treeFilterBox = Ext.get('treeFilterBox');
		var treeFilterItem = Ext.get('treeFilter');
		var treeFilterReset = Ext.get('treeFilterReset');

		toolbarItem.blur();

		if (toolbarItem.hasClass('active')) {
			treeFilterBox.fadeOut();
			TYPO3PageTreeFilter.resetSearchField();
		} else {
			treeFilterBox.fadeIn();
			treeFilterItem.focus();
		}

		toolbarItem.toggleClass('active');

	}
});

var TYPO3BackendFilterMenu = new PagetreeFilterMenu();
