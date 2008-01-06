/***************************************************************
*  Copyright notice
*
*  (c) 2002-2004, interactivetools.com, inc.
*  (c) 2003-2004 dynarch.com
*  (c) 2004-2007 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Main script of TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

/***************************************************
 *  EDITOR INITIALIZATION AND CONFIGURATION
 ***************************************************/

/*
 * Set some basic paths
 */
if (typeof(_editor_url) == "string") {
		// Leave exactly one backslash at the end of _editor_url
	_editor_url = _editor_url.replace(/\x2f*$/, '/');
} else {
	alert("WARNING: _editor_url is not set!");
	var _editor_url = '';
}
if (typeof(_editor_skin) == "string") _editor_skin = _editor_skin.replace(/\x2f*$/, '/');
	else var _editor_skin = _editor_url + "skins/default/";
if (typeof(_editor_CSS) != "string") var _editor_CSS = _editor_url + "skins/default/htmlarea.css";
if (typeof(_editor_edited_content_CSS) != "string") var _editor_edited_content_CSS = _editor_skin + "htmlarea-edited-content.css";
if (typeof(_editor_lang) == "string") _editor_lang = _editor_lang ? _editor_lang.toLowerCase() : "en";

/*
 * HTMLArea object constructor.
 */
var HTMLArea = function(textarea, config) {
	if (HTMLArea.checkSupportedBrowser()) {
		if (typeof(config) == "undefined") this.config = new HTMLArea.Config();
			else this.config = config;
		this._htmlArea = null;
		this._textArea = textarea;
		this._editMode = "wysiwyg";
		this.plugins = {};
		this._timerToolbar = null;
		this._undoQueue = new Array();
		this._undoPos = -1;
		this._customUndo = true;
		this.doctype = '';
		this.eventHandlers = {};
	}
};

HTMLArea.editorCSS = _editor_CSS;

/*
 * Browser identification
 */
HTMLArea.agt = navigator.userAgent.toLowerCase();
HTMLArea.is_opera  = (HTMLArea.agt.indexOf("opera") != -1);
HTMLArea.is_ie = (HTMLArea.agt.indexOf("msie") != -1) && !HTMLArea.is_opera;
HTMLArea.is_safari = (HTMLArea.agt.indexOf("webkit") != -1);
HTMLArea.is_gecko  = (navigator.product == "Gecko") || HTMLArea.is_opera;
// Check on MacOS Wamcom version 1.3 but exclude Firefox rv 1.8.1.3
HTMLArea.is_wamcom = (HTMLArea.agt.indexOf("wamcom") != -1) || (HTMLArea.is_gecko && HTMLArea.agt.indexOf("1.3") != -1 && HTMLArea.agt.indexOf(".1.3") == -1);

/*
 * A log for troubleshooting
 */
HTMLArea._debugMode = false;
if (typeof(_editor_debug_mode) != "undefined") HTMLArea._debugMode = _editor_debug_mode;

HTMLArea._appendToLog = function(str){
	if(HTMLArea._debugMode) {
		var log = document.getElementById("HTMLAreaLog");
		if(log) {
			log.appendChild(document.createTextNode(str));
			log.appendChild(document.createElement("br"));
		}
	}
};

/*
 * Using compressed scripts
 */
HTMLArea._compressedScripts = false;
if (typeof(_editor_compressed_scripts) != "undefined") HTMLArea._compressedScripts = _editor_compressed_scripts;

/*
 * Localization of core script
 */
HTMLArea.I18N = HTMLArea_langArray;

/*
 * Build array of scripts to be loaded
 */
HTMLArea.is_loaded = false;
HTMLArea.onload = function(){ 
	HTMLArea.is_loaded = true; 
	HTMLArea._appendToLog("All scripts successfully loaded.");
};
HTMLArea.loadTimer;
HTMLArea._scripts = [];
HTMLArea._scriptLoaded = [];
HTMLArea._request = [];
HTMLArea.loadScript = function(url, plugin) {
	if (plugin) url = _editor_url + "/plugins/" + plugin + '/' + url;
	if (HTMLArea.is_opera) url = _typo3_host_url + url;
	if (HTMLArea._compressedScripts && url.indexOf("compressed") == -1) url = url.replace(/\.js$/gi, "_compressed.js");
	HTMLArea._scripts.push(url);
};
HTMLArea.loadScript(RTEarea[0]["popupwin"] ? RTEarea[0]["popupwin"] : _editor_url + "popupwin.js");
if(HTMLArea.is_gecko) HTMLArea.loadScript(RTEarea[0]["htmlarea-gecko"] ? RTEarea[0]["htmlarea-gecko"] : _editor_url + "htmlarea-gecko.js");
if(HTMLArea.is_ie) HTMLArea.loadScript(RTEarea[0]["htmlarea-ie"] ? RTEarea[0]["htmlarea-ie"] : _editor_url + "htmlarea-ie.js");

/*
 * Get a script using asynchronous XMLHttpRequest
 */
HTMLArea.MSXML_XMLHTTP_PROGIDS = new Array("Msxml2.XMLHTTP.5.0", "Msxml2.XMLHTTP.4.0", "Msxml2.XMLHTTP.3.0", "Msxml2.XMLHTTP", "Microsoft.XMLHTTP");
HTMLArea.XMLHTTPResponseHandler = function (i) {
	return (function() {
		var url = HTMLArea._scripts[i];
		if (HTMLArea._request[i].readyState != 4) return;
		if (HTMLArea._request[i].status == 200) { 
			try {
				eval(HTMLArea._request[i].responseText);
				HTMLArea._scriptLoaded[i] = true;
				i = null;
			} catch (e) {
				HTMLArea._appendToLog("ERROR [HTMLArea::getScript]: Unable to get script " + url + ": " + e);
			}
		} else {
			HTMLArea._appendToLog("ERROR [HTMLArea::getScript]: Unable to get " + url + " . Server reported " + HTMLArea._request[i].status);
		}
	});
};
HTMLArea._getScript = function (i,asynchronous,url) {
	if (typeof(url) == "undefined") var url = HTMLArea._scripts[i];
	if (typeof(asynchronous) == "undefined") var asynchronous = true;
	if (window.XMLHttpRequest) HTMLArea._request[i] = new XMLHttpRequest();
		else if (window.ActiveXObject) {
			var success = false;
			for (var k = 0; k < HTMLArea.MSXML_XMLHTTP_PROGIDS.length && !success; k++) {
				try {
					HTMLArea._request[i] = new ActiveXObject(HTMLArea.MSXML_XMLHTTP_PROGIDS[k]);
					success = true;
				} catch (e) { }
			}
			if (!success) return false;
		}
	var request = HTMLArea._request[i];
	if (request) {
		request.open("GET", url, asynchronous);
		if (asynchronous) request.onreadystatechange = HTMLArea.XMLHTTPResponseHandler(i);
		if (window.XMLHttpRequest) request.send(null);
			else if (window.ActiveXObject) request.send();
		if (!asynchronous) {
			if (request.status == 200) return request.responseText;
				else return '';
		}
		return true;
	} else {
		return false;
	}
};

/*
 * Wait for the loading process to complete
 */
HTMLArea.checkInitialLoad = function() {
	var scriptsLoaded = true;
	for (var i = HTMLArea._scripts.length; --i >= 0;) {
		scriptsLoaded = scriptsLoaded && HTMLArea._scriptLoaded[i];
	}
	if(HTMLArea.loadTimer) window.clearTimeout(HTMLArea.loadTimer);
	if (scriptsLoaded) {
		HTMLArea.is_loaded = true;
		HTMLArea._appendToLog("[HTMLArea::init]: All scripts successfully loaded.");
		HTMLArea._appendToLog("[HTMLArea::init]: Editor url set to: " + _editor_url);
		HTMLArea._appendToLog("[HTMLArea::init]: Editor skin CSS set to: " + _editor_CSS);
		HTMLArea._appendToLog("[HTMLArea::init]: Editor content skin CSS set to: " + _editor_edited_content_CSS);
		if (window.ActiveXObject) {
			for (var i = HTMLArea._scripts.length; --i >= 0;) {
				HTMLArea._request[i].onreadystatechange = new Function();
				HTMLArea._request[i] = null;
			}
		}
	} else {
		HTMLArea.loadTimer = window.setTimeout("HTMLArea.checkInitialLoad();", 200);
		return false;
	}
};

/*
 * Get all the scripts
 */
HTMLArea.init = function() {
	HTMLArea._eventCache = HTMLArea._eventCacheConstructor();
	if (window.XMLHttpRequest || window.ActiveXObject) {
		try { 
			var success = true;
			for (var i = HTMLArea._scripts.length; --i >= 0 && success;) success = success && HTMLArea._getScript(i);
		} catch (e) {
			HTMLArea._appendToLog("ERROR [HTMLArea::init]: Unable to use XMLHttpRequest: "+ e);
		}
		if (success) {
			HTMLArea.checkInitialLoad();
		} else {
			if (HTMLArea.is_ie) window.setTimeout('if (window.document.getElementById("pleasewait1")) { window.document.getElementById("pleasewait1").innerHTML = HTMLArea.I18N.msg["ActiveX-required"]; } else { alert(HTMLArea.I18N.msg["ActiveX-required"]); };', 200);
		}
	} else {
		if (HTMLArea.is_ie) alert(HTMLArea.I18N.msg["ActiveX-required"]);
	}
};

/*
 * Compile some regular expressions
 */
HTMLArea.RE_tagName = /(<\/|<)\s*([^ \t\n>]+)/ig;
HTMLArea.RE_doctype = /(<!doctype((.|\n)*?)>)\n?/i;
HTMLArea.RE_head    = /<head>((.|\n)*?)<\/head>/i;
HTMLArea.RE_body    = /<body>((.|\n)*?)<\/body>/i;
HTMLArea.Reg_body = new RegExp("<\/?(body)[^>]*>", "gi");
HTMLArea.Reg_entities = new RegExp("&amp;#([0-9]+);", "gi");
HTMLArea.reservedClassNames = /htmlarea/;
HTMLArea.RE_email    = /([0-9a-z]+([a-z0-9_-]*[0-9a-z])*){1}(\.[0-9a-z]+([a-z0-9_-]*[0-9a-z])*)*@([0-9a-z]+([a-z0-9_-]*[0-9a-z])*\.)+[a-z]{2,9}/i;
HTMLArea.RE_url      = /(https?:\/\/)?(([a-z0-9_]+:[a-z0-9_]+@)?[a-z0-9_-]{2,}(\.[a-z0-9_-]{2,})+\.[a-z]{2,5}(:[0-9]+)?(\/\S+)*)/i;

/*
 * Editor configuration object constructor
 */

HTMLArea.Config = function () {
	this.width = "auto";
	this.height = "auto";
		// enable creation of a status bar?
	this.statusBar = true;
		// maximum size of the undo queue
	this.undoSteps = 20;
		// the time interval at which undo samples are taken: 1/2 sec.
	this.undoTimeout = 500;
		// whether the toolbar should be included in the size or not.
	this.sizeIncludesToolbar = true;
		// if true then HTMLArea will retrieve the full HTML, starting with the <HTML> tag.
	this.fullPage = false;
		// if the site is secure, create a secure iframe
	this.useHTTPS = false;
		// for Mozilla
	this.useCSS = false;
	this.enableMozillaExtension = true;
	this.disableEnterParagraphs = false;
	this.removeTrailingBR = false;
		// style included in the iframe document
	this.editedContentStyle = _editor_edited_content_CSS;
		// content style
	this.pageStyle = "";
		// set to true if you want Word code to be cleaned upon Paste
	this.enableWordClean = true;
		// enable the 'Target' field in the Make Link dialog
	this.makeLinkShowsTarget = true;
		// remove tags (these have to be a regexp, or null if this functionality is not desired)
	this.htmlRemoveTags = null;
		// remove tags and any contents (these have to be a regexp, or null if this functionality is not desired)
	this.htmlRemoveTagsAndContents = null;
		// remove comments
	this.htmlRemoveComments = false;
		// custom tags (these have to be a regexp, or null if this functionality is not desired)
	this.customTags = null;
		// BaseURL included in the iframe document
	this.baseURL = document.baseURI || document.URL;
	if(this.baseURL && this.baseURL.match(/(.*)\/([^\/]+)/)) this.baseURL = RegExp.$1 + "/";
		// URL-s
	this.imgURL = "images/";
	this.popupURL = "popups/";

	this.btnList = {
		InsertOrderedList:	["Ordered List", "ed_list_num.gif", false, function(editor) {editor.execCommand("InsertOrderedList");}],
		InsertUnorderedList:	["Bulleted List", "ed_list_bullet", false, function(editor) {editor.execCommand("InsertUnorderedList");}],
		ForeColor:		["Font Color", "ed_color_fg.gif",false, function(editor) {editor.execCommand("ForeColor");}],
		HiliteColor:		["Background Color", "ed_color_bg.gif",false, function(editor) {editor.execCommand("HiliteColor");}],
		InsertHorizontalRule:	["Horizontal Rule", "ed_hr.gif",false, function(editor) {editor.execCommand("InsertHorizontalRule");}],
		CreateLink:		["Insert Web Link", "ed_link.gif", false, function(editor) {editor.execCommand("CreateLink", true);}, "a", false, true],
		InsertImage:		["Insert/Modify Image", "ed_image.gif", false, function(editor) {editor.execCommand("InsertImage");}],
		InsertTable:		["Insert Table", "insert_table.gif", false, function(editor) {editor.execCommand("InsertTable");}],
		HtmlMode:		["Toggle HTML Source", "ed_html.gif", true, function(editor) {editor.execCommand("HtmlMode");}],
		SelectAll:		["SelectAll", "", true, function(editor) {editor.execCommand("SelectAll");}, null, true, false],
		SplitBlock:		["Toggle Container Block", "ed_splitblock.gif", false, function(editor) {editor.execCommand("SplitBlock");}],
		About:			["About this editor", "ed_about.gif", true, function(editor) {editor.execCommand("About");}],
		Undo:			["Undo the last action", "ed_undo.gif", false, function(editor) {editor.execCommand("Undo");}],
		Redo:			["Redo the last action", "ed_redo.gif", false, function(editor) {editor.execCommand("Redo");}],
		Cut:			["Cut selection", "ed_cut.gif", false, function(editor) {editor.execCommand("Cut");}],
		Copy:			["Copy selection", "ed_copy.gif", false, function(editor) {editor.execCommand("Copy");}],
		Paste:			["Paste from clipboard", "ed_paste.gif", false, function(editor) {editor.execCommand("Paste");}],
		SelectAll:		["SelectAll", "", true, function(editor) {editor.execCommand("SelectAll");}, null, true, false],
		LeftToRight:		["Direction left to right", "ed_left_to_right.gif", false, function(editor) {editor.execCommand("LeftToRight");}],
		RightToLeft:		["Direction right to left", "ed_right_to_left.gif", false, function(editor) {editor.execCommand("RightToLeft");}]
	};
		// Default hotkeys
	this.hotKeyList = {
		a:	{ cmd:	"SelectAll", 		action:	null},
		v:	{ cmd:	"Paste", 		action:	null},
		0:	{ cmd:	"CleanWord", 		action:	null},
		z:	{ cmd:	"Undo", 		action:	null},
		y:	{ cmd:	"Redo", 		action:	null}
	};

		// Initialize tooltips from the I18N module, generate correct image path
	for (var buttonId in this.btnList) {
		if (this.btnList.hasOwnProperty(buttonId)) {
			var btn = this.btnList[buttonId];
			if (typeof(HTMLArea.I18N.tooltips[buttonId.toLowerCase()]) !== "undefined") {
				btn[0] = HTMLArea.I18N.tooltips[buttonId.toLowerCase()];
			}
			if (typeof(btn[1]) === "string") {
				btn[1] = _editor_skin + this.imgURL + btn[1];
			} else {
				btn[1][0] = _editor_skin + this.imgURL + btn[1][0];
			}
		}
	}
	this.customSelects = {};
};

/*
 * Register a new button with the configuration.
 * It can be called with all arguments, or with only one (first one).  When called with
 * only one argument it must be an object with the following properties:
 * id, tooltip, image, textMode, action, context.  Examples:
 *
 * 1. config.registerButton("my-hilite", "Hilite text", "my-hilite.gif", false, function(editor) {...}, context);
 * 2. config.registerButton({
 *	id		: "my-hilite",		// Unique id for the button
 *	tooltip		: "Hilite text",	// the tooltip
 *	image		: "my-hilite.gif",	// image to be displayed in the toolbar
 *	textMode	: false,		// disabled in text mode
 *	action		: function(editor) {	// called when the button is clicked
 *				editor.surroundHTML('<span class="hilite">', '</span>');
 *				},
 *	context		: "p"			// will be disabled if not inside a <p> element
 *	hide		: false			// hide in menu and show only in context menu
 *	selection	: false			// will be disabled if there is no selection
 *	dialog		: true			// the button opens a dialog
 *    });
 */
HTMLArea.Config.prototype.registerButton = function(id,tooltip,image,textMode,action,context,hide,selection, dialog) {
	var buttonId;
	switch (typeof(id)) {
		case "string": buttonId = id; break;
		case "object": buttonId = id.id; break;
		default: HTMLArea._appendToLog("[HTMLArea.Config::registerButton]: invalid arguments");
			 return false;
	}
	if (typeof(this.customSelects[buttonId]) !== "undefined") {
		HTMLArea._appendToLog("[HTMLArea.Config::registerButton]: A dropdown with the same Id: " + buttonId + " already exists.");
		return false;
	}
	if (typeof(this.btnList[buttonId]) !== "undefined") {
		HTMLArea._appendToLog("[HTMLArea.Config::registerButton]: A button with the same Id: " + buttonId + " already exists.");
		return false;
	}
	switch (typeof(id)) {
		case "string":
			if (typeof(hide) === "undefined") var hide = false;
			if (typeof(selection) === "undefined") var selection = false;
			if (typeof(dialog) === "undefined") var dialog = true;
			this.btnList[id] = [tooltip, image, textMode, action, context, hide, selection, dialog];
			break;
		case "object":
			if (typeof(id.hide) === "undefined") id.hide = false;
			if (typeof(id.selection) === "undefined") id.selection = false;
			if (typeof(id.dialog) === "undefined") id.dialog = true;
			this.btnList[id.id] = [id.tooltip, id.image, id.textMode, id.action, id.context, id.hide, id.selection, id.dialog];
			break;
	}
	return true;
};

/*
 * Register a dropdown box with the editor configuration.
 */
