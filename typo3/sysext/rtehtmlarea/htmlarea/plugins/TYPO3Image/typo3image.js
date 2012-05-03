/***************************************************************
*  Copyright notice
*
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
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * TYPO3Image plugin for htmlArea RTE
 */
HTMLArea.TYPO3Image = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin: function (editor) {
		this.pageTSConfiguration = this.editorConfiguration.buttons.image;
		this.imageModulePath = this.pageTSConfiguration.pathImageModule;
		/*
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
		/*
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
	/*
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
		this.dialog.mon(Ext.get(Ext.isIE ? this.editor.document.body : this.editor.document.documentElement), 'drop', this.onDrop, this, {single: true});
		return false;
	},
	/*
	 * Insert the image
	 * This function is called from the TYPO3 image script
	 */
 	insertImage: function(image) {
		this.restoreSelection();
		this.editor.getSelection().insertHtml(image);
		this.close();
	},
	/*
	 * Handlers for drag and drop operations
	 */
	onDrop: function (event) {
		if (Ext.isWebKit) {
			this.editor.iframe.onDrop();
		}
		this.close();
	},
	/*
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
