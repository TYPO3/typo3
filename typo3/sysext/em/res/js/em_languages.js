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

/** override mousedown for grid to select checkbox respecting singleSelect */
Ext.override(Ext.grid.CheckboxSelectionModel, {
	handleMouseDown: function(g, rowIndex, e) {
		e.stopEvent();
		if (this.isSelected(rowIndex)) {
			this.deselectRow(rowIndex);
		} else {
			this.selectRow(rowIndex, true);
			this.grid.getView().focusRow(rowIndex);
		}
	}
});

Ext.grid.DynamicColumnModelForLanguages = function(store){
	var cols = [];
	var recordType = store.recordType;
	var fields = recordType.prototype.fields;

	for (var i = 0; i < fields.keys.length; i++) {
		var fieldName = fields.keys[i];
		var field = recordType.getField(fieldName);

		if (i === 0) {
			cols[i] = {
				header: 'Extension',
				dataIndex: field.name,
				width: 200,
				fixed: true,
				sortable: false,
				hidable: false,
				menuDisabled: true,
				renderer: function(value, metaData, record, rowIndex, colIndex, store){
					metaData.css += ' extLangTitleWithIcon';
					return record.data.icon + ' <strong>' + value + '</strong>';
				}
			};
		} else if (i === 1 || i === 2 || i === 3) {
			//bypass
		} else {
			cols[i - 3] = {
				header: field.name,
				dataIndex: field.name,
				hidden: true,
				fixed: true,
				sortable: false,
				hidable: false,
				menuDisabled: true,
				renderer: function(value, metaData, record, rowIndex, colIndex, store) {
					if (value == TYPO3.lang.translation_checking) {
						return '<span class="x-mask-loading">&nbsp;</span>' + value;
					}
					return '<span class="x-mask-loading">&nbsp;</span>' + value;;
				}
			};

		}
	}
	Ext.grid.DynamicColumnModelForLanguages.superclass.constructor.call(this, cols);
};
Ext.extend(Ext.grid.DynamicColumnModelForLanguages, Ext.grid.ColumnModel, {defaultWidth: 170});


TYPO3.EM.LanguagesSelectionModel  = new Ext.grid.CheckboxSelectionModel({
	singleSelect: false,
	header: '',
	dataIndex: 'selected',
	checkOnly: false

});

TYPO3.EM.LanguagesColumnModel = new Ext.grid.ColumnModel([
	TYPO3.EM.LanguagesSelectionModel, {
		id: 'lang-label',
		header: TYPO3.lang.lang_language,
		sortable: true,
		menuDisabled: true,
		dataIndex: 'label',
		hidable: false,
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			return '<span class="' + record.data.cls + '">&nbsp</span>' + value;
		}
	},{
		id: 'lang-key',
		header: TYPO3.lang.lang_short,
		menuDisabled: true,
		sortable: true,
		dataIndex: 'lang',
		hidable: false
	}
]);

TYPO3.EM.LanguagesProgressBar = new Ext.ProgressBar ({
	id:  'langpb',
	cls: 'left-align',
	autoWidth: true,
	style: 'margin: 0 0 20px 0',
	animate: true,
	height: 20,
	hidden: true
});



