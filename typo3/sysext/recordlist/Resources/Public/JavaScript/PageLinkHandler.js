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
 * Page link interaction
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
	"use strict";

	var PageLinkHandler = {
		currentLink: ''
	};

	PageLinkHandler.linkPage = function(event) {
		event.preventDefault();

		var id = $(this).data('id');
		var anchor = $(this).data('anchor');
		LinkBrowser.updateValueInMainForm(id + (anchor ? anchor : ""));

		close();
	};

	PageLinkHandler.linkCurrent = function(event) {
		event.preventDefault();

		LinkBrowser.updateValueInMainForm(PageLinkHandler.currentLink);

		close();
	};

	$(function() {
		PageLinkHandler.currentLink = $('body').data('currentLink');

		$('a.t3-js-pageLink').on('click', PageLinkHandler.linkPage);
		$('input.t3-js-linkCurrent').on('click', PageLinkHandler.linkCurrent);
	});

	return PageLinkHandler;
});
