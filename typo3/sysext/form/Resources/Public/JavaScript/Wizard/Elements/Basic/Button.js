Ext.namespace('TYPO3.Form.Wizard.Elements.Basic');

/**
 * The BUTTON element
 *
 * @class TYPO3.Form.Wizard.Elements.Basic.Button
 * @extends TYPO3.Form.Wizard.Elements
 */
TYPO3.Form.Wizard.Elements.Basic.Button = Ext.extend(TYPO3.Form.Wizard.Elements, {
	/**
	 * @cfg {String} elementClass
	 * An extra CSS class that will be added to this component's Element
	 */
	elementClass: 'button',

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
			'<input {[this.getAttributes(values.attributes)]} />',
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
					'accesskey': '',
					'class': '',
					'contenteditable': '',
					'contextmenu': '',
					'dir': '',
					'draggable': '',
					'dropzone': '',
					'hidden': '',
					'id': '',
					'lang': '',
					'spellcheck': '',
					'style': '',
					'tabindex': '',
					'title': '',
					'translate': '',

					'autofocus': '',
					'disabled': '',
					'name': '',
					'type': 'button',
					'value': TYPO3.l10n.localize('tx_form_domain_model_element_button.value')
				},
				filters: {},
				label: {
					value: TYPO3.l10n.localize('elements_label')
				},
				layout: 'front',
				validation: {}
			}
		});
		TYPO3.Form.Wizard.Elements.Basic.Button.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-elements-basic-button', TYPO3.Form.Wizard.Elements.Basic.Button);