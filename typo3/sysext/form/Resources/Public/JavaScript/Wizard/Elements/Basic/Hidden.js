Ext.namespace('TYPO3.Form.Wizard.Elements.Basic');

/**
 * The HIDDEN element
 *
 * @class TYPO3.Form.Wizard.Elements.Basic.Hidden
 * @extends TYPO3.Form.Wizard.Elements
 */
TYPO3.Form.Wizard.Elements.Basic.Hidden = Ext.extend(TYPO3.Form.Wizard.Elements, {
	/**
	 * @cfg {String} elementClass
	 * An extra CSS class that will be added to this component's Element
	 */
	elementClass: 'hidden-element',

	/**
	 * @cfg {Mixed} tpl
	 * An Ext.Template, Ext.XTemplate or an array of strings to form an
	 * Ext.XTemplate. Used in conjunction with the data and tplWriteMode
	 * configurations.
	 */
	tpl: new Ext.XTemplate(
		'<div class="overflow-hidden">',
			'<p class="hidden-dummy-element">{[this.getAttributes(values.attributes, \'name\')]}</p>',
			'<input {[this.getAttributes(values.attributes)]} />',
		'</div>',
		{
			compiled: true,
			getAttributes: function(attributes, filterBy) {
				var attributesHtml = '';
				Ext.iterate(attributes, function(key, value) {
					if (typeof filterBy != 'undefined') {
						if (key == filterBy) {
							attributesHtml = value;
							return;
						} else {
							return;
						}
					}

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

					'name': '',
					'type': 'hidden',
					'value': ''
				},
				filters: {},
				validation: {}
			}
		});
		TYPO3.Form.Wizard.Elements.Basic.Hidden.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-elements-basic-hidden', TYPO3.Form.Wizard.Elements.Basic.Hidden);