Ext.namespace('TYPO3.Form.Wizard.Elements.Predefined');

/**
 * The predefined RADIO GROUP element
 *
 * @class TYPO3.Form.Wizard.Elements.Predefined.RadioGroup
 * @extends TYPO3.Form.Wizard.Elements.Basic.Fieldset
 */
TYPO3.Form.Wizard.Elements.Predefined.RadioGroup = Ext.extend(TYPO3.Form.Wizard.Elements.Basic.Fieldset, {
	/**
	 * @cfg {Mixed} tpl
	 * An Ext.Template, Ext.XTemplate or an array of strings to form an
	 * Ext.XTemplate. Used in conjunction with the data and tplWriteMode
	 * configurations.
	 */
	tpl: new Ext.XTemplate(
		'<div class="overflow-hidden">',
			'<fieldset {[this.getAttributes(values.attributes)]}>',
			'<tpl for="legend">',
				'<tpl if="value">',
					'<legend>{value}{[this.getMessage(parent.validation)]}</legend>',
				'</tpl>',
			'</tpl>',
			'<ol></ol>',
			'</fieldset>',
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
	 * Initialize the component
	 */
	initComponent: function() {
		var config = {
			elementContainer: {
				hasDragAndDrop: false
			},
			configuration: {
				attributes: {
					"class": 'fieldset-subgroup',
					dir: '',
					id: '',
					lang: '',
					style: ''
				},
				legend: {
					value: TYPO3.l10n.localize('elements_legend')
				},
				options: [
					{
						text: TYPO3.l10n.localize('elements_option_1'),
						attributes: {
							value: TYPO3.l10n.localize('elements_value_1')
						}
					},{
						text: TYPO3.l10n.localize('elements_option_2'),
						attributes: {
							value: TYPO3.l10n.localize('elements_value_2')
						}
					},{
						text: TYPO3.l10n.localize('elements_option_3'),
						attributes: {
							value: TYPO3.l10n.localize('elements_value_3')
						}
					}
				],
				various: {
					name: ''
				},
				validation: {}
			}
		};

			// apply config
		Ext.apply(this, Ext.apply(config, this.initialConfig));

			// call parent
		TYPO3.Form.Wizard.Elements.Predefined.RadioGroup.superclass.initComponent.apply(this, arguments);

		this.on('configurationChange', this.rebuild, this);

		this.on('afterrender', this.rebuild, this);
	},

	/**
	 * Add the radio buttons to the containerComponent of this fieldset,
	 * according to the configuration options.
	 *
	 * @param component
	 */
	rebuild: function(component) {
		this.containerComponent.removeAll();
		if (this.configuration.options.length > 0) {
			var dummy = this.containerComponent.findById('dummy');
			if (dummy) {
				this.containerComponent.remove(dummy, true);
			}
			Ext.each(this.configuration.options, function(option, index, length) {
				var radio = this.containerComponent.add({
					xtype: 'typo3-form-wizard-elements-basic-radio',
					isEditable: false,
					cls: ''
				});
				var optionValue = '';
				if (option.attributes && option.attributes.value) {
					optionValue = option.attributes.value;
				}
				var radioConfiguration = {
					label: {
						value: option.text
					},
					attributes: {
						value: optionValue
					}
				};
				if (
					option.attributes &&
					option.attributes.selected &&
					option.attributes.selected == 'selected'
				) {
					radioConfiguration.attributes.checked = 'checked';
				}
				Ext.merge(radio.configuration, radioConfiguration);
			}, this);
			this.containerComponent.doLayout();
		}
	}
});

Ext.reg('typo3-form-wizard-elements-predefined-radiogroup', TYPO3.Form.Wizard.Elements.Predefined.RadioGroup);