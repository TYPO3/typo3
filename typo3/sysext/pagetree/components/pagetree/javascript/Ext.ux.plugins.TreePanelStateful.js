/******************************************************************
 * @class Ext.ux.plugins.TreePanelStateful
 * @extends Ext.util.Observable

 * @constructor
 * var treePanel = new Ext.ux.TreePanel({
 ...add TreePanel options
 plugins:new Ext.ux.plugins.TreePanelStateful()
 });
 *****************************************************************/
Ext.namespace('Ext.ux.plugins');

Ext.ux.plugins.TreePanelStateful = function(config) {
	Ext.apply(this, config);
};

Ext.extend(Ext.ux.plugins.TreePanelStateful, Ext.util.Observable, {
	treePanel: null,
	init: function(treePanel) {
		this.treePanel = treePanel;
		Ext.apply(treePanel, {
			// internal variables
			taskId: '',
			ignoreCookie: false,
			startQueryClicked: false,
			nodeAttrs: {},
			oldParentNodeAttrs: {},
			newParentNodeAttrs: {},

			//CookieProvider - hold state of TreePanel
			cp: null,

			//TreePanel state - simple array
			state: null,

			//stateful option set to true
			stateful: true,

			//Last selected node
			lastSelectedNode: null,

			//Function which saves TreePanel state
			saveState : function(newState) {
				this.state = newState;
				this.cp.set('TreePanelStateful_' + treePanel.taskId, this.state);
			},

			//Function which restores TreePanel state
			restoreState : function(defaultPath) {
				if (!treePanel.ignoreCookie) {
					var stateToRestore = this.state;

					if (this.state.length == 0) {
						var newState = new Array(defaultPath);
						this.saveState(newState);
						this.expandPath(defaultPath);
						return;
					}

					for (var i = 0; i < stateToRestore.length; ++i) {
						// activate all path strings from the state
						try {
							var path = stateToRestore[i];
							this.expandPath(path);
						}
						catch(e) {
							// ignore invalid path, seems to be remove in the datamodel
							// TODO fix state at this point
						}
					}
				}
			},

			/***** Events which cause TreePanel to remember its state
			 * click, expandnode, collapsenode, load, textchange,
			 * remove, render
			 ********************************************************/
			stateEvents: [{
				click: {
					fn: function(node) {
						this.cp.set('LastSelectedNodePath_' + treePanel.taskId, node.getPath());
						this.cp.set('LastSelectedNodeId_' + treePanel.taskId, node.id);
					}
				},
				expandnode: {
					fn: function(node) {
						var currentPath = node.getPath();
						var newState = new Array();

						for (var i = 0; i < this.state.length; ++i) {
							var path = this.state[i];

							if (currentPath.indexOf(path) == -1) {
								// this path does not already exist
								newState.push(path);
							}
						}

						// now ad the new path
						newState.push(currentPath);
						this.saveState(newState);
					}
				},
				collapsenode: {
					fn: function(node) {
						var parentNode;
						if (node.id == this.root.id) {
							return;
						}

						var closedPath = node.getPath();
						var newState = new Array();

						for (var i = 0; i < this.state.length; ++i) {
							var path = this.state[i];
							if (path.indexOf(closedPath) == -1) {
								// this path is not a subpath of the closed path
								newState.push(path);
							}
							else {
								if (path == closedPath) {
									parentNode = node.parentNode;

									if (parentNode.id != this.root.id) {
										newState.push(parentNode.getPath());
									}
								}
							}
						}

						if (newState.length == 0) {
							parentNode = node.parentNode;
							newState.push((parentNode == null ? this.pathSeparator : parentNode.getPath()));
						}

						this.saveState(newState);
					}
				},
				load: {
					fn: function(node) {
						var lastSelectedNodePath = this.cp.get('LastSelectedNodePath_' + treePanel.taskId);
						var lastSelectedNodeId = this.cp.get('LastSelectedNodeId_' + treePanel.taskId);

						var rootNode = this.getRootNode();
						if (node.id == rootNode.id == lastSelectedNodeId) {
							this.selectPath(lastSelectedNodePath);
							node.fireEvent('click', node);
							return;
						}

						if (node.id == lastSelectedNodeId) {
							node.fireEvent('click', node);
						} else {
							var childNode = node.findChild('id', lastSelectedNodeId);

							if (childNode && childNode.isLeaf()) {
								childNode.ensureVisible();
								this.selectPath(lastSelectedNodePath);
								childNode.fireEvent('click', childNode);
							}
							else if (childNode && !childNode.isLeaf()) {
								this.selectPath(lastSelectedNodePath);
								childNode.fireEvent('click', childNode);
							}
						}
					}
				},
				textchange: {
					fn: function(node, text, oldText) {
						var lastSelectedNodePath = this.cp.get('LastSelectedNodePath_' + treePanel.taskId);
						if (lastSelectedNodePath) {
							return;
						}
						
						var newSelectedNodePath = lastSelectedNodePath.replace(oldText, text);

						this.cp.set('LastSelectedNodePath_' + treePanel.taskId, newSelectedNodePath);

						this.expandPath(node.getPath());
						this.selectPath(node.getPath());
					}
				},
				remove: {
					fn: function(tree, parentNode, node) {
						var lastSelectedNodeId = this.cp.get('LastSelectedNodeId_' + treePanel.taskId);
						if (!tree.movingNode) {
							if (node.id == lastSelectedNodeId) {
								this.cp.set('LastSelectedNodePath_' + treePanel.taskId, parentNode.getPath());
								this.cp.set('LastSelectedNodeId_' + treePanel.taskId, parentNode.id);
							}

							this.cp.set('DeletedNodeParent_' + treePanel.taskId + '_' + node.id, parentNode.id);
							this.cp.set('DeletedNode_' + treePanel.taskId + '_' + node.id, node.id);

							if (tree.deleteWithChildren)
								this.cp.set('DeletedNodeWithChildren_' + treePanel.taskId + '_' + node.id, "true");

							tree.deleteWithChildren = false;
						}
					}
				},
				movenode: {
					fn: function(tree, node, oldParent, newParent) {
						var lastSelectedNodeId = this.cp.get('LastSelectedNodeId_' + treePanel.taskId);
						if (node.id == lastSelectedNodeId) {
							this.cp.set('LastSelectedNodePath_' + treePanel.taskId, newParent.getPath());
							this.cp.set('LastSelectedNodeId_' + treePanel.taskId, newParent.id);
						}

						this.cp.set('MovedNodeOldParent_' + treePanel.taskId + '_' + node.id, oldParent.id);
						this.cp.set('MovedNodeNewParent_' + treePanel.taskId + '_' + node.id, newParent.id);
						this.cp.set('MovedNode_' + treePanel.taskId + '_' + node.id, node.id);
					}
				}
			}]
		});

		if (!treePanel.stateful) {
			treePanel.stateful = true;
		}

		if (!treePanel.cp) {
			treePanel.cp = new Ext.state.CookieProvider({expires: null});
		}

		if (!treePanel.lastSelectedNode) {
			var cookieLastSelectedNode = treePanel.cp.get('LastSelectedNodeId_' + treePanel.taskId);

			if (!cookieLastSelectedNode) {
				treePanel.lastSelectedNode = treePanel.root;
			}
			else {
				treePanel.lastSelectedNode = cookieLastSelectedNode;
			}
		}

		if (!treePanel.state) {
			var cookieState = treePanel.cp.get('TreePanelStateful_' + treePanel.taskId);

			if (!cookieState) {
				treePanel.state = new Array();
			}
			else {
				treePanel.state = cookieState;
			}
		}

		treePanel.restoreState(treePanel.root.getPath());
	},
	updateState: function(treePanel, parentNode, node) {
		if (!treePanel.ignoreCookie && !treePanel.startQueryClicked) {
			/*
			 * Check for deleted nodes.
			 */
			var deletedNode = treePanel.getNodeById(treePanel.cp.get('DeletedNode_' + this.treePanel.taskId + '_' + node.id));

			if (deletedNode != undefined) {
				var deleteWithChildren = treePanel.cp.get('DeletedNodeWithChildren_' + this.treePanel.taskId + '_' + node.id);

				if (deleteWithChildren === "true")
					treePanel.deleteWithChildren = true;

				deletedNode.remove();

				if (!parentNode.hasChildNodes())
					parentNode.expand();
			}
			else {
				/*
				 * Check for moved nodes.
				 */
				treePanel.movingNode = true;

				var movedNode = treePanel.getNodeById(treePanel.cp.get('MovedNode_' + treePanel.taskId + '_' + node.id));

				if (movedNode != undefined) {
					var oldParentNode = treePanel.getNodeById(treePanel.cp.get('MovedNodeOldParent_' + treePanel.taskId + '_' + node.id));
					var newParentNode = treePanel.getNodeById(treePanel.cp.get('MovedNodeNewParent_' + treePanel.taskId + '_' + node.id));

					if (oldParentNode != undefined && newParentNode != undefined) {
						oldParentNode.removeChild(node);
						newParentNode.appendChild(node);
						newParentNode.lastChild = node;

						if (treePanel.nodeAttrs.indexOf(node.attributes.id) === -1) {
							treePanel.nodeAttrs.push(node.id);
							treePanel.oldParentNodeAttrs.push(oldParentNode.id);
							treePanel.newParentNodeAttrs.push(newParentNode.id);
						}
						else {
							treePanel.nodeAttrs[treePanel.nodeAttrs.indexOf(node.attributes.id)] = node.id;
							treePanel.oldParentNodeAttrs[treePanel.nodeAttrs.indexOf(node.attributes.id)] = oldParentNode.id;
							treePanel.newParentNodeAttrs[treePanel.nodeAttrs.indexOf(node.attributes.id)] = newParentNode.id;
						}
						treePanel.gridModified = true;
					}
				}

				treePanel.movingNode = false;
			}
		}
		else {
			treePanel.cp.clear('TreePanelStateful_' + treePanel.taskId);
			treePanel.cp.clear('LastSelectedNodePath_' + treePanel.taskId);
			treePanel.cp.clear('LastSelectedNodeId_' + treePanel.taskId);
			treePanel.cp.clear('DeletedNodeParent_' + treePanel.taskId + '_' + node.id);
			treePanel.cp.clear('DeletedNode_' + treePanel.taskId + '_' + node.id);
			treePanel.cp.clear('DeletedNodeWithChildren_' + treePanel.taskId + '_' + node.id);
			treePanel.cp.clear('MovedNodeOldParent_' + treePanel.taskId + '_' + node.id);
			treePanel.cp.clear('MovedNodeNewParent_' + treePanel.taskId + '_' + node.id);
			treePanel.cp.clear('MovedNode_' + treePanel.taskId + '_' + node.id);
		}
	}
});