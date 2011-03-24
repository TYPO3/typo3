/***************************************************************
 * Admin Panel drag and drop
 *
 * Copyright notice
 *
 * (c) 2010-2011 Dmitry Dulepov <dmitry@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * @author  Dmitry Dulepov  <dmitry@typo3.org>
 */

var TYPO3AdminPanel = function() {
	this.boxElement = null;
	this.dragging = false;
	this.dragElement = document;
	this.previousMouseUpHandler = null;
	this.previousMouseMoveHandler = null;
	this.mouseOffset = {
		x: 0,
		y: 0
	};
}

TYPO3AdminPanel.prototype = {

	init: function(headerElementId, boxElementId) {
		this.boxElement = document.getElementById(boxElementId);
		this.setInitialPosition();
		this.setMouseDownHandler(headerElementId);
	},

	dragStart: function(event) {
		if (!this.dragging) {
			if (!event) {
				event = window.event;
			}
			this.dragging = true;
			this.setMouseOffsets(event);
			this.setDragHandlers();
		}
	},

	dragEnd: function() {
		if (this.dragging) {
			this.dragging = false;
			this.dragElement.onmouseup = this.previousMouseUpHandler;
			this.dragElement.onmousemove = this.previousMouseMoveHandler;
			this.setCookie("admPanelPosX", this.boxElement.style.left);
			this.setCookie("admPanelPosY", this.boxElement.style.top);
		}
	},

	drag: function(event) {
		if (this.dragging) {
			if (!event) {
				event = window.event;
			}
			this.boxElement.style.left = (event.clientX + this.mouseOffset.x) + "px";
			this.boxElement.style.top = (event.clientY + this.mouseOffset.y) + "px";
		}
	},

	getCookie: function(name) {
		var dc = document.cookie;
		var prefix = name + "=";
		var begin = dc.indexOf("; " + prefix);

		if (begin == -1) {
			begin = dc.indexOf(prefix);
			if (begin != 0) {
				return null;
			}
		} else {
			begin += 2;
		}

		var end = dc.indexOf(";", begin);
		if (end == -1) {
			end = dc.length;
		}

		return unescape(dc.substring(begin + prefix.length, end));
	},

	setCookie: function(name, value) {
		document.cookie = name + "=" + escape(value);
	},

	setDragHandlers: function() {
		var _this = this;

		this.previousMouseUpHandler = this.dragElement.onmouseup;
		this.dragElement.onmouseup = function() {
			_this.dragEnd.apply(_this, arguments);
		}
		this.previousMouseMoveHandler = this.dragElement.onmousemove;
		this.dragElement.onmousemove = function() {
			_this.drag.apply(_this, arguments);
		}
	},

	setInitialPosition: function() {
		this.boxElement.style.position = "absolute";

		var pos = this.getCookie("admPanelPosX");
		if (pos) {
			this.boxElement.style.left =  pos;
		}
		pos = this.getCookie("admPanelPosY");
		if (pos) {
			this.boxElement.style.top = pos;
		}
	},

	setMouseDownHandler: function(headerElementId) {
		var _this = this, headerElement = document.getElementById(headerElementId);
		headerElement.onmousedown = function() {
			_this.dragStart.apply(_this, arguments);
		}
	},

	setMouseOffsets: function(event) {
		this.mouseOffset.x = this.boxElement.offsetLeft - event.clientX;
		this.mouseOffset.y = this.boxElement.offsetTop - event.clientY;
	}

};
