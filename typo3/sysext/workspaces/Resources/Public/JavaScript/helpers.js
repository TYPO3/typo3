/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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

Ext.ns('TYPO3.Workspaces');

TYPO3.Workspaces.Helpers = {
	/**
	 * Gets an form values object like {'element-1':on, 'element-2':on} and returns
	 * the checked results in an array like ['1', '2'].
	 *
	 * @param object values
	 * @param string elementPrefix
	 * @return array
	 */
	getElementIdsFromFormValues: function(values, elementPrefix) {
		var results = [];
		var pattern = new RegExp('^' + elementPrefix + '-' + '(.+)$');

		Ext.iterate(values, function(key, value) {
			if (value == 'on' && pattern.test(key)) {
				results.push(RegExp.$1);
			}
		});

		return results;
	},

	getSendToStageWindow: function(configuration) {
		top.TYPO3.Windows.close('sendToStageWindow');
		return top.TYPO3.Windows.showWindow({
			id: 'sendToStageWindow',
			title: configuration.title,
			items: [
				{
					xtype: 'form',
					id: 'sendToStageForm',
					width: '100%',
					bodyStyle: 'padding: 5px 5px 3px 5px; border-width: 0; margin-bottom: 7px;',
					items: configuration.items
				}
			],
			buttons: [
				{
					text: TYPO3.l10n.localize('ok'),
					handler: configuration.executeHandler
				},
				{
					text: TYPO3.l10n.localize('cancel'),
					handler: function(event) {
						top.TYPO3.Windows.close('sendToStageWindow');
					}
				}
			]
		});
	},

	getElementsArrayOfSelection: function(selection) {
		var elements = [];

		Ext.each(selection, function(item) {
			var element = {
				table: item.data.table,
				t3ver_oid: item.data.t3ver_oid,
				uid: item.data.uid
			}
			elements.push(element);
		});

		return elements;
	},

	getElementsArrayOfSelectionForIntegrityCheck: function(selection) {
		var elements = [];

		Ext.each(selection, function(item) {
			var element = {
				table: item.data.table,
				liveId: item.data.t3ver_oid,
				versionId: item.data.uid
			}
			elements.push(element);
		});

		return elements;
	},

	getHistoryWindow: function(configuration) {
		top.TYPO3.Windows.close('historyWindow');
		return top.TYPO3.Windows.showWindow({
			id: 'historyWindow',
			title: 'Record History',
			stateful: false,
			modal: false,

			autoHeight: true,
			boxMaxHeight: 500,
			width: 700,

			buttons: [
				{
					text: TYPO3.l10n.localize('ok'),
					handler: function(event) {
						top.TYPO3.Windows.close('historyWindow');
					}
				}
			],

			items: [
				{
					xtype: 'grid',

					border : false,
					loadMask : true,
					stripeRows: true,
					autoHeight: true,
					style: 'min-height: 100px',

					viewConfig: {
						forceFit: true
					},

					store: {
						xtype: 'directstore',
						autoLoad: true,
						autoDestroy: true,
						reader: new Ext.data.JsonReader({
							idProperty : 'id',
							root : 'data',
							totalProperty : 'total',
							fields: [
								{ name: 'datetime' },
								{ name: 'user' },
								{ name: 'differences' }
							]
						}),
						proxy: new Ext.data.DirectProxy({
							directFn : TYPO3.Workspaces.ExtDirect.getHistory
						}),
						listeners: {
							beforeload: function(store, options) {
								store.setBaseParam('table', configuration.table);
								store.setBaseParam('liveId', configuration.liveId);
								store.setBaseParam('versionId', configuration.versionId);
							}
						}
					},

					colModel: new Ext.grid.ColumnModel({
						columns: [
							{ width: 30, id: 'datetime', header: 'Date' },
							{ width: 20, id: 'user', header: 'User', dataIndex: 'user' },
							{ id: 'differences', header: 'Differences', dataIndex: 'differences' }
						]
					})
				}
			]
		});
	}
};