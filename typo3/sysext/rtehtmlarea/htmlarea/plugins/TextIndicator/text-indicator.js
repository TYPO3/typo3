/***************************************************************
*  Copyright notice
*
*  (c) 2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 *
 * TYPO3 SVN ID: $Id: text-indicator.js 6539 2009-11-25 14:49:14Z stucki $
 */
HTMLArea.TextIndicator = HTMLArea.Plugin.extend({
		
	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},
	
	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function (editor) {
		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.0",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
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
			var doc = editor._doc;
			try {
				var style = {
					backgroundColor: HTMLArea._makeColor(doc.queryCommandValue((Ext.isIE || Ext.isWebKit) ? 'BackColor' : 'HiliteColor')),
					color: HTMLArea._makeColor(doc.queryCommandValue('ForeColor')),
					fontFamily: doc.queryCommandValue('FontName'),
					fontWeight: 'normal',
					fontStyle: 'normal'
				};
					// Mozilla
				if (/transparent/i.test(style.backgroundColor)) {
					style.backgroundColor = HTMLArea._makeColor(doc.queryCommandValue('BackColor'));
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
			} catch (e) { }
		}
	}
});