HTMLArea.Config.prototype.registerDropdown = function(dropDownConfiguration) {
	if (typeof(this.customSelects[dropDownConfiguration.id]) != "undefined") {
		HTMLArea._appendToLog("ERROR [HTMLArea.Config::registerDropdown]: A dropdown with the same ID " + dropDownConfiguration.id + " already exists.");
		return false;
	}
	if (typeof(this.btnList[dropDownConfiguration.id]) != "undefined") {
		HTMLArea._appendToLog("ERROR [HTMLArea.Config::registerDropdown]: A button with the same ID " + dropDownConfiguration.id + " already exists.");
		return false;
	}
	this.customSelects[dropDownConfiguration.id] = dropDownConfiguration;
	return true;
};

/*
 * Register a hotkey with the editor configuration.
 */
HTMLArea.Config.prototype.registerHotKey = function(hotKeyConfiguration) {
	if (typeof(this.hotKeyList[hotKeyConfiguration.id]) != "undefined") {
		HTMLArea._appendToLog("ERROR [HTMLArea.Config::registerHotKey]: A hotkey with the same key " + hotKeyConfiguration.id + " already exists.");
		return false;
	}
	this.hotKeyList[hotKeyConfiguration.id] = hotKeyConfiguration;
	return true;
};

/***************************************************
 *  EDITOR FRAMEWORK
 ***************************************************/
/*
 * Update the state of a toolbar element.
 * This function is member of a toolbar element object, unnamed object created by createButton or createSelect functions.
 */
HTMLArea.setButtonStatus = function(id,newval) {
	var oldval = this[id];
	var el = document.getElementById(this.elementId);
	if (oldval != newval) {
		switch (id) {
			case "enabled":
				if (newval) {
					if (!HTMLArea.is_wamcom) {
						HTMLArea._removeClass(el, "buttonDisabled");
						HTMLArea._removeClass(el.parentNode, "buttonDisabled");
					}
					el.disabled = false;
				} else {
					if (!HTMLArea.is_wamcom) {
						HTMLArea._addClass(el, "buttonDisabled");
						HTMLArea._addClass(el.parentNode, "buttonDisabled");
					}
					el.disabled = true;
				}
				break;
			    case "active":
				if (newval) { 
					HTMLArea._addClass(el, "buttonPressed");
					HTMLArea._addClass(el.parentNode, "buttonPressed");
				} else {
					HTMLArea._removeClass(el, "buttonPressed");
					HTMLArea._removeClass(el.parentNode, "buttonPressed");
				}
				break;
		}
		this[id] = newval;
	}
};

/*
 * Create a new line in the toolbar
 */
HTMLArea.newLine = function(toolbar) {
	tb_line = document.createElement("ul");
	tb_line.className = "tb-line";
	toolbar.appendChild(tb_line);
	return tb_line;
};

/*
 * Add a toolbar element to the current line or group
 */
HTMLArea.addTbElement = function(element, tb_line, first_cell_on_line) {
	var tb_cell = document.createElement("li");
	if (first_cell_on_line) tb_cell.className = "tb-first-cell";
		else tb_cell.className = "tb-cell";
	HTMLArea._addClass(tb_cell, element.className);
	tb_line.appendChild(tb_cell);
	tb_cell.appendChild(element);
	if(element.style.display == "none") {
		tb_cell.style.display = "none";
		if(HTMLArea._hasClass(tb_line, "tb-group")) tb_line.style.display = "none";
		if(HTMLArea._hasClass(tb_cell.previousSibling, "separator")) tb_cell.previousSibling.style.display = "none";
	}
	return false;
};

/*
 * Create a new group on the current line
 */
HTMLArea.addTbGroup = function(tb_line, first_cell_on_line) {
	var tb_group = document.createElement("ul");
	tb_group.className = "tb-group";
	HTMLArea.addTbElement(tb_group, tb_line, first_cell_on_line);
	return tb_group;
};

/*
 * Create a combo box and add it to the toolbar
 */
HTMLArea.prototype.createSelect = function(txt,tb_line,first_cell_on_line,labelObj) {
	var options = null,
		cmd = null,
		context = null,
		tooltip = "",
		newObj = {
			created : false,
			el : null,
			first : first_cell_on_line,
			labelUsed : false
		};

	switch (txt) {
		case "FontSize":
		case "FontName":
			options = this.config[txt.toLowerCase()];
			tooltip = HTMLArea.I18N.tooltips[txt.toLowerCase()];
			cmd = txt;
			break;
		default:
			cmd = txt;
			var dropdown = this.config.customSelects[cmd];
			if (typeof(dropdown) != "undefined") {
				options = dropdown.options;
				context = dropdown.context;
				if (typeof(dropdown.tooltip) != "undefined") tooltip = dropdown.tooltip;
			}
			break;
	}
	if(options) {
		newObj["el"] = document.createElement("select");
		newObj["el"].className = "select";
		newObj["el"].title = tooltip;
		newObj["el"].id = this._editorNumber + "-" + txt;
		newObj["first"] = HTMLArea.addTbElement(newObj["el"], tb_line, first_cell_on_line);
		var obj = {
			name 		: txt,				// field name
			elementId 	: newObj["el"].id,		// unique id for the UI element
			enabled 	: true,				// is it enabled?
			text 		: false,			// enabled in text mode?
			cmd 		: cmd,				// command ID
			state		: HTMLArea.setButtonStatus,	// for changing state
			context 	: context,
			editorNumber	: this._editorNumber
		};
		this._toolbarObjects[txt] = obj;
		newObj["el"]._obj = obj;
		if (labelObj["labelRef"]) {
			labelObj["el"].htmlFor = newObj["el"].id;
			newObj["labelUsed"] = true;
		}
		HTMLArea._addEvent(newObj["el"], "change", HTMLArea.toolBarButtonHandler);
		
		for (var i in options) {
			if (options.hasOwnProperty(i)) {
				var op = document.createElement("option");
				op.innerHTML = i;
				op.value = options[i];
				if (txt == "FontName" && !this.config.disablePCexamples) {
					if (HTMLArea.is_gecko) op.setAttribute("style", "font-family:" + op.value + ";");
						else op.style.cssText = "font-family:" + op.value + ";";
				}
				newObj["el"].appendChild(op);
			}
		}
		
		newObj["created"] = true;
	}

	return newObj;
};

/*
 * Create a button and add it to the toolbar
 */
HTMLArea.prototype.createButton = function (txt,tb_line,first_cell_on_line,labelObj) {
	var btn = null,
		newObj = {
			created : false,
			el : null,
			first : first_cell_on_line,
			labelUsed : false
		};

	switch (txt) {
		case "separator":
			newObj["el"] = document.createElement("div");
			newObj["el"].className = "separator";
			newObj["first"] = HTMLArea.addTbElement(newObj["el"], tb_line, first_cell_on_line);
			newObj["created"] = true;
			break;
		case "space":
			newObj["el"] = document.createElement("div");
			newObj["el"].className = "space";
			newObj["el"].innerHTML = "&nbsp;";
			newObj["first"] = HTMLArea.addTbElement(newObj["el"], tb_line, first_cell_on_line);
			newObj["created"] = true;
			break;
		case "TextIndicator":
			newObj["el"] = document.createElement("div");
			newObj["el"].appendChild(document.createTextNode("A"));
			newObj["el"].className = "indicator";
			newObj["el"].title = HTMLArea.I18N.tooltips.textindicator;
			newObj["el"].id = this._editorNumber + "-" + txt;
			newObj["first"] = HTMLArea.addTbElement(newObj["el"], tb_line, first_cell_on_line);
			var obj = {
				name		: txt,
				elementId	: newObj["el"].id,
				enabled		: true,
				active		: false,
				text		: false,
				cmd		: "TextIndicator",
				state		: HTMLArea.setButtonStatus
			};
			this._toolbarObjects[txt] = obj;
			newObj["created"] = true;
			break;
		default:
			btn = this.config.btnList[txt];
	}
	if(!newObj["created"] && btn) {
		newObj["el"] = document.createElement("button");
		newObj["el"].title = btn[0];
		newObj["el"].className = "button";
		newObj["el"].id = this._editorNumber + "-" + txt;
		if (btn[5]) newObj["el"].style.display = "none";
		newObj["first"] = HTMLArea.addTbElement(newObj["el"], tb_line, first_cell_on_line);
		var obj = {
			name 		: txt, 				// the button name
			elementId	: newObj["el"].id, 		// unique id for the UI element
			enabled 	: true,				// is it enabled?
			active		: false,			// is it pressed?
			text 		: btn[2],			// enabled in text mode?
			cmd 		: btn[3],			// the function to be invoked
			state		: HTMLArea.setButtonStatus,	// for changing state
			context 	: btn[4] || null,		// enabled in a certain context?
			selection	: btn[6],			// disabled when no selection?
			editorNumber	: this._editorNumber
		};
		this._toolbarObjects[txt] = obj;
		newObj["el"]._obj = obj;
		if (labelObj["labelRef"]) {
			labelObj["el"].htmlFor = newObj["el"].id;
			newObj["labelUsed"] = true;
		}
		HTMLArea._addEvents(newObj["el"],["mouseover", "mouseout", "mousedown", "click"], HTMLArea.toolBarButtonHandler);
		
		newObj["el"].className += " " + txt;
		if (this.plugins["TYPO3Browsers"] && (txt == "CreateLink" || txt == "InsertImage")) newObj["el"].className += "-TYPO3Browsers";
		
		newObj["created"] = true;
	}
	return newObj;
};

/*
 * Create a label and add it to the toolbar
 */
HTMLArea.createLabel = function(txt,tb_line,first_cell_on_line) {
	var newObj = {
		created : false,
		el : null,
		labelRef : false,
		first : first_cell_on_line
	};
	if (/^([IT])\[(.*?)\]/.test(txt)) {
		var l7ed = RegExp.$1 == "I"; // localized?
		var label = RegExp.$2;
		if (l7ed) label = HTMLArea.I18N.dialogs[label];
		newObj["el"] = document.createElement("label");
		newObj["el"].className = "label";
		newObj["el"].innerHTML = label;
		newObj["labelRef"] = true;
		newObj["created"] = true;
		newObj["first"] = HTMLArea.addTbElement(newObj["el"], tb_line, first_cell_on_line);
	}
	return newObj;
};

/*
 * Create the toolbar and append it to the _htmlarea.
 */
HTMLArea.prototype._createToolbar = function () {
	var j, k, code, n = this.config.toolbar.length, m,
		tb_line = null, tb_group = null,
		first_cell_on_line = true,
		labelObj = new Object(),
		tbObj = new Object();

	var toolbar = document.createElement("div");
	this._toolbar = toolbar;
	toolbar.className = "toolbar";
	toolbar.unselectable = "1";
	this._toolbarObjects = new Object();

	for (j = 0; j < n; ++j) {
		tb_line = HTMLArea.newLine(toolbar);
		if(!this.config.keepButtonGroupTogether) HTMLArea._addClass(tb_line, "free-float");
		first_cell_on_line = true;
		tb_group = null;
		var group = this.config.toolbar[j];
		m = group.length;
		for (k = 0; k < m; ++k) {
			code = group[k];
			if (code == "linebreak") {
				tb_line = HTMLArea.newLine(toolbar);
				if(!this.config.keepButtonGroupTogether) HTMLArea._addClass(tb_line, "free-float");
				first_cell_on_line = true;
				tb_group = null;
			} else {
				if ((code == "separator" || first_cell_on_line) && this.config.keepButtonGroupTogether) {
					tb_group = HTMLArea.addTbGroup(tb_line, first_cell_on_line);
					first_cell_on_line = false;
				}
				created = false;
				if (/^([IT])\[(.*?)\]/.test(code)) {
					labelObj = HTMLArea.createLabel(code, (tb_group?tb_group:tb_line), first_cell_on_line);
					created = labelObj["created"] ;
					first_cell_on_line = labelObj["first"];
				}
				if (!created) {
					tbObj = this.createButton(code, (tb_group?tb_group:tb_line), first_cell_on_line, labelObj);
					created = tbObj["created"];
					first_cell_on_line = tbObj["first"];
					if(tbObj["labelUsed"]) labelObj["labelRef"] = false;
				}
				if (!created) {
					tbObj = this.createSelect(code, (tb_group?tb_group:tb_line), first_cell_on_line, labelObj);
					created = tbObj["created"];
					first_cell_on_line = tbObj["first"];
					if(tbObj["labelUsed"]) labelObj["labelRef"] = false;
				}
				if (!created) HTMLArea._appendToLog("ERROR [HTMLArea::createToolbar]: Unknown toolbar item: " + code);
			}
		}
	}

	tb_line = HTMLArea.newLine(toolbar);
	this._htmlArea.appendChild(toolbar);
};

/*
 * Handle toolbar element events handler
 */
HTMLArea.toolBarButtonHandler = function(ev) {
	if(!ev) var ev = window.event;
	var target = (ev.target) ? ev.target : ev.srcElement;
	while (target.tagName.toLowerCase() == "img" || target.tagName.toLowerCase() == "div") target = target.parentNode;
	var obj = target._obj;
	var editorNumber = obj["editorNumber"];
	var editor = RTEarea[editorNumber]["editor"];
	if (obj.enabled) {
		switch (ev.type) {
			case "mouseover":
				HTMLArea._addClass(target, "buttonHover");
				HTMLArea._addClass(target.parentNode, "buttonHover");
				break;
			case "mouseout":
				HTMLArea._removeClass(target, "buttonHover");
				HTMLArea._removeClass(target.parentNode, "buttonHover");
				HTMLArea._removeClass(target, "buttonActive");
				HTMLArea._removeClass(target.parentNode, "buttonActive");
				if (obj.active) { 
					HTMLArea._addClass(target, "buttonPressed");
					HTMLArea._addClass(target.parentNode, "buttonPressed");
				}
				break;
			case "mousedown":
				HTMLArea._addClass(target, "buttonActive");
				HTMLArea._addClass(target.parentNode, "buttonActive");
				HTMLArea._removeClass(target, "buttonPressed");
				HTMLArea._removeClass(target.parentNode, "buttonPressed");
				HTMLArea._stopEvent(ev);
				break;
			case "click":
				HTMLArea._removeClass(target, "buttonActive");
				HTMLArea._removeClass(target.parentNode, "buttonActive");
				HTMLArea._removeClass(target, "buttonHover");
				HTMLArea._removeClass(target.parentNode, "buttonHover");
				obj.cmd(editor, obj.name);
				HTMLArea._stopEvent(ev);
				if (HTMLArea.is_opera) {
					editor._iframe.focus();
				}
				if (!editor.config.btnList[obj.name][7]) {
					editor.updateToolbar();
				}
				break;
			case "change":
				editor.focusEditor();
				var value = target.options[target.selectedIndex].value;
				switch (obj.name) {
					case "FontName":
					case "FontSize":
						editor.execCommand(obj.name, false, value);
						break;
					default:
						var dropdown = editor.config.customSelects[obj.name];
						if (typeof(dropdown) !== "undefined") {
							dropdown.action(editor, obj.name);
							HTMLArea._stopEvent(ev);
							if (HTMLArea.is_opera) {
								editor._iframe.focus();
							}
							editor.updateToolbar();
						} else {
							HTMLArea._appendToLog("ERROR [HTMLArea::toolBarButtonHandler]: Combo box " + obj.name + " not registered.");
						}
				}
		}
	}
};

/*
 * Create the status bar
 */
HTMLArea.prototype._createStatusBar = function() {
	var statusBar = document.createElement("div");
	this._statusBar = statusBar;
	statusBar.className = "statusBar";
	if (!this.config.statusBar) statusBar.style.display = "none";
	var statusBarTree = document.createElement("span");
	this._statusBarTree = statusBarTree;
	statusBarTree.className = "statusBarTree";
	statusBar.appendChild(statusBarTree);
	statusBarTree.appendChild(document.createTextNode(HTMLArea.I18N.msg["Path"] + ": "));
	this._htmlArea.appendChild(statusBar);
};

/*
 * Create the htmlArea iframe and replace the textarea with it.
 */
HTMLArea.prototype.generate = function () {

		// get the textarea and hide it
	var textarea = this._textArea;
	if (typeof(textarea) == "string") {
		textarea = HTMLArea.getElementById("textarea", textarea);
		this._textArea = textarea;
	}
	textarea.style.display = "none";

		// create the editor framework and insert the editor before the textarea
	var htmlarea = document.createElement("div");
	htmlarea.className = "htmlarea";
	htmlarea.style.width = textarea.style.width;
	this._htmlArea = htmlarea;
	textarea.parentNode.insertBefore(htmlarea, textarea);

	if(textarea.form) {
			// we have a form, on reset, re-initialize the HTMLArea content and update the toolbar
		var f = textarea.form;
		if (typeof(f.onreset) == "function") {
			var funcref = f.onreset;
			if (typeof(f.__msh_prevOnReset) == "undefined") f.__msh_prevOnReset = [];
			f.__msh_prevOnReset.push(funcref);
		}
		f._editorNumber = this._editorNumber;
		HTMLArea._addEvent(f, "reset", HTMLArea.resetHandler);
	}

		// create & append the toolbar
	this._createToolbar();
	HTMLArea._appendToLog("[HTMLArea::generate]: Toolbar successfully created.");

		// create and append the IFRAME
	var iframe = document.createElement("iframe");
	if (HTMLArea.is_ie || HTMLArea.is_safari || HTMLArea.is_wamcom) {
		iframe.setAttribute("src",_editor_url + "popups/blank.html");
	} else if (HTMLArea.is_opera) {
		iframe.setAttribute("src",_typo3_host_url + _editor_url + "popups/blank.html");
	} else {
		iframe.setAttribute("src","javascript:void(0);");
	}
	iframe.className = "editorIframe";
	if (!this.config.statusBar) iframe.className += " noStatusBar";
	htmlarea.appendChild(iframe);
	this._iframe = iframe;

		// create & append the status bar
	this._createStatusBar();

		// size the iframe
	this.sizeIframe(2);

	HTMLArea._appendToLog("[HTMLArea::generate]: Editor iframe successfully created.");
	this.initIframe();
	return this;
};

/*
 * Size the iframe according to user's prefs or initial textarea
 */
