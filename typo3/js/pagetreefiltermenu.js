/***************************************************************
*  Copyright notice
*
*  (c) 2010 Jigal van Hemert <jigal@xs4all.nl>
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
 * class to handle the pagetree filter menu
 *
 * $Id$
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
			treeFilterReset.fadeOut();
		} else {
			treeFilterBox.fadeIn();
			treeFilterItem.focus();
		}

		toolbarItem.toggleClass('active');

	}
});

var TYPO3BackendFilterMenu = new PagetreeFilterMenu();
