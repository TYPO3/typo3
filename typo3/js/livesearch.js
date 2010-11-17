/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2010 Michael Klapper <michael.klapper@aoemedia.de>
 *  (c) 2010 Jeff Segars <jeff@webempoweredchurch.org>
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

Ext.namespace('TYPO3');

TYPO3.BackendLiveSearch = Ext.extend(Ext.form.ComboBox, {
	autoSelect: false,
	ctCls: 'live-search-results',
	dataProvider: null,
	dbListUrl : 'id=0&search_levels=4&search_field=',
	displayField: 'title',
	emptyText: null,
	enableKeyEvents: true,
	helpTitle: null,
	itemSelector: 'div.search-item-title',
	listAlign : 'tr-br',
	listClass: 'live-search-list',
	listEmptyText: null,
	listWidth: 315,
	loadingText: null,
	minChars: 2,
	resizable: false,
	title: null,
	width: 205,

	triggerClass : 'x-form-clear-trigger',
	triggerConfig: '<span tag="a" class="t3-icon t3-icon-actions t3-icon-actions-input t3-icon-input-clear t3-tceforms-input-clearer">&nbsp;</span>',
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
					'<td class="search-item-type" width="105" align="right">{recordTitle}</td>',
					'<td class="search-item-content" width="195">',
						'<div class="search-item-title">{iconHTML} {title}</span>',
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
				jump(record.data.editLink, 'web_list', 'web');
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
					TYPO3.ModuleMenu.App.showModule('web_list', this.dbListUrl + this.getValue());
				}
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
		var inner = this.innerList.dom;
		var pad = this.list.getFrameWidth('tb')+(this.resizable?this.handleHeight:0)+this.assetHeight + 30; // @todo Remove hardcoded 30
		var h = Math.max(inner.clientHeight, inner.offsetHeight, inner.scrollHeight);
		var ha = this.getPosition()[1]-Ext.getBody().getScroll().top;
		var hb = Ext.lib.Dom.getViewHeight()-ha-this.getSize().height;
		var space = Math.max(ha, hb, this.minHeight || 0)-pad-2;
		/** BUG FIX **/
		if (this.shadow === true) { space-=this.list.shadow.offset; }

		h = Math.min(h, space, this.maxHeight);

		/**
		 * @internal The calcated height of "h" in the line before seems not working as expected.
		 *			 If i define a min height, the box shold at least use this height also if only one entry is in there
		 */
		//h = this.maxHeight;

		this.innerList.setHeight(h);
		this.list.beginUpdate();
		this.list.setHeight(h+pad);
		this.list.alignTo(this.el, this.listAlign);
		this.list.endUpdate();
	},

	initList : function () {
		TYPO3.BackendLiveSearch.superclass.initList.apply(this, arguments);

		var cls = 'x-combo-list';

		/**
		 * Create bottom Toolbar to the result layer
		 */
		this.footer = this.list.createChild({cls:cls+'-ft'});

		this.pageTb = new Ext.Toolbar({
			renderTo:this.footer,
			height: 30,
			items: [{
				xtype: 'tbfill',
				autoWidth : true
			},{
				xtype: 'button',
				text: TYPO3.LLL.liveSearch.showAllResults,
				arrowAlign : 'right',
				shadow: false,
				icon : '../typo3/sysext/t3skin/icons/module_web_list.gif',
				listeners : {
					scope : this,
					click : function () {
							// go to db_list.php and search for given search value
							// @todo the current selected page ID from the page tree is required, also we need the
							// values of $BE_USER->returnWebmounts() to search only during the allowed pages
						TYPO3.ModuleMenu.App.showModule('web_list', this.dbListUrl + this.getValue());
						this.collapse();
					}
				}
			}]
		});
		this.assetHeight += this.footer.getHeight();
	},

	// private
	onLoad : function(){
		TYPO3.BackendLiveSearch.superclass.onLoad.apply(this, arguments);

		// If an pageJump request is done this will immediately load the record for editing.
		// if (this.dataReader.jsonData.pageJump != '') {
		//	this.collapse();
		//	jump(this.dataReader.jsonData.pageJump, 'web_list', 'web');
		// } else {
			// Add an event handler to each iframe, closing the search window when there's a click inside the iframe
			// @todo Is there a cleaner way to handle this?
			var iframes = Ext.query('iframe');
			Ext.each(iframes, function(item, index, allItems) {
				item.contentWindow.document.body.onclick = function() {
					if (parent.TYPO3LiveSearch && parent.TYPO3LiveSearch.isExpanded()) {
						parent.TYPO3LiveSearch.collapse();
					}
				};
			}, this);
		//}
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
				shadow: this.shadow,
				cls: [cls, this.listClass].join(' '),
				constrain:false
			});

			var lw = this.listWidth || Math.max(this.wrap.getWidth(), this.minListWidth);
			this.helpList.setSize(lw);
			this.helpList.swallowEvent('mousewheel');
			if(this.syncFont !== false){
				this.helpList.setStyle('font-size', this.el.getStyle('font-size'));
			}

			this.innerHelpList = this.helpList.createChild({cls:cls+'-inner'});
			this.mon(this.innerHelpList, 'mouseover', this.onViewOver, this);
			this.mon(this.innerHelpList, 'mousemove', this.onViewMove, this);
			this.innerHelpList.setWidth(lw - this.helpList.getFrameWidth('lr'));

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

			var iframes = Ext.query('iframe');
			Ext.each(iframes, function(item, index, allItems) {
				item.contentWindow.document.body.onclick = function() {
					if (parent.TYPO3LiveSearch && parent.TYPO3LiveSearch.helpList.isVisible()) {
						parent.TYPO3LiveSearch.helpList.remove();
					}
				};
			}, this);

		}
	},

	removeHelp : function() {
		if (this.helpList) {
			this.helpList.destroy();
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
		TYPO3.BackendLiveSearch.superclass.reset.apply(this, arguments);
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
});
