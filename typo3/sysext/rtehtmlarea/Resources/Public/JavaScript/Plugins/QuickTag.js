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
 * Quick Tag Editor Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Notification',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, Util, $, Modal, Notification, Severity) {

	var QuickTag = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(QuickTag, Plugin);
	Util.apply(QuickTag.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.pageTSConfiguration = this.editorConfiguration.buttons.inserttag;
			this.allowedTags = (this.pageTSConfiguration && this.pageTSConfiguration.tags) ? this.pageTSConfiguration.tags : null;
			this.denyTags = (this.pageTSConfiguration && this.pageTSConfiguration.denyTags) ? this.pageTSConfiguration.denyTags : null;
			this.allowedAttribs =  (this.pageTSConfiguration && this.pageTSConfiguration.allowedAttribs) ? this.pageTSConfiguration.allowedAttribs : null;
			this.quotes = new RegExp('^\w+\s*([a-zA-Z_0-9:;]+=\"[^\"]*\"\s*|[a-zA-Z_0-9:;]+=\'[^\']*\'\s*)*$');

			/**
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
			/**
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
		/**
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
		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 * @return {Boolean} False if action is completed
		 */
		onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			this.openDialogue(
				'Quick Tag Editor',
				{
					buttonId: buttonId
				},
				this.buildItemsConfig(),
				this.setTag
			);
			this.insertedTag = this.dialog.find('[name="insertedTag"]');
			this.tagCombo = this.dialog.find('[name="tags"]');
			this.attributeCombo = this.dialog.find('[name="attributes"]');
			this.valueCombo = this.dialog.find('[name="values"]');
			this.colorCombo = this.dialog.find('[name="colors"]');
		},
		/**
		 * Build the window items config
		 */
		buildItemsConfig: function () {
			var self = this,
				filteredTags = [],
				$fieldset = $('<fieldset />'),
				$tagSelect = $('<select />', {name: 'tags', 'class': 'form-control'}),
				$attributeSelect = $('<select />', {name: 'attributes', 'class': 'form-control'}),
				$valueSelect = $('<select />', {name: 'values', 'class': 'form-control'}),
				$colorInput = $('<input />', {name: 'colors', 'class': 'form-control t3js-color-input'});

			if (this.denyTags) {
				var denyTags = new RegExp('^(' + this.denyTags.split(',').join('|').replace(/ /g, '') + ')$', 'i');
				for (var i = 0; i < this.tags.length; i++) {
					if (!denyTags.test(this.tags[i][1])) {
						filteredTags.push(this.tags[i]);
					}
				}
			}

			this.captureClasses($valueSelect);

			for (var tag in filteredTags) {
				if (filteredTags.hasOwnProperty(tag)) {
					$tagSelect.append(
						$('<option />', {value: encodeURI(filteredTags[tag][1])}).text(filteredTags[tag][0])
					);
				}
			}

			for (var attribute in this.attributes) {
				if (this.attributes.hasOwnProperty(attribute)) {
					$attributeSelect.append(
						$('<option />', {
							value: encodeURI(this.attributes[attribute][2]),
							'data-tag': this.attributes[attribute][0]
						}).text(this.attributes[attribute][1])
					);
				}
			}

			for (var value in this.values) {
				if (this.values.hasOwnProperty(value)) {
					$valueSelect.append(
						$('<option />', {
							value: encodeURI(this.values[value][2]),
							'data-attribute': this.values[value][0]
						}).text(this.values[value][1])
					);
				}
			}

			require(['TYPO3/CMS/Core/Contrib/jquery.minicolors'], function () {
				$colorInput.minicolors({
					theme: 'bootstrap',
					format: 'hex',
					position: 'bottom left',
					changeDelay: 50
				});
			});

			$fieldset.append(
				$('<h4 />', {'class': 'form-section-headline'}).text(this.localize('Quick Tag Editor')),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text('<'),
					$('<div />', {'class': 'col-sm-8'}).append(
						$('<textarea />', {name: 'insertedTag', 'class': 'form-control'})
							.on('change focus', $.proxy(this.filterAttributes, this))
					),
					$('<label />', {'class': 'col-sm-2'}).text('>')
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('TAGs')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$tagSelect
							.on('change', $.proxy(this.onTagSelect, this))
					)
				),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('ATTRIBUTES')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$attributeSelect
							.on('change', $.proxy(this.onAttributeSelect, this))
					)
				).hide(),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('OPTIONS')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$valueSelect
							.on('change', $.proxy(this.onValueSelect, this))
					)
				).hide(),
				$('<div />', {'class': 'form-group'}).append(
					$('<label />', {'class': 'col-sm-2'}).text(this.localize('Colors')),
					$('<div />', {'class': 'col-sm-10'}).append(
						$colorInput
							.on('change', $.proxy(this.onColorSelect, this))
					)
				).hide()
			);

			return $('<form />', {'class': 'form-horizontal'}).append($fieldset);
		},
		/**
		 * Add a record for each class selector found in the stylesheets
		 *
		 * @param {Object} $valueSelect
		 */
		captureClasses: function ($valueSelect) {
			this.parseCssRule(this.editor.document.styleSheets, $valueSelect);
		},
		/**
		 * @param {Object} rules
		 * @param {Object} $valueSelect
		 */
		parseCssRule: function (rules, $valueSelect) {
			for (var i = 0, n = rules.length; i < n; i++) {
				var rule = rules[i];
				if (rule.selectorText) {
					if (/^(\w*)\.(\w+)$/.test(rule.selectorText)) {
						$valueSelect.append(
							$('<option />', {
								value: RegExp.$2 + '"',
								'data-attribute': 'class'
							}).text(rule.selectorText)
						);
					}
				} else {
					// ImportRule (Mozilla)
					if (rule.styleSheet) {
						try {
							if (rule.styleSheet.cssRules) {
								this.parseCssRule(rule.styleSheet.cssRules, $valueSelect);
							}
						} catch (e) {
							if (/Security/i.test(e)) {
								this.appendToLog('parseCssRule', 'A security error occurred. Make sure all stylesheets are accessed from the same domain/subdomain and using the same protocol as the current script.', 'error');
							} else {
								throw e;
							}
						}
					}
					// MediaRule (Mozilla)
					if (rule.cssRules) {
						this.parseCssRule(rule.cssRules, $valueSelect);
					}
					// IE imports
					if (rule.imports) {
						this.parseCssRule(rule.imports, $valueSelect);
					}
					if (rule.rules) {
						this.parseCssRule(rule.rules, $valueSelect);
					}
				}
			}
		},
		/**
		 * Handler invoked when a tag is selected
		 * Update the attributes combo and the inserted tag field
		 *
		 * @param {Event} e
		 */
		onTagSelect: function (e) {
			var $me = $(e.currentTarget),
				tag = decodeURI($me.val());
			this.filterAttributes(e);
			this.attributeCombo.val('');
			this.attributeCombo.closest('.form-group').show();
			this.valueCombo.closest('.form-group').hide();
			this.insertedTag.val(tag).focus();
		},
		/**
		 * Filter out attributes not applicable to the tag, already present in the tag or not allowed
		 *
		 * @param {Event} event
		 */
		filterAttributes: function (event) {
			var $me = $(event.currentTarget),
				tag = decodeURI($me.val()),
				insertedTag = this.insertedTag.val(),
				allowedAttribs = '';

			this.attributeCombo.find('.hidden').removeClass('hidden');

			if (this.allowedAttribs) {
				allowedAttribs = this.allowedAttribs.split(',').join('|').replace(/ /g, '');
			}
			if (this.tags && this.tags[tag] && this.tags[tag].allowedAttribs) {
				allowedAttribs += allowedAttribs ? '|' : '';
				allowedAttribs += this.tags[tag].allowedAttribs.split(',').join('|').replace(/ /g, '');
			}
			if (allowedAttribs) {
				allowedAttribs = new RegExp('^(' + allowedAttribs + ')$');
			}
			this.attributeCombo.children().each(function() {
				var $me = $(this),
					testAttrib = new RegExp('(' + decodeURI($me.val()) + ')', 'ig'),
					tagValue = $me.data('tag');

				if (tagValue !== 'all'
					&& tagValue !== tag
					|| testAttrib.test(insertedTag)
					|| allowedAttribs
					&& !allowedAttribs.test($me.text())
				) {
					$me.addClass('hidden');
				}
			});
		},
		/**
		 * Filter out not applicable to the attribute or style values already present in the tag
		 * Filter out classes not applicable to the current tag
		 *
		 * @param {String} attribute
		 */
		filterValues: function (attribute) {
			var tag = decodeURI(this.tagCombo.val()),
				insertedTag = this.insertedTag.val(),
				expr = new RegExp('(^' + tag + '[\.])|(^[\.])', 'i');

			this.valueCombo.find('.hidden').removeClass('hidden');
			this.valueCombo.find('option').each(function() {
				var $me = $(this),
					value = decodeURI($me.val());

				if (attribute === 'style') {
					expr = new RegExp('(' + ((value.charAt(0) === '+' || value.charAt(0) === '-') ? '\\' : '') + value + ')', 'ig');
				}
				if (!($me.data('attribute') === attribute
					&& (
						attribute !== 'style'
						|| !expr.test(insertedTag)
					) && (
						attribute !== 'class'
						|| expr.test($me.text())
					)
				)) {
					$me.addClass('hidden');
				}
			});

			this.valueCombo.closest('.form-group').toggle(this.valueCombo.find('option:not(.hidden)').length > 0);
		},
		/**
		 * Handler invoked when an attribute is selected
		 * Update the values combo and the inserted tag field
		 *
		 * @param {Event} event
		 */
		onAttributeSelect: function (event) {
			var $me = $(event.currentTarget),
				insertedTag = this.insertedTag.val(),
				attribute = $me.find('option:selected').text();

			this.valueCombo.val('');
			if (/color/.test(attribute)) {
				this.valueCombo.closest('.form-group').hide();
				this.colorCombo.closest('.form-group').show();
			} else {
				this.filterValues(attribute);
			}
			this.insertedTag
				.val(insertedTag + ((/\"/.test(insertedTag) && (!/\"$/.test(insertedTag) || /=\"$/.test(insertedTag))) ? '" ' : ' ') + decodeURI($me.val()))
				.focus();
		},
		/**
		 * Handler invoked when a value is selected
		 * Update the inserted tag field
		 *
		 * @param {Event} e
		 */
		onValueSelect: function (e) {
			var $me = $(e.currentTarget),
				style = decodeURI(this.attributeCombo.val()) === 'style="';

			this.insertedTag
				.val(this.insertedTag.val() + (style && !/="$/.test(this.insertedTag.val()) ? '; ' : '') + decodeURI($me.val()))
				.focus();

			if (style) {
				if (/color/.test($me.find('option:selected').text())) {
					this.colorCombo.closest('.form-group').show();
				}
			} else {
				$me.closest('.form-group').hide();
				this.attributeCombo.val('');
			}
			$me.val('');
		},
		/**
		 * Handler invoked when a color is selected
		 * Update the inserted tag field
		 *
		 * @param {Event} event
		 */
		onColorSelect: function (event) {
			var $me = $(event.currentTarget),
				style = decodeURI(this.attributeCombo.val()) === 'style="';

			this.insertedTag
				.val(this.insertedTag.val() + decodeURI($me.val()) + (style ? '' : '"'))
				.focus();

			$me.val('');
			$me.closest('.form-group').hide();
			if (!style) {
				this.attributeCombo.clearValue();
			}
		},
		/**
		 * Handler invoked when a OK button is pressed
		 *
		 * @param {Event} event
		 */
		setTag: function (event) {
			this.restoreSelection();
			var insertedTag = this.insertedTag.val();
			var currentTag = this.tagCombo.val();
			if (!insertedTag) {
				Notification.info(
					this.getButton('InsertTag').tooltip.title,
					this.localize('Enter the TAG you want to insert')
				);
				this.insertedTag.focus();
				event.stopImmediatePropagation();
				return false;
			}
			if (!this.quotes.test(insertedTag)) {
				insertedTag += '"';
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
			event.stopImmediatePropagation();
		},
		/**
		 * Open the dialogue window
		 *
		 * @param {String} title The window title
		 * @param {Object} arguments Some arguments for the handler
		 * @param {Object} items The configuration of the window items
		 * @param {Function} handler Handler when the OK button if clicked
		 */
		openDialogue: function (title, arguments, items, handler) {
			this.dialog = Modal.show(title, items, Severity.notice, [
				this.buildButtonConfig('Cancel', $.proxy(this.onCancel, this), true),
				this.buildButtonConfig('OK', $.proxy(handler, this), false, Severity.notice)
			]);
			this.dialog.arguments = arguments;
			this.dialog.on('modal-dismiss', $.proxy(this.onClose, this));
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

	return QuickTag;
});
