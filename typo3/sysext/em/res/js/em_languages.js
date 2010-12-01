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

TYPO3.EM.LanguagesSelectionModel  = new Ext.grid.CheckboxSelectionModel({
	singleSelect: false,
	header: '',
	dataIndex: 'selected'
});

TYPO3.EM.LanguagesColumnModel = new Ext.grid.ColumnModel([
	TYPO3.EM.LanguagesSelectionModel, {
		id: 'lang-label',
		header: TYPO3.lang.lang_language,
		sortable: true,
		menuDisabled: true,
		fixed: true,
		dataIndex: 'label',
		width: 150,
		hidable: false,
		renderer: function(value, metaData, record, rowIndex, colIndex, store) {
			return '<span class="' + record.data.cls + '">&nbsp</span>' + value;
		}
	},{
		id: 'lang-key',
		header: TYPO3.lang.lang_short,
		menuDisabled: true,
		fixed: true,
		sortable: true,
		dataIndex: 'lang',
		hidable: false
	}
]);

TYPO3.EM.LanguagesProgressBar = new Ext.ProgressBar ({
	id:  'langpb',
	cls: 'left-align',
	autoWidth: true,
	style: 'margin: 10px',
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
			//fields : [{name : 'extkey'},{name : 'icon'},{name : 'lang'},{name: 'Danish'}, {name: 'German'}, {name: 'Norwegian'}, {name: 'Italian'}, {name: 'French'}, {name: 'Spanish'}, {name: 'Dutch'}, {name: 'Czech'}, {name: 'Polish'}, {name: 'Slovenian'}, {name: 'Finnish'}, {name: 'Turkish'}, {name: 'Swedish'}, {name: 'Portuguese'}, {name: 'Russian'}, {name: 'Romanian'}, {name: 'Chinese (Simpl)'}, {name: 'Slovak'}, {name: 'Lithuanian'}, {name: 'Icelandic'}, {name: 'Croatian'}, {name: 'Hungarian'}, {name: 'Greenlandic'}, {name: 'Thai'}, {name: 'Greek'}, {name: 'Chinese (Trad)'}, {name: 'Basque'}, {name: 'Bulgarian'}, {name: 'Brazilian Portuguese'}, {name: 'Estonian'}, {name: 'Arabic'}, {name: 'Hebrew'}, {name: 'Ukrainian'}, {name: 'Latvian'}, {name: 'Japanese'}, {name: 'Vietnamese'}, {name: 'Catalan'}, {name: 'Bosnian'}, {name: 'Korean'}, {name: 'Esperanto'}, {name: 'Bahasa Malaysia'}, {name: 'Hindi'}, {name: 'Faroese'}, {name: 'Persian'}, {name: 'Serbian'}, {name: 'Albanian'}, {name: 'Georgian'}, {name: 'Galician'}],
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

		var langStore = new Ext.data.DirectStore({
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
				},
				scope: this
			}
		});


		Ext.apply(this, {
			languagesLoaded: false,
			layout:'hbox',
			layoutConfig: {
				align: 'stretch'
			},
			defaults: {
				border: false
			},
			items: [{
				width: 350,
				layout: 'fit',
				items: [{
					xtype:'fieldset',
					title: TYPO3.lang.translation_settings,
					collapsible: false,
					labelWidth: 1,
					labelPad: 0,
					defaults: {
						border: false
					},
					items: [{
						xtype: 'grid',
						id: 'em-languagegrid',
						stripeRows: true,
						store: langStore,
						cm: TYPO3.EM.LanguagesColumnModel,
						sm: TYPO3.EM.LanguagesSelectionModel,
						enableColumnMove: false,
						anchor: '100% 100%'
					}]
				}]
			}, {
				flex: 1,
				layout: 'fit',
				items: [{
					xtype:'fieldset',
					title: TYPO3.lang.translation_status,
					collapsible: false,
					items: [
						TYPO3.EM.LanguagesActionPanel,
						TYPO3.EM.LanguagesProgressBar, {
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
							anchor: '100% -40'
						}]
					}]
				}]
		});

		// call parent
		TYPO3.EM.Languages.superclass.initComponent.apply(this, arguments);
		this.langGrid = Ext.getCmp('em-languagegrid');
		this.langGrid.getSelectionModel().on('selectionchange', function(){
			this.saveSelection();
		}, this);
		Ext.getCmp('lang-checkbutton').handler = this.langActionHandler.createDelegate(this);
		Ext.getCmp('lang-updatebutton').handler = this.langActionHandler.createDelegate(this);

	} ,

	langActionHandler: function(button, event) {
		var bp = Ext.getCmp('LanguagesActionPanel');
		var pp = Ext.getCmp('langpb');
		bp.hide();
		pp.show();

		if (button.id === 'lang-checkbutton') {
			// check languages
			this.startFetchLanguages(0, Ext.StoreMgr.get('em-languageext-store'), function(){
				TYPO3.EM.LanguagesProgressBar.updateText(TYPO3.lang.msg_finished);
				(function() {
					pp.hide();
					bp.show();
				}).defer(5000, this);
				TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.translation_checking_extension, TYPO3.lang.translation_check_done,3);
			});
		} else {
			// update languages
			this.startFetchLanguages(1, Ext.StoreMgr.get('em-languageext-store'), function(){
				TYPO3.EM.LanguagesProgressBar.updateText(TYPO3.lang.msg_finished);
				TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.langtranslation_update_extension, TYPO3.langtranslation_update_done, 3);
				pp.hide();
				bp.show();
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
			if (this.selectedLanguages.length > 0 ) {
				(function() {
					this.restoreExtLanguageGrid();
				}).defer(100, this);
			}

			TYPO3.EM.ExtDirect.saveLanguageSelection(this.selectedLanguages, function(response) {
				TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.lang.translation_selection_saved, response, 3);
			});
		}
	},

	startFetchLanguages: function(type, store, callback) {
		this.fetchType = type;
		this.extCount = store.data.items.length;
		this.cb = callback;


		// fill arrays
		for(var i = 0; i < this.extCount; i++) {
			this.extkeyArray.push(store.data.items[i].data.extkey);
		}
		if (!this.selectedLanguages.length) {
			this.getSelectedLanguages();
		}
		// start process
		this.fetchLanguage();
	},

	fetchLanguage: function(res) {
		var grid = Ext.getCmp('em-extlanguagegrid');
		var row = this.extCount - this.extkeyArray.length;
		var record = grid.store.getAt(row);
		var i;

		// res is response from request
		// array selectedLanguage key => grid html

		if (res) {
			// update fetched record
			var fetchedRecord = grid.store.getAt(row-1);
			var key = '';
			for (i = 0; i < this.selectedLanguages.length; i++) {
				key = this.selectedLanguages[i];
				fetchedRecord.set(key, res[0][key]);
    		}
    		fetchedRecord.commit();
		}

		if(this.extkeyArray.length > 0) {
			var ext = this.extkeyArray.shift();


			//update Grid
			grid.getView().focusRow(row);
			grid.getSelectionModel().selectRow(row);
			for (i = 0; i < this.selectedLanguages.length; i++) {
				record.set(this.selectedLanguages[i], TYPO3.lang.translation_checking);
    		}
    		record.commit();

			// update Progressbar
			Ext.getCmp('langpb').updateProgress(
				(row+1)/this.extCount,
				(this.fetchType === 0 ?
						TYPO3.lang.msg_checking + ': ' :
						TYPO3.lang.msg_updating + ': ') +
					String.format(TYPO3.lang.translation_fetch_extension, ext, (row+1), this.extCount));

			// fetch language request
			TYPO3.EM.ExtDirect.fetchTranslations(ext, this.fetchType, this.selectedLanguages, function(response) {
				this.fetchLanguage(response);
			}, this);
		} else {
			// finished
			Ext.getCmp('lang-checkbutton').enable();
			Ext.getCmp('lang-updatebutton').enable();
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
		if (count > 1) {
			for(i=1; i<count; i++) {
				columns.removeColumn(1);
			}
		}
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
				id: record.lang
			});
		} else {
			columns.removeColumn(index);
		}
	},

	langColumnRenderer: function(value) {
		if (value === 'update') {
			return '<div style="background:#ff0;">' + TYPO3.lang.translation_status_update + '</div>';
		} else if(value === 'N/A') {
			return '<div style="background:red;">' + TYPO3.lang.translation_n_a + '</div>';
		} else if(value === 'ok') {
			return '<div style="background:#69a550;">' + TYPO3.lang.translation_status_ok + '</div>';
		} else if(value === 'new') {
			return '<div style="background:#ff0;">' + TYPO3.lang.translation_status_new + '</div>';
		} else {
			return '<i>' + value + '</i>';
		}
	},

	onRender:function() {



		// call parent
		TYPO3.EM.Languages.superclass.onRender.apply(this, arguments);

	}
});
Ext.reg('extlanguages', TYPO3.EM.Languages);
