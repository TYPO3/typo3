/***************************************************************
*  Copyright notice
*
*  (c) 2007-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Text Style Plugin for TYPO3 htmlArea RTE
 */
/*
 * Creation of the class of TextStyle plugins
 */
HTMLArea.TextStyle = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		this.cssArray = {};
		this.classesUrl = this.editorConfiguration.classesUrl;
		this.pageTSconfiguration = this.editorConfiguration.buttons.textstyle;
		this.tags = (this.pageTSconfiguration && this.pageTSconfiguration.tags) ? this.pageTSconfiguration.tags : {};
		var allowedClasses;
		for (var tagName in this.tags) {
			if (this.tags.hasOwnProperty(tagName)) {
				if (this.tags[tagName].allowedClasses) {
					allowedClasses = this.tags[tagName].allowedClasses.trim().split(",");
					for (var cssClass in allowedClasses) {
						if (allowedClasses.hasOwnProperty(cssClass)) {
							allowedClasses[cssClass] = allowedClasses[cssClass].trim().replace(/\*/g, ".*");
						}
					}
					this.tags[tagName].allowedClasses = new RegExp( "^(" + allowedClasses.join("|") + ")$", "i");
				}
			}
		}
		this.showTagFreeClasses = this.pageTSconfiguration ? this.pageTSconfiguration.showTagFreeClasses : false;
		this.prefixLabelWithClassName = this.pageTSconfiguration ? this.pageTSconfiguration.prefixLabelWithClassName : false;
		this.postfixLabelWithClassName = this.pageTSconfiguration ? this.pageTSconfiguration.postfixLabelWithClassName : false;
		/*
		 * Regular expression to check if an element is an inline elment
		 */
		this.REInlineTags = /^(a|abbr|acronym|b|bdo|big|cite|code|del|dfn|em|i|img|ins|kbd|q|samp|small|span|strike|strong|sub|sup|tt|u|var)$/;

			// Allowed attributes on inline elements
		this.allowedAttributes = new Array('id', 'title', 'lang', 'xml:lang', 'dir', 'class', 'itemscope', 'itemtype', 'itemprop');
		if (HTMLArea.isIEBeforeIE9) {
			this.addAllowedAttribute('className');
		}
		/*
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
		/*
		 * Registering the dropdown list
		 */
		var buttonId = 'TextStyle';
		var fieldLabel = this.pageTSconfiguration ? this.pageTSconfiguration.fieldLabel : '';
		if (Ext.isEmpty(fieldLabel) && this.isButtonInToolbar('I[text_style]')) {
			fieldLabel = this.localize('text_style');
		}
		var dropDownConfiguration = {
			id: buttonId,
			tooltip: this.localize(buttonId + '-Tooltip'),
			fieldLabel: fieldLabel,
			options: [[this.localize('No style'), 'none']],
			action: 'onChange',
			storeFields: [ { name: 'text'}, { name: 'value'}, { name: 'style'} ],
			tpl: '<tpl for="."><div ext:qtip="{value}" style="{style}text-align:left;font-size:11px;" class="x-combo-list-item">{text}</div></tpl>'
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
	isInlineElement: function (el) {
		return el && (el.nodeType === HTMLArea.DOM.ELEMENT_NODE) && this.REInlineTags.test(el.nodeName.toLowerCase());
	},
	/*
	 * This function adds an attribute to the array of allowed attributes on inline elements
	 *
	 * @param	string	attribute: the name of the attribute to be added to the array
	 *
	 * @return	void
	 */
	addAllowedAttribute: function (attribute) {
		this.allowedAttributes.push(attribute);
	},
	/*
	 * This function gets called when some style in the drop-down list applies it to the highlighted textt
	 */
	onChange: function (editor, combo, record, index) {
		var className = combo.getValue();
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
				fullNodeSelected = (HTMLArea.isIEBeforeIE9 && ((statusBarSelection === ancestors[i] && ancestors[i].innerText === range.text) || (!statusBarSelection && ancestors[i].innerText === range.text)))
							|| (!HTMLArea.isIEBeforeIE9 && ((statusBarSelection === ancestors[i] && ancestors[i].textContent === range.toString()) || (!statusBarSelection && ancestors[i].textContent === range.toString())));
				if (fullNodeSelected) {
					if (this.isInlineElement(ancestors[i])) {
						parent = ancestors[i];
					}
					break;
				}
			}
				// Working around bug in Safari selectNodeContents
			if (!fullNodeSelected && Ext.isWebKit && statusBarSelection && this.isInlineElement(statusBarSelection) && statusBarSelection.textContent === range.toString()) {
				fullNodeSelected = true;
				parent = statusBarSelection;
			}
		}
		if (!selectionEmpty && !fullNodeSelected || (!selectionEmpty && fullNodeSelected && parent && HTMLArea.DOM.isBlockElement(parent))) {
				// The selection is not empty, nor full element, or the selection is full block element
			if (className !== "none") {
					// Add span element with class attribute
				var newElement = editor.document.createElement('span');
				HTMLArea.DOM.addClass(newElement, className);
				editor.getDomNode().wrapWithInlineElement(newElement, range);
				if (!HTMLArea.isIEBeforeIE9) {
					range.detach();
				}
			}
		} else {
			this.applyClassChange(parent, className);
		}
	},
	/*
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
		if (node && !HTMLArea.DOM.isBlockElement(node)) {
			if (className === 'none' && node.className && /\S/.test(node.className)) {
				classNames = node.className.trim().split(' ');
				HTMLArea.DOM.removeClass(node, classNames[classNames.length-1]);
			}
			if (className !== 'none') {
				HTMLArea.DOM.addClass(node, className);
			}
				// Remove the span tag if it has no more attribute
			if (/^span$/i.test(node.nodeName) && !HTMLArea.DOM.hasAllowedAttributes(node, this.allowedAttributes) && !noRemove) {
				this.editor.getDomNode().removeMarkup(node);
			}
		}
	},
	/*
	 * This function gets called when the plugin is generated
	 * Get the classes configuration and initiate the parsing of the style sheets
	 */
	onGenerate: function () {
			// Monitor editor changing mode
		this.editor.iframe.mon(this.editor, 'HTMLAreaEventModeChange', this.onModeChange, this);
			// Create CSS Parser object
		this.textStyles = new HTMLArea.CSS.Parser({
			prefixLabelWithClassName: this.prefixLabelWithClassName,
			postfixLabelWithClassName: this.postfixLabelWithClassName,
			showTagFreeClasses: this.showTagFreeClasses,
			tags: this.tags,
			editor: this.editor
		});
			// Disable the combo while initialization completes
		var dropDown = this.getButton('TextStyle');
		if (dropDown) {
			dropDown.setDisabled(true);
		}
			// Monitor css parsing being completed
		this.editor.iframe.mon(this.textStyles, 'HTMLAreaEventCssParsingComplete', this.onCssParsingComplete, this);
		this.textStyles.initiateParsing();
	},
	/*
	 * This handler gets called when parsing of css classes is completed
	 */
	onCssParsingComplete: function () {
		if (this.textStyles.isReady) {
			this.cssArray = this.textStyles.getClasses();
			if (this.getEditorMode() === 'wysiwyg' && this.editor.isEditable()) {
				this.updateToolbar('TextStyle');
			}
		}
	},
	/*
	 * This handler gets called when the toolbar is being updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg' && this.editor.isEditable() && this.textStyles.isReady) {
			this.updateToolbar(button.itemId);
		}
	},
	/*
	 * This handler gets called when the editor has changed its mode to "wysiwyg"
	 */
	onModeChange: function (mode) {
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			this.updateToolbar('TextStyle');
		}
	},
	/*
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
			if (parent && !HTMLArea.DOM.isBlockElement(parent)) {
				tagName = parent.nodeName.toLowerCase();
				if (parent.className && /\S/.test(parent.className)) {
					classNames = parent.className.trim().split(" ");
				}
			}
			var selectionEmpty = editor.getSelection().isEmpty();
			if (!selectionEmpty) {
				for (var i = 0; i < ancestors.length; ++i) {
					fullNodeSelected = (statusBarSelection === ancestors[i])
						&& ((!HTMLArea.isIEBeforeIE9 && ancestors[i].textContent === range.toString()) || (HTMLArea.isIEBeforeIE9 && ancestors[i].innerText === range.text));
					if (fullNodeSelected) {
						if (!HTMLArea.DOM.isBlockElement(ancestors[i])) {
							tagName = ancestors[i].nodeName.toLowerCase();
							if (ancestors[i].className && /\S/.test(ancestors[i].className)) {
								classNames = ancestors[i].className.trim().split(" ");
							}
						}
						break;
					}
				}
					// Working around bug in Safari selectNodeContents
				if (!fullNodeSelected && Ext.isWebKit && statusBarSelection && this.isInlineElement(statusBarSelection) && statusBarSelection.textContent === range.toString()) {
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
	/*
	 * This function reinitializes the options of the dropdown
	 */
	initializeDropDown: function (dropDown) {
		var store = dropDown.getStore();
		store.removeAll(false);
		store.insert(0, new store.recordType({
			text: this.localize('No style'),
			value: 'none'
		}));
		dropDown.setValue('none');
	},
	/*
	 * This function builds the options to be displayed in the dropDown box
	 */
	buildDropDownOptions: function (dropDown, nodeName) {
		var store = dropDown.getStore();
		this.initializeDropDown(dropDown);
		if (this.textStyles.isReady) {
			var allowedClasses = {};
			if (this.REInlineTags.test(nodeName)) {
				if (Ext.isDefined(this.cssArray[nodeName])) {
					allowedClasses = this.cssArray[nodeName];
				} else if (this.showTagFreeClasses && Ext.isDefined(this.cssArray['all'])) {
					allowedClasses = this.cssArray['all'];
				}
			}
			Ext.iterate(allowedClasses, function (cssClass, value) {
				store.add(new store.recordType({
					text: value,
					value: cssClass,
					style: (!(this.pageTSconfiguration && this.pageTSconfiguration.disableStyleOnOptionLabel) && HTMLArea.classesValues && HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) ? HTMLArea.classesValues[cssClass] : null
				}));
			}, this);
		}
	},
	/*
	 * This function sets the selected option of the dropDown box
	 */
	setSelectedOption: function (dropDown, classNames, noUnknown, defaultClass) {
		var store = dropDown.getStore();
		dropDown.setValue('none');
		if (classNames.length) {
			var index = store.findExact('value', classNames[classNames.length-1]);
			if (index != -1) {
				dropDown.setValue(classNames[classNames.length-1]);
				if (!defaultClass) {
					store.getAt(0).set('text', this.localize('Remove style'));
				}
			}
			if (index == -1 && !noUnknown) {
				store.add(new store.recordType({
					text: this.localize('Unknown style'),
					value: classNames[classNames.length-1]
				}));
				index = store.getCount()-1;
				dropDown.setValue(classNames[classNames.length-1]);
				if (!defaultClass) {
					store.getAt(0).set('text', this.localize('Remove style'));
				}
			}
				// Remove already assigned classes from the dropDown box
			var classNamesString = ',' + classNames.join(',') + ',';
			store.each(function (option) {
				if (classNamesString.indexOf("," + option.get('value') + ",") != -1) {
					store.removeAt(store.indexOf(option));
				}
				return true;
			});
		}
		dropDown.setDisabled(!store.getCount() || (store.getCount() == 1 && dropDown.getValue() == 'none'));
	},
	/*
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
			var store = dropDown.getStore();
			dropDown.setDisabled(!store.getCount() || (store.getCount() == 1 && dropDown.getValue() == 'none') || disabled);
		}
	}
});
