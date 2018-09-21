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
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Install/PasswordStrength',
  'TYPO3/CMS/Backend/Notification'
], function($, Router, Severity, PasswordStrength, Notification) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorModuleContent: '.t3js-module-content',
    selectorChangeForm: '#t3js-changeInstallToolPassword-form',
    selectorOutputContainer: '.t3js-changeInstallToolPassword-output',
    currentModal: {},

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.getData();

      currentModal.on('submit', this.selectorChangeForm, function(e) {
        e.preventDefault();
        self.change();
      });
      currentModal.on('click', '.t3-install-form-password-strength', function(e) {
        PasswordStrength.initialize('.t3-install-form-password-strength');
      });
    },

    getData: function() {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('changeInstallToolPasswordGetData'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            modalContent.empty().append(data.html);
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    change: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('install-tool-token');
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        data: {
          'install': {
            'action': 'changeInstallToolPassword',
            'token': executeToken,
            'password': self.currentModal.find('.t3js-changeInstallToolPassword-password').val(),
            'passwordCheck': self.currentModal.find('.t3js-changeInstallToolPassword-password-check').val()
          }
        },
        cache: false,
        success: function(data) {
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              Notification.showMessage('', element.message, element.severity);
            });
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        },
        complete: function() {
          self.currentModal.find('.t3js-changeInstallToolPassword-password,.t3js-changeInstallToolPassword-password-check').val('')
        }
      });
    }
  };
});
