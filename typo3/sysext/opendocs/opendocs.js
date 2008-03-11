/***************************************************************
*  Copyright notice
*
*  (c) 2008 Benjamin Mack <mack@xnos.org>
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
 * class to handle the open documents menu, loads the open documents dynamically
 */
var OpenDocs = Class.create({
	ajaxScript: 'ajax.php',
	ajaxIDloadMenu: 'tx_opendocs::backendMenu',
	ajaxIDcloseDoc: 'tx_opendocs::closeDocument',
	menuItem: 'open-documents-menu',
	menu: null,		// the <div> tag
	toolbarItem: null,	// the <a> tag


	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		Event.observe(window, 'load', function() {
			this.ajaxScript = top.TS.PATH_typo3 + this.ajaxScript; // can't be initialized earlier
			this.getMenu();
			Event.observe(window,          'resize', this.positionMenu.bindAsEventListener(this));
			Event.observe(this.toolbarItem, 'click',   this.toggleMenu.bindAsEventListener(this));
		}.bindAsEventListener(this));
	},


	getMenu: function() {
		this.toolbarItem = $(this.menuItem).firstChild;
		this.menu = this.toolbarItem.nextSibling;
	},


	/**
	 * positions the menu below the toolbar icon
	 */
	positionMenu: function() {
		var calculatedOffset = 0;
		var ownWidth         = $(this.menu).getWidth();
		var parentWidth      = $(this.menuItem).getWidth();
		var parentSiblings   = $(this.menuItem).previousSiblings();

		parentSiblings.each(function(toolbarItem) {
			calculatedOffset += toolbarItem.getWidth()-1;
		});
		calculatedOffset = calculatedOffset - ownWidth + parentWidth;
		this.menu.setStyle({ left: calculatedOffset-2 + 'px' });
	},


	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		Event.stop(event);
		this.toolbarItem.blur();
		if(!this.toolbarItem.hasClassName('toolbar-item-active')) {
			this.showMenu();
		} else {
			this.hideMenu();
		}
	},



	/**
	 * displays the menu and does the AJAX call to the TYPO3 backend
	 */
	showMenu: function() {
		new Ajax.Updater(this.menu, this.ajaxScript, {
			parameters: { ajaxID: this.ajaxIDloadMenu },
			onSuccess: function(xhr) {
				if (!this.menu.visible()) {
					Effect.Appear(this.menu, {
						duration: 0.2,
						afterFinish: function() { this.positionMenu(); }.bind(this)
					});
				}
			}.bind(this)
		});
		if (!this.toolbarItem.hasClassName('toolbar-item-active')) {
			this.toolbarItem.addClassName('toolbar-item-active');
			TYPO3BackendToolbarManager.hideOthers(this.toolbarItem);
		}
	},


	/**
	 * hides the menu
	 */
	hideMenu: function() {
		Effect.Fade(this.menu, {duration: 0.1} );
		this.toolbarItem.removeClassName('toolbar-item-active');
	},


	/**
	 * updates the number of open documents in the toolbar
	 */
	updateNumberOfDocs: function(num, doNotUpdateMenu) {
		if (num < 0) {
			num = $$('tr.opendoc').length;
		}
		if (num == 0) {
			num = '';
		}
		$('tx-opendocs-num').innerHTML = num;
		if (this.menu.visible() && !doNotUpdateMenu) {
			this.showMenu();
		}
	},

	/**
	 * this function calls the backend to close an open documentshould let the 
	 */
	closeDocument: function(md5sum) {
		new Ajax.Updater(this.menu, this.ajaxScript, {
			parameters: { ajaxID: this.ajaxIDcloseDoc, md5sum: md5sum },
			onSuccess: function() { this.updateNumberOfDocs(-1, true); }.bind(this)
		});
		return false;
	}

});

var TYPO3BackendOpenDocs = new OpenDocs();

