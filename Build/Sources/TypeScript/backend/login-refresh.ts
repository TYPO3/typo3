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

import $ from 'jquery';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Notification from '@typo3/backend/notification';

enum MarkupIdentifiers {
  loginrefresh = 't3js-modal-loginrefresh',
  lockedModal = 't3js-modal-backendlocked',
  loginFormModal = 't3js-modal-backendloginform',
}

interface LoginRefreshOptions {
  intervalTime?: number;
  loginFramesetUrl?: string;
  logoutUrl?: string;
}

/**
 * Module: @typo3/backend/login-refresh
 * @exports @typo3/backend/login-refresh
 */
class LoginRefresh {
  private options: any = {
    modalConfig: {
      backdrop: 'static',
    },
  };
  private intervalTime: number = 60;
  private intervalId: number = null;
  private backendIsLocked: boolean = false;
  private isTimingOut: boolean = false;
  private $timeoutModal: JQuery = null;
  private $backendLockedModal: JQuery = null;
  private $loginForm: JQuery = null;
  private loginFramesetUrl: string = '';
  private logoutUrl: string = '';

  /**
   * Initialize login refresh
   */
  public initialize(options?: LoginRefreshOptions): void {
    if (typeof options === 'object') {
      this.applyOptions(options);
    }
    this.initializeTimeoutModal();
    this.initializeBackendLockedModal();
    this.initializeLoginForm();
    this.startTask();
  }

  /**
   * Start the task
   */
  public startTask(): void {
    if (this.intervalId !== null) {
      return;
    }
    // set interval to 60 seconds
    let interval: number = this.intervalTime * 1000;
    this.intervalId = setInterval(this.checkActiveSession, interval);
  }

  /**
   * Stop the task
   */
  public stopTask(): void {
    clearInterval(this.intervalId);
    this.intervalId = null;
  }

  /**
   * Set interval time
   *
   * @param {number} intervalTime
   */
  public setIntervalTime(intervalTime: number): void {
    // To avoid the integer overflow in setInterval, we limit the interval time to be one request per day
    this.intervalTime = Math.min(intervalTime, 86400);
  }

  /**
   * Set the logout URL
   *
   * @param {string} logoutUrl
   */
  public setLogoutUrl(logoutUrl: string): void {
    this.logoutUrl = logoutUrl;
  }

  /**
   * Set login frameset url
   */
  public setLoginFramesetUrl(loginFramesetUrl: string): void {
    this.loginFramesetUrl = loginFramesetUrl;
  }

  /**
   * Shows the timeout dialog. If the backend is not focused, a Web Notification
   * is displayed, too.
   */
  public showTimeoutModal(): void {
    this.isTimingOut = true;
    this.$timeoutModal.modal(this.options.modalConfig);
    this.$timeoutModal.modal('show');
    this.fillProgressbar(this.$timeoutModal);
  }

  /**
   * Hides the timeout dialog. If a Web Notification is displayed, close it too.
   */
  public hideTimeoutModal(): void {
    this.isTimingOut = false;
    this.$timeoutModal.modal('hide');
  }

  /**
   * Shows the "backend locked" dialog.
   */
  public showBackendLockedModal(): void {
    this.$backendLockedModal.modal(this.options.modalConfig);
    this.$backendLockedModal.modal('show');
  }

  /**
   * Hides the "backend locked" dialog.
   */
  public hideBackendLockedModal(): void {
    this.$backendLockedModal.modal('hide');
  }

  /**
   * Shows the login form.
   */
  public showLoginForm(): void {
    // log off for sure
    new AjaxRequest(TYPO3.settings.ajaxUrls.logout).get().then((): void => {
      if (TYPO3.configuration.showRefreshLoginPopup) {
        this.showLoginPopup();
      } else {
        this.$loginForm.modal(this.options.modalConfig);
        this.$loginForm.modal('show');
      }
    });
  }

  /**
   * Opens the login form in a new window.
   */
  public showLoginPopup(): void {
    const vHWin = window.open(
      this.loginFramesetUrl,
      'relogin_' + Math.random().toString(16).slice(2),
      'height=450,width=700,status=0,menubar=0,location=1',
    );
    if (vHWin) {
      vHWin.focus();
    }
  }

  /**
   * Hides the login form.
   */
  public hideLoginForm(): void {
    this.$loginForm.modal('hide');
  }

  /**
   * Generates the modal displayed if the backend is locked.
   */
  protected initializeBackendLockedModal(): void {
    this.$backendLockedModal = this.generateModal(MarkupIdentifiers.lockedModal);
    this.$backendLockedModal.find('.modal-header h4').text(TYPO3.lang['mess.please_wait']);
    this.$backendLockedModal.find('.modal-body').append(
      $('<p />').text(TYPO3.lang['mess.be_locked']),
    );
    this.$backendLockedModal.find('.modal-footer').remove();

    $('body').append(this.$backendLockedModal);
  }

