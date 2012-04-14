/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2010 Ingo Renner <ingo@typo3.org>
 *  (c) 2010 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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

Ext.ns('TYPO3', 'TYPO3.configuration');
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

		Ext.onReady(function() {
			Event.observe(
				window, 'resize',
				function() { TYPO3BackendToolbarManager.positionMenu('workspace-selector-menu'); }
			);
			if (top.TYPO3.configuration.inWorkspace == 1) {
				Ext.getBody().addClass('typo3-in-workspace');
				this.updateTopBar(top.TYPO3.Workspaces.workspaceTitle);
			} else {
				Ext.getBody().removeClass('typo3-in-workspace');
			}
			TYPO3BackendToolbarManager.refreshAll();
			Event.observe('workspace-selector-menu', 'click', this.toggleMenu);
			if(Ext.get('goToWsModule')) {
				Event.observe('goToWsModule', 'click', this.goToWorkspaceModule.bind(this));
			}

			// observe all clicks on workspace links in the menu
			$$('#workspace-selector-menu li a.ws').each(function(element) {
				Event.observe(element, 'click', this.switchWorkspace.bind(this));
			}.bindAsEventListener(this));

		}, this);
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		var toolbarItem = $$('#workspace-selector-menu > a')[0];
		var menu = $$('#workspace-selector-menu .toolbar-item-menu')[0];
		toolbarItem.blur();

		if (!toolbarItem.hasClassName('toolbar-item-active')) {
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
	 * redirects the user to the workspace module
	 */
	goToWorkspaceModule: function(event) {
		top.goToModule('web_WorkspacesWorkspaces');
		this.toggleMenu(event);
	},

	/**
	 * switches the workspace, reloads the module menu, and the content frame
	 */
	switchWorkspace: function(event) {
		var clickedElement = Event.element(event);
		var workspaceId = clickedElement.identify().substring(3);

		TYPO3.Ajax.ExtDirect.ToolbarMenu.setWorkspace({'workSpaceId': workspaceId }, function(response) {
			if (!response.id) {
				response.id = 0;
			}

			TYPO3BackendWorkspaceMenu.performWorkspaceSwitch(response.id, response.title);

			// when in web module reload, otherwise send the user to the web module
			if (currentModuleLoaded.startsWith('web_')) {
				top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
				top.TYPO3.ModuleMenu.App.reloadFrames();
			} else {
				if (TYPO3.configuration.pageModule) {
					top.TYPO3.ModuleMenu.App.showModule(TYPO3.configuration.pageModule);
				}
			}

			// reload the module menu
			TYPO3ModuleMenu.refreshMenu();
		});

		TYPO3BackendWorkspaceMenu.toggleMenu(event);
	},

	performWorkspaceSwitch: function(id, title) {
		top.TYPO3.Workspaces.workspaceTitle = title;
		top.TYPO3.configuration.inWorkspace = id === 0 ? 0 : 1;
		if (top.TYPO3.configuration.inWorkspace == 1) {
			Ext.getBody().addClass('typo3-in-workspace');
			this.updateTopBar(top.TYPO3.Workspaces.workspaceTitle);
		} else {
			Ext.getBody().removeClass('typo3-in-workspace');
			this.updateTopBar();
		}

		TYPO3BackendToolbarManager.refreshAll();

		// first remove all checks, then set the check in front of the selected workspace
		var stateActiveClass = 't3-icon t3-icon-status t3-icon-status-status t3-icon-status-checked';
		var stateInactiveClass = 't3-icon t3-icon-empty t3-icon-empty-empty t3-icon-empty';

		// remove "selected" class and checkmark
		$$('#workspace-selector-menu li.selected a span.t3-icon-status-checked')[0].removeClassName(stateActiveClass).addClassName(stateInactiveClass);
		$$('#workspace-selector-menu li.selected')[0].removeClassName('selected');

		// add "selected" class and checkmark
		$$('#ws-' + id)[0].down().removeClassName(stateInactiveClass).addClassName(stateActiveClass);
		$$('#ws-' + id)[0].up().addClassName('selected');
	},

	updateTopBar: function(workspaceTitle) {
		if (Ext.get('typo3-topbar-workspaces-title')) {
			Ext.get('typo3-topbar-workspaces-title').remove();
		}

		if (workspaceTitle && workspaceTitle.length) {
			var userItem;
			if (Ext.select('#username a').elements.length) {
				userItem = Ext.select('#username a');
			} else {
				userItem = Ext.select ('#username');
			}
			userItem.insertHtml('beforeEnd', '<span id="typo3-topbar-workspaces-title">@' + Ext.util.Format.htmlEncode(workspaceTitle) + '</span>')
		}
	}

});

var TYPO3BackendWorkspaceMenu = new WorkspaceMenu();