/*
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
 * Module: TYPO3/CMS/Workspaces/Toolbar/WorkspacesMenu
 * toolbar menu for the workspaces functionality to switch between the workspaces
 * and jump to the workspaces module
 */
define(['jquery'], function($) {
	'use strict';

	/**
	 *
	 * @type {{options: {containerSelector: string, menuItemSelector: string, activeMenuItemSelector: string, toolbarItemSelector: string, workspaceBodyClass: string, workspacesTitleInToolbarClass: string, workspaceModuleLinkSelector: string}}}
	 * @exports TYPO3/CMS/Workspaces/Toolbar/WorkspacesMenu
	 */
	var WorkspacesMenu = {
		options: {
			containerSelector: '#typo3-cms-workspaces-backend-toolbaritems-workspaceselectortoolbaritem',
			menuItemSelector: '.dropdown-menu li a.tx-workspaces-switchlink',
			activeMenuItemSelector: '.dropdown-menu .selected',
			toolbarItemSelector: '.dropdown-toggle',
			workspaceBodyClass: 'typo3-in-workspace',	// attached to <body> when in a workspace
			workspacesTitleInToolbarClass: 'topbar-workspaces-title',
			workspaceModuleLinkSelector: '.tx-workspaces-modulelink'
		}
	};

	/**
	 * registers event listeners
	 */
	WorkspacesMenu.initializeEvents = function() {

		// link to the module
		$(WorkspacesMenu.options.containerSelector).on('click', WorkspacesMenu.options.workspaceModuleLinkSelector, function(evt) {
			evt.preventDefault();
			top.goToModule($(this).data('module'));
		});

		// observe all clicks on workspace links in the menu
		$(WorkspacesMenu.options.containerSelector).on('click', WorkspacesMenu.options.menuItemSelector, function(evt) {
			evt.preventDefault();
			WorkspacesMenu.switchWorkspace($(this).data('workspaceid'));
		});
	};

	/**
	 * switches the workspace via AJAX (which returns the new data, as JSON),
	 * then reloads the module menu, and the content frame
	 *
	 * @param {String} workspaceId
	 */
	WorkspacesMenu.switchWorkspace = function(workspaceId) {
		$.ajax({
			url: TYPO3.settings.ajaxUrls['workspace_switch'],
			type: 'post',
			data: {
				workspaceId: workspaceId,
				pageId: fsMod.recentIds['web']
			},
			success: function(response) {
				if (!response.workspaceId) {
					response.workspaceId = 0;
				}

				WorkspacesMenu.performWorkspaceSwitch(response.workspaceId, response.title);

				// append the returned page ID to the current module URL
				if (response.pageId) {
					fsMod.recentIds['web'] = response.pageId;
					var url = TYPO3.Backend.ContentContainer.getUrl();
					url += (url.indexOf('?') == -1 ? '?' : '&') + '&id=' + response.pageId;
					if (TYPO3.Backend.NavigationContainer.PageTree) {
						TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
					}
					TYPO3.Backend.ContentContainer.setUrl(url);

				// when in web module reload, otherwise send the user to the web module
				} else if (currentModuleLoaded.indexOf('web_') === 0) {
					if (TYPO3.Backend.NavigationContainer.PageTree) {
						TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
					}
					TYPO3.ModuleMenu.App.reloadFrames();
				} else if (TYPO3.configuration.pageModule) {
					TYPO3.ModuleMenu.App.showModule(TYPO3.configuration.pageModule);
				}

				// reload the module menu
				TYPO3.ModuleMenu.App.refreshMenu();
			}
		});
	};

	/**
	 * changes the data in the module menu and the updates the backend context
	 *
	 * @param {String} id the workspace ID
	 * @param {String} title the workspace title
	 */
	WorkspacesMenu.performWorkspaceSwitch = function(id, title) {
		top.TYPO3.Workspaces.workspaceTitle = title;
		top.TYPO3.configuration.inWorkspace = id !== 0;

		WorkspacesMenu.updateBackendContext(title);

		// first remove all checks, then set the check in front of the selected workspace
		var stateActiveClass = 'fa fa-check';
		var stateInactiveClass = 'fa fa-empty-empty';

		// remove "selected" class and checkmark
		$(WorkspacesMenu.options.activeMenuItemSelector + ' i', WorkspacesMenu.options.containerSelector).removeClass(stateActiveClass).addClass(stateInactiveClass);
		$(WorkspacesMenu.options.activeMenuItemSelector, WorkspacesMenu.options.containerSelector).removeClass('selected');

		// add "selected" class and checkmark
		var $activeElement = $(WorkspacesMenu.options.menuItemSelector + '[data-workspaceid=' + id + ']', WorkspacesMenu.options.containerSelector);
		$activeElement.find('i').removeClass(stateInactiveClass).addClass(stateActiveClass);
		$activeElement.parent().addClass('selected');
	};

	/**
	 * checks if the TYPO3 backend is within a backend context and adds a class
	 * also updates the workspaces title
	 *
	 * @param {String} title
	 */
	WorkspacesMenu.updateBackendContext = function(title) {

		if (TYPO3.configuration.inWorkspace) {
			$('body').addClass(WorkspacesMenu.options.workspaceBodyClass);
			WorkspacesMenu.updateTopBar(title || TYPO3.lang['Workspaces.workspaceTitle']);
		} else {
			$('body').removeClass(WorkspacesMenu.options.workspaceBodyClass);
			WorkspacesMenu.updateTopBar();
		}
	};

	/**
	 * adds the workspace title to the toolbar next to the username
	 *
	 * @param {String} workspaceTitle
	 */
	WorkspacesMenu.updateTopBar = function(workspaceTitle) {
		$('.' + WorkspacesMenu.options.workspacesTitleInToolbarClass, WorkspacesMenu.options.containerSelector).remove();

		if (workspaceTitle && workspaceTitle.length) {
			var title = $('<span>', {
				'class': WorkspacesMenu.options.workspacesTitleInToolbarClass
			}).text(workspaceTitle);
			$(WorkspacesMenu.options.toolbarItemSelector, WorkspacesMenu.options.containerSelector).append(title);
		}
	};

	$(function() {
		WorkspacesMenu.initializeEvents();
		WorkspacesMenu.updateBackendContext();
	});

	// expose the module in a global object
	TYPO3.WorkspacesMenu = WorkspacesMenu;

	return WorkspacesMenu;
});
