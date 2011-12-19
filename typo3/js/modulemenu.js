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

Ext.define('TYPO3.model.ModuleMenu', {
	extend: 'Ext.data.Model',
	idProperty: 'index',
	fields: [{
			name: 'index',
			type: 'int',
		},{
			name: 'key',
			type: 'string'
		},{
			name: 'name',
			type: 'string'
		},{
			name: 'label',
			type: 'string'
		},{
			name: 'description',
			type: 'string'
		},{
			name: 'icon',
			type: 'string'
		},{
			name: 'menuState',
			type: 'int'
		},{
			name: 'navigationComponentId',
			type: 'string'
		},{
			name: 'navigationFrameScript',
			type: 'string'
		},{
			name: 'navframe',
			type: 'string'
		},{
			name: 'navigationFrameScriptParam',
			type: 'string'
		},{
			name: 'link',
			type: 'string'
		},{
			name: 'originalLink',
			type: 'string'
		},{
			name: 'subitems',
			type: 'int'
	}],
	associations: [{
			name: 'sub',
			type: 'hasMany',
			model: 'TYPO3.model.ModuleMenu'
	}]
});
TYPO3.ModuleMenu.Store = Ext.create('Ext.data.Store', {
	storeId: 'moduleMenuStore',
	model: 'TYPO3.model.ModuleMenu',
	proxy: {
		type: 'ajax',
		url: 'ajax.php?ajaxID=ModuleMenu::getData',
		extraParams: {
			'action': 'getModules'
		},
		reader: {
		    type: 'json',
		    root: 'root'
		}
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

TYPO3.ModuleMenu.Template = Ext.create('Ext.XTemplate',
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
			callback: function(records, operation, success) {
				this.renderMenu(records);
			}
		});
	},

	renderMenu: function(records) {
		TYPO3.Backend.ModuleMenuContainer.removeAll();
		TYPO3.Backend.ModuleMenuContainer.addDocked({
			cls: 'typo3-module-panel-toolbar',
			height: 22,
			html: '<div id="typo3-docheader">' +
				'	<div id="typo3-docheader-row1">' +
				'		<div class="buttonsleft"></div>' +
				'		<div class="buttonsright"></div>' +
				'	</div>' +
				'</div>',
			xtype: 'component'
		});
		TYPO3.Backend.ModuleMenuContainer.add({
			xtype: 'dataview',
			store: TYPO3.ModuleMenu.Store,
			tpl: TYPO3.ModuleMenu.Template,
			singleSelect: true,
			itemSelector: 'li.submodule',
			overItemCls: 'x-view-over',
			trackOver: true,
			selectedItemCls: 'highlighted',
			itemId: 'modDataView',
				// ExtJS is not really ready to handle nested template...
			updateIndexes: function(startIndex, endIndex) {
				var ns = this.all.elements,
					records = this.store.getRange(),
					index = 0, i, j, m, n, record, items;
				startIndex = startIndex || 0;
				endIndex = endIndex || ((endIndex === 0) ? 0 : (ns.length - 1));
				for (i = 0, n = records.length -1; i < n; i++) {
					items = records[i].getAssociatedData().sub;
					if (items.length) {
						for (j = 0, m = items.length - 1; j < m; j++) {
							if (startIndex <= index && index <= endIndex) {
								ns[index].viewIndex = index;
								ns[index].viewRecordId = records[i].internalId;
								if (!ns[index].boundView) {
									ns[index].boundView = this.id;
								}
							}
							index++;
						}
					}
				}
			},
			listeners: {
				viewready: {
					fn: function () {
						if (top.startInModule) {
							this.showModule(top.startInModule[0], top.startInModule[1]);
						} else {
							this.loadFirstAvailableModule();
						}
					},
					scope: this
				},
					// The selection of this view is on the main module. We don't need this
				beforeselect: {
					fn: function (view) {
						return false;
					}
				},
				itemclick: {
					fn: function(view, record, node, index, event) {
						var moduleName = node.getAttribute('id');
						if (moduleName) {
							TYPO3.ModuleMenu.App.showModule(moduleName);
						}
					}
				},
				containerclick: {
					fn: function(view, event) {
						var item = event.getTarget('li.menuSection', view.getEl());
						if (item) {
							var el = Ext.get(item);
							var id = el.getAttribute('id');
							var section = el.first('div'), state;
							if (section.hasCls('expanded')) {
								state = true;
								section.removeCls('expanded').addCls('collapsed');
								el.first('ul').slideOut('t', {
									easing: 'easeOut',
									duration: .2,
									remove: false,
									useDisplay: true
								});
							} else {
								state = false;
								section.removeCls('collapsed').addCls('expanded');
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
					}
				}
			}
		});
	},
	getRecordFromIndex: function(index) {
		var i, record, items;
		for (i = 0; i < TYPO3.ModuleMenu.Store.getCount(); i++) {
			record = TYPO3.ModuleMenu.Store.getAt(i);
			items = record.getAssociatedData().sub;
			if (index < record.get('subitems')) {
				return items[index];
			}
			index -= record.get('subitems');
		}
	},

	getRecordFromName: function(name) {
		var i, j, recordsCount, itemsCount, record, items;
		for (i = 0, recordsCount = TYPO3.ModuleMenu.Store.getCount(); i < recordsCount; i++) {
			record = TYPO3.ModuleMenu.Store.getAt(i);
			items = record.getAssociatedData().sub;
			for (j = 0, itemsCount = record.get('subitems'); j < itemsCount; j++) {
				if (items[j].name === name) {
					return items[j];
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
			mod = TYPO3.ModuleMenu.Store.getAt(0).getAssociatedData().sub[0];
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
			// Set internal state
		this.loadedModule = mod;
		this.highlightModuleMenuItem(mod);
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
		var relatedCard, urlToLoad;
		if (top.nextLoadModuleUrl) {
			TYPO3.Backend.ContentContainer.setUrl(top.nextLoadModuleUrl);
			top.nextLoadModuleUrl = '';
		} else {
			relatedCard = Ext.getCmp('typo3-contentContainerWrapper').getComponent('typo3-card-' + this.loadedModule);
			urlToLoad   = url + (params ? (url.indexOf('?') !== -1 ? '&' : '?') + params : '')
			if(relatedCard) {
				if (typeof relatedCard.setUrl === 'function') {
					relatedCard.setUrl(urlToLoad);
				}
				Ext.getCmp('typo3-contentContainerWrapper').getLayout().setActiveItem('typo3-card-' + this.loadedModule);
			} else {
				TYPO3.Backend.ContentContainer.setUrl(urlToLoad);
				Ext.getCmp('typo3-contentContainerWrapper').getLayout().setActiveItem('typo3-contentContainer');
			}
		}
	},

	highlightModuleMenuItem: function(module, mainModule) {
		var highlighted = Ext.fly('typo3-menu').query('li.highlighted');
		Ext.Array.each(highlighted, function(el) {
			Ext.fly(el).removeCls('highlighted');
		});
		Ext.fly(module).addCls('highlighted');
	},

	relativeUrl: function(url) {
		return url.replace(TYPO3.configuration.siteUrl + 'typo3/', '');
	},

	refreshMenu: function() {
		TYPO3.ModuleMenu.Store.load({
			scope: this,
			callback: function(records, operation, success) {
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