TYPO3.EM.Languages = Ext.extend(Ext.FormPanel, {
	border:false,
	layout: 'form',
	id: 'em-labguage-modul',
	extCount: 0,
	fetchType: 0,
	extkeyArray : [],
	selectedLanguages: [],
	cb: null,

	initComponent: function() {
		var langExtStore = new Ext.data.DirectStore({
			storeId     : 'em-languageext-store',
			autoLoad	: false,
			directFn	: TYPO3.EM.ExtDirect.getInstalledExtkeys,
			root		: 'data',
			idProperty  : 'extkey',
			fields : [{name : 'extkey'},{name : 'icon'},{name: 'stype'}],
			listeners : {
				'load': function(store, records, options){
					if(records.length) {
						Ext.getCmp('lang-checkbutton').enable();
						Ext.getCmp('lang-updatebutton').enable();
						this.restoreExtLanguageGrid();
					}
					this.languageLoaded = true;
				},
				scope : this
			}

		});

		this.langStore = new Ext.data.DirectStore({
			storeId     : 'em-language-store',
			autoLoad	: false,
			directFn	: TYPO3.EM.ExtDirect.getLanguages,
			paramsAsHash: false,
			root		: 'data',
			idProperty  : 'lang',
			fields : [
				{name : 'lang', type : 'string'},
				{name : 'label', type : 'string'},
				{name : 'cls', type : 'string'},
				{name : 'selected', type: 'bool'}
			],
			listeners : {
				'load': function(store, records){
						// get selected languages and update selection and extGrid
					TYPO3.settings.LangLoaded = false;
					var a = [];
					for (var i=0; i<records.length; i++) {
						if(records[i].data.selected) {
							a.push(records[i]);
						}
					}
					TYPO3.EM.LanguagesSelectionModel.selectRecords(a);
					langExtStore.load();
					store.sort('label', 'ASC');
				},
				scope: this
			}
		});


		Ext.apply(this, {
			languagesLoaded: false,
			layout:'hbox',
			bodyStyle: 'padding: 10px 5px 0 5px;',
			layoutConfig: {
				align: 'stretch'
			},
			defaults: {
				border: false
			},
			items: [{
				width: 250,
				layout: 'fit',
				items: [{
					xtype: 'grid',
					id: 'em-languagegrid',
					stripeRows: true,
					store: this.langStore,
					cm: TYPO3.EM.LanguagesColumnModel,
					sm: TYPO3.EM.LanguagesSelectionModel,
					enableColumnMove: false,
					onRowClick: Ext.emptyFn,
					viewConfig: {
						forceFit: true
					}
				}]
			}, {
				flex: 1,
				layout: 'fit',
				items: [{
					xtype:'fieldset',
					//title: TYPO3.lang.translation_status,
					collapsible: false,
					items: [
						TYPO3.EM.LanguagesActionPanel,
						{
							xtype: 'container',
							layout: 'hbox',
							height: 40,
							id: 'LanguagesActionPanel',
							layoutConfig: {
								align: 'stretch'
							},
							defaults: {
								border:false,
								flex: 1
							},
							items: [{
								xtype: 'button',
								text: TYPO3.lang.translation_check_status_button,
								id: 'lang-checkbutton',
								margins: '0 10 10 0'
							}, {
								xtype: 'button',
								text: TYPO3.lang.translation_update_button,
								id: 'lang-updatebutton',
								margins: '0 0 10 10'
							}]
						},
						TYPO3.EM.LanguagesProgressBar,
						{
					    	xtype: 'grid',
							id: 'em-extlanguagegrid',
							stripeRows: true,
							store: langExtStore,
							loadMask: {msg: TYPO3.lang.translation_refresh_languages},
							enableColumnMove: false,
							enableHdMenu : false,
							autoheight: true,
							cm: new Ext.grid.DynamicColumnModelForLanguages(langExtStore),
							margins: '0 10 0 0',
							anchor: '100% -40',
							listeners: {
								render: this.onExtensionLangguageGridRender
							}
						}]
					}]
				}]
		});

			// call parent
		TYPO3.EM.Languages.superclass.initComponent.apply(this, arguments);
		this.langGrid = Ext.getCmp('em-languagegrid');
		this.langGrid.getSelectionModel().on('selectionchange', function(){
			this.langGrid.disable();
			this.saveSelection();
		}, this);
		Ext.getCmp('lang-checkbutton').handler = this.langActionHandler.createDelegate(this);
		Ext.getCmp('lang-updatebutton').handler = this.langActionHandler.createDelegate(this);
	} ,

	onExtensionLangguageGridRender: function(grid) {
		grid.fetchingProcess = false;
		this.on('cellclick', function(grid, rowIndex, columnIndex, event) {
			if (!grid.fetchingProcess && columnIndex > 0) {
				var record = grid.store.getAt(rowIndex);
				var lang = grid.colModel.config[columnIndex].dataIndex;
				Ext.Msg.confirm(
					TYPO3.lang.menu_language_packges,
					String.format(TYPO3.lang.translation_singleCheckQuestion, lang, '<strong>' + record.data.extkey + '</strong>'),
					function(btn) {
						if (btn === 'yes') {
							this.waitBox = Ext.Msg.wait(
								String.format(TYPO3.lang.translation_singleCheck, lang, '<strong>' + record.data.extkey + '</strong>'),
								TYPO3.lang.translation_checking
							);
							TYPO3.EM.ExtDirect.fetchTranslations(record.data.extkey, 1, [lang], function(response) {
								record.set(lang, response[lang]);
								record.commit();
								this.waitBox.hide()
							}, this);
						}
					},
					this
				);
			}
		}, this);
	},

	langActionHandler: function(button, event) {
		var languagegrid = Ext.getCmp('em-languagegrid');
		var buttonPanel = Ext.getCmp('LanguagesActionPanel');
		var progressBar = Ext.getCmp('langpb');
		var grid = Ext.getCmp('em-extlanguagegrid');

		buttonPanel.hide();
		progressBar.show();
	    languagegrid.disable();


		if (button.id === 'lang-checkbutton') {
				// check languages
			this.startFetchLanguages(0, Ext.StoreMgr.get('em-languageext-store'), function(){
				TYPO3.EM.LanguagesProgressBar.updateText(this.interruptProcess ? TYPO3.lang.msg_interrupted : TYPO3.lang.msg_finished);
				(function() {
					progressBar.hide();
					buttonPanel.show();
					languagegrid.enable();
					grid.fetchingProcess = false;
				}).defer(1000, this);
				if (!this.interruptProcess) {
					TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.translation_checking_extension, TYPO3.lang.translation_check_done, 3);
					Ext.getCmp('em-extlanguagegrid').getSelectionModel().clearSelections();
				}
			});
		} else {
				// update languages
			this.startFetchLanguages(1, Ext.StoreMgr.get('em-languageext-store'), function(){
				TYPO3.EM.LanguagesProgressBar.updateText(this.interruptProcess ? TYPO3.lang.msg_interrupted : TYPO3.lang.msg_finished);
				if (!this.interruptProcess) {
					TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.translation_update_extension, TYPO3.lang.translation_update_done, 3);
					Ext.getCmp('em-extlanguagegrid').getSelectionModel().clearSelections();
				}
				progressBar.hide();
				buttonPanel.show();
				languagegrid.enable();
				grid.fetchingProcess = false;
			});
		}
	},

	getSelectedLanguages: function() {
		var selLanguages = this.langGrid.getSelectionModel().getSelections();
		this.selectedLanguages = [];
		if (selLanguages.length > 0 ) {
			for (var i=0; i<selLanguages.length; i++) {
				this.selectedLanguages.push(selLanguages[i].data.lang);
			}
		}
	},

	saveSelection: function() {
		if (this.languageLoaded === true) {
			this.getSelectedLanguages();
			TYPO3.EM.ExtDirect.saveLanguageSelection(this.selectedLanguages, function(response) {
				record = this.langStore.getById(response.diff);
				this.addRemoveExtLanguageGridColumn(record.data);
			},this);
			if (this.selectedLanguages.length) {
				Ext.getCmp('lang-checkbutton').enable();
				Ext.getCmp('lang-updatebutton').enable();
			} else {
				Ext.getCmp('lang-checkbutton').disable();
				Ext.getCmp('lang-updatebutton').disable();
			}
		}
	},

	startFetchLanguages: function(type, store, callback) {
		this.fetchType = type;
		this.extCount = store.data.items.length;
		this.cb = callback;


			// fill arrays
		this.extkeyArray = [];
		for (var i = 0; i < this.extCount; i++) {
			this.extkeyArray.push(store.data.items[i].data.extkey);
		}
		if (!this.selectedLanguages.length) {
			this.getSelectedLanguages();
		}
		// start process
		this.interruptProcess = false;
		Ext.getCmp('em-extlanguagegrid').fetchingProcess = true;
		this.fetchLanguage();
	},

	fetchLanguage: function(response) {
		var grid = Ext.getCmp('em-extlanguagegrid');
		var row = this.extCount - this.extkeyArray.length;
		var record = grid.store.getAt(row);
		var i;


		if (response) {
			// update fetched record
			var fetchedRecord = grid.store.getAt(row - 1);
			for (i = 0; i < this.selectedLanguages.length; i++) {
				fetchedRecord.set(this.selectedLanguages[i], response[this.selectedLanguages[i]]);
    		}
    		fetchedRecord.commit();
		}

		if(this.extkeyArray.length > 0 && !this.interruptProcess) {
			var ext = this.extkeyArray.shift();


			//update Grid
			grid.getView().focusRow(row);
			grid.getSelectionModel().selectRow(row);
			for (i = 0; i < this.selectedLanguages.length; i++) {
				record.set(this.selectedLanguages[i], '<span class="loading-indicator"></span>' + TYPO3.lang.translation_checking);
			}
			record.commit();
			var prefix = TYPO3.lang.msg_checking;
			if (this.fetchType === 1) {
				prefix = TYPO3.lang.msg_updating;
			}
			// update Progressbar
			Ext.getCmp('langpb').updateProgress(
				(row + 1) / this.extCount,
				prefix+ ': ' +
					String.format(TYPO3.lang.translation_fetch_extension, ext, (row+1), this.extCount));

			// fetch language request
			TYPO3.EM.ExtDirect.fetchTranslations(ext, this.fetchType, this.selectedLanguages, function(response) {
				this.fetchLanguage(response);
			}, this);
		} else {
				// finished
			Ext.getCmp('lang-checkbutton').enable();
			Ext.getCmp('lang-updatebutton').enable();
			Ext.getCmp('em-extlanguagegrid').getSelectionModel().clearSelections();
			// call callback
			this.cb();
		}
	},


	restoreExtLanguageGrid: function() {

		var extLangGrid = Ext.getCmp('em-extlanguagegrid');
		var i;

		var selLanguages = Ext.getCmp('em-languagegrid').getSelectionModel().getSelections();
		var columns = extLangGrid.getColumnModel();
		var count = columns.getColumnCount();

		if (selLanguages.length > 0 ) {
			for (i=0; i < selLanguages.length; i++) {
				this.addRemoveExtLanguageGridColumn(selLanguages[i].data);
			}
		}
	},

	addRemoveExtLanguageGridColumn: function(record) {
		var extLangGrid = Ext.getCmp('em-extlanguagegrid');
		var columns = extLangGrid.getColumnModel();
		var index = columns.getIndexById(record.lang);

		if (index === -1) {
			extLangGrid.addColumn({
				name: record.lang,
				defaultValue: TYPO3.lang.translation_status_notchecked
			}, {
				header: '<span class="' + record.cls + '">&nbsp</span>' + record.label,
				dataIndex: record.lang,
				id: record.lang,
				css: 'cursor:pointer;',
				tooltip: TYPO3.lang.translation_singleCheckTip
			});
		} else {
			columns.removeColumn(index);
		}
		this.langGrid.enable();
	},

	afterRender: function() {
			// call parent
		TYPO3.EM.Languages.superclass.afterRender.apply(this, arguments);
			//The following are all of the possible keys that can be implemented: enter, left, right, up, down, tab, esc, pageUp, pageDown, del, home, end
		this.progressNavigation = new Ext.KeyNav(this.getEl(),{
			'esc': function() {
				this.interruptProcess = true;
			},
			scope: this
		});


	}
});
Ext.reg('extlanguages', TYPO3.EM.Languages);
