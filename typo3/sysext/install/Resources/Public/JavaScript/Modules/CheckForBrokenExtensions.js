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
 * Module: TYPO3/CMS/Install/ExtensionChecker
 */
define([
	'jquery',
	'TYPO3/CMS/Install/FlashMessage',
	'TYPO3/CMS/Install/ProgressBar',
	'TYPO3/CMS/Install/InfoBox',
	'TYPO3/CMS/Install/Severity',
	'TYPO3/CMS/Install/Cache'
], function($, FlashMessage, ProgressBar, InfoBox, Severity, Cache) {
	'use strict';

	return {
		selectorMainContainer: '.gridder-show.t3js-checkForBrokenExtensions',
		selectorCheckTrigger: '.t3js-checkForBrokenExtensions-check',
		selectorUninstallTrigger: 't3js-checkForBrokenExtensions-uninstall',
		selectorOutputContainer: '.t3js-checkForBrokenExtensions-output',
		outputContainer: null,

		initialize: function() {
			this.outputContainer = $(this.selectorOutputContainer);

			var self = this;
			$(document).on('click', this.selectorCheckTrigger, function(e) {
				e.preventDefault();
				var message = ProgressBar.render(Severity.loading, 'Loading...', '');
				self.outputContainer.empty().append(message);
				self.checkExtensionsCompatibility(true);
			});

			$(document).on('click', '.' + self.selectorUninstallTrigger, function(e) {
				self.uninstallExtension($(this).data('extension'));
				e.preventDefault();
				return false;
			});
		},

		/**
		 * Call checkExtensionsCompatibility recursive on error
		 * so we can find all incompatible extensions
		 */
		handleCheckExtensionsError: function() {
			this.checkExtensionsCompatibility(false);
		},

		/**
		 * Send an ajax request to uninstall an extension (or multiple extensions)
		 *
		 * @param extension string of extension(s) - may be comma separated
		 */
		uninstallExtension: function(extension) {
			var self = this;
			// @todo: Refactor to a POST request and adapt action
			var url = location.href + '&install[controller]=ajax&install[action]=uninstallExtension' +
				'&install[uninstallExtension][extensions]=' + extension;
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					if (data.success) {
						self.checkExtensionsCompatibility(true);
					} else {
						if (data === 'unauthorized') {
							location.reload();
						}
						// workaround for xdebug returning 200 OK on fatal errors
						if (data.substring(data.length - 2) === 'OK') {
							self.checkExtensionsCompatibility(true);
						} else {
							this.outputContainer.empty();
							self.showMessage(
								Severity.error,
								'Something went wrong. Check failed.',
								'Message: ' + data
							);
						}
					}
				},
				error: function(data) {
					self.handleCheckExtensionsError(data);
				}
			});
		},

		/**
		 * Handles result of extension compatibility check.
		 * Displays uninstall buttons for non-compatible extensions.
		 */
		handleCheckExtensionsSuccess: function() {
			var self = this;
			var $mainContainer = $(this.selectorMainContainer);
			$.ajax({
				url: $mainContainer.find('.t3js-checkForBrokenExtensions-data').data('protocolurl'),
				cache: false,
				success: function(data) {
					if (data) {
						self.outputContainer.empty();
						self.showMessage(
							Severity.error,
							'Incompatible extension found',
							'The following extensions are not compatible. Please uninstall them and try again. '
						);
						var extensions = data.split(',');
						var $unloadButtonGroup = $('<div />', {
							'class': 'btn-group'
						});
						for (var i = 0; i < extensions.length; i++) {
							var extension = extensions[i];
							var unloadButton = $('<button />', {
								text: 'Uninstall ' + $.trim(extension),
								'class': 'btn btn-default ' + self.selectorUninstallTrigger,
								'data-extension': $.trim(extension)
							});
							$unloadButtonGroup.append(unloadButton);
						}
						var unloadAllButton = $('<button />', {
							'class': 'btn btn-default',
							text: 'Uninstall all incompatible extensions: ' + data,
							click: function(e) {
								e.preventDefault();
								self.showMessage(Severity.info, 'Loading...', '');
								self.uninstallExtension(data);
								return false;
							}
						});
						$unloadButtonGroup.append(unloadAllButton);
						self.outputContainer.append($unloadButtonGroup);
						self.outputContainer.append('<hr />');

						$.getJSON(
							$mainContainer.find('.t3js-checkForBrokenExtensions-data').data('errorprotocolurl'),
							function(data) {
								$.each(data, function(i, error) {
									var messageToDisplay = error.message + ' in ' + error.file + ' on line ' + error.line;
									self.showMessage(Severity.warning, error.type, messageToDisplay);
								});
							}
						);
					} else {
						self.outputContainer.empty();
						self.showMessage(Severity.ok, 'All local extensions can be loaded!', '');
					}
				},
				error: function() {
					self.outputContainer.empty();
					self.showMessage(Severity.ok, 'All local extensions can be loaded!', '');
				}
			});
		},

		/**
		 * Checks extension compatibility by trying to load ext_tables and ext_localconf via ajax.
		 *
		 * @param force
		 */
		checkExtensionsCompatibility: function(force) {
			var self = this;
			var url = location.href + '&install[controller]=ajax&install[action]=extensionCompatibilityTester';
			if (force) {
				Cache.clear();
				url += '&install[extensionCompatibilityTester][forceCheck]=1';
			} else {
				url += '&install[extensionCompatibilityTester][forceCheck]=0';
			}
			$.ajax({
				url: url,
				cache: false,
				success: function(data) {
					if (data.success) {
						self.handleCheckExtensionsSuccess();
					} else {
						if (data === 'unauthorized') {
							location.reload();
						}
						// workaround for xdebug returning 200 OK on fatal errors
						if (data.substring(data.length - 2) === 'OK') {
							self.handleCheckExtensionsSuccess();
						} else {
							self.handleCheckExtensionsError();
						}
					}
				},
				error: function() {
					self.handleCheckExtensionsError();
				}
			});
		},

		/**
		 * Show message
		 *
		 * @param {Number} severity
		 * @param {String} title
		 * @param {String} description
		 */
		showMessage: function(severity, title, description) {
			var markup = InfoBox.render(
				severity,
				title,
				description
			);
			this.outputContainer.append(markup);
		}
	};
});
