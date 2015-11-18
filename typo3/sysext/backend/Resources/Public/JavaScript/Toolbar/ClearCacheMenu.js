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
 * Module: TYPO3/CMS/Backend/Toolbar/ClearCacheMenu
 * main functionality for clearing caches via the top bar
 * reloading the clear cache icon
 */
define(['jquery', 'TYPO3/CMS/Backend/Icons'], function($, Icons) {
	'use strict';

	/**
	 *
	 * @type {{options: {containerSelector: string, menuItemSelector: string, toolbarIconSelector: string}}}
	 * @exports TYPO3/CMS/Backend/Toolbar/ClearCacheMenu
	 */
	var ClearCacheMenu = {
		options: {
			containerSelector: '#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem',
			menuItemSelector: '.dropdown-menu a',
			toolbarIconSelector: '.dropdown-toggle span.icon'
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
	 * to a spinner. Restores the original topbar icon when the request completed.
	 *
	 * @param {String} ajaxUrl the URL to load
	 */
	ClearCacheMenu.clearCache = function(ajaxUrl) {
		// Close clear cache menu
		$(ClearCacheMenu.options.containerSelector).removeClass('open');

		var $toolbarItemIcon = $(ClearCacheMenu.options.toolbarIconSelector, ClearCacheMenu.options.containerSelector),
			$existingIcon = $toolbarItemIcon.clone();

		Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(spinner) {
			$toolbarItemIcon.replaceWith(spinner);
		});

		$.ajax({
			url: ajaxUrl,
			type: 'post',
			cache: false,
			complete: function() {
				$(ClearCacheMenu.options.toolbarIconSelector, ClearCacheMenu.options.containerSelector).replaceWith($existingIcon);
			}
		});
	};

	$(ClearCacheMenu.initializeEvents);

	return ClearCacheMenu;
});
