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
 * Block Style Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/CSS/Parser',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, Dom, Event, Parser, Util) {

	var BlockStyle = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(BlockStyle, Plugin);
	Util.apply(BlockStyle.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.cssArray = {};
			this.classesUrl = this.editorConfiguration.classesUrl;
			this.pageTSconfiguration = this.editorConfiguration.buttons.blockstyle;
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
			/**
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

			/**
			 * Registering the drop-down list
			 */
			var dropDownId = 'BlockStyle';
			var fieldLabel = this.pageTSconfiguration ? this.pageTSconfiguration.fieldLabel : '';
			if ((typeof fieldLabel !== 'string' || !fieldLabel.length) && this.isButtonInToolbar('I[Block style label]')) {
				fieldLabel = this.localize('Block style label');
			}
			var dropDownConfiguration = {
				id: dropDownId,
				tooltip: this.localize(dropDownId + '-Tooltip'),
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
		 * This handler gets called when some block style was selected in the drop-down list
		 */
		onChange: function (editor, select) {
			var className = select.getValue();
			this.editor.focus();
			var blocks = this.editor.getSelection().getElements();
			for (var k = 0; k < blocks.length; ++k) {
				var parent = blocks[k];
				while (parent && !Dom.isBlockElement(parent) && !/^(img)$/i.test(parent.nodeName)) {
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

		/**
		 * This function applies the class change to the node
		 */
		applyClassChange: function (node, className) {
			if (className == "none") {
				var classNames = node.className.trim().split(" ");
				for (var i = classNames.length; --i >= 0;) {
					if (!HTMLArea.reservedClassNames.test(classNames[i])) {
						Dom.removeClass(node, classNames[i]);
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
						Dom.addClass(node, className);
					}
				} else if (this.tags && this.tags.all && this.tags.all.allowedClasses) {
					if (this.tags.all.allowedClasses.test(className)) {
						Dom.addClass(node, className);
					}
				} else {
					Dom.addClass(node, className);
				}
				if (nodeName === "table" && this.getPluginInstance('TableOperations')) {
					this.getPluginInstance('TableOperations').reStyleTable(node);
				}
			}
		},

		/**
		 * This handler gets called when the editor is generated
		 */
		onGenerate: function () {
			var self = this;
			// Monitor editor changing mode
			Event.on(this.editor, 'HTMLAreaEventModeChange', function (event, mode) { Event.stopEvent(event); self.onModeChange(mode); return false; });
			// Create CSS Parser object
			this.blockStyles = new Parser({
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
			Event.one(this.blockStyles, 'HTMLAreaEventCssParsingComplete', function (event) { Event.stopEvent(event); self.onCssParsingComplete(); return false; }); 
			this.blockStyles.parse();
		},

		/**
		 * This handler gets called when parsing of css classes is completed
		 */
		onCssParsingComplete: function () {
			if (this.blockStyles.isReady()) {
				this.cssArray = this.blockStyles.getClasses();
				if (this.getEditorMode() === 'wysiwyg' && this.editor.isEditable()) {
					this.updateValue('BlockStyle');
				}
			}
		},

		/**
		 * This handler gets called when the toolbar is being updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if (mode === 'wysiwyg' && this.editor.isEditable() && this.blockStyles.isReady()) {
				this.updateValue(button.itemId);
			}
		},

		/**
		 * This handler gets called when the editor has changed its mode to "wysiwyg"
		 */
		onModeChange: function(mode) {
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				this.updateValue('BlockStyle');
			}
		},

		/**
		 * This function updates the current value of the dropdown list
		 */
		updateValue: function(dropDownId) {
			var dropDown = this.getButton(dropDownId);
			if (dropDown) {
				var classNames = new Array();
				var nodeName = '';
				var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
				var parent = statusBarSelection ? statusBarSelection : this.editor.getSelection().getParentElement();
				while (parent && !Dom.isBlockElement(parent) && !/^(img)$/i.test(parent.nodeName)) {
					parent = parent.parentNode;
				}
				if (parent) {
					nodeName = parent.nodeName.toLowerCase();
					classNames = Dom.getClassNames(parent);
				}
				if (nodeName && nodeName !== 'body'){
					this.buildDropDownOptions(dropDown, nodeName);
					this.setSelectedOption(dropDown, classNames);
				} else {
					this.initializeDropDown(dropDown);
					dropDown.setDisabled(true);
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
					dropDown.setValueByIndex(0);
					break;
				case 'combo':
					var store = dropDown.getStore();
					store.removeAll(false);
					store.insert(0, new store.recordType({
						text: this.localize('No style'),
						value: 'none'
					}));
					dropDown.setValue('none');
					break;
			}
		},

		/**
		 * This function builds the options to be displayed in the dropDown box
		 */
		buildDropDownOptions: function (dropDown, nodeName) {
			this.initializeDropDown(dropDown);
			switch (dropDown.xtype) {
				case 'htmlareaselect':
					if (this.blockStyles.isReady()) {
						var allowedClasses = {};
						if (typeof this.cssArray[nodeName] !== 'undefined') {
							allowedClasses = this.cssArray[nodeName];
						} else if (this.showTagFreeClasses && typeof this.cssArray['all'] !== 'undefined') {
							allowedClasses = this.cssArray['all'];
						}
						for (var cssClass in allowedClasses) {
							if (typeof HTMLArea.classesSelectable[cssClass] === 'undefined' || HTMLArea.classesSelectable[cssClass]) {
								var style = null;
								if (!this.pageTSconfiguration || !this.pageTSconfiguration.disableStyleOnOptionLabel) {
									if (HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) {
										style = HTMLArea.classesValues[cssClass];
									} else if (/-[0-9]+$/.test(cssClass) && HTMLArea.classesValues[RegExp.leftContext + '-'])  {
										style = HTMLArea.classesValues[RegExp.leftContext + '-'];
									}
								}
								dropDown.addOption(allowedClasses[cssClass], cssClass, cssClass, style);
							}
						}
					}
					break;
				case 'combo':
					var store = dropDown.getStore();
					this.initializeDropDown(dropDown);
					if (this.blockStyles.isReady()) {
						var allowedClasses = {};
						if (typeof this.cssArray[nodeName] !== 'undefined') {
							allowedClasses = this.cssArray[nodeName];
						} else if (this.showTagFreeClasses && typeof this.cssArray['all'] !== 'undefined') {
							allowedClasses = this.cssArray['all'];
						}
						for (var cssClass in allowedClasses) {
							if (typeof HTMLArea.classesSelectable[cssClass] === 'undefined' || HTMLArea.classesSelectable[cssClass]) {
								var style = null;
								if (!this.pageTSconfiguration || !this.pageTSconfiguration.disableStyleOnOptionLabel) {
									if (HTMLArea.classesValues[cssClass] && !HTMLArea.classesNoShow[cssClass]) {
										style = HTMLArea.classesValues[cssClass];
									} else if (/-[0-9]+$/.test(cssClass) && HTMLArea.classesValues[RegExp.leftContext + '-'])  {
										style = HTMLArea.classesValues[RegExp.leftContext + '-'];
									}
								}
								store.add(new store.recordType({
									text: allowedClasses[cssClass],
									value: cssClass,
									style: style
								}));
							}
						}
					}
					break;
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
							dropDown.setValue(classNames[classNames.length-1]);
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
		}
	});

	return BlockStyle;

});
