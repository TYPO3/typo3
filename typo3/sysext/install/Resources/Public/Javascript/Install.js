/***************************************************************
 *
 *  Various JavaScript functions for the Install Tool
 *
 *  Copyright notice
 *
 *  (c) 2009-2010 Marcus Krause, Helmut Hummel, Lars Houmark
 *  (c) 2013 Wouter Wolters <typo3@wouterwolters.nl>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 backend provided by
 *  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
 *
 *  Released under GNU/GPL (see license file in /typo3/)
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  This copyright notice MUST APPEAR in all copies of this script
 *
 ***************************************************************/

/**
 * Handle core update
 */
var TYPO3 = {};
TYPO3.Install = {};

TYPO3.Install.Status = {
	getFolderStatus: function() {
		var url = location.href + '&install[controller]=ajax&install[action]=folderStatus';
		$.ajax({
			url: url,
			cache: false,
			success: function(data) {
				if (data > 0) {
					$('#t3-install-menu-folderStructure a').append('<span class="t3-install-menu-errorCount">' + data + '</span>');
				}
			}
		});
	},
	getEnvironmentStatus: function() {
		var url = location.href + '&install[controller]=ajax&install[action]=environmentStatus';
		$.ajax({
			url: url,
			cache: false,
			success: function(data) {
				if (data > 0) {
					$('#t3-install-menu-systemEnvironment a').append('<span class="t3-install-menu-errorCount">' + data + '</span>');
				}
			}
		});
	}
};

