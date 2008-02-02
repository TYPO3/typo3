/***************************************************************
*  Copyright notice
*
*  (c) 2007-2008 Ingo Renner <ingo@typo3.org>
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
 * class to handle the shortcut menu
 *
 * $Id$
 */
var ShortcutMenu = Class.create({

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		Event.observe(window, 'resize', this.positionMenu);

		Event.observe(window, 'load', function(){
			this.positionMenu();
			this.toolbarItemIcon = $$('#shortcut-menu .toolbar-item img')[0].src;

			Event.observe($$('#shortcut-menu .toolbar-item')[0], 'click', this.toggleMenu);
			this.initControls();
		}.bindAsEventListener(this));
	},

	/**
	 * initializes the controls to follow, edit, and delete shortcuts
	 *
	 */
	initControls: function() {

		$$('.shortcut-label a').each(function(element) {
			var shortcutId = element.up('tr.shortcut').identify().slice(9);

				// map InPlaceEditor to edit icons
			new Ajax.InPlaceEditor('shortcut-label-' + shortcutId, 'ajax.php?ajaxID=ShortcutMenu::saveShortcut', {
				externalControl     : 'shortcut-edit-' + shortcutId,
				externalControlOnly : true,
				highlightcolor      : '#f9f9f9',
				highlightendcolor   : '#f9f9f9',
				onFormCustomization : this.addGroupSelect,
				onComplete          : this.reRenderMenu.bind(this),
				callback            : function(form, nameInputFieldValue) {
					var params = form.serialize();
					params += '&shortcutId=' + shortcutId;

					return params;
				},
				textBetweenControls : ' ',
				cancelControl       : 'button',
				clickToEditText     : '',
				htmlResponse        : true
			});

				// follow/execute shortcuts
			element.observe('click', function(event) {
				Event.stop(event);
				this.toggleMenu();
			}.bind(this));

		}.bind(this));

			// activate delete icon
		$$('.shortcut-delete img').each(function(element) {
			element.observe('click', function(event) {
				if(confirm('Do you really want to remove this shortcut?')) {
					var deleteControl = event.element();
					var shortcutId = deleteControl.up('tr.shortcut').identify().slice(9);

					new Ajax.Request('ajax.php', {
						parameters : 'ajaxID=ShortcutMenu::delete&shortcutId=' + shortcutId,
						onComplete : this.reRenderMenu.bind(this)
					});
				}
			}.bind(this));
		}.bind(this));

	},

	/**
	 * positions the menu below the toolbar icon, let's do some math!
	 */
	positionMenu: function() {
		var calculatedOffset = 0;
		var parentWidth      = $('shortcut-menu').getWidth();
		var ownWidth         = $$('#shortcut-menu .toolbar-item-menu')[0].getWidth();
		var parentSiblings   = $('shortcut-menu').previousSiblings();

		parentSiblings.each(function(toolbarItem) {
			calculatedOffset += toolbarItem.getWidth() - 1;
			// -1 to compensate for the margin-right -1px of the list items,
			// which itself is necessary for overlaying the separator with the active state background

			if(toolbarItem.down().hasClassName('no-separator')) {
				calculatedOffset -= 1;
			}
		});
		calculatedOffset = calculatedOffset - ownWidth + parentWidth;


		$$('#shortcut-menu .toolbar-item-menu')[0].setStyle({
			left: calculatedOffset + 'px'
		});
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		var toolbarItem = $$('#shortcut-menu > a')[0];
		var menu        = $$('#shortcut-menu .toolbar-item-menu')[0];
		toolbarItem.blur();

		if(!toolbarItem.hasClassName('toolbar-item-active')) {
			toolbarItem.addClassName('toolbar-item-active');
			Effect.Appear(menu, {duration: 0.2});
			TYPO3BackendToolbarManager.hideOthers(toolbarItem);
		} else {
			toolbarItem.removeClassName('toolbar-item-active');
			Effect.Fade(menu, {duration: 0.1});
		}

		Event.stop(event);
	},

	/**
	 * adds a select field for the groups
	 */
	addGroupSelect: function(inPlaceEditor, inPlaceEditorForm) {
		var selectField = $(document.createElement('select'));

			// determine the shortcut id
		var shortcutId  = inPlaceEditorForm.identify().slice(9, -14);

			// now determine the shortcut's group id
		var shortcut        = $('shortcut-' + shortcutId).up('tr.shortcut');
		var firstInGroup    = null;
		var shortcutGroupId = 0;

		if(shortcut.hasClassName('first-row')) {
			firstInGroup = shortcut;
		} else {
			firstInGroup = shortcut.previous('.first-row');
		}

		if(undefined != firstInGroup) {
			shortcutGroupId = firstInGroup.previous().identify().slice(15);
		}

		selectField.name = 'shortcut-group';
		selectField.id = 'shortcut-group-select-' + shortcutId;
		selectField.size = 1;
		selectField.setStyle({marginBottom: '5px'});

			// create options
		var option;
			// first create an option for "no group"
		option = document.createElement('option');
		option.value = 0;
		option.selected = (shortcutGroupId == 0 ? true : false);
		option.appendChild(document.createTextNode('No Group'));
		selectField.appendChild(option);

			// get the groups
		new Ajax.Request('ajax.php', {
			method: 'get',
			parameters: 'ajaxID=ShortcutMenu::getGroups',
			asynchronous: false, // needs to be synchronous to build the options before adding the selectfield
			requestHeaders: {Accept: 'application/json'},
			onSuccess: function(transport, json) {
				var shortcutGroups = transport.responseText.evalJSON(true);

					// explicitly make the object a Hash
				shortcutGroups = $H(json.shortcutGroups);
				shortcutGroups.each(function(group) {
					option = document.createElement('option');
					option.value = group.key
					option.selected = (shortcutGroupId == group.key ? true : false);
					option.appendChild(document.createTextNode(group.value));
					selectField.appendChild(option);
				});

			}
		});

		inPlaceEditor._form.appendChild(document.createElement('br'));
		inPlaceEditor._form.appendChild(selectField);
		inPlaceEditor._form.appendChild(document.createElement('br'));
	},

	/**
	 * gets called when the update was succesfull, fetches the complete menu to
	 * honor changes in group assignments
	 */
	reRenderMenu: function(transport, element, backPath) {
		var container = $$('#shortcut-menu .toolbar-item-menu')[0];
		if(!backPath) {
			var backPath = '';
		}


		container.setStyle({
			height: container.getHeight() + 'px'
		});
		container.update('LOADING');

		new Ajax.Updater(
			container,
			backPath + 'ajax.php',
			{
				parameters : 'ajaxID=ShortcutMenu::render',
				asynchronous : false
			}
		);

		container.setStyle({
			height: 'auto'
		});

		this.initControls();
	},

	/**
	 * makes a call to the backend class to create a new shortcut,
	 * when finished it reloads the menu
	 */
	createShortcut: function(backPath, moduleName, url) {
		$$('#shortcut-menu .toolbar-item img')[0].src = 'gfx/spinner.gif';

			// synchrous call to wait for it to complete and call the render
			// method with backpath _afterwards_
		new Ajax.Request(backPath + 'ajax.php', {
			parameters : 'ajaxID=ShortcutMenu::create&module=' + moduleName + '&url=' + url,
			asynchronous : false
		});

		this.reRenderMenu(null, null, backPath);
		$$('#shortcut-menu .toolbar-item img')[0].src = this.toolbarItemIcon;
	}

});

var TYPO3BackendShortcutMenu = new ShortcutMenu();
