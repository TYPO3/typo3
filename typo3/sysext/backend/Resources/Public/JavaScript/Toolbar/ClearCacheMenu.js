/**
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
 * main functionality for clearing caches via the top bar
 * reloading the clear cache icon
 */
define('TYPO3/CMS/Backend/Toolbar/ClearCacheMenu', ['jquery'], function($) {

	var ClearCacheMenu = {
		$spinnerElement: $('<span>', {
			'class': 't3-icon fa fa-circle-o-notch fa-spin'
		}),
		options: {
			containerSelector: '#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem',
			menuItemSelector: '.dropdown-menu a',
			toolbarIconSelector: '.dropdown-toggle span.t3-icon'
		}
	};

	/**
	 * Registers listeners for the icons inside the dropdown to trigger
	 * the clear cache call
	 */
	ClearCacheMenu.initializeEvents = function() {
		$(ClearCacheMenu.options.containerSelector).on('click', ClearCacheMenu.options.menuItemSelector, function(evt) {
			evt.preventDefault();
			var ajaxUrl = $(this).attr('href');
			if (ajaxUrl) {
				ClearCacheMenu.clearCache(ajaxUrl);
			}
		});
	};

	/**
	 * calls TYPO3 to clear a cache, then changes the topbar icon
	 * to a spinner. Once done, restores the original topbar icon
	 *
	 * @param ajaxUrl the URL to load
	 */
	ClearCacheMenu.clearCache = function(ajaxUrl) {
		// Close clear cache menu
		$(ClearCacheMenu.options.containerSelector).removeClass('open');

		var $toolbarItemIcon = $(ClearCacheMenu.options.toolbarIconSelector, ClearCacheMenu.options.containerSelector);

		var $spinnerIcon = ClearCacheMenu.$spinnerElement.clone();
		var $existingIcon = $toolbarItemIcon.replaceWith($spinnerIcon);
		$.ajax({
			url: ajaxUrl,
			type: 'post',
			cache: false,
			success: function() {
				$spinnerIcon.replaceWith($existingIcon);
			}
		});
	};

	/**
	 * initialize and return the ClearCacheMenu object
	 */
	return function() {
		$(document).ready(function() {
			ClearCacheMenu.initializeEvents();
		});

		TYPO3.ClearCacheMenu = ClearCacheMenu;
		return ClearCacheMenu;
	}();
});