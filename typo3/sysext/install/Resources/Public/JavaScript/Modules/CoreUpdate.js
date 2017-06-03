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
 * Module: TYPO3/CMS/Install/CoreUpdate
 */
define(['jquery', 'TYPO3/CMS/Install/FlashMessage', 'TYPO3/CMS/Install/Severity'], function ($, FlashMessage, Severity) {
	'use strict';

	return {
		/**
		 * The action queue defines what actions are called in which order
		 */
		actionQueue: {
			coreUpdateUpdateVersionMatrix: {
				loadingMessage: 'Fetching list of released versions from typo3.org',
				finishMessage: 'Fetched list of released versions',
				nextActionName: 'coreUpdateIsUpdateAvailable'
			},
			coreUpdateIsUpdateAvailable: {
				loadingMessage: 'Checking for possible regular or security update',
				finishMessage: undefined,
				nextActionName: undefined
			},
			coreUpdateCheckPreConditions: {
				loadingMessage: 'Checking if update is possible',
				finishMessage: 'System can be updated',
				nextActionName: 'coreUpdateDownload'
			},
			coreUpdateDownload: {
				loadingMessage: 'Downloading new core',
				finishMessage: undefined,
				nextActionName: 'coreUpdateVerifyChecksum'
			},
			coreUpdateVerifyChecksum: {
				loadingMessage: 'Verifying checksum of downloaded core',
				finishMessage: undefined,
				nextActionName: 'coreUpdateUnpack'
			},
			coreUpdateUnpack: {
				loadingMessage: 'Unpacking core',
				finishMessage: undefined,
				nextActionName: 'coreUpdateMove'
			},
			coreUpdateMove: {
				loadingMessage: 'Moving core',
				finishMessage: undefined,
				nextActionName: 'clearAllCache'
			},
			clearAllCache: {
				loadingMessage: 'Clearing caches',
				finishMessage: 'Caches cleared',
				nextActionName: 'coreUpdateActivate'
			},
			coreUpdateActivate: {
				loadingMessage: 'Activating core',
				finishMessage: 'Core updated - please reload your browser',
				nextActionName: undefined
			}
		},

		selectorOutput: '.t3js-coreUpdate-output',
		selectorTemplate: '.t3js-coreUpdate-buttonTemplate',

		/**
		 * Clone of a DOM object acts as button template
		 */
		buttonTemplate: null,

		/**
		 * Fetching the templates out of the DOM
		 */
		initialize: function () {
			var self = this;
			var buttonTemplateSection = $(self.selectorTemplate);
			this.buttonTemplate = buttonTemplateSection.children().clone();

			$(document).on('click', '.t3js-coreUpdate-init', function (e) {
				e.preventDefault();
				var action = $(e.target).data('action');
				$(document).find(self.selectorOutput).empty();
				self[action]();
			});
		},

		/**
		 * Public method checkForUpdate
		 */
		checkForUpdate: function () {
			this.callAction('coreUpdateUpdateVersionMatrix');
		},

		/**
		 * Public method updateDevelopment
		 */
		updateDevelopment: function () {
			this.update('development');
		},

		updateRegular: function () {
			this.update('regular');
		},

		/**
		 * Execute core update.
		 *
		 * @param type Either 'development' or 'regular'
		 */
		update: function (type) {
			if (type !== "development") {
				type = 'regular';
			}
			this.callAction('coreUpdateCheckPreConditions', type);
		},

		/**
		 * Generic method to call actions from the queue
		 *
		 * @param actionName Name of the action to be called
		 * @param type Update type (optional)
		 */
		callAction: function (actionName, type) {
			var self = this;
			var data = {
				install: {
					controller: 'ajax',
					action: actionName
				}
			};
			if (type !== undefined) {
				data.install["type"] = type;
			}
			this.addLoadingMessage(this.actionQueue[actionName].loadingMessage);
			$.ajax({
				url: location.href,
				data: data,
				cache: false,
				success: function (result) {
					var canContinue = self.handleResult(result, self.actionQueue[actionName].finishMessage);
					if (canContinue === true && (self.actionQueue[actionName].nextActionName !== undefined)) {
						self.callAction(self.actionQueue[actionName].nextActionName, type);
					}
				},
				error: function (result) {
					self.handleResult(result);
				}
			});
		},

		/**
		 * Handle ajax result of core update step.
		 *
		 * @param data
		 * @param successMessage Optional success message
		 */
		handleResult: function (data, successMessage) {
			var canContinue = false;
			this.removeLoadingMessage();
			if (data.success === true) {
				canContinue = true;
				if (data.status && typeof(data.status) === 'object') {
					this.showStatusMessages(data.status);
				}
				if (data.action && typeof(data.action) === 'object') {
					this.showActionButton(data.action);
				}
				if (successMessage) {
					this.addMessage(Severity.ok, successMessage);
				}
			}
			return canContinue;
		},

		/**
		 * Add a loading message with some text.
		 *
		 * @param messageTitle
		 */
		addLoadingMessage: function (messageTitle) {
			var domMessage = FlashMessage.render(Severity.loading, messageTitle);
			$(this.selectorOutput).append(domMessage);
		},

		/**
		 * Remove an enabled loading message
		 */
		removeLoadingMessage: function () {
			$(this.selectorOutput).find('.alert-loading').remove();
		},

		/**
		 * Show a list of status messages
		 *
		 * @param messages
		 */
		showStatusMessages: function (messages) {
			var self = this;
			$.each(messages, function (index, element) {
				var title = false;
				var message = false;
				var severity = element.severity;
				if (element.title) {
					title = element.title;
				}
				if (element.message) {
					message = element.message;
				}
				self.addMessage(severity, title, message);
			});
		},

		/**
		 * Show an action button
		 *
		 * @param button
		 */
		showActionButton: function (button) {
			var title = false;
			var action = false;
			if (button.title) {
				title = button.title;
			}
			if (button.action) {
				action = button.action;
			}
			var domButton = this.buttonTemplate;
			if (action) {
				domButton.find('button').data('action', action);
			}
			if (title) {
				domButton.find('button').html(title);
			}
			$(this.selectorOutput).append(domButton);
		},

		/**
		 * Show a status message
		 *
		 * @param severity
		 * @param title
		 * @param message
		 */
		addMessage: function (severity, title, message) {
			var domMessage = FlashMessage.render(severity, title, message);
			$(this.selectorOutput).append(domMessage);
		}
	};
});
