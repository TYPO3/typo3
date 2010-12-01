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

TYPO3.EM.TerUpload = Ext.extend(Ext.form.FormPanel, {
	border:false,
	recordData: null,

	initComponent:function() {



		Ext.apply(this, {
			itemId: 'extUploadForm',
			height: 340,
			defaultType: 'textfield',

			defaults: {width: 350},
			items: [{
				fieldLabel: 'Repository Username',
				name: 'fe_u'
			}, {
				fieldLabel: 'Repository Username',
				inputType: 'password',
				name: 'fe_p'
			}, {
				fieldLabel: 'Changelog for upload',
				xtype: 'textarea',
				height: 150,
				name: 'uploadcomment'
			}, {
				xtype: 'radiogroup',
				fieldLabel: 'New Version',
				itemCls: 'x-check-group-alt',
				columns: 1,
				items: [
					{boxLabel: 'New bugfix version (latest x.x.<strong><span class="typo3-red">x+1</span></strong>)', name: 'newversion', inputValue: 'new_dev',checked: true},
					{boxLabel: 'New sub version (latest x.<strong><span class="typo3-red">x+1</span></strong>.0)', name: 'newversion', inputValue: 'new_sub'},
					{boxLabel: 'New main version (latest <strong><span class="typo3-red">x+1</span></strong>.0.0)', name: 'newversion', inputValue: 'new_main'}
				]
			}, {
				xtype: 'button',
				text: 'Upload extension',
				scope: this,
				handler: function() {
					this.form.submit({
						waitMsg : 'Sending data...',
						success: function(form, action) {
							TYPO3.Flashmessage.display(TYPO3.Severity.information,'TER Upload', 'Extension was uploaded to TER', 5);
							form.reset();
						},
						failure: function(form, action) {
							if (action.failureType === Ext.form.Action.CONNECT_FAILURE) {
								TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Error',
										'Status:'+action.response.status+': '+
										action.response.statusText, 5);
							}
							if (action.failureType === Ext.form.Action.SERVER_INVALID){
								// server responded with success = false
								TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Invalid', action.result.errormsg, 5);
							}
						}
					});
				}
			}],
			listeners: {

				activate: function(panel) {


				}
			},
			scope: this
		});

		TYPO3.EM.TerUpload.superclass.initComponent.apply(this, arguments);
	},

	onRender: function() {


		TYPO3.EM.TerUpload.superclass.onRender.apply(this, arguments);

		Ext.apply(this.getForm(),{
			api: {
				load: TYPO3.EM.ExtDirect.loadUploadExtToTer,
				submit: TYPO3.EM.ExtDirect.uploadExtToTer
			},
			paramsAsHash: false

		});
		this.form.load();
	}


});

Ext.reg('terupload', TYPO3.EM.TerUpload);