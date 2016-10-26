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
 * Various JavaScript functions for the Install Tool
 */

/**
 * Handle core update
 */
var TYPO3 = {};
TYPO3.Install = {};

TYPO3.Install.Cache = {
	/**
	 * Ajax call to clear all caches.
	 */
	clearCache: function() {
		$.ajax({
			url: location.href + '&install[controller]=ajax&install[action]=clearCache',
			cache: false
		});
	}
};

TYPO3.Install.Scrolling = {
	isScrolledIntoView: function(elem) {
		var $window = $(window);
		var docViewTop = $window.scrollTop();
		var docViewBottom = docViewTop + $window.height();
		var $elem = $(elem);
		var elemTop = $elem.offset().top;
		var elemBottom = elemTop + $elem.height();

		return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
	},
	handleButtonScrolling: function() {
		var $fixedFooterHandler = $('#fixed-footer-handler');
		if ($fixedFooterHandler.length > 0) {
			var $fixedFooter = $('#fixed-footer');
			if (!this.isScrolledIntoView($fixedFooterHandler)) {
				$fixedFooter.addClass('fixed');
				$fixedFooter.width($('.content-area').width());
			} else {
				$fixedFooter.removeClass('fixed');
			}
		}
	}
};

TYPO3.Install.ExtensionChecker = {
	/**
	 * Call checkExtensionsCompatibility recursively on error
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
		var url = location.href + '&install[controller]=ajax&install[action]=uninstallExtension' +
			'&install[uninstallExtension][extensions]=' + extension;
		$.ajax({
			url: url,
			cache: false,
			success: function(data) {
				if (data === 'OK') {
					self.checkExtensionsCompatibility(true);
				} else {
					if (data === 'unauthorized') {
						location.reload();
					}
					// workaround for xdebug returning 200 OK on fatal errors
					if (data.substring(data.length - 2) === 'OK') {
						self.checkExtensionsCompatibility(true);
					} else {
						$('.alert-loading', '#checkExtensions').hide();
						$('.alert-error .messageText', '#checkExtensions').html(
							'Something went wrong. Check failed.' + '<p>Message:<br />' + data + '</p>'
						);
					}
				}
			},
			error: function(data) {
				self.handleCheckExtensionsError();
			}
		});
	},
	/**
	 * Handles result of extension compatibility check.
	 * Displays uninstall buttons for non-compatible extensions.
	 */
	handleCheckExtensionsSuccess: function() {
		var self = this;
		var $checkExtensions = $('#checkExtensions');

		$.ajax({
			url: $checkExtensions.data('protocolurl'),
			cache: false,
			success: function(data) {
				if (data) {
					$('.alert-danger .messageText', '#checkExtensions').html(
						'The following extensions are not compatible. Please uninstall them and try again. '
					);
					var extensions = data.split(',');
					var unloadButtonWrapper = $('<fieldset class="t3-install-form-submit"></fieldset>');
					for (var i = 0; i < extensions.length; i++) {
						var extension = extensions[i];
						var unloadButton = $('<button />', {
							text: 'Uninstall ' + $.trim(extension),
							'class': 't3-js-uninstallSingle',
							'data-extension': $.trim(extension)
						});
						var fullButton = unloadButtonWrapper.append(unloadButton);
						$('.alert-danger .messageText', '#checkExtensions').append(fullButton);
					}
					if (extensions.length) {
						$(document).on('click', 't3-js-uninstallSingle', function(e) {
							self.uninstallExtension($(this).data('extension'));
							e.preventDefault();
							return false;
						});
					}
					var unloadAllButton = $('<button />', {
						text: 'Uninstall all incompatible extensions: ' + data,
						click: function(e) {
							$('.alert-loading', '#checkExtensions').show();
							self.uninstallExtension(data);
							e.preventDefault();
							return false;
						}
					});
					unloadButtonWrapper.append('<hr />');
					var fullUnloadAllButton = unloadButtonWrapper.append(unloadAllButton);
					$('.alert-danger .messageText', '#checkExtensions').append(fullUnloadAllButton);

					$('.alert-loading', '#checkExtensions').hide();
					$('button', '#checkExtensions').show();
					$('.alert-danger', '#checkExtensions').show();
				} else {
					$('.t3js-message', '#checkExtensions').hide();
					$('.alert-success', '#checkExtensions').show();
				}
			},
			error: function() {
				$('.t3js-message', '#checkExtensions').hide();
				$('.alert-success', '#checkExtensions').show();
			}
		});
		$.getJSON(
			$checkExtensions.data('errorprotocolurl'),
			function(data) {
				$.each(data, function(i, error) {
					var messageToDisplay = error.message + ' in ' + error.file + ' on line ' + error.line;
					$checkExtensions.find('.t3js-message.alert-danger').before($(
						'<div class="t3js-message alert-warning">' +
						'<h4>' + error.type + '</h4><p class="messageText">' +
						messageToDisplay + '</p></div><p></p>'
					));
				});
			}
		);
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
			TYPO3.Install.Cache.clearCache();
			url += '&install[extensionCompatibilityTester][forceCheck]=1';
		} else {
			url += '&install[extensionCompatibilityTester][forceCheck]=0';
		}
		$.ajax({
			url: url,
			cache: false,
			success: function(data) {
				if (data === 'OK') {
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
	}
};

TYPO3.Install.TcaIntegrityChecker = {

	/**
	 * Default output messages
	 */
	outputMessages: {
		tcaMigrationsCheck: {
			fatalTitle: 'Something went wrong',
			fatalMessage: 'Use "Check for broken extensions!"',
			loadingTitle: 'Loading…',
			loadingMessage: '',
			successTitle: 'No TCA migrations need to be applied',
			successMessage: 'Your TCA looks good.',
			warningTitle: 'TCA migrations need to be applied',
			warningMessage: 'Check the following list and apply needed changes.'
		},
		tcaExtTablesCheck: {
			fatalTitle: 'Something went wrong',
			fatalMessage: 'Use "Check for broken extensions!"',
			loadingTitle: 'Loading…',
			loadingMessage: '',
			successTitle: 'No TCA changes in ext_tables.php files. Good job!',
			successMessage: '',
			warningTitle: 'Extensions change TCA in ext_tables.php',
			warningMessage: 'Check for ExtensionManagementUtility and $GLOBALS["TCA"].'
		}
	},

	/**
	 * output DOM Container
	 */
	outputContainer: {},

	/**
	 * Clone of a DOM object acts as message template
	 */
	messageTemplate: {},

	/**
	 * Clone of the DOM object that contains the submit button
	 */
	submitButton: {},

	/**
	 * Fetching the templates out of the DOM
	 *
	 * @param tcaIntegrityCheckContainer DOM element id with all needed HTML in it
	 * @return boolean DOM container could be found and initialization finished
	 */
	initialize: function(tcaIntegrityCheckContainer) {
		var success = false;
		this.outputContainer[tcaIntegrityCheckContainer] = $('#' + tcaIntegrityCheckContainer);

		if (this.outputContainer[tcaIntegrityCheckContainer]) {
			// submit button: save and delete
			if(!this.submitButton[tcaIntegrityCheckContainer]) {
				var submitButton = this.outputContainer[tcaIntegrityCheckContainer].find('button[type="submit"]');
				this.submitButton[tcaIntegrityCheckContainer] = submitButton.clone();
				// submitButton.remove();
			}

			// message template (for the output): save and delete
			if(!this.messageTemplate[tcaIntegrityCheckContainer]) {
				var messageTemplateSection = this.outputContainer[tcaIntegrityCheckContainer].find('.messageTemplate');
				this.messageTemplate[tcaIntegrityCheckContainer] = messageTemplateSection.children().clone().show();
				messageTemplateSection.remove();
			}

			// clear all messages from the run before
			this.outputContainer[tcaIntegrityCheckContainer].find('.typo3-message:visible ').remove();

			success = true;
		}
		return success;
	},

	checkTcaIntegrity: function(actionName) {
		var self = this;
		var url = location.href + '&install[controller]=ajax&install[action]=' + actionName;

		var isInitialized = self.initialize(actionName);
		if(isInitialized) {
			self.addMessage(
				'loading',
				self.outputMessages[actionName].loadingTitle,
				self.outputMessages[actionName].loadingMessage,
				actionName
			);

			$.ajax({
				url: url,
				cache: false,
				success: function(data) {

					if(data.success === true && Array.isArray(data.status)) {
						if(data.status.length > 0) {
							self.outputContainer[actionName].find('.alert-loading').hide();
							self.addMessage(
								'warning',
								self.outputMessages[actionName].warningTitle,
								self.outputMessages[actionName].warningMessage,
								actionName
							);
							data.status.forEach((function (element) {
								self.addMessage(
									element.severity,
									element.title,
									element.message,
									actionName
								);
							}));
						} else {
							// nothing to complain, everything fine
							self.outputContainer[actionName].find('.alert-loading').hide();
							self.addMessage(
								'success',
								self.outputMessages[actionName].successTitle,
								self.outputMessages[actionName].successMessage,
								actionName
							);
						}
					} else if (data === 'unauthorized') {
						location.reload();
					}
				},
				error: function() {
					self.outputContainer[actionName].find('.alert-loading').hide();
					self.addMessage(
						'fatal',
						self.outputMessages[actionName].fatalTitle,
						self.outputMessages[actionName].fatalMessage,
						actionName
					);
				}
			});
		}
	},

	/**
	 * Move the submit button to the end of the box
	 *
	 * @param tcaIntegrityCheckContainer DOM container name
	 */
	moveSubmitButtonFurtherDown: function(tcaIntegrityCheckContainer) {
		console.debug(this.outputContainer[tcaIntegrityCheckContainer], 'this.outputContainer['+[tcaIntegrityCheckContainer]+']');

		// first remove the currently visible button
		this.outputContainer[tcaIntegrityCheckContainer].find('button[type="submit"]').remove();
		// then append the cloned template to the end
		this.outputContainer[tcaIntegrityCheckContainer].append(this.submitButton[tcaIntegrityCheckContainer]);
	},

	/**
	 * Show a status message
	 *
	 * @param severity
	 * @param title
	 * @param message
	 * @param tcaIntegrityCheckContainer DOM container name
	 */
	addMessage: function(severity, title, message, tcaIntegrityCheckContainer) {
		var domMessage = this.messageTemplate[tcaIntegrityCheckContainer].clone();
		if (severity) {
			domMessage.addClass('alert-' + severity);
		}
		if (title) {
			domMessage.find('h4').html(title);
		}
		if (message) {
			domMessage.find('.messageText').html(message);
		} else {
			domMessage.find('.messageText').remove();
		}
		this.outputContainer[tcaIntegrityCheckContainer].append(domMessage);
		this.moveSubmitButtonFurtherDown(tcaIntegrityCheckContainer);
	}

};

TYPO3.Install.Status = {
	getFolderStatus: function() {
		var url = location.href + '&install[controller]=ajax&install[action]=folderStatus';
		$.ajax({
			url: url,
			cache: false,
			success: function(data) {
				if (data > 0) {
					$('.t3js-install-menu-folderStructure').append('<span class="badge badge-danger">' + data + '</span>');
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
					$('.t3js-install-menu-systemEnvironment').append('<span class="badge badge-danger">' + data + '</span>');
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
			success: function(result) {
				var canContinue = self.handleResult(result, self.actionQueue[actionName].finishMessage);
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
				this.addMessage('success', successMessage);
			}
		} else {
			// Handle clearcache until it uses the new view object
			if (data === "OK") {
				canContinue = true;
				if (successMessage) {
					this.addMessage('success', successMessage);
				}
			} else {
				canContinue = false;
				if (data.status && typeof(data.status) === 'object') {
					this.showStatusMessages(data.status);
				} else {
					this.addMessage('danger', 'General error');
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
		domMessage.find('h4').html(messageTitle);
		domMessage.addClass('alert-notice');
		domMessage.find('.messageText').remove();
		$('#coreUpdate').append(domMessage);
	},

	/**
	 * Remove an enabled loading message
	 */
	removeLoadingMessage: function() {
		$('#coreUpdate').find('.alert-notice').closest('.alert').remove();
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
			domMessage.addClass('alert-' + severity);
		}
		if (title) {
			domMessage.find('h4').html(title);
		}
		if (message) {
			domMessage.find('.messageText').html(message);
		} else {
			domMessage.find('.messageText').remove();
		}
		$('#coreUpdate').append(domMessage);
	}
};

$(function() {
	// Used in database compare section to select/deselect checkboxes
	$('.checkall').on('click', function() {
		$(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
	});

	$('.item-description').find('a').on('click', function() {
		var targetToggleGroupId = $(this.hash);
		if (targetToggleGroupId) {
			var $currentToggleGroup = $(this).closest('.toggleGroup');
			var $targetToggleGroup = $(targetToggleGroupId).closest('.toggleGroup');
			if ($targetToggleGroup !== $currentToggleGroup) {
				$currentToggleGroup.removeClass('expanded');
				$currentToggleGroup.find('.toggleData').hide();
				$targetToggleGroup.addClass('expanded');
				$targetToggleGroup.find('.toggleData').show();
				TYPO3.Install.Scrolling.handleButtonScrolling();
			}
		}
	});

	$(document).on('click', '.t3js-all-configuration-toggle', function() {
		var $panels = $('.panel-collapse', '#allConfiguration');
		var action = ($panels.eq(0).hasClass('in')) ? 'hide' : 'show';
		$panels.collapse(action);
	});

	if ($('#configSearch').length > 0) {
		$(window).on('keydown', function(event) {
			if (event.ctrlKey || event.metaKey) {
				switch (String.fromCharCode(event.which).toLowerCase()) {
					case 'f':
						event.preventDefault();
						$('#configSearch').focus();
						break;
				}
			}
		});
	}

	// Simple password strength indicator
	$('.t3-install-form-password-strength').on('keyup', function() {
		var value = $(this).val();
		var strongRegex = new RegExp('^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$', 'g');
		var mediumRegex = new RegExp('^(?=.{8,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$', 'g');
		var enoughRegex = new RegExp('(?=.{8,}).*', 'g');

		if (value.length === 0) {
			$(this).attr('style', 'background-color:#FBB19B; border:1px solid #DC4C42');
		} else  if (!enoughRegex.test(value)) {
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
			hostField.parents('.form-group').fadeOut();
			hostField.val('localhost');
			portField.parents('.form-group').fadeOut();
			socketField.parents('.form-group').fadeIn();
		} else {
			hostField.parents('.form-group').fadeIn();
			if (hostField.val() === 'localhost') {
				hostField.val('127.0.0.1');
			}
			portField.parents('.form-group').fadeIn();
			socketField.parents('.form-group').fadeOut();
		}
	}).trigger('change');

	// Extension compatibility check
	$('.t3js-message', '#checkExtensions').hide();
	$('button', '#checkExtensions').click(function(e) {
		$('button', '#checkExtensions').hide();
		$('.t3js-message', '#checkExtensions').hide();
		$('.alert-loading', '#checkExtensions').show();
		TYPO3.Install.ExtensionChecker.checkExtensionsCompatibility(true);
		e.preventDefault();
		return false;
	});

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

	// Handle TCA ext_tables check
	var $tcaExtTablesCheckSection = $('#tcaExtTablesCheck');
	if ($tcaExtTablesCheckSection) {
		$tcaExtTablesCheckSection.on('click', 'button', (function(e) {
			TYPO3.Install.TcaIntegrityChecker.checkTcaIntegrity('tcaExtTablesCheck');
			e.preventDefault();
			return false;
		}));
	}

	// Handle TCA Migrations check
	var $tcaMigrationsCheckSection = $('#tcaMigrationsCheck');
	if ($tcaMigrationsCheckSection) {
		$tcaMigrationsCheckSection.on('click', 'button', (function(e) {
			TYPO3.Install.TcaIntegrityChecker.checkTcaIntegrity('tcaMigrationsCheck');
			e.preventDefault();
			return false;
		}));
	}

	var $installLeft = $('.t3js-list-group-wrapper');
	if ($installLeft.length > 0) {
		TYPO3.Install.Status.getFolderStatus();
		TYPO3.Install.Status.getEnvironmentStatus();
	}
	// This makes jquerys "contains" work case-insensitive
	jQuery.expr[':'].contains = jQuery.expr.createPseudo(function(arg) {
		return function(elem) {
			return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
		};
	});
	$('#configSearch').keyup(function() {
		var typedQuery = $(this).val();
		$('div.item').each(function() {
			var $item = $(this);
			if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
				$item.removeClass('hidden').addClass('searchhit');
			} else {
				$item.removeClass('searchhit').addClass('hidden');
			}
		});
		$('.searchhit').parent().collapse('show');
	});
	var $searchFields = $('#configSearch');
	var searchResultShown = ('' !== $searchFields.first().val());

	// make search field clearable
	$searchFields.clearable({
		onClear: function() {
			if (searchResultShown) {
				$(this).closest('form').submit();
			}
		}
	});

	// Define width of fixed menu
	var $menuWrapper = $('#menuWrapper');
	var $menuListGroup = $menuWrapper.children('.t3js-list-group-wrapper');
	$menuWrapper.on('affixed.bs.affix', function() {
		$menuListGroup.width($(this).parent().width());
	});
	$menuListGroup.width($menuWrapper.parent().width());
	$(window).resize(function() {
		$menuListGroup.width($('#menuWrapper').parent().width());
	});
	var $collapse = $('.collapse');
	$collapse.on('shown.bs.collapse', function() {
		TYPO3.Install.Scrolling.handleButtonScrolling();
	});
	$collapse.on('hidden.bs.collapse', function() {
		TYPO3.Install.Scrolling.handleButtonScrolling();
	});

	// trigger 'handleButtonScrolling' on page scroll
	// if the user scroll until page bottom, we need to remove 'position: fixed'
	// so that the copyright info (footer) is not overlayed by the 'fixed button'
	var scrollTimeout;
	$(window).on('scroll', function() {
		clearTimeout(scrollTimeout);
		scrollTimeout = setTimeout(function() {
			TYPO3.Install.Scrolling.handleButtonScrolling();
		}, 50);
	});

	// automatically select the custom preset if a value in one of its input fields is changed
	$('.t3js-custom-preset').on('input', function() {
		$('#' + $(this).data('radio')).prop('checked', true);
	});

	TYPO3.Install.upgradeAnalysis.initialize();
});


TYPO3.Install.upgradeAnalysis = {
	provideTags: function() {
		$('#tagsort_tags_container').tagSort({
			selector: '.upgrade_analysis_item_to_filter'
		});
	},

	initialize: function() {
		TYPO3.Install.upgradeAnalysis.provideTags();
	}
};