TYPO3.Install.coreUpdate = {
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
			finishMessage: 'Core download finished',
			nextActionName: 'coreUpdateVerifyChecksum'
		},
		coreUpdateVerifyChecksum: {
			loadingMessage: 'Verifying checksum of downloaded core',
			finishMessage: 'Checksum verified',
			nextActionName: 'coreUpdateUnpack'
		},
		coreUpdateUnpack: {
			loadingMessage: 'Unpacking core',
			finishMessage: 'Unpacking core successful',
			nextActionName: 'coreUpdateMove'
		},
		coreUpdateMove: {
			loadingMessage: 'Moving core',
			finishMessage: 'Moved core to final location',
			nextActionName: 'clearCache'
		},
		clearCache: {
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

	/**
	 * Clone of a DOM object acts as message template
	 */
	messageTemplate: null,

	/**
	 * Clone of a DOM object acts as button template
	 */
	buttonTemplate: null,

	/**
	 * Fetching the templates out of the DOM
	 */
	initialize: function() {
		var messageTemplateSection = $('#messageTemplate');
		var buttonTemplateSection = $('#buttonTemplate');
		this.messageTemplate = messageTemplateSection.children().clone();
		this.buttonTemplate = buttonTemplateSection.children().clone();
		messageTemplateSection.remove();
	},

	/**
	 * Public method checkForUpdate
	 */
	checkForUpdate: function() {
		this.callAction('coreUpdateUpdateVersionMatrix');
	},

	/**
	 * Public method updateDevelopment
	 */
	updateDevelopment: function() {
		this.update('development');
	},

	/**
	 * Public method updateRegular
	 */
	updateRegular: function() {
		this.update('regular');
	},

	/**
	 * Execute core update.
	 *
	 * @param type Either 'development' or 'regular'
	 */
	update: function(type) {
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
	callAction: function(actionName, type) {
		var self = this;
		var arguments = {
			install: {
				controller: 'ajax',
				action: actionName
			}
		};
		if (type !== undefined) {
			arguments.install["type"] = type;
		}
		this.addLoadingMessage(this.actionQueue[actionName].loadingMessage);
		$.ajax({
			url: location.href,
			data: arguments,
			cache: false,
			success: function(result) {
				canContinue = self.handleResult(result, self.actionQueue[actionName].finishMessage);
				if (canContinue === true && (self.actionQueue[actionName].nextActionName !== undefined)) {
					self.callAction(self.actionQueue[actionName].nextActionName, type);
				}
			},
			error: function(result) {
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
	handleResult: function(data, successMessage) {
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
				this.addMessage('ok', successMessage);
			}
		} else {
			// Handle clearcache until it uses the new view object
			if (data === "OK") {
				canContinue = true;
				if (successMessage) {
					this.addMessage('ok', successMessage);
				}
			} else {
				canContinue = false;
				if (data.status && typeof(data.status) === 'object') {
					this.showStatusMessages(data.status);
				} else {
					this.addMessage('error', 'General error');
				}
			}
		}
		return canContinue;
	},

	/**
	 * Add a loading message with some text.
	 *
	 * @param messageTitle
	 */
	addLoadingMessage: function(messageTitle) {
		var domMessage = this.messageTemplate.clone();
		domMessage.find('.message-header strong').html(messageTitle);
		domMessage.addClass('message-loading');
		$('#coreUpdate').append(domMessage);
	},

	/**
	 * Remove an enabled loading message
	 */
	removeLoadingMessage: function() {
		$('#coreUpdate .message-loading').closest('.typo3-message').remove();
	},

	/**
	 * Show a list of status messages
	 *
	 * @param messages
	 */
	showStatusMessages: function(messages) {
		var self = this;
		$.each(messages, function(index, element) {
			var title = false;
			var severity = false;
			var message = false;
			if (element.severity) {
				severity = element.severity;
			}
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
	showActionButton: function(button) {
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
		$('#coreUpdate').append(domButton);
	},

	/**
	 * Show a status message
	 *
	 * @param severity
	 * @param title
	 * @param message
	 */
	addMessage: function(severity, title, message) {
		var domMessage = this.messageTemplate.clone();
		if (severity) {
			domMessage.addClass('message-' + severity);
		}
		if (title) {
			domMessage.find('.message-header strong').html(title);
		}
		if (message) {
			domMessage.find('.message-body').html(message);
		}
		$('#coreUpdate').append(domMessage);
	}
};

$(document).ready(function() {
	// Used in database compare section to select/deselect checkboxes
	$('.checkall').on('click', function() {
		$(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
	});

	// Toggle open/close
	$('.toggleButton').on('click', function() {
		$toggleGroup = $(this).closest('.toggleGroup');
		$toggleGroup.toggleClass('expanded');
		$toggleGroup.find('.toggleData').toggle();
		handleButtonScrolling();
	});

	$('.toggleAll').on('click', function() {
		$toggleAll = $('.toggleGroup');
		if ($toggleAll.not('.expanded').length == 0) {
			// all elements are open, close them
			$toggleAll.removeClass('expanded');
			$toggleAll.find('.toggleData').hide();
		} else {
			$toggleAll.addClass('expanded');
			$toggleAll.find('.toggleData').show();
		}
		handleButtonScrolling();
	});

	// Simple password strength indicator
	$('.t3-install-form-password-strength').on('keyup', function() {
		var value = $(this).val();
		var strongRegex = new RegExp('^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$', 'g');
		var mediumRegex = new RegExp('^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$', 'g');
		var enoughRegex = new RegExp('(?=.{8,}).*', 'g');

		if (value.length == 0) {
			$(this).attr('style', 'background-color:#FBB19B; border:1px solid #DC4C42');
		} else if (false == enoughRegex.test(value)) {
			$(this).attr('style', 'background-color:#FBB19B; border:1px solid #DC4C42');
		} else if (strongRegex.test(value)) {
			$(this).attr('style', 'background-color:#CDEACA; border:1px solid #58B548');
		} else if (mediumRegex.test(value)) {
			$(this).attr('style', 'background-color:#FBFFB3; border:1px solid #C4B70D');
		} else {
			$(this).attr('style', 'background-color:#FBFFB3; border:1px solid #C4B70D');
		}
	});

	// Install step database settings
	$('#t3-install-step-type').change(function() {
		var connectionType = $(this).val(),
			hostField = $('#t3-install-step-host'),
			portField = $('#t3-install-step-port'),
			socketField = $('#t3-install-step-socket');

		if (connectionType === 'socket') {
			hostField.parent().fadeOut();
			hostField.val('localhost');
			portField.parent().fadeOut();
			socketField.parent().fadeIn();
		} else {
			hostField.parent().fadeIn();
			if (hostField.val() === 'localhost') {
				hostField.val('127.0.0.1');
			}
			portField.parent().fadeIn();
			socketField.parent().fadeOut();
		}
	}).trigger('change');

	// Extension compatibility check
	$('.typo3-message', '#checkExtensions').hide();
	$('button', '#checkExtensions').click(function(e) {
		$('button', '#checkExtensions').hide();
		$('.typo3-message', '#checkExtensions').hide();
		$('.message-loading', '#checkExtensions').show();
		checkExtensionsCompatibility(true);
		e.preventDefault();
		return false;
	});

	// Footer scrolling and visibility
	if ($('#fixed-footer-handler').length > 0) {
		$(window).scroll(handleButtonScrolling);
		$('body.backend #typo3-docbody').scroll(handleButtonScrolling);
	}

	// Handle core update
	var $coreUpdateSection = $('#coreUpdate');
	if ($coreUpdateSection) {
		TYPO3.Install.coreUpdate.initialize();
		$coreUpdateSection.on('click', 'button', (function(e) {
			e.preventDefault();
			var action = $(e.target).data('action');
			TYPO3.Install.coreUpdate[action]();
			$(e.target).closest('.t3-install-form-submit').remove();
		}));
	}
	if ($('#t3-install-left').length > 0) {
		TYPO3.Install.Status.getFolderStatus();
		TYPO3.Install.Status.getEnvironmentStatus();
	}
});

function handleButtonScrolling() {
	if (!isScrolledIntoView($('#fixed-footer-handler'))) {
		$('#fixed-footer').addClass('fixed');
	} else {
		$('#fixed-footer').removeClass('fixed');
	}
}
function isScrolledIntoView(elem) {
	var docViewTop = $(window).scrollTop();
	var docViewBottom = docViewTop + $(window).height();
	var elemTop = $(elem).offset().top;
	var elemBottom = elemTop + $(elem).height();

	return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
}

/**
 * Checks extension compatibility by trying to load ext_tables and ext_localconf
 * via ajax.
 *
 * @param force
 */
function checkExtensionsCompatibility(force) {
	var url = location.href + '&install[controller]=ajax&install[action]=extensionCompatibilityTester';
	if (force) {
		clearCache();
		url += '&install[extensionCompatibilityTester][forceCheck]=1';
	} else {
		url += '&install[extensionCompatibilityTester][forceCheck]=0';
	}
	$.ajax({
		url: url,
		cache: false,
		success: function(data) {
			if (data === 'OK') {
				handleCheckExtensionsSuccess();
			} else {
				if(data === 'unauthorized') {
					location.reload();
				}
				// workaround for xdebug returning 200 OK on fatal errors
				if (data.substring(data.length - 2) === 'OK') {
					handleCheckExtensionsSuccess();
				} else {
					handleCheckExtensionsError();
				}
			}
		},
		error: function(data) {
			handleCheckExtensionsError();
		}
	});
}

/**
 * Handles result of extension compatibility check.
 * Displays uninstall buttons for non-compatible extensions.
 */
function handleCheckExtensionsSuccess() {
	$.ajax({
		url: $('#checkExtensions').data('protocolurl'),
		cache: false,
		success: function(data) {
			if (data) {
				$('.message-error .message-body', '#checkExtensions').html(
					'The following extensions are not compatible. Please uninstall them and try again. '
				);
				var extensions = data.split(',');
				var unloadButtonWrapper = $('<fieldset class="t3-install-form-submit"></fieldset>');
				for(var i=0; i<extensions.length; i++) {
					var extension = extensions[i];
					var unloadButton = $('<button />', {
						text: 'Uninstall '+ $.trim(extension),
						"class": $.trim(extension),
						click: function(e) {
							uninstallExtension($(this).attr('class'));
							e.preventDefault();
							return false;
						}
					});
					var fullButton = unloadButtonWrapper.append(unloadButton);
					$('.message-error .message-body', '#checkExtensions').append(fullButton);
				}
				var unloadAllButton = $('<button />', {
					text: 'Uninstall all incompatible extensions: '+ data,
					click: function(e) {
						$('.message-loading', '#checkExtensions').show();
						uninstallExtension(data);
						e.preventDefault();
						return false;
					}
				});
				unloadButtonWrapper.append('<hr />');
				var fullUnloadAllButton = unloadButtonWrapper.append(unloadAllButton);
				$('.message-error .message-body', '#checkExtensions').append(fullUnloadAllButton);

				$('.message-loading', '#checkExtensions').hide();
				$('button', '#checkExtensions').show();
				$('.message-error', '#checkExtensions').show();
			} else {
				$('.typo3-message', '#checkExtensions').hide();
				$('.message-ok', '#checkExtensions').show();
			}
		},
		error: function() {
			$('.typo3-message', '#checkExtensions').hide();
			$('.message-ok', '#checkExtensions').show();
		}
	});
	$.getJSON(
		$('#checkExtensions').data('errorprotocolurl'),
		function(data) {
			$.each(data, function(i, error) {
				var messageToDisplay = error.message + ' in ' + error.file + ' on line ' + error.line;
				$('#checkExtensions .typo3-message.message-error').before($(
					'<div class="typo3-message message-warning"><div class="header-container"><div class="message-header">' +
					'<strong>' + error.type + '</strong></div><div class="message-body">' +
					messageToDisplay + '</div></div></div><p></p>'
				));
			});
		}
	);
}

/**
 * Call checkExtensionsCompatibility recursively on error
 * so we can find all incompatible extensions
 */
function handleCheckExtensionsError() {
	checkExtensionsCompatibility(false);
}

/**
 * Send an ajax request to uninstall an extension (or multiple extensions)
 *
 * @param extension string of extension(s) - may be comma separated
 */
function uninstallExtension(extension) {
	var url = location.href + '&install[controller]=ajax&install[action]=uninstallExtension' +
		'&install[uninstallExtension][extensions]=' + extension;
	$.ajax({
		url: url,
		cache: false,
		success: function(data) {
			if (data === 'OK') {
				checkExtensionsCompatibility(true);
			} else {
				if(data === 'unauthorized') {
					location.reload();
				}
				// workaround for xdebug returning 200 OK on fatal errors
				if (data.substring(data.length - 2) === 'OK') {
					checkExtensionsCompatibility(true);
				} else {
					$('.message-loading', '#checkExtensions').hide();
					$('.message-error .message-body', '#checkExtensions').html(
						'Something went wrong. Check failed.'
					);
				}
			}
		},
		error: function(data) {
			handleCheckExtensionsError();
		}
	});
}

/**
 * Ajax call to clear all caches.
 */
function clearCache() {
	$.ajax({
		url: location.href + '&install[controller]=ajax&install[action]=clearCache',
		cache: false
	});
}