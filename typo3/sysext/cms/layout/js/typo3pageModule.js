/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Gebert <steffen@steffen-gebert.de>
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
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

Ext.ns('TYPO3', 'TYPO3.Components');

TYPO3.Components.PageModule = {
	/**
	 * Initialization
	 */
	init: function() {
		this.enableHighlighting();
	},

	/**
	 * This method is used to bind the higlighting function "setActive"
	 * to the mouseenter event and the "setInactive" to the mouseleave event.
	 */
	enableHighlighting: function() {
		Ext.select('div.t3-page-ce')
			.on('mouseenter',this.setActive, this)
			.on('mouseleave',this.setInactive, this);
	},

	/**
	 * This method is used to unbind the higlighting function "setActive"
	 * from the mouseenter event and the "setInactive" from the mouseleave event.
	 */
	disableHighlighting: function() {
		Ext.select('div.t3-page-ce')
			.un('mouseenter', this.setActive, this)
			.un('mouseleave', this.setInactive, this);
	},

	/**
	 * This method is used as an event handler when the
	 * user hovers the a content element.
	 */
	setActive: function(event, target) {
		Ext.get(target).findParent('div.t3-page-ce', null, true).addClass('active');
	},

	/**
	 * This method is used as event handler to unset active state of
	 * a content element when the mouse of the user leaves the
	 * content element.
	 */
	setInactive: function(event, target) {
		Ext.get(target).findParent('div.t3-page-ce', null, true).removeClass('active');

	}
}

Ext.onReady(function() {
	TYPO3.Components.PageModule.init();
});