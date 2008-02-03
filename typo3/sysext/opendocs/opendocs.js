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
	ajaxID: 'tx_opendocs::backendmenu',
	menuItem: 'open-documents-menu',
	menu: null,		// the <ul> tag
	toolbarItem: null,	// the <a> tag


	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		Event.observe(window, 'load', function() {
			this.getMenu();
			Event.observe(window,        'resize', this.positionMenu.bindAsEventListener(this));
			Event.observe(this.menuItem, 'click',    this.toggleMenu.bindAsEventListener(this));
		}.bindAsEventListener(this));
	},


	getMenu: function() {
		this.menu = $$('#' + this.menuItem + ' ul')[0];
		this.toolbarItem = $$('#'+this.menuItem+' a')[0];
	},


	/**
	 * positions the menu below the toolbar icon
	 */
	positionMenu: function() {
		var calculatedOffset = 0;
		var ownWidth         = this.menu.getWidth();
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
	toggleMenu: function() {
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
		new Ajax.Request(this.ajaxScript, {
			parameters: 'ajaxID=' + this.ajaxID,
			onSuccess: function(xhr) {
				this.menu.innerHTML = xhr.responseText;
				Effect.Appear(this.menu, {duration: 0.2});
			}.bind(this)
		});
		this.positionMenu();
		this.toolbarItem.addClassName('toolbar-item-active');
		TYPO3BackendToolbarManager.hideOthers(this.toolbarItem);
	},


	/**
	 * hides the menu
	 */
	hideMenu: function() {
		Effect.Fade(this.menu, {duration: 0.1});
		this.toolbarItem.removeClassName('toolbar-item-active');
	}
});

var TYPO3BackendOpenDocs = new OpenDocs();
