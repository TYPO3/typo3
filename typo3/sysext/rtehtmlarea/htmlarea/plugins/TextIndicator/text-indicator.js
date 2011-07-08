/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * TextIndicator Plugin for TYPO3 htmlArea RTE
 */
HTMLArea.TextIndicator = Ext.extend(HTMLArea.Plugin, {
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: '1.1',
			developer	: 'Stanislas Rolland',
			developerUrl	: 'http://www.sjbr.ca/',
			copyrightOwner	: 'Stanislas Rolland',
			sponsor		: 'SJBR',
			sponsorUrl	: 'http://www.sjbr.ca/',
			license		: 'GPL'
		};
		this.registerPluginInformation(pluginInformation);
		
		/*
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

	/*
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
				style.backgroundColor = HTMLArea.util.Color.colorToRgb(doc.queryCommandValue((Ext.isIE || Ext.isWebKit) ? 'BackColor' : 'HiliteColor'));
				style.color = HTMLArea.util.Color.colorToRgb(doc.queryCommandValue('ForeColor'));
				style.fontFamily = doc.queryCommandValue('FontName');
			} catch (e) { }
				// queryCommandValue does not work in Gecko
			if (Ext.isGecko) {
				var computedStyle = editor._iframe.contentWindow.getComputedStyle(editor.getParentElement(), null);
				style.color = computedStyle.getPropertyValue('color');
				style.backgroundColor = computedStyle.getPropertyValue('background-color');
				style.fontFamily = computedStyle.getPropertyValue('font-family');
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
			button.getEl().setStyle(style);
		}
	}
});
