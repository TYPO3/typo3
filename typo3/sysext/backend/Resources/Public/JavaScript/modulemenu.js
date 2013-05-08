/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Steffen Kamper <steffen@typo3.org>
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
 * Class to render the module menu and handle the BE navigation
 *
 * @author	Steffen Kamper
 */


Ext.ns('TYPO3', 'ModuleMenu');

TYPO3.ModuleMenu = {};

TYPO3.ModuleMenu.Store = new Ext.data.JsonStore({
	storeId: 'ModuleMenuStore',
	root: 'root',
	fields: [
		{name: 'index', type: 'int', mapping: 'sub.index'},
		{name: 'key', type: 'string'},
		{name: 'label', type: 'string'},
		{name: 'menuState', type: 'int'},
		{name: 'subitems', type: 'int'},
		'sub'
	],
	url: 'ajax.php?ajaxID=ModuleMenu::getData',
	baseParams: {
		'action': 'getModules'
	},
	listeners: {
		beforeload: function(store) {
			this.loaded = false;
		},
		load: function(store) {
			this.loaded = true;
		}
	},
	// Custom indicator for loaded store:
	loaded: false,
	isLoaded: function() {
		return this.loaded;
	}

});

TYPO3.ModuleMenu.Template = new Ext.XTemplate(
		'<div id="typo3-docheader">',
		'	<div class="typo3-docheader-functions">',
		'	</div>',
		'</div>',
		'<ul id="typo3-menu">',
		'<tpl for=".">',
		'	<li class="menuSection" id="{key}">',
		'		<div class="modgroup {[this.getStateClass(values)]}">{label}</div>',
		'	<ul {[this.getStateStyle(values)]}>',
		'	<tpl for="sub">',
		'	<li id="{name}" class="submodule mod-{name}">',
		'		<a title="{description}" href="#" class="modlink">',
		'			<span class="submodule-icon">',
		'				<img width="16" height="16" alt="{label}" title="{label}" src="{icon}" />',
		'			</span>',
		'			<span>{label}</span>',
		'		</a>',
		'	</li>',
		'	</tpl>',
		'	</ul>',
		'	</li>',
		'</tpl>',
		'</ul>',
		{
			getStateClass: function(value) {
				return value.menuState ? 'collapsed' : 'expanded';
			},
			getStateStyle: function(value) {
				return value.menuState ? 'style="display:none"' : '';
			}
		}
);

