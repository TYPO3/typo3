Ext.namespace('TYPO3.Form.Wizard.Elements');

/**
 * Elements abstract
 *
 * @class TYPO3.Form.Wizard.Elements
 * @extends Ext.Container
 */
TYPO3.Form.Wizard.Elements = Ext.extend(Ext.Container, {
	/**
	 * @cfg {Mixed} autoEl
	 * A tag name or DomHelper spec used to create the Element which will
	 * encapsulate this Component.
	 */
	autoEl: 'li',

	/**
	 * @cfg {String} cls
	 * An optional extra CSS class that will be added to this component's
	 * Element (defaults to ''). This can be useful for adding customized styles
	 * to the component or any of its children using standard CSS rules.
	 */
	cls: 'formwizard-element',

	/**
	 * @cfg {Object} buttonGroup
	 * Reference to the button group
	 */
	buttonGroup: null,

	/**
	 * @cfg {Boolean} isEditable
	 * Defines whether the element is editable. If the item is editable,
	 * a button group with remove and edit buttons will be added to this element
	 * and when the the element is clicked, an event is triggered to edit the
	 * element. Some elements, like the dummy, don't need this.
	 */
	isEditable: true,

	/**
	 * @cfg {Object} configuration
	 * The configuration of this element.
	 * This object contains the configuration of this component. It will be
	 * copied to the 'data' variable before rendering. 'data' is deleted after
	 * rendering the xtemplate, so we need a copy.
	 */
	configuration: {},

	/**
	 * Constructor
	 */
	initComponent: function() {
		this.addEvents({
			'configurationChange': true
		});

		var config = {};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Elements.superclass.initComponent.apply(this, arguments);

			// Add the elementClass to the component
		this.addClass(this.elementClass);

			// Add the listener setactive for the element helper
		TYPO3.Form.Wizard.Helpers.Element.on('setactive', this.toggleActive, this);

			// Set the data before rendering
		this.on('beforerender', this.beforeRender, this);

			// Initialize events after rendering
		this.on('afterrender', this.makeEditable, this);

			// Remove event listeners after the detruction of this component
		this.on('destroy', this.onDestroy, this);
	},

	/**
	 * Copy this.configuration to this.data before rendering
	 *
	 * When using tpl together with data, the data variable will be deleted
	 * after rendering the component. We do not want to lose this data, so we
	 * store it in a different variable 'configuration' which will be copied to
	 * data just before rendering
	 *
	 * All strings within the configuration object are HTML encoded first before
	 * displaying
	 *
	 * @param component This component
	 */
	beforeRender: function(component) {
		this.data = this.encodeConfiguration(this.configuration);
	},

	/**
	 * Html encode all strings in the configuration of an element
	 *
	 * @param unencodedData The configuration object
	 * @returns {Object}
	 */
	encodeConfiguration: function(unencodedData) {
		var encodedData = {};

		Ext.iterate(unencodedData, function (key, value, object) {
			if (Ext.isString(value)) {
				encodedData[key] = Ext.util.Format.htmlEncode(value);
			} else if (Ext.isObject(value)) {
				encodedData[key] = this.encodeConfiguration(value);
			} else {
				encodedData[key] = value;
			}
		}, this);

		return encodedData;
	},

	/**
	 * Add the buttongroup and a click event listener to this component when the
	 * component is editable.
	 */
	makeEditable: function() {
		if (this.isEditable) {
			if (!this.buttonGroup) {
				this.add({
					xtype: 'typo3-form-wizard-buttongroup',
					ref: 'buttonGroup'
				});
			}
			this.el.un('click', this.setActive, this);
			this.el.on('click', this.setActive, this);
				// Add hover class. Normally this would be done with overCls,
				// but this does not take bubbling (propagation) into account
			this.el.hover(
				function(){
					Ext.fly(this).addClass('hover');
				},
				function(){
					Ext.fly(this).removeClass('hover');
				},
				this.el,
				{
					stopPropagation: true
				}
			);
		}
	},

	/**
	 * Called on a click event of this component or when the element is added
	 *
	 * Tells the element helper that this component is set as the active one and
	 * swallows the click event to prevent bubbling
	 *
	 * @param event
	 * @param target
	 * @param object
	 */
	setActive: function(event, target, object) {
		TYPO3.Form.Wizard.Helpers.Element.setActive(this);
		event.stopPropagation();
	},

	/**
	 * Called when the element helper is firing the setactive event
	 *
	 * Adds an extra class 'active' to the element when the current component is
	 * the active one, otherwise removes the class 'active' when this component
	 * has this class
	 * @param component
	 */
	toggleActive: function(component) {
		if (this.isEditable) {
			var element = this.getEl();

			if (component && component.getId() == this.getId()) {
				if (!element.hasClass('active')) {
					element.addClass('active');
				}
			} else if (element.hasClass('active')) {
				element.removeClass('active');
			}
		}
	},

	/**
	 * Display a confirmation box when the delete button has been pressed.
	 *
	 * @param event
	 * @param target
	 * @param object
	 */
	confirmDeleteElement: function(event, target, object) {
		Ext.MessageBox.confirm(
			TYPO3.l10n.localize('elements_confirm_delete_title'),
			TYPO3.l10n.localize('elements_confirm_delete_description'),
			this.deleteElement,
			this
		);
	},

	/**
	 * Delete the component when the yes button of the confirmation box has been
	 * pressed.
	 *
	 * @param button The button which has been pressed (yes / no)
	 */
	deleteElement: function(button) {
		if (button == 'yes') {
			this.ownerCt.removeElement(this);
		}
	},

	/**
	 * Called by the parent of this component when a change has been made in the
	 * form.
	 *
	 * Constructs an array out of this component and the children to add it to
	 * the history or to use when saving the form
	 *
	 * @returns {Array}
	 */
	getConfiguration: function() {
		var historyConfiguration = {
			configuration: this.configuration,
			isEditable: this.isEditable,
			xtype: this.xtype
		};

		if (this.containerComponent) {
			historyConfiguration.elementContainer = this.containerComponent.getConfiguration();
		}
		return historyConfiguration;
	},

	/**
	 * Called when a configuration property has changed in the options tab
	 *
	 * Overwrites the configuration with the configuration from the form,
	 * adds a new snapshot to the history and renders this component again.
	 * @param formConfiguration
	 */
	setConfigurationValue: function(formConfiguration) {
		Ext.merge(this.configuration, formConfiguration);
		TYPO3.Form.Wizard.Helpers.History.setHistory();
		this.rendered = false;
		this.render();
		this.doLayout();
		this.fireEvent('configurationChange', this);
	},

	/**
	 * Remove a validation rule from this element
	 *
	 * @param type
	 */
	removeValidationRule: function(type) {
		if (this.configuration.validation[type]) {
			delete this.configuration.validation[type];
			TYPO3.Form.Wizard.Helpers.History.setHistory();
			if (this.xtype != 'typo3-form-wizard-elements-basic-form') {
				this.rendered = false;
				this.render();
				this.doLayout();
			}
		}
	},

	/**
	 * Remove a filter from this element
	 *
	 * @param type
	 */
	removeFilter: function(type) {
		if (this.configuration.filters[type]) {
			delete this.configuration.filters[type];
			TYPO3.Form.Wizard.Helpers.History.setHistory();
			if (this.xtype != 'typo3-form-wizard-elements-basic-form') {
				this.rendered = false;
				this.render();
				this.doLayout();
			}
		}
	},

	/**
	 * Fires after the component is destroyed.
	 *
	 * Removes the listener for the 'setactive' event of the element helper.
	 * Tells the element helper this element is destroyed and if set active,
	 * it should be unset as active.
	 */
	onDestroy: function() {
		TYPO3.Form.Wizard.Helpers.Element.un('setactive', this.toggleActive, this);
		TYPO3.Form.Wizard.Helpers.Element.unsetActive(this);
	}
});

Ext.reg('typo3-form-wizard-elements',TYPO3.Form.Wizard.Elements);