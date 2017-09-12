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
define([
	'jquery',
	'TYPO3/CMS/Install/Router',
	'TYPO3/CMS/Install/FlashMessage',
	'TYPO3/CMS/Install/ProgressBar',
	'TYPO3/CMS/Install/InfoBox',
	'TYPO3/CMS/Install/Severity'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity) {
	'use strict';

	return {
		selectorGetContentToken: '#t3js-presets-getContent-token',
		selectorActivateToken: '#t3js-presets-activate-token',
		selectorGridderOpener: 't3js-presets-open',
		selectorActivateTrigger: '.t3js-presets-activate',
		selectorContentContainer: '.t3js-presets-content',
		selectorOutputContainer: '.t3js-presets-output',
		selectorImageExecutable: '.t3js-presets-image-executable',
		selectorImageExecutableTrigger: '.t3js-presets-image-executable-trigger',

		initialize: function() {
			var self = this;

			// Get current system maintainer list on card open
			$(document).on('cardlayout:card-opened', function(event, $card) {
				if ($card.hasClass(self.selectorGridderOpener)) {
					self.getContent();
				}
			});

			// Load content on click image executable path button
			$(document).on('click', this.selectorImageExecutableTrigger, function(e) {
				e.preventDefault();
				self.getContent();
			});

			// Write out selected preset
			$(document).on('click', this.selectorActivateTrigger, function(e) {
				e.preventDefault();
				self.activate();
			});

			// Automatically select the custom preset if a value in one of its input fields is changed
			$('.t3js-custom-preset').on('input', function() {
				$('#' + $(this).data('radio')).prop('checked', true);
			});
		},

		getContent: function() {
			var self = this;
			var outputContainer = $(this.selectorContentContainer);
			var executablePath = $(self.selectorImageExecutable).val();
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			outputContainer.empty().html(message);
			$.ajax({
				url: Router.getUrl(),
				method: 'POST',
				data: {
					'install': {
						'token': $(this.selectorGetContentToken).text(),
						'action': 'presetsGetContent',
						'values': {
							'Image': {
								'additionalSearchPath': executablePath
							}
						}
					}
				},
				cache: false,
				success: function(data) {
					if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
						outputContainer.empty().append(data.html);
					} else {
						var message = InfoBox.render(Severity.error, 'Something went wrong', '');
						outputContainer.empty().append(message);
					}
				},
				error: function(xhr) {
					Router.handleAjaxError(xhr);
				}
			});
		},

		activate: function() {
			var postData = {};
			$($(this.selectorContentContainer + ' form').serializeArray()).each(function() {
				postData[this.name] = this.value;
			});
			postData['install[action]'] = 'presetsActivate';
			postData['install[token]'] = $(this.selectorActivateToken).text();
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().html(message);
			$.ajax({
				url: Router.getUrl(),
				method: 'POST',
				data: postData,
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					if (data.success === true && Array.isArray(data.status)) {
						data.status.forEach(function(element) {
							var message = InfoBox.render(element.severity, element.title, element.message);
							$outputContainer.append(message);
						});
					} else {
						var message = FlashMessage.render(Severity.error, 'Something went wrong', '');
						$outputContainer.empty().html(message);
					}
				},
				error: function(xhr) {
					Router.handleAjaxError(xhr);
				}
			});
		}
	};
});