TYPO3.ModuleMenu.App = {
	loadedModule: null,
	loadedNavigationComponentId: '',
	availableNavigationComponents: {},

	init: function() {
		TYPO3.ModuleMenu.Store.load({
			scope: this,
			callback: function(records, options) {
				this.renderMenu(records);
				if (top.startInModule) {
					this.showModule(top.startInModule[0], top.startInModule[1]);
				} else {
					this.loadFirstAvailableModule();
				}
			}
		});
	},

	renderMenu: function(records) {
		TYPO3.Backend.ModuleMenuContainer.removeAll();
		TYPO3.Backend.ModuleMenuContainer.add({
			xtype: 'dataview',
			animCollapse: true,
			store: TYPO3.ModuleMenu.Store,
			tpl: TYPO3.ModuleMenu.Template,
			singleSelect: true,
			itemSelector: 'li.submodule',
			overClass: 'x-view-over',
			selectedClass: 'highlighted',
			autoHeight: true,
			itemId: 'modDataView',
			tbar: [{text: 'test'}],
			listeners: {
				click: function(view, index, node, event) {
					var el = Ext.fly(node);
					if (el.hasClass('submodule')) {
						TYPO3.ModuleMenu.App.showModule(el.getAttribute('id'));
					}
				},
				containerclick: function(view, event) {
					var item = event.getTarget('li.menuSection', view.getEl());
					if (item) {
						var el = Ext.fly(item);
						var id = el.getAttribute('id');
						var section = el.first('div'), state;
						if (section.hasClass('expanded')) {
							state = true;
							section.removeClass('expanded').addClass('collapsed');
							el.first('ul').slideOut('t', {
								easing: 'easeOut',
								duration: .2,
								remove: false,
								useDisplay: true
							});

						} else {
							state = false;
							section.removeClass('collapsed').addClass('expanded');
							el.first('ul').slideIn('t', {
								easing: 'easeIn',
								duration: .2,
								remove: false,
								useDisplay: true
							});
						}
						// save menu state
						Ext.Ajax.request({
							url: 'ajax.php?ajaxID=ModuleMenu::saveMenuState',
							params: {
								'menuid': 'modmenu_' + id,
								'state': state
							}
						});
					}
					return false;
				},
				scope: this
			}
		});
		TYPO3.Backend.ModuleMenuContainer.doLayout();
	},

	getRecordFromIndex: function(index) {
		var i, record;
		for (i = 0; i < TYPO3.ModuleMenu.Store.getCount(); i++) {
			record = TYPO3.ModuleMenu.Store.getAt(i);
			if (index < record.data.subitems) {
				return record.data.sub[index];
			}
			index -= record.data.subitems;
		}
	},

	getRecordFromName: function(name) {
		var i, j, record;
		for (i = 0; i < TYPO3.ModuleMenu.Store.getCount(); i++) {
			record = TYPO3.ModuleMenu.Store.getAt(i);
			for (j = 0; j < record.data.subitems; j++) {
				if (record.data.sub[j].name === name) {
					return record.data.sub[j];
				}
			}
		}
	},

	showModule: function(mod, params) {
		params = params || '';
		this.selectedModule = mod;

		params = this.includeId(mod, params);
		var record = this.getRecordFromName(mod);

		if (record) {
			this.loadModuleComponents(record, params);
		} else {
				//defined startup module is not present, use the first available instead
			this.loadFirstAvailableModule(params);
		}
	},

	loadFirstAvailableModule: function(params) {
		params = params || '';
		if (TYPO3.ModuleMenu.Store.isLoaded() === false) {
			new Ext.util.DelayedTask(
				this.loadFirstAvailableModule,
				this,
				[params]
			).delay(250);
		} else if (TYPO3.ModuleMenu.Store.getCount() === 0) {
				// Store is empty, something went wrong
			TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Module loader', 'No module found. If this is a temporary error, please reload the Backend!', 50000);
		} else {
			mod = TYPO3.ModuleMenu.Store.getAt(0).data.sub[0];
			this.loadModuleComponents(mod, params);
		}
	},

	loadModuleComponents: function(record, params) {
		var mod = record.name;
		if (record.navigationComponentId) {
				this.loadNavigationComponent(record.navigationComponentId);
				TYPO3.Backend.NavigationDummy.hide();
				TYPO3.Backend.NavigationIframe.getEl().parent().setStyle('overflow', 'auto');
			} else if (record.navframe || record.navigationFrameScript) {
				TYPO3.Backend.NavigationDummy.hide();
				TYPO3.Backend.NavigationContainer.show();
				this.loadNavigationComponent('typo3-navigationIframe');
				this.openInNavFrame(record.navigationFrameScript || record.navframe, record.navigationFrameScriptParam);
				TYPO3.Backend.NavigationIframe.getEl().parent().setStyle('overflow', 'hidden');
			} else {
				TYPO3.Backend.NavigationContainer.hide();
				TYPO3.Backend.NavigationDummy.show();
			}

			this.highlightModuleMenuItem(mod);
			this.loadedModule = mod;
			this.openInContentFrame(record.originalLink, params);

				// compatibility
			top.currentSubScript = record.originalLink;
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
		var urlToLoad;
		if (top.nextLoadModuleUrl) {
			TYPO3.Backend.ContentContainer.setUrl(top.nextLoadModuleUrl);
			top.nextLoadModuleUrl = '';
		} else {
			urlToLoad = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '')
			TYPO3.Backend.ContentContainer.setUrl(urlToLoad);
			return;
		}
	},

	highlightModuleMenuItem: function(module, mainModule) {
		TYPO3.Backend.ModuleMenuContainer.getComponent('modDataView').select(module, false, false);
	},

	relativeUrl: function(url) {
		return url.replace(TYPO3.configuration.siteUrl + 'typo3/', '');
	},

	refreshMenu: function() {
		TYPO3.ModuleMenu.Store.load({
			scope: this,
			callback: function(records, options) {
				this.renderMenu(records);
				if (this.loadedModule) {
					this.highlightModuleMenuItem(this.loadedModule);
				}
			}
		});
	},

	reloadFrames: function() {
		TYPO3.Backend.NavigationIframe.refresh();
		TYPO3.Backend.ContentContainer.refresh();
	}

};



Ext.onReady(function() {
	TYPO3.ModuleMenu.App.init();

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
