/**
 * Javascript functions regarding the clickmenu
 * relies on the javascript library "prototype"
 *
 * (c) 2007-2011 Benjamin Mack <www.xnos.org>
 * All rights reserved
 *
 * This script is part of TYPO3 by
 * Kasper Skaarhoj <kasperYYYYY@typo3.com>
 *
 * Released under GNU/GPL (see license file in tslib/)
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This copyright notice MUST APPEAR in all copies of this script
 */

/**
 * new clickmenu code to make an AJAX call and render the 
 * AJAX result in a layer next to the mouse cursor
 */
var Clickmenu = {
	clickURL: 'alt_clickmenu.php',	// URL to the clickmenu.php file, see template.php
	ajax: true,	// template.php -> isCMLayers check
	mousePos: { X: null, Y: null },
	delayClickMenuHide: false,

	/**
	 * main function, called from most clickmenu links
	 * @param	table		table from where info should be fetched
	 * @param	uid		the UID of the item
	 * @param	listFr		list Frame?
	 * @param	enDisItems	Items to disable / enable
	 * @param	backPath	TYPO3 backPath
	 * @param	addParams	additional params
	 * @return	nothing
	 */
	show: function(table, uid, listFr, enDisItems, backPath, addParams) {
		var params = 'table=' + encodeURIComponent(table) +
			'&uid=' + uid +
			'&listFr=' + listFr +
			'&enDisItems=' + enDisItems +
			'&backPath=' + backPath +
			'&addParams=' + addParams;
		this.callURL(params);
	},


	/**
	 * switch function that either makes an AJAX call
	 * or loads the request in the top frame
	 *
 	 * @param	params	parameters added to the URL
	 * @return	nothing
	 */ 
	callURL: function(params) {	
		if (this.ajax && Ajax.getTransport()) { // run with AJAX
			params += '&ajax=1';
			var call = new Ajax.Request(this.clickURL, {
				method: 'get',
				parameters: params,
				onComplete: function(xhr) {
					var response = xhr.responseXML;
					if (!response.getElementsByTagName('data')[0]) {
						return;
					}
					var menu  = response.getElementsByTagName('data')[0].getElementsByTagName('clickmenu')[0];
					var data  = menu.getElementsByTagName('htmltable')[0].firstChild.data;
					var level = menu.getElementsByTagName('cmlevel')[0].firstChild.data;
					this.populateData(data, level);
				}.bind(this)
			});
		}
	},


	/**
	 * fills the clickmenu with content and displays it correctly
	 * depending on the mouse position
	 * @param	data	the data that will be put in the menu
	 * @param	level	the depth of the clickmenu
	 */
	populateData: function(data, level) {
		if (!$('contentMenu0')) {
			this.addClickmenuItem();
		}
		level = parseInt(level, 10) || 0;
		var obj = $('contentMenu' + level);

		if (obj && (level === 0 || Element.visible('contentMenu' + (level-1)))) {
			obj.innerHTML = data;
			var x = this.mousePos.X;
			var y = this.mousePos.Y;
			var dimsWindow = document.viewport.getDimensions();
			dimsWindow.width = dimsWindow.width-20; // saving margin for scrollbars

			var dims = Element.getDimensions(obj); // dimensions for the clickmenu
			var offset = document.viewport.getScrollOffsets();
			var relative = { X: this.mousePos.X - offset.left, Y: this.mousePos.Y - offset.top };

			// adjusting the Y position of the layer to fit it into the window frame
			// if there is enough space above then put it upwards,
			// otherwise adjust it to the bottom of the window
			if (dimsWindow.height - dims.height < relative.Y) {
				if (relative.Y > dims.height) {
					y -= (dims.height - 10);
				} else {
					y += (dimsWindow.height - dims.height - relative.Y);
				}
			}
			// adjusting the X position like Y above, but align it to the left side of the viewport if it does not fit completely
			if (dimsWindow.width - dims.width < relative.X) {
				if (relative.X > dims.width) {
					x -= (dims.width - 10);
				} else if ((dimsWindow.width - dims.width - relative.X) < offset.left) {
					x = offset.left;
				} else {
					x += (dimsWindow.width - dims.width - relative.X);
				}
			}

			obj.style.left = x + 'px';
			obj.style.top  = y + 'px';
			Element.show(obj);
		}
		if (/MSIE5/.test(navigator.userAgent)) {
			this._toggleSelectorBoxes('hidden');
		}
	},


	/**
	 * event handler function that saves the actual position of the mouse
	 * in the Clickmenu object
	 * @param	event	the event object
	 */
	calcMousePosEvent: function(event) {
		if (!event) {
			event = window.event;
		}
		this.mousePos.X = Event.pointerX(event);
		this.mousePos.Y = Event.pointerY(event);
		this.mouseOutFromMenu('contentMenu0');
		this.mouseOutFromMenu('contentMenu1');
	},


	/**
	 * hides a visible menu if the mouse has moved outside
	 * of the object
	 * @param	obj	the object to hide
	 * @result	nothing
	 */
	mouseOutFromMenu: function(obj) {
		obj = $(obj);
		if (obj && Element.visible(obj) && !Position.within(obj, this.mousePos.X, this.mousePos.Y)) {
			this.hide(obj);
			if (/MSIE5/.test(navigator.userAgent) && obj.id === 'contentMenu0') {
				this._toggleSelectorBoxes('visible');
			}
		} else if (obj && Element.visible(obj)) {
			this.delayClickMenuHide = true;
		}
	},

	/**
	 * hides a clickmenu
	 *
	 * @param	obj	the clickmenu object to hide
	 * @result	nothing
	 */
	hide: function(obj) {
		this.delayClickMenuHide = false;
		window.setTimeout(function() {
			if (!Clickmenu.delayClickMenuHide) {
				Element.hide(obj);
			}
		}, 500);
	},

	/**
	 * hides all clickmenus
	 */
	hideAll: function() {
		this.hide('contentMenu0');
		this.hide('contentMenu1');
	},


	/**
	 * hides / displays all selector boxes in a page, fixes an IE 5 selector problem
	 * originally by Michiel van Leening
	 *
	 * @param	action 	hide (= "hidden") or show (= "visible")
	 * @result	nothing
	 */
	_toggleSelectorBoxes: function(action) {
		for (var i = 0; i < document.forms.length; i++) {
			for (var j = 0; j < document.forms[i].elements.length; j++) {
				if (document.forms[i].elements[j].type == 'select-one') {
					document.forms[i].elements[j].style.visibility = action;
				}
			}
		}
	},


	/**
	 * manipulates the DOM to add the divs needed for clickmenu at the bottom of the <body>-tag
	 *
	 * @return	nothing
	 */
	addClickmenuItem: function() {
		var code = '<div id="contentMenu0" style="display: block;"></div><div id="contentMenu1" style="display: block;"></div>';
		var insert = new Insertion.Bottom(document.getElementsByTagName('body')[0], code);
	}
}

