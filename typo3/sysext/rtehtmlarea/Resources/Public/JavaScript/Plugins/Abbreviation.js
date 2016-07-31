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
 * Abbreviation plugin for htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, Util, $, Modal, Severity) {

	var Abbreviation = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(Abbreviation, Plugin);
	Util.apply(Abbreviation.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function(editor) {
			this.pageTSConfiguration = this.editorConfiguration.buttons.abbreviation;
			var removeFieldsets = (this.pageTSConfiguration && this.pageTSConfiguration.removeFieldsets) ? this.pageTSConfiguration.removeFieldsets : '';
			removeFieldsets = removeFieldsets.split(',');
			var fieldsets = ['abbreviation', 'definedAbbreviation', 'acronym' ,'definedAcronym'];
			this.enabledFieldsets = {};
			for (var i = fieldsets.length; --i >= 0;) {
				this.enabledFieldsets[fieldsets[i]] = removeFieldsets.indexOf(fieldsets[i]) === -1;
			}
			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '7.0',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);
			/**
			 * Registering the button
			 */
			var buttonId = 'Abbreviation';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize('Insert abbreviation'),
				action		: 'onButtonPress',
				dialog		: true,
				iconCls		: 'htmlarea-action-abbreviation-edit',
				contextMenuTitle: this.localize(buttonId + '-contextMenuTitle')
			};
			this.registerButton(buttonConfiguration);
			return true;
		 },

		/**
		 * This function gets called when the button was pressed
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 * @return {Boolean} False if action is completed
		 */
		onButtonPress: function(editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			var abbr = this.getCurrentAbbrElement();
			var type = typeof abbr === 'object' && abbr !== null ? abbr.nodeName.toLowerCase() : '';
			this.params = {
				abbr: abbr,
				title: typeof abbr === 'object' && abbr !== null ? abbr.title : '',
				text: typeof abbr === 'object' && abbr !== null ? abbr.innerHTML : this.editor.getSelection().getHtml()
			};
			// Open the dialogue window
			this.openDialogue(
				this.getButton(buttonId).tooltip,
				buttonId,
				this.buildTabItemsConfig(abbr),
				[
					this.buildButtonConfig(this.localize('Cancel'), $.proxy(this.onCancel, this), true),
					this.buildButtonConfig(this.localize('Delete'), $.proxy(this.deleteHandler, this)),
					this.buildButtonConfig(this.localize('OK'), $.proxy(this.okHandler, this), false, Severity.notice)
				],
				type
			);
			return false;
		},

		/**
		 * Get the current abbr or aconym element, if any is selected
		 *
		 * @return {Object} The element or null
		 */
		getCurrentAbbrElement: function() {
			var abbr = this.editor.getSelection().getParentElement();
			// Working around Safari issue
			if (!abbr && this.editor.statusBar && this.editor.statusBar.getSelection()) {
				abbr = this.editor.statusBar.getSelection();
			}
			if (!abbr || !/^(acronym|abbr)$/i.test(abbr.nodeName)) {
				abbr = this.editor.getSelection().getFirstAncestorOfType(['abbr', 'acronym']);
			}
			return abbr;
		},

		/**
		 * Open the dialogue window
		 *
		 * @param {String} title: the window title
		 * @param {String} buttonId: the itemId of the button that was pressed
		 * @param {Object} tabItems: the configuration of the tabbed panel
		 * @param {Object} buttonsConfig: the configuration of the buttons
		 * @param {String} activeTab: itemId of the opening tab
		 */
		openDialogue: function (title, buttonId, tabItems, buttonsConfig, activeTab) {
			this.dialog = Modal.show(title, tabItems, Severity.notice, buttonsConfig);
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
		},

		/**
		 * Build the dialogue tab items config
		 *
		 * @param {Object} element: the element being edited, if any
		 * @return {Object} the tab items configuration
		 */
		buildTabItemsConfig: function (element) {
			var type = typeof element === 'object' && element !== null ? element.nodeName.toLowerCase() : '',
				abbrTabItems = [],
				acronymTabItems = [],
				$finalMarkup,
				$tabs = $('<ul />', {'class': 'nav nav-tabs', role: 'tablist'}),
				$tabContent = $('<div />', {'class': 'tab-content'});

			// abbreviation tab not shown if the current selection is an acronym
			if (type !== 'acronym') {
				// definedAbbreviation fieldset not shown if no pre-defined abbreviation exists
				if (!this.pageTSConfiguration.noAbbr && this.enabledFieldsets['definedAbbreviation']) {
					this.addConfigElement(this.buildDefinedTermFieldsetConfig((type === 'abbr') ? element : null, 'abbr'), abbrTabItems);
				}
				// abbreviation fieldset not shown if the selection is empty or not inside an abbr element
				if ((!this.editor.getSelection().isEmpty() || type === 'abbr') && this.enabledFieldsets['abbreviation']) {
					this.addConfigElement(this.buildUseTermFieldsetConfig((type === 'abbr') ? element : null, 'abbr'), abbrTabItems);
				}
			}

			if (abbrTabItems.length > 0) {
				this.buildTabMarkup($tabs, $tabContent, 'abbr', abbrTabItems, this.localize('Abbreviation'));
			}

			// acronym tab not shown if the current selection is an abbreviation
			if (type !== 'abbr') {
				// definedAcronym fieldset not shown if no pre-defined acronym exists
				if (!this.pageTSConfiguration.noAcronym && this.enabledFieldsets['definedAcronym']) {
					this.addConfigElement(this.buildDefinedTermFieldsetConfig((type === 'acronym') ? element : null, 'acronym'), acronymTabItems);
				}
				// acronym fieldset not shown if the selection is empty or not inside an acronym element
				if ((!this.editor.getSelection().isEmpty() || type === 'acronym') && this.enabledFieldsets['acronym']) {
					this.addConfigElement(this.buildUseTermFieldsetConfig((type === 'acronym') ? element : null, 'acronym'), acronymTabItems);
				}
			}
			if (acronymTabItems.length > 0) {
				this.buildTabMarkup($tabs, $tabContent, 'acronym', acronymTabItems, this.localize('Acronym'));
			}

			$tabs.find('li:first').addClass('active');
			$tabContent.find('.tab-pane:first').addClass('active');

			$finalMarkup = $('<form />', {'class': 'form-horizontal'}).append($tabs, $tabContent);

			return $finalMarkup;
		},

		/**
		 * This function builds the configuration object for the defined Abbreviation or Acronym fieldset
		 *
		 * @param {Object} element: the element being edited, if any
		 * @param {String} type: 'abbr' or 'acronym'
		 * @return {Object} the fieldset configuration object
		 */
		buildDefinedTermFieldsetConfig: function (element, type) {
			var self = this,
				$fieldset = $('<fieldset />');

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).html(this.getHelpTip('preDefined' + ((type == 'abbr') ? 'Abbreviation' : 'Acronym'), 'Defined_' + type))
			);

			$fieldset.append(
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('unabridgedTerm', 'Unabridged_term')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<select />', {name: 'termSelector', 'class': 'form-control'})
							.on('change', $.proxy(this.onTermSelect, this))
					)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('abridgedTerm', 'Abridged_term')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<select />', {name: 'abbrSelector', 'class': 'form-control'})
							.on('change', $.proxy(this.onAbbrSelect, this))
					)
				)
			);

			$.ajax({
				url: this.pageTSConfiguration.abbreviationUrl,
				dataType: 'json',
				success: function (response) {
					var $termSelector = $fieldset.find('select[name="termSelector"]'),
						$abbrSelector = $fieldset.find('select[name="abbrSelector"]');

					for (var item in response.type) {
						if (response.type.hasOwnProperty(item)) {
							var current = response.type[item],
								attributeConfiguration = {
									value: current.term,
									'data-abbr': current.abbr,
									'data-language': current.language
								};
							$termSelector.append(
								$('<option />', attributeConfiguration).text(current.term)
							);

							attributeConfiguration = {
								value: current.abbr,
								'data-term': current.term,
								'data-language': current.language
							};
							$abbrSelector.append(
								$('<option />', attributeConfiguration).text(current.abbr)
							);
						}
					}

					self.onSelectorRender($termSelector);
					self.onSelectorRender($abbrSelector);
				}
			});

			if (this.getButton('Language')) {
				var languageObject = this.getPluginInstance('Language'),
					selectedLanguage = typeof element === 'object' && element !== null
						? languageObject.getLanguageAttribute(element)
						: 'none';

				$fieldset.append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('language', 'Language')),
						$('<div />', {'class': 'col-sm-10'}).append(
							$('<select />', {name: 'language', 'class': 'form-control'})
						)
					)
				);

				$.ajax({
					url: this.getDropDownConfiguration('Language').dataUrl,
					dataType: 'json',
					success: function (response) {
						var $select = $fieldset.find('select[name="language"]');

						for (var language in response.options) {
							if (response.options.hasOwnProperty(language)) {
								if (selectedLanguage !== 'none') {
									response.options[language].value = 'none';
									response.options[language].text = languageObject.localize('Remove language mark');
								}
								var attributeConfiguration = {value: response.options[language].value};
								if (selectedLanguage === response.options[language].value) {
									attributeConfiguration.selected = 'selected';
								}
								$select.append(
									$('<option />', attributeConfiguration).text(response.options[language].text)
								);
							}
						}
					}
				});
			}

			this.onDefinedTermFieldsetRender($fieldset);

			return $fieldset;
		},

		/**
		 * Handler on rendering the defined abbreviation fieldset
		 * If an abbr is selected but no term is selected, select any corresponding term with the correct language value, if any
		 *
		 * @param {Object} fieldset
		 */
		onDefinedTermFieldsetRender: function (fieldset) {
			var termSelector = fieldset.find('[name="termSelector"]');
			var term = termSelector.val();
			var abbrSelector = fieldset.find('[name="abbrSelector"]');
			var abbr = abbrSelector.val();
			var language = '';
			var languageSelector = fieldset.find('[name="language"]');
			if (languageSelector) {
				language = languageSelector.val();
				if (language === 'none') {
					language = '';
				}
			}
			if (abbr && !term) {
				var $activeEl = null;
				abbrSelector.children().each(function(key) {
					var $me = $(this);
					if ($me.data('term') === abbr && (!languageSelector || $me.data('language') === language)) {
						$activeEl = $me;
						return false;
					}
				});
				if ($activeEl !== null) {
					term = $activeEl.data('term');
					termSelector.val(term);
					var useTermField = fieldset.closest('.tab-pane').find('[name="useTerm"]');
					if (useTermField.length) {
						useTermField.val(term);
					}
				}
			}
		},

		/**
		 * Filter the term and abbr selector lists
		 * Set initial values
		 * If there is already an abbr and the filtered list has only one or no element, hide the fieldset
		 */
		onSelectorRender: function (combo) {
			var self = this,
				filteredStore = [],
				index = -1;

			combo.children().each(function() {
				var $me = $(this),
					term = $me.data('term'),
					abbr = $me.data('abbr');

				if (!self.params.text
					|| !self.params.title
					|| self.params.text === term
					|| self.params.title === term
					|| self.params.title === abbr
				) {
					filteredStore.push($me);
				}
			});
			// Initialize the term and abbr combos
			if (combo.attr('name') === 'termSelector') {
				if (this.params.title) {
					for (var i = 0; i < filteredStore.length; ++i) {
						if (filteredStore[i].data('term') === this.params.title) {
							index = i;
							break;
						}
					}

					if (index !== -1) {
						record = filteredStore[i];
						combo.val(record.value);
					}
				} else if (this.params.text) {
					for (var i = 0; i < filteredStore.length; ++i) {
						if (filteredStore[i].data('term') === this.params.text) {
							index = i;
							break;
						}
					}
					if (index !== -1) {
						record = filteredStore[i];
						combo.val(record.value);
					}
				}
			} else if (combo.attr('name') === 'abbrSelector' && this.params.text) {
				for (var i = 0; i < filteredStore.length; ++i) {
					if (filteredStore[i].data('abbr') === this.params.text) {
						index = i;
						break;
					}
				}
				if (index !== -1) {
					var record = filteredStore[index];
					combo.val(record.value);
				}
			}
		},

		/**
		 * Handler when a term is selected
		 *
		 * @param {Event} event
		 */
		onTermSelect: function (event) {
			var $me = $(event.currentTarget),
				fieldset = $me.closest('fieldset'),
				tab = fieldset.closest('.tab-pane'),
				term = $me.data('term'),
				abbr = $me.data('abbr'),
				language = $me.data('language');

			// Update the abbreviation selector
			var abbrSelector = tab.find('[name="abbrSelector"]');
			abbrSelector.val(abbr);

			// Update the language selector
			var languageSelector = tab.find('[name="language"]');
			if (languageSelector.length > 0) {
				if (language) {
					languageSelector.val(language);
				} else {
					languageSelector.val('none');
				}
			}

			// Update the term to use
			var useTermField = tab.find('[name="useTerm"]');
			if (useTermField.length) {
				useTermField.val(term);
			}
		},

		/**
		 * Handler when an abbreviation or acronym is selected
		 *
		 * @param {Event} event
		 */
		onAbbrSelect: function (event) {
			var $me = $(event.currentTarget),
				fieldset = $me.closest('fieldset'),
				tab = fieldset.closest('.tab-pane'),
				term = $me.data('term'),
				language = $me.data('language');

			// Update the term selector
			var termSelector = tab.find('[name="termSelector"]');
			termSelector.val(term);

			// Update the language selector
			var languageSelector = tab.find('[name="language"]');
			if (languageSelector.length > 0) {
				if (language) {
					languageSelector.val(language);
				} else {
					languageSelector.val('none');
				}
			}

			// Update the term to use
			var useTermField = tab.find('[name="useTerm"]');
			if (useTermField.length) {
				useTermField.val(term);
			}
		},

		/**
		 * This function builds the configuration object for the Abbreviation or Acronym to use fieldset
		 *
		 * @param {Object} element The element being edited, if any
		 * @return {Object} The fieldset configuration object
		 */
		buildUseTermFieldsetConfig: function (element) {
			var $fieldset = $('<fieldset />');

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).html(this.getHelpTip('termToAbridge', 'Term_to_abridge')),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).html(this.getHelpTip('useThisTerm', 'Use_this_term')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$('<input />', {name: 'useTerm', 'class': 'form-control', value: element ? element.title : ''})
					)
				)
			);

			return $fieldset;
		},

		/**
		 * Handler when the ok button is pressed
		 *
		 * @param {Event} event
		 */
		okHandler: function (event) {
			this.restoreSelection();
			var tab = this.dialog.find('.tab-pane.active');
			var type = tab.attr('id');
			var languageSelector = tab.find('[name="language"]');
			var language = languageSelector && languageSelector.length > 0 ? languageSelector.val() : '';
			var termSelector = tab.find('[name="termSelector"]');
			var term = termSelector && termSelector.length > 0 ? termSelector.val() : '';
			var abbrSelector = tab.find('[name="abbrSelector"]');
			var useTermField = tab.find('[name="useTerm"]');
			if (!this.params.abbr) {
				var abbr = this.editor.document.createElement(type);
				if (useTermField.length) {
					abbr.title = useTermField.val();
				} else {
					abbr.title = term;
				}
				if (term === abbr.title && abbrSelector && abbrSelector.length > 0) {
					abbr.innerHTML = abbrSelector.val();
				} else {
					abbr.innerHTML = this.params.text;
				}
				if (language) {
					this.getPluginInstance('Language').setLanguageAttributes(abbr, language);
				}
				this.editor.getSelection().insertNode(abbr);
				// Position the cursor just after the inserted abbreviation
				abbr = this.editor.getSelection().getParentElement();
				if (abbr.nextSibling) {
					this.editor.getSelection().selectNodeContents(abbr.nextSibling, true);
				} else {
					this.editor.getSelection().selectNodeContents(abbr.parentNode, false);
				}
			} else {
				var abbr = this.params.abbr;
				if (useTermField.length) {
					abbr.title = useTermField.val();
				} else {
					abbr.title = term;
				}
				if (language) {
					this.getPluginInstance('Language').setLanguageAttributes(abbr, language);
				}
				if (term === abbr.title && abbrSelector && abbrSelector.length > 0) {
					abbr.innerHTML = abbrSelector.val();
				}
			}
			this.close();
			event.stopImmediatePropagation();
		},

		/**
		 * Handler when the delete button is pressed
		 *
		 * @param {Event} event
		 */
		deleteHandler: function (event) {
			this.restoreSelection();
			var abbr = this.params.abbr;
			if (abbr) {
				this.editor.getDomNode().removeMarkup(abbr);
			}
			this.close();
			event.stopImmediatePropagation();
		},

		/**
		 * This function gets called when the toolbar is updated
		 *
		 * @param {Object} button
		 * @param {String} mode
		 */
		onUpdateToolbar: function (button, mode) {
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				var el = this.getCurrentAbbrElement();
				var nodeName = typeof el === 'object' && el !== null ? el.nodeName.toLowerCase() : '';
				// Disable the button if the selection and not inside a abbr or acronym element
				button.setDisabled(
					(this.editor.getSelection().isEmpty() && nodeName !== 'abbr' && nodeName !== 'acronym')
					&& (this.pageTSConfiguration.noAbbr || !this.enabledFieldsets['definedAbbreviation'])
					&& (this.pageTSConfiguration.noAcronym || !this.enabledFieldsets['definedAcronym'])
				);
				button.setInactive(
					!(nodeName === 'abbr' && (this.enabledFieldsets['definedAbbreviation'] || this.enabledFieldsets['abbreviation']))
					&& !(nodeName === 'acronym' && (this.enabledFieldsets['definedAcronym'] || this.enabledFieldsets['acronym']))
				);
				button.setTooltip(this.localize((button.disabled || button.inactive) ? 'Insert abbreviation' : 'Edit abbreviation'));
				button.contextMenuTitle = '';
				if (this.dialog) {
					this.dialog.focus();
				}
			}
		}
	});

	return Abbreviation;
});
