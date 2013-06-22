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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.TreeEditor
 *
 * Custom Tree Editor implementation to enable different source fields for the
 * editable label.
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.tree.TreeEditor
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.TreeEditor = Ext.extend(Ext.tree.TreeEditor, {
	/**
	 * Don't send any save events if the value wasn't changed
	 *
	 * @type {Boolean}
	 */
	ignoreNoChange: false,

	/**
	 * Edit delay
	 *
	 * @type {int}
	 */
	editDelay: 250,

	/**
	 * Indicates if an underlying shadow should be shown
	 *
	 * @type {Boolean}
	 */
	shadow: false,

	/**
	 * Listeners
	 *
	 * Handles the synchronization between the edited label and the shown label.
	 */
	listeners: {
		beforecomplete: function(node) {
			this.updatedValue = this.getValue();
			if (this.updatedValue === '') {
				this.cancelEdit();
				return false;
			}
			this.setValue(this.editNode.attributes.prefix + Ext.util.Format.htmlEncode(this.updatedValue) + this.editNode.attributes.suffix);
		},

		complete: {
			fn: function(node, newValue, oldValue) {
				if (newValue === oldValue) {
					this.fireEvent('canceledit', this);
					return false;
				}

				this.editNode.getOwnerTree().commandProvider.saveTitle(node, this.updatedValue, oldValue, this);
			}
		},

		startEdit: {
			fn: function(element, value) {
				this.field.selectText();
			}
		},

		canceledit: function() {
			var tree = this.editNode.getOwnerTree();
			if (tree.currentSelectedNode) {
				tree.currentSelectedNode.select();
			}
		}
	},

	/**
	 * Updates the edit node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {String} editableText
	 * @param {String} updatedNode
	 * @return {void}
	 */
	updateNodeText: function(node, editableText, updatedNode) {
		this.editNode.setText(this.editNode.attributes.prefix + updatedNode + this.editNode.attributes.suffix);
		this.editNode.attributes.editableText = editableText;
	},

	/**
	 * Overridden method to set another editable text than the node text attribute
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @return {Boolean}
	 */
	triggerEdit: function(node) {
		this.completeEdit();
		if (node.attributes.editable !== false) {
			this.editNode = node;
			if (this.tree.autoScroll) {
				Ext.fly(node.ui.getEl()).scrollIntoView(this.tree.body);
			}

			var value = node.text || '';
			if (!Ext.isGecko && Ext.isEmpty(node.text)) {
				node.setText(' ');
			}

				// TYPO3 MODIFICATION to use another attribute
			value = node.attributes.editableText;

			this.autoEditTimer = this.startEdit.defer(this.editDelay, this, [node.ui.textNode, value]);
			return false;
		}
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.TreeEditor', TYPO3.Components.PageTree.TreeEditor);
