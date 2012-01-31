Ext.namespace('TYPO3.Form.Wizard.Elements.Basic');

/**
 * The FIELDSET element
 *
 * @class TYPO3.Form.Wizard.Elements.Basic.Fieldset
 * @extends TYPO3.Form.Wizard.Elements
 */
TYPO3.Form.Wizard.Elements.Basic.Fieldset = Ext.extend(TYPO3.Form.Wizard.Elements, {
	/**
	 * @cfg {String} elementClass
	 * An extra CSS class that will be added to this component's Element
	 */
	elementClass: 'fieldset',

	/**
	 * @cfg {Mixed} tpl
	 * An Ext.Template, Ext.XTemplate or an array of strings to form an
	 * Ext.XTemplate. Used in conjunction with the data and tplWriteMode
	 * configurations.
	 */
	tpl: new Ext.XTemplate(
		'<div>',
			'<fieldset {[this.getAttributes(values.attributes)]}>',
			'<tpl for="legend">',
				'<tpl if="value">',
					'<legend>{value}</legend>',
				'</tpl>',
			'</tpl>',
			'<ol></ol>',
			'</fieldset>',
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
	 * @cfg {Array} elementContainer
	 * Configuration for the containerComponent
	 */
	elementContainer: {
		hasDragAndDrop: true
	},

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
					style: ''
				},
				legend: {
					value: TYPO3.l10n.localize('elements_legend')
				}
			}
		});

		TYPO3.Form.Wizard.Elements.Basic.Fieldset.superclass.constructor.apply(this, arguments);
	},

	/**
	 * Constructor
	 */
	initComponent: function() {
		var config = {};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// Initialize the container component
		this.containerComponent = new TYPO3.Form.Wizard.Container(this.elementContainer);

			// call parent
		TYPO3.Form.Wizard.Elements.Basic.Fieldset.superclass.initComponent.apply(this, arguments);

			// Initialize events after rendering
		this.on('afterrender', this.afterRender, this);
	},

	/**
	 * Called by the 'afterrender' event.
	 *
	 * Add the container component to this component
	 */
	afterRender: function() {
		this.addContainerAfterRender();

			// Call parent
		TYPO3.Form.Wizard.Elements.Basic.Form.superclass.afterRender.call(this);
	},

	/**
	 * Add the container component to this component
	 *
	 * Because we are using a XTemplate for rendering this component, we can
	 * only add the container after rendering, because the <ol> tag needs to be
	 * replaced with this container.
	 *
	 * The container needs to be rerendered when a configuration parameter
	 * (legend or attributes) of the ownerCt, for instance fieldset, has changed
	 * otherwise it will not show up
	 */
	addContainerAfterRender: function() {
		this.containerComponent.applyToMarkup(this.getEl().child('ol'));
		this.containerComponent.rendered = false;
		this.containerComponent.render();
		this.containerComponent.doLayout();
	}
});

Ext.reg('typo3-form-wizard-elements-basic-fieldset', TYPO3.Form.Wizard.Elements.Basic.Fieldset);