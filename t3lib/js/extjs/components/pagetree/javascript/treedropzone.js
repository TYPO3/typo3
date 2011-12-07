/***************************************************************
*  Copyright notice
*
*  (c) 2011 Stanislas Rolland <typo3@sjbr.ca>
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
 * @class TYPO3.Components.PageTree.ViewDropZone
 *
 * handleNodeDrop method is modified in order to process copied nodes
 * 
 * Based on ExtJS 4.0.7.
 * Should be reviewed on ExtJS upgrade
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.tree.ViewDropZone
 * @author Stanislas Rolland <typo3@sjbr.ca>
 */
Ext.define('TYPO3.Components.PageTree.ViewDropZone', {
	extend: 'Ext.tree.ViewDropZone',

	handleNodeDrop: function(data, targetNode, position) {
		var me = this,
			view = me.view,
			parentNode = targetNode.parentNode,
			store = view.getStore(),
			recordDomNodes = [],
			records, i, len,
			insertionMethod, argList,
			needTargetExpand,
			transferData,
			processDrop;

		if (data.copy) {
			records = data.records;
			data.records = [];
			for (i = 0, len = records.length; i < len; i++) {
				data.records.push(Ext.apply({}, records[i].data));
			}
		}

		me.cancelExpand();

		if (position == 'before') {
			insertionMethod = parentNode.insertBefore;
			argList = [null, targetNode];
			targetNode = parentNode;
		} else if (position == 'after') {
			if (targetNode.nextSibling) {
				insertionMethod = parentNode.insertBefore;
				argList = [null, targetNode.nextSibling];
			} else {
				insertionMethod = parentNode.appendChild;
				argList = [null];
			}
			targetNode = parentNode;
		} else {
			if (!targetNode.isExpanded()) {
				needTargetExpand = true;
			}
			insertionMethod = targetNode.appendChild;
			argList = [null];
		}

		transferData = function() {
			var node;
			for (i = 0, len = data.records.length; i < len; i++) {
				argList[0] = data.records[i];
				node = insertionMethod.apply(targetNode, argList);
					// We need to update the records array in order to process the copied nodes in the drop event handler
				data.records[i] = node;

				if (Ext.enableFx && me.dropHighlight) {
					recordDomNodes.push(view.getNode(node));
				}
			}

			if (Ext.enableFx && me.dropHighlight) {
				Ext.Array.forEach(recordDomNodes, function(n) {
					if (n) {
						Ext.fly(n.firstChild ? n.firstChild : n).highlight(me.dropHighlightColor);
					}
				});
			}
		};

		if (needTargetExpand) {
			targetNode.expand(false, transferData);
		} else {
			transferData();
		}
	}
});