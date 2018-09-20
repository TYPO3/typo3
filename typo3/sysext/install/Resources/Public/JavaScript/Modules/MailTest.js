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
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Backend/Notification',
  'bootstrap'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, Notification) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorModuleContent: '.t3js-module-content',
    selectorForm: '#t3js-mailTest-form',
    selectorEmail: '.t3js-mailTest-email',
    selectorOutputContainer: '.t3js-mailTest-output',
    currentModal: {},

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.getData();
      currentModal.on('submit', this.selectorForm, function(e) {
        e.preventDefault();
        self.send();
      });
    },

    getData: function() {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('mailTestGetData'),
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

    send: function() {
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('mail-test-token');
      var $outputContainer = this.currentModal.find(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.empty().html(message);
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        data: {
          'install': {
            'action': 'mailTest',
            'token': executeToken,
            'email': this.currentModal.find('.t3js-mailTest-email').val()
          }
        },
        cache: false,
        success: function(data) {
          $outputContainer.empty();
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              var message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.html(message);
            });
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          // 500 can happen here if the mail configuration is broken
          Notification.error('Something went wrong');
        }
      });
    }
  };
});
