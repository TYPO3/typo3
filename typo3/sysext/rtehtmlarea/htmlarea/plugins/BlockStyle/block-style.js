/***************************************************************
*  Copyright notice
*
* (c) 2007-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Block Style Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.BlockStyle = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		this.cssArray = {};
		this.classesUrl = this.editorConfiguration.classesUrl;
		this.pageTSconfiguration = this.editorConfiguration.buttons.blockstyle;
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
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '3.0',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: this.localize('Technische Universitat Ilmenau'),
			sponsorUrl	: 'http://www.tu-ilmenau.de/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the drop-down list
		 */
		var dropDownId = 'BlockStyle';
		var fieldLabel = this.pageTSconfiguration ? this.pageTSconfiguration.fieldLabel : '';
		if (Ext.isEmpty(fieldLabel) && this.isButtonInToolbar('I[Block style label]')) {
			fieldLabel = this.localize('Block style label');
		}
		var dropDownConfiguration = {
			id: dropDownId,
			tooltip: this.localize(dropDownId + '-Tooltip'),
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
	/*
	 * This handler gets called when some block style was selected in the drop-down list
	 */
	onChange: function (editor, combo, record, index) {
		var className = combo.getValue();
		this.editor.focus();
		var blocks = this.editor.getSelection().getElements();
		for (var k = 0; k < blocks.length; ++k) {
			var parent = blocks[k];
			while (parent && !HTMLArea.DOM.isBlockElement(parent) && !/^(img)$/i.test(parent.nodeName)) {
				parent = parent.parentNode;
			}
			if (!k) {
				var tagName = parent.tagName.toLowerCase();
			}
			if (parent.tagName.toLowerCase() == tagName) {
				this.applyClassChange(parent, className);
			}
		}
	},
	/*
	 * This function applies the class change to the node
	 */
	applyClassChange: function (node, className) {
		if (className == "none") {
			var classNames = node.className.trim().split(" ");
			for (var i = classNames.length; --i >= 0;) {
				if (!HTMLArea.reservedClassNames.test(classNames[i])) {
					HTMLArea.DOM.removeClass(node, classNames[i]);
					if (node.nodeName.toLowerCase() === "table" && this.getPluginInstance('TableOperations')) {
						this.getPluginInstance('TableOperations').removeAlternatingClasses(node, classNames[i]);
						this.getPluginInstance('TableOperations').removeCountingClasses(node, classNames[i]);
					}
					break;
				}
			}
		} else {
			var nodeName = node.nodeName.toLowerCase();
			if (this.tags && this.tags[nodeName] && this.tags[nodeName].allowedClasses) {
				if (this.tags[nodeName].allowedClasses.test(className)) {
					HTMLArea.DOM.addClass(node, className);
				}
			} else if (this.tags && this.tags.all && this.tags.all.allowedClasses) {
				if (this.tags.all.allowedClasses.test(className)) {
					HTMLArea.DOM.addClass(node, className);
				}
			} else {
				HTMLArea.DOM.addClass(node, className);
			}
			if (nodeName === "table" && this.getPluginInstance('TableOperations')) {
				this.getPluginInstance('TableOperations').reStyleTable(node);
			}
		}
	},
	/*
	 * This handler gets called when the editor is generated
	 */
	onGenerate: function () {
			// Monitor editor changing mode
		this.editor.iframe.mon(this.editor, 'HTMLAreaEventModeChange', this.onModeChange, this);
			// Create CSS Parser object
		this.blockStyles = new HTMLArea.CSS.Parser({
			prefixLabelWithClassName: this.prefixLabelWithClassName,
			postfixLabelWithClassName: this.postfixLabelWithClassName,
			showTagFreeClasses: this.showTagFreeClasses,
			tags: this.tags,
			editor: this.editor
		});
			// Disable the combo while initialization completes
		var dropDown = this.getButton('BlockStyle');
		if (dropDown) {
			dropDown.setDisabled(true);
		}
			// Monitor css parsing being completed
		this.editor.iframe.mon(this.blockStyles, 'HTMLAreaEventCssParsingComplete', this.onCssParsingComplete, this);
		this.blockStyles.initiateParsing();
	},
	/*
	 * This handler gets called when parsing of css classes is completed
	 */
	onCssParsingComplete: function () {
		if (this.blockStyles.isReady) {
			this.cssArray = this.blockStyles.getClasses();
			if (this.getEditorMode() === 'wysiwyg' && this.editor.isEditable()) {
				this.updateValue('BlockStyle');
			}
		}
	},
	/*
	 * This handler gets called when the toolbar is being updated
	 */
	onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
		if (mode === 'wysiwyg' && this.editor.isEditable() && this.blockStyles.isReady) {
			this.updateValue(button.itemId);
		}
	},
	/*
	 * This handler gets called when the editor has changed its mode to "wysiwyg"
	 */
	onModeChange: function(mode) {
		if (mode === 'wysiwyg' && this.editor.isEditable()) {
			this.updateValue('BlockStyle');
		}
	},
	/*
	 * This function updates the current value of the dropdown list
	 */
	updateValue: function(dropDownId) {
		var dropDown = this.getButton(dropDownId);
		if (dropDown) {
			var classNames = new Array();
			var tagName = null;
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
			var parent = statusBarSelection ? statusBarSelection : this.editor.getSelection().getParentElement();
			while (parent && !HTMLArea.DOM.isBlockElement(parent) && !/^(img)$/i.test(parent.nodeName)) {
				parent = parent.parentNode;
			}
			if (parent) {
				tagName = parent.nodeName.toLowerCase();
				classNames = HTMLArea.DOM.getClassNames(parent);
			}
			if (tagName && tagName !== "body"){
				this.buildDropDownOptions(dropDown, tagName);
				this.setSelectedOption(dropDown, classNames);
			} else {
				this.initializeDropDown(dropDown);
				dropDown.setDisabled(true);
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
		if (this.blockStyles.isReady) {
			var allowedClasses = {};
			if (Ext.isDefined(this.cssArray[nodeName])) {
				allowedClasses = this.cssArray[nodeName];
			} else if (this.showTagFreeClasses && Ext.isDefined(this.cssArray['all'])) {
				allowedClasses = this.cssArray['all'];
			}
			Ext.iterate(allowedClasses, function (cssClass, value) {
				var style = null;
				if (!this.pageTSconfiguration.disableStyleOnOptionLabel) {
					if (HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) {
						style = HTMLArea.classesValues[cssClass];
					} else if (/-[0-9]+$/.test(cssClass) && HTMLArea.classesValues[RegExp.leftContext + '-'])  {
						style = HTMLArea.classesValues[RegExp.leftContext + '-'];
					}
				}
				store.add(new store.recordType({
					text: value,
					value: cssClass,
					style: style
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
				if (classNamesString.indexOf(',' + option.get('value') + ',') != -1) {
					store.removeAt(store.indexOf(option));
				}
				return true;
			});
		}
		dropDown.setDisabled(!store.getCount() || (store.getCount() == 1 && dropDown.getValue() == 'none'));
	}
});
