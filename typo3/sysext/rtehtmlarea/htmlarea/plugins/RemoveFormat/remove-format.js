/***************************************************************
*  Copyright notice
*
*  (c) 2005-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Remove Format Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
RemoveFormat = HTMLArea.Plugin.extend({

	constructor : function(editor, pluginName) {
		this.base(editor, pluginName);
	},

	/*
	 * This function gets called by the class constructor
	 */
	configurePlugin : function(editor) {

		/*
		 * Registering plugin "About" information
		 */
		var pluginInformation = {
			version		: "1.7",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.sjbr.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "SJBR",
			sponsorUrl	: "http://www.sjbr.ca/",
			license		: "GPL"
		};
		this.registerPluginInformation(pluginInformation);

		/*
		 * Registering the button
		 */
		var buttonId = "RemoveFormat";
		var buttonConfiguration = {
			id		: buttonId,
			tooltip		: this.localize(buttonId+"Tooltip"),
			action		: "onButtonPress",
			dialog		: true
		};
		this.registerButton(buttonConfiguration);

		this.popupWidth = 370;
		this.popupHeight = 260;

		return true;
	},

	/*
	 * This function gets called when the button was pressed.
	 *
	 * @param	object		editor: the editor instance
	 * @param	string		id: the button id or the key
	 *
	 * @return	boolean		false if action is completed
	 */
	onButtonPress : function (editor, id, target) {
			// Could be a button or its hotkey
		var buttonId = this.translateHotKey(id);
		buttonId = buttonId ? buttonId : id;

		this.dialog = this.openDialog("RemoveFormat", this.makeUrlFromPopupName("removeformat"), "applyRequest", null, {width:this.popupWidth, height:this.popupHeight});
		return false;
	},

	/*
	 * Perform the cleaning request
	 * .
	 */
	applyRequest : function(param) {

		var editor = this.editor;
		editor.focusEditor();

		if (param) {

			if (param["cleaning_area"] == "all") {
				var html = editor._doc.body.innerHTML;
			} else {
				var html = editor.getSelectedHTML();
 			}

			if (html) {

				if (param["html_all"]== true) {
					html = html.replace(/<[\!]*?[^<>]*?>/g, "");
				}

				if (param["formatting"] == true) {
						// Remove font, b, strong, i, em, u, strike, span and other inline tags
					html = html.replace(/<\/?(abbr|acronym|b|big|cite|code|em|font|i|q|s|samp|small|span|strike|strong|sub|sup|tt|u|var)(>|[^>a-zA-Z][^>]*>)/gi, '');
						// Keep tags, strip attributes
					html = html.replace(/[ \t\n\r]+(style|class|align|cellpadding|cellspacing|frame|bgcolor)=\"[^>\"]*\"/gi, "");
				}

				if (param["images"] == true) {
						// remove any IMG tag
					html = html.replace(/<\/?img[^>]*>/gi, ""); //remove img tags
				}

				if (param["ms_formatting"] == true) {
						// Make one line
					html = html.replace(/[ \r\n\t]+/g, " ");
						// Clean up tags
					html = html.replace(/<(b|strong|i|em|p|li|ul) [^>]*>/gi, "<$1>");
						// Keep tags, strip attributes
					html = html.replace(/ (style|class|align)=\"[^>\"]*\"/gi, "");
						// kill unwanted tags: span, div, ?xml:, st1:, [a-z]:, meta, link
					html = html.replace(/<\/?span[^>]*>/gi, "").
						replace(/<\/?div[^>]*>/gi, "").
						replace(/<\?xml:[^>]*>/gi, "").
						replace(/<\/?st1:[^>]*>/gi, "").
						replace(/<\/?[a-z]:[^>]*>/g, "").
						replace(/<\/?meta[^>]*>/g, "").
						replace(/<\/?link[^>]*>/g, "");
						// remove unwanted tags and their contents: style, title
					html = html.replace(/<style[^>]*>.*<\/style[^>]*>/gi, "").
						replace(/<title[^>]*>.*<\/title[^>]*>/gi, "");
						// remove comments
					html = html.replace(/<!--[^>]*>/gi, "");
						// Remove inline elements resets
					html = html.replace(/<\/(b[^a-zA-Z]|big|i[^a-zA-Z]|s[^a-zA-Z]|small|strike|tt|u[^a-zA-Z])><\1>/gi, "");
						// Remove double tags
					var oldlen = html.length + 1;
					while(oldlen > html.length) {
						oldlen = html.length;
							// Remove double opening tags
						html = html.replace(/<([a-z][a-z]*)> *<\/\1>/gi, " ").replace(/<([a-z][a-z]*)> *<\/?([a-z][^>]*)> *<\/\1>/gi, "<$2>");
							// Remove double closing tags
						html = html.replace(/<([a-z][a-z]*)><\1>/gi, "<$1>").replace(/<\/([a-z][a-z]*)><\/\1>/gi, "<\/$1>");
							// Remove multiple spaces
						html = html.replace(/[\x20]+/gi, " ");
					}
				}

				if (param["cleaning_area"] == "all") {
					editor._doc.body.innerHTML = html;
				} else {
					editor.insertHTML(html);
				}
			}
		} else {
			return false;
		}
	}
});

