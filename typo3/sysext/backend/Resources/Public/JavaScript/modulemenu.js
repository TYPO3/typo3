/**
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
 *
 * @author	Steffen Kamper
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
		if (top.startInModule && top.startInModule[0] && top.startInModule[0].length > 0) {
			me.showModule(top.startInModule[0]);
		} else {
			// fetch first module
			me.showModule(jQuery('.t3-menuitem-submodule:first').attr('id'));
		}

		// check if there are collapsed items in the local storage
		var collapsedMainMenuItems = this.getCollapsedMainMenuItems();
		jQuery.each(collapsedMainMenuItems, function(key, itm) {
			var $headerElement = jQuery('#' + key).find('.modgroup:first');
			if ($headerElement.length > 0) {
				$headerElement.addClass('collapsed').removeClass('expanded').next('.t3-menuitem-submodules').slideUp('fast');
			}
		});

		me.initializeEvents();
	},

	initializeEvents: function() {
		var me = this;
		jQuery(document).on('click', '.t3-menuitem-main .modgroup', function() {
			var $headerElement = jQuery(this);
			if ($headerElement.hasClass('expanded')) {
				me.addCollapsedMainMenuItem($headerElement.parent().attr('id'));
				$headerElement.addClass('collapsed').removeClass('expanded').next('.t3-menuitem-submodules').slideUp();
			} else {
				me.removeCollapseMainMenuItem($headerElement.parent().attr('id'));
				$headerElement.addClass('expanded').removeClass('collapsed').next('.t3-menuitem-submodules').slideDown();
			}
		});

		// register clicking on sub modules
		jQuery(document).on('click', '.t3-menuitem-submodule', function(evt) {
			evt.preventDefault();
			me.showModule(jQuery(this).attr('id'));
		});
	},

	/* fetch the data for a submodule */
	getRecordFromName: function(name) {
		var $subModuleElement = jQuery('#' + name);
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
			TYPO3.Backend.NavigationDummy.hide();
			TYPO3.Backend.NavigationIframe.getEl().parent().setStyle('overflow', 'auto');
		} else if (record.navigationFrameScript) {
			TYPO3.Backend.NavigationDummy.hide();
			TYPO3.Backend.NavigationContainer.show();
			this.loadNavigationComponent('typo3-navigationIframe');
			this.openInNavFrame(record.navigationFrameScript, record.navigationFrameScriptParam);
			TYPO3.Backend.NavigationIframe.getEl().parent().setStyle('overflow', 'hidden');
		} else {
			TYPO3.Backend.NavigationContainer.hide();
			TYPO3.Backend.NavigationDummy.show();
		}

		this.highlightModuleMenuItem(mod);
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
		jQuery('#typo3-menu').find('.highlighted').removeClass('highlighted');
		jQuery('#' + module).addClass('highlighted');
	},

	relativeUrl: function(url) {
		return url.replace(TYPO3.configuration.siteUrl + 'typo3/', '');
	},

		// refresh the HTML by fetching the menu again
	refreshMenu: function() {
		jQuery.ajax(TYPO3.settings.ajaxUrls['ModuleMenu::reload']).done(function(result) {
			jQuery('#typo3-menu').replaceWith(result.menu);
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
		if (typeof localStorage.getItem('t3-modulemenu') !== "undefined" && typeof localStorage.getItem('t3-modulemenu') !== "null" && localStorage.getItem('t3-modulemenu') != 'undefined' && localStorage.getItem('t3-modulemenu')) {
			return JSON.parse(localStorage.getItem('t3-modulemenu'));
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
		localStorage.setItem('t3-modulemenu', JSON.stringify(existingItems));
	},

	/**
	 * removes a module menu item from the local storage
	 * @param item
	 */
	removeCollapseMainMenuItem: function(item) {
		var existingItems = this.getCollapsedMainMenuItems();
		existingItems[item] = null;
		localStorage.setItem('t3-modulemenu', JSON.stringify(jQuery.existingItems));
	}

};



Ext.onReady(function() {
	TYPO3.ModuleMenu.App.initialize();

		// keep backward compatibility
	top.list = TYPO3.Backend.ContentContainer;
	top.list_frame = top.list.getIframe();
	top.nav_frame = TYPO3.Backend.NavigationContainer.PageTree;

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
* Backwards compatability handling down here
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
