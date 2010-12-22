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
Ext.namespace('TYPO3.Components.PageTree');

/**
 * @class TYPO3.Components.PageTree.DeletionDropZone
 *
 * Deletion Drop Zone
 *
 * @namespace TYPO3.Components.PageTree
 * @extends Ext.Panel
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.DeletionDropZone = Ext.extend(Ext.Panel, {
	/**
	 * Component Id
	 *
	 * @type {String}
	 */
	id: 'typo3-pagetree-deletionDropZone',

	/**
	 * Border
	 *
	 * @type {Boolean}
	 */
	border: true,

	/**
	 * Command Provider
	 *
	 * @cfg {Object}
	 */
	commandProvider: null,

	/**
	 * Drag and Drop Group
	 *
	 * @cfg {String}
	 */
	ddGroup: '',

	/**
	 * Page Tree
	 *
	 * @cfg {TYPO3.Components.PageTree.Tree}
	 */
	tree: null,

	/**
	 * Removed node had a previous sibling
	 *
	 * @type {Boolean}
	 */
	isPreviousSibling: false,

	/**
	 * Removed node
	 *
	 * @type {Ext.tree.TreeNode}
	 */
	previousNode: null,

	/**
	 * Click Handler for the recovery text
	 *
	 * @type {Function}
	 */
	textClickHandler: null,

	/**
	 * Listeners
	 *
	 * The "afterrender" event creates the drop zone
	 */
	listeners: {
		afterrender: {
			fn: function() {
				this.createDropZone();
			}
		}
	},

	/**
	 * Initializes the component
	 *
	 * @return {void}
	 */
	initComponent: function() {
		this.html = '<p><span id="' + this.id + '-icon" class="' +
			TYPO3.Components.PageTree.Sprites.TrashCan +
			'">&nbsp;</span><span id="' + this.id + '-text">' +
			TYPO3.Components.PageTree.LLL.dropToRemove + '</span></p>';

		TYPO3.Components.PageTree.DeletionDropZone.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Creates the drop zone and it's functionality
	 *
	 * @return {void}
	 */
	createDropZone: function() {
		(new Ext.dd.DropTarget(this.getEl(), {
			ddGroup: this.ddGroup,

			notifyDrop: function(ddProxy, e, n) {
				var node = n.node;
				if (!node) {
					return;
				}

				var tree = node.ownerTree;
				if (this.textClickHandler) {
					this.toOriginState(false);
				}

				if (!top.TYPO3.configuration.inWorkspace) {
					this.updateText(TYPO3.Components.PageTree.LLL.dropZoneElementRemoved);
					this.updateIcon(TYPO3.Components.PageTree.Sprites.TrashCanRestore);

					(function() {
						if (this.textClickHandler) {
							this.toOriginState();
						}
					}).defer(5000, this);

					this.textClickHandler = this.restoreNode.createDelegate(this, [node, tree]);
					Ext.get(this.id + '-text').on('click', this.textClickHandler);

					this.isPreviousSibling = false;
					this.previousNode = node.parentNode;
					if (node.previousSibling) {
						this.previousNode = node.previousSibling;
						this.isPreviousSibling = true;
					}
				}

				node.ownerTree.commandProvider.removeNode(node, tree);
			}.createDelegate(this)
		}));
	},

	/**
	 * Updates the drop zone text label
	 *
	 * @param {String} text
	 * @param {Boolean} animate
	 * @return {void}
	 */
	updateText: function(text, animate) {
		animate = animate || false;

		var elementText = Ext.get(this.id + '-text');
		if (animate) {
			elementText.animate({opacity: {to: 0}}, 1, function(elementText) {
				elementText.update(text);
				elementText.setStyle('opacity', 1);
			});
		} else {
			elementText.update(text);
		}
	},

	/**
	 * Updates the drop zone icon with another sprite icon
	 *
	 * @param {String} classes
	 * @return {void}
	 */
	updateIcon: function(classes) {
		var icon = Ext.get(this.id + '-icon');
		icon.set({
			'class': classes
		});
	},

	/**
	 * Resets the drop zone to the initial state
	 *
	 * @param {Boolean} animate
	 * @return {void}
	 */
	toOriginState: function(animate) {
		if (animate !== false) {
			animate = true;
		}

		this.updateText(TYPO3.Components.PageTree.LLL.dropToRemove, animate);
		this.updateIcon(TYPO3.Components.PageTree.Sprites.TrashCan);
		Ext.get(this.id + '-text').un('click', this.textClickHandler);
		this.previousNode = this.textClickHandler = null;
		this.isPreviousSibling = false;
	},

	/**
	 * Restores the last removed node
	 *
	 * @param {Ext.tree.TreeNode} node
	 * @param {TYPO3.Components.PageTree.Tree} tree
	 * @return {void}
	 */
	restoreNode: function(node, tree) {
		if (this.isPreviousSibling) {
			this.commandProvider.restoreNodeAfterDestination(node, tree, this.previousNode);
		} else {
			this.commandProvider.restoreNodeToFirstChildOfDestination(node, tree, this.previousNode);
		}
		this.updateText(TYPO3.Components.PageTree.LLL.dropZoneElementRestored);

		(function() {
			if (this.textClickHandler) {
				this.toOriginState();
			}
		}).defer(3000, this);
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.DeletionDropZone', TYPO3.Components.PageTree.DeletionDropZone);