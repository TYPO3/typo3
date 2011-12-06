/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stefan Galinski <stefan.galinski@gmail.com>
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
 * @class TYPO3.Components.PageTree.TreeEditor
 *
 * Custom Tree Editor implementation to enable different source fields for the
 * editable label.
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.ux.tree.TreeEditing
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
Ext.define('TYPO3.Components.PageTree.TreeEditor', {
	extend: 'Ext.grid.plugin.CellEditing',
	alias: 'plugin.pagetreeeditor',

	/**
	 * @override
	 * @private Collects all information necessary for any subclasses to perform their editing functions.
	 * @param record
	 * @param columnHeader
	 * @returns {Object} The editing context based upon the passed record and column
	 */
	getEditingContext: function (record, columnHeader) {
	 	var me = this,
	 		grid = me.grid,
	 		store = grid.store,
	 		colIdx,
	 		editor,
	 		originalValue,
	 		value;

		if (Ext.isNumber(columnHeader)) {
			colIdx = columnHeader;
			columnHeader = grid.headerCt.getHeaderAtIndex(colIdx);
		} else {
			colIdx = columnHeader.getIndex();
		}

		return {
			column: columnHeader,
			colIdx: colIdx,
			field: columnHeader.dataIndex,
			grid: grid,
			originalValue: record.getNodeData('editableText'),
			record: record,
			tree: grid
		};
	},

	/**
	 * Listeners
	 *
	 * Handles the synchronization between the edited label and the shown label.
	 */
	listeners: {
		beforeedit: {
			fn: function (editEvent) {
				var tree = editEvent.tree;
					// Prevent editing the currently selected node
					// Prevent editing if the node is not editable
				if (editEvent.record == tree.currentSelectedNode || !editEvent.record.getNodeData('editable')) {
					if (tree.currentSelectedNode) {
						tree.getView().select(tree.currentSelectedNode);
					}
					return false;
				}
					// Inhibit clicks on the tree while editing
				tree.inhibitClicks = 2;
			}
		},
		validateedit: {
			fn: function (treeEditor, editEvent) {
				var editorField = treeEditor.getEditor(editEvent.record, editEvent.column);
				this.newValue = editorField.getValue();
				if (this.newValue === '' || this.newValue === editEvent.originalValue) {
					var tree = editEvent.tree;
					if (tree.currentSelectedNode) {
						tree.getView().select(tree.currentSelectedNode);
					}
					return false;
				} else {
					editorField.setValue(editEvent.record.getNodeData('prefix') + Ext.util.Format.htmlEncode(this.newValue) + editEvent.record.getNodeData('suffix'));
				}
				
			}
		},
		edit: {
			fn: function (treeEditor, editEvent) {
				var tree = editEvent.tree;
				tree.commandProvider.saveTitle(editEvent.record, this.newValue, editEvent.originalValue, treeEditor, tree, editEvent.field);
			}
		}
	},
	cancelEdit: function () {
		var tree = this.grid;
		if (tree.currentSelectedNode) {
			tree.getView().select(tree.currentSelectedNode);
		}
		this.callParent(arguments);
	},

	/**
	 * Updates the text field
	 *
	 * @param {TYPO3.Components.PageTree.Model} node
	 * @param {String} editableText
	 * @param {String} updatedText
	 * @param {String} dataIndex
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	updateNodeText: function (record, editableText, updatedText, dataIndex, tree) {
		record.set(dataIndex, record.getNodeData('prefix') + updatedText + record.getNodeData('suffix'));
		record.setNodeData('editableText', editableText);
		record.commit();
		tree.getView().refresh(record.getId());
	}
});
