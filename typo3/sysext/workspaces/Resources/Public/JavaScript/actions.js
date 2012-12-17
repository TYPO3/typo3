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

TYPO3.Workspaces.Actions = {

	runningMassAction: null,
	currentSendToMode: 'next',

	checkIntegrity: function(parameters, callbackFunction, callbackArguments) {
		TYPO3.Workspaces.ExtDirect.checkIntegrity(
				parameters,
				function (response) {
					switch (response.result) {
						case 'error':
							top.TYPO3.Dialog.ErrorDialog({
								minWidth: 400,
								title: 'Error',
								msg: '<div class="scope">' + TYPO3.l10n.localize('integrity.hasIssuesDescription') + '</div>'
							});
							break;
						case 'warning':
							top.TYPO3.Dialog.QuestionDialog({
								minWidth: 400,
								title: 'Warning',
								msg: '<div class="scope">' + TYPO3.l10n.localize('integrity.hasIssuesDescription') + '</div>' +
									'<div class="question">' + TYPO3.l10n.localize('integrity.hasIssuesQuestion') + '</div>',
								fn: function(result) {
									if (result == 'yes') {
										callbackFunction.call(this, callbackArguments)
									}
								}
							});
							break;
						default:
							callbackFunction.call(this, callbackArguments);
					}
				}
		)
	},

	triggerMassAction: function(action, language) {
		switch (action) {
			case 'publish':
			case 'swap':
				this.runningMassAction = TYPO3.Workspaces.ExtDirectMassActions.publishWorkspace;
				break;
			case 'discard':
				this.runningMassAction = TYPO3.Workspaces.ExtDirectMassActions.flushWorkspace;
				break;
		}

		// Publishing large amount of changes may require a longer timeout
		Ext.Ajax.timeout = 3600000;

		this.runMassAction({
			init: true,
			total:0,
			processed:0,
			language: language,
			swap: (action == 'swap')
		});
	},

	runMassAction: function(parameters) {
		if (parameters.init) {
			top.Ext.getCmp('executeMassActionForm').hide();
			top.Ext.getCmp('executeMassActionProgressBar').show();
			top.Ext.getCmp('executeMassActionOkButton').disable();
		}

		var progress = parameters.total > 0 ? parameters.processed / parameters.total : 0;
		var label = parameters.total > 0 ? parameters.processed + '/' + parameters.total : TYPO3.l10n.localize('runMassAction.init');
		top.Ext.getCmp('executeMassActionProgressBar').updateProgress(progress, label, true);

		this.runningMassAction(parameters, TYPO3.Workspaces.Actions.runMassActionCallback);
	},

	runMassActionCallback: function(response) {
		if (response.error) {
			top.Ext.getCmp('executeMassActionProgressBar').hide();
			top.Ext.getCmp('executeMassActionOkButton').hide();
			top.Ext.getCmp('executeMassActionCancleButton').setText(TYPO3.l10n.localize('close'));
			top.Ext.getCmp('executeMassActionForm').show();
			top.Ext.getCmp('executeMassActionForm').update(response.error);
		} else {
			if (response.total > response.processed) {
				TYPO3.Workspaces.Actions.runMassAction(response);
			} else {
				top.Ext.getCmp('executeMassActionProgressBar').hide();
				top.Ext.getCmp('executeMassActionOkButton').hide();
				top.Ext.getCmp('executeMassActionCancleButton').setText(TYPO3.l10n.localize('close'));
				top.Ext.getCmp('executeMassActionForm').show();
				top.Ext.getCmp('executeMassActionForm').update(TYPO3.l10n.localize('runMassAction.done').replace('%d', response.total));
				top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
			}
		}
	},
	generateWorkspacePreviewLink: function() {
		TYPO3.Workspaces.ExtDirectActions.generateWorkspacePreviewLink(TYPO3.settings.Workspaces.id, function(response) {
			top.TYPO3.Dialog.InformationDialog({
				title: TYPO3.l10n.localize('previewLink'),
				msg: String.format('<a href="{0}" target="_blank">{0}</a>', response)
			});
		});
	},
	swapSingleRecord: function(table, t3ver_oid, orig_uid) {
		TYPO3.Workspaces.ExtDirectActions.swapSingleRecord(table, t3ver_oid, orig_uid, function(response) {
			TYPO3.Workspaces.MainStore.load();
		});
	},
	deleteSingleRecord: function(table, uid) {
		TYPO3.Workspaces.ExtDirectActions.deleteSingleRecord(table, uid, function(response) {
			TYPO3.Workspaces.MainStore.load();
		});
	},
	viewSingleRecord: function(table, uid) {
		TYPO3.Workspaces.ExtDirectActions.viewSingleRecord(table, uid, function(response) {
			eval(response);
		});
	},
	sendToStageWindow: function(response, selection) {
		if (Ext.isObject(response.error)) {
			TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
		} else {
			var dialog = TYPO3.Workspaces.Helpers.getSendToStageWindow({
				title: response.title,
				items: response.items,
				executeHandler: function(event) {
					var values = top.Ext.getCmp('sendToStageForm').getForm().getValues();
					affects = response.affects;
					affects.elements = TYPO3.Workspaces.Helpers.getElementsArrayOfSelection(selection);
					var parameters = {
						affects: affects,
						receipients: TYPO3.Workspaces.Helpers.getElementIdsFromFormValues(values, 'receipients'),
						additional: values.additional,
						comments: values.comments
					};

					TYPO3.Workspaces.Actions.sendToStageExecute(parameters);
					top.TYPO3.Windows.close('sendToStageWindow');
					TYPO3.Workspaces.MainStore.reload();
					top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
				}
			});
		}
	},
	sendToNextStageWindow: function(table, uid, t3ver_oid) {
		TYPO3.Workspaces.ExtDirectActions.sendToNextStageWindow(uid, table, t3ver_oid, function(response) {
			TYPO3.Workspaces.Actions.currentSendToMode = 'next';
			TYPO3.Workspaces.Actions.sendToStageWindow(response);
		});
	},
	sendToPrevStageWindow: function(table, uid) {
		TYPO3.Workspaces.ExtDirectActions.sendToPrevStageWindow(uid, table, function(response) {
			TYPO3.Workspaces.Actions.currentSendToMode = 'prev';
			TYPO3.Workspaces.Actions.sendToStageWindow(response);
		});
	},
	sendToSpecificStageWindow: function(selection, nextStage) {
		TYPO3.Workspaces.ExtDirectActions.sendToSpecificStageWindow(nextStage, function(response) {
			TYPO3.Workspaces.Actions.currentSendToMode = 'specific';
			TYPO3.Workspaces.Actions.sendToStageWindow(response, selection);
		});
	},
	sendToStageExecute: function (parameters) {
		switch (TYPO3.Workspaces.Actions.currentSendToMode) {
			case 'next':
				TYPO3.Workspaces.ExtDirectActions.sendToNextStageExecute(parameters, TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction);
			break;
			case 'prev':
				TYPO3.Workspaces.ExtDirectActions.sendToPrevStageExecute(parameters, TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction);
			break;
			case 'specific':
				TYPO3.Workspaces.ExtDirectActions.sendToSpecificStageExecute(parameters, TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction);
			break;
		}

	},
	updateColModel: function(colModel) {
		var dataArray = [];
		for (var i = 0; i < colModel.config.length; i++) {
			if (colModel.config[i].dataIndex !== '') {
				dataArray.push({
					'position': i,
					'column': colModel.config[i].dataIndex,
					'hidden': colModel.config[i].hidden ? 1 : 0
				});
			}
		}
		TYPO3.Workspaces.ExtDirectActions.saveColumnModel(dataArray);
	},
	loadColModel: function(grid) {
		TYPO3.Workspaces.ExtDirectActions.loadColumnModel(function(response) {
			var colModel = grid.getColumnModel();
			for (var field in response) {
				var colIndex = colModel.getIndexById(field);
				if (colIndex != -1) {
					colModel.setHidden(colModel.getIndexById(field), (response[field].hidden == 1 ? true : false));
					colModel.moveColumn(colModel.getIndexById(field), response[field].position);
				}
			}
		});
	},
	handlerResponseOnExecuteAction: function(response) {
		if (!Ext.isObject(response)) {
			response = { error: { message: TYPO3.l10n.localize('error.noResponse') }};
		}

		if (Ext.isObject(response.error)) {
			var error = response.error;
			var code = (error.code ? ' #' + error.code : '');
			top.TYPO3.Dialog.ErrorDialog({ title: 'Error' + code, msg: error.message });
		}
	},

	/**
	 * Process "send to next stage" action.
	 *
	 * This method is used in the split frontend preview part.
	 *
	 * @return void
	 *
	 * @author Michael Klapper <development@morphodo.com>
	 */
	sendPageToNextStage: function () {
		TYPO3.Workspaces.ExtDirectActions.sendPageToNextStage(TYPO3.settings.Workspaces.id, function (response) {
			if (Ext.isObject(response.error)) {
				TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
			} else {
				var dialog = TYPO3.Workspaces.Helpers.getSendToStageWindow({
					title: TYPO3.l10n.localize('nextStage'),
					items: response.items.items,
					executeHandler: function(event) {
						var values = top.Ext.getCmp('sendToStageForm').getForm().getValues();
						affects = response.affects;
						var parameters = {
							affects: affects,
							receipients: TYPO3.Workspaces.Helpers.getElementIdsFromFormValues(values, 'receipients'),
							additional: values.additional,
							comments: values.comments,
							stageId: response.stageId
						};
						TYPO3.Workspaces.ExtDirectActions.sentCollectionToStage(parameters, function (response) {
							TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
							TYPO3.Workspaces.ExtDirectActions.updateStageChangeButtons(TYPO3.settings.Workspaces.id, TYPO3.Workspaces.Actions.updateStageChangeButtons);

							if (response.refreshLivePanel == true) {
								Ext.getCmp('livePanel').refresh();
								Ext.getCmp('livePanel-hbox').refresh();
								Ext.getCmp('livePanel-vbox').refresh();
							}
						});
						top.TYPO3.Windows.close('sendToStageWindow');
					}
				});
			}
		});
	},

	/**
	 * Process "send to previous stage" action.
	 *
	 * This method is used in the split frontend preview part.
	 *
	 * @return void
	 *
	 * @author Michael Klapper <development@morphodo.com>
	 */
	sendPageToPrevStage: function () {
		TYPO3.Workspaces.ExtDirectActions.sendPageToPreviousStage(TYPO3.settings.Workspaces.id, function (response) {
			if (Ext.isObject(response.error)) {
				TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
			} else {
				var dialog = TYPO3.Workspaces.Helpers.getSendToStageWindow({
					title: TYPO3.l10n.localize('nextStage'),
					items: response.items.items,
					executeHandler: function(event) {
						var values = top.Ext.getCmp('sendToStageForm').getForm().getValues();

						affects = response.affects;
						var parameters = {
							affects: affects,
							receipients: TYPO3.Workspaces.Helpers.getElementIdsFromFormValues(values, 'receipients'),
							additional: values.additional,
							comments: values.comments,
							stageId: response.stageId
						};
						TYPO3.Workspaces.ExtDirectActions.sentCollectionToStage(parameters, function (response) {
							TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
							TYPO3.Workspaces.ExtDirectActions.updateStageChangeButtons(TYPO3.settings.Workspaces.id, TYPO3.Workspaces.Actions.updateStageChangeButtons);
						});
						top.TYPO3.Windows.close('sendToStageWindow');
					}
				});
			}
		});
	},

	/**
	 * Update the visible state for the buttons "next stage", "prev stage" and "discard".
	 *
	 * This method is used in the split frontend preview part.
	 *
	 * @param object response
	 * @return void
	 *
	 * @author Michael Klapper <development@morphodo.com>
	 */
	updateStageChangeButtons: function (response) {

		if (Ext.isObject(response.error)) {
				TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
		} else {
			for (componentId in response) {
				if (response[componentId].visible) {
					if (!top.Ext.getCmp(componentId).isVisible()) {
						top.Ext.getCmp(componentId).show();
					}
					top.Ext.getCmp(componentId).setText(response[componentId].text.substr(0, 35));
					top.Ext.getCmp(componentId).setTooltip(response[componentId].text);
				} else {
					if (top.Ext.getCmp(componentId).isVisible()) {
						top.Ext.getCmp(componentId).hide();
					}
				}
			}
				// force doLayout on each plugin containing the preview panel
			Ext.getCmp('preview').plugins.each(function (item, index) {
				if (Ext.isFunction(item.doLayout)) {
					item.doLayout();
				}
			});
		}
	},

	/**
	 * Process the discard all items from current page action.
	 *
	 * This method is used in the split frontend preview part.
	 *
	 * @return void
	 *
	 * @author Michael Klapper <development@morphodo.com>
	 */
	discardPage: function () {
		var configuration = {
			title: TYPO3.l10n.localize('window.discardAll.title'),
			msg: TYPO3.l10n.localize('window.discardAll.message'),
			fn: function(result) {
				if (result == 'yes') {
					TYPO3.Workspaces.ExtDirectActions.discardStagesFromPage(TYPO3.settings.Workspaces.id, function (response) {
						TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
						TYPO3.Workspaces.ExtDirectActions.updateStageChangeButtons(TYPO3.settings.Workspaces.id, TYPO3.Workspaces.Actions.updateStageChangeButtons);
						Ext.getCmp('wsPanel').refresh();
						Ext.getCmp('wsPanel-hbox').refresh();
						Ext.getCmp('wsPanel-vbox').refresh();
					});
				}
			}
		};

		top.TYPO3.Dialog.QuestionDialog(configuration);
	}
};
