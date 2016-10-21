/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: TYPO3/CMS/Backend/ClickMenu
 * Javascript container used to load the clickmenu via AJAX
 * to render the result in a layer next to the mouse cursor
 */
define(['jquery'], function($) {

	/**
	 *
	 * @type {{mousePos: {X: null, Y: null}, delayClickMenuHide: boolean}}
	 * @exports TYPO3/CMS/Backend/ClickMenu
	 */
	var ClickMenu = {
		mousePos: {
			X: null,
			Y: null
		},
		delayClickMenuHide: false
	};

	/**
	 * Initialize events
	 */
	ClickMenu.initializeEvents = function() {
		$(document).on('click contextmenu', '.t3-js-clickmenutrigger', function(event) {
			// if there is an other "inline" onclick setting, clickmenu is not triggered
			// usually this is the case for the foldertree
			if ($(this).prop('onclick') && event.type === 'click') {
				return;
			}
			event.preventDefault();
			ClickMenu.show(
				$(this).data('table'),
				$(this).data('uid'),
				$(this).data('listframe'),
				$(this).data('iteminfo'),
				$(this).data('parameters')
			);
		});

		// register mouse movement inside the document
		$(document).on('mousemove', ClickMenu.storeMousePositionEvent);
	};

	/**
	 * Main function, called from most clickmenu links
	 *
	 * @param {String} table Table from where info should be fetched
	 * @param {(String|Number)} uid The UID of the item
	 * @param {String} listFr list Frame?
	 * @param {String} enDisItems Items to disable / enable
	 * @param {String} addParams Additional params
	 * @return void
	 */
	ClickMenu.show = function(table, uid, listFr, enDisItems, addParams) {
		var parameters = '';

		if (typeof table !== 'undefined') {
			parameters += 'table=' + encodeURIComponent(table);
		}
		if (typeof uid !== 'undefined') {
			parameters += (parameters.length > 0 ? '&' : '') + 'uid=' + uid;
		}
		if (typeof listFr !== 'undefined') {
			parameters += (parameters.length > 0 ? '&' : '') + 'listFr=' + listFr;
		}
		if (typeof enDisItems !== 'undefined') {
			parameters += (parameters.length > 0 ? '&' : '') + 'enDisItems=' + enDisItems;
		}
		if (typeof addParams !== 'undefined') {
			parameters += (parameters.length > 0 ? '&' : '') + 'addParams=' + addParams;
		}
		this.fetch(parameters);
	};

	/**
	 * Make the AJAX request
	 *
	 * @param {array} parameters Parameters sent to the server
	 * @return void
	 */
	ClickMenu.fetch = function(parameters) {
		var url = TYPO3.settings.ajaxUrls['contextmenu'];
		if (parameters) {
			url += ((url.indexOf('?') == -1) ? '?' : '&') + parameters;
		}
		$.ajax(url).done(function(response) {
			if (!response.getElementsByTagName('data')[0]) {
				var res = parameters.match(/&reloadListFrame=(0|1|2)(&|$)/);
				var reloadListFrame = res !== null && parseInt(res[1], 0);
				if (reloadListFrame) {
					var doc = reloadListFrame != 2 ? top.content.list_frame : top.content;
					doc.location.reload(true);
				}
				return;
			}
			var menu = response.getElementsByTagName('data')[0].getElementsByTagName('clickmenu')[0];
			var data = menu.getElementsByTagName('htmltable')[0].firstChild.data;
			var level = menu.getElementsByTagName('cmlevel')[0].firstChild.data;
			ClickMenu.populateData(data, level);
		});
	};

	/**
	 * fills the clickmenu with content and displays it correctly
	 * depending on the mouse position
	 *
	 * @param {String} data The data that will be put in the menu
	 * @param {Number} level The depth of the clickmenu
	 */
	ClickMenu.populateData = function(data, level) {
		this.initializeClickMenuContainer();

		level = parseInt(level, 10) || 0;
		var $obj = $('#contentMenu' + level);

		if ($obj.length && (level === 0 || $('#contentMenu' + (level-1)).is(':visible'))) {
			$obj.html(data);
			var x = this.mousePos.X;
			var y = this.mousePos.Y;
			var dimsWindow = {
				width: $(document).width()-20, // saving margin for scrollbars
				height: $(document).height()
			};

			// dimensions for the clickmenu
			var dims = {
				width: $obj.width(),
				height: $obj.height()
			};

			var relative = {
				X: this.mousePos.X - $(document).scrollLeft(),
				Y: this.mousePos.Y - $(document).scrollTop()
			};

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
				} else if ((dimsWindow.width - dims.width - relative.X) < $(document).scrollLeft()) {
					x = $(document).scrollLeft();
				} else {
					x += (dimsWindow.width - dims.width - relative.X);
				}
			}

			$obj.css({left: x + 'px', top: y + 'px'}).show();
		}
	};

	/**
	 * event handler function that saves the
	 * actual position of the mouse
	 * in the Clickmenu object
	 *
	 * @param {Event} event The event object
	 */
	ClickMenu.storeMousePositionEvent = function(event) {
		ClickMenu.mousePos.X = event.pageX;
		ClickMenu.mousePos.Y = event.pageY;
		ClickMenu.mouseOutFromMenu('#contentMenu0');
		ClickMenu.mouseOutFromMenu('#contentMenu1');
	};

	/**
	 * hides a visible menu if the mouse has moved outside
	 * of the object
	 *
	 * @param {Object} obj The object to hide
	 */
	ClickMenu.mouseOutFromMenu = function(obj) {
		var $element = $(obj);

		if ($element.length > 0 && $element.is(':visible') && !this.within($element, this.mousePos.X, this.mousePos.Y)) {
			this.hide($element);
		} else if ($element.length > 0 && $element.is(':visible')) {
			this.delayClickMenuHide = true;
		}
	};

	/**
	 *
	 * @param {Object} $element
	 * @param {Number} x
	 * @param {Number} y
	 * @returns {Boolean}
	 */
	ClickMenu.within = function($element, x, y) {
		var offset = $element.offset();
	    return (y >= offset.top &&
				y <  offset.top + $element.height() &&
				x >= offset.left &&
				x <  offset.left + $element.width());
	};

	/**
	 * hides a clickmenu
	 *
	 * @param {Object} obj The clickmenu object to hide
	 */
	ClickMenu.hide = function(obj) {
		this.delayClickMenuHide = false;
		window.setTimeout(function() {
			if (!ClickMenu.delayClickMenuHide) {
				$(obj).hide();
			}
		}, 500);
	};

	/**
	 * hides all clickmenus
	 */
	ClickMenu.hideAll = function() {
		this.hide('#contentMenu0');
		this.hide('#contentMenu1');
	};

	/**
	 * manipulates the DOM to add the divs needed for clickmenu at the bottom of the <body>-tag
	 */
	ClickMenu.initializeClickMenuContainer = function() {
		if ($('#contentMenu0').length === 0) {
			var code = '<div id="contentMenu0" style="display: block;"></div><div id="contentMenu1" style="display: block;"></div>';
			$('body').append(code);
		}
	};

	ClickMenu.initializeEvents();

	// make it globally available
	TYPO3.ClickMenu = ClickMenu;
	return ClickMenu;
});


/**
 * available calls to the old API
 */
Clickmenu = {
	show: function(table, uid, listFr, enDisItems, addParams) {
		if (console !== undefined) {
			console.log('Clickmenu.show is deprecated and will be removed with CMS 8, please use TYPO3.ClickMenu.');
		}
		TYPO3.ClickMenu.show(table, uid, listFr, enDisItems, addParams);
	},
	populateData: function(data, level) {
		if (console !== undefined) {
			console.log('Clickmenu.popuplateData is deprecated and will be removed with CMS 8, please use TYPO3.ClickMenu.');
		}
		TYPO3.ClickMenu.populateData(data, level);
	}
};

/**
 * @param url
 * @deprecated since 4.2, Used in Core: \TYPO3\CMS\Backend\ClickMenu\ClickMenu::linkItem()
 */
function showClickmenu_raw(url) {
	if (console !== undefined) {
		console.log('showClickmenu_raw is deprecated and will be removed with CMS 8, please use TYPO3.ClickMenu.');
	}
	var parts = url.split('?');
	if (parts.length === 2) {
		TYPO3.ClickMenu.fetch(parts[1]);
	} else {
		TYPO3.ClickMenu.fetch(url);
	}
}
