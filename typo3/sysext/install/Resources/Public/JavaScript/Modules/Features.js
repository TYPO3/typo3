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
 * Module: TYPO3/CMS/Install/Features
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Backend/Notification'
], function($, Router, Severity, Notification) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorModuleContent: '.t3js-features-content',
    selectorSaveTrigger: '.t3js-features-save',

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.getContent();

      currentModal.on('click', this.selectorSaveTrigger, function(e) {
        e.preventDefault();
        self.save();
      });
    },

    getContent: function() {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('featuresGetContent'),
        cache: false,
        success: function(data) {
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
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

    save: function() {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('features-save-token');
      var postData = {};
      $(this.currentModal.find(this.selectorModuleContent + ' form').serializeArray()).each(function() {
        postData[this.name] = this.value;
      });
      postData['install[action]'] = 'featuresSave';
      postData['install[token]'] = executeToken;
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        data: postData,
        cache: false,
        success: function(data) {
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              Notification.showMessage(element.title, element.message, element.severity);
            });
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    }
  };
});
