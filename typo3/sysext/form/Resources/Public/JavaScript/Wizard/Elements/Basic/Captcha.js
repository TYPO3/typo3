Ext.namespace('TYPO3.Form.Wizard.Elements.Basic');

/**
 * The CAPTCHA element
 *
 * @class TYPO3.Form.Wizard.Elements.Basic.Captcha
 * @extends TYPO3.Form.Wizard.Elements
 */
TYPO3.Form.Wizard.Elements.Basic.Captcha = Ext.extend(TYPO3.Form.Wizard.Elements, {
	/**
	 * @cfg {String} elementClass
	 * An extra CSS class that will be added to this component's Element
	 */
	elementClass: 'captcha',

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
			'<img src="../../Resources/Public/Images/captcha.jpg" />',
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
					accesskey: '',
					alt: '',
					class: '',
					dir: '',
					disabled: '',
					id: '',
					lang: '',
					maxlength: '',
					name: '',
					readonly: '',
					size: '',
					style: '',
					tabindex: '',
					title: '',
					type: 'text',
					value: ''
				},
				filters: {},
				label: {
					value: TYPO3.lang['tx_form_domain_model_element_captcha.captcha']
				},
				layout: 'front',
				validation: {
					captcha: {
						breakOnError: 0,
						showMessage: 1,
						message: TYPO3.lang['tx_form_system_validate_captcha.message'],
						error: TYPO3.lang['tx_form_system_validate_captcha.error']
					}
				}
			}
		});
		TYPO3.Form.Wizard.Elements.Basic.Captcha.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-elements-basic-captcha', TYPO3.Form.Wizard.Elements.Basic.Captcha);