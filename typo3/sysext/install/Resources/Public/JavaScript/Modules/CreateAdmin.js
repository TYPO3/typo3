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
	'TYPO3/CMS/Install/PasswordStrength',
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, PasswordStrength) {
	'use strict';

	return {
		selectorCreateForm: '#t3js-createAdmin-form',
		selectorCreateToken: '#t3js-createAdmin-token',
		selectorCreateTrigger: '.t3js-createAdmin-create',
		selectorOutputContainer: '.t3js-createAdmin-output',

		initialize: function() {
			var self = this;
			$(document).on('submit', this.selectorCreateForm, function(e) {
				e.preventDefault();
				self.create();
			});
			$(document).on('keyup', '.t3-install-form-password-strength', function() {
				PasswordStrength.initialize('.t3-install-form-password-strength');
			});
		},

		create: function() {
			var self = this;
			var $outputContainer = $(this.selectorOutputContainer);
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().html(message);
			$.ajax({
				url: Router.getUrl(),
				method: 'POST',
				data: {
					'install': {
						'action': 'createAdmin',
						'token': $(self.selectorCreateToken).text(),
						'userName': $('.t3js-createAdmin-user').val(),
						'userPassword': $('.t3js-createAdmin-password').val(),
						'userPasswordCheck': $('.t3js-createAdmin-password-check').val(),
						'userSystemMaintainer': ($('.t3js-createAdmin-system-maintainer').is(':checked'))? 1 : 0
					}
				},
				cache: false,
				success: function(data) {
					$outputContainer.empty();
					if (data.success === true && Array.isArray(data.status)) {
						data.status.forEach(function(element) {
							var message = InfoBox.render(element.severity, element.title, element.message);
							$outputContainer.html(message);
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
