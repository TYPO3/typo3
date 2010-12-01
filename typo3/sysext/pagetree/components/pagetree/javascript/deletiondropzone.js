Ext.namespace('TYPO3.Components.PageTree');

/**
 * Main filtering tree widget
 */
TYPO3.Components.PageTree.DeletionDropZone = Ext.extend(Ext.Panel, {
	id: 'typo3-pagetree-deletionDropZone',
	border: true,
	height: 50,
	html: '<strong>Drag a page into this drop zone to delete it</strong>',

	pageTree: null,

	listeners: {
		afterrender: {
			fn: function() {
				var filteringTree = this.pageTree;
				(new Ext.dd.DropTarget(this.getEl(), {
					notifyDrop: function(ddProxy, e, n) {
						var node = n.node;

						var dropZoneBody = Ext.get(this.el.query('.x-panel-body')[0]);
						dropZoneBody.update('You just deleted "' + node.text + '"<span class="undelete" style="text-decoration: underline;">Undelete</span>');

						Ext.get(dropZoneBody.query('.undelete')[0]).on('click', function() {
							filteringTree.commandProvider.restoreNode(node, filteringTree);
							filteringTree.tree.refreshTree();
							this.update('<strong>Restored</strong>');  // TODO: LOCALIZE

						}.createDelegate(dropZoneBody, [node.id, filteringTree]));

						var fadeOutFn = function() {
							this.animate({opacity: {to: 0}}, 1, function(dropZoneBody) {
								dropZoneBody.update('<strong>DropZone</strong>'); // TODO
								dropZoneBody.setStyle('opacity', 1);
							});
						};
						fadeOutFn.defer(5000, dropZoneBody);

						node.ownerTree.pageTree.commandProvider.removeNode(node, node.ownerTree);
					},
					ddGroup: 'TreeDD',
					overClass: 'dd-delete-over'
				}));

				// TODO: Show drop zone only if currently dragging
			}
		}
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.DeletionDropZone', TYPO3.Components.PageTree.DeletionDropZone);