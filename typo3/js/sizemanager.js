/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Ingo Renner <ingo@typo3.org>
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


var SizeManager = Class.create({

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		Event.observe(window, 'resize', this.resizeBackend);
		Event.observe(window, 'load', this.resizeBackend);
	},

	/**
	 * resizes the divs in the TYPO3 backend to fit a resized window
	 */
	resizeBackend: function() {
		var viewportHeight  = document.viewport.getHeight();
		var topHeight       = $('typo3-top-container').getHeight();
		var containerHeight = viewportHeight - topHeight;

		$('typo3-main-container').setStyle({height: containerHeight+'px'});

		$('typo3-side-menu').setStyle({height: containerHeight+'px'});

		$('typo3-content').setStyle({height: containerHeight+'px'});
		$('content').setStyle({height: containerHeight+'px'});
	}

});

var TYPO3BackendSizeManager = new SizeManager();


