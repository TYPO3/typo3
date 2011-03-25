/**
 * ExtJS for the extension manager.
 *
 *
 * @author Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage extension manager
 */

Ext.ns('TYPO3.EM');

TYPO3.EM.ExtFilelist = Ext.extend(Ext.Panel, {
	recordData: null,
	isWindow: false,
	loaderUrl: null,
	rootIcon: 'sysext/t3skin/icons/module_tools_em.png',
	rootText: TYPO3.lang.ext_details_ext_files,
	baseParams: null,
	treeId: null,
	fileContent: '',
	parser: 'PHPHTMLMixedParser',

	allowedOperations: {
		moveFile: TYPO3.settings.EM.fileAllowMove,
		deleteFile: TYPO3.settings.EM.fileAllowDelete,
		renameFile: TYPO3.settings.EM.fileAllowRename,
		uploadFile: TYPO3.settings.EM.fileAllowUpload,
		createFile: TYPO3.settings.EM.fileAllowCreate,
		downloadFile: TYPO3.settings.EM.fileAllowDownload
	},

	initComponent:function() {

		this.highlightEditor = new TYPO3.EM.CodeMirror({
			itemId: 'this.highlightEditor',
			fileContent: this.fileContent,
			parser: this.parser,
			stylesheet: TYPO3.settings.EM.editorCss
		});




		this.fileTree = new Ext.tree.TreePanel ({
			itemId: 'extfiletree',
			cls: 'extfiletree',
			margins: '0 0 0 0',
			cmargins: '0 0 0 0',
			id: this.treeId ? this.treeId : Ext.id(),
			stateful: this.treeId ? true : false,
			stateEvents: [],
			plugins: new Ext.ux.state.TreePanel(),

			enableDD: this.allowedOperations.moveFile,
			ddAppendOnly: true,
			copyAction: false,

			root: {
				text: this.rootText,
				itemId: 'fileroot',
				expanded: true,
				icon: this.rootIcon
			},
			loader: {
				directFn: this.loaderUrl || TYPO3.EM.ExtDirect.getExtFileTree,
				baseParams: this.baseParams ? this.baseParams : {
					extkey: this.recordData.extkey,
					typeShort: this.recordData.typeShort,
					baseNode: this.recordData.nodePath
				},
				paramsAsHash: true
			},
			listeners: {
				click: function(node) {
					this.clickNode(node);
				},

			/* Drag and Drop of file nodes */
			nodedragover: function (dragevent) {
				// allow only drop on dirs and exclude parent
				return (!dragevent.target.leaf && (dragevent.target.id !== dragevent.dropNode.parentNode.id));
			},
			beforenodedrop : function(dropEvent) {
				var action = dropEvent.rawEvent.ctrlKey ? 'copy' : 'move';
				dropEvent.tree.dragZone.proxy.animRepair = false;
				dropEvent.cancel = true;
				var question = this.copyAction ? TYPO3.lang.fileEditCopyConfirmation : TYPO3.lang.fileEditMoveConfirmation;

				Ext.Msg.confirm(TYPO3.lang.fileEditOperation, String.format(question, dropEvent.dropNode.text, dropEvent.target.text), function(button) {
					if (button == 'yes') {
						TYPO3.EM.ExtDirect.moveFile(dropEvent.dropNode.id, dropEvent.target.id, !dropEvent.dropNode.leaf, function(response) {
							if (response.success) {
								dropEvent.tree.dragZone.proxy.animRepair = true;
								if (!dropEvent.target.leaf) {
									dropEvent.target.expand();
								}
								switch (dropEvent.point) {
									case "append":
									case "ap-pend":
										dropEvent.target.appendChild(dropEvent.dropNode);
										break;
									case "above":
										dropEvent.target.parentNode.insertBefore(dropEvent.dropNode, dropEvent.target);
										break;
									case "below":
										dropEvent.target.parentNode.insertBefore(dropEvent.dropNode, dropEvent.target.nextSibling);
										break;
								}
								dropEvent.dropNode.ui.focus();
								dropEvent.dropNode.ui.highlight();

							} else {

							}
						});
					}
				});
			},

				scope: this
			}

		});

		Ext.apply(this, {

			layout: 'border',
			items: [{
				region: 'west',
				layout: 'fit',
				split: true,
				width: 260,
				collapsible: true,
				collapseMode: 'mini',
				cls: 'filetree-panel',
				hideCollapseTool: true,
				items: [this.fileTree],
				tbar: [{
					iconCls: 'x-tbar-loading',
					tooltip: TYPO3.lang.fileEditReloadFiletree,
					handler: function() {
						this.fileTree.getRootNode().reload();
					},
					scope: this
				}, {
					iconCls: 'x-btn-upload',
					tooltip: TYPO3.lang.cmd_upload,
					ref: '../uploadFileButton',
					hidden: !this.allowedOperations.uploadFile
				}, '-', {
					iconCls: 't3-icon t3-icon-actions t3-icon-actions-system t3-icon-system-extension-download',
					tooltip: TYPO3.lang.cmd_download,
					ref: '../downloadFileButton',
					disabled: true,
					hidden: !this.allowedOperations.downloadFile,
					handler: function() {
						var node = this.fileTree.getSelectionModel().getSelectedNode();
						if (node.isLeaf()) {
							this.downloadFile(node.attributes.id);
						}
					},
					scope: this
				}, {
					iconCls: 't3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-new',
					tooltip: TYPO3.lang.fileEditCreateFileFolder,
					ref: '../createFileButton',
					disabled: true,
					hidden: !this.allowedOperations.createFile,
					scope: this,
					handler: function() {
						var folderNode = this.fileTree.getSelectionModel().getSelectedNode();
						this.fileCreationDialog(folderNode, function() {
							//this.createNewFile(folderNode, text);
						});
					}
				}, {
					iconCls: 't3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-rename',
					tooltip: TYPO3.lang.fileEditRename,
					ref: '../renameButton',
					scope: this,
					hidden: !this.allowedOperations.renameFile,
					handler: function() {
						var node = this.fileTree.getSelectionModel().getSelectedNode();
						var isFolder = !node.isLeaf();
						Ext.Msg.prompt(TYPO3.lang.fileEditRename, '', function(btn, text) {
							if (btn == 'ok' && text != node.text) {
								TYPO3.EM.ExtDirect.renameFile(node.attributes.id, text, isFolder, function(response) {
									if (response.success) {
										node.setId(response.newFile);
										node.setText(response.newFilename);
										node.ui.focus();
										node.ui.highlight();
									}
								}, this);
							}
						}, this, false, node.text);
					}
				}, {
					iconCls: 't3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-delete',
					tooltip: TYPO3.lang.ext_details_delete,
					ref: '../deleteButton',
					hidden: !this.allowedOperations.deleteFile,
					scope: this,
					handler: function() {
						var node = this.fileTree.getSelectionModel().getSelectedNode();
						var isFolder = !node.isLeaf();
						Ext.Msg.confirm(TYPO3.lang.ext_details_delete, '', function(btn, text) {
							if (btn == 'yes') {
								TYPO3.EM.ExtDirect.deleteFile(node.attributes.id, isFolder, function(response) {
									if (response.success) {
										node.remove();
									}
								}, this);
							}
						}, this, false, node.text);
					}
				}]
			}, {

				region: 'center',

				layout: 'fit',
				margins: '0 0 0 0',
				cmargins: '0 0 0 0',
				border: false,
				cls: 'file-editor',
				items: [this.highlightEditor],
				tbar: [{
					iconCls: 'x-btn-filebrowser',
					tooltip: TYPO3.lang.cmd_openInNewWindow,
					ref: '../openWindowButton',
					scope: this,
					hidden: this.isWindow || this.noWindowOpen,
					handler: function() {

						var newEditor = new Ext.Window({
							title: this.recordData.title + ' (' + this.recordData.extkey + ')',
							width: 600,
							height: 400,
							layout: 'fit',
							maximizable: true,
							collapsible: true,
							items: [{
								xtype: 'extfilelist',
								minHeight: 400,
								recordData: this.recordData,
								isWindow: true
							}]
						}).show();
					}
				 }, {
					 xtype: 'tbseparator',
					 hidden: this.isWindow
				 }, {
					iconCls: 'x-tbar-loading',
					tooltip: TYPO3.lang.cmd_reloadFile,
					ref: '../reloadButton',
					scope: this,
					hidden: true,
					handler: function() {
						if (this.highlightEditor.editFile) {
							this.layout.center.panel.reloadButton.disable();
							TYPO3.EM.ExtDirect.readExtFile(this.highlightEditor.editFile , function(response) {
								this.highlightEditor.setValue(response);
								this.layout.center.panel.reloadButton.enable();
							}, this);
						}
					}
				}, {
					iconCls: 'x-btn-save',
					tooltip: TYPO3.settings.EM.fileAllowSave ? TYPO3.lang.cmd_save : TYPO3.lang.ext_details_saving_disabled,
					ref: '../saveButton',
					disabled: true,
					scope: this,
					handler: function() {
						this.saveFile(this.highlightEditor.editFile);
					}

				}, '-', {
					iconCls: 't3-icon t3-icon-status t3-icon-status-version t3-icon-version-no-version',
						tooltip: 'Show diff',
						ref: '../diffButton',
						disabled: true,
						scope: this,
						handler: this.showDiff
				},
				{
					iconCls: 'x-btn-undo',
					tooltip: TYPO3.lang.cmd_undo,
					ref: '../undoButton',
					disabled: true,
					scope: this,
					handler: function() {
						this.highlightEditor.codeMirrorEditor.undo();
					}
				},
				{
					iconCls: 'x-btn-redo',
					tooltip: TYPO3.lang.cmd_redo,
					ref: '../redoButton',
					disabled: true,
					scope: this,
					handler: function() {
						this.highlightEditor.codeMirrorEditor.redo();
					}
				},
				{
					iconCls: 'x-btn-indent',
					tooltip: TYPO3.lang.cmd_indent,
					ref: '../indentButton',
					disabled: true,
					scope: this,
					handler: function() {
						this.highlightEditor.codeMirrorEditor.reindent();
					}
				},
				{
					iconCls: 'x-btn-jslint',
					tooltip: TYPO3.lang.cmd_jslint,
					ref: '../jslintButton',
					disabled: true,
					scope: this,
					handler: function() {
						try {
							var bValidates = JSLINT(this.findByType('textarea')[0].getValue());
							var oStore = this.highlightEditor.debugWindow.findByType('grid')[0].getStore();
							if (!bValidates) {
								var aErrorData = [];
								for (var err in JSLINT.errors) {
									if (JSLINT.errors.hasOwnProperty(err) && (JSLINT.errors[err] !== null)) {
										aErrorData.push([JSLINT.errors[err].line, JSLINT.errors[err].character, JSLINT.errors[err].reason]);
									}
								}
								oStore.loadData(aErrorData, false);
							} else {
								oStore.loadData([
									[1, 1, TYPO3.lang.msg_congratsNoErrors]
								], false);
							}
							this.highlightEditor.debugWindow.show();
						} catch(e) {
						}
					}
				},
				'->',
				{
					xtype: 'tbtext',
					ref: '../fileLabel',
					itemId: 'editarea-filename',
					text: TYPO3.lang.help_loadFileInEditor
				}]
			}]
		});

		TYPO3.EM.ExtFilelist.superclass.initComponent.apply(this, arguments);

	},

	onRender: function() {
		TYPO3.EM.ExtFilelist.superclass.onRender.apply(this, arguments);
	},


	clickNode: function(node, doSelect) {
		if (doSelect) {
			this.fileTree.getSelectionModel().select(node);
		}
		if (node.attributes.fileType === 'text') {
			var file = node.attributes.id;
			if (this.highlightEditor.contentChanged) {
				 Ext.MessageBox.confirm(TYPO3.lang.fileEditFileChanged, TYPO3.lang.fileEditFileChangedSavePrompt, function(btn){
					if (btn == 'yes'){
						this.saveFile(this.highlightEditor.editFile, function() {
							this.loadFile(node);
						});

					}
				}, this);
			} else {
				this.loadFile(node);
			}

		}
		if (node.attributes.fileType === 'image') {
			var w = new Ext.Window({
				width: 200,
				height: 200,
				title: node.attributes.text,
				layout: 'fit',
				items: [{
					xtype: 'image',
					src: TYPO3.settings.EM.siteUrl + node.attributes.id,
					autoSize: true,
					resizable: false,
					renderTo: document.body
				}]
			}).show();
		}
		if (node.isLeaf()) {
			this.layout.west.panel.downloadFileButton.enable();
			this.layout.west.panel.createFileButton.disable();
		} else {
			this.layout.west.panel.downloadFileButton.disable();
			this.layout.west.panel.createFileButton.enable();
		}
	},

	loadFile: function(node) {
		this.layout.center.panel.reloadButton.show().disable();
		TYPO3.EM.ExtDirect.readExtFile(node.attributes.id , function(response) {
			// load in textarea
			var centerPanel = this.layout.center.panel;
			this.highlightEditor.openText(response, node.attributes.ext);
			this.highlightEditor.editFile = node.attributes.id;
			this.highlightEditor.contentChanged = false;
			centerPanel.reloadButton.enable();
			centerPanel.fileLabel.setText('File: ' + this.highlightEditor.editFile);
			centerPanel.fileLabel.removeClass('fileChanged');
			centerPanel.saveButton.disable();
			centerPanel.undoButton.enable();
			centerPanel.redoButton.enable();
			centerPanel.indentButton.enable();
			centerPanel.diffButton.disable();
			if (node.attributes.ext == 'js') {
				centerPanel.jslintButton.enable();
			} else {
				centerPanel.jslintButton.disable();
			}

		}, this);
	},

	saveFile: function(file, cb) {
		var panel = this.layout.center.panel;
		var content = this.highlightEditor.getValue();

		panel.reloadButton.disable();
		TYPO3.EM.ExtDirect.saveExtFile(
			file,
			content,
			function(response) {
				if (response.success) {
					TYPO3.Flashmessage.display(TYPO3.Severity.ok, TYPO3.lang.cmd_save, String.format(TYPO3.lang.msg_fileSaved, response.file), 5);
					this.highlightEditor.contentChanged = false;
					this.layout.center.panel.fileLabel.removeClass('fileChanged');
				} else {
					TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.cmd_save, response.error, 5);
				}
				if (Ext.isFunction(cb)) {
					cb.call(this);
				}
		}, this);

		panel.saveButton.disable();
		panel.reloadButton.enable();

	},

	moveFile: function(file, destination) {

	},

	showDiff: function() {
		var original =  this.highlightEditor.oldSourceCode;
		var content = this.highlightEditor.codeMirrorEditor.getCode();
		TYPO3.EM.ExtDirect.makeDiff(original, content, function(response) {
			TYPO3.Windows.showWindow({
				title: 'Diff view',
				resizable: true,
				html: '<div style="background:#fff;padding: 5px;overflow:auto;">' + response.diff + '</div>'
			});
		});
	},

	downloadFile: function(path) {

		// create hidden target iframe
		var id = Ext.id();
		var frame = document.createElement('iframe');
		frame.id = id;
		frame.name = id;
		frame.className = 'x-hidden';
		if (Ext.isIE) {
			frame.src = Ext.SSL_SECURE_URL;
		}

		document.body.appendChild(frame);

		if (Ext.isIE) {
			document.frames[id].name = id;
		}

		var form = Ext.DomHelper.append(document.body, {
					tag: 'form',
					method: 'post',
					action: 'mod.php?M=tools_em',
					target: id
				});

		document.body.appendChild(form);

		var hidden;

		// append path to form
		hidden = document.createElement('input');
		hidden.type = 'hidden';
		hidden.name = 'CMD[downloadExtFile]';
		hidden.value = path;
		form.appendChild(hidden);

		var callback = function() {
			Ext.EventManager.removeListener(frame, 'load', callback, this);
			setTimeout(function() {
				document.body.removeChild(form);
			}, 100);
			setTimeout(function() {
				document.body.removeChild(frame);
			}, 110);
		};

		Ext.EventManager.on(frame, 'load', callback, this);

		form.submit();
	},

	fileCreationDialog: function(folderNode) {
		new Ext.Window({
			id: 'em-files-createfilefolderdialog',
			title: TYPO3.lang.fileEditCreateFileFolder,
			layout: 'form',
			callerClass: this,
			items: [
				{
					fieldLabel:  TYPO3.lang.fileEditNewFilePrompt,
					itemId: 'newfile',
					xtype: 'textfield',
					width: 250
				},
				{
					fieldLabel: TYPO3.lang.fileEditCreateFolder,
					xtype: 'checkbox',
					itemId: 'isFolder'
				}
			],
			buttons: [{
				text: TYPO3.lang.cmd_create,
				handler: function() {
					var me = Ext.WindowMgr.get('em-files-createfilefolderdialog');
					var newfile = me.getComponent('newfile').getValue();
					var isFolder = me.getComponent('isFolder').getValue();
					TYPO3.EM.ExtDirect.createNewFile(folderNode.attributes.id, newfile, isFolder, function(response) {
						if (response.success) {
							var newNode = null, delay;
							if (!folderNode.isExpanded()) {
								folderNode.expand();
								delay = 750;
							} else {
								newNode = new Ext.tree.TreeNode(
									response.node
								);
								folderNode.appendChild(newNode);
								delay = 250;
							}
							(function() {
								if (!newNode) {
									newNode = folderNode.findChild('id', response.node.id);
								}
								newNode.ui.focus();
								newNode.ui.highlight();
								me.callerClass.clickNode(newNode, true);
							}).defer(delay);
						}
					}, this);
					me.close();
				},
				scope: this
			}, {
				text: TYPO3.lang.cmd_cancel,
				handler: function() {
					var me = TYPO3.Windows.getById('em-files-createfilefolderdialog');
					me.close();
				}
			}]
		}).show();
	}



});

