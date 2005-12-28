/*
 * Remove Format Plugin for TYPO3 htmlArea RTE
 *
 * @author	Stanislas Rolland. Sponsored by Fructifor Inc.
 * Copyright (c) 2005 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 * Distributed under GPL.
 * This notice MUST stay intact for use.
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
};

RemoveFormat.I18N = RemoveFormat_langArray;

RemoveFormat._pluginInfo = {
	name          : "RemoveFormat",
	version       : "1.2",
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
	editor._popupDialog("plugin://RemoveFormat/removeformat", applyRequestFunctRef, editor, 285, 265);
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
					var regF1 = new RegExp("<\/?(abbr|acronym|b[^r]|big|cite|code|em|font|i|q|s|samp|small|span|strike|strong|sub|sup|u|var)[^>]*>", "gi"); 
					html = html.replace(regF1, "");
						// keep tags, strip attributes
					var regF2 = new RegExp(" style=\"[^>\"]*\"", "gi");
					var regF3 = new RegExp(" (class|align|cellpadding|cellspacing|frame|bgcolor)=(([^>\s\"]+)|(\"[^>]*\"))", "gi");
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
					var regMS4 = new RegExp(" (class|align)=(([^>\s\"]+)|(\"[^>]*\"))", "gi");
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
