/***************************************************************
*  Copyright notice
*
*  (c) 2002-2004, interactivetools.com, inc.
*  (c) 2003-2004 dynarch.com
*  (c) 2004, 2005, 2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * TYPO3 CVS ID: $Id$
 */

PopupWin = function(editor, _title, handler, initFunction, width, height, _opener) {
	this.editor = editor;
	this.handler = handler;
	if (typeof initFunction == "undefined") initFunction = window;	// pass this window object by default
	this._geckoOpenModal(editor, _title, handler, initFunction, width, height, _opener);
};

	// Bring focus from the parent window to the popup
PopupWin.prototype._parentEvent = function(ev) {
	if (this.dialogWindow && !this.dialogWindow.closed) {
		if(!ev) var ev = this.dialogWindow.opener.event;
		HTMLArea._stopEvent(ev);
		this.dialogWindow.focus();
	}
	return false;
};

	// Open the popup
PopupWin.prototype._geckoOpenModal = function(editor, _title, handler, initFunction, width, height, _opener) {
	if(!editor) var editor = this.editor;
	var dlg = editor._iframe.contentWindow.open("", "", "toolbar=no,menubar=no,personalbar=no,width=" + (width?width:100) + ",height=" + (height?height:100) + ",scrollbars=no,resizable=yes,modal=yes,dependent=yes");
	if(!dlg)  var dlg = window.open("", "", "toolbar=no,menubar=no,personalbar=no,width=" + (width?width:100) + ",height=" + (height?height:100) + ",scrollbars=no,resizable=yes,modal=yes,dependent=yes");
	this.dialogWindow = dlg;
	this._opener = (_opener) ? _opener : this.dialogWindow.opener;
	this._opener.dialog = this;
	if(Dialog._modal && !Dialog._modal.closed) Dialog._dialog = this;
	var doc = this.dialogWindow.document;
	this.doc = doc;

	if (doc.all) {
		doc.open();
		var html = "<html><head></head><body></body></html>\n";
		doc.write(html);
		doc.close();
	}
	var html = doc.documentElement;
	html.className = "popupwin";
	var head = doc.getElementsByTagName("head")[0];
	if(!doc.all) var head = doc.createElement("head");
	var title = doc.createElement("title");
	head.appendChild(title);
	doc.title = _title;
	var link = doc.createElement("link");
	link.rel = "stylesheet";
	link.type ="text/css";
	if( _editor_CSS.indexOf("http") == -1 ) {
		link.href = _typo3_host_url + _editor_CSS;
	} else {
		link.href = _editor_CSS;
	}
	head.appendChild(link);
	if(!doc.all) html.appendChild(head);
	var body = doc.body;
	if(!doc.all) var body = doc.createElement("body");
	body.className = "popupwin dialog";
	body.id = "--HA-body";
	var content = doc.createElement("div");
	content.className = "content";
	this.content = content;
	body.appendChild(content);
	if(!doc.all) html.appendChild(body);
	this.element = body;

	initFunction(this);

	this.captureEvents();
	this.dialogWindow.focus();
};

	// Close the popup when escape is hit
PopupWin.prototype._dlg_close_on_esc = function(ev) {
	if (ev.keyCode == 27) {
		this.releaseEvents();
		this.close();
		return false;
	}
	return true;
};

	// Call the form input handler
PopupWin.prototype.callHandler = function() {
	var tags = ["input", "textarea", "select"];
	var params = new Object();
	for (var ti = tags.length; --ti >= 0;) {
		var tag = tags[ti];
		var els = this.content.getElementsByTagName(tag);
		for (var j = 0; j < els.length; ++j) {
			var el = els[j];
			var val = el.value;
			if (el.tagName.toLowerCase() == "input") {
				if (el.type == "checkbox") {
					val = el.checked;
				}
			}
			params[el.name] = val;
		}
	}
	this.handler(this, params);
	return false;
};

	// Capture some events
