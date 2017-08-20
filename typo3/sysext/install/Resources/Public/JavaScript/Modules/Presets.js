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
 * Module: TYPO3/CMS/Install/Presets
 */
define(['jquery', 'TYPO3/CMS/Install/FlashMessage', 'TYPO3/CMS/Install/ProgressBar', 'TYPO3/CMS/Install/InfoBox', 'TYPO3/CMS/Install/Severity'], function($, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorActivateToken: '#t3js-presets-activate-token',
		selectorActivateTrigger: '.t3js-presets-activate',
		selectorOutputContainer: '.t3js-presets-output',

		initialize: function() {
			var self = this;

			$(document).on('click', this.selectorActivateTrigger, function(e) {
				e.preventDefault();
				self.activate();
			});

			// Automatically select the custom preset if a value in one of its input fields is changed
			$('.t3js-custom-preset').on('input', function() {
				$('#' + $(this).data('radio')).prop('checked', true);
			});
		},

		activate: function() {
			var url = location.href + '&install[controller]=ajax';
			var postData = {
				'install[action]': 'presetActivate',
				'install[token]': $(this.selectorActivateToken).text()
			};
			$('.gridder-show .t3js-presets-formField').each(function(i, element) {
				var $element = $(element);
				if ($element.attr('type') === 'radio') {
					if (element.checked) {
						postData[$element.attr('name')] = $element.val();
					}
				} else {
					postData[$element.attr('name')] = $element.val();
				}
			});
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
							$outputContainer.append(message);
						});
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