HTMLArea.prototype.sizeIframe = function(diff) {
	var height = (this.config.height == "auto" ? (this._textArea.style.height) : this.config.height);
	var textareaHeight = height;
		// All nested tabs and inline levels in the sorting order they were applied:
	this.nested = {};
	this.nested.all = RTEarea[this._editorNumber].tceformsNested;
	this.nested.sorted = HTMLArea.simplifyNested(this.nested.all);
		// Clone the array instead of using a reference (this.accessParentElements will change the array):
	var parentElements = (this.nested.sorted && this.nested.sorted.length ? [].concat(this.nested.sorted) : []);
		// Walk through all nested tabs and inline levels to make a correct positioning:
	var dimensions = this.accessParentElements(parentElements, 'this.getDimensions()');

	if(height.indexOf("%") == -1) {
		height = parseInt(height) - diff;		
		if (this.config.sizeIncludesToolbar) {
			this._initialToolbarOffsetHeight = dimensions.toolbar.height;
			height -= dimensions.toolbar.height;
			height -= dimensions.statusbar.height;
		}
		if (height < 0) height = 0;
		textareaHeight = (height - 4);
		if (textareaHeight < 0) textareaHeight = 0;
		height += "px";
		textareaHeight += "px";
	}
	this._iframe.style.height = height;
	this._textArea.style.height = textareaHeight;
	var textareaWidth = (this.config.width == "auto" ? this._textArea.style.width : this.config.width);
	var iframeWidth = textareaWidth;
	if(textareaWidth.indexOf("%") == -1) {
		iframeWidth = parseInt(textareaWidth) + "px";
		textareaWidth = parseInt(textareaWidth) - diff;
		if (textareaWidth < 0) textareaWidth = 0;
		textareaWidth += 'px';
	}
	this._iframe.style.width = "100%";
	if (HTMLArea.is_opera) this._iframe.style.width = iframeWidth;
	this._textArea.style.width = textareaWidth;
};

/**
 * Get the dimensions of the toolbar and statusbar.
 *
 * @return	object		An object with width/height pairs for statusbar and toolbar.
 * @author	Oliver Hader <oh@inpublica.de>
 */
HTMLArea.prototype.getDimensions = function() {
	return {
		toolbar: {width: this._toolbar.offsetWidth, height: this._toolbar.offsetHeight},
		statusbar: {width: this._statusBar.offsetWidth, height: this._statusBar.offsetHeight}
	};
};

/**
 * Access an inline relational element or tab menu and make it "accesible".
 * If a parent object has the style "display: none", offsetWidth & offsetHeight are '0'.
 *
 * @params	object		callbackFunc: A function to be called, when the embedded objects are "accessible".
 * @return	object		An object returned by the callbackFunc.
 * @author	Oliver Hader <oh@inpublica.de>
 */
HTMLArea.prototype.accessParentElements = function(parentElements, callbackFunc) {
	var result = {};

	if (parentElements.length) {
		var currentElement = parentElements.pop();
		var elementStyle = document.getElementById(currentElement).style;
		var actionRequired = (elementStyle.display == 'none' ? true : false);

		if (actionRequired) {
			var originalVisibility = elementStyle.visibility;
			var originalPosition = elementStyle.position;
			elementStyle.visibility = 'hidden';
			elementStyle.position = 'absolute';
			elementStyle.display = '';
		}

		result = this.accessParentElements(parentElements, callbackFunc);

		if (actionRequired) {
			elementStyle.display = 'none';
			elementStyle.position = originalPosition;
			elementStyle.visibility = originalVisibility;
		}

	} else {
		result = eval(callbackFunc);		

	}

	return result;
};

/**
 * Simplify the array of nested levels. Create an indexed array with the correct names of the elements.
 *
 * @param	object		nested: The array with the nested levels
 * @return	object		The simplified array
 * @author	Oliver Hader <oh@inpublica.de>
 */
HTMLArea.simplifyNested = function(nested) {
	var i, type, level, max, simplifiedNested=[];
	if (nested && nested.length) {
		if (nested[0][0]=='inline') {
			nested = inline.findContinuedNestedLevel(nested, nested[0][1]);
		}
		for (i=0, max=nested.length; i<max; i++) {
			type = nested[i][0];
			level = nested[i][1];
			if (type=='tab') {
				simplifiedNested.push(level+'-DIV');
			} else if (type=='inline') {
				simplifiedNested.push(level+'_fields');
			}
		}
	}
	return simplifiedNested;
};

/*
 * Initialize the iframe
 */
HTMLArea.initIframe = function(editorNumber) {
	var editor = RTEarea[editorNumber]["editor"];
	editor.initIframe();
};

HTMLArea.prototype.initIframe = function() {
	if (this._initIframeTimer) window.clearTimeout(this._initIframeTimer);
	if (!this._iframe || (!this._iframe.contentWindow && !this._iframe.contentDocument)) {
		this._initIframeTimer = window.setTimeout("HTMLArea.initIframe(" + this._editorNumber + ");", 50);
		return false;
	} else if (this._iframe.contentWindow && !HTMLArea.is_safari) {
		if (!this._iframe.contentWindow.document || !this._iframe.contentWindow.document.documentElement) {
			this._initIframeTimer = window.setTimeout("HTMLArea.initIframe(" + this._editorNumber + ");", 50);
			return false;
		}
	} else if (!this._iframe.contentDocument.documentElement || !this._iframe.contentDocument.body) {
		this._initIframeTimer = window.setTimeout("HTMLArea.initIframe(" + this._editorNumber + ");", 50);
		return false;
	}
	var doc = this._iframe.contentWindow ? this._iframe.contentWindow.document : this._iframe.contentDocument;
	this._doc = doc;

	if (!this.config.fullPage) {
		var head = doc.getElementsByTagName("head")[0];
		if (!head) {
			head = doc.createElement("head");
			doc.documentElement.appendChild(head);
		}
		if (this.config.baseURL && !HTMLArea.is_opera) {
			var base = doc.getElementsByTagName("base")[0];
			if (!base) {
				base = doc.createElement("base");
				base.href = this.config.baseURL;
				head.appendChild(base);
			}
			HTMLArea._appendToLog("[HTMLArea::initIframe]: Iframe baseURL set to: " + this.config.baseURL);
		}
		var link0 = doc.getElementsByTagName("link")[0];
		if (!link0) {
 			link0 = doc.createElement("link");
			link0.rel = "stylesheet";
			link0.href = this.config.editedContentStyle;
			head.appendChild(link0);
			HTMLArea._appendToLog("[HTMLArea::initIframe]: Skin CSS set to: " + this.config.editedContentStyle);
		}
		if (this.config.defaultPageStyle) {
			var link = doc.getElementsByTagName("link")[1];
			if (!link) {
 				link = doc.createElement("link");
				link.rel = "stylesheet";
				link.href = this.config.defaultPageStyle;
				head.appendChild(link);
			}
			HTMLArea._appendToLog("[HTMLArea::initIframe]: Override CSS set to: " + this.config.defaultPageStyle);
		}
		if (this.config.pageStyle) {
			var link = doc.getElementsByTagName("link")[2];
			if (!link) {
 				link = doc.createElement("link");
				link.rel = "stylesheet";
				link.href = this.config.pageStyle;
				head.appendChild(link);
			}
			HTMLArea._appendToLog("[HTMLArea::initIframe]: Content CSS set to: " + this.config.pageStyle);
		}
	} else {
		var html = this._textArea.value;
		this.setFullHTML(html);
	}
	HTMLArea._appendToLog("[HTMLArea::initIframe]: Editor iframe head successfully initialized.");

	this.stylesLoaded();
};

/*
 * Finalize editor Iframe initialization after loading the style sheets
 */
HTMLArea.stylesLoaded = function(editorNumber) {
	var editor = RTEarea[editorNumber]["editor"];
	editor.stylesLoaded();
};

HTMLArea.prototype.stylesLoaded = function() {
	var doc = this._doc;
	var docWellFormed = true;

		// check if the stylesheets have been loaded

	if (this._stylesLoadedTimer) window.clearTimeout(this._stylesLoadedTimer);
	var stylesAreLoaded = true;
	var errorText = '';
	var rules;
	for (var rule = 0; rule < doc.styleSheets.length; rule++) {
		if (HTMLArea.is_gecko) try { rules = doc.styleSheets[rule].cssRules; } catch(e) { stylesAreLoaded = false; errorText = e; }
		if (HTMLArea.is_ie) try { rules = doc.styleSheets[rule].rules; } catch(e) { stylesAreLoaded = false; errorText = e; }
		if (HTMLArea.is_ie) try { rules = doc.styleSheets[rule].imports; } catch(e) { stylesAreLoaded = false; errorText = e; }
	}
	if (!stylesAreLoaded && !HTMLArea.is_wamcom) {
		HTMLArea._appendToLog("[HTMLArea::initIframe]: Failed attempt at loading stylesheets: " + errorText + " Retrying...");
		this._stylesLoadedTimer = window.setTimeout("HTMLArea.stylesLoaded(" + this._editorNumber + ");", 100);
		return false;
	}
	HTMLArea._appendToLog("[HTMLArea::initIframe]: Stylesheets successfully loaded.");

	if (!this.config.fullPage) {
		doc.body.style.borderWidth = "0px";
		doc.body.className = "htmlarea-content-body";
		try { 
			doc.body.innerHTML = this._textArea.value;
		} catch(e) { 
			HTMLArea._appendToLog("[HTMLArea::initIframe]: The HTML document is not well-formed.");
			alert(HTMLArea.I18N.msg["HTML-document-not-well-formed"]);
			docWellFormed = false;
		}
	}
		// Start undo snapshots
	if (this._customUndo) this._timerUndo = window.setInterval("HTMLArea.undoTakeSnapshot(" + this._editorNumber + ");", this.config.undoTimeout);

		// Set contents editable
	if (docWellFormed) {
		if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera && !this._initEditMode()) {
			return false;
		}
		if (HTMLArea.is_ie || HTMLArea.is_safari) {
			doc.body.contentEditable = true;
		}
		if (HTMLArea.is_opera || HTMLArea.is_safari) {
			doc.designMode = "on";
			if (this._doc.queryCommandEnabled("insertbronreturn")) this._doc.execCommand("insertbronreturn", false, this.config.disableEnterParagraphs);
			if (this._doc.queryCommandEnabled("styleWithCSS")) this._doc.execCommand("styleWithCSS", false, this.config.useCSS);
		}
		if (HTMLArea.is_ie) doc.selection.empty();
		this._editMode = "wysiwyg";
		if (doc.body.contentEditable || doc.designMode == "on") HTMLArea._appendToLog("[HTMLArea::initIframe]: Design mode successfully set.");
	} else {
		this._editMode = "textmode";
		this.setMode("docnotwellformedmode");
		HTMLArea._appendToLog("[HTMLArea::initIframe]: Design mode could not be set.");
	}

		// set editor number in iframe and document for retrieval in event handlers
	doc._editorNo = this._editorNumber;
	if (HTMLArea.is_ie) doc.documentElement._editorNo = this._editorNumber;

		// intercept events for updating the toolbar & for keyboard handlers
	HTMLArea._addEvents((HTMLArea.is_ie ? doc.body : doc), ["keydown","keypress","mousedown","mouseup","drag"], HTMLArea._editorEvent, true);

		// add unload handler
	if (!HTMLArea.hasUnloadHandler) {
		HTMLArea.hasUnloadHandler = true;
		HTMLArea._addEvent((this._iframe.contentWindow ? this._iframe.contentWindow : this._iframe.contentDocument), "unload", HTMLArea.removeEditorEvents);
	}

		// set enableWordClean and intercept paste, dragdrop and drop events for wordClean
	if (this.config.enableWordClean) HTMLArea._addEvents((HTMLArea.is_ie ? doc.body : doc), ["paste","dragdrop","drop"], HTMLArea.wordClean, true);

	window.setTimeout("HTMLArea.generatePlugins(" + this._editorNumber + ");", 100);
};

HTMLArea.generatePlugins = function(editorNumber) {
	var editor = RTEarea[editorNumber]["editor"];
		// check if any plugins have registered generate handlers
		// check also if any plugin has a onKeyPress handler
	editor._hasPluginWithOnKeyPressHandler = false;
	for (var pluginId in editor.plugins) {
		if (editor.plugins.hasOwnProperty(pluginId)) {
			var pluginInstance = editor.plugins[pluginId].instance;
			if (typeof(pluginInstance.onGenerate) === "function") {
				pluginInstance.onGenerate();
			}
			if (typeof(pluginInstance.onGenerateOnce) === "function") {
				pluginInstance.onGenerateOnce();
				pluginInstance.onGenerateOnce = null;
			}
			if (typeof(pluginInstance.onKeyPress) === "function") {
				editor._hasPluginWithOnKeyPressHandler = true;
			}
		}
	}
	if (typeof(editor.onGenerate) === "function") {
		editor.onGenerate();
		editor.onGenerate = null;
	}
	HTMLArea._appendToLog("[HTMLArea::initIframe]: All plugins successfully generated.");
	editor.updateToolbar();
};

/*
 * When we have a form, on reset, re-initialize the HTMLArea content and update the toolbar
 */
HTMLArea.resetHandler = function(ev) {
	if(!ev) var ev = window.event;
	var form = (ev.target) ? ev.target : ev.srcElement;
	var editor = RTEarea[form._editorNumber]["editor"];
	editor.setHTML(editor._textArea.value);
	editor.updateToolbar();
	var a = form.__msh_prevOnReset;
		// call previous reset methods if they were there.
	if (typeof(a) != "undefined") {
		for (var i=a.length; --i >= 0; ) { a[i](); }
	}
};

/*
 * Clean up event handlers and object references, undo/redo snapshots, update the textarea for submission
 */
HTMLArea.removeEditorEvents = function(ev) {
	if(!ev) var ev = window.event;
	HTMLArea._stopEvent(ev);
	if (Dialog._modal) {
		Dialog._modal.close();
		Dialog._modal = null;
	}
	for (var ed = RTEarea.length; --ed > 0 ;) {
		var editor = RTEarea[ed]["editor"];
		if(editor) {
			RTEarea[ed]["editor"] = null;
				// save the HTML content into the original textarea for submit, back/forward, etc.
			editor._textArea.value = editor.getHTML();
				// release undo/redo snapshots
			window.clearInterval(editor._timerUndo);
			editor._undoQueue = null;
				// release events
			if (HTMLArea.is_ie) HTMLArea._cleanup(editor);
		}
	}
	if (HTMLArea._eventCache && !HTMLArea.is_opera) HTMLArea._eventCache.flush();
};

/*
 * Switch editor mode; parameter can be "textmode" or "wysiwyg".
 *  If no parameter was passed, toggle between modes.
 */
HTMLArea.prototype.setMode = function(mode) {
	if (typeof(mode) == "undefined") var mode = (this._editMode == "textmode") ? "wysiwyg" : "textmode";
	switch (mode) {
		case "textmode":
		case "docnotwellformedmode":
			this._textArea.value = this.getHTML();
			this._iframe.style.display = "none";
			this._textArea.style.display = "block";
			if(this.config.statusBar) {
				var statusBarTextMode = document.createElement("span");
				statusBarTextMode.className = "statusBarTextMode";
				statusBarTextMode.appendChild(document.createTextNode(HTMLArea.I18N.msg["TEXT_MODE"]));
				this._statusBar.innerHTML = '';
				this._statusBar.appendChild(statusBarTextMode);
			}
			this._editMode = "textmode";
			break;
		case "wysiwyg":
			if(HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) this._doc.designMode = "off";
			try {
				if(!this.config.fullPage) this._doc.body.innerHTML = this.getHTML();
					else this.setFullHTML(this.getHTML());
			} catch(e) {
				alert(HTMLArea.I18N.msg["HTML-document-not-well-formed"]);
				break;
			}
			this._textArea.style.display = "none";
			this._iframe.style.display = "block";
			if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) this._doc.designMode = "on";
			if(this.config.statusBar) {
				this._statusBar.innerHTML = "";
				this._statusBar.appendChild(this._statusBarTree);
			}
			this._editMode = "wysiwyg";
					//set gecko options
			if (HTMLArea.is_gecko) {
				if (this._doc.queryCommandEnabled("insertbronreturn")) this._doc.execCommand("insertbronreturn", false, this.config.disableEnterParagraphs);
				if (this._doc.queryCommandEnabled("styleWithCSS")) this._doc.execCommand("styleWithCSS", false, this.config.useCSS);
					else if (this._doc.queryCommandEnabled("useCSS")) this._doc.execCommand("useCSS", false, !this.config.useCSS);
			}
			break;
		default:
			return false;
	}
	if (mode !== "docnotwellformedmode") this.focusEditor();
	for (var pluginId in this.plugins) {
		if (this.plugins.hasOwnProperty(pluginId)) {
			var pluginInstance = this.plugins[pluginId].instance;
			if (typeof(pluginInstance.onMode) === "function") {
				pluginInstance.onMode(mode);
			}
		}
	}
};

/*
 * Get editor mode
 */
HTMLArea.prototype.getMode = function() {
	return this._editMode;
};

/*
 * Initialize iframe content when in full page mode
 */
HTMLArea.prototype.setFullHTML = function(html) {
	var save_multiline = RegExp.multiline;
	RegExp.multiline = true;
	if(html.match(HTMLArea.RE_doctype)) {
		this.setDoctype(RegExp.$1);
		html = html.replace(HTMLArea.RE_doctype, "");
	};
	RegExp.multiline = save_multiline;
	if(!HTMLArea.is_ie) {
		if(html.match(HTMLArea.RE_head)) this._doc.getElementsByTagName("head")[0].innerHTML = RegExp.$1;
		if(html.match(HTMLArea.RE_body)) this._doc.getElementsByTagName("body")[0].innerHTML = RegExp.$1;
	} else {
		var html_re = /<html>((.|\n)*?)<\/html>/i;
		html = html.replace(html_re, "$1");
		this._doc.open();
		this._doc.write(html);
		this._doc.close();
		this._doc.body.contentEditable = true;
		return true;
	};
};

/***************************************************
 *  PLUGINS, STYLESHEETS, AND IMAGE AND POPUP URL'S
 ***************************************************/

/*
 * Instantiate the specified plugin and register it with the editor
 *
 * @param	string		plugin: the name of the plugin
 *
 * @return	boolean		true if the plugin was successfully registered
 */
HTMLArea.prototype.registerPlugin = function(plugin) {
	var pluginName = plugin;
	if (typeof(plugin) === "string") {
		try {
			var plugin = eval(plugin);
		} catch(e) {
			HTMLArea._appendToLog("ERROR [HTMLArea::registerPlugin]: Cannot register invalid plugin: " + e);
			return false;
		}
	}
	if (typeof(plugin) !== "function") {
		HTMLArea._appendToLog("ERROR [HTMLArea::registerPlugin]: Cannot register undefined plugin.");
		return false;
	}
	var pluginInstance = new plugin(this, pluginName);
	if (pluginInstance) {
		var pluginInformation = plugin._pluginInfo;
		if(!pluginInformation) {
			pluginInformation = pluginInstance.getPluginInformation();
		}
		pluginInformation.instance = pluginInstance;
		this.plugins[pluginName] = pluginInformation;
		HTMLArea._appendToLog("[HTMLArea::registerPlugin]: Plugin " + pluginName + " was successfully registered.");
		return true;
	} else {
		HTMLArea._appendToLog("ERROR [HTMLArea::registerPlugin]: Can't register plugin " + pluginName + ".");
		return false;
	}
};

