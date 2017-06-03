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
 * Module: TYPO3/CMS/Install/CreateAdmin
 */
define(['jquery', 'TYPO3/CMS/Install/FlashMessage', 'TYPO3/CMS/Install/ProgressBar', 'TYPO3/CMS/Install/InfoBox', 'TYPO3/CMS/Install/Severity'], function($, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorCreateToken: '#t3js-createAdmin-token',
		selectorCreateTrigger: '.t3js-createAdmin-create',
		selectorOutputContainer: '.t3js-createAdmin-output',

		initialize: function() {
			var self = this;
			$(document).on('click', this.selectorCreateTrigger, function(e) {
				e.preventDefault();
				self.create();
			});
		},

		create: function() {
			var token = $(this.selectorCreateToken).text();
			var url = location.href + '&install[controller]=ajax';
			var postData = {
				'install': {
					'action': 'createAdmin',
					'token': token,
					'userName': $('.t3js-createAdmin-user').val(),
					'userPassword': $('.t3js-createAdmin-password').val(),
					'userPasswordCheck': $('.t3js-createAdmin-password-check').val()
				}
			};
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().html(message);
			$.ajax({
				method: 'POST',
				data: postData,
				url: url,
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					if (data.success === true && Array.isArray(data.status)) {
						data.status.forEach(function(element) {
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
