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
 * class to handle the open documents menu, loads the open documents dynamically
 */
var OpenDocs = Class.create({
	menu: null,
	toolbarItemIcon: null,

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {

		Ext.onReady(function() {
			this.toolbarItemIcon = $$('#tx-opendocs-menu .dropdown-toggle span.t3-icon')[0];
			this.menu = $$('#tx-opendocs-menu .dropdown-menu')[0];
			this.updateNumberOfDocs(-1, true);
		}, this);
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {},

	/**
	 * displays the menu and does the AJAX call to the TYPO3 backend
	 */
	updateMenu: function() {
		var origToolbarItemIcon = this.toolbarItemIcon.src;
		this.toolbarItemIcon.src = 'gfx/spinner.gif';

		new Ajax.Updater(
			this.menu,
			top.TS.PATH_typo3 + TYPO3.settings.ajaxUrls['OpendocsController::renderMenu'], {
				onComplete: function(xhr) {
					this.toolbarItemIcon.src = origToolbarItemIcon;
				}.bind(this)
			}
		);
	},

	/**
	 * updates the number of open documents in the toolbar according to the
	 * first parameter. If "num" is smaller than "0", the number of opendocs
	 * is counted from the open menu
	 *
	 * @param	integer		number of open documents
	 * @param	boolean		flag to explicitly update the menu
	 */
	updateNumberOfDocs: function(num, explicitlyUpdateMenu) {
		if (explicitlyUpdateMenu) {
				// re-render the menu e.g. if a document was closed inside the menu
			this.updateMenu();
		}

		if (num < 0) {
			num = $$('#tx-opendocs-menu li.opendoc').length;
		}
		var counter = $('tx-opendocs-counter');
		counter.update(num);
		counter.toggle(num > 0);
	},

	/**
	 * closes an open document
	 */
	closeDocument: function(md5sum) {
		new Ajax.Updater(
			this.menu,
			top.TS.PATH_typo3 + TYPO3.settings.ajaxUrls['OpendocsController::closeDocument'], {
				parameters: {
					md5sum: md5sum
				},
				onComplete: function() {
					this.updateNumberOfDocs(-1, false);
				}.bind(this)
			}
		);

		this.updateNumberOfDocs(-1, true);
	}

});

var TYPO3BackendOpenDocs = new OpenDocs();
