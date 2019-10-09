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
 * Module: TYPO3/CMS/Install/ExtensionConfiguration
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/FlashMessage',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Backend/ModuleMenu',
  'bootstrap'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity, Notification, ModuleMenu) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorModuleContent: '.t3js-module-content',
    selectorFormListener: '.t3js-extensionConfiguration-form',
    selectorOutputContainer: '.t3js-extensionConfiguration-output',
    selectorSearchInput: '.t3js-extensionConfiguration-search',

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.getContent();

      // Focus search field on certain user interactions
      currentModal.on('keydown', function(e) {
        var $searchInput = currentModal.find(self.selectorSearchInput);
        if (e.ctrlKey || e.metaKey) {
          // Focus search field on ctrl-f
          switch (String.fromCharCode(e.which).toLowerCase()) {
            case 'f':
              e.preventDefault();
              $searchInput.focus();
              break;
          }
        } else if (e.keyCode === 27) {
          // Clear search on ESC key
          e.preventDefault();
          $searchInput.val('').focus();
        }
      });

      // Perform expand collapse on search matches
      currentModal.on('keyup', this.selectorSearchInput, function(e) {
        var typedQuery = $(e.target).val();
        var $searchInput = currentModal.find(self.selectorSearchInput);
        currentModal.find('.search-item').each(function() {
          var $item = $(this);
          if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
            $item.removeClass('hidden').addClass('searchhit');
          } else {
            $item.removeClass('searchhit').addClass('hidden');
          }
        });
        currentModal.find('.searchhit').collapse('show');
        // Make search field clearable
        require(['jquery.clearable'], function() {
          $searchInput.clearable().focus();
        });
      });

      currentModal.on('submit', this.selectorFormListener, function(e) {
        e.preventDefault();
        self.write($(this));
      });
    },

    getContent: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('extensionConfigurationGetContent'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            if (Array.isArray(data.status)) {
              data.status.forEach(function(element) {
                Notification.success(element.title, element.message);
              });
            }
            modalContent.html(data.html);
            self.initializeWrap();
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    /**
     * Submit the form and show the result message
     *
     * @param {jQuery} $form The form of the current extension
     */
    write: function($form) {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-configuration-write-token');
      var extensionConfiguration = {};
      $.each($form.serializeArray(), function() {
        extensionConfiguration[this.name] = this.value;
      });

      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        data: {
          'install': {
            'token': executeToken,
            'action': 'extensionConfigurationWrite',
            'extensionKey': $form.attr('data-extensionKey'),
            'extensionConfiguration': extensionConfiguration
          }
        },
        success: function(data) {
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              Notification.showMessage(element.title, element.message, element.severity);
            });
            if ($('body').data('context') === 'backend') {
              ModuleMenu.App.refreshMenu();
            }
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      }).always(function() {

      });
    },

    /**
     * configuration properties
     */
    initializeWrap: function() {
      this.currentModal.find('.t3js-emconf-offset').each(function() {
        var $me = $(this);
        var $parent = $me.parent();
        var id = $me.attr('id');
        var val = $me.attr('value');
        var valArr = val.split(',');

        $me
          .attr('data-offsetfield-x', '#' + id + '_offset_x')
          .attr('data-offsetfield-y', '#' + id + '_offset_y')
          .wrap('<div class="hidden"></div>');

        var elementX = $('<div>', {'class': 'form-multigroup-item'}).append(
            $('<div>', {'class': 'input-group'}).append(
              $('<div>', {'class': 'input-group-addon'}).text('x'),
              $('<input>', {
                'id': id + '_offset_x',
                'class': 'form-control t3js-emconf-offsetfield',
                'data-target': '#' + id,
                'value': $.trim(valArr[0])
              })
            )
        );
        var elementY = $('<div>', {'class': 'form-multigroup-item'}).append(
            $('<div>', {'class': 'input-group'}).append(
              $('<div>', {'class': 'input-group-addon'}).text('y'),
              $('<input>', {
                'id': id + '_offset_y',
                'class': 'form-control t3js-emconf-offsetfield',
                'data-target': '#' + id,
                'value': $.trim(valArr[1])
              })
            )
        );

        var offsetGroup = $('<div>', {'class': 'form-multigroup-wrap'}).append(elementX, elementY);
        $parent.append(offsetGroup);
        $parent.find('.t3js-emconf-offsetfield').keyup(function() {
          var $target = $parent.find($(this).data('target'));
          $target.val($parent.find($target.data('offsetfield-x')).val() + ',' + $parent.find($target.data('offsetfield-y')).val());
        });
      });

      this.currentModal.find('.t3js-emconf-wrap').each(function() {
        var $me = $(this),
          $parent = $me.parent(),
          id = $me.attr('id'),
          val = $me.attr('value'),
          valArr = val.split('|');

        $me.attr('data-wrapfield-start', '#' + id + '_wrap_start')
          .attr('data-wrapfield-end', '#' + id + '_wrap_end')
          .wrap('<div class="hidden"></div>');

        var wrapGroup = $('<div>', {'class': 'form-multigroup-wrap'}).append(
          $('<div>', {'class': 'form-multigroup-item'}).append(
            $('<input>', {
              'id': id + '_wrap_start',
              'class': 'form-control t3js-emconf-wrapfield',
              'data-target': '#' + id,
              'value': $.trim(valArr[0])
            })
          ),
          $('<div>', {'class': 'form-multigroup-item'}).append(
            $('<input>', {
              'id': id + '_wrap_end',
              'class': 'form-control t3js-emconf-wrapfield',
              'data-target': '#' + id,
              'value': $.trim(valArr[1])
            })
          )
        );
        $parent.append(wrapGroup);
        $parent.find('.t3js-emconf-wrapfield').keyup(function() {
          var $target = $parent.find($(this).data('target'));
          $target.val($parent.find($target.data('wrapfield-start')).val() + '|' + $parent.find($target.data('wrapfield-end')).val());
        });
      });
    }
  };
});
