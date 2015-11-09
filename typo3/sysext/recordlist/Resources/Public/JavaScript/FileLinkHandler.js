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
 * Module: TYPO3/CMS/Recordlist/FileLinkHandler
 * File link interaction
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser', 'TYPO3/CMS/Backend/LegacyTree'], function($, LinkBrowser, Tree) {
	'use strict';

	/**
	 *
	 * @type {{currentLink: string}}
	 * @exports TYPO3/CMS/Recordlist/FileLinkHandler
	 */
	var FileLinkHandler = {
		currentLink: ''
	};

	/**
	 *
	 * @param {Event} event
	 */
	FileLinkHandler.linkFile = function(event) {
		event.preventDefault();

		LinkBrowser.finalizeFunction($(this).data('file'));
	};

	/**
	 *
	 * @param {Event} event
	 */
	FileLinkHandler.linkCurrent = function(event) {
		event.preventDefault();

		LinkBrowser.finalizeFunction(FileLinkHandler.currentLink);
	};

	Tree.ajaxID = 'sc_alt_file_navframe_expandtoggle';

	$(function() {
		FileLinkHandler.currentLink = $('body').data('currentLink');

		$('a.t3js-fileLink').on('click', FileLinkHandler.linkFile);
		$('input.t3js-linkCurrent').on('click', FileLinkHandler.linkCurrent);
	});

	return FileLinkHandler;
});
