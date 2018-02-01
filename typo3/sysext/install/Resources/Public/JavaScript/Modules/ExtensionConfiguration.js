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
  'bootstrap'
], function($, Router, FlashMessage, ProgressBar, InfoBox, Severity) {
  'use strict';

  return {
    selectorGridderOpener: 't3js-extensionConfiguration-open',
    selectorContentContainer: '.t3js-extensionConfiguration-content',
    selectorFormListener: '.t3js-extensionConfiguration-form',
    selectorWriteToken: '#t3js-extensionConfiguration-write-token',
    selectorOutputContainer: '.t3js-extensionConfiguration-output',
    selectorSearchInput: '.t3js-extensionConfiguration-search',

    initialize: function() {
      var self = this;

      // Get extension configuration list on card open
      $(document).on('cardlayout:card-opened', function(event, $card) {
        if ($card.hasClass(self.selectorGridderOpener)) {
          self.getContent();
        }
      });

      // Focus search field on certain user interactions
      $(document).on('keydown', function(e) {
        var $searchInput = $(self.selectorSearchInput);
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
      $(document).on('keyup', this.selectorSearchInput, function() {
        var typedQuery = $(this).val();
        var $searchInput = $(self.selectorSearchInput);
        $('.search-item').each(function() {
          var $item = $(this);
          if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
            $item.removeClass('hidden').addClass('searchhit');
          } else {
            $item.removeClass('searchhit').addClass('hidden');
          }
        });
        $('.searchhit').collapse('show');
        // Make search field clearable
        require(['jquery.clearable'], function() {
          $searchInput.clearable().focus();
        });
      });

      $(document).on('submit', this.selectorFormListener, function(e) {
        e.preventDefault();
        self.write($(this));
      });
    },

    getContent: function() {
      var self = this;
      var outputContainer = $(this.selectorContentContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      outputContainer.empty().html(message);
      $.ajax({
        url: Router.getUrl('extensionConfigurationGetContent'),
        cache: false,
        success: function(data) {
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            outputContainer.empty().append(data.html);
            self.initializeWrap();
          } else {
            var message = InfoBox.render(Severity.error, 'Something went wrong', '');
            outputContainer.empty().append(message);
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    },

    /**
     * Submit the form and show the result message
     *
     * @param {jQuery} $form The form of the current extension
     */
    write: function($form) {
      var $outputContainer = $(this.selectorOutputContainer);
      var message = ProgressBar.render(Severity.loading, 'Loading...', '');
      $outputContainer.append(message);
      var extensionConfiguration = {};
      $.each($form.serializeArray(), function() {
        extensionConfiguration[this.name] = this.value;
      });
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        data: {
          'install': {
            'token': $(this.selectorWriteToken).text(),
            'action': 'extensionConfigurationWrite',
            'extensionKey': $form.attr('data-extensionKey'),
            'extensionConfiguration': extensionConfiguration
          }
        },
        success: function(data) {
          if (data.success === true && Array.isArray(data.status)) {
            data.status.forEach(function(element) {
              var message = InfoBox.render(element.severity, element.title, element.message);
              $outputContainer.empty().append(message);
            });
          } else {
            var message = InfoBox.render(Severity.error, 'Something went wrong', '');
            $outputContainer.empty().html(message);
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      }).always(function() {
        $outputContainer.find('.alert-loading').remove();
      });
    },

    /**
     * configuration properties
     */
    initializeWrap: function() {
      $('.t3js-emconf-offset').each(function() {
        var $me = $(this),
          $parent = $me.parent(),
          id = $me.attr('id'),
          val = $me.attr('value'),
          valArr = val.split(',');

        $me.attr('data-offsetfield-x', '#' + id + '_offset_x')
          .attr('data-offsetfield-y', '#' + id + '_offset_y')
          .wrap('<div class="hidden"></div>');

        var elementX = '' +
          '<div class="form-multigroup-item">' +
          '<div class="input-group">' +
          '<div class="input-group-addon">x</div>' +
          '<input id="' + id + '_offset_x" class="form-control t3js-emconf-offsetfield" data-target="#' + id + '" value="' + $.trim(valArr[0]) + '">' +
          '</div>' +
          '</div>';
        var elementY = '' +
          '<div class="form-multigroup-item">' +
          '<div class="input-group">' +
          '<div class="input-group-addon">y</div>' +
          '<input id="' + id + '_offset_y" class="form-control t3js-emconf-offsetfield" data-target="#' + id + '" value="' + $.trim(valArr[1]) + '">' +
          '</div>' +
          '</div>';

        var offsetGroup = '<div class="form-multigroup-wrap">' + elementX + elementY + '</div>';
        $parent.append(offsetGroup);
        $parent.find('.t3js-emconf-offset').keyup(function() {
          var $target = $($(this).data('target'));
          $target.attr(
            'value',
            $($target.data('offsetfield-x')).val() + ',' + $($target.data('offsetfield-y')).val()
          );
        });
      });

      $('.t3js-emconf-wrap').each(function() {
        var $me = $(this),
          $parent = $me.parent(),
          id = $me.attr('id'),
          val = $me.attr('value'),
          valArr = val.split('|');

        $me.attr('data-wrapfield-start', '#' + id + '_wrap_start')
          .attr('data-wrapfield-end', '#' + id + '_wrap_end')
          .wrap('<div class="hidden"></div>');

        var elementStart = '' +
          '<div class="form-multigroup-item">' +
          '<input id="' + id + '_wrap_start" class="form-control t3js-emconf-wrapfield" data-target="#' + id + '" value="' + $.trim(valArr[0]) + '">' +
          '</div>';
        var elementEnd = '' +
          '<div class="form-multigroup-item">' +
          '<input id="' + id + '_wrap_end" class="form-control t3js-emconf-wrapfield" data-target="#' + id + '" value="' + $.trim(valArr[1]) + '">' +
          '</div>';

        var wrapGroup = '<div class="form-multigroup-wrap">' + elementStart + elementEnd + '</div>';
        $parent.append(wrapGroup);
        $parent.find('.t3js-emconf-wrapfield').keyup(function() {
          var $target = $($(this).data('target'));
          $target.attr(
            'value',
            $($target.data('wrapfield-start')).val() + '|' + $($target.data('wrapfield-end')).val()
          );
        });
      });
    }
  };
});
