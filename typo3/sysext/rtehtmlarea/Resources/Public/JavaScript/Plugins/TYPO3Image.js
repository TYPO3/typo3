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
 * TYPO3Image plugin for htmlArea RTE
 */
define('TYPO3/CMS/Rtehtmlarea/Plugins/TYPO3Image',
	['TYPO3/CMS/Rtehtmlarea/HTMLArea/Plugin/Plugin',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/UserAgent/UserAgent',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Event/Event',
	'TYPO3/CMS/Rtehtmlarea/HTMLArea/Util/Util'],
	function (Plugin, UserAgent, Event, Util) {

	var TYPO3Image = function (editor, pluginName) {
		this.constructor.super.call(this, editor, pluginName);
	};
	Util.inherit(TYPO3Image, Plugin);
	Util.apply(TYPO3Image.prototype, {

		/**
		 * This function gets called by the class constructor
		 */
		configurePlugin: function (editor) {
			this.pageTSConfiguration = this.editorConfiguration.buttons.image;
			this.imageModulePath = this.pageTSConfiguration.pathImageModule;

			/**
			 * Registering plugin "About" information
			 */
			var pluginInformation = {
				version		: '2.3',
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
			var buttonId = 'InsertImage';
			var buttonConfiguration = {
				id		: buttonId,
				tooltip		: this.localize(buttonId + '-Tooltip'),
				iconCls		: 'htmlarea-action-image-edit',
				action		: 'onButtonPress',
				hotKey		: (this.pageTSConfiguration ? this.pageTSConfiguration.hotKey : null),
				dialog		: true
			};
			this.registerButton(buttonConfiguration);
			return true;
		},

		/**
		 * This function gets called when the button was pressed
		 *
		 * @param	object		editor: the editor instance
		 * @param	string		id: the button id or the key
		 *
		 * @return	boolean		false if action is completed
		 */
		onButtonPress: function (editor, id) {
			// Could be a button or its hotkey
			var buttonId = this.translateHotKey(id);
			buttonId = buttonId ? buttonId : id;
			var additionalParameter;
			this.image = this.editor.getSelection().getParentElement();
			if (this.image && !/^img$/i.test(this.image.nodeName)) {
				this.image = null;
			}
			if (this.image) {
				additionalParameter = '&act=image';
			}
			this.openContainerWindow(
				buttonId,
				this.getButton(buttonId).tooltip.title,
				this.getWindowDimensions(
					{
						width:	650,
						height:	500
					},
					buttonId
				),
				this.makeUrlFromModulePath(this.imageModulePath, additionalParameter)
			);
			var self = this;
			Event.one(UserAgent.isIE ? this.editor.document.body : this.editor.document.documentElement, 'drop.TYPO3Image', function (event) { return self.onDrop(event); });
			return false;
		},

		/**
		 * Insert the image
		 * This function is called from the TYPO3 image script
		 */
		insertImage: function(image) {
			this.restoreSelection();
			this.editor.getSelection().insertHtml(image);
			this.close();
		},

		/**
		 * Handlers for drag and drop operations
		 */
		onDrop: function (event) {
			if (UserAgent.isWebKit) {
				this.editor.iframe.onDrop();
			}
			this.close();
			return true;
		},

		/**
		 * Remove the event listeners
		 */
		removeListeners: function () {
			Event.off(UserAgent.isIE ? this.editor.document.body : this.editor.document.documentElement, '.TYPO3Image');
		},

		/**
		 * This function gets called when the toolbar is updated
		 */
		onUpdateToolbar: function (button, mode, selectionEmpty, ancestors) {
			if (mode === 'wysiwyg' && this.editor.isEditable() && button.itemId === 'InsertImage' && !button.disabled) {
				var image = this.editor.getSelection().getParentElement();
				if (image && !/^img$/i.test(image.nodeName)) {
					image = null;
				}
				if (image) {
					button.setTooltip({ title: this.localize('Modify image') });
				} else {
					button.setTooltip({ title: this.localize('Insert image') });
				}
			}
		}
	});

	return TYPO3Image;

});
