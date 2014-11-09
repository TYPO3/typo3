/**
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
 * Task that periodically checks if a blocking event in the backend occured and
 * displays a proper dialog to the user.
 */
define('TYPO3/CMS/Backend/LoginRefresh', ['jquery'], function($) {
	var LoginRefresh = {
		identifier: {
			loginrefresh: 't3-modal-loginrefresh',
			lockedModal: 't3-modal-backendlocked',
			loginFormModal: 't3-modal-backendloginform'
		},
		options: {
			modalConfig: {
				backdrop: 'static'
			}
		},
		webNotification: null,
		intervalId: null,
		backendIsLocked: false,
		isTimingOut: false,
		$timeoutModal: '',
		$backendLockedModal: '',
		$loginForm: ''
	};

	/**
	 * Starts the session check task (if not running already)
	 */
	LoginRefresh.startTask = function() {
		if (LoginRefresh.intervalId !== null) {
			return;
		}

		// set interval to 60 seconds
		var interval = 1000 * 60;
		LoginRefresh.intervalId = setInterval(LoginRefresh.checkActiveSession, interval);
	};

	/**
	 * Stops the session check task
	 */
	LoginRefresh.stopTask = function() {
		clearInterval(LoginRefresh.intervalId);
		LoginRefresh.intervalId = null;
	};

	/**
	 * Generates a modal dialog as template.
	 */
	LoginRefresh.generateModal = function(identifier) {
		return TYPO3.jQuery('<div />', {id: identifier, class: 't3-modal t3-blr-modal ' + identifier + ' modal fade'}).append(
			$('<div />', {class: 'modal-dialog'}).append(
				$('<div />', {class: 'modal-content'}).append(
					$('<div />', {class: 'modal-header'}).append(
						$('<h4 />', {class: 'modal-title'})
					),
					$('<div />', {class: 'modal-body'}),
					$('<div />', {class: 'modal-footer'})
				)
			)
		);
	};

	/**
	 * Generates the modal displayed on near session time outs
	 */
	LoginRefresh.initializeTimeoutModal = function() {
		LoginRefresh.$timeoutModal = LoginRefresh.generateModal(LoginRefresh.identifier.loginrefresh);
		LoginRefresh.$timeoutModal.find('.modal-header h4').text(TYPO3.LLL.core.login_about_to_expire_title);
		LoginRefresh.$timeoutModal.find('.modal-body').append(
			$('<p />').text(TYPO3.LLL.core.login_about_to_expire),
			$('<div />', {class: 'progress'}).append(
				$('<div />', {
					class: 'progress-bar progress-bar-warning progress-bar-striped active',
					role: 'progressbar',
					'aria-valuemin': '0',
					'aria-valuemax': '100'
				}).append(
					$('<span />', {class: 'sr-only'})
				)
			)
		);
		LoginRefresh.$timeoutModal.find('.modal-footer').append(
			$('<button />', {class: 't3-button', 'data-action': 'refreshSession'}).text(TYPO3.LLL.core.refresh_login_refresh_button).on('click', function() {
				$.ajax({
					url: TYPO3.settings.ajaxUrls['BackendLogin::isTimedOut'],
					method: 'GET',
					success: function() {
						LoginRefresh.hideTimeoutModal();
					}
				});
			}),
			$('<button />', {class: 't3-button', 'data-action': 'logout'}).text(TYPO3.LLL.core.refresh_direct_logout_button).on('click', function() {
				top.location.href = TYPO3.configuration.siteUrl + TYPO3.configuration.TYPO3_mainDir + 'logout.php';
			})
		);

		LoginRefresh.registerDefaultModalEvents(LoginRefresh.$timeoutModal);

		$('body').append(LoginRefresh.$timeoutModal);
	};

	/**
	 * Shows the timeout dialog. If the backend is not focused, a Web Notification
	 * is displayed, too.
	 */
	LoginRefresh.showTimeoutModal = function() {
		LoginRefresh.isTimingOut = true;
		LoginRefresh.$timeoutModal.modal(LoginRefresh.options.modalConfig);
		LoginRefresh.fillProgressbar(LoginRefresh.$timeoutModal);

		if (typeof Notification !== 'undefined' && Notification.permission === 'granted' && !LoginRefresh.isPageActive()) {
			LoginRefresh.webNotification = new Notification(TYPO3.LLL.core.login_about_to_expire_title, {
				body: TYPO3.LLL.core.login_about_to_expire,
				icon: '/typo3/sysext/backend/Resources/Public/Images/Logo.png'
			});
		}
	};

	/**
	 * Hides the timeout dialog. If a Web Notification is displayed, close it too.
	 */
	LoginRefresh.hideTimeoutModal = function() {
		LoginRefresh.isTimingOut = false;
		LoginRefresh.$timeoutModal.modal('hide');

		if (typeof Notification !== 'undefined' && LoginRefresh.webNotification !== null) {
			LoginRefresh.webNotification.close();
		}
	};

	/**
	 * Generates the modal displayed if the backend is locked.
	 */
	LoginRefresh.initializeBackendLockedModal = function() {
		LoginRefresh.$backendLockedModal = LoginRefresh.generateModal(LoginRefresh.identifier.lockedModal);
		LoginRefresh.$backendLockedModal.find('.modal-header h4').text(TYPO3.LLL.core.please_wait);
		LoginRefresh.$backendLockedModal.find('.modal-body').append(
			$('<p />').text(TYPO3.LLL.core.be_locked)
		);
		LoginRefresh.$backendLockedModal.find('.modal-footer').remove();

		$('body').append(LoginRefresh.$backendLockedModal);
	};

	/**
	 * Shows the "backend locked" dialog.
	 */
	LoginRefresh.showBackendLockedModal = function() {
		LoginRefresh.$backendLockedModal.modal(LoginRefresh.options.modalConfig);
	};

	/**
	 * Hides the "backend locked" dialog.
	 */
	LoginRefresh.hideBackendLockedModal = function() {
		LoginRefresh.$backendLockedModal.modal('hide');
	};

	/**
	 * Generates the login form displayed if the session has timed out.
	 */
	LoginRefresh.initializeLoginForm = function() {
		if (TYPO3.configuration.showRefreshLoginPopup) {
			// dialog is not required if "showRefreshLoginPopup" is enabled
			return;
		}

		LoginRefresh.$loginForm = LoginRefresh.generateModal(LoginRefresh.identifier.loginFormModal);
		LoginRefresh.$loginForm.find('.modal-header h4').text(TYPO3.LLL.core.refresh_login_title);
		LoginRefresh.$loginForm.find('.modal-body').append(
			$('<p />').text(TYPO3.LLL.core.login_expired),
			$('<form />', {id: 'beLoginRefresh', method: 'POST', action: TYPO3.settings.ajaxUrls['BackendLogin::login']}).append(
				$('<div />', {class: 'form-group'}).append(
					$('<input />', {type: 'password', name: 'p_field', autofocus: 'autofocus', class: 'form-control', placeholder: TYPO3.LLL.core.refresh_login_password})
				),
				$('<input />', {type: 'hidden', name: 'username', value: TYPO3.configuration.username}),
				$('<input />', {type: 'hidden', name: 'userident'}),
				$('<input />', {type: 'hidden', name: 'challenge'})
			)
		);
		LoginRefresh.$loginForm.find('.modal-footer').append(
			$('<button />', {type: 'submit', form: 'beLoginRefresh', class: 't3-button', 'data-action': 'refreshSession'}).text(TYPO3.LLL.core.refresh_login_button),
			$('<button />', {class: 't3-button', 'data-action': 'logout'}).text(TYPO3.LLL.core.refresh_direct_logout_button).on('click', function() {
				top.location.href = TYPO3.configuration.siteUrl + TYPO3.configuration.TYPO3_mainDir + 'logout.php';
			})
		);

		LoginRefresh.registerDefaultModalEvents(LoginRefresh.$loginForm).on('submit', LoginRefresh.triggerSubmitForm);

		$('body').append(LoginRefresh.$loginForm);
	};

	/**
	 * Shows the login form.
	 */
	LoginRefresh.showLoginForm = function() {
		// log off for sure
		$.ajax({
			url: TYPO3.settings.ajaxUrls['BackendLogin::logout'],
			method: 'GET',
			success: function() {
				if (TYPO3.configuration.showRefreshLoginPopup) {
					LoginRefresh.showLoginPopup();
				} else {
					LoginRefresh.$loginForm.modal(LoginRefresh.options.modalConfig);
				}
			},
			failure: function() {
				alert('something went wrong');
			}
		});
	};

	/**
	 * Opens the login form in a new window.
	 */
	LoginRefresh.showLoginPopup = function() {
		var vHWin = window.open('login_frameset.php', 'relogin_' + TYPO3.configuration.uniqueID, 'height=450,width=700,status=0,menubar=0,location=1');
		vHWin.focus();
	};

	/**
	 * Hides the login form.
	 */
	LoginRefresh.hideLoginForm = function() {
		LoginRefresh.$loginForm.modal('hide');
	};

	/**
	 * Fills the progressbar attached to the given modal.
	 */
	LoginRefresh.fillProgressbar = function($activeModal) {
		if (!LoginRefresh.isTimingOut) {
			return;
		}

		var max = 100,
			current = 0,
			$progressBar = $activeModal.find('.progress-bar'),
			$srText = $progressBar.children('.sr-only');

		var progress = setInterval(function() {
			var isOverdue = (current >= max);

			if (!LoginRefresh.isTimingOut || isOverdue) {
				clearInterval(progress);

				if (isOverdue) {
					// show login form
					LoginRefresh.hideTimeoutModal();
					LoginRefresh.showLoginForm();
				}

				// reset current
				current = 0;
			} else {
				current += 1;
			}

			var percentText = (current) + '%';
			$progressBar.css('width', percentText);
			$srText.text(percentText);
		}, 300);
	};

	/**
	 * Triggers the form submit based on the security level.
	 */
	LoginRefresh.triggerSubmitForm = function(e) {
		e.preventDefault();

		switch (TYPO3.configuration.securityLevel) {
			case 'superchallenged':
			case 'challenged':
				$.ajax({
					url: TYPO3.settings.ajaxUrls['BackendLogin::getChallenge'],
					method: 'GET',
					data: {
						skipSessionUpdate: 1
					},
					success: function(response) {
						if (response.challenge) {
							LoginRefresh.$loginForm.find('input[name=challenge]').val(response.challenge);
							LoginRefresh.submitForm();
						}
					}
				});
				break;
			case 'rsa':
				$.ajax({
					url: TYPO3.settings.ajaxUrls['BackendLogin::getRsaPublicKey'],
					method: 'GET',
					data: {
						skipSessionUpdate: 1
					},
					success: function(response) {
						if (response.publicKeyModulus && response.exponent) {
							LoginRefresh.submitForm(response);
						}
					}
				});
				break;
			default:
				LoginRefresh.submitForm();
		}
	};

	/**
	 * Creates additional data based on the security level and "submits" the form
	 * via an AJAX request.
	 */
	LoginRefresh.submitForm = function(parameters) {
		var $form = LoginRefresh.$loginForm.find('form'),
			$usernameField = $form.find('input[name=username]'),
			$passwordField = $form.find('input[name=p_field]'),
			$challengeField = $form.find('input[name=challenge]'),
			$useridentField = $form.find('input[name=userident]'),
			passwordFieldValue = $passwordField.val();

		if (passwordFieldValue === '') {
			top.TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.LLL.core.refresh_login_failed, TYPO3.LLL.core.refresh_login_emptyPassword);
			$passwordField.focus();
			return;
		}

		if (TYPO3.configuration.securityLevel === 'superchallenged') {
			$passwordField.val(MD5(passwordFieldValue));
		}

		if (TYPO3.configuration.securityLevel === 'superchallenged' || TYPO3.configuration.securityLevel === 'challenged') {
			$challengeField.val(parameters.challenge);
			$useridentField.val(MD5($usernameField.val() + ':' + passwordFieldValue + ':' + parameters.challenge));
		} else if (TYPO3.configuration.securityLevel === 'rsa') {
			var rsa = new RSAKey();
			rsa.setPublic(parameters.publicKeyModulus, parameters.exponent);
			var encryptedPassword = rsa.encrypt(passwordFieldValue);
			$useridentField.val('rsa:' + hex2b64(encryptedPassword));
		} else {
			$useridentField.val(passwordFieldValue);
		}
		$passwordField.val('');

		var postData = {
			login_status: 'login'
		};
		$.each($form.serializeArray(), function(i, field) {
			postData[field.name] = field.value;
		});
		$.ajax({
			url: $form.attr('action'),
			method: 'POST',
			data: postData,
			success: function(response) {
				var result = response.login;
				if (result.success) {
					// User is logged in
					LoginRefresh.hideLoginForm();
				} else {
					// TODO: add failure to notification system instead of alert
					top.TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.LLL.core.refresh_login_failed, TYPO3.LLL.core.refresh_login_failed_message);
					$passwordField.focus();
				}
			}
		});
	};

	/**
	 * Registers the (shown|hidden).bs.modal events.
	 * If a modal is shown, the interval check is stopped. If the modal hides,
	 * the interval check starts again.
	 * This method is not invoked for the backend locked modal, because we still
	 * need to check if the backend gets unlocked again.
	 */
	LoginRefresh.registerDefaultModalEvents = function($modal) {
		$modal.on('hidden.bs.modal', function() {
			LoginRefresh.startTask();
		}).on('shown.bs.modal', function() {
			LoginRefresh.stopTask();
		});

		return $modal;
	};

	/**
	 * Checks if the user is in focus of the backend.
	 * Thanks to http://stackoverflow.com/a/19519701
	 */
	LoginRefresh.isPageActive = function() {
		var stateKey, eventKey, keys = {
			hidden: 'visibilitychange',
			webkitHidden: 'webkitvisibilitychange',
			mozHidden: 'mozvisibilitychange',
			msHidden: 'msvisibilitychange'
		};

		for (stateKey in keys) {
			if (stateKey in document) {
				eventKey = keys[stateKey];
				break;
			}
		}
		return function(c) {
			if (c) {
				document.addEventListener(eventKey, c);
			}
			return !document[stateKey];
		}();
	};

	/**
	 * Periodically called task that checks if
	 *
	 * - the user's backend session is about to expire
	 * - the user's backend session has expired
	 * - the backend got locked
	 *
	 * and opens a dialog.
	 */
	LoginRefresh.checkActiveSession = function() {
		$.ajax({
			url: TYPO3.settings.ajaxUrls['BackendLogin::isTimedOut'],
			data: {
				skipSessionUpdate: 1
			},
			success: function(response) {
				if (response.login.locked) {
					if (!LoginRefresh.backendIsLocked) {
						LoginRefresh.backendIsLocked = true;
						LoginRefresh.showBackendLockedModal();
					}
				} else {
					if (LoginRefresh.backendIsLocked) {
						LoginRefresh.backendIsLocked = false;
						LoginRefresh.hideBackendLockedModal();
					}
				}

				if (!LoginRefresh.backendIsLocked) {
					if (response.login.timed_out || response.login.will_time_out) {
						if (response.login.timed_out) {
							LoginRefresh.showLoginForm();
						} else {
							LoginRefresh.showTimeoutModal();
						}
					}
				}
			}
		});
	};

	// initialize and return the LoginRefresh object
	return function() {
		$(document).ready(function() {
			LoginRefresh.initializeTimeoutModal();
			LoginRefresh.initializeBackendLockedModal();
			LoginRefresh.initializeLoginForm();

			LoginRefresh.startTask();

			if (typeof Notification !== 'undefined' && Notification.permission !== 'granted') {
				Notification.requestPermission();
			}
		});

		TYPO3.LoginRefresh = LoginRefresh;
		return LoginRefresh;
	}();
});
