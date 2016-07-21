/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Microdata Schema Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, UserAgent, Dom, Util, $, Modal, Severity) {

	var MicrodataSchema = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(MicrodataSchema, Plugin);
	Util.apply(MicrodataSchema.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '1.0',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);

			/**
			 * Registering the buttons
			 */
			var button = this.buttonList[0];
			var buttonId = button[0];
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + '-Tooltip'),
				iconCls		: 'htmlarea-action-' + button[2],
				action		: 'onButtonPress',
				context		: button[1]
			};
			this.registerButton(buttonConfiguration);
			return true;
		},

		/**
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['ShowMicrodata', null, 'microdata-show']
		],

		itemtype: [],
		itemprop: [],
		filteredProperties: [],
		/**
		 * This function gets called when the editor is generated
		 */
		onGenerate: function () {
			var self = this;
			$.ajax({
				url: this.editorConfiguration.schemaUrl,
				dataType: 'json',
				success: function (response) {
					self.itemtype = response.types;
					self.itemprop = response.properties;
					self.addMicrodataMarkingRules('itemtype', response.types);
					self.addMicrodataMarkingRules('itemprop', response.properties);
				}
			});
		},
		/**
		 * This function adds rules to the stylesheet for language mark highlighting
		 * Model: body.htmlarea-show-language-marks *[lang=en]:before { content: "en: "; }
		 * Works in IE8, but not in earlier versions of IE
		 *
		 * @param {String} category
		 * @param {Array} items
		 */
		addMicrodataMarkingRules: function (category, items) {
			var styleSheet = this.editor.document.styleSheets[0];
			$.each(items, function (_, option) {
				var selector = 'body.htmlarea-show-microdata *[' + category + '="' + option.name + '"]:before';
				var style = 'content: "' + option.label + ': "; font-variant: small-caps;';
				var rule = selector + ' { ' + style + ' }';
				try {
					styleSheet.insertRule(rule, styleSheet.cssRules.length);
				} catch (e) {
					this.appendToLog('onGenerate', 'Error inserting css rule: ' + rule + ' Error text: ' + e, 'warn');
				}
			});
		},

		/**
		 * This function gets called when a button was pressed.
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 * @return {Boolean} false if action is completed
		 */
		onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			switch (buttonId) {
				case 'ShowMicrodata':
					this.toggleMicrodata();
					break;
			}
			return false;
		},

		/**
		 * Toggles the display of microdata
		 *
		 * @param {Boolean} forceMicrodata If set, microdata is displayed whatever the current state
		 */
		toggleMicrodata: function (forceMicrodata) {
			var body = this.editor.document.body;
			if (!Dom.hasClass(body, 'htmlarea-show-microdata')) {
				Dom.addClass(body,'htmlarea-show-microdata');
			} else if (!forceMicrodata) {
				Dom.removeClass(body,'htmlarea-show-microdata');
			}
		},
		/**
		 * This function builds the configuration object for the Microdata fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @param {Object} properties configured properties for the microdata fields
		 * @return {Object} the fieldset configuration object
		 */
		buildMicrodataFieldsetConfig: function (element, properties) {
			var $fieldset = $('<fieldset />');
			var typeStore = this.itemtype;
			var propertyStore = this.filteredProperties.length > 0 ? this.filteredProperties : this.itemprop;
			this.inheritedType = 'none';
			var parent = element.parentNode;
			while (parent && !/^(html|body)$/i.test(parent.nodeName)) {
				if (parent.getAttribute('itemtype')) {
					this.inheritedType = parent.getAttribute('itemtype');
					break;
				} else {
					parent = parent.parentNode;
				}
			}
			var selectedType = element && element.getAttribute('itemtype') ? element.getAttribute('itemtype') : 'none';
			var selectedProperty = element && element.getAttribute('itemprop') ? element.getAttribute('itemprop') : 'none';

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('microdata')),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('currentItemType', 'currentItemType')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<p />', {'class': 'form-control-static'}).text(this.inheritedType)
					)
				)
			);

			var $itemPropSelect = $('<select />', {name: 'itemprop', 'class': 'form-control'});
			this.attachItemProperties($itemPropSelect, propertyStore, selectedProperty);

			$fieldset.append(
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('itemprop', 'itemprop')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$itemPropSelect
					)
				).toggle(this.inheritedType !== 'none')
			);

			$fieldset.append(
				$('<div />', {'class': 'form-group col-sm-12'}).append(
					$('<div />', {'class': 'checkbox'}).append(
						$('<label />').append(
							$('<span />').html(this.getHelpTip('itemscope', 'itemscope'))
						).prepend(
							$('<input />', {type: 'checkbox', name: 'itemscope'})
								.prop('checked', element ? (element.getAttribute('itemscope') === 'itemscope') : false)
								.on('click', $.proxy(this.onItemScopeChecked, this))
						)
					)
				)
			);

			var $itemTypeSelect = $('<select />', {name: 'itemtype', 'class': 'form-control'});
			for (var i = 0; i < typeStore.length; ++i) {
				var attributeConfiguration = {
					value: typeStore[i].name
				};

				if (typeStore[i].name === selectedType) {
					attributeConfiguration.selected = 'selected';
				}

				$itemTypeSelect.append(
					$('<option />', attributeConfiguration).text(typeStore[i].label)
				);
			}
			$fieldset.append(
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('itemtype', 'itemtype')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$itemTypeSelect
					)
				).toggle(!(element && !element.getAttribute('itemscope')))
			);

			this.onMicroDataRender($fieldset);

			return $fieldset;
		},
		attachItemProperties: function($select, properties, selectedProperty) {
			$select.empty();

			for (var i = 0; i < properties.length; ++i) {
				var attributeConfiguration = {
					value: properties[i].name
				};

				if (properties[i].name === selectedProperty) {
					attributeConfiguration.selected = 'selected';
				}

				$select.append(
					$('<option />', attributeConfiguration).text(properties[i].label)
				);
			}
		},
		/**
		 * Handler invoked when the Microdata fieldset is rendered
		 *
		 * @param {Object} $fieldset
		 */
		onMicroDataRender: function ($fieldset) {
			this.fieldset = $fieldset;
			var typeStore = this.itemtype,
				index = -1;

			for (var i = 0; i < typeStore.length; ++i) {
				if (typeStore[i].name === this.inheritedType) {
					index = i;
					break;
				}
			}

			if (index !== -1) {
				// If there is an inherited type, set the label
				var inheritedTypeName = typeStore[index].label;
				this.fieldset.find('[name="currentItemType"]').val(inheritedTypeName);

				// Filter the properties by the inherited type, if any
				var propertyCombo = this.fieldset.find('[name="itemprop"]');
				var selectedProperty = propertyCombo.val();

				// Filter the properties by the inherited type, if any
				this.filterProperties(this.inheritedType, selectedProperty);
			}
		},
		/**
		 * Handler invoked when the itemscope checkbox is checked/unchecked
		 *
		 * @param {Event} e
		 */
		onItemScopeChecked: function (e) {
			this.fieldset.find('[name="itemtype"]').closest('.form-group').toggle($(e.currentTarget).prop('checked'));
		},
		/**
		 * Filter out properties not part of the selected type
		 */
		filterProperties: function (type, selectedProperty) {
			var self = this,
				typeStore = this.itemtype,
				propertyStore = this.itemprop,
				index = -1,
				superType = type,
				superTypes = [];

			if (this.filteredProperties.length > 0) {
				this.filteredProperties = [];
			}

			while (superType) {
				superTypes.push(superType);
				for (var i = 0; i < typeStore.length; ++i) {
					if (typeStore[i].name === superType) {
						index = i;
						break;
					}
				}
				if (index !== -1) {
					superType = typeStore[index].subClassOf;
				} else {
					superType = null;
				}
			}
			superTypes = new RegExp( '^(' + superTypes.join('|') + ')$', 'i');

			$.each(propertyStore, function() {
				// Filter out properties not part of the type
				if (superTypes.test(this.domain) || this.name === 'none') {
					self.filteredProperties.push(this);
				}
			});

			// Make sure the combo list is filtered
			var propertyCombo = this.fieldset.find('[name="itemprop"]');
			this.attachItemProperties(propertyCombo, this.filteredProperties, selectedProperty);
		},

		/**
		 * Set microdata attributes of the element
		 */
		setMicrodataAttributes: function (element) {
			if (this.fieldset) {
				var comboFields = this.fieldset.find('select');
				for (var i = comboFields.length; --i >= 0;) {
					var field = $(comboFields[i]);
					var itemId = field.attr('name');
					var value = field.val();
					switch (itemId) {
						case 'itemprop':
						case 'itemtype':
							element.setAttribute(itemId, value === 'none' ? '' : value);
							break;
					}
				}
				var itemScopeField = this.fieldset.find('[name="itemscope"]');
				if (itemScopeField) {
					if (itemScopeField.prop('checked')) {
						element.setAttribute('itemscope', 'itemscope');
					} else {
						element.removeAttribute('itemscope');
						element.removeAttribute('itemtype');
					}
				}
			}
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
			if (this.getEditorMode() === 'wysiwyg' && this.editor.isEditable()) {
				switch (button.itemId) {
					case 'ShowMicrodata':
						button.setInactive(!Dom.hasClass(this.editor.document.body, 'htmlarea-show-microdata'));
						break;
					default:
						break;
				}
			}
		}
	});

	return MicrodataSchema;
});
