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
define([
	'jquery',
	'TYPO3/CMS/Install/Router',
	'TYPO3/CMS/Install/FlashMessage',
	'TYPO3/CMS/Install/ProgressBar',
	'TYPO3/CMS/Install/InfoBox',
	'TYPO3/CMS/Install/Severity',
	'TYPO3/CMS/Install/PasswordStrength'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, PasswordStrength) {
	'use strict';

	return {
		selectorChangeToken: '#t3js-changeInstallToolPassword-token',
		selectorChangeTrigger: '.t3js-changeInstallToolPassword-change',
		selectorOutputContainer: '.t3js-changeInstallToolPassword-output',

		initialize: function() {
			var self = this;
			$(document).on('click', this.selectorChangeTrigger, function(e) {
				e.preventDefault();
				self.change();
			});
			$(document).on('keyup', '.t3-install-form-password-strength', function() {
				PasswordStrength.initialize('.t3-install-form-password-strength');
			});
		},

		change: function() {
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().html(message);
			$.ajax({
				url: Router.getUrl(),
				method: 'POST',
				data: {
					'install': {
						'action': 'changeInstallToolPassword',
						'token': $(this.selectorChangeToken).text(),
						'password': $('.t3js-changeInstallToolPassword-password').val(),
						'passwordCheck': $('.t3js-changeInstallToolPassword-password-check').val()
					}
				},
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
