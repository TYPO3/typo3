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
 * Text Style Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/CSS/Parser',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, UserAgent, Dom, Event, Parser, Util) {

	var TextStyle = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(TextStyle, Plugin);
	Util.apply(TextStyle.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.cssArray = {};
			this.classesUrl = this.editorConfiguration.classesUrl;
			this.pageTSconfiguration = this.editorConfiguration.buttons.textstyle;
			this.tags = (this.pageTSconfiguration && this.pageTSconfiguration.tags) ? this.pageTSconfiguration.tags : {};
			var allowedClasses;
			for (var tagName in this.tags) {
				if (this.tags[tagName].allowedClasses) {
					allowedClasses = this.tags[tagName].allowedClasses.trim().split(",");
					for (var i = allowedClasses.length; --i >= 0;) {
						allowedClasses[i] = allowedClasses[i].trim().replace(/\*/g, ".*");
					}
					this.tags[tagName].allowedClasses = new RegExp( "^(" + allowedClasses.join("|") + ")$", "i");
				}
			}
			this.showTagFreeClasses = this.pageTSconfiguration ? this.pageTSconfiguration.showTagFreeClasses : false;
			this.prefixLabelWithClassName = this.pageTSconfiguration ? this.pageTSconfiguration.prefixLabelWithClassName : false;
			this.postfixLabelWithClassName = this.pageTSconfiguration ? this.pageTSconfiguration.postfixLabelWithClassName : false;

			// Regular expression to check if an element is an inline element
			this.REInlineTags = /^(a|abbr|acronym|b|bdo|big|cite|code|del|dfn|em|i|img|ins|kbd|q|samp|small|span|strike|strong|sub|sup|tt|u|var)$/;

			// Allowed attributes on inline elements
			this.allowedAttributes = new Array('id', 'title', 'lang', 'xml:lang', 'dir', 'class', 'itemscope', 'itemtype', 'itemprop');

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.3',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: this.localize('Technische Universitat Ilmenau'),
				sponsorUrl	: 'http://www.tu-ilmenau.de/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);

			/**
			 * Registering the dropdown list
			 */
			var buttonId = 'TextStyle';
			var fieldLabel = this.pageTSconfiguration ? this.pageTSconfiguration.fieldLabel : '';
			if ((typeof fieldLabel !== 'string' || !fieldLabel.length) && this.isButtonInToolbar('I[text_style]')) {
				fieldLabel = this.localize('text_style');
			}
			var dropDownConfiguration = {
				id: buttonId,
				tooltip: this.localize(buttonId + '-Tooltip'),
				fieldLabel: fieldLabel,
				options: [[this.localize('No style'), 'none']],
				action: 'onChange'
			};
			if (this.pageTSconfiguration) {
				if (this.pageTSconfiguration.width) {
					dropDownConfiguration.width = parseInt(this.pageTSconfiguration.width, 10);
				}
				if (this.pageTSconfiguration.listWidth) {
					dropDownConfiguration.listWidth = parseInt(this.pageTSconfiguration.listWidth, 10);
				}
				if (this.pageTSconfiguration.maxHeight) {
					dropDownConfiguration.maxHeight = parseInt(this.pageTSconfiguration.maxHeight, 10);
				}
			}
			this.registerDropDown(dropDownConfiguration);
			return true;
		},

		/**
		 * Determine whether the element is an inline element
		 *
		 * @param object el: the element
		 * @return boolen true if the element is an inline element
		 */
		isInlineElement: function (el) {
			return el && (el.nodeType === Dom.ELEMENT_NODE) && this.REInlineTags.test(el.nodeName.toLowerCase());
		},

		/**
		 * This function adds an attribute to the array of allowed attributes on inline elements
		 *
		 * @param	string	attribute: the name of the attribute to be added to the array
		 *
		 * @return	void
		 */
		addAllowedAttribute: function (attribute) {
			this.allowedAttributes.push(attribute);
		},

		/**
		 * This function gets called when some style in the drop-down list applies it to the highlighted textt
		 */
		onChange: function (editor, select) {
			var className = select.getValue();
			var classNames = null;
			var fullNodeSelected = false;
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
			var range = this.editor.getSelection().createRange();
			var parent = this.editor.getSelection().getParentElement();
			var selectionEmpty = this.editor.getSelection().isEmpty();
			var ancestors = this.editor.getSelection().getAllAncestors();

			if (!selectionEmpty) {
					// The selection is not empty
				for (var i = 0; i < ancestors.length; ++i) {
					fullNodeSelected = (statusBarSelection === ancestors[i] && ancestors[i].textContent === range.toString()) || (!statusBarSelection && ancestors[i].textContent === range.toString());
					if (fullNodeSelected) {
						if (this.isInlineElement(ancestors[i])) {
							parent = ancestors[i];
						}
						break;
					}
				}
					// Working around bug in Safari selectNodeContents
				if (!fullNodeSelected && UserAgent.isWebKit && statusBarSelection && this.isInlineElement(statusBarSelection) && statusBarSelection.textContent === range.toString()) {
					fullNodeSelected = true;
					parent = statusBarSelection;
				}
			}
			if (!selectionEmpty && !fullNodeSelected || (!selectionEmpty && fullNodeSelected && parent && Dom.isBlockElement(parent))) {
					// The selection is not empty, nor full element, or the selection is full block element
				if (className !== 'none') {
						// Add span element with class attribute
					var newElement = editor.document.createElement('span');
					Dom.addClass(newElement, className);
					editor.getDomNode().wrapWithInlineElement(newElement, range);
					range.detach();
				}
			} else {
				this.applyClassChange(parent, className);
			}
		},

		/**
		 * This function applies the class change to the node
		 *
		 * @param	object	node: the node on which to apply the class change
		 * @param	string	className: the class to add, 'none' to remove the last class added to the class attribute
		 * @param	boolean	noRemove: true not to remove a span element with no more attribute
		 *
		 * @return	void
		 */
		applyClassChange: function (node, className, noRemove) {
				// Add or remove class
			if (node && !Dom.isBlockElement(node)) {
				if (className === 'none' && node.className && /\S/.test(node.className)) {
					classNames = node.className.trim().split(' ');
					Dom.removeClass(node, classNames[classNames.length-1]);
				}
				if (className !== 'none') {
					Dom.addClass(node, className);
				}
					// Remove the span tag if it has no more attribute
				if (/^span$/i.test(node.nodeName) && !Dom.hasAllowedAttributes(node, this.allowedAttributes) && !noRemove) {
					this.editor.getDomNode().removeMarkup(node);
				}
			}
		},

		/**
		 * This function gets called when the plugin is generated
		 * Get the classes configuration and initiate the parsing of the style sheets
		 */
		onGenerate: function () {
			var self = this;
			// Monitor editor changing mode
			Event.on(this.editor, 'HTMLAreaEventModeChange', function (event, mode) { Event.stopEvent(event); self.onModeChange(mode); return false; });
			// Create CSS Parser object
			this.textStyles = new Parser({
				prefixLabelWithClassName: this.prefixLabelWithClassName,
				postfixLabelWithClassName: this.postfixLabelWithClassName,
				showTagFreeClasses: this.showTagFreeClasses,
				tags: this.tags,
				editor: this.editor
			});
			// Disable the dropdown while initialization completes
			var dropDown = this.getButton('TextStyle');
			if (dropDown) {
				dropDown.setDisabled(true);
			}
			// Monitor css parsing being completed
			Event.one(this.textStyles, 'HTMLAreaEventCssParsingComplete', function (event) { Event.stopEvent(event); self.onCssParsingComplete(); return false; }); 
			this.textStyles.parse();
		},

		/**
		 * This handler gets called when parsing of css classes is completed
		 */
		onCssParsingComplete: function () {
			if (this.textStyles.isReady()) {
				this.cssArray = this.textStyles.getClasses();
				if (this.getEditorMode() === 'wysiwyg' && this.editor.isEditable()) {
					this.updateToolbar('TextStyle');
				}
			}
		},

		/**
		 * This handler gets called when the toolbar is being updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if (mode === 'wysiwyg' && this.editor.isEditable() && this.textStyles.isReady()) {
				this.updateToolbar(button.itemId);
			}
		},

		/**
		 * This handler gets called when the editor has changed its mode to "wysiwyg"
		 */
		onModeChange: function (mode) {
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				this.updateToolbar('TextStyle');
			}
		},

		/**
		* This function gets called when the drop-down list needs to be refreshed
		*/
		updateToolbar: function (dropDownId) {
			var editor = this.editor;
			if (this.getEditorMode() === "wysiwyg" && this.editor.isEditable()) {
				var tagName = false, classNames = Array(), fullNodeSelected = false;
				var statusBarSelection = editor.statusBar ? editor.statusBar.getSelection() : null;
				var range = editor.getSelection().createRange();
				var parent = editor.getSelection().getParentElement();
				var ancestors = editor.getSelection().getAllAncestors();
				if (parent && !Dom.isBlockElement(parent)) {
					tagName = parent.nodeName.toLowerCase();
					if (parent.className && /\S/.test(parent.className)) {
						classNames = parent.className.trim().split(" ");
					}
				}
				var selectionEmpty = editor.getSelection().isEmpty();
				if (!selectionEmpty) {
					for (var i = 0; i < ancestors.length; ++i) {
						fullNodeSelected = (statusBarSelection === ancestors[i]) && ancestors[i].textContent === range.toString();
						if (fullNodeSelected) {
							if (!Dom.isBlockElement(ancestors[i])) {
								tagName = ancestors[i].nodeName.toLowerCase();
								if (ancestors[i].className && /\S/.test(ancestors[i].className)) {
									classNames = ancestors[i].className.trim().split(" ");
								}
							}
							break;
						}
					}
						// Working around bug in Safari selectNodeContents
					if (!fullNodeSelected && UserAgent.isWebKit && statusBarSelection && this.isInlineElement(statusBarSelection) && statusBarSelection.textContent === range.toString()) {
						fullNodeSelected = true;
						tagName = statusBarSelection.nodeName.toLowerCase();
						if (statusBarSelection.className && /\S/.test(statusBarSelection.className)) {
							classNames = statusBarSelection.className.trim().split(" ");
						}
					}
				}
				var selectionInInlineElement = tagName && this.REInlineTags.test(tagName);
				var disabled = !editor.getSelection().endPointsInSameBlock() || (fullNodeSelected && !tagName) || (selectionEmpty && !selectionInInlineElement);
				if (!disabled && !tagName) {
					tagName = "span";
				}
				this.updateValue(dropDownId, tagName, classNames, selectionEmpty, fullNodeSelected, disabled);
			} else {
				var dropDown = this.getButton(dropDownId);
				if (dropDown) {
					dropDown.setDisabled(!dropDown.textMode);
				}
			}
		},

		/**
		 * This function reinitializes the options of the dropdown
		 */
		initializeDropDown: function (dropDown) {
			switch (dropDown.xtype) {
				case 'htmlareaselect':
					dropDown.removeAll();
					dropDown.setFirstOption(this.localize('No style'), 'none', this.localize('No style'));
					dropDown.setValue('none');
					break;
				case 'combo':
					var store = dropDown.getStore();
					store.removeAll(false);
					store.insert(0, new store.recordType({
						text: this.localize('No style'),
						value: 'none'
					}));
					dropDown.setValue('none');
			}
		},

		/**
		 * This function builds the options to be displayed in the dropDown box
		 */
		buildDropDownOptions: function (dropDown, nodeName) {
			this.initializeDropDown(dropDown);
			switch (dropDown.xtype) {
				case 'htmlareaselect':
					if (this.textStyles.isReady()) {
						var allowedClasses = {};
						if (this.REInlineTags.test(nodeName)) {
							if (typeof this.cssArray[nodeName] !== 'undefined') {
								allowedClasses = this.cssArray[nodeName];
							} else if (this.showTagFreeClasses && typeof this.cssArray['all'] !== 'undefined') {
								allowedClasses = this.cssArray['all'];
							}
						}
						for (var cssClass in allowedClasses) {
							if (typeof HTMLArea.classesSelectable[cssClass] === 'undefined' || HTMLArea.classesSelectable[cssClass]) {
								var style = (!(this.pageTSconfiguration && this.pageTSconfiguration.disableStyleOnOptionLabel) && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) ? HTMLArea.classesValues[cssClass] : null;
								dropDown.addOption(allowedClasses[cssClass], cssClass, cssClass, style);
							}
						}
					}
					break;
				case 'combo':
					var store = dropDown.getStore();
					if (this.textStyles.isReady()) {
						var allowedClasses = {};
						if (this.REInlineTags.test(nodeName)) {
							if (typeof this.cssArray[nodeName] !== 'undefined') {
								allowedClasses = this.cssArray[nodeName];
							} else if (this.showTagFreeClasses && typeof this.cssArray['all'] !== 'undefined') {
								allowedClasses = this.cssArray['all'];
							}
						}
						for (var cssClass in allowedClasses) {
							if (typeof HTMLArea.classesSelectable[cssClass] === 'undefined' || HTMLArea.classesSelectable[cssClass]) {
								store.add(new store.recordType({
									text: allowedClasses[cssClass],
									value: cssClass,
									style: (!(this.pageTSconfiguration && this.pageTSconfiguration.disableStyleOnOptionLabel) && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) ? HTMLArea.classesValues[cssClass] : null
								}));
							}
						}
					}
			}
		},

		/**
		 * This function sets the selected option of the dropDown box
		 */
		setSelectedOption: function (dropDown, classNames, noUnknown, defaultClass) {
			switch (dropDown.xtype) {
				case 'htmlareaselect':
					dropDown.setValue('none');
					if (classNames.length) {
						var index = dropDown.findValue(classNames[classNames.length-1]);
						if (index !== -1) {
							dropDown.setValueByIndex(index);
							if (!defaultClass) {
								var text = this.localize('Remove style');
								dropDown.setFirstOption(text, 'none', text);
							}
						}
						if (index === -1 && !noUnknown) {
							var text = this.localize('Unknown style');
							var value = classNames[classNames.length-1];
							if (typeof HTMLArea.classesSelectable[value] !== 'undefined' && !HTMLArea.classesSelectable[value] && typeof HTMLArea.classesLabels[value] !== 'undefined') {
								text = HTMLArea.classesLabels[value];
							}
							var style = (!(this.pageTSconfiguration && this.pageTSconfiguration.disableStyleOnOptionLabel) && HTMLArea.classesValues && HTMLArea.classesValues[value] && !HTMLArea.classesNoShow[value]) ? HTMLArea.classesValues[value] : null;
							dropDown.addOption(text, value, value, style);
							dropDown.setValue(value);
							if (!defaultClass) {
								text = this.localize('Remove style');
								dropDown.setFirstOption(text, 'none', text);
							}
						}
						// Remove already assigned classes from the dropDown box
						var selectedValue = dropDown.getValue();
						for (var i = 0, n = classNames.length; i < n; i++) {
							index = dropDown.findValue(classNames[i]);
							if (index !== -1) {
								if (dropDown.getOptionValue(index) !== selectedValue) {
									dropDown.removeAt(index);
								}
							}
						}
					}
					dropDown.setDisabled(!dropDown.getCount() || (dropDown.getCount() === 1 && dropDown.getValue() === 'none'));
					break;
				case 'combo':
					var store = dropDown.getStore();
					dropDown.setValue('none');
					if (classNames.length) {
						var index = store.findExact('value', classNames[classNames.length-1]);
						if (index !== -1) {
							dropDown.setValue(classNames[classNames.length-1]);
							if (!defaultClass) {
								store.getAt(0).set('text', this.localize('Remove style'));
							}
						}
						if (index === -1 && !noUnknown) {
							var text = this.localize('Unknown style');
							var value = classNames[classNames.length-1];
							if (typeof HTMLArea.classesSelectable[value] !== 'undefined' && !HTMLArea.classesSelectable[value] && typeof HTMLArea.classesLabels[value] !== 'undefined') {
								text = HTMLArea.classesLabels[value];
							}
							store.add(new store.recordType({
								text: text,
								value: value,
								style: (!(this.pageTSconfiguration && this.pageTSconfiguration.disableStyleOnOptionLabel) && HTMLArea.classesValues && HTMLArea.classesValues[value] && !HTMLArea.classesNoShow[value]) ? HTMLArea.classesValues[value] : null
							}));
							dropDown.setValue(value);
							if (!defaultClass) {
								store.getAt(0).set('text', this.localize('Remove style'));
							}
						}
						// Remove already assigned classes from the dropDown box
						var classNamesString = ',' + classNames.join(',') + ',';
						var selectedValue = dropDown.getValue(), optionValue;
						store.each(function (option) {
							optionValue = option.get('value');
							if (classNamesString.indexOf(',' + optionValue + ',') !== -1 && optionValue !== selectedValue) {
								store.removeAt(store.indexOf(option));
							}
							return true;
						});
					}
					dropDown.setDisabled(!store.getCount() || (store.getCount() == 1 && dropDown.getValue() == 'none'));
					break;
			}
		},

		/**
		 * This function updates the current value of the dropdown list
		 */
		updateValue: function (dropDownId, nodeName, classNames, selectionEmpty, fullNodeSelected, disabled) {
			var editor = this.editor;
			var dropDown = this.getButton(dropDownId);
			if (dropDown) {
				this.buildDropDownOptions(dropDown, nodeName);
				if (classNames.length && (selectionEmpty || fullNodeSelected)) {
					this.setSelectedOption(dropDown, classNames);
				}
				dropDown.setDisabled(!dropDown.getCount() || (dropDown.getCount() === 1 && dropDown.getValue() === 'none') || disabled);
			}
		}
	});

	return TextStyle;

});
