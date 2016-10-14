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
 * Module: TYPO3/CMS/Backend/LoginRefresh
 * Task that periodically checks if a blocking event in the backend occurred and
 * displays a proper dialog to the user.
 */
define(['jquery', 'TYPO3/CMS/Backend/Notification', 'bootstrap'], function($, Typo3Notification) {
	'use strict';

	/**
	 *
	 * @type {{identifier: {loginrefresh: string, lockedModal: string, loginFormModal: string}, options: {modalConfig: {backdrop: string}}, webNotification: null, intervalTime: integer, intervalId: null, backendIsLocked: boolean, isTimingOut: boolean, $timeoutModal: string, $backendLockedModal: string, $loginForm: string, loginFramesetUrl: string, logoutUrl: string}}
	 * @exports TYPO3/CMS/Backend/LoginRefresh
	 */
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
		intervalTime: 60,
		intervalId: null,
		backendIsLocked: false,
		isTimingOut: false,
		$timeoutModal: '',
		$backendLockedModal: '',
		$loginForm: '',
		loginFramesetUrl: '',
		logoutUrl: ''
	};

	/**
	 * Starts the session check task (if not running already)
	 */
	LoginRefresh.startTask = function() {
		if (LoginRefresh.intervalId !== null) {
			return;
		}

		// set interval to 60 seconds
		var interval = 1000 * LoginRefresh.intervalTime;
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
	 *
	 * @param {String} identifier
	 * @returns {Object}
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
	 * Set interval time
	 *
	 * @param {integer} intervalTime
	 */
	LoginRefresh.setIntervalTime = function(intervalTime) {
		// To avoid the integer overflow in setInterval, we limit the interval time to be one request per day
		LoginRefresh.intervalTime = Math.min(intervalTime, 86400);
	};

	/**
	 * Set logout url
	 *
	 * @param {String} logoutUrl
	 */
	LoginRefresh.setLogoutUrl = function(logoutUrl) {
		LoginRefresh.logoutUrl = logoutUrl;
	};

	/**
	 * Generates the modal displayed on near session time outs
	 */
	LoginRefresh.initializeTimeoutModal = function() {
		LoginRefresh.$timeoutModal = LoginRefresh.generateModal(LoginRefresh.identifier.loginrefresh);
		LoginRefresh.$timeoutModal.addClass('t3-modal-notice');
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
			$('<button />', {class: 'btn btn-default', 'data-action': 'logout'}).text(TYPO3.LLL.core.refresh_login_logout_button).on('click', function() {
				top.location.href = LoginRefresh.logoutUrl;
			}),
			$('<button />', {class: 'btn btn-primary t3js-active', 'data-action': 'refreshSession'}).text(TYPO3.LLL.core.refresh_login_refresh_button).on('click', function() {
				$.ajax({
					url: TYPO3.settings.ajaxUrls['login_timedout'],
					method: 'GET',
					success: function() {
						LoginRefresh.hideTimeoutModal();
					}
				});
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
			LoginRefresh.webNotification.onclick = function() {
				window.focus();
			};
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
		LoginRefresh.$loginForm.addClass('t3-modal-notice');
		var refresh_login_title = String(TYPO3.LLL.core.refresh_login_title).replace('%s', TYPO3.configuration.username);
		LoginRefresh.$loginForm.find('.modal-header h4').text(refresh_login_title);
		LoginRefresh.$loginForm.find('.modal-body').append(
			$('<p />').text(TYPO3.LLL.core.login_expired),
			$('<form />', {id: 'beLoginRefresh', method: 'POST', action: TYPO3.settings.ajaxUrls['login']}).append(
				$('<div />', {class: 'form-group'}).append(
					$('<input />', {type: 'password', name: 'p_field', autofocus: 'autofocus', class: 'form-control', placeholder: TYPO3.LLL.core.refresh_login_password, 'data-rsa-encryption': 't3-loginrefres-userident'})
				),
				$('<input />', {type: 'hidden', name: 'username', value: TYPO3.configuration.username}),
				$('<input />', {type: 'hidden', name: 'userident', id: 't3-loginrefres-userident'})
			)
		);
		LoginRefresh.$loginForm.find('.modal-footer').append(
			$('<a />', {href: LoginRefresh.logoutUrl, class: 'btn btn-default'}).text(TYPO3.LLL.core.refresh_exit_button),
			$('<button />', {type: 'button', class: 'btn btn-primary', 'data-action': 'refreshSession'})
				.text(TYPO3.LLL.core.refresh_login_button)
				.on('click', function(e) {
					LoginRefresh.$loginForm.find('form').submit();
				})
		);
		LoginRefresh.registerDefaultModalEvents(LoginRefresh.$loginForm).on('submit', LoginRefresh.submitForm);
		$('body').append(LoginRefresh.$loginForm);
		if (require.specified('TYPO3/CMS/Rsaauth/RsaEncryptionModule')) {
			require(['TYPO3/CMS/Rsaauth/RsaEncryptionModule'], function(RsaEncryption) {
				RsaEncryption.registerForm($('#beLoginRefresh').get(0));
			});
		}
	};

	/**
	 * Shows the login form.
	 */
	LoginRefresh.showLoginForm = function() {
		// log off for sure
		$.ajax({
			url: TYPO3.settings.ajaxUrls['logout'],
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
	 * Set login frameset url
	 */
	LoginRefresh.setLoginFramesetUrl = function(loginFramesetUrl) {
		LoginRefresh.loginFramesetUrl = loginFramesetUrl;
	};

	/**
	 * Opens the login form in a new window.
	 */
	LoginRefresh.showLoginPopup = function() {
		var vHWin = window.open(LoginRefresh.loginFramesetUrl, 'relogin_' + TYPO3.configuration.uniqueID, 'height=450,width=700,status=0,menubar=0,location=1');
		if (vHWin) {
			vHWin.focus();
		}
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
	 * Creates additional data based on the security level and "submits" the form
	 * via an AJAX request.
	 *
	 * @param {Event} event
	 */
	LoginRefresh.submitForm = function(event) {
		event.preventDefault();

		var $form = LoginRefresh.$loginForm.find('form'),
			$passwordField = $form.find('input[name=p_field]'),
			$useridentField = $form.find('input[name=userident]'),
			passwordFieldValue = $passwordField.val();

		if (passwordFieldValue === '' && $useridentField.val() === '') {
			Typo3Notification.error(TYPO3.LLL.core.refresh_login_failed, TYPO3.LLL.core.refresh_login_emptyPassword);
			$passwordField.focus();
			return;
		}

		if (passwordFieldValue) {
			$useridentField.val(passwordFieldValue);
			$passwordField.val('');
		}

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
					Typo3Notification.error(TYPO3.LLL.core.refresh_login_failed, TYPO3.LLL.core.refresh_login_failed_message);
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
	 *
	 * @param {Object} $modal
	 * @returns {Object}
	 */
	LoginRefresh.registerDefaultModalEvents = function($modal) {
		$modal.on('hidden.bs.modal', function() {
			LoginRefresh.startTask();
		}).on('shown.bs.modal', function() {
			LoginRefresh.stopTask();
			// focus the button which was configured as active button
			LoginRefresh.$timeoutModal.find('.modal-footer .t3js-active').first().focus();
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
			url: TYPO3.settings.ajaxUrls['login_timedout'],
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

	LoginRefresh.initialize = function() {
		LoginRefresh.initializeTimeoutModal();
		LoginRefresh.initializeBackendLockedModal();
		LoginRefresh.initializeLoginForm();

		LoginRefresh.startTask();

		if (typeof Notification !== 'undefined' && Notification.permission !== 'granted') {
			Notification.requestPermission();
		}
	};

	// expose to global
	TYPO3.LoginRefresh = LoginRefresh;

	return LoginRefresh;
});
