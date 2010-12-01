/***************************************************************
*  Copyright notice
*
*  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Override TreeNodeUI
 */

Ext.namespace('TYPO3.Components.PageTree');

TYPO3.Components.PageTree.PageTreeUI = Ext.extend(Ext.tree.TreeNodeUI, {
	// private
	// This method is taken from ExtJS sources. Modifications are marked with // START TYPO3-MODIFICATION
	renderElements : function(n, a, targetNode, bulkRender) {
		// add some indent caching, this helps performance when rendering a large tree
		this.indentMarkup = n.parentNode ? n.parentNode.ui.getChildIndent() : '';
		var cb = typeof a.checked === 'boolean';
		var href = a.href ? a.href : Ext.isGecko ? "" : "#";
		var buf = ['<li class="x-tree-node"><div ext:tree-node-id="',n.id,'" class="x-tree-node-el x-tree-node-leaf x-unselectable ', a.cls,'" unselectable="on">',
			'<span class="x-tree-node-indent">',this.indentMarkup,"</span>",
			'<img src="', this.emptyIcon, '" class="x-tree-ec-icon x-tree-elbow" />',
			// START TYPO3-MODIFICATION
			a.spriteIconCode,
			'<span class="prefixText">', a.prefixText, '</span>',
			// END TYPO3-MODIFICATION
			cb ? ('<input class="x-tree-node-cb" type="checkbox" ' + (a.checked ? 'checked="checked" />' : '/>')) : '',
			'<a hidefocus="on" class="x-tree-node-anchor" href="',href,'" tabIndex="1" ',
			a.hrefTarget ? ' target="' + a.hrefTarget + '"' : "", '><span unselectable="on">',n.text,"</span></a></div>",
			'<ul class="x-tree-node-ct" style="display:none;"></ul>',
			"</li>"].join('');

		var nel;
		if (bulkRender !== true && n.nextSibling && (nel = n.nextSibling.ui.getEl())) {
			this.wrap = Ext.DomHelper.insertHtml("beforeBegin", nel, buf);
		} else {
			this.wrap = Ext.DomHelper.insertHtml("beforeEnd", targetNode, buf);
		}

		this.elNode = this.wrap.childNodes[0];
		this.ctNode = this.wrap.childNodes[1];
		var cs = this.elNode.childNodes;
		this.indentNode = cs[0];
		this.ecNode = cs[1];
		this.iconNode = cs[2];
		// START TYPO3-MODIFICATION
		Ext.fly(this.iconNode).on('click', function(event) {
			this.getOwnerTree().openContextMenu(this, event); // calling the context-menu event doesn't work!'
			event.stopEvent();
		}, n);
		// Index from 3 to 4 incremented!
		var index = 4;
		// STOP TYPO3-MODIFICATION
		if (cb) {
			this.checkbox = cs[3];
			// fix for IE6
			this.checkbox.defaultChecked = this.checkbox.checked;
			index++;
		}
		this.anchor = cs[index];
		this.textNode = cs[index].firstChild;
	}
});