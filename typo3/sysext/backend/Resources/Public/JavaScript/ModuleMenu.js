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
		'TYPO3/CMS/Backend/Storage/Persistent',
		'TYPO3/CMS/Backend/Icons',
		'TYPO3/CMS/Backend/Viewport',
		'TYPO3/CMS/Backend/Event/ClientRequest',
		'TYPO3/CMS/Backend/Event/TriggerRequest'
	],
	function ($, PersistentStorage, Icons, Viewport, ClientRequest, TriggerRequest) {
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

				var deferred = $.Deferred();
				deferred.resolve();

				// load the start module
				if (top.startInModule && top.startInModule[0] && $('#' + top.startInModule[0]).length > 0) {
					deferred = me.showModule(
						top.startInModule[0],
						top.startInModule[1]
					);
				} else {
					// fetch first module
					if ($('.t3js-mainmodule:first').attr('id')) {
						deferred = me.showModule(
							$('.t3js-mainmodule:first').attr('id')
						);
					}
					// else case: the main module has no entries, this is probably a backend
					// user with very little access rights, maybe only the logout button and
					// a user settings module in topbar.
				}

				deferred.then(function() {
					// check if module menu should be collapsed or not
					var state = PersistentStorage.get('BackendComponents.States.typo3-module-menu');
					if (state && state.collapsed) {
						TYPO3.ModuleMenu.App.toggleMenu(state.collapsed === 'true');
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
				});
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
					me.showModule(
						$(this).attr('id'),
						'',
						evt
					);
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
			 * @param {Boolean} collapse
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
				PersistentStorage.set(
					'BackendComponents.States.typo3-module-menu',
					{
						collapsed: collapse
					}
				);

				TYPO3.Backend.doLayout();
			},

			/**
			 * Gets the module properties from module menu markup (data attributes)
			 *
			 * @param {string} name module name e.g. web_list
			 * @return {Object}
			 */
			getRecordFromName: function (name) {
				var $subModuleElement = $('#' + name);
				return {
					name: name,
					navigationComponentId: $subModuleElement.data('navigationcomponentid'),
					navigationFrameScript: $subModuleElement.data('navigationframescript'),
					navigationFrameScriptParam: $subModuleElement.data('navigationframescriptparameters'),
					link: $subModuleElement.find('a').data('link')
				};
			},

			/**
			 * Event handler called after clicking on the module menu item
			 *
			 * @param {string} name module name e.g. web_layout
			 * @param {string} params
			 * @param {Event} [event]
			 * @return {jQuery.Deferred}
			 */
			showModule: function (name, params, event) {
				params = params || '';
				params = this.includeId(name, params);
				var moduleData = this.getRecordFromName(name);
				return this.loadModuleComponents(
					moduleData,
					params,
					new ClientRequest('typo3.showModule', event)
				);
			},

			/**
			 * Shows requested module (e.g. list/page)
			 *
			 * @param {Object} moduleData
			 * @param {string} params
			 * @param {InteractionRequest} [interactionRequest]
			 * @return {jQuery.Deferred}
			 */
			loadModuleComponents: function (moduleData, params, interactionRequest) {
				var moduleName = moduleData.name;

				// Allow other components e.g. Formengine to cancel switching between modules
				// (e.g. you have unsaved changes in the form)
				var deferred = TYPO3.Backend.ContentContainer.beforeSetUrl(interactionRequest);
				deferred.then(
					$.proxy(function() {
						if (moduleData.navigationComponentId) {
							this.loadNavigationComponent(moduleData.navigationComponentId);
						} else if (moduleData.navigationFrameScript) {
							TYPO3.Backend.NavigationContainer.show('typo3-navigationIframe');
							this.openInNavFrame(
								moduleData.navigationFrameScript,
								moduleData.navigationFrameScriptParam,
								new TriggerRequest(
									'typo3.loadModuleComponents',
									interactionRequest
								)
							);
						} else {
							TYPO3.Backend.NavigationContainer.hide();
						}

						this.highlightModuleMenuItem(moduleName);
						this.loadedModule = moduleName;
						this.openInContentFrame(
							moduleData.link,
							params,
							new TriggerRequest(
								'typo3.loadModuleComponents',
								interactionRequest
							)
						);

						// compatibility
						top.currentSubScript = moduleData.link;
						top.currentModuleLoaded = moduleName;

						TYPO3.Backend.doLayout();
					}, this
				));

				return deferred;
			},

			/**
			 * Prepends previously saved record id to the url params
			 *
			 * @param {string} moduleName module name e.g. web_list
			 * @param {string} params query string parameters for module url
			 * @return {string}
			 */
			includeId: function (moduleName, params) {
				if (typeof moduleName === 'undefined') {
					return params;
				}
				//get id
				var section = moduleName.split('_')[0];
				if (top.fsMod.recentIds[section]) {
					params = 'id=' + top.fsMod.recentIds[section] + '&' + params;
				}

				return params;
			},

			/**
			 * Renders registered (non-iframe) navigation component e.g. a page tree
			 *
			 * @param {string} navigationComponentId
			 */
			loadNavigationComponent: function (navigationComponentId) {
				TYPO3.Backend.NavigationContainer.show(navigationComponentId);
				if (navigationComponentId === this.loadedNavigationComponentId) {
					return;
				}
				if (this.loadedNavigationComponentId !== '') {
					$('#navigationComponent-' + this.loadedNavigationComponentId).hide();
				}
				if ($('.t3js-scaffold-content-navigation [data-component="' + navigationComponentId + '"]').length < 1) {
					$('.t3js-scaffold-content-navigation')
						.append('<div class="scaffold-content-navigation-component" data-component="' + navigationComponentId + '" id="navigationComponent-' + navigationComponentId + '"></div>');
				}
				// allow to render the page tree hard-coded in order to have acceptance tests apply correctly
				// and to ensure that something is loaded
				var component = Ext.getCmp(navigationComponentId);
				if (typeof this.availableNavigationComponents['typo3-pagetree'] === 'undefined') {
					component = new TYPO3.Components.PageTree.App();
					component.render('navigationComponent-' + navigationComponentId);
					this.availableNavigationComponents['typo3-pagetree'] = component;
					// re-evaluate the component
					component = Ext.getCmp(navigationComponentId);
				}

				if (typeof component === 'undefined') {
					var self = this,
						deferredComponentExists = $.Deferred();

					function checkIfComponentIdIsAvailable(componentId) {
						if (typeof self.availableNavigationComponents[componentId] === 'undefined') {
							setTimeout(function (id) {
								checkIfComponentIdIsAvailable(id);
							}, 100, componentId);
						} else {
							deferredComponentExists.resolve();
						}
					}
					checkIfComponentIdIsAvailable(navigationComponentId);

					deferredComponentExists.promise().done(function() {
						component = self.availableNavigationComponents[navigationComponentId]();
						component.render('navigationComponent-' + navigationComponentId);

						TYPO3.Backend.NavigationContainer.show(navigationComponentId);
						self.loadedNavigationComponentId = navigationComponentId;
					});
				} else {
					// Tree was previously rendered, and was hidden because a different component was displayed
					TYPO3.Backend.NavigationContainer.show(navigationComponentId);
					this.loadedNavigationComponentId = navigationComponentId;
				}
			},

			registerNavigationComponent: function (componentId, initCallback) {
				if (typeof this.availableNavigationComponents[componentId] === 'undefined') {
					this.availableNavigationComponents[componentId] = initCallback;
				}
			},

			/**
			 * @param {string} url
			 * @param {string} params
			 * @param {InteractionRequest} [interactionRequest]
			 * @return {jQuery.Deferred}
			 */
			openInNavFrame: function (url, params, interactionRequest) {
				var navUrl = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '');
				var currentUrl = TYPO3.Backend.NavigationContainer.getUrl();
				var deferred = TYPO3.Backend.NavigationContainer.setUrl(
					url,
					new TriggerRequest('typo3.openInNavFrame', interactionRequest)
				);
				if (currentUrl !== navUrl) {
					// if deferred is already resolved, execute directly
					if (deferred.state() === 'resolved') {
						TYPO3.Backend.NavigationContainer.refresh();
					// otherwise hand in future callback
					} else {
						deferred.then(TYPO3.Backend.NavigationContainer.refresh);
					}
				}
				return deferred;
			},

			/**
			 * @param {string} url
			 * @param {string} params
			 * @param {InteractionRequest} [interactionRequest]
			 * @return {jQuery.Deferred}
			 */
			openInContentFrame: function (url, params, interactionRequest) {
				var deferred;

				if (top.nextLoadModuleUrl) {
					deferred = TYPO3.Backend.ContentContainer.setUrl(
						top.nextLoadModuleUrl,
						new TriggerRequest('typo3.openInContentFrame', interactionRequest)
					);
					top.nextLoadModuleUrl = '';
				} else {
					var urlToLoad = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '');
					deferred = TYPO3.Backend.ContentContainer.setUrl(
						urlToLoad,
						new TriggerRequest('typo3.openInContentFrame', interactionRequest)
					);
				}

				return deferred;
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
				if (PersistentStorage.isset('modulemenu')) {
					return JSON.parse(PersistentStorage.get('modulemenu'));
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
				PersistentStorage.set('modulemenu', JSON.stringify(existingItems));
			},

			/**
			 * removes a module menu item from the local storage
			 * @param item
			 */
			removeCollapseMainMenuItem: function (item) {
				var existingItems = this.getCollapsedMainMenuItems();
				delete existingItems[item];
				PersistentStorage.set('modulemenu', JSON.stringify(existingItems));
			}

		};
		// start the module menu app
		TYPO3.ModuleMenu.App.initialize();
		return TYPO3.ModuleMenu;
	}
);
