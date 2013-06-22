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
Ext.ns('Ext.ux.state');

// dummy constructor
Ext.ux.state.TreePanel = function() {};

/**
 * State Provider for a tree panel
 */
Ext.override(Ext.ux.state.TreePanel, {
	/**
	 * Initializes the plugin
	 * @param {Ext.tree.TreePanel} tree
	 * @private
	 */
	init:function(tree) {
		tree.lastSelectedNode = null;
		tree.isRestoringState = false;
		tree.stateHash = {};

		// install event handlers on TreePanel
		tree.on({
			// add path of expanded node to stateHash
			beforeexpandnode:function(node) {
				if (this.isRestoringState) {
					return;
				}

				var saveID = (node.id === 'root' ? node.id : node.id.substr(1));
				this.stateHash[saveID] = 1;
			},

			// delete path and all subpaths of collapsed node from stateHash
			beforecollapsenode:function(node) {
				if (this.isRestoringState) {
					return;
				}

				var deleteID = (node.id === 'root' ? node.id : node.id.substr(1));
				delete this.stateHash[deleteID];
			},

			beforeclick: function(node) {
				if (this.isRestoringState) {
					return;
				}
				this.stateHash['lastSelectedNode'] = node.id;
			}
		});

			// update state on node expand or collapse
		tree.stateEvents = tree.stateEvents || [];
		tree.stateEvents.push('expandnode', 'collapsenode', 'click');

		// add state related props to the tree
		Ext.apply(tree, {
			// keeps expanded nodes paths keyed by node.ids
			stateHash:{},

			restoreState: function() {
				this.isRestoringState = true;
					// get last selected node
				for (var pageID in this.stateHash) {
					var pageNode = this.getNodeById((pageID !== 'root' ? 'p' : '') + pageID);
					if (pageNode) {
						pageNode.on({
							expand: {
								single:true,
								scope:this,
								fn: this.restoreState
							}
						});
						if (pageNode.expanded === false && pageNode.rendered == true) {
							pageNode.expand();
						}
					}
				}

				if (this.stateHash['lastSelectedNode']) {
					var node = this.getNodeById(this.stateHash['lastSelectedNode']);
					if (node) {
						var contentId = TYPO3.Backend.ContentContainer.getIdFromUrl() ||
							String(fsMod.recentIds['web']) || '-1';

						var hasContentFrameValidPageId = (contentId !== '-1' && contentId !== '0');
						var isCurrentSelectedNode = (
							String(node.attributes.nodeData.id) === contentId ||
							contentId.indexOf('pages' + String(node.attributes.nodeData.id)) !== -1
						);

						if (isCurrentSelectedNode) {
							this.selectPath(node.getPath());
						}

						var isSingleClickPossible = (this.app.isVisible() && this.commandProvider && this.commandProvider.singleClick);
						if (!hasContentFrameValidPageId && !isCurrentSelectedNode && isSingleClickPossible) {
							this.selectPath(node.getPath());
							this.commandProvider.singleClick(node, this);
						}
					}
				}

				this.isRestoringState = false;
			},

			// apply state on tree initialization
			applyState:function(state) {
				if(state) {
					Ext.apply(this, state);

					// it is too early to expand paths here
					// so do it once on root load
					this.root.on({
						load: {
							single:true,
							scope:this,
							fn: this.restoreState
						}
					});
				}
			},

			// returns stateHash for save by state manager
			getState:function() {
				return {stateHash:this.stateHash};
			}
		});
	}
});
