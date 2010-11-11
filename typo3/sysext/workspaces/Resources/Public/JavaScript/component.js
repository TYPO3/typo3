/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Workspace Team <worksapceteam@typo3.org>
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


Ext.ns('TYPO3.Workspaces');

TYPO3.Workspaces.Component = {};

TYPO3.Workspaces.RowDetail = {};
TYPO3.Workspaces.RowDetail.rowDataStore = new Ext.data.DirectStore({
	storeId : 'rowDetailService',
	root : 'data',
	totalProperty : 'total',
	idProperty : 'uid',
	fields : [
		{name : 'uid'},
		{name : 't3ver_oid'},
		{name : 'table'},
		{name : 'stage'},
		{name : 'diff'},
		{name : 'path_Live'},
		{name : 'label_Stage'},
		{name : 'stage_position'},
		{name : 'stage_count'},
		{name : 'live_record'},
		{name : 'comments'},
		{name : 'icon_Live'},
		{name : 'icon_Workspace'}
	]
});
TYPO3.Workspaces.RowDetail.rowDetailTemplate = new Ext.XTemplate(
	'<div class="t3-workspaces-foldoutWrapper">',
	'<tpl for=".">',
		'<tpl>',
			'<table class="char_select_template" width="100%">',
				'<tr class="header">',
					'<th class="char_select_profile_title">',
						'Workspace Version',
					'</th>',
					'<th class="char_select_profile_title">',
						'Live Workspace',
					'</th>',
				'</tr>',
				'<tr>',
					'<td class="t3-workspaces-foldout-subheader">',
						'<b>Current stage step:</b> {label_Stage} (<b>{stage_position}</b>/{stage_count})',
					'</td>',
					'<td class="t3-workspaces-foldout-subheader">',
						'<b>Path:</b> {path_Live}',
					'</td>',
				'</tr>',
				'<tr>',
					'<td class="t3-workspaces-foldout-td-contentDiff">',
						'<table class="t3-workspaces-foldout-contentDiff">',
							'<span class="{icon_Workspace}">&nbsp;</span>',
							'<tpl for="diff">',
								'<tr><th>{label}</th><td>{content}</td></tr>',
							'</tpl>',
						'</table>',
					'</td>',
					'<td class="t3-workspaces-foldout-td-contentDiff">',
						'<table class="t3-workspaces-foldout-contentDiff">',
							'<span class="{icon_Live}"></span>',
							'<tpl for="live_record">',
								'<tr><th>{label}</th><td>{content}</td></tr>',
							'</tpl>',
						'</table>',
					'</td>',
				'</tr>',
				'<tr>',
					'<td class="t3-workspaces-foldout-subheader">',
						'User comments for <b>step {stage_position} of stage</b> "{label_Stage}"',
					'</td>',
					'<td class="t3-workspaces-foldout-subheader">',
						'&nbsp;',
					'</td>',
				'</tr>',
				'<tr>',
					'<td class="char_select_profile_stats">',
						'<div class="t3-workspaces-comments">',
						'<tpl for="comments">',
							'<div class="t3-workspaces-comments-singleComment">',
								'<div class="t3-workspaces-comments-singleComment-author">',
									'{user_username}',
								'</div>',
								'<div class="t3-workspaces-comments-singleComment-content">',
									'<span class="t3-workspaces-comments-singleComment-content-date">{tstamp}</span> @ Stage {stage_title}',
									'<div class="t3-workspaces-comments-singleComment-content-text">{user_comment}</div>',
								'</div>',
							'</div>',
						'</tpl>',
						'</div>',
					'</td>',
					'<td class="char_select_profile_title">',
						'&nbsp;',
					'</td>',
				'</tr>',
			'</table>',
		'</tpl>',
	'</tpl>',
	'</div>',
	'<div class="x-clear"></div>'
);

TYPO3.Workspaces.RowDetail.rowDataView = new Ext.DataView({
	store: TYPO3.Workspaces.RowDetail.rowDataStore,
	tpl: TYPO3.Workspaces.RowDetail.rowDetailTemplate
});

Ext.ns('Ext.ux.TYPO3.Workspace');
Ext.ux.TYPO3.Workspace.RowPanel = Ext.extend(Ext.Panel, {
	constructor: function(config) {
		config = config || {
			frame:true,
			width:'100%',
			autoHeight:true,
			layout:'fit',
			title: TYPO3.lang.rowDetails
		};
		Ext.apply(this, config);
		Ext.ux.TYPO3.Workspace.RowPanel.superclass.constructor.call(this, config);
	}
});

