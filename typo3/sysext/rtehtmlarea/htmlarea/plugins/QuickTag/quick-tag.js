/***************************************************************
*  Copyright notice
*
*  (c) 2004 Cau guanabara <caugb@ibest.com.br>
*  (c) 2005-2012 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Quick Tag Editor Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.QuickTag = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		this.pageTSConfiguration = this.editorConfiguration.buttons.inserttag;
		this.allowedTags = (this.pageTSConfiguration && this.pageTSConfiguration.tags) ? this.pageTSConfiguration.tags : null;
		this.denyTags = (this.pageTSConfiguration && this.pageTSConfiguration.denyTags) ? this.pageTSConfiguration.denyTags : null;
		this.allowedAttribs =  (this.pageTSConfiguration && this.pageTSConfiguration.allowedAttribs) ? this.pageTSConfiguration.allowedAttribs : null;
		this.quotes = new RegExp('^\w+\s*([a-zA-Z_0-9:;]+=\"[^\"]*\"\s*|[a-zA-Z_0-9:;]+=\'[^\']*\'\s*)*$');
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '2.3',
			developer	: 'Cau Guanabara & Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca',
			copyrightOwner	: 'Cau Guanabara & Stanislas Rolland',
			sponsor		: 'Independent production & SJBR',
			sponsorUrl	: 'http://www.sjbr.ca',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		/*
		 * Registering the button
		 */
		var buttonId = 'InsertTag';
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize('Quick Tag Editor'),
			iconCls		: 'htmlarea-action-tag-insert',
			action		: 'onButtonPress',
			selection	: true,
			dialog		: true
		};
		this.registerButton(buttonConfiguration);
		return true;
	 },
	/*
	 * Sets of default configuration values for dialogue form fields
	 */
	configDefaults: {
		combo: {
			editable: true,
			typeAhead: true,
			triggerAction: 'all',
			forceSelection: true,
			mode: 'local',
			valueField: 'value',
			displayField: 'text',
			helpIcon: true
		}
	},
	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 * @param	object		target: the target element of the contextmenu event, when invoked from the context menu
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;
		this.openDialogue(
			'Quick Tag Editor',
			{
				buttonId: buttonId
			},
			this.getWindowDimensions({ width: 570}, buttonId),
			this.buildItemsConfig(),
			this.setTag
		);
		this.insertedTag = this.dialog.find('itemId', 'insertedTag')[0];
		this.tagCombo = this.dialog.find('itemId', 'tags')[0];
		this.attributeCombo = this.dialog.find('itemId', 'attributes')[0];
		this.valueCombo = this.dialog.find('itemId', 'values')[0];
		this.colorCombo = this.dialog.find('itemId', 'colors')[0];
	},
	/*
	 * Build the window items config
	 */
	buildItemsConfig: function (element, buttonId) {
		var tagStore = new Ext.data.ArrayStore({
			autoDestroy:  true,
			fields: [ { name: 'text'}, { name: 'value'}],
			data: this.tags
		});
		if (this.denyTags) {
			var denyTags = new RegExp('^(' + this.denyTags.split(',').join('|').replace(/ /g, '') + ')$', 'i');
			tagStore.filterBy(function (record) {
				return !denyTags.test(record.get('value'));
			});
				// Make sure the combo list is filtered
			tagStore.snapshot = tagStore.data;
		}
		var attributeStore = new Ext.data.ArrayStore({
			autoDestroy:  true,
			fields: [ {name: 'tag'}, { name: 'text'}, { name: 'value'}],
			data: this.attributes
		});
		this.valueRecord = Ext.data.Record.create([{name: 'attribute'}, { name: 'text'}, { name: 'value'}]);
		var valueStore = new Ext.data.ArrayStore({
			autoDestroy:  true,
			fields: [ {name: 'attribute'}, { name: 'text'}, { name: 'value'}],
			data: this.values,
			listeners: {
				load: {
					fn: this.captureClasses,
					scope: this
				}
			}
		});
		var itemsConfig = [{
				xtype: 'textarea',
				itemId: 'tagopen',
				width: 400,
				itemId: 'insertedTag',
				fieldLabel: '<',
				labelSeparator: '',
				grow: true,
				listeners: {
					change: {
						fn: this.filterAttributes,
						scope: this
					},
					focus: {
						fn: this.filterAttributes,
						scope: this
					}
				}
			},{
				xtype: 'displayfield',
				text: '>'
			}, Ext.apply({
				xtype: 'combo',
				itemId: 'tags',
				fieldLabel: this.localize('TAGs'),
				store: tagStore,
				listeners: {
					select: {
						fn: this.onTagSelect,
						scope: this
					}
				}
			}, this.configDefaults['combo'])
			, Ext.apply({
				xtype: 'combo',
				itemId: 'attributes',
				fieldLabel: this.localize('ATTRIBUTES'),
				store: attributeStore,
				hidden: true,
				listeners: {
					select: {
						fn: this.onAttributeSelect,
						scope: this
					}
				}
			}, this.configDefaults['combo'])
			, Ext.apply({
				xtype: 'combo',
				itemId: 'values',
				fieldLabel: this.localize('OPTIONS'),
				store: valueStore,
				hidden: true,
				listeners: {
					select: {
						fn: this.onValueSelect,
						scope: this
					}
				}
			}, this.configDefaults['combo'])
			,{
				xtype: 'colorpalettefield',
				fieldLabel: this.localize('Colors'),
				itemId: 'colors',
				colors: this.editorConfiguration.disableColorPicker ? [] : null,
				colorsConfiguration: this.editorConfiguration.colors,
				hidden: true,
				listeners: {
					select: {
						fn: this.onColorSelect,
						scope: this
					}
				}
			}
		];
	 	return {
			xtype: 'fieldset',
			title: this.localize('Quick Tag Editor'),
			defaultType: 'textfield',
			labelWidth: 100,
			defaults: {
				helpIcon: true
			},
			items: itemsConfig
		};
	},
	/*
	 * Add a record for each class selector found in the stylesheets
	 */
	captureClasses: function (valueStore) {
		this.parseCssRule(this.editor.document.styleSheets, valueStore);
	},
	parseCssRule: function (rules, valueStore) {
		Ext.each(rules, function (rule) {
			if (rule.selectorText) {
				if (/^(\w*)\.(\w+)$/.test(rule.selectorText)) {
					valueStore.add(new this.valueRecord({
						attribute: 'class',
						text: rule.selectorText,
						value: RegExp.$2 + '"'
					}));
				}
			} else {
					// ImportRule (Mozilla)
				if (rule.styleSheet) {
					this.parseCssRule(rule.styleSheet.cssRules, valueStore);
				}
					// MediaRule (Mozilla)
				if (rule.cssRules) {
					this.parseCssRule(rule.cssRules, valueStore);
				}
					// IE imports
				if (rule.imports) {
					this.parseCssRule(rule.imports, valueStore);
				}
				if (rule.rules) {
					this.parseCssRule(rule.rules, valueStore);
				}
			}
		}, this);
	},
	/*
	 * Handler invoked when a tag is selected
	 * Update the attributes combo and the inserted tag field
	 */
	onTagSelect: function (tagCombo, tagRecord) {
		var tag = tagRecord.get('value');
		this.filterAttributes();
		this.attributeCombo.clearValue();
		this.attributeCombo.show();
		this.valueCombo.hide();
		this.insertedTag.setValue(tag);
		this.insertedTag.focus(false, 50);
	},
	/*
	 * Filter out attributes not applicable to the tag, already present in the tag or not allowed
	 */
	filterAttributes: function () {
		var tag = this.tagCombo.getValue();
		var insertedTag = this.insertedTag.getValue();
		var attributeStore = this.attributeCombo.getStore();
		if (attributeStore.realSnapshot) {
			attributeStore.snapshot = attributeStore.realSnapshot;
			delete attributeStore.realSnapshot;
			attributeStore.clearFilter(true);
		}
		var allowedAttribs = '';
		if (this.allowedAttribs) {
			allowedAttribs = this.allowedAttribs.split(',').join('|').replace(/ /g, '');
		}
		if (this.tags && this.tags[tag] && this.tags[tag].allowedAttribs) {
			allowedAttribs += allowedAttribs ? '|' : '';
			allowedAttribs += this.tags[tag].allowedAttribs.split(',').join('|').replace(/ /g, '');
		}
		if (allowedAttribs) {
			var allowedAttribs = new RegExp('^(' + allowedAttribs + ')$');
		}
		attributeStore.filterBy(function (attributeRecord) {
				// Filter out attributes already used in the tag, not applucable to tag or not allowed
			var testAttrib = new RegExp('(' + attributeRecord.get('value') + ')', 'ig');
			var tagValue = attributeRecord.get('tag');
			return (tagValue == 'all' || tagValue == tag) && !testAttrib.test(insertedTag) && (!allowedAttribs || allowedAttribs.test(attributeRecord.get('text')));
		});
			// Make sure the combo list is filtered
		attributeStore.realSnapshot = attributeStore.snapshot;
		attributeStore.snapshot = attributeStore.data;
	},
	/*
	 * Filter out not applicable to the attribute or style values already present in the tag
	 * Filter out classes not applicable to the current tag
	 */
	filterValues: function (attribute) {
		var tag = this.tagCombo.getValue();
		var insertedTag = this.insertedTag.getValue();
		var valueStore = this.valueCombo.getStore();
		if (valueStore.realSnapshot) {
			valueStore.snapshot = valueStore.realSnapshot;
			delete valueStore.realSnapshot;
			valueStore.clearFilter(true);
		}
		var expr = new RegExp('(^' + tag + '[\.])|(^[\.])', 'i');
		valueStore.filterBy(function (valueRecord) {
			var value = valueRecord.get('value');
			if (attribute === 'style') {
				expr = new RegExp('(' + ((value.charAt(0) == '+' || value.charAt(0) == '-') ? '\\' : '') + value + ')', 'ig');
			}
			return valueRecord.get('attribute') == attribute && (attribute !== 'style' || !expr.test(insertedTag)) && (attribute !== 'class' || expr.test(valueRecord.get('text')));
		});
			// Make sure the combo list is filtered
		valueStore.realSnapshot = valueStore.snapshot;
		valueStore.snapshot = valueStore.data;
		this.valueCombo.setVisible(valueStore.getCount() ? true : false);
	},
	/*
	 * Handler invoked when an attribute is selected
	 * Update the values combo and the inserted tag field
	 */
	onAttributeSelect: function (attributeCombo, attributeRecord) {
		var insertedTag = this.insertedTag.getValue();
		var attribute = attributeRecord.get('text');
		this.valueCombo.clearValue();
		if (/color/.test(attribute)) {
			this.valueCombo.hide();
			this.colorCombo.show();
		} else {
			this.filterValues(attribute);
		}
		this.insertedTag.setValue(insertedTag + ((/\"/.test(insertedTag) && (!/\"$/.test(insertedTag) || /=\"$/.test(insertedTag))) ? '" ' : ' ') + attributeRecord.get('value'));
		this.insertedTag.focus(false, 50);
	},
	/*
	 * Handler invoked when a value is selected
	 * Update the inserted tag field
	 */
	onValueSelect: function (combo, record) {
		var style = this.attributeCombo.getValue() === 'style="';
		this.insertedTag.setValue(this.insertedTag.getValue() + (style && !/="$/.test(this.insertedTag.getValue()) ? '; ' : '') + combo.getValue());
		this.insertedTag.focus(false, 50);
		combo.clearValue();
		if (style) {
			if (/color/.test(record.get('text'))) {
				this.colorCombo.show();
			}
		} else {
			combo.hide();
			this.attributeCombo.clearValue();
		}
	},
	/*
	 * Handler invoked when a color is selected
	 * Update the inserted tag field
	 */
	onColorSelect: function (combo, record) {
		var style = this.attributeCombo.getValue() === 'style="';
		this.insertedTag.setValue(this.insertedTag.getValue() + '#' + combo.getValue() + (style ? '' : '"'));
		this.insertedTag.focus(false, 50);
		combo.setValue('');
		combo.hide();
		if (!style) {
			this.attributeCombo.clearValue();
		}
	},
	/*
	 * Handler invoked when a OK button is pressed
	 */
	setTag: function (button, event) {
		this.restoreSelection();
		var insertedTag = this.insertedTag.getValue();
		var currentTag = this.tagCombo.getValue();
		if (!insertedTag) {
			TYPO3.Dialog.InformationDialog({
				title: this.getButton('InsertTag').tooltip.title,
				msg: this.localize('Enter the TAG you want to insert'),
				fn: function () { this.insertedTag.focus(); },
				scope: this
			});
			event.stopEvent();
			return false;
		}
		if (this.quotes.test(insertedTag)) {
			if (this.quotes.test(insertedTag + '"')) {
				TYPO3.Dialog.InformationDialog({
					title: this.getButton('InsertTag').tooltip.title,
					msg: this.localize('There are some unclosed quote'),
					fn: function () { this.insertedTag.focus(); this.insertedTag.select(); },
					scope: this
				});
				event.stopEvent();
				return false;
			} else {
				this.insertedTag.setValue(insertedTag + '"');
			}
		}
		insertedTag = insertedTag.replace(/(<|>)/g, '');
		var tagOpen = '<' + insertedTag + '>';
		var tagClose = tagOpen.replace(/^<(\w+) ?.*>/, '</$1>');
		var subTags = this.subTags[currentTag];
		if (subTags) {
			tagOpen = tagOpen + this.subTags.open;
			tagClose = this.subTags.close + tagClose;
		}
		this.editor.getSelection().surroundHtml(tagOpen, tagClose);
		this.close();
		event.stopEvent();
	},
	/*
	 * Open the dialogue window
	 *
	 * @param	string		title: the window title
	 * @param	object		arguments: some arguments for the handler
	 * @param	integer		dimensions: the opening dimensions of the window
	 * @param	object		items: the configuration of the window items
	 * @param	function	handler: handler when the OK button if clicked
	 *
	 * @return	void
	 */
	openDialogue: function (title, arguments, dimensions, items, handler) {
		if (this.dialog) {
			this.dialog.close();
		}
		this.dialog = new Ext.Window({
			title: this.localize(title),
			arguments: arguments,
			cls: 'htmlarea-window',
			border: false,
			width: dimensions.width,
			height: 'auto',
			iconCls: this.getButton(arguments.buttonId).iconCls,
			listeners: {
				close: {
					fn: this.onClose,
					scope: this
				}
			},
			items: {
				xtype: 'container',
				layout: 'form',
				defaults: {
					labelWidth: 150
				},
				items: items
			},
			buttons: [
				this.buildButtonConfig('OK', handler),
				this.buildButtonConfig('Cancel', this.onCancel)
			]
		});
		this.show();
	},
	tags: [
		['a', 'a'],
		['abbr', 'abbr'],
		['acronym', 'acronym'],
		['address', 'address'],
		['b', 'b'],
		['big', 'big'],
		['blockquote', 'blockquote'],
		['cite', 'cite'],
		['code', 'code'],
		['div', 'div'],
		['em', 'em'],
		['fieldset', 'fieldset'],
		['font', 'font'],
		['h1', 'h1'],
		['h2', 'h2'],
		['h3', 'h3'],
		['h4', 'h4'],
		['h5', 'h5'],
		['h6', 'h6'],
		['i', 'i'],
		['legend', 'legend'],
		['li', 'li'],
		['ol', 'ol'],
		['ul', 'ul'],
		['p', 'p'],
		['pre', 'pre'],
		['q', 'q'],
		['small', 'small'],
		['span', 'span'],
		['strike', 'strike'],
		['strong', 'strong'],
		['sub', 'sub'],
		['sup', 'sup'],
		['table', 'table'],
		['tt', 'tt'],
		['u', 'u']
	],
	attributes: [
		['all', 'class', 'class="'],
		['all', 'dir', 'dir="'],
		['all', 'id', 'id="'],
		['all', 'lang', 'lang="'],
		['all', 'onFocus', 'onFocus="'],
		['all', 'onBlur', 'onBlur="'],
		['all', 'onClick', 'onClick="'],
		['all', 'onDblClick', 'onDblClick="'],
		['all', 'onMouseDown', 'onMouseDown="'],
		['all', 'onMouseUp', 'onMouseUp="'],
		['all', 'onMouseOver', 'onMouseOver="'],
		['all', 'onMouseMove', 'onMouseMove="'],
		['all', 'onMouseOut', 'onMouseOut="'],
		['all', 'onKeyPress', 'onKeyPress="'],
		['all', 'onKeyDown', 'onKeyDown="'],
		['all', 'onKeyUp', 'onKeyUp="'],
		['all', 'style', 'style="'],
		['all', 'title', 'title="'],
		['all', 'xml:lang', 'xml:lang="'],
		['a', 'href', 'href="'],
		['a', 'name', 'name="'],
		['a', 'target', 'target="'],
		['font', 'face', 'face="'],
		['font', 'size', 'size="'],
		['font', 'color', 'color="'],
		['div', 'align', 'align="'],
		['h1', 'align', 'align="'],
		['h2', 'align', 'align="'],
		['h3', 'align', 'align="'],
		['h4', 'align', 'align="'],
		['h5', 'align', 'align="'],
		['h6', 'align', 'align="'],
		['p', 'align', 'align="'],
		['table', 'align', 'align="'],
		['table', 'width', 'width="'],
		['table', 'height', 'height="'],
		['table', 'cellpadding', 'cellpadding="'],
		['table', 'cellspacing', 'cellspacing="'],
		['table', 'background', 'background="'],
		['table', 'bgcolor', 'bgcolor="'],
		['table', 'border', 'border="'],
		['table', 'bordercolor', 'bordercolor="']
	],
	values: [
		['href', 'http://', 'http://'],
		['href', 'https://', 'https://'],
		['href', 'ftp://', 'ftp://'],
		['href', 'mailto:', 'mailto:'],
		['href', '#', '#"'],
		['target', '_top', '_top"'],
		['target', '_self', '_self"'],
		['target', '_parent', '_parent"'],
		['target', '_blank', '_blank"'],
		['face', 'Verdana', 'Verdana"'],
		['face', 'Arial', 'Arial"'],
		['face', 'Tahoma', 'Tahoma"'],
		['face', 'Courier New', 'Courier New"'],
		['face', 'Times New Roman', 'Times New Roman"'],
		['size', '1', '1"'],
		['size', '2', '2"'],
		['size', '3', '3"'],
		['size', '4', '4"'],
		['size', '5', '5"'],
		['size', '6', '6"'],
		['size', '+1', '+1"'],
		['size', '+2', '+2"'],
		['size', '+3', '+3"'],
		['size', '+4', '+4"'],
		['size', '+5', '+5"'],
		['size', '+6', '+6"'],
		['size', '-1', '-1"'],
		['size', '-2', '-2"'],
		['size', '-3', '-3"'],
		['size', '-4', '-4"'],
		['size', '-5', '-5"'],
		['size', '-6', '-6"'],
		['align', 'center', 'center"'],
		['align', 'left', 'left"'],
		['align', 'right', 'right"'],
		['align', 'justify', 'justify"'],
		['dir', 'rtl', 'rtl"'],
		['dir', 'ltr', 'ltr"'],
		['lang', 'Afrikaans ', 'af"'],
		['lang', 'Albanian ', 'sq"'],
		['lang', 'Arabic ', 'ar"'],
		['lang', 'Basque ', 'eu"'],
		['lang', 'Breton ', 'br"'],
		['lang', 'Bulgarian ', 'bg"'],
		['lang', 'Belarusian ', 'be"'],
		['lang', 'Catalan ', 'ca"'],
		['lang', 'Chinese ', 'zh"'],
		['lang', 'Croatian ', 'hr"'],
		['lang', 'Czech ', 'cs"'],
		['lang', 'Danish ', 'da"'],
		['lang', 'Dutch ', 'nl"'],
		['lang', 'English ', 'en"'],
		['lang', 'Estonian ', 'et"'],
		['lang', 'Faeroese ', 'fo"'],
		['lang', 'Farsi ', 'fa"'],
		['lang', 'Finnish ', 'fi"'],
		['lang', 'French ', 'fr"'],
		['lang', 'Gaelic ', 'gd"'],
		['lang', 'German ', 'de"'],
		['lang', 'Greek ', 'el"'],
		['lang', 'Hebrew ', 'he"'],
		['lang', 'Hindi ', 'hi"'],
		['lang', 'Hungarian ', 'hu"'],
		['lang', 'Icelandic ', 'is"'],
		['lang', 'Indonesian ', 'id"'],
		['lang', 'Italian ', 'it"'],
		['lang', 'Japanese ', 'ja"'],
		['lang', 'Korean ', 'ko"'],
		['lang', 'Latvian ', 'lv"'],
		['lang', 'Lithuanian ', 'lt"'],
		['lang', 'Macedonian ', 'mk"'],
		['lang', 'Malaysian ', 'ms"'],
		['lang', 'Maltese ', 'mt"'],
		['lang', 'Norwegian ', 'no"'],
		['lang', 'Polish ', 'pl"'],
		['lang', 'Portuguese ', 'pt"'],
		['lang', 'Rhaeto-Romanic ', 'rm"'],
		['lang', 'Romanian ', 'ro"'],
		['lang', 'Russian ', 'ru"'],
		['lang', 'Sami ', 'sz"'],
		['lang', 'Serbian ', 'sr"'],
		['lang', 'Setswana ', 'tn"'],
		['lang', 'Slovak ', 'sk"'],
		['lang', 'Slovenian ', 'sl"'],
		['lang', 'Spanish ', 'es"'],
		['lang', 'Sutu ', 'sx"'],
		['lang', 'Swedish ', 'sv"'],
		['lang', 'Thai ', 'th"'],
		['lang', 'Tsonga ', 'ts"'],
		['lang', 'Turkish ', 'tr"'],
		['lang', 'Ukrainian ', 'uk"'],
		['lang', 'Urdu ', 'ur"'],
		['lang', 'Vietnamese ', 'vi"'],
		['lang', 'Xhosa ', 'xh"'],
		['lang', 'Yiddish ', 'yi"'],
		['lang', 'Zulu', 'zu"'],
		['style', 'azimuth', 'azimuth: '],
		['style', 'background', 'background: '],
		['style', 'background-attachment', 'background-attachment: '],
		['style', 'background-color', 'background-color: '],
		['style', 'background-image', 'background-image: '],
		['style', 'background-position', 'background-position: '],
		['style', 'background-repeat', 'background-repeat: '],
		['style', 'border', 'border: '],
		['style', 'border-bottom', 'border-bottom: '],
		['style', 'border-left', 'border-left: '],
		['style', 'border-right', 'border-right: '],
		['style', 'border-top', 'border-top: '],
		['style', 'border-bottom-color', 'border-bottom-color: '],
		['style', 'border-left-color', 'border-left-color: '],
		['style', 'border-right-color', 'border-right-color: '],
		['style', 'border-top-color', 'border-top-color: '],
		['style', 'border-bottom-style', 'border-bottom-style: '],
		['style', 'border-left-style', 'border-left-style: '],
		['style', 'border-right-style', 'border-right-style: '],
		['style', 'border-top-style', 'border-top-style: '],
		['style', 'border-bottom-width', 'border-bottom-width: '],
		['style', 'border-left-width', 'border-left-width: '],
		['style', 'border-right-width', 'border-right-width: '],
		['style', 'border-top-width', 'border-top-width: '],
		['style', 'border-collapse', 'border-collapse: '],
		['style', 'border-color', 'border-color: '],
		['style', 'border-style', 'border-style: '],
		['style', 'border-width', 'border-width: '],
		['style', 'bottom', 'bottom: '],
		['style', 'caption-side', 'caption-side: '],
		['style', 'cell-spacing', 'cell-spacing: '],
		['style', 'clear', 'clear: '],
		['style', 'clip', 'clip: '],
		['style', 'color', 'color: '],
		['style', 'column-span', 'column-span: '],
		['style', 'content', 'content: '],
		['style', 'cue', 'cue: '],
		['style', 'cue-after', 'cue-after: '],
		['style', 'cue-before', 'cue-before: '],
		['style', 'cursor', 'cursor: '],
		['style', 'direction', 'direction: '],
		['style', 'display', 'display: '],
		['style', 'elevation', 'elevation: '],
		['style', 'filter', 'filter: '],
		['style', 'float', 'float: '],
		['style', 'font-family', 'font-family: '],
		['style', 'font-size', 'font-size: '],
		['style', 'font-size-adjust', 'font-size-adjust: '],
		['style', 'font-style', 'font-style: '],
		['style', 'font-variant', 'font-variant: '],
		['style', 'font-weight', 'font-weight: '],
		['style', 'height', 'height: '],
		['style', '!important', '!important: '],
		['style', 'left', 'left: '],
		['style', 'letter-spacing', 'letter-spacing: '],
		['style', 'line-height', 'line-height: '],
		['style', 'list-style', 'list-style: '],
		['style', 'list-style-image', 'list-style-image: '],
		['style', 'list-style-position', 'list-style-position: '],
		['style', 'list-style-type', 'list-style-type: '],
		['style', 'margin', 'margin: '],
		['style', 'margin-bottom', 'margin-bottom: '],
		['style', 'margin-left', 'margin-left: '],
		['style', 'margin-right', 'margin-right: '],
		['style', 'margin-top', 'margin-top: '],
		['style', 'marks', 'marks: '],
		['style', 'max-height', 'max-height: '],
		['style', 'min-height', 'min-height: '],
		['style', 'max-width', 'max-width: '],
		['style', 'min-width', 'min-width: '],
		['style', 'orphans', 'orphans: '],
		['style', 'overflow', 'overflow: '],
		['style', 'padding', 'padding: '],
		['style', 'padding-bottom', 'padding-bottom: '],
		['style', 'padding-left', 'padding-left: '],
		['style', 'padding-right', 'padding-right: '],
		['style', 'padding-top', 'padding-top: '],
		['style', 'page-break-after', 'page-break-after: '],
		['style', 'page-break-before', 'page-break-before: '],
		['style', 'pause', 'pause: '],
		['style', 'pause-after', 'pause-after: '],
		['style', 'pause-before', 'pause-before: '],
		['style', 'pitch', 'pitch: '],
		['style', 'pitch-range', 'pitch-range: '],
		['style', 'play-during', 'play-during: '],
		['style', 'position', 'position: '],
		['style', 'richness', 'richness: '],
		['style', 'right', 'right: '],
		['style', 'row-span', 'row-span: '],
		['style', 'size', 'size: '],
		['style', 'speak', 'speak: '],
		['style', 'speak-date', 'speak-date: '],
		['style', 'speak-header', 'speak-header: '],
		['style', 'speak-numeral', 'speak-numeral: '],
		['style', 'speak-punctuation', 'speak-punctuation: '],
		['style', 'speak-time', 'speak-time: '],
		['style', 'speech-rate', 'speech-rate: '],
		['style', 'stress', 'stress: '],
		['style', 'table-layout', 'table-layout: '],
		['style', 'text-align', 'text-align: '],
		['style', 'text-decoration', 'text-decoration: '],
		['style', 'text-indent', 'text-indent: '],
		['style', 'text-shadow', 'text-shadow: '],
		['style', 'text-transform', 'text-transform: '],
		['style', 'top', 'top: '],
		['style', 'vertical-align', 'vertical-align: '],
		['style', 'visibility', 'visibility: '],
		['style', 'voice-family', 'voice-family: '],
		['style', 'volume', 'volume: '],
		['style', 'white-space', 'white-space: '],
		['style', 'widows', 'widows: '],
		['style', 'width', 'width: '],
		['style', 'word-spacing', 'word-spacing: '],
		['style', 'z-index', 'z-index: ']
	],
	subTags: {
		'table': {
			'open': '<tbody><tr><td>',
			'close': '</td></tr></tbody>'
		}
	}
});