// register xtype
Ext.reg('extfilelist', TYPO3.EM.ExtFilelist);


TYPO3.EM.CodeMirror = Ext.extend(Ext.Panel, {
	layout: 'fit',
	sourceCode: '',
	stylesheet: null,
	fileLoaded: false,
	fileContent: '',

	initComponent: function() {
			// add custom stylesheet to all parser

		this.contentChanged = false;
		var me = this;
		this.debugWindow = new Ext.Window({
			title: TYPO3.lang.msg_debug,
			width: 500,
			layout: 'border',
			closeAction: 'hide',
			height: 160,
			items: [new Ext.grid.GridPanel({
				layout: 'fit',
				region: 'center',
				border: false,
				viewConfig: {
					forceFit: true
				},
				listeners: {
					rowclick: function(grid) {
						var oData = grid.getSelectionModel().getSelected().data;
						me.codeMirrorEditor.jumpToLine(oData.line);
						var pos = me.codeMirrorEditor.cursorPosition(true);
						me.codeMirrorEditor.selectLines(pos.line, 0, pos.line, oData.character - 1);
					}
				},
				store: new Ext.data.ArrayStore({
					fields: [
						{name: 'line'},
						{name: 'character'},
						{name: 'reason'}
					]
				}),
				columns: [
					{
						id: 'line',
						header: TYPO3.lang.msg_line,
						width: 40,
						fixed: true,
						menuDisabled: true,
						dataIndex: 'line'
					},
					{
						id: 'character',
						header: TYPO3.lang.msg_character,
						width: 70,
						fixed: true,
						menuDisabled: true,
						dataIndex: 'character'
					},
					{
						header: TYPO3.lang.show_description,
						menuDisabled: true,
						dataIndex: 'reason'
					}
				],
				stripeRows: true
			})]
		});

		me.addEvents('init');

		Ext.apply(this, {
			items: [
				{
					xtype: 'textarea',
					readOnly: false,
					hidden: true,
					value: this.sourceCode
				}
			]
		});

		TYPO3.EM.CodeMirror.superclass.initComponent.apply(this, arguments);
	},

	triggerOnSave: function() {
		this.changeAction();
		var sNewCode = this.codeMirrorEditor.getCode();
		this.oldSourceCode = sNewCode;
		this.onSave(arguments[0] || false);
	},

	onRender: function() {
		this.oldSourceCode = this.sourceCode;
		TYPO3.EM.CodeMirror.superclass.onRender.apply(this, arguments);
			// trigger editor on afterlayout
		this.on('afterlayout', this.triggerCodeEditor, this, {
			single: true
		});
		this.on('resize', this.resizeCodeEditor, this);

	},

	/** @private */
	resizeCodeEditor: function(component, width, height, origWidth, origHeight) {
		var el = Ext.fly(this.codeMirrorEditor.frame);
		el.setSize(width - 50, height); // subtract width of line numbers
		el.next().setHeight(height);
		this.doLayout();
	},

	/** @private */
	triggerCodeEditor: function() {
		var me = this;
		var stylesheets = [
			TYPO3.settings.EM.codemirrorCssPath + "xmlcolors.css",
			TYPO3.settings.EM.codemirrorCssPath + "jscolors.css",
			TYPO3.settings.EM.codemirrorCssPath + "csscolors.css",
			TYPO3.settings.EM.codemirrorContribPath + "php/css/phpcolors.css",
			TYPO3.settings.EM.codemirrorContribPath + "sql/css/sqlcolors.css"
		];
		if (this.stylesheet) {
			stylesheets.push(this.stylesheet);
		}
		var oCmp = this.findByType('textarea')[0];

		this.editorConfig = Ext.applyIf(this.codeMirror || {}, {
			lineNumbers: true,
			textWrapping: false,
			content: this.fileContent || '',
			indentUnit: 4,
			tabMode: 'shift',
			readOnly: oCmp.readOnly,
			path: TYPO3.settings.EM.codemirrorJsPath,
			autoMatchParens: true,
			parser: this.parser,
			parserfile: [
				"parsexml.js",
				"parsecss.js",
				"tokenizejavascript.js",
				"parsejavascript.js",
				"../contrib/php/js/tokenizephp.js",
				"../contrib/php/js/parsephp.js",
				"../contrib/php/js/parsephphtmlmixed.js",
				"../contrib/sql/js/parsesql.js"
			],
			stylesheet: stylesheets,

			initCallback: function(editor) {
				me.fireEvent('init', editor);
			},

			onChange: function() {
				var sCode = me.codeMirrorEditor.getCode();
				oCmp.setValue(sCode);

				if (me.oldSourceCode == sCode) {
					me.changeAction(false);
				} else {
					me.changeAction(true);
				}
			}
		});

		var sParserType = me.parser || 'defo';
		this.codeMirrorEditor = new CodeMirror.fromTextArea(Ext.getDom(oCmp.id).id, this.editorConfig);

	},

	changeAction: function(changed) {
		if (TYPO3.settings.EM.fileAllowSave && this.fileLoaded) {
			if (!changed) {
				this.ownerCt.saveButton.disable();
				this.ownerCt.diffButton.disable();
				this.ownerCt.fileLabel.removeClass('fileChanged');
				this.contentChanged = false;
			} else {
				this.ownerCt.saveButton.enable();
				this.ownerCt.diffButton.enable();
				this.ownerCt.fileLabel.addClass('fileChanged');
				this.contentChanged = true;
			}
		}
	},

	getValue: function() {
		return this.codeMirrorEditor.getCode();
	},

	setValue: function(text) {
		this.codeMirrorEditor.setCode(text);
		this.resizeCodeEditor();
	},

	setValueAtCursor: function(text) {
		var cursorPosition = this.codeMirrorEditor.cursorPosition();
		var handleForCursorLine = this.codeMirrorEditor.cursorLine();
		this.codeMirrorEditor.insertIntoLine(handleForCursorLine, cursorPosition.character, text);
	},

	openText: function(text, ext) {
		var parserName = this.getParserFromExtension(ext);
		this.codeMirrorEditor.setParser(parserName);
		this.codeMirrorEditor.setCode(text);
		this.oldSourceCode = text;
		this.fileLoaded = true;
	},

	getParserFromExtension: function(ext) {
		var parser = '';
		switch(ext.toLowerCase()) {
			case 'js':
			case 'json':
				parser = 'JSParser';
			break;
			case 'css':
				parser = 'CSSParser';
			break;
			case 'php':
			case 'php3':
			case 'php4':
			case 'php5':
			case 'php6':
			case 'phpsh':
			case 'inc':
			case 'html':
			case 'htm':
			case 'tmpl':
			case 'phtml':
				parser = 'PHPHTMLMixedParser';
			break;
			case 'sql':
				parser = 'SqlParser';
			break;
			case 'xml':
			default:
				parser = 'XMLParser';
		}
		return parser;
	}

});
Ext.reg('TYPO3.EM.CodeMirror', TYPO3.EM.CodeMirror);