Ext.namespace('TYPO3.Form.Wizard.Elements.Basic');

/**
 * The SELECT element
 *
 * @class TYPO3.Form.Wizard.Elements.Basic.Select
 * @extends TYPO3.Form.Wizard.Elements
 */
TYPO3.Form.Wizard.Elements.Basic.Select = Ext.extend(TYPO3.Form.Wizard.Elements, {
	/**
	 * @cfg {String} elementClass
	 * An extra CSS class that will be added to this component's Element
	 */
	elementClass: 'select',

	/**
	 * @cfg {Mixed} tpl
	 * An Ext.Template, Ext.XTemplate or an array of strings to form an
	 * Ext.XTemplate. Used in conjunction with the data and tplWriteMode
	 * configurations.
	 */
	tpl: new Ext.XTemplate(
		'<div class="overflow-hidden">',
			'<tpl for="label">',
				'<tpl if="value && parent.layout == \'front\'">',
					'<label for="">{value}{[this.getMessage(parent.validation)]}</label>',
				'</tpl>',
			'</tpl>',
			'<select {[this.getAttributes(values.attributes)]}>',
				'<tpl for="options">',
					'<option {[this.getAttributes(values.attributes)]}>{data}</option>',
				'</tpl>',
			'</select>',
			'<tpl for="label">',
				'<tpl if="value && parent.layout == \'back\'">',
					'<label for="">{value}{[this.getMessage(parent.validation)]}</label>',
				'</tpl>',
			'</tpl>',
		'</div>',
		{
			compiled: true,
			getMessage: function(rules) {
				var messageHtml = '';
				var messages = [];
				Ext.iterate(rules, function(rule, configuration) {
					if (configuration.showMessage) {
						messages.push(configuration.message);
					}
				}, this);

				messageHtml = ' <em>' + messages.join(', ') + '</em>';
				return messageHtml;

			},
			getAttributes: function(attributes) {
				var attributesHtml = '';
				Ext.iterate(attributes, function(key, value) {
					if (value) {
						attributesHtml += key + '="' + value + '" ';
					}
				}, this);
				return attributesHtml;
			}
		}
	),

	/**
	 * Constructor
	 *
	 * Add the configuration object to this component
	 * @param config
	 */
	constructor: function(config) {
		Ext.apply(this, {
			configuration: {
				attributes: {
					"class": '',
					disabled: '',
					id: '',
					lang: '',
					multiple: '',
					name: '',
					size: '',
					style: '',
					tabindex: '',
					title: ''
				},
				filters: {},
				label: {
					value: TYPO3.l10n.localize('elements_label')
				},
				options: [
					{
						data: TYPO3.l10n.localize('elements_option_1')
					}, {
						data: TYPO3.l10n.localize('elements_option_2')
					}, {
						data: TYPO3.l10n.localize('elements_option_3')
					}
				],
				layout: 'front',
				validation: {}
			}
		});
		TYPO3.Form.Wizard.Elements.Basic.Select.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-elements-basic-select', TYPO3.Form.Wizard.Elements.Basic.Select);