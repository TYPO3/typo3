Ext.namespace('TYPO3.Form.Wizard.Viewport.Left.Form');

/**
 * The post processor accordion panel in the form options in the left tabpanel
 *
 * @class TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessor
 * @extends Ext.Panel
 */
TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessor = Ext.extend(Ext.Panel, {
	/**
	 * @cfg {String} title
	 * The title text to be used as innerHTML (html tags are accepted) to
	 * display in the panel header (defaults to '').
	 */
	title: TYPO3.l10n.localize('form_postprocessor'),

	/**
	 * @cfg {Object} validPostProcessors
	 * Keeps track which post processors are valid. Post processors contain forms which
	 * do client validation. If there is a validation change in a form in the
	 * post processor, a validation event will be fired, which changes one of these
	 * values
	 */
	validPostProcessors: {
		mail: true
	},

	/**
	 * Constructor
	 *
	 * Add the post processors to the accordion
	 */
	initComponent: function() {
		var postProcessors = this.getPostProcessorsBySettings();

			// Adds the specified events to the list of events which this Observable may fire.
		this.addEvents({
			'validation': true
		});

		var config = {
			items: [{
				xtype: 'typo3-form-wizard-viewport-left-form-postprocessors-dummy',
				ref: 'dummy'
			}],
			tbar: [
				{
					xtype: 'combo',
					hideLabel: true,
					name: 'postprocessor',
					ref: 'postprocessor',
					mode: 'local',
					triggerAction: 'all',
					forceSelection: true,
					editable: false,
					hiddenName: 'postprocessor',
					emptyText: TYPO3.l10n.localize('postprocessor_emptytext'),
					width: 150,
					displayField: 'label',
					valueField: 'value',
					store: new Ext.data.JsonStore({
						fields: ['label', 'value'],
						data: postProcessors
					}),
					listeners: {
						'select': {
							scope: this,
							fn: this.addPostProcessor
						}
					}
				}
			]
		};

			// apply config
		Ext.apply(this, Ext.apply(this.initialConfig, config));

			// call parent
		TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessor.superclass.initComponent.apply(this, arguments);

			// Initialize the post processors when they are available for this element
		this.initPostProcessors();
	},

	/**
	 * Called when constructing the post processor accordion
	 *
	 * Checks if the form already has post processors and loads these instead of the dummy
	 */
	initPostProcessors: function() {
		var postProcessors = this.element.configuration.postProcessor;
		if (!Ext.isEmptyObject(postProcessors)) {
			this.remove(this.dummy);
			Ext.iterate(postProcessors, function(key, value) {
				var xtype = 'typo3-form-wizard-viewport-left-form-postprocessors-' + key;
				if (Ext.ComponentMgr.isRegistered(xtype)) {
					this.add({
						xtype: xtype,
						element: this.element,
						configuration: value,
						listeners: {
							'validation': {
								fn: this.validation,
								scope: this
							}
						}
					});
				}
			}, this);
		}
	},

	/**
	 * Add a post processor to the list
	 *
	 * @param comboBox
	 * @param record
	 * @param index
	 */
	addPostProcessor: function(comboBox, record, index) {
		var postProcessor = comboBox.getValue();
		var xtype = 'typo3-form-wizard-viewport-left-form-postprocessors-' + postProcessor;

		if (!Ext.isEmpty(this.findByType(xtype))) {
			Ext.MessageBox.alert(TYPO3.l10n.localize('postprocessor_alert_title'), TYPO3.l10n.localize('postprocessor_alert_description'));
		} else {
			this.remove(this.dummy);

			this.add({
				xtype: xtype,
				element: this.element,
				listeners: {
					'validation': {
						fn: this.validation,
						scope: this
					}
				}
			});
			this.doLayout();
		}
	},

	/**
	 * Remove a post processor from the list
	 *
	 * Shows dummy when there is no post processor for the form
	 *
	 * @param component
	 */
	removePostProcessor: function(component) {
		this.remove(component);
		this.validation(component.processor, true);
		if (this.items.length == 0) {
			this.add({
				xtype: 'typo3-form-wizard-viewport-left-form-postprocessors-dummy',
				ref: 'dummy'
			});
		}
		this.doLayout();
	},

	getPostProcessorsBySettings: function() {
		var postProcessors = [];

		var allowedPostProcessors = [];
		try {
			allowedPostProcessors = TYPO3.Form.Wizard.Settings.defaults.tabs.form.accordions.postProcessor.showPostProcessors.split(/[, ]+/);
		} catch (error) {
			// The object has not been found
			allowedPostProcessors = [
				'mail'
			];
		}

		Ext.iterate(allowedPostProcessors, function(item, index, allItems) {
			postProcessors.push({label: TYPO3.l10n.localize('postprocessor_' + item), value: item});
		}, this);

		return postProcessors;
	},

	/**
	 * Called by the validation listeners of the post processors
	 *
	 * Checks if all post processors are valid. If not, adds a class to the accordion
	 *
	 * @param {String} postProcessor The post processor which fires the event
	 * @param {Boolean} isValid Post processor is valid or not
	 */
	validation: function(postProcessor, isValid) {
		this.validPostProcessors[postProcessor] = isValid;
		var accordionIsValid = true;
		Ext.iterate(this.validPostProcessors, function(key, value) {
			if (!value) {
				accordionIsValid = false;
			}
		}, this);
		if (this.el) {
			if (accordionIsValid && this.el.hasClass('validation-error')) {
				this.removeClass('validation-error');
				this.fireEvent('validation', 'postProcessor', accordionIsValid);
			} else if (!accordionIsValid && !this.el.hasClass('validation-error')) {
				this.addClass('validation-error');
				this.fireEvent('validation', 'postProcessor', accordionIsValid);
			}
		}
	}
});

Ext.reg('typo3-form-wizard-viewport-left-form-postprocessor', TYPO3.Form.Wizard.Viewport.Left.Form.PostProcessor);