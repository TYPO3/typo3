/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2010 Michael Klapper <michael.klapper@aoemedia.de>
 *  (c) 2010-2011 Jeff Segars <jeff@webempoweredchurch.org>
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
Ext.define('TYPO3.BackendLiveSearch.Model', {
	extend: 'Ext.data.Model',
	fields: [{
		name: 'id',
		type: 'string'
	},{
		name: 'type',
		type: 'string'
	},{
		name: 'recordTitle',
		type: 'string'
	},{
		name: 'iconHTML',
		type: 'string'
	},{
		name: 'title',
		type: 'string'
	},{
		name: 'editLink',
		type: 'string'
	},{
		name: 'pageJump',
		type: 'string'
	}]
});
/**
 * Extend Boundlist layout (ExtJS 4.0.7)
 * Make space for live search toolbar
 * Should be reviewed on ExtJS upgrade
 */
Ext.define('TYPO3.BackendLiveSearch.BoundList.Layout', {
	extend: 'Ext.layout.component.BoundList',
	alias: 'layout.livesearchboundlist',
	setTargetSize: function (width, height) {
		var me = this,
			owner = me.owner,
			listHeight = null,
			toolbar;
		me.callParent(arguments);
			// Size the listEl
		if (Ext.isNumber(height)) {
			listHeight = height - owner.el.getFrameWidth('tb');
				// Make space for live search toolbar
			toolbar = owner.liveSearchToolbar;
			if (toolbar) {
				listHeight -= toolbar.getHeight();
			}
		}
		me.setElementSize(owner.listEl, null, listHeight);
	}
});
/**
 * Extend Boundlist (ExtJS 4.0.7)
 * Add live search toolbar instead of paging toolbar
 * Should be reviewed on ExtJS upgrade
 */
Ext.define('TYPO3.BackendLiveSearch.BoundList', {
	extend: 'Ext.view.BoundList',
	/**
	 * Use extended boundlist layout
	 */
	componentLayout: 'livesearchboundlist',
	/**
	 * Create live search toolbar when the boundlist is initialized
	 */
	initComponent: function() {
		this.liveSearchToolbar = this.createLiveSearchToolbar();
		this.callParent();
	},
	/**
	 * Render the live search toolbar when the boundlist is rendered
	 */
	onRender: function() {
		var me = this,
			toolbar = me.liveSearchToolbar;
		me.callParent(arguments);
		if (toolbar) {
			toolbar.render(me.el);
		}
	},
	/**
	 * Create the live search toolbar
	 *
	 * @return	Object{Ext.toolbar.Toolbar}
	 */
	createLiveSearchToolbar: function () {
		return Ext.create('Ext.toolbar.Toolbar', {
			height: 30,
			items: [{
				xtype: 'tbfill',
				flex: 1
			},{
				xtype: 'button',
				text: TYPO3.LLL.liveSearch.showAllResults,
				arrowAlign: 'right',
				shadow: false,
				icon: '../typo3/sysext/t3skin/icons/module_web_list.gif',
				listeners: {
					click: {
						fn: function () {
								// go to db_list.php and search for given search value
								// @todo the current selected page ID from the page tree is required, also we need the
								// values of $GLOBALS['BE_USER']->returnWebmounts() to search only during the allowed pages
							TYPO3.ModuleMenu.App.showModule('web_list', this.pickerField.getSearchResultsUrl(this.pickerField.getValue()));
							this.pickerField.collapse();
						},
						scope: this
						
					}
				}
			}]
		});	
	}
});
/**
 * Extend Ext.form.field.ComboBox (ExtJS 4.0.7)
 * Review createPicker method on ExtJS upgrade
 */
