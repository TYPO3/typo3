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

Ext.namespace('TYPO3');

TYPO3.BackendLiveSearch = Ext.extend(Ext.form.ComboBox, {
	autoSelect: false,
	ctCls: 'live-search-results',
	dataProvider: null,
	searchResultsPid : 0,
	displayField: 'title',
	emptyText: null,
	enableKeyEvents: true,
	helpTitle: null,
	hideTrigger: true,
	itemSelector: 'div.search-item-title',
	listAlign : 'tr-br',
	listClass: 'live-search-list',
	listEmptyText: null,
	listWidth: 400,
	listHovered: false,
	loadingText: null,
	minChars: 1,
	resizable: false,
	title: null,
	triggerClass : 'x-form-clear-trigger',
	triggerConfig: '<span class="t3-icon fa fa-remove"></span>',
	onTriggerClick: function() {
		// Empty the form field, give it focus, and collapse the results
		this.reset(this);
		this.focus();
		this.collapse();
	},
	tpl: new Ext.XTemplate(
		'<table border="0" cellspacing="0">',
			'<tpl for=".">',
				'<tr class="search-item">',
					'<td class="search-item-type">{recordTitle}</td>',
					'<td class="search-item-content" width="95%">',
						'<div class="search-item-title">{iconHTML} {title}</div>',
					'</td>',
				'</tr>',
			'</tpl>',
		'</table>'
	),

	dataReader : new Ext.data.JsonReader({
		idProperty : 'type',
		root : 'searchItems',
		fields : [
			{name: 'recordTitle'},
			{name: 'pageId'},
			{name: 'id'},
			{name: 'iconHTML'},
			{name: 'title'},
			{name: 'editLink'}
		]
	}),
	listeners: {
		select : {
			scope: this,
			fn: function (combo, record, index) {
				jump(record.data.editLink, 'web_list', 'web', record.data.pageId);
			}
		},
		focus : {
			fn: function() {
				if (this.getValue() == this.emptyText) {
					this.reset(this);
				}
			}
		},
		specialkey : function (field, e) {
			if (e.getKey() == e.RETURN || e.getKey() == e.ENTER) {
				if (this.dataReader.jsonData.pageJump != '') {
					jump(this.dataReader.jsonData.pageJump, 'web_list', 'web');
				} else {
					TYPO3.ModuleMenu.App.showModule('web_list', this.getSearchResultsUrl(this.getValue()));
				}
			}
		},
		keyup : function() {
			if ((this.getValue() == this.emptyText) || (this.getValue() == '')) {
				this.setHideTrigger(true);
			} else {
				this.setHideTrigger(false);
			}
		}
	},

	/**
	 * Initializes the component.
	 */
	initComponent: function() {
		this.store = new Ext.data.DirectStore({
			directFn: this.dataProvider.find,
			reader: this.dataReader
		});
		TYPO3.BackendLiveSearch.superclass.initComponent.apply(this, arguments);
	},

	restrictHeight : function(){
		this.innerList.dom.style.height = '';
		this.innerList.dom.style.width = '';
		this.list.beginUpdate();
		this.list.setHeight('auto');
		this.list.endUpdate();
	},

	initList : function () {
		TYPO3.BackendLiveSearch.superclass.initList.apply(this, arguments);
		var cls = 'x-combo-list';

			// Track whether the hovering over the results list or not, to aid in detecting iframe clicks.
		this.mon(this.list, 'mouseover', function() {this.listHovered = true;}, this);
		this.mon(this.list, 'mouseout', function() {this.listHovered = false; }, this);

		/**
		 * Create bottom Toolbar to the result layer
		 */
		this.footer = this.list.createChild({cls:cls+'-ft'});
		this.pageTb = new Ext.Toolbar({
			renderTo:this.footer,
			items: [{
				xtype: 'tbfill',
				autoWidth : true
			},{
				xtype: 'button',
				text: TYPO3.LLL.liveSearch.showAllResults,
				shadow: false,
				listeners : {
					scope : this,
					click : function () {
							// go to db_list.php and search for given search value
							// @todo the current selected page ID from the page tree is required
						TYPO3.ModuleMenu.App.showModule('web_list', this.getSearchResultsUrl(this.getValue()));
						this.collapse();
					}
				}
			}]
		});
	},

	initQuery : function(){
		TYPO3.BackendLiveSearch.superclass.initQuery.apply(this, arguments);
		this.removeHelp();
	},
	initHelp : function () {
		if(!this.helpList){
			var cls = 'search-list-help';

			this.helpList = new Ext.Layer({
				parentEl: this.getListParent(),
				shadow: false,
				cls: [cls, this.listClass].join(' '),
				constrain:false
			});

				// Track whether the hovering over the help list or not, to aid in detecting iframe clicks.
			this.mon(this.helpList, 'mouseover', function() {this.listHovered = true;}, this);
			this.mon(this.helpList, 'mouseout', function() {this.listHovered = false; }, this);

			this.helpList.swallowEvent('mousewheel');

			this.innerHelpList = this.helpList.createChild({cls:cls+'-inner'});
			this.mon(this.innerHelpList, 'mouseover', this.onViewOver, this);
			this.mon(this.innerHelpList, 'mousemove', this.onViewMove, this);

			if(!this.helpTpl){
				this.helpTpl = '<tpl for="."><div class="'+cls+'-item">{' + this.displayField + '}</div></tpl>';
			 }

			/**
			* The {@link Ext.DataView DataView} used to display the ComboBox's options.
			* @type Ext.DataView
			*/
			this.helpView = new Ext.DataView({
				applyTo: this.innerHelpList,
				tpl: this.helpTpl,
				singleSelect: true,
				selectedClass: this.selectedClass,
				itemSelector: this.itemSelector || '.' + cls + '-item',
				emptyText: this.listEmptyText
			});

			this.helpList.createChild({
				cls: cls + '-content',
				// @todo Can we grab this content via ExtDirect?
				html: '<strong>' + this.helpTitle + '</strong><p>' + TYPO3.LLL.liveSearch.helpDescription + '<br /> ' + TYPO3.LLL.liveSearch.helpDescriptionPages + '</p>'
			});

			this.helpList.alignTo(this.wrap, this.listAlign);
			this.helpList.show();
		}
	},

	removeHelp : function() {
		if (this.helpList) {
			this.helpList.destroy();
			delete this.helpList;
		}
	},

	onFocus : function() {
		TYPO3.BackendLiveSearch.superclass.onFocus.apply(this, arguments);

		// If search is blank, show the help on focus. Otherwise, show last results
		if (this.getValue() == '') {
			this.initHelp();
		} else {
			this.expand();
		}
	},

	/**
	 * Fired when search results are clicked. We do not want the search result
	 * appear so we always set doFocus = false
	 */
	onViewClick : function(doFocus){
		doFocus = false;
		TYPO3.BackendLiveSearch.superclass.onViewClick.apply(this, arguments);
	},

	postBlur : function() {
		TYPO3.BackendLiveSearch.superclass.postBlur.apply(this, arguments);
		this.removeHelp();
	},

	getTriggerWidth : function() {
		// Trigger is inset, so width used in calculations is 0
		return 0;
	},

	reset : function() {
	    this.originalValue = this.emptyText;
		this.setHideTrigger(true);
		TYPO3.BackendLiveSearch.superclass.reset.apply(this, arguments);
	},

	getSearchResultsUrl : function(searchTerm) {
		return 'id=' + this.searchResultsPid + '&search_levels=4&search_field=' + searchTerm;
	},

	handleBlur : function(e) {

		if (!this.listHovered) {
			this.hasFocus = false;
			if (this.getValue() == '') {
				this.reset();
			}
			this.postBlur();
		}

	}
});

var TYPO3LiveSearch;

Ext.onReady(function() {
	TYPO3LiveSearch = new TYPO3.BackendLiveSearch({
		dataProvider: TYPO3.LiveSearchActions.ExtDirect,
		title: TYPO3.LLL.liveSearch.title,
		helpTitle: TYPO3.LLL.liveSearch.helpTitle,
		emptyText: TYPO3.LLL.liveSearch.emptyText,
		loadingText: TYPO3.LLL.liveSearch.loadingText,
		listEmptyText: TYPO3.LLL.liveSearch.listEmptyText
	});

	TYPO3LiveSearch.applyToMarkup(Ext.get('live-search-box'));

		// Add a blur event listener outside the ExtJS widget to handle clicks in iframes also.
	Ext.get('live-search-box').on('blur', TYPO3LiveSearch.handleBlur, TYPO3LiveSearch);
});
