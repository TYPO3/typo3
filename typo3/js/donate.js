/***************************************************************
*  Copyright notice
*
*  (c) 2010 Oliver Hader <oliver@typo3.org>
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
 * Donate window appearing in the backend
 */
Ext.namespace('Ext.ux.TYPO3');

Ext.ux.TYPO3.donate = Ext.extend(Ext.util.Observable, {
	isUnloading: false,
	logoutButton: null,
	ajaxRequestDefault: null,
	donateUrl: 'http://typo3.org/donate/',

	constructor: function(config) {
		this.ajaxRequestDefault = {
			url: TYPO3.configuration.PATH_typo3 + 'ajax.php',
			success: Ext.emptyFn,
			failure: Ext.emptyFn
		};

		config = config || {};
		Ext.apply(this, config);

		this.initComponents();
		this.execute.defer(3000, this);
		this.logoutButton = Ext.DomQuery.selectNode('#logout-button input');

		Ext.ux.TYPO3.donate.superclass.constructor.call(this, config);
	},

	execute: function() {
		this.donateWindow.show();
	},

	initComponents: function() {
		this.donateWindow = new Ext.Window({
			width: 450,
			autoHeight: true,
			closable: false,
			resizable: false,
			plain: true,
			border: false,
			modal: true,
			draggable: false,
			closeAction: 'hide',
			id: 'donateWindow',
			cls: 't3-window',
			title: TYPO3.LLL.core.donateWindow_title,
			html: TYPO3.LLL.core.donateWindow_message,
			buttons: [{
				scope: this,
				icon: this.getDonateIcon(),
				text: TYPO3.LLL.core.donateWindow_button_donate,
				handler: this.donateAction
			}, {
				scope: this,
				text: TYPO3.LLL.core.donateWindow_button_disable,
				handler: this.disableAction
			}, {
				scope: this,
				text: TYPO3.LLL.core.donateWindow_button_postpone,
				handler: this.postponeAction
			}]
		});
	},

	unloadEventHandler: function(event) {
		event.stopEvent();
		this.isUnloading = true;
		this.donateWindow.show();
		this.removeUnloadEventListener();
	},

	donateAction: function() {
		this.submitDisableAction();
		this.donateWindow.hide();
		window.open(this.donateUrl).focus();
		this.continueUnloading();
	},

	disableAction: function() {
		this.submitDisableAction();
		this.donateWindow.hide();
		this.continueUnloading();
	},

	postponeAction: function() {
		this.submitPostponeAction();
		this.donateWindow.hide();
		this.addUnloadEventListener();
		this.continueUnloading();
	},

	submitDisableAction: function() {
		Ext.Ajax.request(Ext.apply(
			this.ajaxRequestDefault, {
				params: { 'ajaxID': 'DonateWindow::disable' }
			}
		));
	},

	submitPostponeAction: function() {
		Ext.Ajax.request(Ext.apply(
			this.ajaxRequestDefault, {
				params: { 'ajaxID': 'DonateWindow::postpone' }
			}
		));
	},

	getDonateIcon: function() {
		return TYPO3.configuration.PATH_typo3 + 'sysext/t3skin/images/icons/status/dialog-ok.png';
	},

	addUnloadEventListener: function() {
		if (!this.isUnloading) {
			Ext.EventManager.addListener(this.logoutButton, 'click', this.unloadEventHandler, this);
		}
	},

	removeUnloadEventListener: function() {
		Ext.EventManager.removeListener(this.logoutButton, 'click', this.unloadEventHandler, this);
	},

	continueUnloading: function() {
		if (this.isUnloading && this.logoutButton) {
			this.logoutButton.click();
		}
	}
});



/**
 * Initialize the donate widget
 */
Ext.onReady(function() {
	TYPO3.donate = new Ext.ux.TYPO3.donate();
});
