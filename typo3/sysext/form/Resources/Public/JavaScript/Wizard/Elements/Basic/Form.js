Ext.namespace('TYPO3.Form.Wizard.Elements.Basic');

/**
 * The FORM element
 *
 * @class TYPO3.Form.Wizard.Elements.Basic.Form
 * @extends TYPO3.Form.Wizard.Elements
 */
TYPO3.Form.Wizard.Elements.Basic.Form = Ext.extend(TYPO3.Form.Wizard.Elements, {
	/**
	 * @cfg {Mixed} autoEl
	 * A tag name or DomHelper spec used to create the Element which will
	 * encapsulate this Component.
	 */
	autoEl: 'li',

	/**
	 * @cfg {String} elementClass
	 * An extra CSS class that will be added to this component's Element
	 */
	elementClass: 'form',

	/**
	 * @cfg {Mixed} tpl
	 * An Ext.Template, Ext.XTemplate or an array of strings to form an
	 * Ext.XTemplate. Used in conjunction with the data and tplWriteMode
	 * configurations.
	 *
	 * Adding novalidate attribute avoids HTML5 validation of elements.
	 */
	tpl: new Ext.XTemplate(
		'<form {[this.getAttributes(values.attributes)]} novalidate="novalidate">',
			'<ol></ol>',
		'</form>',
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
	 * @cfg {Boolean} isEditable
	 * Defines whether the element is editable. If the item is editable,
	 * a button group with remove and edit buttons will be added to this element
	 * and when the the element is clicked, an event is triggered to edit the
	 * element. Some elements, like the dummy, don't need this.
	 */
	isEditable: false,

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

					'accept': '',
					'accept-charset': '',
					'action': '',
					'autocomplete': '',
					'enctype': 'application/x-www-form-urlencoded',
					'method': 'post',
					'novalidate': ''
				},
				prefix: 'tx_form',
				confirmation: true,
				postProcessor: {
					mail: {
						recipientEmail: '',
						senderEmail: ''
					},
					redirect: {
						destination: ''
					}
				}
			}
		});
		TYPO3.Form.Wizard.Elements.Basic.Form.superclass.constructor.apply(this, arguments);
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

			// Call parent
		TYPO3.Form.Wizard.Elements.Basic.Form.superclass.initComponent.apply(this, arguments);

			// Initialize events after rendering
		this.on('afterrender', this.afterRender, this);
	},

	/**
	 * Called by the 'afterrender' event.
	 *
	 * Add the container component to this component
	 * Stop the submit event of the form, because this form does not need to be
	 * submitted
	 */
	afterRender: function() {
		this.addContainerAfterRender();

		this.getEl().child('form').on(
			'submit',
			function(eventObject, htmlElement, object) {
				eventObject.stopEvent();
			}
		);

			// Call parent
		TYPO3.Form.Wizard.Elements.Basic.Form.superclass.afterRender.call(this);
	},

	/**
	 * Add the container component to this component
	 *
	 * Because we are using a XTemplate for rendering this component, we can
	 * only add the container after rendering, because the <ol> tag needs to be
	 * replaced with this container.
	 */
	addContainerAfterRender: function() {
		this.containerComponent.applyToMarkup(this.getEl().child('ol'));
		this.containerComponent.rendered = false;
		this.containerComponent.render();
		this.containerComponent.doLayout();
	},

	/**
	 * Remove a post processor from this element
	 *
	 * @param type
	 */
	removePostProcessor: function(type) {
		if (this.configuration.postProcessor[type]) {
			delete this.configuration.postProcessor[type];
			TYPO3.Form.Wizard.Helpers.History.setHistory();
		}
	}
});

Ext.reg('typo3-form-wizard-elements-basic-form', TYPO3.Form.Wizard.Elements.Basic.Form);