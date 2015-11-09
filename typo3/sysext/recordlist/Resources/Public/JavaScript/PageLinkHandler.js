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
 * Module: TYPO3/CMS/Recordlist/PageLinkHandler
 * Page link interaction
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
	'use strict';

	/**
	 *
	 * @type {{currentLink: string}}
	 * @exports TYPO3/CMS/Recordlist/PageLinkHandler
	 */
	var PageLinkHandler = {
		currentLink: ''
	};

	/**
	 *
	 * @param {Event} event
	 */
	PageLinkHandler.linkPage = function(event) {
		event.preventDefault();

		var id = $(this).data('id');
		var anchor = $(this).data('anchor');

		LinkBrowser.finalizeFunction('page:' + id + (anchor ? anchor : ''));
	};

	/**
	 *
	 * @param {Event} event
	 */
	PageLinkHandler.linkPageByTextfield = function(event) {
		event.preventDefault();

		var value = $('#luid').val();
		if (!value) {
			return;
		}

		LinkBrowser.finalizeFunction('page:' + value);
	};

	/**
	 *
	 * @param {Event} event
	 */
	PageLinkHandler.linkCurrent = function(event) {
		event.preventDefault();

		LinkBrowser.finalizeFunction('page:' + PageLinkHandler.currentLink);
	};

	$(function() {
		PageLinkHandler.currentLink = $('body').data('currentLink');

		$('a.t3js-pageLink').on('click', PageLinkHandler.linkPage);
		$('input.t3js-linkCurrent').on('click', PageLinkHandler.linkCurrent);
		$('input.t3js-pageLink').on('click', PageLinkHandler.linkPageByTextfield);
	});

	return PageLinkHandler;
});
