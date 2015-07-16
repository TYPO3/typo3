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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.DeletionDropZone
 *
 * Tree Node User Interface that can handle sprite icons and more
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.tree.TreeNodeUI
 */
TYPO3.Components.PageTree.PageTreeNodeUI = Ext.extend(Ext.tree.TreeNodeUI, {
	/**
	 * Adds the sprite icon and adds an event to open the context menu on a single click at the icon node
	 *
	 * @param {Ext.tree.TreeNode} n
	 * @param {Object} a
	 * @param {Ext.tree.TreeNode} targetNode
	 * @param {Boolean} bulkRender
	 * @return {void}
	 */
	renderElements : function(n, a, targetNode, bulkRender) {
		// add some indent caching, this helps performance when rendering a large tree
		this.indentMarkup = n.parentNode ? n.parentNode.ui.getChildIndent() : '';

		var cb = Ext.isBoolean(a.checked),
			nel,
			href = this.getHref(a.href),
			nodeStyles = '',
			rootline = '';

			// TYPO3 modification to show the readable rootline above the user mounts
		if (a.readableRootline !== '') {
			rootline = '<li class="x-tree-node-readableRootline">' + a.readableRootline + '</li>';
		}

		if (a.nodeData.backgroundColor) {
			nodeStyles = 'style="background-color: ' + a.nodeData.backgroundColor + '"';
		}

		var buf = [
			rootline,
			'<li class="x-tree-node" ' + nodeStyles + '><div ext:tree-node-id="', n.id, '" class="x-tree-node-el x-tree-node-leaf x-unselectable ', a.cls, '" unselectable="on">',
			'<span class="x-tree-node-indent">', this.indentMarkup, "</span>",
			'<img alt="" src="', this.emptyIcon, '" class="x-tree-ec-icon x-tree-elbow" />',
//            '<img alt="" src="', a.icon || this.emptyIcon, '" class="x-tree-node-icon',(a.icon ? " x-tree-node-inline-icon" : ""),(a.iconCls ? " "+a.iconCls : ""),'" unselectable="on" />',
			a.spriteIconCode, // TYPO3: add sprite icon code
			(a.nodeData.stopPageTree ? '<span class="text-danger">+</span>' : ''),
			cb ? ('<input class="x-tree-node-cb" type="checkbox" ' + (a.checked ? 'checked="checked" />' : '/>')) : '',
			'<a hidefocus="on" class="x-tree-node-anchor" href="',href,'" tabIndex="1" ',
			 a.hrefTarget ? ' target="'+a.hrefTarget+'"' : "", '><span unselectable="on">',n.text,"</span></a></div>",
			'<ul class="x-tree-node-ct" style="display:none;"></ul>',
			"</li>"
		].join('');

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
//        this.iconNode = cs[2];
		this.iconNode = (cs[2].firstChild.tagName === 'SPAN' ? cs[2].firstChild : cs[2]); // TYPO3: get possible overlay icon
		var index = 3; // TYPO3: index 4?
		if(cb){
			this.checkbox = cs[3];
			// fix for IE6
			this.checkbox.defaultChecked = this.checkbox.checked;
			index++;
		}
		this.anchor = cs[index];
		this.textNode = cs[index].firstChild;

			// TYPO3: call the context menu on a single click (Beware of drag&drop!)
		if (!TYPO3.Components.PageTree.Configuration.disableIconLinkToContextmenu
			|| TYPO3.Components.PageTree.Configuration.disableIconLinkToContextmenu === '0'
		) {
			Ext.fly(this.iconNode).on('click', function(event) {
				this.getOwnerTree().fireEvent('contextmenu', this, event);
				event.stopEvent();
			}, n);
		}
	},

	/**
	 * Adds a quick tip to the sprite icon
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {Object} tip
	 * @param {String} title
	 * @return {void}
	 */
	onTipChange : function(node, tip, title) {
		TYPO3.Components.PageTree.PageTreeNodeUI.superclass.onTipChange.apply(this, arguments);

		if (this.rendered) {
			var hasTitle = Ext.isDefined(title);
			if (this.iconNode.setAttributeNS) {
				this.iconNode.setAttributeNS('ext', 'data-toggle', 'tooltip');
				this.iconNode.setAttributeNS('ext', 'data-title', tip);
				this.iconNode.setAttributeNS('ext', 'data-html', 'true');
				this.iconNode.setAttributeNS('ext', 'data-placement', 'right');
				if (hasTitle) {
					this.iconNode.setAttributeNS("ext", "qtitle", title);
				}
			} else {
				this.iconNode.setAttribute("ext:qtip", tip);
				this.iconNode.setAttribute('ext:data-toggle', 'tooltip');
				this.iconNode.setAttribute('ext:data-title', tip);
				this.iconNode.setAttribute('ext:data-html', 'true');
				this.iconNode.setAttribute('ext:data-placement', 'right');
				if (hasTitle) {
					this.iconNode.setAttribute("ext:qtitle", title);
				}
			}
			TYPO3.jQuery(this.iconNode).tooltip();
		}
	},

	/**
	 * Returns the drag and drop handles
	 *
	 * @return {Object}
	 */
	getDDHandles: function() {
		var ddHandles = [this.iconNode, this.textNode, this.elNode];
		var handlesIndex = ddHandles.length;

		var textNode = Ext.get(this.textNode);
		for (var i = 0; i < textNode.dom.childNodes.length; ++i) {
			if (textNode.dom.childNodes[i].nodeName === 'SPAN') {
				ddHandles[handlesIndex++] = textNode.dom.childNodes[i];
			}
		}

		return ddHandles;
	},

	/**
	 * Only set the onOver class if we are not in dragging mode
	 *
	 * @return {void}
	 */
	onOver: function() {
		if (!this.node.ownerTree.dontSetOverClass) {
			TYPO3.Components.PageTree.PageTreeNodeUI.superclass.onOver.apply(this, arguments);
		}
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.PageTreeNodeUI', TYPO3.Components.PageTree.PageTreeNodeUI);
