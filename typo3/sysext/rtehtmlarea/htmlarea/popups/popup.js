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
 * Popup utilities for TYPO3 htmlArea RTE
 *
 * TYPO3 CVS ID: $Id$
 */

getAbsolutePos = function(el) {
	var r = { x: el.offsetLeft, y: el.offsetTop };
	if (el.offsetParent) {
		var tmp = getAbsolutePos(el.offsetParent);
		r.x += tmp.x;
		r.y += tmp.y;
	}
	return r;
};

comboSelectValue = function(c, val) {
	var ops = c.getElementsByTagName("option");
	for (var i = ops.length; --i >= 0;) {
		var op = ops[i];
		op.selected = (op.value == val);
	}
	c.value = val;
};

__dlg_loadStyle = function(url) {
	var head = document.getElementsByTagName("head")[0];
	var link = document.createElement("link");
	link.rel = "stylesheet";
	link.href = url;
	head.appendChild(link);
}

__dlg_init = function(bottom,noResize) {
	window.dialogArguments = window.opener.Dialog._arguments;
		// resize if allowed
	if (HTMLArea.is_gecko && !HTMLArea.is_opera && !HTMLArea.is_safari) {
		setTimeout( function() {
			try {
				if (!noResize) window.sizeToContent();
			} catch(e) { };
				// center on parent if allowed
			var x = window.opener.screenX + (window.opener.outerWidth - window.outerWidth) / 2;
			var y = window.opener.screenY + (window.opener.outerHeight - window.outerHeight) / 2;
			try {
				window.moveTo(x, y);
			} catch(e) { };
		}, 25);
	} else {
		var innerX,innerY;
		if (self.innerHeight) {
				// all except Explorer
			innerX = self.innerWidth;
			innerY = self.innerHeight;
		} else if (document.documentElement && document.documentElement.clientHeight) {
				// Explorer 6 Strict Mode
			innerX = document.documentElement.clientWidth;
			innerY = document.documentElement.clientHeight;
		} else if (document.body) {
				// other Explorers
			innerX = document.body.clientWidth;
			innerY = document.body.clientHeight;
		}

		var pageX,pageY;
		var test1 = document.body.scrollHeight;
		var test2 = document.body.offsetHeight
		if (test1 > test2) {
				// all but Explorer Mac
			pageX = document.body.scrollWidth;
			pageY = document.body.scrollHeight;
		} else {
				// Explorer Mac;
				//would also work in Explorer 6 Strict, Mozilla and Safari
			pageX = document.body.offsetWidth;
			pageY = document.body.offsetHeight;
		}
		window.resizeBy(pageX - innerX, pageY - innerY);
			
			// center on parent if allowed
		var W = document.body.offsetWidth;
		var H = document.body.offsetHeight;
		var x = (screen.availWidth - W) / 2;
		var y = (screen.availHeight - H) / 2;
		window.moveTo(x, y);
	}
		// capture escape events
	HTMLArea._addEvent(document, "keypress", __dlg_close_on_esc);
};

__dlg_translate = function(i18n) {
	var types = ["input", "label", "option", "select", "legend", "span", "td", "button", "div", "h1", "h2", "a"];
	for(var type = 0; type < types.length; ++type) {
		var spans = document.getElementsByTagName(types[type]);
		for(var i = spans.length; --i >= 0;) {
			var span = spans[i];
			if(span.firstChild && span.firstChild.data) {
				var txt = i18n[span.firstChild.data];
				if (txt) span.firstChild.data = txt;
			}
			if(span.title) {
				var txt = i18n[span.title];
				if (txt) span.title = txt;
			}
				// resetting the selected option for Mozilla	 
			if(types[type] == "option" && span.selected ) { 	 
				span.selected = false;
				span.selected = true;
			}
		}
	}
	var txt = i18n[document.title];
	if(txt) document.title = txt;
};

// closes the dialog and passes the return info upper.
__dlg_close = function(val) {
	if(window.opener && window.opener.Dialog) window.opener.Dialog._return(val);
	window.close();
};

__dlg_close_on_esc = function(ev) {
	if(!ev) var ev = window.event;
	if (ev.keyCode == 27) {
		if(window.opener && window.opener.Dialog) window.opener.Dialog._return(null);
		window.close();
		return false;
	}
	return true;
};
