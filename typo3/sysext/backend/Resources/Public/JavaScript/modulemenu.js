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


Ext.ns('TYPO3', 'ModuleMenu');

TYPO3.ModuleMenu = {};

TYPO3.ModuleMenu.App = {
	loadedModule: null,
	loadedNavigationComponentId: '',
	availableNavigationComponents: {},

	initialize: function() {
		var me = this;

		// load the start module
		if (top.startInModule && top.startInModule[0] && TYPO3.jQuery('#' + top.startInModule[0]).length > 0) {
			me.showModule(top.startInModule[0], top.startInModule[1]);
		} else {
			// fetch first module
			me.showModule(TYPO3.jQuery('.t3js-mainmodule:first').attr('id'));
		}

		// check if there are collapsed items in the users' configuration
		require(['TYPO3/CMS/Backend/Storage'], function() {
			var collapsedMainMenuItems = me.getCollapsedMainMenuItems();
			TYPO3.jQuery.each(collapsedMainMenuItems, function(key, itm) {
				if (itm !== true) {
					return;
				}
				var $group = TYPO3.jQuery('#' + key);
				if ($group.length > 0) {
					var $groupContainer = $group.find('.typo3-module-menu-group-container');
					$group.addClass('collapsed').removeClass('expanded');
					$groupContainer.hide().promise().done(function() {
						TYPO3.Backend.doLayout();
					});
				}
			});
			me.initializeEvents();
		});
	},

	initializeEvents: function() {
		var me = this;
		TYPO3.jQuery(document).on('click', '.typo3-module-menu-group .typo3-module-menu-group-header', function() {
			var $group = TYPO3.jQuery(this).parent('.typo3-module-menu-group');
			var $groupContainer = $group.find('.typo3-module-menu-group-container');
			if ($group.hasClass('expanded')) {
				me.addCollapsedMainMenuItem($group.attr('id'));
				$group.addClass('collapsed').removeClass('expanded');
				$groupContainer.stop().slideUp().promise().done(function() {
					TYPO3.Backend.doLayout();
				});
			} else {
				me.removeCollapseMainMenuItem($group.attr('id'));
				$group.addClass('expanded').removeClass('collapsed');
				$groupContainer.stop().slideDown().promise().done(function() {
					TYPO3.Backend.doLayout();
				});
			}
		});
		// register clicking on sub modules
		TYPO3.jQuery(document).on('click', '.typo3-module-menu-item,.t3-menuitem-submodule', function(evt) {
			evt.preventDefault();
			me.showModule(TYPO3.jQuery(this).attr('id'));
		});
	},

	/* fetch the data for a submodule */
	getRecordFromName: function(name) {
		var $subModuleElement = TYPO3.jQuery('#' + name);
		return {
			name: name,
			navigationComponentId: $subModuleElement.data('navigationcomponentid'),
			navigationFrameScript: $subModuleElement.data('navigationframescript'),
			navigationFrameScriptParam: $subModuleElement.data('navigationframescriptparameters'),
			link: $subModuleElement.find('a').attr('href')
		};
	},

	showModule: function(mod, params) {
		params = params || '';
		params = this.includeId(mod, params);
		var record = this.getRecordFromName(mod);
		this.loadModuleComponents(record, params);
	},

	loadModuleComponents: function(record, params) {
		var mod = record.name;
		if (record.navigationComponentId) {
			this.loadNavigationComponent(record.navigationComponentId);
			TYPO3.Backend.NavigationIframe.getEl().parent().setStyle('overflow', 'auto');
		} else if (record.navigationFrameScript) {
			TYPO3.Backend.NavigationContainer.show();
			this.loadNavigationComponent('typo3-navigationIframe');
			this.openInNavFrame(record.navigationFrameScript, record.navigationFrameScriptParam);
			TYPO3.Backend.NavigationIframe.getEl().parent().setStyle('overflow', 'hidden');
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

	includeId: function(mod, params) {
			//get id
		var section = mod.split('_')[0];
		if (top.fsMod.recentIds[section]) {
			params = 'id=' + top.fsMod.recentIds[section] + '&' + params;
		}

		return params;
	},

	loadNavigationComponent: function(navigationComponentId) {
		if (navigationComponentId === this.loadedNavigationComponentId) {
			if (TYPO3.Backend.NavigationContainer.hidden) {
				TYPO3.Backend.NavigationContainer.show();
			}

			return;
		}

		if (this.loadedNavigationComponentId !== '') {
			Ext.getCmp(this.loadedNavigationComponentId).hide();
		}

		var component = Ext.getCmp(navigationComponentId);
		if (typeof component !== 'object') {
			if (typeof this.availableNavigationComponents[navigationComponentId] !== 'function') {
				throw 'The navigation component "' + navigationComponentId + '" is not available ' +
					'or has no valid callback function';
			}

			component = this.availableNavigationComponents[navigationComponentId]();
			TYPO3.Backend.NavigationContainer.add(component);
		}

		component.show()

			// backwards compatibility
		top.nav = component;

		TYPO3.Backend.NavigationContainer.show();
		this.loadedNavigationComponentId = navigationComponentId;
	},

	registerNavigationComponent: function(componentId, initCallback) {
		this.availableNavigationComponents[componentId] = initCallback;
	},

	openInNavFrame: function(url, params) {
		var navUrl = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '');
		var currentUrl = this.relativeUrl(TYPO3.Backend.NavigationIframe.getUrl());
		if (currentUrl !== navUrl) {
			TYPO3.Backend.NavigationIframe.setUrl(navUrl);
		}
	},

	openInContentFrame: function(url, params) {
		if (top.nextLoadModuleUrl) {
			TYPO3.Backend.ContentContainer.setUrl(top.nextLoadModuleUrl);
			top.nextLoadModuleUrl = '';
		} else {
			var urlToLoad = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '')
			TYPO3.Backend.ContentContainer.setUrl(urlToLoad);
		}
	},

	highlightModuleMenuItem: function(module, mainModule) {
		TYPO3.jQuery('.typo3-module-menu-item.active').removeClass('active');
		TYPO3.jQuery('#' + module).addClass('active');
	},

	relativeUrl: function(url) {
		return url.replace(TYPO3.configuration.siteUrl + 'typo3/', '');
	},

		// refresh the HTML by fetching the menu again
	refreshMenu: function() {
		TYPO3.jQuery.ajax(TYPO3.settings.ajaxUrls['modulemenu']).done(function(result) {
			TYPO3.jQuery('#typo3-menu').replaceWith(result.menu);
			if (top.currentModuleLoaded) {
				TYPO3.ModuleMenu.App.highlightModuleMenuItem(top.currentModuleLoaded);
			}
			TYPO3.Backend.doLayout();
		});
	},

	reloadFrames: function() {
		TYPO3.Backend.NavigationIframe.refresh();
		TYPO3.Backend.ContentContainer.refresh();
	},

	/**
	 * fetches all module menu elements in the local storage that should be collapsed
	 * @returns {*}
	 */
	getCollapsedMainMenuItems: function() {
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
	addCollapsedMainMenuItem: function(item) {
		var existingItems = this.getCollapsedMainMenuItems();
		existingItems[item] = true;
		TYPO3.Storage.Persistent.set('modulemenu', JSON.stringify(existingItems));
	},

	/**
	 * removes a module menu item from the local storage
	 * @param item
	 */
	removeCollapseMainMenuItem: function(item) {
		var existingItems = this.getCollapsedMainMenuItems();
		delete existingItems[item];
		TYPO3.Storage.Persistent.set('modulemenu', JSON.stringify(existingItems));
	}

};



Ext.onReady(function() {
	TYPO3.ModuleMenu.App.initialize();

		// keep backward compatibility
	top.list = TYPO3.Backend.ContentContainer;
	top.list_frame = top.list.getIframe();
	top.nav_frame = TYPO3.Backend.NavigationContainer.PageTree;

	// not in use anymore
	top.TYPO3ModuleMenu = TYPO3.ModuleMenu.App;
	top.content = {
		nav_frame: TYPO3.Backend.NavigationContainer.PageTree,
		list_frame: TYPO3.Backend.ContentContainer.getIframe(),
		location: TYPO3.Backend.ContentContainer.getIframe().location,
		document: TYPO3.Backend.ContentContainer.getIframe()
	}
});


/*******************************************************************************
*
* Backwards compatibility handling down here
*
******************************************************************************/

/**
* Highlight module:
*/
var currentlyHighLightedId = '';
var currentlyHighLighted_restoreValue = '';
var currentlyHighLightedMain = '';
function highlightModuleMenuItem(trId, mainModule) {
	TYPO3.ModuleMenu.App.highlightModule(trId, mainModule);
}
