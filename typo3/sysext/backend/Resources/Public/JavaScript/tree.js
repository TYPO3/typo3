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

Ext.ns('TYPO3.Components', 'TYPO3.Components.Tree');

/**
 * TYPO3window - General TYPO3 tree component
 */

TYPO3.Components.Tree = {};
TYPO3.Components.Tree.StandardTreeItemData = [];

TYPO3.Components.Tree.StandardTree = function(config) {
	var conf = Ext.apply({
		header: false,
		width: 280,
		rootVisible: false,
		useArrows: false,
		lines: true,
		autoScroll: true,
		containerScroll: true,
		exclusiveSelectedKey: null,
		stateful: true,
		filterOptionStartsWith: true,
		countSelectedNodes: 0,
		loader: new Ext.tree.TreeLoader({
			preloadChildren: true,
			clearOnLoad: false
		}),
		root: new Ext.tree.AsyncTreeNode({
			text: TYPO3.l10n.localize('tcatree'),
			id: 'root',
			expanded: true,
			children: TYPO3.Components.Tree.StandardTreeItemData[config.id]
		}),
		collapseFirst: false,
		listeners: {
			'checkchange': function(checkedNode, checked) {
				if (Ext.isFunction(this.checkChangeHandler)) {
					this.checkChangeHandler.call(this, checkedNode, checked);
				}
			},
			scope: this
		}
	}, config);
	TYPO3.Components.Tree.StandardTree.superclass.constructor.call(this, conf);
};


Ext.extend(TYPO3.Components.Tree.StandardTree, Ext.tree.TreePanel, {

	initComponent: function() {
		if (!this.uiProvider) {
			this.uiProvider = TYPO3.Components.Tree.TreeNodeUI;
		}
		this.loader = new Ext.tree.TreeLoader({
			baseAttrs: {
				uiProvider: this.uiProvider
			}
		});

		// prepare hidden input field (make sure only comma separated uids are present)
		var selected = Ext.fly('treeinput' + this.id).dom.value.split(',');
		for (var i = 0; i < selected.length; i++) {
			var value = selected[i].split('|');
			selected[i] = value[0];
		}
		Ext.fly('treeinput' + this.id).dom.value = selected.join(',');

		Ext.apply(this, {
			tbar: this.initialConfig.showHeader ? TYPO3.Components.Tree.Toolbar([], this) : null
		});
		TYPO3.Components.Tree.StandardTree.superclass.initComponent.call(this);
	},
	filterTree: function(filterText) {
		var text = filterText.getValue();
		Ext.each(this.hiddenNodes, function(node) {
			node.ui.show();
			node.ui.removeClass('bgColor6');
		});
		if (!text) {
			this.filter.clear();
			return;
		}
		this.expandAll();
		var regText = (this.filterOptionStartsWith ? '^' : '') + Ext.escapeRe(text);
		var re = new RegExp(regText, 'i');

			// hide empty nodes that weren't filtered
		this.hiddenNodes = [];
		var me = this;
		this.root.cascade(function(node) {
			if (node.ui.ctNode.offsetHeight < 3) {
				if (!re.test(node.text)) {
					node.ui.hide();
					me.hiddenNodes.push(node);
				} else {
					node.ui.addClass('bgColor6');
				}
			}
		}, this);
	}
});

TYPO3.Components.Tree.Toolbar = function(items, scope) {
	items = items || [];
	items.push([
		' ',
		{
			iconCls: 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-tree-search-open',
			menu: {
				items: [
					{
						text: TYPO3.l10n.localize('tcatree.filter.startsWith'),
						checked: true,
						group: 'searchStartsWith',
						handler: function(item) {
							scope.filterOptionStartsWith = true;
							scope.filterTree(scope.getTopToolbar().getComponent('filterText'));
						},
						scope: scope
					},
					{
						text: TYPO3.l10n.localize('tcatree.filter.contains'),
						checked: false,
						group: 'searchStartsWith',
						handler: function(item) {
							scope.filterOptionStartsWith = false;
							scope.filterTree(scope.getTopToolbar().getComponent('filterText'));
						},
						scope: scope
					}
				]
			}
		},
		new Ext.form.TextField({
			width: 150,
			emptyText: TYPO3.l10n.localize('tcatree.findItem'),
			enableKeyEvents: true,
			itemId: 'filterText',
			listeners:{
				render: function(f) {
					this.filter = new Ext.tree.TreeFilter(this, {
						clearBlank: true,
						autoClear: true
					});
				},
				keydown: {
					fn: scope.filterTree,
					buffer: 350,
					scope: scope
				},
				scope: scope
			}
		}),
		'->',
		{
			iconCls: 't3-icon t3-icon-apps t3-icon-apps-tcatree t3-icon-tcatree-select-recursive',
			tooltip: TYPO3.lang['tcatree.enableRecursiveSelection'],
			enableToggle: true,
			disable: scope.tcaSelectRecursive,
			toggleHandler: function(btn, state) {
				this.tcaSelectRecursive = state;
			},
			scope: scope
		},
		{
			iconCls: 'icon-expand-all',
			tooltip: TYPO3.l10n.localize('tcatree.expandAll'),
			handler: function() {
					this.root.expand(true);
			},
			scope: scope
		}, {
			iconCls: 'icon-collapse-all',
			tooltip: TYPO3.l10n.localize('tcatree.collapseAll'),
			handler: function() {
				this.root.collapse(true);
			},
			scope: scope
		}
	]);
	return items;
};

