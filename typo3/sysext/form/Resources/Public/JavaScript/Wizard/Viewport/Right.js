Ext.namespace('TYPO3.Form.Wizard.Viewport');

/**
 * The form container on the right side
 *
 * @class TYPO3.Form.Wizard.Viewport.Right
 * @extends TYPO3.Form.Wizard.Elements.Container
 */
TYPO3.Form.Wizard.Viewport.Right = Ext.extend(Ext.Container, {
	/**
	 * @cfg {String} id
	 * The unique id of this component (defaults to an auto-assigned id).
	 * You should assign an id if you need to be able to access the component
	 * later and you do not have an object reference available
	 * (e.g., using Ext.getCmp).
	 *
	 * Note that this id will also be used as the element id for the containing
	 * HTML element that is rendered to the page for this component.
	 * This allows you to write id-based CSS rules to style the specific
	 * instance of this component uniquely, and also to select sub-elements
	 * using this component's id as the parent.
	 */
	id: 'formwizard-right',

	/**
	 * @cfg {Mixed} autoEl
	 * A tag name or DomHelper spec used to create the Element which will
	 * encapsulate this Component.
	 */
	autoEl: 'ol',

	/**
	 * @cfg {String} region
	 * Note: this config is only used when this BoxComponent is rendered
	 * by a Container which has been configured to use the BorderLayout
	 * layout manager (e.g. specifying layout:'border').
	 */
	region: 'center',

	/**
	 * @cfg {Boolean} autoScroll
	 * true to use overflow:'auto' on the components layout element and show
	 * scroll bars automatically when necessary, false to clip any overflowing
	 * content (defaults to false).
	 */
	autoScroll: true,

	/**
	 * Constructor
	 */
	initComponent: function() {
		var config = {
			items: [
				{
					xtype: 'typo3-form-wizard-elements-basic-form'
				}
			]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Right.superclass.initComponent.apply(this, arguments);

			// Initialize the form after rendering
		this.on('afterrender', this.initializeForm, this);
	},

	/**
	 * Initialize the form after rendering
	 */
	initializeForm: function() {
		this.loadForm();
	},

	/**
	 * Load the form with an AJAX call
	 *
	 * Loads the configuration and initializes the history
	 */
	loadForm: function() {
		var url = document.location.href.substring(document.location.href.indexOf('&P'));
		url = TYPO3.settings.ajaxUrls['formwizard_load'] + url;
		Ext.Ajax.request({
			url: url,
			method: 'POST',
			success: function(response, opts) {
				var responseObject = Ext.decode(response.responseText);
				this.loadConfiguration(responseObject.configuration);
				this.initializeHistory();
			},
			failure: function(response, opts) {
				Ext.MessageBox.alert(
					'Loading form',
					'Server-side failure with status code ' + response.status
				);
			},
			scope: this
		});
	},

	/**
	 * Initialize the history
	 *
	 * After the form has been rendered for the first time, we need to add the
	 * initial configuration to the history, so it is possible to go back to the
	 * initial state of the form when it was loaded.
	 */
	initializeHistory: function() {
		TYPO3.Form.Wizard.Helpers.History.setHistory();
		this.setForm();
	},

	/**
	 * Called by the history class when a change has been made in the form
	 *
	 * Constructs an array out of this component and the children to add it to
	 * the history or to use when saving the form
	 *
	 * @returns {Array}
	 */
	getConfiguration: function() {
		var historyConfiguration = new Array;

		if (this.items) {
			this.items.each(function(item, index, length) {
				historyConfiguration.push(item.getConfiguration());
			}, this);
		}
		return historyConfiguration;
	},

	/**
	 * Load a previous configuration from the history
	 *
	 * Removes all the components from this container and adds the components
	 * from the history configuration depending on the 'undo' or 'redo' action.
	 *
	 * @param historyConfiguration
	 */
	loadConfiguration: function(historyConfiguration) {
		this.removeAll();
		this.add(historyConfiguration);
		this.doLayout();
		this.setForm();
	},

	/**
	 * Pass the form configuration to the left form tab
	 */
	setForm: function() {
		if (Ext.getCmp('formwizard-left-form')) {
			Ext.getCmp('formwizard-left-form').setForm(this.get(0));
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-right', TYPO3.Form.Wizard.Viewport.Right);
