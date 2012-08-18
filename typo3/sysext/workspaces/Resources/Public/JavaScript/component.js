/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
	idProperty : 'id',
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
		{name : 'icon_Workspace'},
		{name : 'languageValue'},
		{name : 'integrity'}
	]
});

Ext.override(Ext.XTemplate, {
	exists: function(o, name) {
		return typeof o != 'undefined' && o != null && o!='';
	}
});

Ext.override(Ext.grid.GroupingView, {
	constructId : function(value, field, idx) {
		var cfg = this.cm.config[idx],
			groupRenderer = cfg.groupRenderer || cfg.renderer,
			val = (this.groupMode == 'value') ? value : this.getGroup(value, {data:{}}, groupRenderer, 0, idx, this.ds);

		var id = this.getPrefix(field) + val;
		id = id.replace(/[^a-zA-Z0-9_]/g, '');
		return id;
	}
});


TYPO3.Workspaces.RowDetail.rowDetailTemplate = new Ext.XTemplate(
	'<div class="t3-workspaces-foldoutWrapper">',
	'<tpl for=".">',
		'<tpl>',
			'<table class="char_select_template" width="100%">',
				'<tr class="header">',
					'<th class="char_select_profile_titleLeft">',
						'{[TYPO3.l10n.localize(\'workspace_version\')]}',
					'</th>',
					'<th class="char_select_profile_titleRight">',
						'{[TYPO3.l10n.localize(\'live_workspace\')]}',
					'</th>',
				'</tr>',
				'<tr>',
					'<td class="t3-workspaces-foldout-subheaderLeft">',
						'{[String.format(TYPO3.l10n.localize(\'current_step\'), values.label_Stage, values.stage_position, values.stage_count)]}',
					'</td>',
					'<td class="t3-workspaces-foldout-subheaderRight">',
						'{[String.format(TYPO3.l10n.localize(\'path\'), values.path_Live)]}',
					'</td>',
				'</tr>',
				'<tr>',
					'<td class="t3-workspaces-foldout-td-contentDiffLeft">',
						'<div class="t3-workspaces-foldout-contentDiff-container">',
							'<table class="t3-workspaces-foldout-contentDiff">',
								'<tr><th><span class="{icon_Workspace}">&nbsp;</span></th><td>{type_Workspace}</td></tr>',
								'<tpl for="diff">',
									'<tr><th>{label}</th><td>',
										'<tpl if="this.exists(content)">',
											'{content}',
										'</tpl>',
									'</td></tr>',
								'</tpl>',
							'</table>',
						'</div>',
					'</td>',
					'<td class="t3-workspaces-foldout-td-contentDiffRight">',
						'<div class="t3-workspaces-foldout-contentDiff-container">',
							'<table class="t3-workspaces-foldout-contentDiff">',
								'<tr><th><span class="{icon_Live}"></span></th><td>{type_Live}</td></tr>',
								'<tpl for="live_record">',
									'<tr><th>{label}</th><td>',
										'<tpl if="this.exists(content)">',
											'{content}',
										'</tpl>',
									'</td></tr>',
								'</tpl>',
							'</table>',
						'</div>',
					'</td>',
				'</tr>',
				'<tpl if="this.hasComments(comments)">',
				'<tr>',
					'<td class="t3-workspaces-foldout-subheaderLeft">',
						'<div class="t3-workspaces-foldout-subheader-container">',
							'{[String.format(TYPO3.l10n.localize(\'comments\'), values.stage_position, values.label_Stage)]}',
						'</div>',
					'</td>',
					'<td class="t3-workspaces-foldout-subheaderRight">',
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
								'<div class="t3-workspaces-comments-singleComment-content-wrapper"><div class="t3-workspaces-comments-singleComment-content">',
									'<span class="t3-workspaces-comments-singleComment-content-date">{tstamp}</span>',
									'<div class="t3-workspaces-comments-singleComment-content-title">@ {[String.format(TYPO3.l10n.localize(\'stage\'), values.stage_title)]}</div>',
									'<div class="t3-workspaces-comments-singleComment-content-text">{user_comment}</div>',
								'</div></div>',
							'</div>',
						'</tpl>',
						'</div>',
					'</td>',
					'<td class="char_select_profile_title">',
						'&nbsp;',
					'</td>',
					'</tpl>',
				'</tr>',
			'</table>',
		'</tpl>',
	'</tpl>',
	'</div>',
	'<div class="x-clear"></div>',
	{
		hasComments: function(comments){
			return comments.length>0;
		}
	}
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
			title: TYPO3.l10n.localize('rowDetails')
		};
		Ext.apply(this, config);
		Ext.ux.TYPO3.Workspace.RowPanel.superclass.constructor.call(this, config);
	}
});

TYPO3.Workspaces.RowExpander = new Ext.grid.RowExpander({
	menuDisabled: true,
	hideable: false,
	getRowClass : function(record, rowIndex, p, ds) {
		cssClass = '';
		if (!record.json.allowedAction_nextStage && !record.json.allowedAction_prevStage && !record.json.allowedAction_swap) {
			cssClass = 'typo3-workspaces-row-disabled ';
		}
		if(this.state[record.id]) {
			cssClass += 'x-grid3-row-expanded';
		} else {
			cssClass += 'x-grid3-row-collapsed';
		}
		return cssClass;
	},
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