// register mouse movement inside the document
Event.observe(document, 'mousemove', Clickmenu.calcMousePosEvent.bindAsEventListener(Clickmenu), true);


// @deprecated: Deprecated functions since 4.2, here for compatibility, remove in 4.4+
// ## BEGIN ##

// Still used in Core: typo3/template.php::wrapClickMenuOnIcon()
function showClickmenu(table, uid, listFr, enDisItems, backPath, addParams) {
	Clickmenu.show(table, uid, listFr, enDisItems, backPath, addParams);
}

// Still used in Core: typo3/alt_clickmenu.php::linkItem()
function showClickmenu_raw(url) {
	var parts = url.split('?');
	if (parts.length === 2) {
		Clickmenu.clickURL = parts[0];
		Clickmenu.callURL(parts[1]);
	} else {
		Clickmenu.callURL(url);
	}
}
function showClickmenu_noajax(url) {
	Clickmenu.ajax = false; showClickmenu_raw(url);
}
function setLayerObj(tableData, cmLevel) {
	Clickmenu.populateData(tableData, cmLevel);
}
function hideEmpty() {
	Clickmenu.hideAll();
	return false;
}
function hideSpecific(level) {
	if (level === 0 || level === 1) {
		Clickmenu.hide('contentMenu'+level);
	}
} 
function showHideSelectorBoxes(action) {
	toggleSelectorBoxes(action);
}
// ## END ##
