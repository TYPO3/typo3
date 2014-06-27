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
 * class to handle the dev links menu
 */
var SysActionMenu = Class.create({

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {

		Ext.onReady(function() {
			Event.observe(
				window, 'resize',
				function() { TYPO3BackendToolbarManager.positionMenu('tx-sys-action-menu'); }
			);
			TYPO3BackendToolbarManager.positionMenu('tx-sys-action-menu');
			this.toolbarItemIcon = $$('#tx-sys-action-menu .toolbar-item span')[0].src;

			Event.observe('tx-sys-action-menu', 'click', this.toggleMenu);

		}, this);
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		var toolbarItem = $$('#tx-sys-action-menu > a')[0];
		var menu        = $$('#tx-sys-action-menu .toolbar-item-menu')[0];
		toolbarItem.blur();

		if(!toolbarItem.hasClassName('toolbar-item-active')) {
			toolbarItem.addClassName('toolbar-item-active');
			Effect.Appear(menu, {duration: 0.2});
			TYPO3BackendToolbarManager.hideOthers(toolbarItem);
		} else {
			toolbarItem.removeClassName('toolbar-item-active');
			Effect.Fade(menu, {duration: 0.1});
		}

		if(event && (Event.element(event).hasClassName('toolbar-item') || Event.element(event).up().hasClassName('toolbar-item'))) {
			Event.stop(event);
		}
	}

});

var TYPO3BackendSysactionMenu = new SysActionMenu();
