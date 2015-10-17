Ext.namespace('TYPO3.Form.Wizard.Elements.Predefined');

/**
 * The predefined NAME element
 *
 * @class TYPO3.Form.Wizard.Elements.Predefined.Name
 * @extends TYPO3.Form.Wizard.Elements.Basic.Fieldset
 */
TYPO3.Form.Wizard.Elements.Predefined.Name = Ext.extend(TYPO3.Form.Wizard.Elements.Basic.Fieldset, {
	/**
	 * Initialize the component
	 */
	initComponent: function() {
		var config = {
			configuration: {
				attributes: {
					"class": 'predefined-name fieldset-subgroup fieldset-horizontal label-below',
					dir: '',
					id: '',
					lang: '',
					style: ''
				},
				legend: {
					value: TYPO3.l10n.localize('elements_legend_name')
				},
				various: {
					prefix: true,
					suffix: true,
					middleName: true
				}
			}
		};

			// apply config
		Ext.apply(this, Ext.apply(config, this.initialConfig));

			// call parent
		TYPO3.Form.Wizard.Elements.Predefined.Name.superclass.initComponent.apply(this, arguments);

		this.on('configurationChange', this.rebuild, this);

		this.on('afterrender', this.rebuild, this);
	},

	/**
	 * Add the fields to the containerComponent of this fieldset,
	 * according to the configuration options.
	 *
	 * @param component
	 */
	rebuild: function(component) {
		this.containerComponent.removeAll();
		var dummy = this.containerComponent.findById('dummy');
		if (dummy) {
			this.containerComponent.remove(dummy, true);
		}
		if (this.configuration.various.prefix) {
			var prefix = this.containerComponent.add({
				xtype: 'typo3-form-wizard-elements-basic-textline',
				isEditable: false,
				cls: '',
				configuration: {
					label: {
						value: TYPO3.l10n.localize('elements_label_prefix')
					},
					attributes: {
						name: 'prefix',
						size: 4
					},
					layout: 'back'
				}
			});
		}
		var firstName = this.containerComponent.add({
			xtype: 'typo3-form-wizard-elements-basic-textline',
			isEditable: false,
			cls: '',
			configuration: {
				label: {
					value: TYPO3.l10n.localize('elements_label_firstname')
				},
				attributes: {
					name: 'firstName',
					size: 10
				},
				layout: 'back',
				validation: {
					required: {
						showMessage: true,
						message: '*',
						error: 'Required'
					}
				}
			}
		});
		if (this.configuration.various.middleName) {
			var middleName = this.containerComponent.add({
				xtype: 'typo3-form-wizard-elements-basic-textline',
				isEditable: false,
				cls: '',
				configuration: {
					label: {
						value: TYPO3.l10n.localize('elements_label_middlename')
					},
					attributes: {
						name: 'middleName',
						size: 6
					},
					layout: 'back'
				}
			});
		}
		var lastName = this.containerComponent.add({
			xtype: 'typo3-form-wizard-elements-basic-textline',
			isEditable: false,
			cls: '',
			configuration: {
				label: {
					value: TYPO3.l10n.localize('elements_label_lastname')
				},
				attributes: {
					name: 'lastName',
					size: 15
				},
				layout: 'back',
				validation: {
					required: {
						showMessage: true,
						message: '*',
						error: 'Required'
					}
				}
			}
		});
		if (this.configuration.various.suffix) {
			var suffix = this.containerComponent.add({
				xtype: 'typo3-form-wizard-elements-basic-textline',
				isEditable: false,
				cls: '',
				configuration: {
					label: {
						value: TYPO3.l10n.localize('elements_label_suffix')
					},
					attributes: {
						name: 'suffix',
						size: 4
					},
					layout: 'back'
				}
			});
		}
		this.containerComponent.doLayout();
	}
});

Ext.reg('typo3-form-wizard-elements-predefined-name', TYPO3.Form.Wizard.Elements.Predefined.Name);