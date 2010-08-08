/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Ingo Renner <ingo@typo3.org>
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
 * class to handle the workspace menu
 *
 * $Id$
 */
var WorkspaceMenu = Class.create({

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		Event.observe(window, 'resize', this.positionMenu);

		Event.observe(window, 'load', function(){
			this.positionMenu();

			Event.observe('workspace-selector-menu', 'click', this.toggleMenu);
			Event.observe('frontendPreviewToggle', 'click', this.toggleFrontendPreview.bind(this));
			Event.observe('goToWsModule', 'click', this.goToWorkspaceModule.bind(this));

				// observe all clicks on workspace links in the menu
			$$('#workspace-selector-menu li a.ws').each(function(element) {
				Event.observe(element, 'click', this.switchWorkspace.bind(this));
			}.bindAsEventListener(this));

		}.bindAsEventListener(this));
	},

	/**
	 * positions the menu below the toolbar icon, let's do some math!
	 */
	positionMenu: function() {
		var calculatedOffset = 0;
		var parentWidth      = $('workspace-selector-menu').getWidth();
		var ownWidth         = $$('#workspace-selector-menu ul')[0].getWidth();
		var parentSiblings   = $('workspace-selector-menu').previousSiblings();

		parentSiblings.each(function(toolbarItem) {
			calculatedOffset += toolbarItem.getWidth() - 1;
			// -1 to compensate for the margin-right -1px of the list items,
			// which itself is necessary for overlaying the separator with the active state background

			if(toolbarItem.down().hasClassName('no-separator')) {
				calculatedOffset -= 1;
			}
		});
		calculatedOffset = calculatedOffset - ownWidth + parentWidth;


		$$('#workspace-selector-menu ul')[0].setStyle({
			left: calculatedOffset + 'px'
		});
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		var toolbarItem = $$('#workspace-selector-menu > a')[0];
		var menu        = $$('#workspace-selector-menu .toolbar-item-menu')[0];
		toolbarItem.blur();

		if(!toolbarItem.hasClassName('toolbar-item-active')) {
			toolbarItem.addClassName('toolbar-item-active');
			Effect.Appear(menu, {duration: 0.2});
			TYPO3BackendToolbarManager.hideOthers(toolbarItem);
		} else {
			toolbarItem.removeClassName('toolbar-item-active');
			Effect.Fade(menu, {duration: 0.1});
		}

		if (event) {
			Event.stop(event);
		}
	},

	/**
	 * toggles the workspace frontend preview
	 */
	toggleFrontendPreview: function(event) {
		var clickedElement = Event.element(event);

		new Ajax.Request('ajax.php', {
			parameters: 'ajaxID=WorkspaceMenu::toggleWorkspacePreview',
			onSuccess: function(transport, response) {
				var stateActiveIcon = $$('#workspace-selector-menu img.state-active')[0].cloneNode(true);
				var stateInactiveIcon = $$('#workspace-selector-menu img.state-inactive')[0].cloneNode(true);

				if (response.newWorkspacePreviewState == 1) {
					clickedElement.previous().replace(stateActiveIcon);
					top.WorkspaceFrontendPreviewEnabled = true;
				} else {
					clickedElement.previous().replace(stateInactiveIcon);
					top.WorkspaceFrontendPreviewEnabled = false;
				}
			}
		});

		this.toggleMenu(event);
	},

	/**
	 * redirects the user to the workspace module
	 */
	goToWorkspaceModule: function(event) {
		top.goToModule('user_ws');

		this.toggleMenu(event);
	},

	/**
	 * switches the workspace, reloads the module menu, and the content frame
	 */
	switchWorkspace: function(event) {
		var clickedElement = Event.element(event);
		var workspaceId = clickedElement.identify().substring(3);

		new Ajax.Request('ajax.php', {
			parameters: 'ajaxID=WorkspaceMenu::setWorkspace&workspaceId=' + workspaceId,
			onSuccess: function(transport, response) {
					// first remove all checks, then set the check in front of the selected workspace
				var stateActiveIcon = $$('#workspace-selector-menu img.state-active')[0].cloneNode(true);
				var stateInactiveIcon = $$('#workspace-selector-menu img.state-inactive')[0].cloneNode(true);

					// remove "selected" class and checkmark
				$$('#workspace-selector-menu li.selected img.state-active')[0].replace(stateInactiveIcon);
				$$('#workspace-selector-menu li.selected')[0].removeClassName('selected');

					// add "selected" class and checkmark
				clickedElement.previous().replace(stateActiveIcon);
				clickedElement.up().addClassName('selected');

					// when in web module reload, otherwise send the user to the web module
				if (currentModuleLoaded.startsWith('web_')) {
						// the boolean "true" makes the page reload from the server
					$('content').contentWindow.location.reload(true);
				} else {
					top.goToModule('web_layout');
				}

					// reload the module menu
				TYPO3ModuleMenu.refreshMenu();
			}
		});

		this.toggleMenu(event);
	}

});

var TYPO3BackendWorkspaceMenu = new WorkspaceMenu();
