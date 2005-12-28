// htmlArea v3.0 - Copyright (c) 2002, 2003 interactivetools.com, inc.
// This copyright notice MUST stay intact for use (see license.txt).
// Portions (c) dynarch.com, 2003
//
// (c) 2004-2005, Stanislas Rolland <stanislas.rolland@fructifor.ca> for the TYPO3 htmlArea RTE version
//

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
	var body = document.body;
	window.dialogArguments = window.opener.Dialog._arguments;
		// resize if allowed
	if (!HTMLArea.is_ie) {
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
		var w = body.scrollWidth +12;
		if (document.documentElement && document.documentElement.clientHeight) var h = document.documentElement.clientHeight;
			else var h = document.body.clientHeight;
		if(h < body.scrollHeight) h = body.scrollHeight;
		if(h < body.offsetHeight) h = body.offsetHeight;
		
			// Sometimes IE is broken here, in those cases we wrap the inside of the body into a div with id = "content"
			// Then it seems that while the size of the body is wrong, the size of the div is right
		var content = document.getElementById("content");
		if(content) {
			var h = content.offsetHeight + 12;
			var w = content.offsetWidth + 12;
		}
		window.resizeTo(w, h);
		if (document.documentElement && document.documentElement.clientHeight) {
			var ch = document.documentElement.clientHeight;
			var cw = document.documentElement.clientWidth;
		} else {
			var ch = body.clientHeight;
			var cw = body.clientWidth;
		}
		window.resizeBy(w - cw, h - ch);
			// center on parent if allowed
		var W = body.offsetWidth;
		var H = body.offsetHeight;
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
