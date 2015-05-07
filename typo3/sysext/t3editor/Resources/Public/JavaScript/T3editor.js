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

define('TYPO3/CMS/T3editor/T3editor', ['jquery'], function ($) {

	var T3editor = {};

	/**
	 * Convert all textareas to enable tab
	 */
	T3editor.convertTextareasEnableTab = function() {
		var $elements = $('.enable-tab');
		if ($elements.length) {
			require(['taboverride'], function(taboverride) {
				taboverride.set($elements);
			});
		}
	};

	/**
	 * Initialize and return the T3editor object
	 */
	return function() {
		$(document).ready(function() {
			T3editor.convertTextareasEnableTab();
		});

		return T3editor;
	}();
});
