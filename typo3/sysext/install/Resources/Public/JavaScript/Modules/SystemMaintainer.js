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
 * Module: TYPO3/CMS/Install/SystemMaintainer
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Backend/Notification',
  'bootstrap',
  'chosen'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, Notification) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorModuleContent: '.t3js-module-content',
    selectorWriteTrigger: '.t3js-systemMaintainer-write',
    selectorOutputContainer: '.t3js-systemMaintainer-output',
    selectorChosenContainer: '.t3js-systemMaintainer-chosen',
    selectorChosenField: '.t3js-systemMaintainer-chosen-select',
    currentModal: {},

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      var isInIframe = window.location !== window.parent.location;
      if (isInIframe) {
        top.require(['TYPO3/CMS/Install/chosen.jquery.min'], function () {
          self.getList();
        });
      }
      else {
        this.getList();
      }

      currentModal.on('click', this.selectorWriteTrigger, function(e) {
        e.preventDefault();
        self.write();
      });

    },

    getList: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('systemMaintainerGetList'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                Notification.success(element.title, element.message);
              });
            }
            modalContent.html((data.html));
            if (Array.isArray(data.users)) {
              data.users.forEach(function(element) {
                var name = element.username;
                if (element.disable) {
                  name = '[DISABLED] ' + name;
                }
                var selected = '';
                if (element.isSystemMaintainer) {
                  selected = 'selected="selected"';
                }
                modalContent.find(self.selectorChosenField).append(
                  '<option value="' + element.uid + '" ' + selected + '>' + name + '</option>'
                );
              });
            }
            var config = {
              '.t3js-systemMaintainer-chosen-select': {width: "100%", placeholder_text_multiple: "users"}
            };

            for (var selector in config) {
              modalContent.find(selector).chosen(config[selector]);
            }
            modalContent.find(self.selectorChosenContainer).show();
            modalContent.find(self.selectorChosenField).trigger('chosen:updated');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });


    },

    write: function() {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('system-maintainer-write-token');
      var selectedUsers = this.currentModal.find(this.selectorChosenField).val();
      $.ajax({
        method: 'POST',
        url: Router.getUrl(),
        data: {
          'install': {
            'users': selectedUsers,
            'token': executeToken,
            'action': 'systemMaintainerWrite'
          }
        },
        success: function(data) {
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                Notification.success(element.title, element.message);
              });
            }
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
