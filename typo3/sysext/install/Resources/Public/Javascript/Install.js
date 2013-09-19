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
 * Small javascript helpers of the install tool based on jquery
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
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

	// Simple password strength indicator
	$('.t3-install-form-password-strength').on('keyup', function() {
		var value = $(this).val();
		var strongRegex = new RegExp('^(?=.{8,})(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*\\W).*$', 'g');
		var mediumRegex = new RegExp('^(?=.{7,})(((?=.*[A-Z])(?=.*[a-z]))|((?=.*[A-Z])(?=.*[0-9]))|((?=.*[a-z])(?=.*[0-9]))).*$', 'g');
		var enoughRegex = new RegExp('(?=.{6,}).*', 'g');

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

	$('.typo3-message', '#checkExtensions').hide();
	$('button', '#checkExtensions').click(function(e) {
		$('button', '#checkExtensions').hide();
		$('.typo3-message', '#checkExtensions').hide();
		$('.message-loading', '#checkExtensions').show();
		checkExtensionsCompatibility(true);
		e.preventDefault();
		return false;
	});

	// Focus input field on click on item-div around it
	$('.toggleGroup .item').on('click', function() {
		$(this).find('input').focus();
	});

	if ($('#fixed-footer-fieldset').length > 0) {
		$(window).scroll(handleButtonScrolling);
		$('body.backend #typo3-docbody').scroll(handleButtonScrolling);
	}
});

function handleButtonScrolling() {
	if (!isScrolledIntoView($('#fixed-footer-fieldset'))) {
		$('#fixed-footer-fieldset fieldset').addClass('fixed');
	} else {
		$('#fixed-footer-fieldset fieldset').removeClass('fixed');
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
		error: function(data) {
			$('.typo3-message', '#checkExtensions').hide();
			$('.message-ok', '#checkExtensions').show();
		}
	})
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