  /**
   * Generates the modal displayed on near session time outs
   */
  protected initializeTimeoutModal(): void {
    this.$timeoutModal = this.generateModal(MarkupIdentifiers.loginrefresh);
    this.$timeoutModal.addClass('modal-severity-notice');
    this.$timeoutModal.find('.modal-header h4').text(TYPO3.lang['mess.login_about_to_expire_title']);
    this.$timeoutModal.find('.modal-body').append(
      $('<p />').text(TYPO3.lang['mess.login_about_to_expire']),
      $('<div />', {class: 'progress'}).append(
        $('<div />', {
          class: 'progress-bar progress-bar-warning progress-bar-striped progress-bar-animated',
          role: 'progressbar',
          'aria-valuemin': '0',
          'aria-valuemax': '100',
        }).append(
          $('<span />', {class: 'sr-only'}),
        ),
      ),
    );
    this.$timeoutModal.find('.modal-footer').append(
      $('<button />', {
        class: 'btn btn-default',
        'data-action': 'logout',
      }).text(TYPO3.lang['mess.refresh_login_logout_button']).on('click', () => {
        top.location.href = this.logoutUrl;
      }),
      $('<button />', {
        class: 'btn btn-primary t3js-active',
        'data-action': 'refreshSession',
      }).text(TYPO3.lang['mess.refresh_login_refresh_button']).on('click', () => {
        new AjaxRequest(TYPO3.settings.ajaxUrls.login_timedout).get().then((): void => {
          this.hideTimeoutModal();
        });
      }),
    );
    this.registerDefaultModalEvents(this.$timeoutModal);

    $('body').append(this.$timeoutModal);
  }

  /**
   * Generates the login form displayed if the session has timed out.
   */
  protected initializeLoginForm(): void {
    if (TYPO3.configuration.showRefreshLoginPopup) {
      // dialog is not required if "showRefreshLoginPopup" is enabled
      return;
    }

    this.$loginForm = this.generateModal(MarkupIdentifiers.loginFormModal);
    this.$loginForm.addClass('modal-notice');
    let refresh_login_title = String(TYPO3.lang['mess.refresh_login_title']).replace('%s', TYPO3.configuration.username);
    this.$loginForm.find('.modal-header h4').text(refresh_login_title);
    this.$loginForm.find('.modal-body').append(
      $('<p />').text(TYPO3.lang['mess.login_expired']),
      $('<form />', {
        id: 'beLoginRefresh',
        method: 'POST',
        action: TYPO3.settings.ajaxUrls.login,
      }).append(
        $('<div />').append(
          $('<input />', {type: 'text', name: 'username', class: 'd-none', value: TYPO3.configuration.username}),
          $('<input />', {type: 'hidden', name: 'userident', id: 't3-loginrefresh-userident'})
        ),
        $('<div />', {class: 'form-group'}).append(
          $('<input />', {
            type: 'password',
            name: 'p_field',
            autofocus: 'autofocus',
            class: 'form-control',
            placeholder: TYPO3.lang['mess.refresh_login_password'],
          }),
        ),
      ),
    );
    // Added to disable DOM warnings in browser consoles
    this.$loginForm.find('.modal-body .d-none').attr('autocomplete', 'username');
    this.$loginForm.find('.modal-body .form-control').attr('autocomplete', 'current-password');
    this.$loginForm.find('.modal-footer').append(
      $('<a />', {
        href: this.logoutUrl,
        class: 'btn btn-default',
      }).text(TYPO3.lang['mess.refresh_exit_button']),
      $('<button />', {type: 'button', class: 'btn btn-primary', 'data-action': 'refreshSession'})
        .text(TYPO3.lang['mess.refresh_login_button'])
        .on('click', () => {
          this.$loginForm.find('form').trigger('submit');
        }),
    );
    this.registerDefaultModalEvents(this.$loginForm).on('submit', this.submitForm);
    $('body').append(this.$loginForm);
  }

  /**
   * Generates a modal dialog as template.
   *
   * @param {string} identifier
   * @returns {JQuery}
   */
  protected generateModal(identifier: string): JQuery {
    return $('<div />', {
      id: identifier,
      class: 't3js-modal ' + identifier + ' modal modal-type-default modal-severity-notice modal-style-light modal-size-small fade',
    }).append(
      $('<div />', {class: 'modal-dialog'}).append(
        $('<div />', {class: 'modal-content'}).append(
          $('<div />', {class: 'modal-header'}).append(
            $('<h4 />', {class: 'modal-title'}),
          ),
          $('<div />', {class: 'modal-body'}),
          $('<div />', {class: 'modal-footer'}),
        ),
      ),
    );
  }

