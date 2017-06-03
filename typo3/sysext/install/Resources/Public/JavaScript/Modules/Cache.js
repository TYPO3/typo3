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
 * Module: TYPO3/CMS/Install/Cache
 */
define(['jquery', 'TYPO3/CMS/Install/FlashMessage', 'TYPO3/CMS/Install/ProgressBar', 'TYPO3/CMS/Install/InfoBox', 'TYPO3/CMS/Install/Severity'], function($, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorClearTrigger: '.t3js-clearAllCache-clear',
		selectorOutputContainer: '.t3js-clearAllCache-output',

		initialize: function() {
			var self = this;
			$(document).on('click', this.selectorClearTrigger, function(e) {
				e.preventDefault();
				self.clear();
			});
		},

		clear: function() {
			var url = location.href + '&install[controller]=ajax&install[action]=clearAllCache';
			if (location.hash) {
				url = url.replace(location.hash, "");
			}
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, '', '');
			$outputContainer.empty().html(message);
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					if (data.success === true && Array.isArray(data.status)) {
						if (data.status.length > 0) {
							$outputContainer.empty();
							data.status.forEach((function (element) {
								var message = InfoBox.render(element.severity, element.title, element.message);
								$outputContainer.append(message);
							}));
						}
					}
				},
				error: function() {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					$outputContainer.empty().html(message);
				}
			});
		}
	};
});
