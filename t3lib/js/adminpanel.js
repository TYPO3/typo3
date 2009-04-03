/***************************************************************
 * Admin Panel drag and drop
 *
 * $Id$
 *
 * Copyright notice
 *
 * (c) 2009 Ingo Renner <ingo@typo3.org>
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
 * @author  Ingo Renner  <ingo@typo3.org>
 * @author	Oliver Hader <oliver@typo3.org>
 * @author  Ingmar Schlecht <ingmar@typo3.org>
 * @author  Jonas Dübi <jd@cabag.ch>
 */

var TYPO3AdminPanel = {

	positionRestored: false,
	dragObject: null,
	dragX: 0,
	dragY: 0,
	posX: 0,
	posY: 0,

	savePosition: function(panel) {
		var admPanelPosX = panel.offsetLeft;
		var admPanelPosY = panel.offsetTop;

		TYPO3AdminPanel.setCookie('admPanelPosX', admPanelPosX, '', '/');
		TYPO3AdminPanel.setCookie('admPanelPosY', admPanelPosY, '', '/');
	},

	restorePosition: function() {
		if (TYPO3AdminPanel.positionRestored == false) {

			var admPanelPosX = TYPO3AdminPanel.getCookie('admPanelPosX');
			if (admPanelPosX > 0) {
				document.getElementById('admPanel').style.left = admPanelPosX + 'px';
			}

			var admPanelPosY = TYPO3AdminPanel.getCookie('admPanelPosY');
			if (admPanelPosY > 0) {
				document.getElementById('admPanel').style.top = admPanelPosY + 'px';
			}

			TYPO3AdminPanel.positionRestored = true;
		}
	},

	setCookie: function(name, value, expires, path, domain, secure) {
		document.cookie = name + '=' + escape(value)
			+ (expires ? '; expires=' + expires.toGMTString() : '')
			+ (path ? '; path=' + path : '')
			+ (domain ? '; domain=' + domain : '')
			+ (secure ? '; secure' : '');
	},

	getCookie: function(name) {
		var dc = document.cookie;
		var prefix = name + '=';
		var begin = dc.indexOf('; ' + prefix);

		if (begin == -1) {
			begin = dc.indexOf(prefix);
			if (begin != 0) {
				return null;
			}
		} else {
			begin += 2;
		}

		var end = dc.indexOf(';', begin);
		if (end == -1) {
			end = dc.length;
		}

		return unescape(dc.substring(begin + prefix.length, end));
	},

	dragInit: function() {
		document.onmousemove = TYPO3AdminPanel.drag;
		document.onmouseup = TYPO3AdminPanel.dragStop;
	},

	dragStart: function(element) {
		TYPO3AdminPanel.dragObject = element;
		TYPO3AdminPanel.dragX = TYPO3AdminPanel.posX - TYPO3AdminPanel.dragObject.offsetLeft;
		TYPO3AdminPanel.dragY = TYPO3AdminPanel.posY - TYPO3AdminPanel.dragObject.offsetTop;
	},

	dragStop: function() {
		TYPO3AdminPanel.dragObject = null;
	},

	drag: function(dragEvent) {
		TYPO3AdminPanel.posX = document.all ? window.event.clientX : dragEvent.pageX;
		TYPO3AdminPanel.posY = document.all ? window.event.clientY : dragEvent.pageY;

		if (TYPO3AdminPanel.dragObject != null) {
			TYPO3AdminPanel.dragObject.style.left = (TYPO3AdminPanel.posX - TYPO3AdminPanel.dragX) + 'px';
			TYPO3AdminPanel.dragObject.style.top = (TYPO3AdminPanel.posY - TYPO3AdminPanel.dragY) + 'px';
		}
	}
};
