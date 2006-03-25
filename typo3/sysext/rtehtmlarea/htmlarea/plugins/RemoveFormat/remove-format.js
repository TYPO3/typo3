/***************************************************************
*  Copyright notice
*
*  (c) 2005, 2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * TYPO3 CVS ID: $Id$
 */

RemoveFormat = function(editor) {
	this.editor = editor;
	var cfg = editor.config;
	var actionHandlerFunctRef = RemoveFormat.actionHandler(this);
	cfg.registerButton({
		id		: "RemoveFormat",
		tooltip		: RemoveFormat_langArray["RemoveFormatTooltip"],
		image		: editor.imgURL("ed_clean.gif", "RemoveFormat"),
		textMode	: false,
		action		: actionHandlerFunctRef
	});
	
	this.popupWidth = 285;
	this.popupHeight = 255;
};

RemoveFormat.I18N = RemoveFormat_langArray;

RemoveFormat._pluginInfo = {
	name          : "RemoveFormat",
	version       : "1.5",
	developer     : "Stanislas Rolland",
	developer_url : "http://www.fructifor.ca/",
	sponsor       : "Fructifor Inc.",
	sponsor_url   : "http://www.fructifor.ca/",
	license       : "GPL"
};

RemoveFormat.actionHandler = function(instance) {
	return (function(editor) {
		instance.buttonPress(editor);
	});
};

RemoveFormat.prototype.buttonPress = function(editor){
	var applyRequestFunctRef = RemoveFormat.applyRequest(this, editor);
	editor._popupDialog("plugin://RemoveFormat/removeformat", applyRequestFunctRef, editor, this.popupWidth, this.popupHeight);
};

RemoveFormat.applyRequest = function(instance,editor){
	return(function(param) {
		editor.focusEditor();

		if (param) {

			if (param["cleaning_area"] == "all") {
				var html = editor._doc.body.innerHTML;
			} else {
				var html = editor.getSelectedHTML();
 			}

			if(html) {

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
						// kill unwanted tags: span, div, ?xml:, st1:, [a-z]: 
					html = html.replace(/<\/?span[^>]*>/gi, "").
						replace(/<\/?div[^>]*>/gi, "").
						replace(/<\?xml:[^>]*>/gi, "").
						replace(/<\/?st1:[^>]*>/gi, "").
						replace(/<\/?[a-z]:[^>]*>/g, "");
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
	});
};
