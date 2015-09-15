Ext.ns('TYPO3.Workspaces.Component');

TYPO3.Workspaces.Component.RowExpander = Ext.extend(Ext.grid.RowExpander, {
	menuDisabled: true,
	hideable: false,

	rowDetailTemplate: [
		'<div class="t3-workspaces-foldoutWrapper">',
		'<tpl for=".">',
			'<tpl>',
				'<table class="char_select_template" width="100%">',
					'<tr class="header">',
						'<th class="char_select_profile_titleLeft">',
							'{icon_Workspace} {[TYPO3.l10n.localize(\'workspace_version\')]}',
						'</th>',
						'<th class="char_select_profile_titleRight">',
							'{icon_Live} {[TYPO3.l10n.localize(\'live_workspace\')]}',
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
									'<tpl for="diff">',
										'<tr><th>{label}</th><td class="content">',
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
									'<tpl for="live_record">',
										'<tr><th>{label}</th><td class="content">',
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
		'<div class="x-clear"></div>'
	],

	detailStoreConfiguration: {
		xtype : 'directstore',
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
	},

	detailStore: null,

	init : function(grid) {
		TYPO3.Workspaces.Component.RowExpander.superclass.init.call(this, grid);
		this.detailStore = Ext.create(this.detailStoreConfiguration);

		this.addEvents({
			beforeExpandCollection: true,
			beforeExpandCollectionChild: true,
			beforeCollapseCollection: true,
			beforeCollapseCollectionChild: true
		})
	},

	getRowClass : function(record, rowIndex, p, ds) {
		var cls = [];

		cls.push(Ext.grid.RowExpander.prototype.getRowClass.call(this, record, rowIndex, p, ds));

		if (record.json.Workspaces_CollectionChildren > 0) {
			// @todo Extend by new nodeState check
			cls.push('typo3-workspaces-collection-parent-collapsed');
		}
		if (record.json.Workspaces_CollectionParent) {
			// @todo Extend by new nodeState check
			cls.push('typo3-workspaces-collection-child-collapsed');
		}
		if (!record.json.allowedAction_nextStage && !record.json.allowedAction_prevStage && !record.json.allowedAction_swap) {
			cls.push('typo3-workspaces-row-disabled');
		}

		return cls.join(' ');
	},
	renderer : function(v, p, record) {
		var html;
		html = Ext.grid.RowExpander.prototype.renderer.call(this, v, p, record);
		return html;
	},
	remoteDataMethod : function (record, index) {
		this.detailStore.baseParams = {
			uid: record.json.uid,
			table: record.json.table,
			stage: record.json.stage,
			t3ver_oid: record.json.t3ver_oid,
			path_Live: record.json.path_Live,
			label_Stage: record.json.label_Stage
		};
		this.detailStore.load({
			callback: function(r, options, success) {
				TYPO3.Workspaces.RowExpander.expandRow(index);
			}
		});
		new Ext.ux.TYPO3.Workspace.RowPanel({
			renderTo: 'remData' + index,
			items: [{
				xtype: 'dataview',
				store: this.detailStore,
				tpl: new TYPO3.Workspaces.Component.RowDetailTemplate(this.rowDetailTemplate)
			}]
		});
	},
	onMouseDown : function(e, t) {
		tObject = Ext.get(t);
		if (tObject.hasClass('x-grid3-row-expander')) {
			e.stopEvent();
			row = e.getTarget('.x-grid3-row');
			this.toggleRow(row);
		} else if (tObject.hasClass('typo3-workspaces-collection-level-node')) {
			e.stopEvent();
			row = e.getTarget('.x-grid3-row');
			this.toggleCollection(row);
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
	},

	toggleCollection : function(row) {
		if (Ext.fly(row).hasClass('typo3-workspaces-collection-parent-collapsed')) {
			this.expandCollection(row);
		} else {
			this.collapseCollection(row);
		}
	},
	expandCollection : function(row) {
		var record, body, child, i;

		if (typeof row === 'number') {
			row = this.grid.view.getRow(row);
		}

		record = this.grid.store.getAt(row.rowIndex);
		body = Ext.DomQuery.selectNode('tr:nth(2) div.x-grid3-row-body', row);
		if (this.fireEvent('beforeExpandCollection', this, record, body, row.rowIndex) !== false) {
			for(i = 0; i < this.grid.store.getCount(); i++) {
				child = this.grid.store.getAt(i);
				if (child.json.Workspaces_CollectionParent === record.json.Workspaces_CollectionCurrent) {
					this.expandCollectionChild(i);
				}
			}
			Ext.fly(row).replaceClass('typo3-workspaces-collection-parent-collapsed', 'typo3-workspaces-collection-parent-expanded');
		}
	},
	expandCollectionChild : function(row) {
		var record, body;

		if (typeof row === 'number') {
			row = this.grid.view.getRow(row);
		}

		record = this.grid.store.getAt(row.rowIndex);
		body = Ext.DomQuery.selectNode('tr:nth(2) div.x-grid3-row-body', row);
		if (this.fireEvent('beforeCollapseCollectionChild', this, record, body, row.rowIndex) !== false) {
			Ext.fly(row).replaceClass('typo3-workspaces-collection-child-collapsed', 'typo3-workspaces-collection-child-expanded');
		}
	},
	collapseCollection : function(row) {
		var record, body, child, i;

		if (typeof row === 'number') {
			row = this.grid.view.getRow(row);
		}

		record = this.grid.store.getAt(row.rowIndex);
		body = Ext.fly(row).child('tr:nth(1) div.x-grid3-row-body', true);
		if (this.fireEvent('beforeCollapseCollectionChild', this, record, body, row.rowIndex) !== false) {
			for(i = 0; i < this.grid.store.getCount(); i++) {
				child = this.grid.store.getAt(i);
				if (child.json.Workspaces_CollectionParent === record.json.Workspaces_CollectionCurrent) {
					// Delegate collapsing to child if it has children as well
					if (child.json.Workspaces_CollectionChildren > 0) {
						this.collapseCollection(i);
					}
					this.collapseCollectionChild(i);
				}
			}
			Ext.fly(row).replaceClass('typo3-workspaces-collection-parent-expanded', 'typo3-workspaces-collection-parent-collapsed');
		}
	},
	collapseCollectionChild : function(row) {
		var record, body;

		if (typeof row === 'number') {
			row = this.grid.view.getRow(row);
		}

		record = this.grid.store.getAt(row.rowIndex);
		body = Ext.fly(row).child('tr:nth(1) div.x-grid3-row-body', true);
		if (this.fireEvent('beforeCollapseCollection', this, record, body, row.rowIndex) !== false) {
			Ext.fly(row).replaceClass('typo3-workspaces-collection-child-expanded', 'typo3-workspaces-collection-child-collapsed');
		}
	}
});
