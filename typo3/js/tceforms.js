/***************************************************************
*  Copyright notice
*
*  (c) 2011 Kay Strobach <typo3@kay-strobach.de>
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
 * Implements polling for changes of TCEForms
 *
 * @author Kay Strobach <typo3@kay-strobach.de>
 * @package TYPO3
 * @subpackage t3lib
 */
Ext.ns('TYPO3.Tceforms.Polling');

TYPO3.Tceforms.Polling = {
		// Contains records and tables in the form
	tables: new Ext.util.MixedCollection(),
		// Contains records edited by some else, where we had a warning
	alreadyWarned: new Ext.util.MixedCollection(),
		//RegExp to find fields which are related to a record
	tableRecordExpression: new RegExp('data\\[(.*)\\]\\[(.*)\\]\\[(.*)\\](.*)'),
		//periodic task
	task: null,
		//First steps
	init: function() {
		this.findRecordsInForm();
		this.poll();
		this.enablePolling();
	},
		// Parses tceforms to identify the related records
	findRecordsInForm: function() {
			// Get all formfields
		var formElements = Ext.select('select, input');
			// Find openend records
		formElements.each(
			function(el, allEl, idx) {
				formElName = el.getAttribute('name');
					// Filter tceform fields - should not test useless fields ;)
				if(formElName.substr(0,4)=='data') {
					this.tableRecordExpression.test(formElName);
						// RegExp.$1 contains table name
						// RegExp.$2 contains recordnumber
						// RegExp.$3 contains fieldname
						// RegExp.$4 contains additional stuff
					this.tables.add(RegExp.$1 + '-' + RegExp.$2,0);
				}
			},
			this
		);
	},
		// Polling handler
	poll: function() {
		this.tables.eachKey(
			function(index, item) {
				var message;
				var win;
				parts = index.split('-');
				TYPO3.Components.TceForms.Commands.getRecordLastchange(
					parts[0],
					parts[1],
					function(result) {
						key = result.table + '-' + result.uid;
							// Add first timestamp if it's unknown
						if(this.tables.key(key) == 0) {
							this.tables.add(key, result.tstamp);
						} else {
							if(this.tables.key(key) != result.tstamp) {
								this.tables.add(key, result.tstamp);
								message = '<p>'
										+ TYPO3.l10n.localize('tceform.recordChangedDetailed', {1: result.tableTranslated, 2: result.title, 3:result.uid})
										+ '</p><p>'
										+ TYPO3.l10n.localize('tceform.recordChangedMsg') + '<a href="' + window.location.href +'" target="_blank">' + TYPO3.l10n.localize('tceform.recordChangedMsg2') + '</a>'
										+ '</p>';
								Ext.Msg.show(
									{
										title: TYPO3.l10n.localize('tceform.recordChanged'),
										msg: message,
										icon: Ext.MessageBox.WARNING,
										width: 500,
										buttons: {
											ok: TYPO3.l10n.localize('tceform.reloadform'),
											cancel: TYPO3.l10n.localize('tceform.checkoutdifferences')
										},
										fn: function(btn) {
											if(btn == 'ok') {
												document.location.reload();
											} else {
													// Reenable polling for manual merge
												this.enablePolling();
												win = window.open(window.location.href);
													// Check if window was opened
												if(win==null || typeof(win)) {
													TYPO3.Flashmessage.display(
														3,
														TYPO3.l10n.localize('tceform.popupNotificationTitle'),
														TYPO3.l10n.localize('tceform.popupNotificationMessage')
														+ ' <a href="' + window.location.href + '" target="_blank">' + TYPO3.l10n.localize('tceform.popupNotificationLink') + '</a>'
													)
												}
											}
										},
										scope:this
									}
								);
									// Change found - disable polling to reduce server load
								this.disablePolling();
							}
						}
					},
					this
				);
			},
			this
		);
	},
		// Initialize and / or start the polling task
	enablePolling: function() {
		if(this.task != null) {
			Ext.TaskMgr.stop(this.task);
		}
		if(this.tables.getCount() >= 1) {
			this.task = Ext.TaskMgr.start({
				run: this.poll,
				interval: 10000,
				scope: this
			});
		};
	},
		//Stop the polling task
	disablePolling: function() {
		Ext.TaskMgr.stop(this.task);
	}
};

Ext.onReady(function() {
	TYPO3.Tceforms.Polling.init();
});