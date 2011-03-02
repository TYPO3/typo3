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
 * @class TYPO3.Components.PageTree.FilteringTree
 *
 * Filtering Tree
 *
 * @namespace TYPO3.Components.PageTree
 * @extends TYPO3.Components.PageTree.Tree
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
TYPO3.Components.PageTree.FilteringTree = Ext.extend(TYPO3.Components.PageTree.Tree, {
	/**
	 * Search word
	 *
	 * @type {String}
	 */
	searchWord: '',

	/**
	 * Tree loader implementation for the filtering tree
	 *
	 * @return {void}
	 */
	addTreeLoader: function() {
		this.loader = new Ext.tree.TreeLoader({
			directFn: this.treeDataProvider.getFilteredTree,
			paramOrder: 'nodeId,attributes,searchWord',
			nodeParameter: 'nodeId',
			baseAttrs: {
				uiProvider: this.uiProvider
			},

			listeners: {
				beforeload: function(treeLoader, node) {
					if (!node.ownerTree.searchWord || node.ownerTree.searchWord === '') {
						return false;
					}

					treeLoader.baseParams.nodeId = node.id;
					treeLoader.baseParams.searchWord = node.ownerTree.searchWord;
					treeLoader.baseParams.attributes = node.attributes.nodeData;
				}
			}
		});
	}
});

// XTYPE Registration
Ext.reg('TYPO3.Components.PageTree.FilteringTree', TYPO3.Components.PageTree.FilteringTree);
