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
 * TYPO3 Color Plugin for TYPO3 htmlArea RTE
 */
define([
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Color',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util',
	'jquery',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Severity'
], function (Plugin, UserAgent, Dom, Color, Util, $, Modal, Severity) {

	var TYPO3Color = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(TYPO3Color, Plugin);
	Util.apply(TYPO3Color.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.buttonsConfiguration = this.editorConfiguration.buttons;
			this.disableColorPicker = this.editorConfiguration.disableColorPicker;
				// Coloring will use the style attribute
			if (this.getPluginInstance('TextStyle')) {
				this.getPluginInstance('TextStyle').addAllowedAttribute('style');
				this.allowedAttributes = this.getPluginInstance('TextStyle').allowedAttributes;
			}
			if (this.getPluginInstance('InlineElements')) {
				this.getPluginInstance('InlineElements').addAllowedAttribute('style');
				if (!this.allowedAllowedAttributes) {
					this.allowedAttributes = this.getPluginInstance('InlineElements').allowedAttributes;
				}
			}
			if (this.getPluginInstance('BlockElements')) {
				this.getPluginInstance('BlockElements').addAllowedAttribute('style');
			}
			if (!this.allowedAttributes) {
				this.allowedAttributes = ['id', 'title', 'lang', 'xml:lang', 'dir', 'class', 'style'];
			}
			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '4.3',
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
			var buttonList = this.buttonList, buttonId;
			for (var i = 0; i < buttonList.length; ++i) {
				var button = buttonList[i];
				buttonId = button[0];
				var buttonConfiguration = {
					id		: buttonId,
					tooltip		: this.localize(buttonId),
					iconCls		: 'htmlarea-action-' + button[2],
					action		: 'onButtonPress',
					hotKey		: (this.buttonsConfiguration[button[1]] ? this.buttonsConfiguration[button[1]].hotKey : null),
					dialog		: true
				};
				this.registerButton(buttonConfiguration);
			}
			return true;
		 },
		/**
		 * The list of buttons added by this plugin
		 */
		buttonList: [
			['ForeColor', 'textcolor', 'color-foreground'],
			['HiliteColor', 'bgcolor', 'color-background']
		],
		/**
		 * Conversion object: button name to corresponding style property name
		 */
		styleProperty: {
			ForeColor	: 'color',
			HiliteColor	: 'backgroundColor'
		},
		/**
		 * This function gets called when the button was pressed.
		 *
		 * @param {Object} editor The editor instance
		 * @param {String} id The button id or the key
		 * @param {Object} target The target element of the contextmenu event, when invoked from the context menu
		 *
		 * @return {Boolean} false if action is completed
		 */
		onButtonPress: function (editor, id, target) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			var element = this.editor.getSelection().getParentElement();
			this.openDialogue(
				buttonId + '_title',
				{
					element: element,
					buttonId: buttonId
				},
				this.buildItemsConfig(element, buttonId),
				this.setColor
			);
		},
		/**
		 * Build the window items config
		 */
		buildItemsConfig: function (element, buttonId) {
			var $form = $('<form />', {'class': 'form-horizontal'}),
				$input = $('<input />', {'class': 'form-control t3js-color-input'}),
				activeColor = '';

			if (element && element.style[this.styleProperty[buttonId]]) {
				activeColor = Color.colorToHex(element.style[this.styleProperty[buttonId]]);
			}

			$input.val(activeColor);

			require(['TYPO3/CMS/Core/Contrib/jquery.minicolors'], function () {
				$input.minicolors({
					theme: 'bootstrap',
					format: 'hex',
					position: 'bottom left'
				});
			});

			$form.append(
				$('<div />', {'class': 'form-section'}).append(
					$('<div />', {'class': 'form-group'}).append(
						$('<label />', {'class': 'col-sm-2'}).text(this.localize(buttonId)),
						$('<div />', {'class': 'col-sm-10'}).append($input)
					)
				)
			);

			return $form;
		},
		/**
		 * Open the dialogue window
		 *
		 * @param {String} title The window title
		 * @param {Object} arguments Some arguments for the handler
		 * @param {Object} items The configuration of the tabbed panel
		 * @param {Function} handler Handler when the OK button if clicked
		 */
		openDialogue: function (title, arguments, items, handler) {
			var self = this;
			this.dialog = Modal.confirm(this.localize(title), items, Severity.notice);
			this.dialog.arguments = arguments;

			this.dialog.on('confirm.button.ok', $.proxy(handler, this))
				.on('confirm.button.cancel', function () {
					Modal.currentModal.trigger('modal-dismiss');
					self.onCancel();
				});
		},
		/**
		 * Set the color and close the dialogue
		 *
		 * @param {Event} event
		 */
		setColor: function(event) {
			this.restoreSelection();
			var buttonId = this.dialog.arguments.buttonId;
			var color = this.dialog.find('.t3js-color-input').val();
			if (color) {
				if (color.indexOf('#') === 0) {
					color = color.substr(1);
				}
				color = Color.colorToHex(parseInt(color, 16));
			}
			var element,
				fullNodeSelected = false;
			var range = this.editor.getSelection().createRange();
			var parent = this.editor.getSelection().getParentElement();
			var selectionEmpty = this.editor.getSelection().isEmpty();
			var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null;
			if (!selectionEmpty) {
				var fullySelectedNode = this.editor.getSelection().getFullySelectedNode();
				if (fullySelectedNode) {
					fullNodeSelected = true;
					parent = fullySelectedNode;
				}
			}
			if (selectionEmpty || fullNodeSelected) {
				element = parent;
					// Set the color in the style attribute
				element.style[this.styleProperty[buttonId]] = color;
					// Remove the span tag if it has no more attribute
				if (/^span$/i.test(element.nodeName) && !Dom.hasAllowedAttributes(element, this.allowedAttributes)) {
					this.editor.getDomNode().removeMarkup(element);
				}
			} else if (statusBarSelection) {
				element = statusBarSelection;
					// Set the color in the style attribute
				element.style[this.styleProperty[buttonId]] = color;
					// Remove the span tag if it has no more attribute
				if (/^span$/i.test(element.nodeName) && !Dom.hasAllowedAttributes(element, this.allowedAttributes)) {
					this.editor.getDomNode().removeMarkup(element);
				}
			} else if (color && this.editor.getSelection().endPointsInSameBlock()) {
				element = this.editor.document.createElement('span');
					// Set the color in the style attribute
				element.style[this.styleProperty[buttonId]] = color;
				this.editor.getDomNode().wrapWithInlineElement(element, range);
			}
			Modal.currentModal.trigger('modal-dismiss');
			event.stopImmediatePropagation();
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors, endPointsInSameBlock) {
			if (mode === 'wysiwyg' && this.editor.isEditable()) {
				var statusBarSelection = this.editor.statusBar ? this.editor.statusBar.getSelection() : null,
					parentElement = statusBarSelection ? statusBarSelection : this.editor.getSelection().getParentElement(),
					disabled = !endPointsInSameBlock || (selectionEmpty && /^body$/i.test(parentElement.nodeName));
				button.setInactive(!parentElement.style[this.styleProperty[button.itemId]]);
				button.setDisabled(disabled);
			}
		}
	});

	return TYPO3Color;

});