/*
 * Load the required plugin script and, unless not requested, the language file
 */
HTMLArea.loadPlugin = function(pluginName,noLangFile,url) {
	if (typeof(url) == "undefined") {
		var dir = _editor_url + "plugins/" + pluginName;
		var plugin = pluginName.replace(/([a-z])([A-Z])([a-z])/g, "$1" + "-" + "$2" + "$3").toLowerCase() + ".js";
		var plugin_file = dir + "/" + plugin;
		HTMLArea.loadScript(plugin_file);
		if (typeof(noLangFile) == "undefined" || !noLangFile) {
			var plugin_lang = dir + "/lang/" + _editor_lang + ".js";
			HTMLArea._scripts.push(plugin_lang);
		}
	} else {
		HTMLArea.loadScript(url);
	}
};

/*
 * Load a stylesheet file
 */
HTMLArea.loadStyle = function(style, plugin, url) {
	if (typeof(url) == "undefined") {
		var url = _editor_url || '';
		if (typeof(plugin) != "undefined") { url += "plugins/" + plugin + "/"; }
		url += style;
		if (/^\//.test(style)) { url = style; }
	}
	var head = document.getElementsByTagName("head")[0];
	var link = document.createElement("link");
	link.rel = "stylesheet";
	link.href = url;
	head.appendChild(link);
};

/*
 * Get the url of some image
 */
HTMLArea.prototype.imgURL = function(file, plugin) {
	if (typeof(plugin) == "undefined") return _editor_skin + this.config.imgURL + file;
		else return _editor_skin + this.config.imgURL + plugin + "/" + file;
};

/*
 * Get the url of some popup
 */
HTMLArea.prototype.popupURL = function(file) {
	var url = "";
	if(file.match(/^plugin:\/\/(.*?)\/(.*)/)) {
		var plugin = RegExp.$1;
		var popup = RegExp.$2;
		if(!/\.html$/.test(popup)) popup += ".html";
		url = _editor_url + "plugins/" + plugin + "/popups/" + popup;
	} else {
		url = _typo3_host_url + _editor_url + this.config.popupURL + file;
	}
	return url;
};

/***************************************************
 *  EDITOR UTILITIES
 ***************************************************/
HTMLArea.getInnerText = function(el) {
	var txt = '', i;
	if(el.firstChild) {
		for(i=el.firstChild;i;i =i.nextSibling) {
			if(i.nodeType == 3) txt += i.data;
			else if(i.nodeType == 1) txt += HTMLArea.getInnerText(i);
		}
	} else {
		if(el.nodeType == 3) txt = el.data;
	}
	return txt;
};

HTMLArea._wordClean = function(editor,html) {
	function clearClass(node) {
		var newc = node.className.replace(/(^|\s)mso.*?(\s|$)/ig,' ');
		if(newc != node.className) {
			node.className = newc;
			if(!/\S/.test(node.className)) node.removeAttribute("className");
		}
	}
	function clearStyle(node) {
		if (HTMLArea.is_ie) var style = node.style.cssText;
			else var style = node.getAttribute("style");
		if (style) {
			var declarations = style.split(/\s*;\s*/);
			for (var i = declarations.length; --i >= 0;) {
				if(/^mso|^tab-stops/i.test(declarations[i]) || /^margin\s*:\s*0..\s+0..\s+0../i.test(declarations[i])) declarations.splice(i,1);
			}
			node.setAttribute("style", declarations.join("; "));
		}
	}
	function stripTag(el) {
		if(HTMLArea.is_ie) {
			el.outerHTML = HTMLArea.htmlEncode(el.innerText);
		} else {
			var txt = document.createTextNode(HTMLArea.getInnerText(el));
			el.parentNode.insertBefore(txt,el);
			el.parentNode.removeChild(el);
		}
	}
	function checkEmpty(el) {
		if(/^(span|b|strong|i|em|font)$/i.test(el.tagName) && !el.firstChild) el.parentNode.removeChild(el);
	}
	function parseTree(root) {
		var tag = root.tagName.toLowerCase(), i, next;
		if((HTMLArea.is_ie && root.scopeName != 'HTML') || (!HTMLArea.is_ie && /:/.test(tag)) || /o:p/.test(tag)) {
			stripTag(root);
			return false;
		} else {
			clearClass(root);
			clearStyle(root);
			for (i=root.firstChild;i;i=next) {
				next = i.nextSibling;
				if(i.nodeType == 1 && parseTree(i)) { checkEmpty(i); }
			}
		}
		return true;
	}
	parseTree(html);
};

HTMLArea.wordCleanLater = function(editorNumber,doUpdateToolbar) {
	var editor = RTEarea[editorNumber]["editor"];
	HTMLArea._wordClean(editor, editor._doc.body);
	if (doUpdateToolbar) editor.updateToolbar();
};

/*
 * Handler for paste, dragdrop and drop events
 */
HTMLArea.wordClean = function(ev) {
	if(!ev) var ev = window.event;
	var target = (ev.target) ? ev.target : ev.srcElement;
	var owner = (target.ownerDocument) ? target.ownerDocument : target;
	while (HTMLArea.is_ie && owner.parentElement ) { // IE5.5 does not report any ownerDocument
		owner = owner.parentElement;
	}
		// if we dropped an image dragged from the TYPO3 Browser, let's close the browser window
	if (typeof(browserWin) != "undefined" && browserWin.close) browserWin.close();
	window.setTimeout("HTMLArea.wordCleanLater(" + owner._editorNo + ", true);", 250);
};

HTMLArea.prototype.forceRedraw = function() {
	this._doc.body.style.visibility = "hidden";
	this._doc.body.style.visibility = "visible";
};

/*
 * Focus the editor iframe document or the textarea.
 */
HTMLArea.prototype.focusEditor = function() {
	switch (this._editMode) {
		case "wysiwyg" :
			try {
				if (HTMLArea.is_safari) {
					this._iframe.focus();
				} else if (HTMLArea.is_opera) {
					this._doc.focus();
				} else {
					this._iframe.contentWindow.focus();
				}
			} catch(e) { }
			break;
		case "textmode":
			this._textArea.focus();
			break;
	}
	return this._doc;
};

HTMLArea.undoTakeSnapshot = function(editorNumber) {
	var editor = RTEarea[editorNumber]["editor"];
	if (editor._doc) editor._undoTakeSnapshot();
};

/*
 * Take a snapshot of the current contents for undo
 */
HTMLArea.prototype._undoTakeSnapshot = function() {
	var curTime = (new Date()).getTime();
	var newOne = true;
	if(this._undoPos >= this.config.undoSteps) {
			// remove the first element
		this._undoQueue.shift();
		--this._undoPos;
	}
		// New undo slot should be used if this is first undoTakeSnapshot call or if undoTimeout is elapsed
	if (this._undoPos < 0 || this._undoQueue[this._undoPos].time < curTime - this.config.undoTimeout) {
		++this._undoPos;
	} else {
		newOne = false;
	}
 		// use the fasted method (getInnerHTML);
 	var txt = this.getInnerHTML();
	if (newOne){
			// If previous slot contain same text new one should not be used
		if(this._undoPos == 0 || this._undoQueue[this._undoPos - 1].text != txt){
			this._undoQueue[this._undoPos] = { text: txt, time: curTime };
			this._undoQueue.length = this._undoPos + 1;
		} else {
			this._undoPos--;
		}
 	} else {
		if(this._undoQueue[this._undoPos].text != txt){
			this._undoQueue[this._undoPos].text = txt;
			this._undoQueue.length = this._undoPos + 1;
		}
 	}
};

HTMLArea.setUndoQueueLater = function(editorNumber,op) {
	var editor = RTEarea[editorNumber]["editor"];
	if (op == "undo") {
		editor.setHTML(editor._undoQueue[--editor._undoPos].text);
	} else if (op == "redo") {
		if(editor._undoPos < editor._undoQueue.length - 1) editor.setHTML(editor._undoQueue[++editor._undoPos].text);	
	}
};

HTMLArea.prototype.undo = function() {
	if(this._undoPos > 0){
			// Make sure we would not loose any changes
		this._undoTakeSnapshot();
		if (!HTMLArea.is_opera) this.setHTML(this._undoQueue[--this._undoPos].text);
			else window.setTimeout("HTMLArea.setUndoQueueLater(" + this._editorNumber + ", 'undo');", 10);
	}
};

HTMLArea.prototype.redo = function() {
	if(this._undoPos < this._undoQueue.length - 1) {
			// Make sure we would not loose any changes
		this._undoTakeSnapshot();
			// Previous call could make undo queue shorter
		if (!HTMLArea.is_opera) {
			if(this._undoPos < this._undoQueue.length - 1) this.setHTML(this._undoQueue[++this._undoPos].text);
		} else {
			window.setTimeout("HTMLArea.setUndoQueueLater(" + this._editorNumber + ", 'redo');", 10);
		}
	}
};

/*
 * Update the enabled/disabled/active state of the toolbar elements
 */
HTMLArea.updateToolbar = function(editorNumber) {
	var editor = RTEarea[editorNumber]["editor"];
	editor.updateToolbar();
	editor._timerToolbar = null;
};

HTMLArea.prototype.updateToolbar = function(noStatus) {
	var doc = this._doc,
		text = (this._editMode == "textmode"),
		selection = false,
		ancestors = null, cls = new Array(),
		txt, txtClass, i, inContext, match, matchAny, k, j, n, commandState;
	if(!text) {
		selection = !this._selectionEmpty(this._getSelection());
		ancestors = this.getAllAncestors();
		if(this.config.statusBar && !noStatus) {
				// Unhook previous events handlers
			if(this._statusBarTree.hasChildNodes()) {
				for (i = this._statusBarTree.firstChild; i; i = i.nextSibling) {
					if(i.nodeName.toLowerCase() == "a") {
						HTMLArea._removeEvents(i,["click", "contextmenu, mousedown"], HTMLArea.statusBarHandler);
						i.el = null;
						i.editor = null;
					}
				}
			}
			this._statusBarTree.selected = null;
			this._statusBarTree.innerHTML = '';
			this._statusBarTree.appendChild(document.createTextNode(HTMLArea.I18N.msg["Path"] + ": ")); // clear
			for (i = ancestors.length; --i >= 0;) {
				var el = ancestors[i];
				if(!el) continue;
				var a = document.createElement("a");
				a.href = "#";
				a.el = el;
				a.editor = this;
				if (!HTMLArea.is_opera) {
					HTMLArea._addEvents(a, ["click", "contextmenu"], HTMLArea.statusBarHandler);
				} else {
					HTMLArea._addEvents(a, ["mousedown", "click"], HTMLArea.statusBarHandler);
				}
				txt = el.tagName.toLowerCase();
				a.title = el.style.cssText;
				if (el.id) { txt += "#" + el.id; }
				if (el.className) {
					txtClass = "";
					cls = el.className.trim().split(" ");
					for (j = 0; j < cls.length; ++j) {
						if (!HTMLArea.reservedClassNames.test(cls[j])) {
							txtClass += "." + cls[j];
						}
					}
					txt += txtClass;
				}
				a.appendChild(document.createTextNode(txt));
				this._statusBarTree.appendChild(a);
				if (i != 0) this._statusBarTree.appendChild(document.createTextNode(String.fromCharCode(0xbb)));
			}
		}
	}
	for (var cmd in this._toolbarObjects) {
		if (this._toolbarObjects.hasOwnProperty(cmd)) {
			var btn = this._toolbarObjects[cmd];

				// Determine if the button should be enabled
			inContext = true;
			if (btn.context && !text) {
				inContext = false;
				var attrs = [];
				var contexts = [];
				if (/(.*)\[(.*?)\]/.test(btn.context)) {
					contexts = RegExp.$1.split(",");
					attrs = RegExp.$2.split(",");
				} else {
					contexts = btn.context.split(",");
				}
				for (j = contexts.length; --j >= 0;) contexts[j] = contexts[j].toLowerCase();
				matchAny = (contexts[0] == "*");
				for (k = 0; k < ancestors.length; ++k) {
					if (!ancestors[k]) continue;
					match = false;
					for (j = contexts.length; --j >= 0;) match = match || (ancestors[k].tagName.toLowerCase() == contexts[j]);
					if (matchAny || match) {
						inContext = true;
						for (j = attrs.length; --j >= 0;) {
							if (!eval("ancestors[k]." + attrs[j])) {
								inContext = false;
								break;
							}
						}
						if (inContext) break;
					}
				}
			}
			if (cmd == "CreateLink") btn.state("enabled", (!text || btn.text) && (inContext || selection));
				else btn.state("enabled", (!text || btn.text) && inContext && (selection || !btn.selection));
			
			if (typeof(cmd) == "function") { continue; };
				// look-it-up in the custom dropdown boxes
			var dropdown = this.config.customSelects[cmd];
			if ((!text || btn.text) && (typeof(dropdown) !== "undefined") && (typeof(dropdown.refresh) === "function")) {
				dropdown.refresh(this, cmd);
				continue;
			}
			switch (cmd) {
				case "FontName":
				case "FontSize":
					if(!text) try {
						var value = ("" + doc.queryCommandValue(cmd)).trim().toLowerCase().replace(/\'/g, "");
						if(!value) {
							document.getElementById(btn.elementId).selectedIndex = 0;
							break;
						}
							// We rely on the fact that the variable in config has the same name as button name in the toolbar.
						var options = this.config[cmd.toLowerCase()];
						k = 0;
						for (var j in options) {
							if (options.hasOwnProperty(j)) {
								if ((j.toLowerCase().indexOf(value) !== -1)
										|| (options[j].trim().substr(0, value.length).toLowerCase() == value)
										|| ((cmd === "FontName") && (options[j].toLowerCase().indexOf(value) !== -1))) {
									document.getElementById(btn.elementId).selectedIndex = k;
									throw "ok";
								}
								++k;
							}
						}
						document.getElementById(btn.elementId).selectedIndex = 0;
					} catch(e) {}
					break;
				case "TextIndicator":
					if(!text) {
						try {with (document.getElementById(btn.elementId).style) {
							backgroundColor = HTMLArea._makeColor(doc.queryCommandValue((HTMLArea.is_ie || HTMLArea.is_safari) ? "BackColor" : "HiliteColor"));
								// Mozilla
							if(/transparent/i.test(backgroundColor)) { backgroundColor = HTMLArea._makeColor(doc.queryCommandValue("BackColor")); }
							color = HTMLArea._makeColor(doc.queryCommandValue("ForeColor"));
							fontFamily = doc.queryCommandValue("FontName");
								// Check if queryCommandState is available
							fontWeight = "normal";
							fontStyle = "normal";
							try { fontWeight = doc.queryCommandState("Bold") ? "bold" : "normal"; } catch(ex) { fontWeight = "normal"; };
							try { fontStyle = doc.queryCommandState("Italic") ? "italic" : "normal"; } catch(ex) { fontStyle = "normal"; };
						}} catch (e) {
							// alert(e + "\n\n" + cmd);
						}
					}
					break;
				case "HtmlMode": btn.state("active", text); break;
				case "LeftToRight":
				case "RightToLeft":
					if (!text) {
						var el = this.getParentElement();
						while (el && !HTMLArea.isBlockElement(el)) { el = el.parentNode; }
						if (el) btn.state("active",(el.style.direction == ((cmd == "RightToLeft") ? "rtl" : "ltr")));
					}
					break;
				case "Paste":
					if (!text) {
						try {
							btn.state("enabled", doc.queryCommandEnabled('Paste'));
						} catch(e) {
							btn.state("enabled", false);
						}
					}
					break;
				case "InsertOrderedList":
				case "InsertUnorderedList":
					commandState = false;
					if(!text) try { commandState = doc.queryCommandState(cmd); } catch(e) { commandState = false; }
					btn.state("active",commandState);
					break;
				default: break;
			}
		}
	}
	
	if (this._customUndo) {
		this._undoTakeSnapshot();
	}
	for (var pluginId in this.plugins) {
		if (this.plugins.hasOwnProperty(pluginId)) {
			var pluginInstance = this.plugins[pluginId].instance;
			if (typeof(pluginInstance.onUpdateToolbar) === "function") {
				pluginInstance.onUpdateToolbar();
			}
		}
	}
};

/***************************************************
 *  DOM TREE MANIPULATION
 ***************************************************/

/*
 * Surround the currently selected HTML source code with the given tags.
 * Delete the selection, if any.
 */
HTMLArea.prototype.surroundHTML = function(startTag,endTag) {
	this.insertHTML(startTag + this.getSelectedHTML().replace(HTMLArea.Reg_body, "") + endTag);
};

/*
 * Change the tag name of a node.
 */
HTMLArea.prototype.convertNode = function(el,newTagName) {
	var newel = this._doc.createElement(newTagName), p = el.parentNode;
	while (el.firstChild) newel.appendChild(el.firstChild);
	p.insertBefore(newel, el);
	p.removeChild(el);
	return newel;
};

/*
 * Find a parent of an element with a specified tag
 */
HTMLArea.getElementObject = function(el,tagName) {
	var oEl = el;
	while (oEl != null && oEl.nodeName.toLowerCase() != tagName) oEl = oEl.parentNode;
	return oEl;
};

/***************************************************
 *  SELECTIONS AND RANGES
 ***************************************************/

/*
 * Return true if we have some selected content
 */
HTMLArea.prototype.hasSelectedText = function() {
	return this.getSelectedHTML() != "";
};

/*
 * Get an array with all the ancestor nodes of the selection.
 */
HTMLArea.prototype.getAllAncestors = function() {
	var p = this.getParentElement();
	var a = [];
	while (p && (p.nodeType === 1) && (p.nodeName.toLowerCase() !== "body")) {
		a.push(p);
		p = p.parentNode;
	}
	a.push(this._doc.body);
	return a;
};

/*
 * Get the block elements containing the start and the end points of the selection
 */
HTMLArea.prototype.getEndBlocks = function(selection) {
	var range = this._createRange(selection);
	if (HTMLArea.is_gecko) {
		var parentStart = range.startContainer;
		var parentEnd = range.endContainer;
	} else {
		if (selection.type !== "Control" ) {
			var rangeEnd = range.duplicate();
			range.collapse(true);
			var parentStart = range.parentElement();
			rangeEnd.collapse(false);
			var parentEnd = rangeEnd.parentElement();
		} else {
			var parentStart = range.item(0);
			var parentEnd = parentStart;
		}
	}
	while (parentStart && !HTMLArea.isBlockElement(parentStart)) {
		parentStart = parentStart.parentNode;
	}
	while (parentEnd && !HTMLArea.isBlockElement(parentEnd)) {
		parentEnd = parentEnd.parentNode;
	}
	return {	start	: parentStart,
			end	: parentEnd
	};
};

/*
 * Get the deepest ancestor of the selection that is of the specified type
 * Borrowed from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 */
HTMLArea.prototype._getFirstAncestor = function(sel,types) {
	var prnt = this._activeElement(sel);
	if (prnt == null) {
		try { 
			prnt = (HTMLArea.is_ie ? this._createRange(sel).parentElement() : this._createRange(sel).commonAncestorContainer);
		} catch(e) {
			return null;
		}
	}
	if (typeof(types) == 'string') types = [types];

	while (prnt) {
		if (prnt.nodeType == 1) {
			if (types == null) return prnt;
			for (var i = 0; i < types.length; i++) { 
				if(prnt.tagName.toLowerCase() == types[i]) return prnt;
			}
			if(prnt.tagName.toLowerCase() == 'body') break;
			if(prnt.tagName.toLowerCase() == 'table') break;
		}
		prnt = prnt.parentNode;
	}
	return null;
};

/***************************************************
 *  LINKS, IMAGES AND TABLES
 ***************************************************/

/*
 * Get the create link action function
 */
HTMLArea.createLinkDialog = function(editor,link) {
	return (function(param) {
		if (!param || typeof(param.f_href) == "undefined") return false;
		var a = link;
		if(!a) {
			try {
				editor._doc.execCommand("CreateLink",false,param.f_href);
				a = editor.getParentElement();
				var sel = editor._getSelection();
				var range = editor._createRange(sel);
				if (!HTMLArea.is_ie) {
					a = range.startContainer;
					if (!/^a$/i.test(a.tagName)) {
						a = a.nextSibling;
						if(a == null) a = range.startContainer.parentNode;
					}
				}
			} catch(e) {}
		} else {
			var href = param.f_href.trim();
			editor.selectNodeContents(a);
			if (href == "") {
				editor._doc.execCommand("Unlink", false, null);
				editor.updateToolbar();
				return false;
			}
			else {
				a.href = href;
			}
		}
		if (!(a && /^a$/i.test(a.tagName))) return false;
		if (typeof(param.f_target) != "undefined") a.target = param.f_target.trim();
		if (typeof(param.f_title) != "undefined") a.title = param.f_title.trim();
		editor.selectNodeContents(a);
		editor.updateToolbar();
		editor = null;
		link = null;
	});
};

/*
 * Process the create link request
 */
HTMLArea.prototype._createLink = function(link) {
	var outparam = null;
	this.focusEditor();
	if (typeof(link) == "undefined") {
		link = this.getParentElement();
		if(link) {
			if(/^img$/i.test(link.tagName)) link = link.parentNode;
			if(!/^a$/i.test(link.tagName)) link = null;
		}
	}
	if (!link) {
		var sel = this._getSelection();
		if (this._selectionEmpty(sel)) {
			alert("You need to select some text before creating a link");
			return;
		}
		outparam = {
			f_href : '',
			f_title : '',
			f_target : '',
			f_usetarget : this.config.makeLinkShowsTarget
		};
	} else {
		outparam = {
			f_href   : HTMLArea.is_ie ? this.stripBaseURL(link.href) : link.getAttribute("href"),
			f_title  : link.title,
			f_target : link.target,
			f_usetarget : this.config.makeLinkShowsTarget
		};
	}
	var createLinkDialogFunctRef = HTMLArea.createLinkDialog(this, link);
	this._popupDialog("link.html", createLinkDialogFunctRef, outparam, 450, 145);
};

/*
 * Get the insert image action function
 */
HTMLArea.insertImageDialog = function(editor,image) {
	return (function(param) {
		if (!param || typeof(param.f_url) == "undefined") return false;
		var img = image;
		if (!img) {
			var sel = editor._getSelection();
			var range = editor._createRange(sel);
			editor._doc.execCommand("InsertImage",false,param.f_url);
			if (HTMLArea.is_ie) {
				img = range.parentElement();
				if(img.tagName.toLowerCase() != "img") img = img.previousSibling;
			} else {
				var sel = editor._getSelection();
				var range = editor._createRange(sel);
				img = range.startContainer;
				if (HTMLArea.is_opera) img = img.parentNode;
				img = img.lastChild;
				while(img && img.nodeName.toLowerCase() != "img") img = img.previousSibling;
			}
		} else {
			img.src = param.f_url;
		}

		for (var field in param) {
			if (param.hasOwnProperty(field)) {
				var value = param[field];
				switch (field) {
					case "f_alt"    : img.alt = value; break;
					case "f_border" :
						if (parseInt(value)) {
							img.style.borderWidth = parseInt(value)+"px";
							img.style.borderStyle = "solid";
						} else {
							img.style.borderWidth = "";
							img.style.borderStyle = "none";
						}
						break;
					case "f_align"  :
						img.style.verticalAlign = value;
						break;
					case "f_vert"   :
						if (parseInt(value)) {
							img.style.marginTop = parseInt(value)+"px";
							img.style.marginBottom = parseInt(value)+"px";
						} else {
							img.style.marginTop = "";
							img.style.marginBottom = "";
						}
						break;
					case "f_horiz"  :
						if (parseInt(value)) {
							img.style.marginLeft = parseInt(value)+"px";
							img.style.marginRight = parseInt(value)+"px";
						} else {
							img.style.marginLeft = "";
							img.style.marginRight = "";
						}
						break;
					case "f_float"  :
						if (HTMLArea.is_ie) img.style.styleFloat = value;
							else img.style.cssFloat = value;
						break;
				}
			}
		}
		editor = null;
		image = null;
	});
};

/*
 * Called when the "InsertImage" button is clicked.
 * If an image is already there, it will just modify it's properties.
 */
HTMLArea.prototype._insertImage = function(image) {
	var outparam = null;
	this.focusEditor();
	if (typeof(image) == "undefined") {
		var image = this.getParentElement();
		if(image && !/^img$/i.test(image.tagName)) image = null;
	}
	if(image) outparam = {
		f_base		: this.config.baseURL,
		f_url		: image.getAttribute("src"),
		f_alt		: image.alt,
		f_border	: isNaN(parseInt(image.style.borderWidth))?"":parseInt(image.style.borderWidth),
		f_align 	: image.style.verticalAlign,
		f_vert		: isNaN(parseInt(image.style.marginTop))?"":parseInt(image.style.marginTop),
		f_horiz 	: isNaN(parseInt(image.style.marginLeft))?"":parseInt(image.style.marginLeft),
 		f_float 	: HTMLArea.is_ie ? image.style.styleFloat : image.style.cssFloat
	};
	var insertImageDialogFunctRef = HTMLArea.insertImageDialog(this, image);
	this._popupDialog("insert_image.html", insertImageDialogFunctRef, outparam, 580, 460);
};

/*
 * Get the insert table action function
 */
HTMLArea.insertTableDialog = function(editor, sel, range) {
	return (function(param) {
		if(!param) return false;
		var doc = editor._doc;
		var table = doc.createElement("table");
		for (var field in param) {
			if (param.hasOwnProperty(field)) {
				var value = param[field];
				if (value) {
					switch (field) {
						case "f_width"   : 
							if(value != "") {
								table.style.width = parseInt(value) + param["f_unit"];
								break;
							}
						case "f_align"   :
							table.style.textAlign = value;
							break;
						case "f_border"  :
							if(value != "") {
								table.style.borderWidth	 = parseInt(value)+"px";
								table.style.borderStyle = "solid";
							}
							break;
						case "f_spacing" :
							if(value != "") {
								table.cellSpacing = parseInt(value);
								break;
							}
						case "f_padding" :
							if(value != "") {
								table.cellPadding = parseInt(value);
								break;
							}
						case "f_float"   :
							if (HTMLArea.is_ie) {
								table.style.styleFloat = ((value != "not set") ? value : "");
							} else {
								table.style.cssFloat = ((value != "not set") ? value : "");
							}
							break;
					}
				}
			}
		}
		var cellwidth = 0;
		if(param.f_fixed) cellwidth = Math.floor(100 / parseInt(param.f_cols));
		var tbody = doc.createElement("tbody");
		table.appendChild(tbody);
		for (var i = param["f_rows"]; i > 0; i--) {
			var tr = doc.createElement("tr");
			tbody.appendChild(tr);
			for (var j = param["f_cols"]; j > 0; j--) {
				var td = doc.createElement("td");
				if (cellwidth) td.style.width = cellwidth + "%";
				if (HTMLArea.is_opera) { td.innerHTML = '&nbsp;'; }
				tr.appendChild(td);
			}
		}
		editor.focusEditor();
		if(HTMLArea.is_ie) range.pasteHTML(table.outerHTML);
			else editor.insertNodeAtSelection(table);
		if (editor.config.buttons["toggleborders"] && editor.config.buttons["toggleborders"]["setOnTableCreation"]) editor.plugins["TableOperations"].instance.buttonPress(editor,"TO-toggle-borders");
		if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) editor.setMode("wysiwyg");
		editor.updateToolbar();
		editor = null;
		sel = null;
		range = null;
		return true;
	});
};

/*
 * Process insert table request
 */
HTMLArea.prototype._insertTable = function() {
	var sel = this._getSelection();
	var range = this._createRange(sel);
	this.focusEditor();
	var insertTableDialogFunctRef = HTMLArea.insertTableDialog(this, sel, range);
	this._popupDialog("insert_table.html", insertTableDialogFunctRef, this, 520, 230);
};

/***************************************************
 *  Category: EVENT HANDLERS
 ***************************************************/
HTMLArea.selectColorDialog = function(editor,cmdID) {
	return (function(color) {
		if(color) editor._doc.execCommand(cmdID, false, "#" + color);
	});
};

/*
 * Intercept some commands and replace them with our own implementation
 */
HTMLArea.prototype.execCommand = function(cmdID, UI, param) {
	this.focusEditor();
	switch (cmdID) {
	    case "HtmlMode"	: this.setMode(); break;
	    case "SplitBlock"	: this._doc.execCommand('FormatBlock',false,((HTMLArea.is_ie || HTMLArea.is_safari) ? "<div>" : "div")); break;
	    case "HiliteColor"	:
		if (HTMLArea.is_ie || HTMLArea.is_safari) { cmdID = "BackColor"; }
	    case "ForeColor"	:
		var colorDialogFunctRef = HTMLArea.selectColorDialog(this, cmdID);
		this._popupDialog("select_color.html", colorDialogFunctRef, HTMLArea._colorToRgb(this._doc.queryCommandValue(cmdID)), 200, 182);
		break;
	    case "CreateLink"	: this._createLink(); break;
	    case "Undo"		:
	    case "Redo"		:
		if(this._customUndo) this[cmdID.toLowerCase()]();
			else this._doc.execCommand(cmdID,UI,param);
		break;
	    case "InsertTable"	: this._insertTable(); break;
	    case "InsertImage"	: this._insertImage(); break;
	    case "About"	: this._popupDialog("about.html", null, this, 475, 350); break;
	    case "CleanWord"	: HTMLArea._wordClean(this, this._doc.body); break;
	    case "Cut"		:
	    case "Copy"		:
	    case "Paste"	:
		try {
			this._doc.execCommand(cmdID, false, null);
			if (cmdID == "Paste" && this.config.enableWordClean) HTMLArea._wordClean(this, this._doc.body);
		} catch (e) {
			if (HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) this._mozillaPasteException(cmdID, UI, param);
		}
		break;
	    case "LeftToRight"	:
	    case "RightToLeft"	:
		var dir = (cmdID == "RightToLeft") ? "rtl" : "ltr";
		var el = this.getParentElement();
		while (el && !HTMLArea.isBlockElement(el)) el = el.parentNode;
		if(el) {
			if(el.style.direction == dir) el.style.direction = "";
				else el.style.direction = dir;
		}
		break;
	    case "FontSize"	:
	    case "FontName"	:
	    	if (param) {
			this._doc.execCommand(cmdID, UI, param);
			break;
		} else {
			var sel = this._getSelection();
				// Find font and select it
			if (HTMLArea.is_gecko && sel.isCollapsed) {
				var fontNode = this._getFirstAncestor(sel, "font");
				if (fontNode != null) this.selectNode(fontNode);
			}
				// Remove format
			this._doc.execCommand("RemoveFormat", UI, null);
				// Collapse range if font was found
			if (HTMLArea.is_gecko && fontNode != null) {
				sel = this._getSelection();
				var r = this._createRange(sel).cloneRange();
				r.collapse(false);
				this.emptySelection(sel);
				this.addRangeToSelection(sel, r);
			}
		}
		break;
	    default		:
	    	try { this._doc.execCommand(cmdID, UI, param); }
			catch(e) { if (this.config.debug) alert(e + "\n\nby execCommand(" + cmdID + ");"); }
	}
	this.updateToolbar();
	return false;
};

/*
* A generic event handler for things that happen in the IFRAME's document.
*/
HTMLArea._editorEvent = function(ev) {
	if(!ev) var ev = window.event;
	var target = (ev.target) ? ev.target : ev.srcElement;
	var owner = (target.ownerDocument) ? target.ownerDocument : target;
	if(HTMLArea.is_ie) { // IE5.5 does not report any ownerDocument
		while (owner.parentElement) { owner = owner.parentElement; }
	}
	var editor = RTEarea[owner._editorNo]["editor"];
	var keyEvent = (HTMLArea.is_ie && ev.type == "keydown") || (HTMLArea.is_gecko && ev.type == "keypress");
	editor.focusEditor();

	if(keyEvent) {
		if(editor._hasPluginWithOnKeyPressHandler) {
			for (var pluginId in editor.plugins) {
				if (editor.plugins.hasOwnProperty(pluginId)) {
					var pluginInstance = editor.plugins[pluginId].instance;
					if (typeof(pluginInstance.onKeyPress) === "function") {
						if (!pluginInstance.onKeyPress(ev)) {
							HTMLArea._stopEvent(ev);
							return false;
						}
					}
				}
			}
		}
		if(ev.ctrlKey) {
			if(!ev.altKey) {
					// execute hotkey command
				var key = String.fromCharCode((HTMLArea.is_ie || HTMLArea.is_safari || HTMLArea.is_opera) ? ev.keyCode : ev.charCode).toLowerCase();
				if (HTMLArea.is_gecko && ev.keyCode == 32) key = String.fromCharCode(ev.keyCode).toLowerCase();
				var cmd = null;
				var value = null;
				switch (key) {
					case ' ':
						editor.insertHTML("&nbsp;");
						editor.updateToolbar();
						HTMLArea._stopEvent(ev);
						return false;
						// other hotkeys
					default:
						if (editor.config.hotKeyList[key] && editor.config.hotKeyList[key].cmd) {
							switch (editor.config.hotKeyList[key].cmd) {
								case "SelectAll":
								case "CleanWord":
									cmd = editor.config.hotKeyList[key].cmd;
									break;
								case "Paste":
									if (HTMLArea.is_ie || HTMLArea.is_safari) {
										cmd = editor.config.hotKeyList[key].cmd;
									} else if (editor.config.enableWordClean) {
										window.setTimeout("HTMLArea.wordCleanLater(" + owner._editorNo + ", false);", 50);
									}
									break;
								default:
									if (editor._toolbarObjects[editor.config.hotKeyList[key].cmd]) {
										cmd = editor.config.hotKeyList[key].cmd;
									}
							}
						}
				}
				if(cmd && !(editor.config.hotKeyList[key] && editor.config.hotKeyList[key].action)) {
					editor.execCommand(cmd, false, value);
					HTMLArea._stopEvent(ev);
					return false;
				} else {
					if (editor.config.hotKeyList[key] && editor.config.hotKeyList[key].action) {
						if (!editor.config.hotKeyList[key].action(editor, key)) {
							HTMLArea._stopEvent(ev);
							return false;
						}
					}
					editor.updateToolbar();
				}
			}
		} else if (ev.altKey) {
				// check if context menu is already handling this event
			if(editor.plugins["ContextMenu"] && editor.plugins["ContextMenu"].instance) {
				var keys = editor.plugins["ContextMenu"].instance.keys;
				if (keys.length > 0) {
					var k;
					for (var i = keys.length; --i >= 0;) {
						k = keys[i];
						if (k[0].toLowerCase() == key) {
							HTMLArea._stopEvent(ev);
							return false;
						}
					}
				}
			}
		} else if (keyEvent) {
			if (HTMLArea.is_gecko) editor._detectURL(ev);
			switch (ev.keyCode) {
				case 13	: // KEY enter
					if (HTMLArea.is_gecko) { 
						if (!ev.shiftKey && !editor.config.disableEnterParagraphs) {
							if (editor._checkInsertP()) {
								HTMLArea._stopEvent(ev);
							}
						} else if (HTMLArea.is_safari) {
							var brNode = document.createElement("br");
							editor.insertNodeAtSelection(brNode);
							if (!brNode.nextSibling || !HTMLArea.getInnerText(brNode.nextSibling)) {
								var secondBrNode = document.createElement("br");
								secondBrNode = brNode.parentNode.appendChild(secondBrNode);
								editor.selectNode(secondBrNode, false);
							}
							HTMLArea._stopEvent(ev);
						}
							// update the toolbar state after some time
						if (editor._timerToolbar) window.clearTimeout(editor._timerToolbar);
						editor._timerToolbar = window.setTimeout("HTMLArea.updateToolbar(" + editor._editorNumber + ");", 100);
						return false;
					}
					break;
				case 8	: // KEY backspace
				case 46	: // KEY delete
					if ((HTMLArea.is_gecko && !ev.shiftKey) || HTMLArea.is_ie) {
						if (editor._checkBackspace()) HTMLArea._stopEvent(ev);
					}
						// update the toolbar state after some time
					if (editor._timerToolbar) window.clearTimeout(editor._timerToolbar);
					editor._timerToolbar = window.setTimeout("HTMLArea.updateToolbar(" + editor._editorNumber + ");", 50);
					break;
				case 9: // KEY horizontal tab
					var newkey = (ev.shiftKey ? "SHIFT-" : "") + "TAB";
					if (editor.config.hotKeyList[newkey] && editor.config.hotKeyList[newkey].action) {
						if (!editor.config.hotKeyList[newkey].action(editor, newkey)) {
							HTMLArea._stopEvent(ev);
							return false;
						}
					}
					break;
				case 37: // LEFT arrow key
				case 38: // UP arrow key
				case 39: // RIGHT arrow key
				case 40: // DOWN arrow key
					if (HTMLArea.is_ie) {
						if (editor._timerToolbar) window.clearTimeout(editor._timerToolbar);
						editor._timerToolbar = window.setTimeout("HTMLArea.updateToolbar(" + editor._editorNumber + ");", 10);
						break;
					}
			}
		}
	} else {
			// mouse event
		if (editor._timerToolbar) window.clearTimeout(editor._timerToolbar);
		if (ev.type == "mouseup") editor.updateToolbar();
			else editor._timerToolbar = window.setTimeout("HTMLArea.updateToolbar(" + editor._editorNumber + ");", 50);
	}
};

HTMLArea.prototype.scrollToCaret = function() {
	var e = this.getParentElement(),
		w = this._iframe.contentWindow ? this._iframe.contentWindow : window,
		h = w.innerHeight || w.height,
		d = this._doc,
		t = d.documentElement.scrollTop || d.body.scrollTop;
	if (typeof(h) == "undefined") return false;
	if(e.offsetTop > h + t) w.scrollTo(e.offsetLeft,e.offsetTop - h + e.offsetHeight);
};

/*
 * Retrieve the HTML
 */
HTMLArea.prototype.getHTML = function() {
	switch (this._editMode) {
		case "wysiwyg":
			if (!this.config.fullPage) {
				return HTMLArea.getHTML(this._doc.body, false, this);
			} else {
				return this.doctype + "\n" + HTMLArea.getHTML(this._doc.documentElement,true,this);
			}
		case "textmode":
			return this._textArea.value;
	}
	return false;
};

/*
 * Retrieve the HTML using the fastest method
 */
HTMLArea.prototype.getInnerHTML = function() {
	switch (this._editMode) {
		case "wysiwyg":
			if(!this.config.fullPage) return this._doc.body.innerHTML;
				else return this.doctype + "\n" + this._doc.documentElement.innerHTML;
		case "textmode": return this._textArea.value;
	}
	return false;
};

/*
 * Replace the HTML inside
 */
HTMLArea.prototype.setHTML = function(html) {
	switch (this._editMode) {
		case "wysiwyg":
			if(!this.config.fullPage) this._doc.body.innerHTML = html;
				else this._doc.body.innerHTML = html;
			break;
		case "textmode": this._textArea.value = html; break;
	}
	return false;
};

/*
 * Set the given doctype when config.fullPage is true
 */
HTMLArea.prototype.setDoctype = function(doctype) {
	this.doctype = doctype;
};

/***************************************************
 *  UTILITY FUNCTIONS
 ***************************************************/

// variable used to pass the object to the popup editor window.
HTMLArea._object = null;

/*
 * Check if the client agent is supported
 */
HTMLArea.checkSupportedBrowser = function() {
	if(HTMLArea.is_gecko && !HTMLArea.is_safari && !HTMLArea.is_opera) {
		if(navigator.productSub < 20030210) return false;
	}
	return HTMLArea.is_gecko || HTMLArea.is_ie;
};

/*	EventCache Version 1.0
 *	Copyright 2005 Mark Wubben
 *	Adaptation by Stanislas Rolland
 *	Provides a way for automatically removing events from nodes and thus preventing memory leakage.
 *	See <http://novemberborn.net/javascript/event-cache> for more information.
 *	This software is licensed under the CC-GNU LGPL <http://creativecommons.org/licenses/LGPL/2.1/>
 *	Event Cache uses an anonymous function to create a hidden scope chain. This is to prevent scoping issues.
 */
HTMLArea._eventCacheConstructor = function() {
	var listEvents = [];

	return ({
		listEvents : listEvents,

		add : function(node, sEventName, fHandler) {
			listEvents.push(arguments);
		},

		flush : function() {
			var item;
			for (var i = listEvents.length; --i >= 0;) {
				item = listEvents[i];
				try {
					HTMLArea._removeEvent(item[0], item[1], item[2]);
					item[0][item[1]] = null;
					item[0] = null;
					item[2] = null;
				} catch(e) { }
			}
		}
	});
};

/*
 * Register an event
 */
HTMLArea._addEvent = function(el,evname,func,useCapture) {
	if (typeof(useCapture) == "undefined") var useCapture = false;
	if (HTMLArea.is_gecko) {
		el.addEventListener(evname, func, !HTMLArea.is_opera || useCapture);
	} else {
		el.attachEvent("on" + evname, func);
	}
	HTMLArea._eventCache.add(el, evname, func);
};

/*
 * Register a list of events
 */
HTMLArea._addEvents = function(el,evs,func,useCapture) {
	if (typeof(useCapture) == "undefined") var useCapture = false;
	for (var i = evs.length; --i >= 0;) {
		HTMLArea._addEvent(el,evs[i], func, useCapture);
	}
};

/*
 * Remove an event listener
 */
HTMLArea._removeEvent = function(el,evname,func) {
	if(HTMLArea.is_gecko) {
		try { el.removeEventListener(evname, func, true); el.removeEventListener(evname, func, false); } catch(e) { }
	} else {
		try { el.detachEvent("on" + evname, func); } catch(e) { }
	}
};

/*
 * Remove a list of events
 */
HTMLArea._removeEvents = function(el,evs,func) {
	for (var i = evs.length; --i >= 0;) { HTMLArea._removeEvent(el, evs[i], func); }
};

/*
 * Stop event propagation
 */
HTMLArea._stopEvent = function(ev) {
	if(HTMLArea.is_gecko) {
		ev.stopPropagation();
		ev.preventDefault();
	} else {
		ev.cancelBubble = true;
		ev.returnValue = false;
	}
};

/*
 * Remove a class name from the class attribute
 */
HTMLArea._removeClass = function(el, removeClassName) {
	if(!(el && el.className)) return;
	var cls = el.className.trim().split(" ");
	var ar = new Array();
	for (var i = cls.length; i > 0;) {
		if (cls[--i] != removeClassName) ar[ar.length] = cls[i];
	}
	if (ar.length == 0) {
		if (!HTMLArea.is_opera) el.removeAttribute(HTMLArea.is_gecko ? "class" : "className");
			else el.className = '';

	} else el.className = ar.join(" ");
};

/*
 * Add a class name to the class attribute
 */
HTMLArea._addClass = function(el, addClassName) {
	HTMLArea._removeClass(el, addClassName);
	if (el.className && HTMLArea.classesXOR) {
		var classNames = el.className.trim().split(" ");
		for (var i = classNames.length; --i >= 0;) {
			if (HTMLArea.classesXOR[addClassName] && HTMLArea.classesXOR[addClassName].test(classNames[i])) {
				HTMLArea._removeClass(el, classNames[i]);
			}
		}
	}
	if (el.className) el.className += " " + addClassName;
		else el.className = addClassName;
};

/*
 * Check if a class name is in the class attribute
 */
HTMLArea._hasClass = function(el, className) {
	if (!el || !el.className) return false;
	var cls = el.className.split(" ");
	for (var i = cls.length; i > 0;) {
		if(cls[--i] == className) return true;
	}
	return false;
};
HTMLArea.RE_blockTags = /^(body|p|h1|h2|h3|h4|h5|h6|ul|ol|pre|dl|dt|dd|div|noscript|blockquote|form|hr|table|caption|fieldset|address|td|tr|th|li|tbody|thead|tfoot|iframe)$/;
HTMLArea.isBlockElement = function(el) { return el && el.nodeType == 1 && HTMLArea.RE_blockTags.test(el.nodeName.toLowerCase()); };
HTMLArea.RE_closingTags = /^(p|blockquote|a|li|ol|ul|dl|dt|td|th|tr|tbody|thead|tfoot|caption|colgroup|table|div|b|bdo|big|cite|code|del|dfn|em|i|ins|kbd|label|q|samp|small|span|strike|strong|sub|sup|tt|u|var|abbr|acronym|font|center|object|embed|style|script|title|head|clickenlarge)$/;
HTMLArea.RE_noClosingTag = /^(img|br|hr|col|input|area|base|link|meta|param)$/;
HTMLArea.needsClosingTag = function(el) { return el && el.nodeType == 1 && !HTMLArea.RE_noClosingTag.test(el.tagName.toLowerCase()); };

/*
 * Perform HTML encoding of some given string
 * Borrowed in part from Xinha (is not htmlArea) - http://xinha.gogo.co.nz/
 */
HTMLArea.htmlDecode = function(str) {
	str = str.replace(/&lt;/g, "<").replace(/&gt;/g, ">");
	str = str.replace(/&nbsp;/g, "\xA0"); // Decimal 160, non-breaking-space
	str = str.replace(/&quot;/g, "\x22");
	str = str.replace(/&#39;/g, "'") ;
	str = str.replace(/&amp;/g, "&");
	return str;
};
HTMLArea.htmlEncode = function(str) {
	if (typeof(str) != 'string') str = str.toString(); // we don't need regexp for that, but.. so be it for now.
		// Let's not do it twice
	//str = HTMLArea.htmlDecode(str);
	str = str.replace(/&/g, "&amp;");
	str = str.replace(/</g, "&lt;").replace(/>/g, "&gt;");
	str = str.replace(/\xA0/g, "&nbsp;"); // Decimal 160, non-breaking-space
	str = str.replace(/\x22/g, "&quot;"); // \x22 means '"'
	str = str.replace(HTMLArea.Reg_entities, "&$1;"); // keep numeric entities
	return str;
};

/*
 * Retrieve the HTML code from the given node.
 * This is a replacement for getting innerHTML, using standard DOM calls.
 * Wrapper catches a Mozilla-Exception with non well-formed html source code.
 */
HTMLArea.getHTML = function(root, outputRoot, editor){
	try {
		return HTMLArea.getHTMLWrapper(root,outputRoot,editor); 
	} catch(e) {
		HTMLArea._appendToLog("The HTML document is not well-formed.");
		if(!HTMLArea._debugMode) alert(HTMLArea.I18N.msg["HTML-document-not-well-formed"]);
			else return HTMLArea.getHTMLWrapper(root,outputRoot,editor);
		return editor._doc.body.innerHTML;
	}
};

HTMLArea.getHTMLWrapper = function(root, outputRoot, editor) {
	var html = "";
	if(!root) return html;
	switch (root.nodeType) {
	   case 1:	// ELEMENT_NODE
	   case 11:	// DOCUMENT_FRAGMENT_NODE
	   case 9:	// DOCUMENT_NODE
		var closed, i, config = editor.config;
		var root_tag = (root.nodeType == 1) ? root.tagName.toLowerCase() : '';
		if (root_tag == "br" && config.removeTrailingBR && !root.nextSibling && HTMLArea.isBlockElement(root.parentNode) && (!root.previousSibling || root.previousSibling.nodeName.toLowerCase() != "br")) {
			if (!root.previousSibling && root.parentNode && root.parentNode.nodeName.toLowerCase() == "p" && root.parentNode.className) html += "&nbsp;";
			break;
		}
		if (config.htmlRemoveTagsAndContents && config.htmlRemoveTagsAndContents.test(root_tag)) break;
		var custom_tag = (config.customTags && config.customTags.test(root_tag));
		var empty_root = (root_tag == "clickenlarge" && !(root.firstChild && root.firstChild.nodeName.toLowerCase() == "img"));
		if (outputRoot) outputRoot = !(config.htmlRemoveTags && config.htmlRemoveTags.test(root_tag)) && !empty_root;
		if ((HTMLArea.is_ie || HTMLArea.is_safari) && root_tag == "head") {
			if(outputRoot) html += "<head>";
			var save_multiline = RegExp.multiline;
			RegExp.multiline = true;
			var txt = root.innerHTML.replace(HTMLArea.RE_tagName, function(str, p1, p2) {
				return p1 + p2.toLowerCase();
			});
			RegExp.multiline = save_multiline;
			html += txt;
			if(outputRoot) html += "</head>";
			break;
		} else if (outputRoot) {
			if (HTMLArea.is_gecko && root.hasAttribute('_moz_editor_bogus_node')) break;
			closed = (!(root.hasChildNodes() || HTMLArea.needsClosingTag(root) || custom_tag));
			html = "<" + root_tag;
			var a, name, value, attrs = root.attributes;
			var n = attrs.length;
			for (i = attrs.length; --i >= 0 ;) {
				a = attrs.item(i);
				name = a.nodeName.toLowerCase();
				if ((!a.specified && name != 'value') || /_moz|contenteditable|_msh/.test(name)) continue;
				if (!HTMLArea.is_ie || name != "style") {
						// IE5.5 reports wrong values. For this reason we extract the values directly from the root node.
						// Using Gecko the values of href and src are converted to absolute links unless we get them using nodeValue()
					if (typeof(root[a.nodeName]) != "undefined" && name != "href" && name != "src" && name != "style" && !/^on/.test(name)) {
						value = root[a.nodeName];
					} else {
						value = a.nodeValue;
						if (HTMLArea.is_ie && (name == "href" || name == "src")) value = editor.stripBaseURL(value);
					}
				} else { // IE fails to put style in attributes list.
					value = root.style.cssText;
				}
					// Mozilla reports some special values; we don't need them.
				if(/(_moz|^$)/.test(value)) continue;
					// Strip value="0" reported by IE on all li tags
				if(HTMLArea.is_ie && root_tag == "li" && name == "value" && a.nodeValue == 0) continue;
				html += " " + name + '="' + HTMLArea.htmlEncode(value) + '"';
			}
			if (html != "") html += closed ? " />" : ">";
		}
		for (i = root.firstChild; i; i = i.nextSibling) {
			if (/^li$/i.test(i.tagName) && !/^[ou]l$/i.test(root.tagName)) html += "<ul>" + HTMLArea.getHTMLWrapper(i, true, editor) + "</ul>";
				 else html += HTMLArea.getHTMLWrapper(i, true, editor);
		}
		if (outputRoot && !closed) html += "</" + root_tag + ">";
		break;
	    case 3:	// TEXT_NODE
		html = /^(script|style)$/i.test(root.parentNode.tagName) ? root.data : HTMLArea.htmlEncode(root.data);
		break;
	    case 8:	// COMMENT_NODE
		if (!editor.config.htmlRemoveComments) html = "<!--" + root.data + "-->";
		break;
	    case 4:	// Node.CDATA_SECTION_NODE
			// Mozilla seems to convert CDATA into a comment when going into wysiwyg mode, don't know about IE
		html += '<![CDATA[' + root.data + ']]>';
		break;
	    case 5:	// Node.ENTITY_REFERENCE_NODE
		html += '&' + root.nodeValue + ';';
		break;
	    case 7:	// Node.PROCESSING_INSTRUCTION_NODE
			// PI's don't seem to survive going into the wysiwyg mode, (at least in moz) so this is purely academic
		html += '<?' + root.target + ' ' + root.data + ' ?>';
		break;
	    default:
	    	break;
	}
	return html;
};

HTMLArea.getPrevNode = function(node) {
	if(!node)                return null;
	if(node.previousSibling) return node.previousSibling;
	if(node.parentNode)      return node.parentNode;
	return null;
};

HTMLArea.getNextNode = function(node) {
	if(!node)            return null;
	if(node.nextSibling) return node.nextSibling;
	if(node.parentNode)  return node.parentNode;
	return null;
};

HTMLArea.removeFromParent = function(el) {
	if(!el.parentNode) return;
	var pN = el.parentNode;
	pN.removeChild(el);
	return el;
};

HTMLArea.prototype.stripBaseURL = function(string) {
	var baseurl = this.config.baseURL;

	// strip to last directory in case baseurl points to a file
	baseurl = baseurl.replace(/[^\/]+$/, '');
	var basere = new RegExp(baseurl);
	string = string.replace(basere, "");

	// strip host-part of URL which is added by MSIE to links relative to server root
	baseurl = baseurl.replace(/^(https?:\/\/[^\/]+)(.*)$/, '$1');
	basere = new RegExp(baseurl);
	return string.replace(basere, "");
};

String.prototype.trim = function() {
	return this.replace(/^\s+/, '').replace(/\s+$/, '');
};

// creates a rgb-style color from a number
HTMLArea._makeColor = function(v) {
	if (typeof(v) != "number") {
		// already in rgb (hopefully); IE doesn't get here.
		return v;
	}
	// IE sends number; convert to rgb.
	var r = v & 0xFF;
	var g = (v >> 8) & 0xFF;
	var b = (v >> 16) & 0xFF;
	return "rgb(" + r + "," + g + "," + b + ")";
};

// returns hexadecimal color representation from a number or a rgb-style color.
HTMLArea._colorToRgb = function(v) {
	if (!v)
		return '';

	// returns the hex representation of one byte (2 digits)
	function hex(d) {
		return (d < 16) ? ("0" + d.toString(16)) : d.toString(16);
	};

	if (typeof(v) == "number") {
		// we're talking to IE here
		var r = v & 0xFF;
		var g = (v >> 8) & 0xFF;
		var b = (v >> 16) & 0xFF;
		return "#" + hex(r) + hex(g) + hex(b);
	}

	if (v.substr(0, 3) == "rgb") {
		// in rgb(...) form -- Mozilla
		var re = /rgb\s*\(\s*([0-9]+)\s*,\s*([0-9]+)\s*,\s*([0-9]+)\s*\)/;
		if (v.match(re)) {
			var r = parseInt(RegExp.$1);
			var g = parseInt(RegExp.$2);
			var b = parseInt(RegExp.$3);
			return "#" + hex(r) + hex(g) + hex(b);
		}
		// doesn't match RE?!  maybe uses percentages or float numbers
		// -- FIXME: not yet implemented.
		return null;
	}

	if (v.substr(0, 1) == "#") {
		// already hex rgb (hopefully :D )
		return v;
	}

	// if everything else fails ;)
	return null;
};

/** Use XML HTTPRequest to post some data back to the server and do something
 * with the response (asyncronously!), this is used by such things as the spellchecker update personal dict function
 */
HTMLArea._postback = function(url, data, handler, addParams, charset) {
	if (typeof(charset) == "undefined") var charset = "utf-8";
	var req = null;
	if (window.XMLHttpRequest) req = new XMLHttpRequest();
		else if (window.ActiveXObject) {
			var success = false;
			for (var k = 0; k < HTMLArea.MSXML_XMLHTTP_PROGIDS.length && !success; k++) {
				try {
					req = new ActiveXObject(HTMLArea.MSXML_XMLHTTP_PROGIDS[k]);
					success = true;
				} catch (e) { }
			}
		}

	if(req) {
		var content = '';
		for (var i in data) {
			content += (content.length ? '&' : '') + i + '=' + encodeURIComponent(data[i]);
		}
		content += (content.length ? '&' : '') + 'charset=' + charset;
		if (typeof(addParams) != "undefined") content += addParams;
		if (url.substring(0,1) == '/') {
			var postUrl = _typo3_host_url + url; 
		} else {
			var postUrl = _typo3_host_url + _editor_url + url;
		}

		function callBack() {
			if(req.readyState == 4) {
				if (req.status == 200) {
					if (typeof(handler) == 'function') handler(req.responseText, req);
					HTMLArea._appendToLog("[HTMLArea::_postback]: Server response: " + req.responseText);
				} else {
					HTMLArea._appendToLog("ERROR [HTMLArea::_postback]: Unable to post " + postUrl + " . Server reported " + req.statusText);
				}
			}
		}
		req.onreadystatechange = callBack;
		function sendRequest() {
			HTMLArea._appendToLog("[HTMLArea::_postback]: Request: " + content);
			req.send(content);
		}

		req.open('POST', postUrl, true);
		req.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
		window.setTimeout(sendRequest, 500);
	}
};

/***************************************************
 *  MODAL DIALOG
 ***************************************************/
/*
 * Modal dialog pseudo-object
 */
Dialog = function(url, action, init, width, height, opener, editor, scrollbars) {
	Dialog._open(url, action, init, (width?width:100), (height?height:100), opener, editor, scrollbars);
};

/*
 * Open modal popup window
 */
Dialog._open = function(url, action, init, width, height, _opener, editor, scrollbars) {

	if (typeof(Dialog._modal) == "object" && typeof(Dialog._modal.close) == "function") {
		Dialog._modal.close();
		Dialog._modal = null;
	}

	var dlg = window.open(url, 'hadialog', "toolbar=no,location=no,directories=no,menubar=no,width=" + width + ",height=" + height + ",scrollbars=" + scrollbars + ",resizable=yes,modal=yes,dependent=yes,top=100,left=100");
	var obj = new Object();
	obj.dialogWindow = dlg;
	Dialog._dialog = obj;
	Dialog._modal = dlg;
	Dialog._arguments = null;
	if (typeof(init) != "undefined") { Dialog._arguments = init; }

				// Capture focus events
	function capwin(w) {
		if (HTMLArea.is_gecko) { w.addEventListener("focus", function(ev) { Dialog._parentEvent(ev); }, false); }
			else { HTMLArea._addEvent(w, "focus", function(ev) { Dialog._parentEvent(ev); }); }
		for (var i=0;i < w.frames.length;i++) { capwin(w.frames[i]); }
	}
	capwin(window);

		// Close dialog window
	function closeDialog() {
		if (Dialog._dialog && Dialog._dialog.dialogWindow) {
			Dialog._dialog.dialogWindow.close();
			Dialog._dialog = null;
		}
		if (dlg && !dlg.closed) {
			dlg.close();
			dlg = null;
		}
		return false;
	}

		// make up a function to be called when the Dialog ends.
	Dialog._return = function (val) {
		if(val && action) { action(val); }

			// release the captured events
		function relwin(w) {
			HTMLArea._removeEvent(w, "focus", function(ev) { Dialog._parentEvent(ev); });
			try { for (var i=0;i < w.frames.length;i++) { relwin(w.frames[i]); } } catch(e) { }
		}
		relwin(window);

		HTMLArea._removeEvent(window, "unload", closeDialog);
		Dialog._dialog = null;
	};

		// capture unload events
	HTMLArea._addEvent(dlg, "unload", function() { if (typeof(Dialog) != "undefined") Dialog._return(null); return false; });
	HTMLArea._addEvent(window, "unload", closeDialog);
};

Dialog._parentEvent = function(ev) {
	if (Dialog._modal && !Dialog._modal.closed) {
		if (!ev) var ev = window.event;
		var target = (ev.target) ? ev.target : ev.srcElement;
		Dialog._modal.focus();
		HTMLArea._stopEvent(ev);
	}
	return false;
};

/*
 * Request modal dialog
 * Receives an URL to the popup dialog, an action function that receives one value and an initialization object.
 * The action function will get called after the dialog is closed, with the return value of the dialog.
 */
HTMLArea.prototype._popupDialog = function(url, action, init, width, height, _opener, scrollbars) {
	if (typeof(_opener) == "undefined" || !_opener) var _opener = (this._iframe.contentWindow ? this._iframe.contentWindow : window);
	if (typeof(scrollbars) == "undefined") var scrollbars = "no";
	Dialog(this.popupURL(url), action, init, width, height, _opener, this, scrollbars);
};

/**
 * Internet Explorer returns an item having the _name_ equal to the given id, even if it's not having any id.
 * This way it can return a different form field even if it's not a textarea.  This works around the problem by
 * specifically looking to search only elements having a certain tag name.
 */
HTMLArea.getElementById = function(tag, id) {
	var el, i, objs = document.getElementsByTagName(tag);
	for (i = objs.length; --i >= 0 && (el = objs[i]);) {
		if (el.id == id) return el;
	}
	return null;
};

/*
 * Hide the popup window
 */
HTMLArea.edHidePopup = function() {
	Dialog._modal.close();
	setTimeout( "if (typeof(browserWin) != 'undefined' && typeof(browserWin.focus) == 'function') browserWin.focus();", 200);
};

/***************************************************
 * TYPO3-SPECIFIC FUNCTIONS
 ***************************************************/
/*
 * Set the size of textarea with the RTE. It's called, if we are in fullscreen-mode.
 */
var setRTEsizeByJS = function(divId, height, width) {
	if (HTMLArea.is_gecko) height = height - 25; 
		else height = height - 60;
	if (height > 0) document.getElementById(divId).style.height =  height + "px";
	if (HTMLArea.is_gecko) width = "99%"; 
		else width = "97%";
	document.getElementById(divId).style.width = width;
};

/*
 * Extending the TYPO3 Lorem Ipsum extension
 */
var lorem_ipsum = function(element,text) {
	if (element.tagName.toLowerCase() == "textarea" && element.id && element.id.substr(0,7) == "RTEarea") {
		var editor = RTEarea[element.id.substr(7,8)]["editor"];
		editor.insertHTML(text);
		editor.updateToolbar();
	}
};

/*
 * Initialize the editor, configure the toolbar, setup the plugins, etc.
 */
HTMLArea.initTimer = [];

HTMLArea.onGenerateHandler = function(editorNumber) {
	return (function() {
		document.getElementById('pleasewait' + editorNumber).style.display = 'none';
		document.getElementById('editorWrap' + editorNumber).style.visibility = 'visible';
		editorNumber = null;
	});
};

HTMLArea.initEditor = function(editorNumber) {
	if(HTMLArea.checkSupportedBrowser()) {
		document.getElementById('pleasewait' + editorNumber).style.display = 'block';
		document.getElementById('editorWrap' + editorNumber).style.visibility = 'hidden';
		if(HTMLArea.initTimer[editorNumber]) window.clearTimeout(HTMLArea.initTimer[editorNumber]);
		if(!HTMLArea.is_loaded) {
			HTMLArea.initTimer[editorNumber] = window.setTimeout( "HTMLArea.initEditor(" + editorNumber + ");", 150);
		} else {
			var RTE = RTEarea[editorNumber];
			
				// Get the configuration properties
			var config = new HTMLArea.Config();
			for (var property in RTE) {
				if (RTE.hasOwnProperty(property)) {
					config[property] = RTE[property] ? RTE[property] : false;
				}
			}
				// Create an editor for the textarea
			var editor = new HTMLArea(RTE.id, config);
			RTE.editor = editor;
			
				// Save the editornumber in the object
			editor._typo3EditerNumber = editorNumber;
			editor._editorNumber = editorNumber;
			
				// Override these settings if they were ever modified
			editor.config.width = "auto";
			editor.config.height = "auto";
			editor.config.sizeIncludesToolbar = true;
			editor.config.fullPage = false;
			
				// Register the plugins included in the configuration
			for (var plugin in editor.config.plugin) {
				if (editor.config.plugin.hasOwnProperty(plugin) && editor.config.plugin[plugin]) {
					editor.registerPlugin(plugin);
				}
			}
			
			editor.onGenerate = HTMLArea.onGenerateHandler(editorNumber);
			
			editor.generate();
			return false;
		} 
	} else {
		document.getElementById('pleasewait' + editorNumber).style.display = 'none';
		document.getElementById('editorWrap' + editorNumber).style.visibility = 'visible';
	}
};

HTMLArea.allElementsAreDisplayed = function(elements) {
	for (var i=0, length=elements.length; i < length; i++) {
		if (document.getElementById(elements[i]).style.display == 'none') {
			return false;
		}
	}
	return true;
};

/**
 *	Base, version 1.0.2
 *	Copyright 2006, Dean Edwards
 *	License: http://creativecommons.org/licenses/LGPL/2.1/
 */

HTMLArea.Base = function() {
	if (arguments.length) {
		if (this == window) { // cast an object to this class
			HTMLArea.Base.prototype.extend.call(arguments[0], arguments.callee.prototype);
		} else {
			this.extend(arguments[0]);
		}
	}
};

HTMLArea.Base.version = "1.0.2";

HTMLArea.Base.prototype = {
	extend: function(source, value) {
		var extend = HTMLArea.Base.prototype.extend;
		if (arguments.length == 2) {
			var ancestor = this[source];
			// overriding?
			if ((ancestor instanceof Function) && (value instanceof Function) &&
				ancestor.valueOf() != value.valueOf() && /\bbase\b/.test(value)) {
				var method = value;
			//	var _prototype = this.constructor.prototype;
			//	var fromPrototype = !Base._prototyping && _prototype[source] == ancestor;
				value = function() {
					var previous = this.base;
				//	this.base = fromPrototype ? _prototype[source] : ancestor;
					this.base = ancestor;
					var returnValue = method.apply(this, arguments);
					this.base = previous;
					return returnValue;
				};
				// point to the underlying method
				value.valueOf = function() {
					return method;
				};
				value.toString = function() {
					return String(method);
				};
			}
			return this[source] = value;
		} else if (source) {
			var _prototype = {toSource: null};
			// do the "toString" and other methods manually
			var _protected = ["toString", "valueOf"];
			// if we are prototyping then include the constructor
			if (HTMLArea.Base._prototyping) _protected[2] = "constructor";
			for (var i = 0; (name = _protected[i]); i++) {
				if (source[name] != _prototype[name]) {
					extend.call(this, name, source[name]);
				}
			}
			// copy each of the source object's properties to this object
			for (var name in source) {
				if (!_prototype[name]) {
					extend.call(this, name, source[name]);
				}
			}
		}
		return this;
	},

	base: function() {
		// call this method from any other method to invoke that method's ancestor
	}
};

HTMLArea.Base.extend = function(_instance, _static) {
	var extend = HTMLArea.Base.prototype.extend;
	if (!_instance) _instance = {};
	// build the prototype
	HTMLArea.Base._prototyping = true;
	var _prototype = new this;
	extend.call(_prototype, _instance);
	var constructor = _prototype.constructor;
	_prototype.constructor = this;
	delete HTMLArea.Base._prototyping;
	// create the wrapper for the constructor function
	var klass = function() {
		if (!HTMLArea.Base._prototyping) constructor.apply(this, arguments);
		this.constructor = klass;
	};
	klass.prototype = _prototype;
	// build the class interface
	klass.extend = this.extend;
	klass.implement = this.implement;
	klass.toString = function() {
		return String(constructor);
	};
	extend.call(klass, _static);
	// single instance
	var object = constructor ? klass : _prototype;
	// class initialisation
	if (object.init instanceof Function) object.init();
	return object;
};

HTMLArea.Base.implement = function(_interface) {
	if (_interface instanceof Function) _interface = _interface.prototype;
	this.prototype.extend(_interface);
};

/**
 * HTMLArea.plugin class
 *
 * Every plugin should be a subclass of this class
 *
 */
HTMLArea.Plugin = HTMLArea.Base.extend({
	
	/**
	 * HTMLArea.plugin constructor
	 *
	 * @param	object		editor: instance of RTE
	 * @param	string		pluginName: name of the plugin
	 *
	 * @return	boolean		true if the plugin was configured
	 */
	constructor : function(editor, pluginName) {
		this.editor = editor;
		this.editorNumber = editor._editorNumber;
		this.editorConfiguration = editor.config;
		this.name = pluginName;
		try {
			HTMLArea.I18N[this.name] = eval(this.name + "_langArray");
			this.I18N = HTMLArea.I18N[this.name];
		} catch(e) {
			this.appendToLog("initialize", "The localization array for plugin " + this.name + "  could not be assigned.");
		}
		return this.configurePlugin(editor);
	},
	
	/**
	 * Configures the plugin
	 * This function is invoked by the class constructor.
	 * This function should be redefined by the plugin subclass. Normal steps would be:
	 *	- registering plugin ingormation with method registerPluginInformation;
	 *	- registering any buttons with method registerButton;
	 *	- registering any drop-down lists with method registerDropDown.
	 *
	 * @param	object		editor: instance of RTE
	 *
	 * @return	boolean		true if the plugin was configured
	 */
	configurePlugin : function(editor) {
		return false;
	},
	
	/**
	 * Registers the plugin "About" information
	 *
	 * @param	object		pluginInformation:
	 *					version		: the version,
	 *					developer	: the name of the developer,
	 *					developerUrl	: the url of the developer,
	 *					copyrightOwner	: the name of the copyright owner,
	 *					sponsor		: the name of the sponsor,
	 *					sponsorUrl	: the url of the sponsor,
	 *					license		: the type of license (should be "GPL")
	 *
	 * @return	boolean		true if the information was registered
	 */
	registerPluginInformation : function(pluginInformation) {
		if (typeof(pluginInformation) !== "object") {
			this.appendToLog("registerPluginInformation", "Plugin information was not provided");
			return false;
		} else {
			this.pluginInformation = pluginInformation;
			this.pluginInformation.name = this.name;
				/* Ensure backwards compatibility */
			this.pluginInformation.developer_url = this.pluginInformation.developerUrl;
			this.pluginInformation.c_owner = this.pluginInformation.copyrightOwner;
			this.pluginInformation.sponsor_url = this.pluginInformation.sponsorUrl;
			return true;
		}
	},
	
	/**
	 * Returns the plugin information
	 *
	 * @return	object		the plugin information object
	 */
	getPluginInformation : function() {
		return this.pluginInformation;
	},
	
	/**
	 * Returns true if the button is enabled in the toolbar configuration
	 *
	 * @param	string		buttonId: identification of the button
	 *
	 * @return	boolean		true if the button is enabled in the toolbar configuration
	 */
	isButtonInToolbar : function(buttonId) {
		var toolbar = this.editorConfiguration.toolbar;
		var n = toolbar.length;
		for ( var i = 0; i < n; ++i ) {
			var buttonInToolbar = new RegExp( "^(" + toolbar[i].join("|") + ")$", "i");
			if (buttonInToolbar.test(buttonId)) {
				return true;
			}
		}
		return false;
	},
	
	/**
	 * Registors a button for inclusion in the toolbar
	 *
	 * @param	object		buttonConfiguration: the configuration object of the button:
	 *					id		: unique id for the button
	 *					tooltip		: tooltip for the button
	 *					textMode	: enable in text mode
	 *					action		: name of the function invoked when the button is pressed
	 *					context		: will be disabled if not inside one of listed elements
	 *					hide		: hide in menu and show only in context menu?
	 *					selection	: will be disabled if there is no selection?
	 *					hotkey		: hotkey character
	 *					dialog		: if true, the button opens a dialog
	 *
	 * @return	boolean		true if the button was successfully registered
	 */
	registerButton : function (buttonConfiguration) {
		if (this.isButtonInToolbar(buttonConfiguration.id)) {
			if ((typeof(buttonConfiguration.action) === "string") && (typeof(this[buttonConfiguration.action]) === "function")) {
				var hotKeyAction = buttonConfiguration.action;
				var actionFunctionReference = this.makeFunctionReference(buttonConfiguration.action);
				buttonConfiguration.action = actionFunctionReference;
				if (!buttonConfiguration.textMode) {
					buttonConfiguration.textMode = false;
				}
				if (!buttonConfiguration.dialog) {
					buttonConfiguration.dialog = false;
				}
				if (this.editorConfiguration.registerButton(buttonConfiguration)) {
					var hotKey = buttonConfiguration.hotKey ? buttonConfiguration.hotKey : ((this.editorConfiguration.buttons[buttonConfiguration.id.toLowerCase()] && this.editorConfiguration.buttons[buttonConfiguration.id.toLowerCase()].hotKey) ? this.editorConfiguration.buttons[buttonConfiguration.id.toLowerCase()].hotKey : null);
					if (hotKey) {
						var hotKeyConfiguration = {
							id	: hotKey,
							cmd	: buttonConfiguration.id,
							action	: hotKeyAction
						};
						return this.registerHotKey(hotKeyConfiguration);
					}
					return true;
				}
			} else {
				this.appendToLog("registerButton", "Function " + buttonConfiguration.action + " was not defined when registering button " + buttonConfiguration.id);
			}
		}
		return false;
	},
	
	/**
	 * Registors a drop-down list for inclusion in the toolbar
	 *
	 * @param	object		dropDownConfiguration: the configuration object of the drop-down:
	 *					id		: unique id for the drop-down
	 *					tooltip		: tooltip for the drop-down
	 *					textMode	: enable in text mode
	 *					action		: name of the function invoked when a new option is selected
	 *					refresh		: name of the function invoked in order to refresh the drop-down when the toolbar is updated
	 *					context		: will be disabled if not inside one of listed elements
	 *
	 * @return	boolean		true if the drop-down list was successfully registered
	 */
	registerDropDown : function (dropDownConfiguration) {
		if (this.isButtonInToolbar(dropDownConfiguration.id)) {
			if (typeof((dropDownConfiguration.action) === "string") && (typeof(this[dropDownConfiguration.action]) === "function")) {
				var actionFunctionReference = this.makeFunctionReference(dropDownConfiguration.action);
				dropDownConfiguration.action = actionFunctionReference;
				if (!dropDownConfiguration.textMode) {
					dropDownConfiguration.textMode = false;
				}
				if (typeof(dropDownConfiguration.refresh) === "string") {
					if (typeof(this[dropDownConfiguration.refresh]) === "function") {
						var refreshFunctionReference = this.makeFunctionReference(dropDownConfiguration.refresh);
						dropDownConfiguration.refresh = refreshFunctionReference;
					} else {
						this.appendToLog("registerDropDown", "Function " + dropDownConfiguration.refresh + " was not defined when registering drop-down " + dropDownConfiguration.id);
						return false;
					}
				}
				return this.editorConfiguration.registerDropdown(dropDownConfiguration);
			} else {
				this.appendToLog("registerDropDown", "Function " + dropDownConfiguration.action + " was not defined when registering drop-down " + dropDownConfiguration.id);
			}
		}
		return false;
	},
	
	/**
	 * Returns the drop-down configuration
	 *
	 * @param	string		dropDownId: the unique id of the drop-down
	 *
	 * @return	object		the drop-down configuration object
	 */
	getDropDownConfiguration : function(dropDownId) {
		return this.editorConfiguration.customSelects[dropDownId];
	},
	
	/**
	 * Registors a hotkey
	 *
	 * @param	object		hotKeyConfiguration: the configuration object of the hotkey:
	 *					id		: the key
	 *					action		: name of the function invoked when a hotkey is pressed
	 *
	 * @return	boolean		true if the hotkey was successfully registered
	 */
	registerHotKey : function (hotKeyConfiguration) {
		if (typeof((hotKeyConfiguration.action) === "string") && (typeof(this[hotKeyConfiguration.action]) === "function")) {
			var actionFunctionReference = this.makeFunctionReference(hotKeyConfiguration.action);
			hotKeyConfiguration.action = actionFunctionReference;
			return this.editorConfiguration.registerHotKey(hotKeyConfiguration);
		} else {
			this.appendToLog("registerHotKey", "Function " + hotKeyConfiguration.action + " was not defined when registering hotkey " + hotKeyConfiguration.id);
			return false;
		}
	},
	
	/**
	 * Returns the buttonId corresponding to the hotkey, if any
	 *
	 * @param	string		key: the hotkey
	 *
	 * @return	string		the buttonId or ""
	 */
	translateHotKey : function(key) {
		if (typeof(this.editorConfiguration.hotKeyList[key]) !== "undefined") {
			var buttonId = this.editorConfiguration.hotKeyList[key].cmd;
			if (typeof(buttonId) !== "undefined") {
				return buttonId;
			} else {
				return "";
			}
		}
		return "";
	},
	
	/**
	 * Returns the hotkey configuration
	 *
	 * @param	string		key: the hotkey
	 *
	 * @return	object		the hotkey configuration object
	 */
	getHotKeyConfiguration : function(key) {
		if (typeof(this.editorConfiguration.hotKeyList[key]) !== "undefined") {
			return this.editorConfiguration.hotKeyList[key];
		} else {
			return null;
		}
	},
	
	/**
	 * The toolbar refresh handler of the plugin
	 * This function may be defined by the plugin subclass.
	 * If defined, the function will be invoked whenever the toolbar state is refreshed.
	 *
	 * @return	boolean
	 */
	onUpdateToolbar : null,
	
	/**
	 * The keyPress event handler
	 * This function may be defined by the plugin subclass.
	 * If defined, the function is invoked whenever a key is pressed.
	 *
	 * @param	event		keyEvent: the event that was triggered when a key was pressed
	 *
	 * @return	boolean
	 */
	onKeyPress : null,
	
	/**
	 * The hotKey event handler
	 * This function may be defined by the plugin subclass.
	 * If defined, the function is invoked whenever a hot key is pressed.
	 *
	 * @param	event		key: the hot key that was pressed
	 *
	 * @return	boolean
	 */
	onHotKey : null,
	
	/**
	 * The onMode event handler
	 * This function may be redefined by the plugin subclass.
	 * The function is invoked whenever the editor changes mode.
	 *
	 * @param	string		mode: "wysiwyg" or "textmode"
	 *
	 * @return	boolean
	 */
	onMode : function(mode) {
		if (this.dialog && mode === "textmode" && !this.editorConfiguration.btnList[this.dialog.buttonId].textMode) {
			this.dialog.close();
		}
	},
	
	/**
	 * The onGenerate event handler
	 * This function may be defined by the plugin subclass.
	 * If defined, the function is invoked when the editor is initialized
	 *
	 * @return	boolean
	 */
	onGenerate : null,
	
	/**
	 * Make function reference in order to avoid memory leakage in IE
	 *
	 * @param	string		functionName: the name of the plugin function to be invoked
	 *
	 * @return	function	function definition invoking the specified function of the plugin
	 */
	makeFunctionReference : function (functionName) {
		var self = this;
		return (function(arg1, arg2) {
			self[functionName](arg1, arg2);});
	},
	
	/**
	 * Localize a string
	 *
	 * @param	string		label: the name of the label to localize
	 *
	 * @return	string		the localization of the label
	 */
	localize : function (label) {
		return this.I18N[label];
	},
	
	/**
	 * Load a Javascript file synchronously
	 *
	 * @param	string		url: url of the file to load
	 *
	 * @return	boolean		true on success
	 */
	getJavascriptFile : function (url, noEval) {
		var script = HTMLArea._getScript(0, false, url);
		if (script) {
			if (noEval) {
				return script;
			} else {
				try {
					eval(script);
					return true;
				} catch(e) {
					this.appendToLog("getJavascriptFile", "Error evaluating contents of Javascript file: " + url);
					return false;
				}
			}
		} else {
			return false;
		}
	},
	
	/**
	 * Open a dialog window or bring focus to it if is already opened
	 *
	 * @param	string		buttonId: buttonId requesting the opening of the dialog
	 * @param	string		url: name, without extension, of the html file to be loaded into the dialog window
	 * @param	string		action: name of the plugin function to be invoked when the dialog ends
	 * @param	object		arguments: object of variable type to be passed to the dialog
	 * @param	object		dimensions: object giving the width and height of the dialog window
	 * @param	string		showScrollbars: specifies by "yes" or "no" whether or not the dialog window should have scrollbars
	 *
	 * @return	object		the dialog window
	 */
	openDialog : function (buttonId, url, action, arguments, dimensions, showScrollbars) {
		if (this.dialog && this.dialog.hasOpenedWindow() && this.dialog.buttonId === buttonId) {
			this.dialog.focus();
			return this.dialog;
		} else {
			var actionFunctionReference = action;
			if (typeof(action) === "string") {
				if (typeof(this[action]) === "function") {
					var actionFunctionReference = this.makeFunctionReference(action);
				} else {
					this.appendToLog("openDialog", "Function " + action + " was not defined when opening dialog for " + buttonId);
				}
			}
			return new HTMLArea.Dialog(
					this,
					buttonId,
					url,
					actionFunctionReference,
					arguments,
					{width: (dimensions.width?dimensions.width:100), height: (dimensions.height?dimensions.height:100)},
					(showScrollbars?showScrollbars:"no")
				);
		}
	},
	
	/**
	 * Make url from the name of a popup of the plugin
	 *
	 * @param	string		popupName: name, without extension, of the html file to be loaded into the dialog window
	 *
	 * @return	string		the url
	 */
	makeUrlFromPopupName : function(popupName) {
		return this.editor.popupURL("plugin://" + this.name + "/" + popupName);
	},
	
	/**
	 * Make url from module path
	 *
	 * @param	string		modulePath: module path
	 * @param	string		parameters: additional parameters
	 *
	 * @return	string		the url
	 */
	makeUrlFromModulePath : function(modulePath, parameters) {
		return this.editor.popupURL(modulePath + "?" + RTEarea[this.editorNumber]["RTEtsConfigParams"] + parameters);
	},
	
	/**
	 * Append an entry at the end of the troubleshooting log
	 *
	 * @param	string		functionName: the name of the plugin function writing to the log
	 * @param	string		text: the text of the message
	 *
	 * @return	void
	 */
	appendToLog : function (functionName, text) {
		HTMLArea._appendToLog("[" + this.name + "::" + functionName + "]: " + text);
	}
});

/**
 * HTMLArea.Dialog class
 *
 * Every dialog should be an instance of this class
 *
 */
HTMLArea.Dialog = HTMLArea.Base.extend({
	
	/**
	 * HTMLArea.Dialog constructor
	 *
	 * @param	object		plugin: reference to the invoking plugin
	 * @param	string		buttonId: buttonId triggering the opening of the dialog
	 * @param	string		url: url of the html document to load into the dialog window
	 * @param	function	action: function to be executed when the the dialog ends
	 * @param	object		arguments: object of variable type to be passed to the dialog
	 * @param	object		dimensions: object giving the width and height of the dialog window
	 * @param	string		showScrollbars: specifies by "yes" or "no" whether or not the dialog window should have scrollbars
	 *
	 * @return	boolean		true if the dialog window was opened
	 */
	constructor : function (plugin, buttonId, url, action, arguments, dimensions, showScrollbars) {
		this.window = window.window ? window.window : window.self;
		this.plugin = plugin;
		this.buttonId = buttonId;
		this.action = action;
		if (typeof(arguments) !== "undefined") {
			this.arguments = arguments;
		}
		this.plugin.dialog = this;
		
		if (HTMLArea.Dialog[this.plugin.name] && HTMLArea.Dialog[this.plugin.name].hasOpenedWindow() && HTMLArea.Dialog[this.plugin.name].plugin != this.plugin) {
			HTMLArea.Dialog[this.plugin.name].close();
		}
		HTMLArea.Dialog[this.plugin.name] = this;
		
		this.dialogWindow = window.open(url, this.plugin.name + "Dialog", "toolbar=no,location=no,directories=no,menubar=no,resizable=yes,top=100,left=100,dependent=yes,dialog=yes,chrome=no,width=" + dimensions.width + ",height=" + dimensions.height + ",scrollbars=" + showScrollbars);
		if (!this.dialogWindow) {
			this.plugin.appendToLog("openDialog", "Dialog window could not be opened with url " + url);
			return false;
		}
		if (!this.dialogWindow.opener) {
			this.dialogWindow.opener = this.window;
		}
		return true;
	},
	
	hasOpenedWindow : function () {
		return this.dialogWindow && !this.dialogWindow.closed;
	},
	
	/**
	 * Initialize the dialog window: load the stylesheets, localize labels, resize if required, etc.
	 * This function MUST be invoked from the dialog window in the onLoad event handler
	 *
	 * @param	boolean		noResize: if true the window in not resized, but may be centered
	 *
	 * @return	void
	 */
	initialize : function (noLocalize, noResize) {
		this.dialogWindow.HTMLArea = HTMLArea;
		this.dialogWindow.dialog = this;
			// Get stylesheets for the dialog window
		this.loadStyle();
			// Localize the labels of the popup window
		if (!noLocalize) this.localize();
			// Resize the dialog window to its contents
		if (!noResize) this.resize(noResize);
			// Capture unload and escape events
		this.captureEvents();
	},
	
	/**
	 * Load the stylesheets in the dialog window
	 *
	 * @return	void
	 */
	loadStyle : function () {
		var head = this.dialogWindow.document.getElementsByTagName("head")[0];
		var link = this.dialogWindow.document.createElement("link");
		link.rel = "stylesheet";
		link.type = "text/css";
		link.href = HTMLArea.editorCSS;
		head.appendChild(link);
	},
	
	/**
	 * Localize the labels contained in the dialog window
	 *
	 * @return	void
	 */
	localize : function () {
		var label;
		var types = ["input", "label", "option", "select", "legend", "span", "td", "button", "div", "h1", "h2", "a"];
		for (var type = 0; type < types.length; ++type) {
			var elements = this.dialogWindow.document.getElementsByTagName(types[type]);
			for (var i = elements.length; --i >= 0;) {
				var element = elements[i];
				if (element.firstChild && element.firstChild.data) {
					label = this.plugin.localize(element.firstChild.data);
					if (label) element.firstChild.data = label;
				}
				if (element.title) {
					element.title = this.plugin.localize(element.title);
				}
					// resetting the selected option for Mozilla
				if (types[type] == "option" && element.selected ) {
					element.selected = false;
					element.selected = true;
				}
			}
		}
		this.dialogWindow.document.title = this.plugin.localize(this.dialogWindow.document.title);
	},
	
	/**
	 * Resize the dialog window to its contents
	 *
	 * @param	boolean		noResize: if true the window in not resized, but may be centered
	 *
	 * @return	void
	 */
	resize : function (noResize) {
			// resize if allowed
		var dialogWindow = this.dialogWindow;
		var content = dialogWindow.document.getElementById("content");
		if ((HTMLArea.is_gecko && !HTMLArea.is_opera && !HTMLArea.is_safari) || (HTMLArea.is_opera && content)) {
			var self = this;
			setTimeout( function() {
				try {
					if (!noResize) {
						dialogWindow.sizeToContent();
					}
				} catch(e) {
					self.resizeToContent(content);
				}
					// center on parent if allowed
				var x = dialogWindow.opener.screenX + (dialogWindow.opener.outerWidth - dialogWindow.outerWidth) / 2;
				var y = dialogWindow.opener.screenY + (dialogWindow.opener.outerHeight - dialogWindow.outerHeight) / 2;
				try {
					dialogWindow.moveTo(x, y);
				} catch(e) { }
			}, 25);
		} else {
			var innerX, innerY;
			if (dialogWindow.innerHeight) {
					// all except Explorer
				innerX = dialogWindow.innerWidth;
				innerY = dialogWindow.innerHeight;
			} else if (dialogWindow.document.documentElement && dialogWindow.document.documentElement.clientHeight) {
					// Explorer 6 Strict Mode
				innerX = dialogWindow.document.documentElement.clientWidth;
				innerY = dialogWindow.document.documentElement.clientHeight;
			} else if (document.body) {
					// other Explorers
				innerX = dialogWindow.document.body.clientWidth;
				innerY = dialogWindow.document.body.clientHeight;
			}
			
			var pageX, pageY;
			var test1 = dialogWindow.document.body.scrollHeight;
			var test2 = dialogWindow.document.body.offsetHeight
			if (test1 > test2) {
					// all but Explorer Mac
				pageX = dialogWindow.document.body.scrollWidth;
				pageY = dialogWindow.document.body.scrollHeight;
			} else {
					// Explorer Mac;
					//would also work in Explorer 6 Strict, Mozilla and Safari
				pageX = dialogWindow.document.body.offsetWidth;
				pageY = dialogWindow.document.body.offsetHeight;
			}
			dialogWindow.resizeBy(pageX - innerX, pageY - innerY);
			
				// center on parent if allowed
			var W = dialogWindow.document.body.offsetWidth;
			var H = dialogWindow.document.body.offsetHeight;
			var x = (screen.availWidth - W) / 2;
			var y = (screen.availHeight - H) / 2;
			dialogWindow.moveTo(x, y);
		}
	},
	
	/**
	 * Resize the Opera dialog window to its contents, based on size of content div
	 *
	 * @param	object		content: reference to the div section containing the contents of the dialog window
	 *
	 * @return	void
	 */
	resizeToContent : function(content) {
		var dialogWindow = this.dialogWindow;
		var doc = dialogWindow.document;
		var docElement = doc.documentElement;
		var body = doc.body;
		var width = 0, height = 0;
	
		var contentWidth = content.offsetWidth;
		var contentHeight = content.offsetHeight;
		dialogWindow.resizeTo( contentWidth + 200, contentHeight + 200 );
		
		if (dialogWindow.innerWidth) {
			width = dialogWindow.innerWidth;
			height = dialogWindow.innerHeight;
		} else if (docElement && docElement.clientWidth) {
			width = docElement.clientWidth;
			height = docElement.clientHeight;
		} else if (body && body.clientWidth) {
			width = body.clientWidth;
			height = body.clientHeight;
		}
		dialogWindow.resizeTo( contentWidth + ( ( contentWidth + 200 ) - width ), contentHeight + ( (contentHeight + 200 ) - (height - 16) ) );
	},
	
	/**
	 * Perform the action function when the dialog end
	 *
	 * @return	void
	 */
	performAction : function (val) {
		if (val && this.action) {
			this.action(val);
		}
	},
	
	/**
	 * Bring the focus on the dialog window
	 *
	 * @return	void
	 */
	focus : function () {
		this.dialogWindow.focus();
	},
	
	/**
	 * Close the dialog window
	 *
	 * @return	void
	 */
	close : function () {
		if (this.hasOpenedWindow()) {
			if (this.dialogWindow.opener && !this.dialogWindow.opener.closed) {
				this.releaseEvents();
			}
			this.releaseEvents(this.dialogWindow);
			HTMLArea.Dialog[this.plugin.name] = null;
			if (HTMLArea.is_safari) {
				this.dialogWindow.blur();
			}
			this.dialogWindow.close();
			this.dialogWindow.dialog = null;
			this.plugin.editor.updateToolbar();
		}
		return false;
	},
	
	/**
	 * Make function reference in order to avoid memory leakage in IE
	 *
	 * @param	string		functionName: the name of the dialog function to be invoked
	 *
	 * @return	function	function definition invoking the specified function of the dialog
	 */
	makeFunctionReference : function (functionName) {
		var self = this;
		return (function(arg1, arg2) {
			self[functionName](arg1, arg2);});
	},
	
	/**
	 * Escape event handler
	 *
	 * @param	object		ev: the event
	 *
	 * @return	boolean		false if the event was handled
	 */
	closeOnEscape : function(ev) {
		if (!ev) var ev = window.event;
		if (ev.keyCode == 27) {
			if (HTMLArea.is_gecko) {
				var parentWindow = ev.currentTarget.defaultView;
			} else {
				var parentWindow = ev.srcElement.parentNode.parentNode.parentWindow;
			}
			if (parentWindow && parentWindow.dialog) {
				parentWindow.dialog.close();
				return false;
			}
		}
		return true;
	},
	
	/**
	 *Capture unload and escape events
	 *
	 * @return	void
	 */	
	captureEvents : function () {
		this.unloadFunctionReference = this.makeFunctionReference("close");
		HTMLArea._addEvent(this.dialogWindow.opener, "unload", this.unloadFunctionReference);
		if (HTMLArea.is_gecko && this.plugin.editor._iframe.contentWindow) {
			HTMLArea._addEvent(this.plugin.editor._iframe.contentWindow, "unload", this.unloadFunctionReference);
		}
		HTMLArea._addEvent(this.dialogWindow, "unload", this.unloadFunctionReference);
		this.escapeFunctionReference = this.makeFunctionReference("closeOnEscape");
		HTMLArea._addEvent(this.dialogWindow.document, "keypress", this.escapeFunctionReference);
	 },
	
	/**
	 * Release all event handlers that were set when the dialog window was opened
	 *
	 * @return	void
	 */
	releaseEvents : function() {
		var opener = this.dialogWindow.opener;
		if (opener && !opener.closed) {
				// release the capturing of events
			HTMLArea._removeEvent(opener, "unload", this.unloadFunctionReference);
			HTMLArea._removeEvent(this.dialogWindow, "unload", this.unloadFunctionReference);
			if (HTMLArea.is_gecko && this.plugin.editor._iframe.contentWindow) {
				HTMLArea._removeEvent(this.plugin.editor._iframe.contentWindow, "unload", this.unloadFunctionReference);
			}
			HTMLArea._removeEvent(this.dialogWindow.document, "keypress", this.escapeFunctionReference);
		}
	}
});

