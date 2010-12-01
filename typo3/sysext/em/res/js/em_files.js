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
	//border: false,
	height: 400,

	recordData: null,
	isWindow: false,

	initComponent:function() {

		var editArea = new Ext.form.TextArea({
			itemId: 'editarea',
			ctCls: 'editareapanel',
			enableKeyEvents: true,
			listeners: {
				change: function() {
					//Ext.getComponent('editarea-save').enable();
				},
				keypress: function(textfield, event) {
					event.stopPropagation();
				},
				specialkey: function(textfield, event) {
					event.stopPropagation();
				},
				scope: this
			}

		});


		var fileTree = new Ext.tree.TreePanel ({
			//directFn: TYPO3.EM.ExtDirect.getExtFileTree,
			itemId: 'extfiletree',
			autoScroll: true,
			containerScroll: true,
			margins: '0 0 0 0',
			cmargins: '0 0 0 0',
			//useArrows: true,

			root: {
				text: 'Extension Files',
				itemId: 'fileroot',
				expanded: true
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
				click: function(node) {  console.log(node.attributes.fileType);
					var p = this.getComponent('editarea');
					if (node.attributes.fileType === 'text') {
						this.layout.center.panel.reloadButton.show().disable();
						TYPO3.EM.ExtDirect.readExtFile(node.attributes.id , function(response) {
							// load in textarea
							editArea.setValue(response);
							this.layout.center.panel.reloadButton.enable();
							this.layout.center.panel.fileLabel.setText('File: ' + node.attributes.text);
							this.layout.center.panel.saveButton.disable();
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
				}, '-', {
					iconCls: 'x-btn-upload',
					tooltip: 'upload'
				}, {
					iconCls: 'x-btn-download',
					tooltip: 'download'
				}]
			}, {

				 region: 'center',

				 layout: 'fit',
				 margins: '0 0 0 0',
				 cmargins: '0 0 0 0',
				 border: false,
				 items: [editArea],
				 tbar: [{
				 	iconCls: 'x-btn-filebrowser',
					tooltip: 'open in new window',
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
					tooltip: 'reload the file',
					ref: '../reloadButton',
					scope: this,
					hidden: true,
					handler: function() {
						this.layout.center.panel.reloadButton.disable();
						TYPO3.EM.ExtDirect.readExtFile(fileTree.getSelectionModel().getSelectedNode().attributes.id , function(response) {
							editArea.setValue(response);
							this.layout.center.panel.reloadButton.enable();
						}, this);
					}
				}, {
					iconCls: 'x-btn-save',
					tooltip: 'save',
					ref: '../saveButton',
					disabled: true,
					scope: this,
					handler: function() {
						this.layout.center.panel.reloadButton.disable();
						TYPO3.EM.ExtDirect.saveExtFile(
							Ext.getComponent('extfiletree').getSelectionModel().getSelectedNode().attributes.id,
							Ext.getComponent('editarea').getValue(),
							function(response) {
								this.layout.center.panel.saveButton.disable();
								this.layout.center.panel.reloadButton.enable();
						}, this);
					}
				}, '->', {
					xtype: 'tbtext',
					ref: '../fileLabel',
					itemId: 'editarea-filename',
					text: 'click on a file to load in editor ...'
				}]
			}]
		});

		TYPO3.EM.ExtFilelist.superclass.initComponent.apply(this, arguments);

	},

	fileClick: function(response) {
		Ext.getComponent('editarea').setValue(response);
	},

	onRender: function() {
		TYPO3.EM.ExtFilelist.superclass.onRender.apply(this, arguments);
	}



});

// register xtype
Ext.reg('extfilelist', TYPO3.EM.ExtFilelist);
