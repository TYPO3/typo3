Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms');

/**
 * The options properties of the element
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Options
 * @extends Ext.FormPanel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Options = Ext.extend(Ext.grid.EditorGridPanel, {
	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('options_fieldoptions'),

	/**
	 * @cfg {String} autoExpandColumn
	 * The id of a column in this grid that should expand to fill unused space.
	 * This value specified here can not be 0.
	 */
	autoExpandColumn: 'text',

	/**
	 * @cfg {Number/String} padding
	 * A shortcut for setting a padding style on the body element. The value can
	 * either be a number to be applied to all sides, or a normal css string
	 * describing padding.
	 */
	padding: '10px 0 10px 15px',

	/**
	 * @cfg {Number} clicksToEdit
	 * The number of clicks on a cell required to display the cell's editor (defaults to 2).
	 * Setting this option to 'auto' means that mousedown on the selected cell starts
	 * editing that cell.
	 */
	clicksToEdit: 1,

	/**
	 * @cfg {Object} viewConfig A config object that will be applied to the grid's UI view.  Any of
	 * the config options available for Ext.grid.GridView can be specified here. This option
	 * is ignored if view is specified.
	 */
	viewConfig: {
		forceFit: true,
		emptyText: TYPO3.l10n.localize('fieldoptions_emptytext'),
		scrollOffset: 0
	},

	/**
	 * Constructor
	 *
	 * Configure store and columns for the grid
	 */
	initComponent: function () {
		var optionRecord = Ext.data.Record.create([
			{
				name: 'text',
				mapping: 'text',
				type: 'string'
			}, {
				name: 'selected',
				convert: this.convertSelected,
				type: 'bool'
			}, {
				name: 'value',
				convert: this.convertValue,
				type: 'string'
			}
		]);

		var store = new Ext.data.JsonStore({
			idIndex: 1,
			fields: optionRecord,
			data: this.element.configuration.options,
			autoDestroy: true,
			autoSave: true,
			listeners: {
				'add': {
					scope: this,
					fn: this.storeOptions
				},
				'remove': {
					scope: this,
					fn: this.storeOptions
				},
				'update': {
					scope: this,
					fn: this.storeOptions
				}
			}
		});

		var checkColumn = new Ext.ux.grid.SingleSelectCheckColumn({
			id: 'selected',
			header: TYPO3.l10n.localize('fieldoptions_selected'),
			dataIndex: 'selected',
			width: 20
		});

		var itemDeleter = new Ext.ux.grid.ItemDeleter();

		var config = {
			store: store,
			cm: new Ext.grid.ColumnModel({
				defaults: {
					sortable: false
				},
				columns: [
					{
						width: 40,
						id: 'data',
						header: TYPO3.l10n.localize('fieldoptions_text'),
						dataIndex: 'text',
						editor: new Ext.ux.form.TextFieldSubmit({
							allowBlank: false,
							listeners: {
								'triggerclick': function (field) {
									field.gridEditor.record.set('text', field.getValue());
								}
							}
						})
					},
					checkColumn,
					{
						width: 40,
						id: 'value',
						header: TYPO3.l10n.localize('fieldoptions_value'),
						dataIndex: 'value',
						editor: new Ext.ux.form.TextFieldSubmit({
							allowBlank: true,
							listeners: {
								'triggerclick': function (field) {
									field.gridEditor.record.set('value', field.getValue());
								}
							}
						})
					},
					itemDeleter
				]
			}),
			selModel: itemDeleter,
			plugins: [checkColumn],
			tbar: [{
				text: TYPO3.l10n.localize('fieldoptions_button_add'),
				handler: this.addOption,
				scope: this
			}]
		};

		// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

		// call parent
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Options.superclass.initComponent.apply(this, arguments);
	},

	/**
	 * Adds a new record to the grid
	 *
	 * Called when the button to add option in the top bar has been clicked
	 */
	addOption: function () {
		var option = this.store.recordType;
		var newOption = new option({
			text: TYPO3.l10n.localize('fieldoptions_new'),
			selected: false,
			value: TYPO3.l10n.localize('fieldoptions_value')
		});
		this.stopEditing();
		this.store.add(newOption);
		this.startEditing(0, 0);
	},

	/**
	 * Stores the options in the element whenever a change has been done to the
	 * grid, like add, remove or update
	 *
	 * @param store
	 * @param record
	 */
	storeOptions: function (store, record) {
		if (record && record.dirty) {
			record.commit();
		} else {
			var option = {};
			var options = [];
			this.store.each(function (record) {
				var option = {
					text: record.get('text')
				};
				if (record.get('selected')) {
					if (!option.attributes) {
						option.attributes = {};
					}
					option.attributes['selected'] = 'selected';
				}
				if (record.get('value')) {
					if (!option.attributes) {
						option.attributes = {};
					}
					option.attributes['value'] = record.get('value');
				}
				options.push(option);
			});
			this.element.configuration.options = [];
			var formConfiguration = {
				options: options
			};
			this.element.setConfigurationValue(formConfiguration);
		}
	},

	/**
	 * Convert and remap the "selected" attribute. In HTML the attribute needs
	 * be as selected="selected", while the grid uses a boolean.
	 *
	 * @param v
	 * @param record
	 * @returns {Boolean}
	 */
	convertSelected: function (v, record) {
		if (record.attributes && record.attributes.selected) {
			if (record.attributes.selected == 'selected') {
				return true;
			}
		}
		return false;
	},

	/**
	 * Remap value from different locations
	 *
	 * @param v
	 * @param record
	 * @returns {string}
	 */
	convertValue: function (v, record) {
		if (record.attributes && record.attributes.value) {
			return record.attributes.value;
		} else if (record.data) {
			return record.data;
		}
		return '';
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-options', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Options);
