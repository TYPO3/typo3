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
 * Module: TYPO3/CMS/Install/Router
 */
define([
	'jquery',
	'TYPO3/CMS/Install/InfoBox',
	'TYPO3/CMS/Install/Severity',
	'TYPO3/CMS/Install/CardLayout',
	'TYPO3/CMS/Install/ProgressBar'
], function($, InfoBox, Severity, CardLayout, ProgressBar) {
	'use strict';

	return {
		selectorBody: '.t3js-body',
		selectorMainContent: '.t3js-module-body',

		initialize: function() {
			var self = this;

			$(document).on('click', '.t3js-login-lockInstallTool', function(e) {
				e.preventDefault();
				self.logout();
			});
			$(document).on('click', '.t3js-login-login', function(e) {
				e.preventDefault();
				self.login();
			});
			$(document).on('keydown', '#t3-install-form-password', function(e) {
				if (e.keyCode === 13) {
					e.preventDefault();
					$('.t3js-login-login').click();
				}
			});

			this.executeSilentConfigurationUpdate();
		},

		getUrl: function(action, controller) {
			var url = location.href;
			var context = $(this.selectorBody).data('context');
			url = url.replace(location.search, '');
			if (controller === undefined) {
				controller = $(this.selectorBody).data('controller');
			}
			url = url + '?install[controller]=' + controller;
			if (context !== undefined && context !== '') {
				url = url + '&install[context]=' + context;
			}
			if (action !== undefined) {
				url = url + '&install[action]=' + action;
			}
			return url;
		},

		executeSilentConfigurationUpdate: function() {
			var self = this;
			$.ajax({
				url: this.getUrl('executeSilentConfigurationUpdate', 'layout'),
				cache: false,
				success: function(data) {
					if (data.success === true) {
						self.executeSilentLegacyExtConfExtensionConfigurationUpdate();
					} else {
						self.executeSilentConfigurationUpdate();
					}
				},
				error: function(xhr) {
					self.handleAjaxError(xhr);
				}
			});
		},

		/**
		 * Legacy layer to upmerge LocalConfiguration EXT/extConf serialized array keys
		 * to EXTENSIONS array in LocalConfiguration for initial update from v8 to v9.
		 *
		 * @deprecated since TYPO3 v9, will be removed with v10 - re-route executeSilentConfigurationUpdate()
		 * to executeSilentExtensionConfigurationUpdate() on removal of this function.
		 */
		executeSilentLegacyExtConfExtensionConfigurationUpdate: function() {
			var self = this;
			$.ajax({
				url: this.getUrl('executeSilentLegacyExtConfExtensionConfigurationUpdate', 'layout'),
				cache: false,
				success: function(data) {
					if (data.success === true) {
						self.executeSilentExtensionConfigurationSynchronization();
					} else {
						var message = InfoBox.render(Severity.error, 'Something went wrong', '');
						$outputContainer.empty().append(message);
					}
				},
				error: function(xhr) {
					self.handleAjaxError(xhr);
				}
			});
		},

		/**
		 * Extensions which come with new default settings in ext_conf_template.txt extension
		 * configuration files get their new defaults written to LocalConfiguration.
		 */
		executeSilentExtensionConfigurationSynchronization: function() {
			var self = this;
			$.ajax({
				url: this.getUrl('executeSilentExtensionConfigurationSynchronization', 'layout'),
				cache: false,
				success: function(data) {
					if (data.success === true) {
						self.loadMainLayout();
					} else {
						var message = InfoBox.render(Severity.error, 'Something went wrong', '');
						$outputContainer.empty().append(message);
					}
				},
				error: function(xhr) {
					self.handleAjaxError(xhr);
				}
			});
		},

		loadMainLayout: function() {
			var self = this;
			var $outputContainer = $(this.selectorBody);
			$.ajax({
				url: this.getUrl('mainLayout', 'layout'),
				cache: false,
				success: function(data) {
					if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
						$outputContainer.empty().append(data.html);
						// Mark main module as active in standalone
						if ($(self.selectorBody).data('context') !== 'backend') {
							var controller = $outputContainer.data('controller');
							$outputContainer.find('.t3js-mainmodule[data-controller="' + controller + '"]').addClass('active');
						}
						self.loadCards();
					} else {
						var message = InfoBox.render(Severity.error, 'Something went wrong', '');
						$outputContainer.empty().append(message);
					}
				},
				error: function(xhr) {
					self.handleAjaxError(xhr);
				}
			});
		},

		handleAjaxError: function(xhr) {
			var message = '';
			if (xhr.status === 401) {
				// Install tool session expired - depending on context render error message or login
				var context = $(this.selectorBody).data('context');
				if (context === 'backend') {
					message = InfoBox.render(
						Severity.error,
						'The install tool session expired. Please reload the backend and try again.'
					);
					$(this.selectorBody).empty().append(message);
				} else {
					this.checkEnableInstallToolFile();
				}
			} else {
				// @todo Recovery tests should be started here
				var url = this.getUrl(undefined, 'upgrade');
				message = '<div class="t3js-infobox callout callout-sm callout-danger"><div class="callout-body">'
						+ 'Something went wrong. Please use <b><a href="' + url + '">Check for broken'
						+ ' extensions</a></b> to see if a loaded extension breaks this part of the install tool'
						+ ' and unload it.</div></div>';
				$(this.selectorBody).empty().html(message);
			}
		},

		checkEnableInstallToolFile: function() {
			var self = this;
			$.ajax({
				url: this.getUrl('checkEnableInstallToolFile'),
				cache: false,
				success: function(data) {
					if (data.success === true) {
						self.checkLogin();
					} else {
						self.showEnableInstallTool();
					}
				}
			});
		},

		showEnableInstallTool: function() {
			var self = this;
			$.ajax({
				url: this.getUrl('showEnableInstallToolFile'),
				cache: false,
				success: function(data) {
					if (data.success === true) {
						$(self.selectorBody).empty().append(data.html);
					}
				}
			});
		},

		checkLogin: function() {
			var self = this;
			$.ajax({
				url: this.getUrl('checkLogin'),
				cache: false,
				success: function(data) {
					if (data.success === true) {
						self.loadMainLayout();
					} else {
						self.showLogin();
					}
				}
			});
		},

		showLogin: function() {
			var self = this;
			$.ajax({
				url: this.getUrl('showLogin'),
				cache: false,
				success: function(data) {
					if (data.success === true) {
						$(self.selectorBody).empty().append(data.html);
					}
				}
			});
		},

		login: function() {
			var self = this;
			var $outputContainer = $('.t3js-login-output');
			var message = ProgressBar.render(Severity.loading, 'Loading...', '');
			$outputContainer.empty().html(message);
			$.ajax({
				url: self.getUrl(),
				cache: false,
				method: 'POST',
				data: {
					'install': {
						'action': 'login',
						'token': $('#t3js-login-token').text(),
						'password': $('.t3-install-form-input-text').val()
					}
				},
				success: function(data) {
					if (data.success === true) {
						self.loadMainLayout();
					} else {
						data.status.forEach(function(element) {
							var message = InfoBox.render(element.severity, element.title, element.message);
							$outputContainer.empty().html(message);
						});
					}
				}
			});
		},

		logout: function() {
			var self = this;
			$.ajax({
				url: self.getUrl('logout'),
				cache: false,
				success: function(data) {
					if (data.success === true) {
						self.showEnableInstallTool();
					}
				}
			});
		},

		loadCards: function() {
			var self = this;
			var outputContainer = $(this.selectorMainContent);
			$.ajax({
				url: this.getUrl('cards'),
				cache: false,
				success: function(data) {
					if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
						outputContainer.empty().append(data.html);
						CardLayout.initialize();
						// Each card head can have a t3js-require class and a data-require attribute
						// with the name of a requireJS module. Those are loaded here and initialize()
						// is executed if exists.
						$('.t3js-require').each(function() {
							var module = $(this).data('require');
							require([module], function(aModule) {
								if (typeof aModule.initialize !== 'undefined') {
									aModule.initialize();
								}
							});
						});
					} else {
						var message = InfoBox.render(Severity.error, 'Something went wrong', '');
						outputContainer.empty().append(message);
					}
				},
				error: function(xhr) {
					self.handleAjaxError(xhr);
				}
			});
		}
	};
});