TYPO3.Workspaces.RowExpander = new Ext.grid.RowExpander({
	menuDisabled: true,
	hideable: false,
	remoteDataMethod : function (record, index) {
		TYPO3.Workspaces.RowDetail.rowDataStore.baseParams = {
			uid: record.json.uid,
			table: record.json.table,
			stage: record.json.stage,
			t3ver_oid: record.json.t3ver_oid,
			path_Live: record.json.path_Live,
			label_Stage: record.json.label_Stage
		};
		TYPO3.Workspaces.RowDetail.rowDataStore.load({
			callback: function(r, options, success) {
				TYPO3.Workspaces.RowExpander.expandRow(index);
			}
		});
		new Ext.ux.TYPO3.Workspace.RowPanel({
			renderTo: 'remData' + index,
			items: TYPO3.Workspaces.RowDetail.rowDataView
		});
	},
	onMouseDown : function(e, t) {
		tObject = Ext.get(t);
		if (tObject.hasClass('x-grid3-row-expander')) {
			e.stopEvent();
			var row = e.getTarget('.x-grid3-row');
			this.toggleRow(row);
		}
	},
	toggleRow : function(row) {
		this[Ext.fly(row).hasClass('x-grid3-row-collapsed') ? 'beforeExpand' : 'collapseRow'](row);
	},
	beforeExpand : function(row) {
		if (typeof row == 'number') {
			row = this.grid.view.getRow(row);
		}
		var record = this.grid.store.getAt(row.rowIndex);
		var body = Ext.DomQuery.selectNode('tr:nth(2) div.x-grid3-row-body', row);

		if (this.fireEvent('beforexpand', this, record, body, row.rowIndex) !== false) {
			this.tpl = new Ext.Template("<div id=\"remData" + row.rowIndex + "\" class=\"rem-data-expand\"><\div>");
			if (this.tpl && this.lazyRender) {
				body.innerHTML = this.getBodyContent(record, row.rowIndex);
			}
		}
			// toggle remoteData loading
		this.remoteDataMethod(record, row.rowIndex);
		return true;
	},
	expandRow : function(row) {
		if (typeof row == 'number') {
			row = this.grid.view.getRow(row);
		}
		var record = this.grid.store.getAt(row.rowIndex);
		var body = Ext.DomQuery.selectNode('tr:nth(2) div.x-grid3-row-body', row);
		this.state[record.id] = true;
		Ext.fly(row).replaceClass('x-grid3-row-collapsed', 'x-grid3-row-expanded');
		this.fireEvent('expand', this, record, body, row.rowIndex);
		var i;
		for(i = 0; i < this.grid.store.getCount(); i++) {
			if(i != row.rowIndex) {
				this.collapseRow(i);
			}
		}
	},
	collapseRow : function(row) {
		if (typeof row == 'number') {
			row = this.grid.view.getRow(row);
		}
		var record = this.grid.store.getAt(row.rowIndex);
		var body = Ext.fly(row).child('tr:nth(1) div.x-grid3-row-body', true);
		if (this.fireEvent('beforcollapse', this, record, body, row.rowIndex) !== false) {
			this.state[record.id] = false;
			Ext.fly(row).replaceClass('x-grid3-row-expanded', 'x-grid3-row-collapsed');
			this.fireEvent('collapse', this, record, body, row.rowIndex);
		}
	}
});


TYPO3.Workspaces.MainStore = new Ext.data.GroupingStore({
	storeId : 'workspacesMainStore',
	reader : new Ext.data.JsonReader({
		idProperty : 'uid',
		root : 'data',
		totalProperty : 'total'
	}, TYPO3.Workspaces.Configuration.StoreFieldArray),
	groupField: 'path_Workspace',
	remoteGroup: false,
	paramsAsHash : true,
	sortInfo : {
		field : 'label_Live',
		direction : "ASC"
	},
	remoteSort : true,
	baseParams: {
		depth : 990,
		id: TYPO3.settings.Workspaces.id,
		query: '',
		start: 0,
		limit: 10
	},

	showAction : false,
	listeners : {
		beforeload : function() {
		},
		load : function(store, records) {
		},
		datachanged : function(store) {
		},
		scope : this
	}
});
