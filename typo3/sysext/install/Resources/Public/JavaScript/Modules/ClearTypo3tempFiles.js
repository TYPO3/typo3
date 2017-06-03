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
 * Module: TYPO3/CMS/Install/ClearTypo3tempFiles
 */
define(['jquery', 'TYPO3/CMS/Install/FlashMessage', 'TYPO3/CMS/Install/ProgressBar', 'TYPO3/CMS/Install/InfoBox', 'TYPO3/CMS/Install/Severity'], function($, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorDeleteToken: '#t3js-clearTypo3temp-delete-token',
		selectorDeleteTrigger: '.t3js-clearTypo3temp-delete',
		selectorOutputContainer: '.t3js-clearTypo3temp-output',

		initialize: function() {
			var self = this;
			$(document).on('click', this.selectorDeleteTrigger, function(e) {
				var folder = $(e.target).data('folder');
				e.preventDefault();
				self.delete(folder);
			});
		},

		delete: function(folder) {
			var url = location.href + '&install[controller]=ajax';
			var postData = {
				'install': {
					'action': 'clearTypo3tempFiles',
					'token': $(this.selectorDeleteToken).text(),
					'folder': folder
				}
			};
			var $container = $('.t3js-clearTypo3temp-container-' + folder);
			var message = ProgressBar.render(Severity.loading, '', '');
			var $outputContainer = $container.find(this.selectorOutputContainer);
			$outputContainer.empty().html(message);
			$.ajax({
				method: 'POST',
				data: postData,
				url: url,
				cache: false,
				success: function (data) {
					$outputContainer.empty();
					if (data.success === true && Array.isArray(data.status)) {
						data.status.forEach(function (element) {
							var message = InfoBox.render(element.severity, element.title, element.message);
							$outputContainer.html(message);
						});
					}
				},
				error: function () {
					var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
					$outputContainer.empty().html(message);
				}
			});
		}
	};
});
