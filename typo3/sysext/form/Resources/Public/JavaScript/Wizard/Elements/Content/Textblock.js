Ext.namespace('TYPO3.Form.Wizard.Elements.Content');

/**
 * The content HEADER element
 *
 * @class TYPO3.Form.Wizard.Elements.Content.Header
 * @extends TYPO3.Form.Wizard.Elements
 */
TYPO3.Form.Wizard.Elements.Content.Textblock = Ext.extend(TYPO3.Form.Wizard.Elements, {
	/**
	 * @cfg {String} elementClass
	 * An extra CSS class that will be added to this component's Element
	 */
	elementClass: 'textblock',

	/**
	 * @cfg {Mixed} tpl
	 * An Ext.Template, Ext.XTemplate or an array of strings to form an
	 * Ext.XTemplate. Used in conjunction with the data and tplWriteMode
	 * configurations.
	 */
	tpl: new Ext.XTemplate(
		'<div class="overflow-hidden">',
			'<tpl for="various">',
				'<div {[this.getAttributes(parent.attributes)]}>',
				'{text:nl2br}',
				'</{type}>',
			'</tpl>',
		'</div>',
		{
			compiled: true,
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
					dir: '',
					id: '',
					lang: '',
					style: '',
					title: ''
				},
				various: {
					text: TYPO3.l10n.localize('elements_textblock_content')
				}
			}
		});
		TYPO3.Form.Wizard.Elements.Content.Textblock.superclass.constructor.apply(this, arguments);
	}
});

Ext.reg('typo3-form-wizard-elements-content-textblock', TYPO3.Form.Wizard.Elements.Content.Textblock);