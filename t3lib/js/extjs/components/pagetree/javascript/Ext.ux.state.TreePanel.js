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
 * State Provider for a tree panel
 */
Ext.define('Ext.ux.state.TreePanel', {
	extend: 'Ext.state.Stateful',

	/**
	 * Mixin constructor
	 * @param {Object} config
	 *
	 */
	constructor: function (config) {
		if (config.stateful) {
				// Install event handlers on TreePanel
			this.on({
					// Add path of expanded node to stateHash
				beforeitemexpand: function (node) {
						if (this.isRestoringState) {
							return;
						}
						this.stateHash[String(node.getNodeData('id'))] = node.getId();
					},
					// Delete collapsed node from stateHash
				beforeitemcollapse: function (node) {
						if (this.isRestoringState) {
							return;
						}
						delete this.stateHash[String(node.getNodeData('id'))];
					},
					// Update last selected node in stateHash
				selectionchange: function (view, node) {
					this.onStateChange();
						if (this.isRestoringState) {
							return;
						}
						if (this.getSelectionModel().getLastSelected()) {
							this.stateHash['lastSelectedNode'] = this.getSelectionModel().getLastSelected().getId();
						}
					},
				scope: this
			});
				// Add/override state properties
			Ext.apply(this, {
					// Update state on node expand, collapse or selection change
				stateEvents: ['itemexpand', 'itemcollapse', 'selectionchange'],
					// Avoid updating state while restoring
				isRestoringState: false,
					// State object
				stateHash: {},
				/**
				 * Restore tree state into the saved state
				 *
				 */
				restoreState: function() {
					if (this.stateful) {
						this.isRestoringState = true;
							// Expand paths according to stateHash
						for (var pageID in this.stateHash) {
							var pageNode = this.getStore().getNodeById(this.stateHash[pageID]);
							if (pageNode && !pageNode.isLeaf() && !pageNode.isExpanded()) {
								pageNode.on({
									expand: {
										single: true,
										scope: this,
										fn: this.restoreState
									}
								});
								pageNode.set('expanded', true);
								pageNode.commit();
								this.refreshNode(pageNode);
								pageNode.fireEvent('expand', pageNode);
							}
						}
							// Get last selected node
						if (this.stateHash['lastSelectedNode']) {
							var node = this.getStore().getNodeById(this.stateHash['lastSelectedNode']);
							if (node) {
								this.selectPath(node.getPath());
				
								var contentId = TYPO3.Backend.ContentContainer.getIdFromUrl() ||
									String(fsMod.recentIds['web']) || '-1';
				
								var isCurrentSelectedNode = (
									String(node.getNodeData('id')) === contentId ||
									contentId.indexOf('pages' + node.getNodeData('id')) !== -1
								);
				
								if (contentId !== '-1' 
									&& !isCurrentSelectedNode
									&& this == this.app.getTree()
									&& this.commandProvider
									&& this.commandProvider.singleClick
								) {
									this.commandProvider.singleClick(node, this);
								}
							}
						}
						this.isRestoringState = false;
					}
				},
				/**
				 * Return stateHash for save by state manager
				 *
				 */
				getState: function() {
					return {
						stateHash: this.stateHash
					};
				}
			});
			this.callParent(arguments);
		}
	}
});