PopupWin.prototype.captureEvents = function() {
		// capture some events on the opener window
	var editor = this.editor;
	var _opener = this._opener;
	var self = this;

	function capwin(w) {
		if(w.addEventListener) {
			w.addEventListener("focus", self._parentEvent, false);
		} else {
			HTMLArea._addEvent(w, "focus", function(ev) {self._parentEvent(ev); });
		}
		for (var i = 0; i < w.frames.length; i++) { capwin(w.frames[i]); }
	};
	capwin(window);

		// capture unload events
	HTMLArea._addEvent(_opener, "unload", function() { self.releaseEvents(); self.close(); return false; });
	HTMLArea._addEvent(self.dialogWindow, "unload", function() { self.releaseEvents(); self.close(); return false; });
		// capture escape events
	HTMLArea._addEvent(self.doc, "keypress", function(ev) { return self._dlg_close_on_esc((!ev) ? self.dialogWindow.event : ev); });
};

	// Release the capturing of events
PopupWin.prototype.releaseEvents = function() {
	var editor = this.editor;
	var _opener = this._opener;
	if(_opener) {
		var self = this;
			// release the capturing of events
		function relwin(w) {
		if(w.removeEventListener) {
			HTMLArea._removeEvent(w, "focus", self._parentEvent);
		} else {
			HTMLArea._removeEvent(w, "focus", function(ev) {self._parentEvent(ev); });
		}
			try { for (var i = 0; i < w.frames.length; i++) { relwin(w.frames[i]); }; } catch(e) { };
		};
		relwin(window);
		HTMLArea._removeEvent(_opener, "unload", function() { self.releaseEvents(); self.close(); return false; });	
	}
};

	// Close the popup
PopupWin.prototype.close = function() {
	if(this.dialogWindow && this.dialogWindow.dialog) {
		this.dialogWindow.dialog.releaseEvents();
		this.dialogWindow.dialog.close();
		this.dialogWindow.dialog = null;
	}
	if(this.dialogWindow) {
		this.dialogWindow.close();
		this.dialogWindow = null;
	}
	if(this._opener) this._opener.focus();
	if(this._opener.dialog) this._opener.dialog = null;
};

	// Add OK and Cancel buttons to the popup
PopupWin.prototype.addButtons = function() {
	var self = this;
	var div = this.doc.createElement("div");
	this.content.appendChild(div);
	div.className = "buttons";
	for (var i = 0; i < arguments.length; ++i) {
		var btn = arguments[i];
		var button = this.doc.createElement("button");
		div.appendChild(button);
		switch (btn) {
		    case "ok":
		    	button.innerHTML = HTMLArea.I18N.dialogs["OK"];
			button.onclick = function() {
				try { self.callHandler(); } catch(e) { };
				self.releaseEvents();
				self.close();
				return false;
			};
			break;
		    case "cancel":
		    	button.innerHTML = HTMLArea.I18N.dialogs["Cancel"];
			button.onclick = function() {
				self.releaseEvents();
				self.close();
				return false;
			};
			break;
		}
	}
};

	// Resize the popup and center on screen
PopupWin.prototype.showAtElement = function() {
	var self = this;
	var doc = self.doc;
	var body = doc.body;
		// resize if allowed
	if (!HTMLArea.is_ie) {
		setTimeout( function() {
			try {
				self.dialogWindow.sizeToContent();
				self.dialogWindow.innerWidth += 20;
			} catch(e) { };
				// center on parent if allowed
			var x = self._opener.screenX + (self._opener.outerWidth - self.dialogWindow.outerWidth) / 2;
			var y = self._opener.screenY + (self._opener.outerHeight - self.dialogWindow.outerHeight) / 2;
			try {
				self.dialogWindow.moveTo(x, y);
			} catch(e) { };
		}, 25);
	} else {
		/* Something broken in IE ...
		var w = body.scrollWidth + 12;
		if (doc.documentElement && doc.documentElement.clientHeight) var h = doc.documentElement.clientHeight;
			else var h = body.clientHeight;
		if(h < body.scrollHeight) var h = body.scrollHeight;
		if(h < body.offsetHeight) h = body.offsetHeight;
		*/
		var h = this.content.offsetHeight + 12;
		var w = this.content.offsetWidth + 12;
		
		self.dialogWindow.resizeTo(w, h);
		
		if (doc.documentElement && doc.documentElement.clientHeight) {
			var ch = doc.documentElement.clientHeight;
			var cw = doc.documentElement.clientWidth;
		} else {
			var ch = body.clientHeight;
			var cw = body.clientWidth;
		}
		self.dialogWindow.resizeBy(w - cw, h - ch);
		
			// center on parent if allowed
		self.dialogWindow.moveTo((screen.availWidth - body.offsetWidth)/2,(screen.availHeight - body.offsetHeight)/2);
	}
};