  /**
   * Fills the progressbar attached to the given modal.
   */
  protected fillProgressbar($activeModal: JQuery): void {
    if (!this.isTimingOut) {
      return;
    }

    const max = 100;
    let current = 0;
    const $progressBar = $activeModal.find('.progress-bar');
    const $srText = $progressBar.children('.sr-only');

    const progress = setInterval(() => {
      const isOverdue = (current >= max);
      if (!this.isTimingOut || isOverdue) {
        clearInterval(progress);

        if (isOverdue) {
          // show login form
          this.hideTimeoutModal();
          this.showLoginForm();
        }

        // reset current
        current = 0;
      } else {
        current += 1;
      }

      const percentText = (current) + '%';
      $progressBar.css('width', percentText);
      $srText.text(percentText);
    },                           300);
  }

  /**
   * Creates additional data based on the security level and "submits" the form
   * via an AJAX request.
   *
   * @param {JQueryEventObject} event
   */
  protected submitForm = (event: JQueryEventObject): void => {
    event.preventDefault();

    const $form = this.$loginForm.find('form');
    const $passwordField = $form.find('input[name=p_field]');
    const $useridentField = $form.find('input[name=userident]');
    const passwordFieldValue = $passwordField.val();

    if (passwordFieldValue === '' && $useridentField.val() === '') {
      Notification.error(TYPO3.lang['mess.refresh_login_failed'], TYPO3.lang['mess.refresh_login_emptyPassword']);
      $passwordField.focus();
      return;
    }

    if (passwordFieldValue) {
      $useridentField.val(passwordFieldValue);
      $passwordField.val('');
    }

    const postData: any = {
      login_status: 'login',
    };
    $.each($form.serializeArray(), function (i: number, field: any): void {
      postData[field.name] = field.value;
    });
    new AjaxRequest($form.attr('action')).post(postData).then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      if (data.login.success) {
        // User is logged in
        this.hideLoginForm();
      } else {
        Notification.error(TYPO3.lang['mess.refresh_login_failed'], TYPO3.lang['mess.refresh_login_failed_message']);
        $passwordField.focus();
      }
    });
  }

  /**
   * Registers the (shown|hidden).bs.modal events.
   * If a modal is shown, the interval check is stopped. If the modal hides,
   * the interval check starts again.
   * This method is not invoked for the backend locked modal, because we still
   * need to check if the backend gets unlocked again.
   *
   * @param {JQuery} $modal
   * @returns {JQuery}
   */
  protected registerDefaultModalEvents($modal: JQuery): JQuery {
    $modal.on('hidden.bs.modal', () => {
      this.startTask();
    }).on('shown.bs.modal', () => {
      this.stopTask();
      // focus the button which was configured as active button
      this.$timeoutModal.find('.modal-footer .t3js-active').first().focus();
    });
    return $modal;
  }

  /**
   * Periodically called task that checks if
   *
   * - the user's backend session is about to expire
   * - the user's backend session has expired
   * - the backend got locked
   *
   * and opens a dialog.
   */
  protected checkActiveSession = (): void => {
    new AjaxRequest(TYPO3.settings.ajaxUrls.login_timedout).get().then(async (response: AjaxResponse): Promise<void> => {
      const data = await response.resolve();
      if (data.login.locked) {
        if (!this.backendIsLocked) {
          this.backendIsLocked = true;
          this.showBackendLockedModal();
        }
      } else {
        if (this.backendIsLocked) {
          this.backendIsLocked = false;
          this.hideBackendLockedModal();
        }
      }

      if (!this.backendIsLocked) {
        if (data.login.timed_out || data.login.will_time_out) {
          data.login.timed_out
            ? this.showLoginForm()
            : this.showTimeoutModal();
        }
      }
    });
  }

  private applyOptions(options: LoginRefreshOptions): void {
    if (options.intervalTime !== undefined) {
      this.setIntervalTime(options.intervalTime);
    }
    if (options.loginFramesetUrl !== undefined) {
      this.setLoginFramesetUrl(options.loginFramesetUrl);
    }
    if (options.logoutUrl !== undefined) {
      this.setLogoutUrl(options.logoutUrl);
    }
  }
}

let loginRefreshObject;
try {
  // fetch from opening window
  if (window.opener && window.opener.TYPO3 && window.opener.TYPO3.LoginRefresh) {
    loginRefreshObject = window.opener.TYPO3.LoginRefresh;
  }

  // fetch from parent
  if (parent && parent.window.TYPO3 && parent.window.TYPO3.LoginRefresh) {
    loginRefreshObject = parent.window.TYPO3.LoginRefresh;
  }

  // fetch object from outer frame
  if (top && top.TYPO3 && top.TYPO3.LoginRefresh) {
    loginRefreshObject = top.TYPO3.LoginRefresh;
  }
} catch {
  // This only happens if the opener, parent or top is some other url (eg a local file)
  // which loaded the current window. Then the browser's cross domain policy jumps in
  // and raises an exception.
  // For this case we are safe and we can create our global object below.
}

if (!loginRefreshObject) {
  loginRefreshObject = new LoginRefresh();

  // attach to global frame
  if (typeof TYPO3 !== 'undefined') {
    TYPO3.LoginRefresh = loginRefreshObject;
  }
}

export default loginRefreshObject;
