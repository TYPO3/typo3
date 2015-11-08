Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Options.Forms');

/**
 * The attributes properties of the element
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes
 * @extends Ext.FormPanel
 */
TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes = Ext.extend(Ext.FormPanel, {
	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('options_attributes'),

	/** @cfg {String} defaultType
	 *
	 * The default xtype of child Components to create in this Container when
	 * a child item is specified as a raw configuration object,
	 * rather than as an instantiated Component.
	 *
	 * Defaults to 'panel', except Ext.menu.Menu which defaults to 'menuitem',
	 * and Ext.Toolbar and Ext.ButtonGroup which default to 'button'.
	 */
	defaultType: 'textfieldsubmit',

	/**
	 * @cfg {Boolean} monitorValid If true, the form monitors its valid state client-side and
	 * regularly fires the clientvalidation event passing that state.
	 * When monitoring valid state, the FormPanel enables/disables any of its configured
	 * buttons which have been configured with formBind: true depending
	 * on whether the form is valid or not. Defaults to false
	 */
	monitorValid: true,

	/**
	 * Constructor
	 *
	 * @param config
	 */
	constructor: function(config){
			// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'validation': true
		});

			// Call our superclass constructor to complete construction process.
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes.superclass.constructor.call(this, config);
	},

	/**
	 * Constructor
	 *
	 * Add the form elements to the accordion
	 */
	initComponent: function() {
		var attributes = this.getAttributesBySettings();
		var formItems = new Array();

		Ext.iterate(attributes, function(item, index, allItems) {
			switch(item) {
				case 'accept':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_accept'),
						name: 'accept',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'accept-charset':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_acceptcharset'),
						name: 'accept-charset',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'accesskey':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_accesskey'),
						name: 'accesskey',
						maxlength: 1,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'action':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_action'),
						name: 'action',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'alt':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_alt'),
						name: 'alt',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'autocomplete':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_autocomplete'),
						name: 'autocomplete',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'autocomplete',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_autocomplete_none'), value: ''},
								{label: TYPO3.l10n.localize('attributes_autocomplete_off'), value: 'off'},
								{label: TYPO3.l10n.localize('attributes_autocomplete_on'), value: 'on'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'autofocus':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_autofocus'),
						name: 'autofocus',
						inputValue: 'autofocus',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'checked':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_checked'),
						name: 'checked',
						inputValue: 'checked',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'class':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_class'),
						name: 'class',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'cols':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_cols'),
						name: 'cols',
						xtype: 'spinnerfield',
						allowBlank: false,
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'contenteditable':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_contenteditable'),
						name: 'contenteditable',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'contenteditable',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_contenteditable_none'), value: ''},
								{label: TYPO3.l10n.localize('attributes_contenteditable_true'), value: 'true'},
								{label: TYPO3.l10n.localize('attributes_contenteditable_false'), value: 'false'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'contextmenu':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_contextmenu'),
						name: 'contextmenu',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'dir':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_dir'),
						name: 'dir',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'dir',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_dir_ltr'), value: 'ltr'},
								{label: TYPO3.l10n.localize('attributes_dir_rtl'), value: 'rtl'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'disabled':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_disabled'),
						name: 'disabled',
						inputValue: 'disabled',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'draggable':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_draggable'),
						name: 'draggable',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'draggable',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_draggable_none'), value: ''},
								{label: TYPO3.l10n.localize('attributes_draggable_false'), value: 'false'},
								{label: TYPO3.l10n.localize('attributes_draggable_true'), value: 'true'},
								{label: TYPO3.l10n.localize('attributes_draggable_auto'), value: 'auto'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'dropzone':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_dropzone'),
						name: 'dropzone',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'enctype':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_enctype'),
						name: 'enctype',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'enctype',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_enctype_1'), value: 'application/x-www-form-urlencoded'},
								{label: TYPO3.l10n.localize('attributes_enctype_2'), value: 'multipart/form-data'},
								{label: TYPO3.l10n.localize('attributes_enctype_3'), value: 'text/plain'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'height':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_height'),
						name: 'height',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'hidden':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_hidden'),
						name: 'hidden',
						inputValue: 'hidden',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'id':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_id'),
						name: 'id',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'inputmode':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_inputmode'),
						name: 'inputmode',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'inputmode',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_inputmode_none'), value: ''},
								{label: TYPO3.l10n.localize('attributes_inputmode_verbatim'), value: 'verbatim'},
								{label: TYPO3.l10n.localize('attributes_inputmode_latin'), value: 'latin'},
								{label: TYPO3.l10n.localize('attributes_inputmode_latin-name'), value: 'latin-name'},
								{label: TYPO3.l10n.localize('attributes_inputmode_latin-prose'), value: 'latin-prose'},
								{label: TYPO3.l10n.localize('attributes_inputmode_full-width-latin'), value: 'full-width-latin'},
								{label: TYPO3.l10n.localize('attributes_inputmode_kana'), value: 'kana'},
								{label: TYPO3.l10n.localize('attributes_inputmode_kana-name'), value: 'kana-name'},
								{label: TYPO3.l10n.localize('attributes_inputmode_katakana'), value: 'katakana'},
								{label: TYPO3.l10n.localize('attributes_inputmode_numeric'), value: 'numeric'},
								{label: TYPO3.l10n.localize('attributes_inputmode_tel'), value: 'tel'},
								{label: TYPO3.l10n.localize('attributes_inputmode_email'), value: 'email'},
								{label: TYPO3.l10n.localize('attributes_inputmode_url'), value: 'url'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'label':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_label'),
						name: 'label',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'lang':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_lang'),
						name: 'lang',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'list':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_list'),
						name: 'list',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'max':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_max'),
						name: 'max',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'maxlength':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_maxlength'),
						name: 'maxlength',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'method':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_method'),
						name: 'method',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'method',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_method_get'), value: 'get'},
								{label: TYPO3.l10n.localize('attributes_method_post'), value: 'post'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'min':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_min'),
						name: 'min',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'minlength':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_minlength'),
						name: 'minlength',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'multiple':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_multiple'),
						name: 'multiple',
						inputValue: 'multiple',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'name':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_name'),
						name: 'name',
						allowBlank:false,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'novalidate':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_novalidate'),
						name: 'novalidate',
						inputValue: 'novalidate',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'pattern':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_pattern'),
						name: 'pattern',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'placeholder':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_placeholder'),
						name: 'placeholder',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'readonly':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_readonly'),
						name: 'readonly',
						inputValue: 'readonly',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'required':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_required'),
						name: 'required',
						inputValue: 'required',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'rows':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_rows'),
						name: 'rows',
						xtype: 'spinnerfield',
						allowBlank: false,
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'selected':
					formItems.push({
						xtype: 'typo3-form-wizard-valuecheckbox',
						fieldLabel: TYPO3.l10n.localize('attributes_selected'),
						name: 'selected',
						inputValue: 'selected',
						listeners: {
							'check': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'selectionDirection':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_selectionDirection'),
						name: 'selectionDirection',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'selectionDirection',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_selectionDirection_none'), value: ''},
								{label: TYPO3.l10n.localize('attributes_selectionDirection_forward'), value: 'forward'},
								{label: TYPO3.l10n.localize('attributes_selectionDirection_backward'), value: 'backward'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'selectionEnd':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_selectionEnd'),
						name: 'selectionEnd',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'selectionStart':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_selectionStart'),
						name: 'selectionStart',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'size':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_size'),
						name: 'size',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'spellcheck':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_spellcheck'),
						name: 'spellcheck',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'spellcheck',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_spellcheck_none'), value: ''},
								{label: TYPO3.l10n.localize('attributes_spellcheck_true'), value: 'true'},
								{label: TYPO3.l10n.localize('attributes_spellcheck_false'), value: 'false'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'src':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_src'),
						name: 'src',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'step':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_step'),
						name: 'step',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'style':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_style'),
						name: 'style',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'tabindex':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_tabindex'),
						name: 'tabindex',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'text':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_text'),
						xtype: 'textarea',
						name: 'text',
						allowBlank: true,
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'title':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_title'),
						name: 'title',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'translate':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_translate'),
						name: 'translate',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'translate',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_translate_none'), value: ''},
								{label: TYPO3.l10n.localize('attributes_translate_no'), value: 'no'},
								{label: TYPO3.l10n.localize('attributes_translate_yes'), value: 'yes'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'type':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_type'),
						name: 'type',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'type',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_type_color'), value: 'color'},
								{label: TYPO3.l10n.localize('attributes_type_date'), value: 'date'},
								{label: TYPO3.l10n.localize('attributes_type_datetime'), value: 'datetime'},
								{label: TYPO3.l10n.localize('attributes_type_datetime-local'), value: 'datetime-local'},
								{label: TYPO3.l10n.localize('attributes_type_email'), value: 'email'},
								{label: TYPO3.l10n.localize('attributes_type_month'), value: 'month'},
								{label: TYPO3.l10n.localize('attributes_type_number'), value: 'number'},
								{label: TYPO3.l10n.localize('attributes_type_search'), value: 'search'},
								{label: TYPO3.l10n.localize('attributes_type_tel'), value: 'tel'},
								{label: TYPO3.l10n.localize('attributes_type_text'), value: 'text'},
								{label: TYPO3.l10n.localize('attributes_type_time'), value: 'time'},
								{label: TYPO3.l10n.localize('attributes_type_url'), value: 'url'},
								{label: TYPO3.l10n.localize('attributes_type_week'), value: 'week'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'value':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_value'),
						name: 'value',
						listeners: {
							'triggerclick': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'width':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_width'),
						name: 'width',
						xtype: 'spinnerfield',
						listeners: {
							'spin': {
								scope: this,
								fn: this.storeValue
							},
							'blur': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
				case 'wrap':
					formItems.push({
						fieldLabel: TYPO3.l10n.localize('attributes_wrap'),
						name: 'wrap',
						xtype: 'combo',
						mode: 'local',
						triggerAction: 'all',
						forceSelection: true,
						editable: false,
						hiddenName: 'wrap',
						displayField: 'label',
						valueField: 'value',
						store: new Ext.data.JsonStore({
							fields: ['label', 'value'],
							data: [
								{label: TYPO3.l10n.localize('attributes_wrap_none'), value: ''},
								{label: TYPO3.l10n.localize('attributes_wrap_soft'), value: 'soft'},
								{label: TYPO3.l10n.localize('attributes_wrap_hard'), value: 'hard'}
							]
						}),
						listeners: {
							'select': {
								scope: this,
								fn: this.storeValue
							}
						}
					});
					break;
			}
		}, this);

		var config = {
			items: [{
				xtype: 'fieldset',
				title: '',
				autoHeight: true,
				border: false,
				defaults: {
					width: 150,
					msgTarget: 'side'
				},
				defaultType: 'textfieldsubmit',
				items: formItems
			}]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes.superclass.initComponent.apply(this, arguments);

			// Initialize clientvalidation event
		this.on('clientvalidation', this.validation, this);

			// Fill the form with the configuration values
		this.fillForm();
	},

	/**
	 * Store a changed value from the form in the element
	 *
	 * @param {Object} field The field which has changed
	 */
	storeValue: function(field) {
		if (field.isValid()) {
			var fieldName = field.getName();

			var formConfiguration = {attributes: {}};
			formConfiguration.attributes[fieldName] = field.getValue();

			this.element.setConfigurationValue(formConfiguration);
		}
	},

	/**
	 * Fill the form with the configuration of the element
	 *
	 * @return void
	 */
	fillForm: function() {
		this.getForm().setValues(this.element.configuration.attributes);
	},

	/**
	 * Get the attributes for the element
	 *
	 * Based on the elements attributes, the TSconfig general allowed attributes
	 * and the TSconfig allowed attributes for this type of element
	 *
	 * @returns object
	 */
	getAttributesBySettings: function() {
		var attributes = [];
		var elementAttributes = this.element.configuration.attributes;
		var elementType = this.element.xtype.split('-').pop();

		var allowedGeneralAttributes = [];
		try {
			allowedGeneralAttributes = TYPO3.Form.Wizard.Settings.defaults.tabs.options.accordions.attributes.showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found or constructed wrong
			allowedGeneralAttributes = [
				'accept',
				'acceptcharset',
				'accesskey',
				'action',
				'alt',
				'checked',
				'class',
				'cols',
				'dir',
				'disabled',
				'enctype',
				'id',
				'label',
				'lang',
				'maxlength',
				'method',
				'multiple',
				'name',
				'readonly',
				'rows',
				'selected',
				'size',
				'src',
				'style',
				'tabindex',
				'title',
				'type',
				'value'
			];
		}

		var allowedElementAttributes = [];
		try {
			allowedElementAttributes = TYPO3.Form.Wizard.Settings.elements[elementType].accordions.attributes.showProperties.split(/[, ]+/);
		} catch (error) {
			// The object has not been found
			allowedElementAttributes = allowedGeneralAttributes;
		}

		Ext.iterate(allowedElementAttributes, function(item, index, allItems) {
			if (allowedGeneralAttributes.indexOf(item) > -1 && Ext.isDefined(elementAttributes[item])) {
				attributes.push(item);
			}
		}, this);

		return attributes;
	},

	/**
	 * Called by the clientvalidation event
	 *
	 * Adds or removes the error class if the form is valid or not
	 *
	 * @param {Object} formPanel This formpanel
	 * @param {Boolean} valid True if the client validation is true
	 */
	validation: function(formPanel, valid) {
		if (this.el) {
			if (valid && this.el.hasClass('validation-error')) {
				this.removeClass('validation-error');
				this.fireEvent('validation', 'attributes', valid);
			} else if (!valid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', 'attributes', valid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-options-forms-attributes', TYPO3.Form.Wizard.Viewport.Left.Options.Forms.Attributes);