Ext.define('TYPO3.BackendLiveSearch.ComboBox', {
	extend: 'Ext.form.field.ComboBox',
	dataProvider: null,
	searchResultsPid: 0,
	autoSelect: false,
	displayField: 'title',
	emptyText: '',
	enableKeyEvents: true,
	helpTitle: null,
	hideTrigger: true,
	listConfig: {
		cls: 'live-search-list',
		itemCls: 'search-item-title',
		tpl: Ext.create('Ext.XTemplate',
			'<table>',
				'<tr><th colspan="2" class="live-search-list-title">' + TYPO3.LLL.liveSearch.title + '<th><tr>',
				'<tpl for=".">',
					'<tr class="search-item">',
						'<td class="search-item-type">{recordTitle}</td>',
						'<td class="search-item-content">',
							'<div class="search-item-title">{iconHTML} {title}</div>',
						'</td>',
					'</tr>',
				'</tpl>',
			'</table>'
		),
		loadingHeight: '200',
		loadingText: '',
		overCls: 'live-search-list-over',
		resizable: false,
		width: 315,
		listeners: {
				// Keep the focus on the input field so that the blur event is triggered when another iframe is clicked
			containermouseout: {
				fn: function (list) {
					list.pickerField.focus();
				}
			}
		}
	},
	minChars: 1,
	pickerAlign: 'tr-br',
	title: null,
	triggerBaseCls: 'x-form-trigger t3-icon t3-icon-actions t3-icon-actions-input t3-icon-input-clear t3-tceforms-input-clearer',
	width: 205,
	listeners: {
		select: {
			fn: function (combo, records) {
					// We do not wish to have the value selected in the results list
					// to be displayed in the search field.
				combo.setRawValue(combo.lastQuery);
				jump(records[0].get('editLink'), 'web_list', 'web');
			}
		},
			// If the search field is null, blank or emptyText, show the help on focus.
			// Otherwise, show last results
		focus: {
			fn: function (combo) {
				if (!combo.getValue() || combo.getValue() == combo.emptyText) {
					combo.initHelp();
				} else {
					combo.expand();
				}

			}
		},
		blur: {
			fn: function (combo) {
				combo.collapse();
				combo.removeHelp();
			}
		},
		keyup: {
			fn: function (combo, event) {
				if (!combo.getValue() || combo.getValue() == combo.emptyText) {
					combo.setHideTrigger(true);
				} else {
					combo.setHideTrigger(false);
				}
			}
		},
		specialkey: {
			fn: function (combo, event) {
				if (event.getKey() == event.RETURN || event.getKey() == event.ENTER) {
					var rawData = combo.getStore().getProxy().getReader().rawData;
					if (rawData && rawData.pageJump != '') {
						jump(rawData.pageJump, 'web_list', 'web');
					} else {
						TYPO3.ModuleMenu.App.showModule('web_list', this.getSearchResultsUrl(combo.getValue()));
					}
				}
			}
		},
		beforequery: {
			fn: function (queryEvent) {
				queryEvent.combo.removeHelp();
			}	
		}
	},

	/**
	 * Create the store and initialize the component.
	 */
	initComponent: function() {
		this.store = Ext.data.StoreManager.lookup(this.getId() + 'BackendLiveSearchStore');
		if (!this.store) {
			this.store = Ext.create('Ext.data.Store', {
				model: 'TYPO3.BackendLiveSearch.Model',
				proxy: {
					type: 'direct',
					directFn: this.dataProvider.find,
					reader: {
						type: 'json',
						idProperty: 'type',
						root: 'searchItems'
					}
				},
				storeId: this.getId() + 'BackendLiveSearchStore'
			});
		}
		this.listConfig.loadingText = this.loadingText;
		this.callParent();
	},
	/**
	 * Create the list of search results
	 *
	 * @return Object{TYPO3.BackendLiveSearch.BoundList}
	 */
	createPicker: function() {
		var me = this,
			picker,
			menuCls = Ext.baseCSSPrefix + 'menu',
			opts = Ext.apply({
				pickerField: me,
				selModel: {
					mode: me.multiSelect ? 'SIMPLE' : 'SINGLE'
				},
				floating: true,
				hidden: true,
				ownerCt: me.ownerCt,
				cls: me.el.up('.' + menuCls) ? menuCls : '',
				store: me.store,
				displayField: me.displayField,
				focusOnToFront: false,
				pageSize: 0,
				tpl: me.tpl
			}, me.listConfig, me.defaultListConfig);

		picker = me.picker = Ext.create('TYPO3.BackendLiveSearch.BoundList', opts);

		me.mon(picker, {
			itemclick: me.onItemClick,
			refresh: me.onListRefresh,
			scope: me
		});

		me.mon(picker.getSelectionModel(), {
			'beforeselect': me.onBeforeSelect,
			'beforedeselect': me.onBeforeDeselect,
			'selectionchange': me.onListSelectionChange,
			scope: me
		});

		return picker;
	},
	/**
	 * Empty the search field, give it focus and collapse the results list when the trigger is clicked
	 */
	onTriggerClick: function () {
		this.reset();
		this.focus();
		this.collapse();
	},
	/**
	 * Add a help layer when the field is focused while empty
	 */
	initHelp: function () {
		if (!this.helpList){
			var cls = 'search-list-help';
			this.helpList = Ext.create('Ext.Layer', {
				parentEl: this.getEl(),
				shadow: this.shadow,
				cls: [this.listConfig.cls, cls].join(' '),
				constrain: false
			});
			var helpListWidth = this.listConfig.width;
			this.helpList.setSize(helpListWidth);
				// Keep the focus on the input field so that the blur event is triggered when another iframe is clicked
			this.helpList.on('mouseout', function () { this.focus(); }, this);
			this.helpList.swallowEvent('mousewheel');
			this.helpList.setStyle('font-size', this.getEl().getStyle('font-size'));
			this.helpList.createChild({
				cls: cls + '-content',
				// @todo Can we grab this content via ExtDirect?
				html: '<strong>' + this.helpTitle + '</strong><p>' + TYPO3.LLL.liveSearch.helpDescription + '<br /> ' + TYPO3.LLL.liveSearch.helpDescriptionPages + '</p>'
			});
			this.helpList.alignTo(this.getEl(), this.pickerAlign);
			this.helpList.show();
		}
	},
	/**
	 * Remove the help layer when a query search is being submitted
	 */
	removeHelp: function () {
		if (this.helpList) {
			this.helpList.destroy();
			delete this.helpList;
		}
	},
	/**
	 * Hide the trigger and clear the search field
	 */
	reset: function () {
		this.setHideTrigger(true);
		this.callParent();
	},
	/**
	 * Build the url params of the search results
	 *
	 * @return	String
	 */
	getSearchResultsUrl: function (searchTerm) {
		return 'id=' + this.searchResultsPid + '&search_levels=4&search_field=' + searchTerm;
	},
	/**
	 * If the mouse is not over the list and the field lost focus to another iframe
	 */
	handleBlur: function (event) {
		if (this.destroying) {
			return;
		}
		if ((this.getPicker().el && !this.getPicker().el.hasCls('live-search-list-over')) || this.helpList) {
			if (!this.getValue()) {
				this.reset();
			};
			this.inputEl.removeCls(this.focusCls);
			this.hasFocus = false;
			this.fireEvent('blur', this);
		}
	}
});
/**
 * Create the live search box when Ext is ready
 */
Ext.onReady(function() {
	TYPO3.BackendLiveSearch.Box = Ext.create('TYPO3.BackendLiveSearch.ComboBox', {
		dataProvider: TYPO3.LiveSearchActions.ExtDirect,
		helpTitle: TYPO3.LLL.liveSearch.helpTitle,
		emptyText: TYPO3.LLL.liveSearch.emptyText,
		loadingText: TYPO3.LLL.liveSearch.loadingText,
		renderTo: Ext.get('live-search-box'),
		searchResultsPid: TYPO3.configuration.firstWebmountPid
	});
		// Add a blur event listener outside the ExtJS widget to handle clicks in iframes also.
	Ext.get('live-search-box').down('input').on('blur', TYPO3.BackendLiveSearch.Box.handleBlur, TYPO3.BackendLiveSearch.Box);
});
