/***************************************************************
*  Copyright notice
*
*  (c) 2005-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
			version		: "1.6",
			developer	: "Stanislas Rolland",
			developerUrl	: "http://www.fructifor.ca/",
			copyrightOwner	: "Stanislas Rolland",
			sponsor		: "Fructifor Inc.",
			sponsorUrl	: "http://www.fructifor.ca/",
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
						// remove font, b, strong, i, em, u, strike, span and other tags
					var regF1 = new RegExp("<\/?(abbr|acronym|b[^a-zA-Z]|big|cite|code|em[^a-zA-Z]|font|i[^a-zA-Z]|q|s[^a-zA-Z]|samp|small|span|strike|strong|sub|sup|u[^a-zA-Z]|var)[^>]*>", "gi");
					html = html.replace(regF1, "");
						// keep tags, strip attributes
					var regF2 = new RegExp(" style=\"[^>\"]*\"", "gi");
					var regF3 = new RegExp(" (class|align|cellpadding|cellspacing|frame|bgcolor)=(([^>\s\"]+)|(\"[^>\"]*\"))", "gi");
					html = html.replace(regF2, "").replace(regF3, "");
				}

				if (param["images"] == true) {
						// remove any IMG tag
					html = html.replace(/<\/?img[^>]*>/gi, ""); //remove img tags
				}

				if (param["ms_formatting"] == true) {
						// make one line
					var regMS1 = new RegExp("(\r\n|\n|\r)", "g");
					html = html.replace(regMS1, " ");
						//clean up tags
					var regMS2 = new RegExp("<(b[^r]|strong|i|em|p|li|ul) [^>]*>", "gi");
					html = html.replace(regMS2, "<$1>");
						// keep tags, strip attributes
					var regMS3 = new RegExp(" style=\"[^>\"]*\"", "gi");
					var regMS4 = new RegExp(" (class|align)=(([^>\s\"]+)|(\"[^>\"]*\"))", "gi");
					html = html.replace(regMS3, "").replace(regMS4, "");
						// mozilla doesn't like <em> tags
					html = html.replace(/<em>/gi, "<i>").replace(/<\/em>/gi, "</i>");
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
						// remove double tags
					oldlen = html.length + 1;
					var reg6 = new RegExp("<([a-z][a-z]*)> *<\/\1>", "gi");
					var reg7 = new RegExp("<([a-z][a-z]*)> *<\/?([a-z][^>]*)> *<\/\1>", "gi");
					var reg8 = new RegExp("<([a-z][a-z]*)><\1>", "gi");
					var reg9 = new RegExp("<\/([a-z][a-z]*)><\/\1>", "gi");
					var reg10 = new RegExp("[\x20]+", "gi");
					while(oldlen > html.length) {
						oldlen = html.length;
							// join us now and free the tags
						html = html.replace(reg6, " ").replace(reg7, "<$2>");
							// remove double tags
						html = html.replace(reg8, "<$1>").replace(reg9, "<\/$1>");
							// remove double spaces
						html = html.replace(reg10, " ");
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

