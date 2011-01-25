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

TYPO3.EM.ExtFilelist = Ext.extend(Ext.Panel, {
	recordData: null,
	isWindow: false,

	initComponent:function() {

		var hlEditor = new TYPO3.EM.CodeMirror({
			parser: 'mixed',
			itemId: 'hlEditor',
			stylesheet: TYPO3.settings.EM.editorCss,
			editFile: null
		});




		var fileTree = new Ext.tree.TreePanel ({
			itemId: 'extfiletree',
			cls: 'extfiletree',
			margins: '0 0 0 0',
			cmargins: '0 0 0 0',

			root: {
				text: TYPO3.lang.ext_details_ext_files,
				itemId: 'fileroot',
				expanded: true,
				icon: 'sysext/t3skin/icons/module_tools_em.png'
			},
			loader: {
				directFn: TYPO3.EM.ExtDirect.getExtFileTree,
				baseParams: {
					extkey: this.recordData.extkey,
					typeShort: this.recordData.typeShort,
					baseNode: this.recordData.nodePath
				},
				paramsAsHash: true
			},
			listeners: {
				click: function(node) {
					if (node.attributes.fileType === 'text') {
						this.layout.center.panel.reloadButton.show().disable();
						TYPO3.EM.ExtDirect.readExtFile(node.attributes.id , function(response) {
							// load in textarea
							hlEditor.openText(response, node.attributes.ext);
							hlEditor.editFile = node.attributes.id;
							this.layout.center.panel.reloadButton.enable();
							this.layout.center.panel.fileLabel.setText('File: ' + hlEditor.editFile);
							this.layout.center.panel.saveButton.disable();
							this.layout.center.panel.undoButton.enable();
							this.layout.center.panel.redoButton.enable();
							this.layout.center.panel.indentButton.enable();
							if (node.attributes.ext == 'js') {
								this.layout.center.panel.jslintButton.enable();
							} else {
								this.layout.center.panel.jslintButton.disable();
							}

						}, this);
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
				items: [fileTree],
				tbar: [{
					iconCls: 'x-tbar-loading',
					handler: function() {
						fileTree.getRootNode().reload();
					},
					scope: this
				}, {
					iconCls: 'x-btn-upload',
					tooltip: TYPO3.lang.cmd_upload,
					hidden: true
				}, {
					iconCls: 'x-btn-download',
					tooltip: TYPO3.lang.cmd_download,
					hidden: true
				}]
			}, {

				region: 'center',

				layout: 'fit',
				margins: '0 0 0 0',
				cmargins: '0 0 0 0',
				border: false,
				cls: 'file-editor',
				items: [hlEditor],
				tbar: [{
					iconCls: 'x-btn-filebrowser',
					tooltip: TYPO3.lang.cmd_openInNewWindow,
					ref: '../openWindowButton',
					scope: this,
					hidden: this.isWindow,
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
						if (hlEditor.editFile) {
							this.layout.center.panel.reloadButton.disable();
							TYPO3.EM.ExtDirect.readExtFile(hlEditor.editFile , function(response) {
								hlEditor.setValue(response);
								this.layout.center.panel.reloadButton.enable();
							}, this);
						}
					}
				}, {
					iconCls: 'x-btn-save',
					tooltip: TYPO3.settings.EM.fileSaveAllowed ? TYPO3.lang.cmd_save : TYPO3.lang.ext_details_saving_disabled,
					ref: '../saveButton',
					disabled: true,
					scope: this,
					handler: function() {
						this.layout.center.panel.reloadButton.disable();
						var file = this.layout.west.items[0].getSelectionModel().getSelectedNode().attributes.id;
						TYPO3.EM.ExtDirect.saveExtFile(
							file,
							hlEditor.getValue(),
							function(response) {
								if (response.success) {
									TYPO3.Flashmessage.display(TYPO3.Severity.ok, TYPO3.lang.cmd_save, String.format(TYPO3.lang.msg_fileSaved, response.file), 5);
									this.layout.center.panel.saveButton.disable();
									this.layout.center.panel.reloadButton.enable();
								} else {
									TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.lang.cmd_save, response.error, 5);
								}
						}, this);
					}
				},
				{
					iconCls: 'x-btn-undo',
					tooltip: TYPO3.lang.cmd_undo,
					ref: '../undoButton',
					disabled: true,
					scope: this,
					handler: function() {
						hlEditor.codeMirrorEditor.undo();
					}
				},
				{
					iconCls: 'x-btn-redo',
					tooltip: TYPO3.lang.cmd_redo,
					ref: '../redoButton',
					disabled: true,
					scope: this,
					handler: function() {
						hlEditor.codeMirrorEditor.redo();
					}
				},
				{
					iconCls: 'x-btn-indent',
					tooltip: TYPO3.lang.cmd_indent,
					ref: '../indentButton',
					disabled: true,
					scope: this,
					handler: function() {
						hlEditor.codeMirrorEditor.reindent();
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

							var oStore = hlEditor.debugWindow.findByType('grid')[0].getStore();
							if (!bValidates) {
								var aErrorData = [];

								for (var err in JSLINT.errors) {
									if (JSLINT.errors.hasOwnProperty(err) && (JSLINT.errors[err] !== null)) {
										aErrorData.push([JSLINT.errors[err].line, JSLINT.errors[err].character, JSLINT.errors[err].reason]);
									}
								}

								oStore.loadData(aErrorData, false);
								hlEditor.debugWindow.show();

							}
							else {

								oStore.loadData([
									[1, 1, TYPO3.lang.msg_congratsNoErrors]
								], false);
								hlEditor.debugWindow.show();
							}
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
	}



});

// register xtype
Ext.reg('extfilelist', TYPO3.EM.ExtFilelist);


TYPO3.EM.CodeMirrorConfig = {
	cssPath: TYPO3.settings.EM.codemirrorCssPath,
	jsPath: TYPO3.settings.EM.codemirrorJsPath,
	parser: {
		defo: { // js code
			parserfile: ["tokenizejavascript.js", "parsejavascript.js"],
			stylesheet: [TYPO3.settings.EM.codemirrorCssPath + "jscolors.css"]
		},
		css: {
			parserfile: ["parsecss.js"],
			stylesheet: [TYPO3.settings.EM.codemirrorCssPath + "csscolors.css"]
		},
		js: {
			parserfile: ["tokenizejavascript.js", "parsejavascript.js"],
			stylesheet: [TYPO3.settings.EM.codemirrorCssPath + "jscolors.css"]
		},
		php: {
			parserfile: ["../contrib/php/js/tokenizephp.js", "../contrib/php/js/parsephp.js"],
			stylesheet: [TYPO3.settings.EM.codemirrorContribPath + "php/css/phpcolors.css"]
		},
		html: {
			parserfile: ["parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js", "../contrib/php/js/tokenizephp.js", "../contrib/php/js/parsephp.js", "../contrib/php/js/parsephphtmlmixed.js"],
			stylesheet: [
				TYPO3.settings.EM.codemirrorCssPath + "xmlcolors.css",
				TYPO3.settings.EM.codemirrorCssPath + "jscolors.css",
				TYPO3.settings.EM.codemirrorCssPath + "csscolors.css",
				TYPO3.settings.EM.codemirrorContribPath + "php/css/phpcolors.css"]

		},
		mixed: {
			parserfile: ["parsexml.js", "parsecss.js", "tokenizejavascript.js", "parsejavascript.js", "../contrib/php/js/tokenizephp.js", "../contrib/php/js/parsephp.js", "../contrib/php/js/parsephphtmlmixed.js"],
			stylesheet: [
				TYPO3.settings.EM.codemirrorCssPath + "xmlcolors.css",
				TYPO3.settings.EM.codemirrorCssPath + "jscolors.css",
				TYPO3.settings.EM.codemirrorCssPath + "csscolors.css",
				TYPO3.settings.EM.codemirrorContribPath + "php/css/phpcolors.css"
			]
		}
	}
};


TYPO3.EM.CodeMirror = Ext.extend(Ext.Panel, {
	layout: 'fit',
	sourceCode: '',
	stylesheet: null,
	initComponent: function() {
			// add custom stylesheet to all parser
		if (this.stylesheet) {
			Ext.iterate(TYPO3.EM.CodeMirrorConfig.parser, function(key, value) {
				value.stylesheet.push(this.stylesheet);
			}, this);
		}

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
				listeners: {
					rowclick: function(grid) {
						var oData = grid.getSelectionModel().getSelected().data;
						me.codeMirrorEditor.jumpToLine(oData.line);
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
						width: 60,
						sortable: true,
						dataIndex: 'line'
					},
					{
						id: 'character',
						header: TYPO3.lang.msg_character,
						width: 60,
						sortable: true,
						dataIndex: 'character'
					},
					{
						header: TYPO3.lang.show_description,
						width: 240,
						sortable: true,
						dataIndex: 'reason'
					}
				],
				stripeRows: true
			})]
		});

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
		var oCmp = this.findByType('textarea')[0];
		this.editorConfig = Ext.applyIf(this.codeMirror || {}, {
			lineNumbers: true,
			textWrapping: false,
			content: oCmp.getValue(),
			indentUnit: 4,
			tabMode: 'shift',
			readOnly: oCmp.readOnly,
			path: TYPO3.EM.CodeMirrorConfig.jsPath,
			autoMatchParens: true,
			initCallback: function(editor) {
				try {
					var iLineNmbr = ((Ext.state.Manager.get("edcmr_" + me.itemId + '_lnmbr') !== undefined) ? Ext.state.Manager.get("edcmr_" + me.itemId + '_lnmbr') : 1);
					editor.jumpToLine(iLineNmbr);
				} catch(e) {
				}
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
		this.editorConfig = Ext.applyIf(this.editorConfig, TYPO3.EM.CodeMirrorConfig.parser[sParserType]);
		this.codeMirrorEditor = new CodeMirror.fromTextArea(Ext.getDom(oCmp.id).id, this.editorConfig);

	},

	changeAction: function(changed) {
		if (TYPO3.settings.EM.fileSaveAllowed) {
			if (!changed) {
				this.ownerCt.saveButton.disable();
				this.contentChanged = false;
			} else {
				this.ownerCt.saveButton.enable();
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

	openText: function(text, parser) {
		this.codeMirrorEditor.setCode(text);
	}

});
Ext.reg('TYPO3.EM.CodeMirror', TYPO3.EM.CodeMirror);