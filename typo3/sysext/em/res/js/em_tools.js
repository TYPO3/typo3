/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper <info@sk-typo3.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * ExtJS for the extension manager.
 *
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage extension manager
 * @version $Id: $
 */
Ext.ns('TYPO3.EM');

TYPO3.EM.Tools = function() {
	return {
		displayLocalExtension: function(extKey, reload) {
			localStore = Ext.StoreMgr.get('localextensionstore');
			// select local extension list
			Ext.getCmp('em-main').setActiveTab(0);
			if (reload === true) {
				localStore.showAction = extKey;
				localStore.load();
			} else {
				// find row and expand
				/*var row = localStore.find('extkey', extKey);
				 var grid = Ext.getCmp('em-local-extensions');

				 grid.expander.expandRow(row);
				 grid.getView().focusRow(row);
				 grid.getSelectionModel().selectRow(row);*/
			}
		},

		uploadExtension: function() {
			w = new Ext.Window({
				title: 'Upload extension file directly (.t3x)',
				modal: true,
				closable: true,
				plain: true,
				width: 400,
				height: 160,
				layout: 'form',
				fileUpload: true,
				items: [
					{
						xtype: 'fileuploadfield',
						id: 'form-file',
						emptyText: 'Select Extension (*.t3x)',
						fieldLabel: 'Extension',
						name: 'extupload-path',
						buttonText: '...',
						width: 250,
						validator: function(value) {
							if (value) {
								return value.split('.').pop().toLowerCase() === 't3x';
							}
							return false;
						}
					},
					TYPO3.EM.UploadLocationCombo,
					{
						xtype: 'checkbox',
						fieldLabel: 'Overwrite any existing extension!',
						name: 'uploadOverwrite',
						labelWidth: 250
					},
					{
						xtype: 'button',
						text: 'Upload extension from your computer',
						id: 'uploadSubmitButton',
						width: 420,
						scope: this,
						handler: function() {
							if (this.form.isValid()) {
								this.form.submit({
									waitMsg : 'Sending data...',
									success: function(form, action) {
										form.reset();
										TYPO3.Flashmessage.display(TYPO3.Severity.information, 'Extension Upload', 'Extension "' + action.result.extKey + '" was uploaded.', 5);
										w.close();
										TYPO3.EM.Tools.displayLocalExtension(action.result.extKey, true);
									},
									failure: function(form, action) {
										if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
											TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Error',
													'Status:' + action.response.status + ': ' +
															action.response.statusText, 15);
										}
										if (action.failureType === Ext.form.Action.SERVER_INVALID) {
											// server responded with success = false
											TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Invalid', action.result.errormsg, 5);
										}
										w.close();
									}
								});
							}
						}
					}
				]
			}).show();
		}
	};
}();