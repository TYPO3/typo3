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
 * File link interaction
 */
define('TYPO3/CMS/Recordlist/FileLinkHandler', ['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser', 'TYPO3/CMS/Backend/LegacyTree'], function($, LinkBrowser, Tree) {
	"use strict";

	var FileLinkHandler = {
		currentLink: ''
	};

	FileLinkHandler.linkFile = function(event) {
		event.preventDefault();

		LinkBrowser.updateValueInMainForm($(this).data('file'));

		close();
	};

	FileLinkHandler.linkCurrent = function(event) {
		event.preventDefault();

		LinkBrowser.updateValueInMainForm(FileLinkHandler.currentLink);

		close();

	};

	Tree.ajaxID = 'sc_alt_file_navframe_expandtoggle';

	$(function() {
		FileLinkHandler.currentLink = $('body').data('currentLink');

		$('a.t3-js-fileLink').on('click', FileLinkHandler.linkFile);
		$('input.t3-js-linkCurrent').on('click', FileLinkHandler.linkCurrent);
	});

	return FileLinkHandler;
});
