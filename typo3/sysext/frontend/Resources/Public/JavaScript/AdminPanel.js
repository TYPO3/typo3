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
};

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
		};
		this.previousMouseMoveHandler = this.dragElement.onmousemove;
		this.dragElement.onmousemove = function() {
			_this.drag.apply(_this, arguments);
		};
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
		headerElement.onmousedown = function(event) {
			_this.dragStart.apply(_this, arguments);
			event.preventDefault();
			return false;
		}
	},

	setMouseOffsets: function(event) {
		this.mouseOffset.x = this.boxElement.offsetLeft - event.clientX;
		this.mouseOffset.y = this.boxElement.offsetTop - event.clientY;
	}

};