TYPO3.Components.Tree.EmptySelectionModel = new Ext.tree.DefaultSelectionModel({
	select: Ext.emptyFn
});

TYPO3.Components.Tree.TcaCheckChangeHandler = function(checkedNode, checked) {
	var exclusiveKeys = this.tcaExclusiveKeys.split(','),
		uid = '' + checkedNode.attributes.uid,
		selected = Ext.fly('treeinput' + this.id).dom.value.length ? Ext.fly('treeinput' + this.id).dom.value.split(',') : [];

	this.suspendEvents();

	if (this.tcaExclusiveKeys.length) {
		if (checked === true && exclusiveKeys.indexOf(uid) > -1) {
				// this key is exclusive, so uncheck all others
			this.root.cascade(function(node) {
				if (node !== checkedNode && node.attributes.checked) {
					node.attributes.checked = false;
					node.ui.toggleCheck(false);
				}
			});
			this.exclusiveSelectedKey = uid;
		} else if (checked === true && exclusiveKeys.indexOf(uid) === -1 && !Ext.isEmpty(this.exclusiveSelectedKey)) {
				// this key is exclusive, so uncheck all others
			this.root.cascade(function(node) {
				if (exclusiveKeys.indexOf('' + node.attributes.uid) > -1) {
					node.attributes.checked = false;
					node.ui.toggleCheck(false);
				}
			});
			this.exclusiveSelectedKey = null;
		}
	}

	if (checked === true && this.countSelectedNodes >= this.tcaMaxItems) {
		checkedNode.attributes.checked = false;
		checkedNode.getUI().toggleCheck(false);
		this.resumeEvents();
		return false;
	}
	if (checked) {
		checkedNode.getUI().addClass('complete');
	} else {
		checkedNode.getUI().removeClass('complete');
	}
		// if recursive selection is asked, hand over selection
	if(this.tcaSelectRecursive) {
		checkedNode.cascade(function(node) {
			node.attributes.checked = checkedNode.attributes.checked;
			node.ui.toggleCheck(checkedNode.attributes.checked);
		})
	}

	this.root.cascade(function(node) {
		if (node.ui.isChecked()) {
			if (selected.indexOf(node.attributes.uid) < 0) {
				selected.push(node.attributes.uid)
			}
		} else {
			var index = selected.indexOf(node.attributes.uid);
			if (index >= 0) {
				selected.splice(index, 1);
			}
		}
	});

	this.countSelectedNodes = selected.length;
	Ext.fly('treeinput' + this.id).dom.value = selected.join(',');
	eval(this.onChange);

	this.resumeEvents();
};

TYPO3.Components.Tree.TreeNodeUI = Ext.extend(Ext.tree.TreeNodeUI, {
	renderElements : function(n, a, targetNode, bulkRender){
		this.indentMarkup = n.parentNode ? n.parentNode.ui.getChildIndent() : '';

		var cb = Ext.isBoolean(a.checked),
			nel,
			href = this.getHref(a.href),
			buf = ['<li class="x-tree-node"><div ext:tree-node-id="',n.id,'" class="x-tree-node-el x-tree-node-leaf x-unselectable ', a.cls,'" unselectable="on">',
				'<span class="x-tree-node-indent">',this.indentMarkup,"</span>",
				'<img alt="" src="', this.emptyIcon, '" class="x-tree-ec-icon x-tree-elbow" />',
				a.iconTag || ('<img alt="" src="' + (a.icon || this.emptyIcon) + '" class="x-tree-node-icon' + (a.icon ? " x-tree-node-inline-icon" : "") + (a.iconCls ? " "+a.iconCls : "") + '" unselectable="on" />'),
				cb ? ('<input class="x-tree-node-cb" type="checkbox" ' + (a.checked ? 'checked="checked" />' : '/>')) : '',
				'<a hidefocus="on" class="x-tree-node-anchor" href="',href,'" tabIndex="1" ',
				a.hrefTarget ? ' target="'+a.hrefTarget+'"' : "", '><span unselectable="on">',n.text,"</span></a></div>",
				'<ul class="x-tree-node-ct" style="display:none;"></ul>',
				"</li>"].join('');

		if(bulkRender !== true && n.nextSibling && (nel = n.nextSibling.ui.getEl())){
			this.wrap = Ext.DomHelper.insertHtml("beforeBegin", nel, buf);
		}else{
			this.wrap = Ext.DomHelper.insertHtml("beforeEnd", targetNode, buf);
		}

		this.elNode = this.wrap.childNodes[0];
		this.ctNode = this.wrap.childNodes[1];
		var cs = this.elNode.childNodes;
		this.indentNode = cs[0];
		this.ecNode = cs[1];
		this.iconNode = cs[2];
		var index = 3;
		if(cb){
			this.checkbox = cs[3];

			this.checkbox.defaultChecked = this.checkbox.checked;
			index++;
		}
		this.anchor = cs[index];
		this.textNode = cs[index].firstChild;
	}
});
