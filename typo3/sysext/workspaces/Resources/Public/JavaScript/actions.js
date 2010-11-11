TYPO3.Workspaces.Actions = {

	runningMassAction: null,
	triggerMassAction: function(action) {

		switch (action) {
			case 'publish':
			case 'swap':
				this.runningMassAction = TYPO3.Workspaces.ExtDirectMassActions.publishWorkspace;
				break;
			case 'release':
				this.runningMassAction = TYPO3.Workspaces.ExtDirectMassActions.flushWorkspace;
				break;
		}

		this.runMassAction({
			init: true,
			total:0,
			processed:0,
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
		var label = parameters.total > 0 ? parameters.processed + '/' + parameters.total : 'init';
		top.Ext.getCmp('executeMassActionProgressBar').updateProgress(progress, label, true);

		this.runningMassAction(parameters, TYPO3.Workspaces.Actions.runMassActionCallback);
	},

	runMassActionCallback: function(response) {
		if (response.error) {
			top.Ext.getCmp('executeMassActionProgressBar').hide();
			top.Ext.getCmp('executeMassActionOkButton').hide();
			top.Ext.getCmp('executeMassActionCancleButton').setText('Close');
			top.Ext.getCmp('executeMassActionForm').show();
			top.Ext.getCmp('executeMassActionForm').update(response.error);
		} else {
			if (response.total > response.processed) {
				TYPO3.Workspaces.Actions.runMassAction(response);
			} else {
				top.Ext.getCmp('executeMassActionProgressBar').hide();
				top.Ext.getCmp('executeMassActionOkButton').hide();
				top.Ext.getCmp('executeMassActionCancleButton').setText('Close');
				top.Ext.getCmp('executeMassActionForm').show();
				top.Ext.getCmp('executeMassActionForm').update('Done processing ' + response.total + ' elements');
			}
		}
	},
	generateWorkspacePreviewLink: function() {
		TYPO3.Workspaces.ExtDirectActions.generateWorkspacePreviewLink(TYPO3.settings.Workspaces.id, function(response) {
			top.TYPO3.Dialog.getInformationDialog({title: 'Preview link', msg: response});
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
	viewSingleRecord: function(pid) {
		TYPO3.Workspaces.ExtDirectActions.viewSingleRecord(pid, function(response) {
			eval(response);
		});
	},
	sendToNextStageWindow: function(table, uid, t3ver_oid) {
		TYPO3.Workspaces.ExtDirectActions.sendToNextStageWindow(table, uid, t3ver_oid, function(response) {
			if (Ext.isObject(response.error)) {
				TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
			} else {
				var dialog = TYPO3.Workspaces.Helpers.getSendToStageWindow({
					title: response.title,
					items: response.items,
					executeHandler: function(event) {
						var values = top.Ext.getCmp('sendToStageForm').getForm().getValues();

						var parameters = {
							affects: response.affects,
							receipients: TYPO3.Workspaces.Helpers.getElementIdsFromFormValues(values, 'receipients'),
							additional: values.additional,
							comments: values.comments
						};

						TYPO3.Workspaces.Actions.sendToNextStageExecute(parameters);
						top.TYPO3.Windows.close('sendToStageWindow');
						TYPO3.Workspaces.MainStore.reload();
					}
				});
			}
		});
	},
	sendToPrevStageWindow: function(table, uid, t3ver_oid) {
		TYPO3.Workspaces.ExtDirectActions.sendToPrevStageWindow(table, uid, function(response) {
			if (Ext.isObject(response.error)) {
				TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
			} else {
				var dialog = TYPO3.Workspaces.Helpers.getSendToStageWindow({
					title: response.title,
					items: response.items,
					executeHandler: function(event) {
						var values = top.Ext.getCmp('sendToStageForm').getForm().getValues();

						var parameters = {
							affects: response.affects,
							receipients: TYPO3.Workspaces.Helpers.getElementIdsFromFormValues(values, 'receipients'),
							additional: values.additional,
							comments: values.comments
						};

						TYPO3.Workspaces.Actions.sendToPrevStageExecute(parameters);
						top.TYPO3.Windows.close('sendToStageWindow');
						TYPO3.Workspaces.MainStore.reload();
					}
				});
			}
		});
	},
	sendToSpecificStageWindow: function(selection, nextStage) {
		TYPO3.Workspaces.ExtDirectActions.sendToSpecificStageWindow(nextStage, function(response) {
			if (Ext.isObject(response.error)) {
				TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction(response);
			} else {
				var dialog = TYPO3.Workspaces.Helpers.getSendToStageWindow({
					title: response.title,
					items: response.items,
					executeHandler: function(event) {
						var values = top.Ext.getCmp('sendToStageForm').getForm().getValues();

						var parameters = {
							affects: {
								nextStage: response.affects.nextStage,
								elements: TYPO3.Workspaces.Helpers.getElementsArrayOfSelection(selection)
							},
							receipients: TYPO3.Workspaces.Helpers.getElementIdsFromFormValues(values, 'receipients'),
							additional: values.additional,
							comments: values.comments
						};

						TYPO3.Workspaces.Actions.sendToSpecificStageExecute(parameters);
						top.TYPO3.Windows.close('sendToStageWindow');
						TYPO3.Workspaces.MainStore.reload();
					}
				});
			}
		});
	},
	sendToNextStageExecute: function (parameters) {
		TYPO3.Workspaces.ExtDirectActions.sendToNextStageExecute(parameters, TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction);
	},
	sendToPrevStageExecute: function (parameters) {
		TYPO3.Workspaces.ExtDirectActions.sendToPrevStageExecute(parameters, TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction);
	},
	sendToSpecificStageExecute: function (parameters) {
		TYPO3.Workspaces.ExtDirectActions.sendToSpecificStageExecute(parameters, TYPO3.Workspaces.Actions.handlerResponseOnExecuteAction);
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
			response = { error: { message: 'The server did not send any response whether the action was successful.' }};
		}

		if (Ext.isObject(response.error)) {
			var error = response.error;
			var code = (error.code ? ' #' + error.code : '');
			top.TYPO3.Dialog.ErrorDialog({ title: 'Error' + code, msg: error.message });
		}
	}
};