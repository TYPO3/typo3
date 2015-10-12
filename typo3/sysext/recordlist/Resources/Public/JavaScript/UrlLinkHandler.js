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
 * URL link interaction
 */
define(['jquery', 'TYPO3/CMS/Recordlist/LinkBrowser'], function($, LinkBrowser) {
	"use strict";

	var UrlLinkHandler = {};

	UrlLinkHandler.link = function(event) {
		event.preventDefault();

		var value = $(this).find('[name="lurl"]').val();
		if (value === "http://") {
			return;
		}

		if (value.substr(0, 7) === "http://") {
			value = value.substr(7);
		}

		LinkBrowser.updateValueInMainForm(value);

		close();
	};

	$(function() {
		$('#lurlform').on('submit', UrlLinkHandler.link);
	});

	return UrlLinkHandler;
});
