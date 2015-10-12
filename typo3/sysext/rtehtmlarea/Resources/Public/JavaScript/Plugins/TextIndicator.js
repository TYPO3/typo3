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
 * TextIndicator Plugin for TYPO3 htmlArea RTE
 */
define(['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/DOM/DOM',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Color',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, UserAgent, Dom, Event, Color, Util) {

	var TextIndicator = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(TextIndicator, Plugin);
	Util.apply(TextIndicator.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '1.2',
				developer	: 'Stanislas Rolland',
				developerUrl	: 'http://www.sjbr.ca/',
				copyrightOwner	: 'Stanislas Rolland',
				sponsor		: 'SJBR',
				sponsorUrl	: 'http://www.sjbr.ca/',
				license		: 'GPL'
			};
			this.registerPluginInformation(pluginInformation);

			/**
			 * Registering the indicator
			 */
			var buttonId = 'TextIndicator';
			var textConfiguration = {
				id: buttonId,
				cls: 'indicator',
				text: 'A',
				tooltip: this.localize(buttonId.toLowerCase())
			};
			this.registerText(textConfiguration);
			return true;
		 },

		/**
		 * This handler gets called when the editor is generated
		 */
		onGenerate: function () {
			var self = this;
			// Ensure text indicator is updated AFTER style sheets are loaded
			var blockStylePlugin = this.getPluginInstance('BlockStyle');
			if (blockStylePlugin && blockStylePlugin.blockStyles) {
				// Monitor css parsing being completed
				Event.one(blockStylePlugin.blockStyles, 'HTMLAreaEventCssParsingComplete', function (event) { Event.stopEvent(event); self.onCssParsingComplete(); return false; }); 
			}
			var textStylePlugin = this.getPluginInstance('TextStyle');
			if (textStylePlugin && textStylePlugin.textStyles) {
				// Monitor css parsing being completed
				Event.one(textStylePlugin.textStyles, 'HTMLAreaEventCssParsingComplete', function (event) { Event.stopEvent(event); self.onCssParsingComplete(); return false; });
			}
		},

		/**
		 * This handler gets called when parsing of css classes is completed
		 */
		onCssParsingComplete: function () {
			var button = this.getButton('TextIndicator'),
				selection = this.editor.getSelection(),
				selectionEmpty = selection.isEmpty(),
				ancestors = selection.getAllAncestors(),
				endPointsInSameBlock = selection.endPointsInSameBlock();
			if (button) {
				this.onUpdateToolbar(button, this.getEditorMode(), selectionEmpty, ancestors, endPointsInSameBlock);
			}
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			var editor = this.editor;
			if (mode === 'wysiwyg' && editor.isEditable()) {
				var doc = editor.document;
				var style = {
					fontWeight: 'normal',
					fontStyle: 'normal'
				};
				try {
					//  Note: IE always reports FFFFFF as background color
					style.backgroundColor = Color.colorToRgb(doc.queryCommandValue((UserAgent.isIE || UserAgent.isWebKit) ? 'BackColor' : 'HiliteColor'));
					style.color = Color.colorToRgb(doc.queryCommandValue('ForeColor'));
					style.fontFamily = doc.queryCommandValue('FontName');
				} catch (e) { }
				// queryCommandValue does not work in Gecko
				if (UserAgent.isGecko) {
					var computedStyle = editor.iframe.getIframeWindow().getComputedStyle(editor.getSelection().getParentElement(), null);
					if (computedStyle) {
						style.color = computedStyle.getPropertyValue('color');
						style.backgroundColor = computedStyle.getPropertyValue('background-color');
						style.fontFamily = computedStyle.getPropertyValue('font-family');
					}
				}
				try {
					style.fontWeight = doc.queryCommandState('Bold') ? 'bold' : 'normal';
				} catch(e) {
					style.fontWeight = 'normal';
				}
				try {
					style.fontStyle = doc.queryCommandState('Italic') ? 'italic' : 'normal';
				} catch(e) {
					style.fontStyle = 'normal';
				}
				Dom.setStyle(button.getEl(), style);
			}
		}
	});

	return TextIndicator;

});
