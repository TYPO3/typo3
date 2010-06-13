/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Benjamin Mack <mack@xnos.org>
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
 *
 */
var OpenDocs = Class.create({
	ajaxScript: 'ajax.php',
	menu: null,
	toolbarItemIcon: null,

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		Event.observe(window, 'resize', this.positionMenu);

		Ext.onReady(function() {
			this.positionMenu();
			this.toolbarItemIcon = $$('#tx-opendocs-menu .toolbar-item span.t3-icon')[0];
			this.ajaxScript      = top.TS.PATH_typo3 + this.ajaxScript; // can't be initialized earlier

			Event.observe($$('#tx-opendocs-menu .toolbar-item')[0], 'click', this.toggleMenu);
			this.menu = $$('#tx-opendocs-menu .toolbar-item-menu')[0];
		}, this);
	},

	/**
	 * positions the menu below the toolbar icon, let's do some math!
	 */
	positionMenu: function() {
		var calculatedOffset = 0;
		var parentWidth      = $('tx-opendocs-menu').getWidth();
		var currentToolbarItemLayer = $$('#tx-opendocs-menu .toolbar-item-menu')[0];
		var ownWidth         = currentToolbarItemLayer.getWidth();
		var parentSiblings   = $('tx-opendocs-menu').previousSiblings();

		parentSiblings.each(function(toolbarItem) {
			calculatedOffset += toolbarItem.getWidth() - 1;
			// -1 to compensate for the margin-right -1px of the list items,
			// which itself is necessary for overlaying the separator with the active state background

			if(toolbarItem.down().hasClassName('no-separator')) {
				calculatedOffset -= 1;
			}
		});
		calculatedOffset = calculatedOffset - ownWidth + parentWidth;

			// border correction
		if (currentToolbarItemLayer.getStyle('display') !== 'none') {
			calculatedOffset += 2;
		}

		$$('#tx-opendocs-menu .toolbar-item-menu')[0].setStyle({
			left: calculatedOffset + 'px'
		});
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		var toolbarItem = $$('#tx-opendocs-menu > a')[0];
		var menu        = $$('#tx-opendocs-menu .toolbar-item-menu')[0];
		toolbarItem.blur();

		if(!toolbarItem.hasClassName('toolbar-item-active')) {
			toolbarItem.addClassName('toolbar-item-active');
			Effect.Appear(menu, {duration: 0.2});
			TYPO3BackendToolbarManager.hideOthers(toolbarItem);
		} else {
			toolbarItem.removeClassName('toolbar-item-active');
			Effect.Fade(menu, {duration: 0.1});
		}

		if(event) {
			Event.stop(event);
		}
	},

	/**
	 * displays the menu and does the AJAX call to the TYPO3 backend
	 */
	updateMenu: function() {
		var origToolbarItemIcon = this.toolbarItemIcon.src;
		this.toolbarItemIcon.src = 'gfx/spinner.gif';

		new Ajax.Updater(
			this.menu,
			this.ajaxScript, {
				parameters: {
					ajaxID: 'tx_opendocs::renderMenu'
				},
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
			num = $$('#tx-opendocs-menu tr.opendoc').length;
		}

		$('tx-opendocs-counter').writeAttribute('value', num);
	},

	/**
	 * closes an open document
	 */
	closeDocument: function(md5sum) {
		new Ajax.Updater(
			this.menu,
			this.ajaxScript, {
				parameters: {
					ajaxID: 'tx_opendocs::closeDocument',
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
