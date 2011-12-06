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
 * @class TYPO3.Components.PageTree.FilteringTree
 *
 * Filtering Tree
 *
 * @namespace TYPO3.Components.PageTree
 * @extends TYPO3.Components.PageTree.Tree
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 */
Ext.define('TYPO3.Components.PageTree.FilteringTree', {
	extend: 'TYPO3.Components.PageTree.Tree',

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
	addStore: function (store) {
		this.store = Ext.data.StoreManager.lookup(this.getId() + 'FilteredPageTreeStore');
		if (!this.store) {
			this.store = Ext.create('Ext.data.TreeStore', {
				clearOnLoad: false,
				listeners: {
					beforeload: {
						fn: function (store, operation) {
							if (!this.searchWord || this.searchWord === '') {
								return false;
							}
							if (operation.node) {
								var node = operation.node;
								node.removeAll();
								node.commit();
								operation.params = {
									nodeId: node.getNodeData('id'),
									nodeData: node.get('nodeData'),
									searchWord: this.searchWord
									
								};
							}
						},
						scope: this
					}
				},
				model: 'TYPO3.Components.PageTree.Model',
				nodeParam: 'nodeId',
				proxy: {
					type: 'direct',
					paramOrder: ['nodeId', 'nodeData', 'searchWord'],
					directFn: this.treeDataProvider.getFilteredTree,
					reader: {
					    type: 'json'
					}
				},
				root: this.rootNodeConfig,
				storeId: this.getId() + 'FilteredPageTreeStore'
			});
		}
	}
});
