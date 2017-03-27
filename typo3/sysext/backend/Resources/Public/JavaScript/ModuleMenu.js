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
 * Class to render the module menu and handle the BE navigation
 */
require(
	[
		'jquery',
		'TYPO3/CMS/Backend/Storage',
		'TYPO3/CMS/Backend/Icons',
		'TYPO3/CMS/Backend/Viewport'
	],
	function ($, Storage, Icons) {
		if (typeof TYPO3.ModuleMenu !== 'undefined') {
			return TYPO3.ModuleMenu.App;
		}
		TYPO3.ModuleMenu = {};
		TYPO3.ModuleMenu.App = {
			loadedModule: null,
			loadedNavigationComponentId: '',
			availableNavigationComponents: {},

			initialize: function () {
				var me = this;

				// load the start module
				if (top.startInModule && top.startInModule[0] && $('#' + top.startInModule[0]).length > 0) {
					me.showModule(top.startInModule[0], top.startInModule[1]);
				} else {
					// fetch first module
					if ($('.t3js-mainmodule:first').attr('id')) {
						me.showModule($('.t3js-mainmodule:first').attr('id'));
					}
					// else case: the main module has no entries, this is probably a backend
					// user with very little access rights, maybe only the logout button and
					// a user settings module in topbar.
				}

				// check if module menu should be collapsed or not
				var state = Storage.Persistent.get('BackendComponents.States.typo3-module-menu');
				if (state && state.collapsed) {
					TYPO3.ModuleMenu.App.toggleMenu(state.collapsed);
				}

				// check if there are collapsed items in the users' configuration
				var collapsedMainMenuItems = me.getCollapsedMainMenuItems();
				$.each(collapsedMainMenuItems, function (key, itm) {
					if (itm !== true) {
						return;
					}
					var $group = $('#' + key);
					if ($group.length > 0) {
						var $groupContainer = $group.find('.modulemenu-group-container');
						$group.addClass('collapsed').removeClass('expanded');
						TYPO3.Backend.NavigationContainer.cleanup();
						$groupContainer.hide().promise().done(function () {
							TYPO3.Backend.doLayout();
						});
					}
				});
				me.initializeEvents();
			},

			initializeEvents: function () {
				var me = this;
				$(document).on('click', '.modulemenu-group .modulemenu-group-header', function () {
					var $group = $(this).parent('.modulemenu-group');
					var $groupContainer = $group.find('.modulemenu-group-container');

					TYPO3.Backend.NavigationContainer.cleanup();
					if ($group.hasClass('expanded')) {
						me.addCollapsedMainMenuItem($group.attr('id'));
						$group.addClass('collapsed').removeClass('expanded');
						$groupContainer.stop().slideUp().promise().done(function () {
							TYPO3.Backend.doLayout();
						});
					} else {
						me.removeCollapseMainMenuItem($group.attr('id'));
						$group.addClass('expanded').removeClass('collapsed');
						$groupContainer.stop().slideDown().promise().done(function () {
							TYPO3.Backend.doLayout();
						});
					}

				});
				// register clicking on sub modules
				$(document).on('click', '.modulemenu-item,.t3-menuitem-submodule', function (evt) {
					evt.preventDefault();
					me.showModule($(this).attr('id'));
					TYPO3.Backend.doLayout();
				});
				$(document).on('click', '.t3js-topbar-button-modulemenu',
					function (evt) {
						evt.preventDefault();
						TYPO3.ModuleMenu.App.toggleMenu();
					}
				);
				$(document).on('click', '.t3js-scaffold-content-overlay',
					function (evt) {
						evt.preventDefault();
						TYPO3.ModuleMenu.App.toggleMenu(true);
					}
				);
				$(document).on('click', '.t3js-topbar-button-navigationcomponent',
					function (evt) {
						evt.preventDefault();
						TYPO3.Backend.NavigationContainer.toggle();
					}
				);

			},
			/**
			 * @param {bool} collapse
			 */
			toggleMenu: function (collapse) {
				TYPO3.Backend.NavigationContainer.cleanup();

				var $mainContainer = $('.t3js-scaffold');
				var expandedClass = 'scaffold-modulemenu-expanded';

				if (typeof collapse === 'undefined') {
					collapse = $mainContainer.hasClass(expandedClass);
				}
				$mainContainer.toggleClass(expandedClass, !collapse);
				if (!collapse) {
					$('.scaffold')
						.removeClass('scaffold-search-expanded')
						.removeClass('scaffold-toolbar-expanded');
				}

				// Persist collapsed state in the UC of the current user
				Storage.Persistent.set(
					'BackendComponents.States.typo3-module-menu',
					{
						collapsed: collapse
					}
				);

				TYPO3.Backend.doLayout();
			},

			/* fetch the data for a submodule */
			getRecordFromName: function (name) {
				var $subModuleElement = $('#' + name);
				return {
					name: name,
					navigationComponentId: $subModuleElement.data('navigationcomponentid'),
					navigationFrameScript: $subModuleElement.data('navigationframescript'),
					navigationFrameScriptParam: $subModuleElement.data('navigationframescriptparameters'),
					link: $subModuleElement.find('a').attr('href')
				};
			},

			showModule: function (mod, params) {
				params = params || '';
				params = this.includeId(mod, params);
				var record = this.getRecordFromName(mod);
				this.loadModuleComponents(record, params);
			},

			loadModuleComponents: function (record, params) {
				var mod = record.name;
				if (record.navigationComponentId) {
					this.loadNavigationComponent(record.navigationComponentId);
				} else if (record.navigationFrameScript) {
					TYPO3.Backend.NavigationContainer.show('typo3-navigationIframe');
					this.openInNavFrame(record.navigationFrameScript, record.navigationFrameScriptParam);
				} else {
					TYPO3.Backend.NavigationContainer.hide();
				}

				this.highlightModuleMenuItem(mod);
				this.loadedModule = mod;
				this.openInContentFrame(record.link, params);

				// compatibility
				top.currentSubScript = record.link;
				top.currentModuleLoaded = mod;

				TYPO3.Backend.doLayout();
			},

			includeId: function (mod, params) {
				if (typeof mod === 'undefined') {
					return params;
				}
				//get id
				var section = mod.split('_')[0];
				if (top.fsMod.recentIds[section]) {
					params = 'id=' + top.fsMod.recentIds[section] + '&' + params;
				}

				return params;
			},

			loadNavigationComponent: function (navigationComponentId) {
				TYPO3.Backend.NavigationContainer.show(navigationComponentId);
				if (navigationComponentId === this.loadedNavigationComponentId) {
					return;
				}
				if (this.loadedNavigationComponentId !== '') {
					Ext.getCmp(this.loadedNavigationComponentId).hide();
				}
				if ($('.t3js-scaffold-content-navigation [data-component="' + navigationComponentId + '"]').length < 1) {
					$('.t3js-scaffold-content-navigation')
						.append('<div class="scaffold-content-navigation-component" data-component="' + navigationComponentId + '" id="navigationComponent-' + navigationComponentId + '"></div>');
				}
				var component = Ext.getCmp(navigationComponentId);
				if (typeof component === 'undefined') {
					/** @todo fix hard coded pagetree **/
					component = new TYPO3.Components.PageTree.App();
					component.render('navigationComponent-' + navigationComponentId);
				}
				TYPO3.Backend.NavigationContainer.show(navigationComponentId);
				this.loadedNavigationComponentId = navigationComponentId;
			},

			registerNavigationComponent: function (componentId, initCallback) {
				this.availableNavigationComponents[componentId] = initCallback;
			},

			openInNavFrame: function (url, params) {
				var navUrl = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '');
				var currentUrl = TYPO3.Backend.NavigationContainer.getUrl();
				if (currentUrl !== navUrl) {
					TYPO3.Backend.NavigationContainer.refresh();
				}
				TYPO3.Backend.NavigationContainer.setUrl(url);
			},

			openInContentFrame: function (url, params) {
				if (top.nextLoadModuleUrl) {
					TYPO3.Backend.ContentContainer.setUrl(top.nextLoadModuleUrl);
					top.nextLoadModuleUrl = '';
				} else {
					var urlToLoad = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '');
					TYPO3.Backend.ContentContainer.setUrl(urlToLoad);
				}
			},

			highlightModuleMenuItem: function (module, mainModule) {
				$('.modulemenu-item.active').removeClass('active');
				$('#' + module).addClass('active');
			},

			// refresh the HTML by fetching the menu again
			refreshMenu: function () {
				$.ajax(TYPO3.settings.ajaxUrls['modulemenu']).done(function (result) {
					$('#menu').replaceWith(result.menu);
					if (top.currentModuleLoaded) {
						TYPO3.ModuleMenu.App.highlightModuleMenuItem(top.currentModuleLoaded);
					}
					TYPO3.Backend.doLayout();
				});
			},

			reloadFrames: function () {
				TYPO3.Backend.NavigationContainer.refresh();
				TYPO3.Backend.ContentContainer.refresh();
			},

			/**
			 * fetches all module menu elements in the local storage that should be collapsed
			 * @returns {*}
			 */
			getCollapsedMainMenuItems: function () {
				if (TYPO3.Storage.Persistent.isset('modulemenu')) {
					return JSON.parse(TYPO3.Storage.Persistent.get('modulemenu'));
				} else {
					return {};
				}
			},

			/**
			 * adds a module menu item to the local storage
			 * @param item
			 */
			addCollapsedMainMenuItem: function (item) {
				var existingItems = this.getCollapsedMainMenuItems();
				existingItems[item] = true;
				TYPO3.Storage.Persistent.set('modulemenu', JSON.stringify(existingItems));
			},

			/**
			 * removes a module menu item from the local storage
			 * @param item
			 */
			removeCollapseMainMenuItem: function (item) {
				var existingItems = this.getCollapsedMainMenuItems();
				delete existingItems[item];
				TYPO3.Storage.Persistent.set('modulemenu', JSON.stringify(existingItems));
			}

		};
		// start the module menu app
		TYPO3.ModuleMenu.App.initialize();
		return TYPO3.ModuleMenu;
	}
);
