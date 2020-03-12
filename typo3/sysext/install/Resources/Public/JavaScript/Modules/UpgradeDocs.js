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
 * Module: TYPO3/CMS/Install/UpgradeDocs
 */
define([
  'jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Install/ProgressBar',
  'TYPO3/CMS/Install/InfoBox',
  'TYPO3/CMS/Install/Severity',
  'TYPO3/CMS/Backend/Notification',
  'bootstrap',
  'chosen',
  'jquery.clearable'
], function($, Router, ProgressBar, InfoBox, Severity, Notification) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorModuleContent: '.t3js-module-content',
    selectorFulltextSearch: '.t3js-upgradeDocs-fulltext-search',
    selectorChosenField: '.t3js-upgradeDocs-chosen-select',
    selectorChangeLogsForVersionContainer: '.t3js-version-changes',
    selectorChangeLogsForVersion: '.t3js-changelog-list',
    selectorUpgradeDoc: '.t3js-upgrade-doc',

    chosenField: null,
    fulltextSearchField: null,

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      var isInIframe = (window.location !== window.parent.location);
      if (isInIframe) {
        top.require(['TYPO3/CMS/Install/chosen.jquery.min'], function () {
          self.getContent();
        });
      }
      else {
        this.getContent();
      }

      // Mark a file as read
      currentModal.on('click',  '.t3js-upgradeDocs-markRead', function(e) {
        self.markRead(e.target);
      });
      currentModal.on('click',  '.t3js-upgradeDocs-unmarkRead', function(e) {
        self.unmarkRead(e.target);
      });

      // Make jquerys "contains" work case-insensitive
      jQuery.expr[':'].contains = jQuery.expr.createPseudo(function(arg) {
        return function(elem) {
          return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
        };
      });
    },

    getContent: function() {
      var self = this;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      modalContent.on('show.bs.collapse', this.selectorUpgradeDoc, function (e) {
        self.renderTags($(e.currentTarget));
      });

      $.ajax({
        url: Router.getUrl('upgradeDocsGetContent'),
        cache: false,
        success: function(data) {
          if (data.success === true && data.html !== 'undefined' && data.html.length > 0) {
            modalContent.empty().append(data.html);

            self.initializeFullTextSearch();
            self.initializeChosenSelector();
            self.loadChangelogs();
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    loadChangelogs: function() {
      var self = this;
      var promises = [];
      var modalContent = this.currentModal.find(this.selectorModalBody);
      this.currentModal.find(this.selectorChangeLogsForVersionContainer).each(function(index, el) {
        var $request = $.ajax({
          url: Router.getUrl('upgradeDocsGetChangelogForVersion'),
          cache: false,
          data: {
            install: {
              version: el.dataset.version
            }
          },
          success: function(data) {
            if (data.success === true) {
              var $panelGroup = $(el);
              var $container = $panelGroup.find(self.selectorChangeLogsForVersion);
              $container.html(data.html);
              self.moveNotRelevantDocuments($container);

              // Remove loading spinner form panel
              $panelGroup.find('.t3js-panel-loading').remove();
            } else {
              Notification.error('Something went wrong');
            }
          },
          error: function(xhr) {
            Router.handleAjaxError(xhr, modalContent);
          }
        });

        promises.push($request);
      });

      $.when.apply($, promises).done(function () {
        self.fulltextSearchField.prop('disabled', false);
        self.appendItemsToChosenSelector();
      });
    },

    initializeFullTextSearch: function() {
      var self = this;
      this.fulltextSearchField = this.currentModal.find(this.selectorFulltextSearch);
      this.fulltextSearchField.clearable().focus();
      this.initializeChosenSelector();
      this.fulltextSearchField.on('keyup', function() {
        self.combinedFilterSearch();
      });
    },

    initializeChosenSelector: function() {
      var self = this;
      this.chosenField = this.currentModal.find(this.selectorModalBody).find(this.selectorChosenField);

      var config = {
        '.chosen-select': {width: "100%", placeholder_text_multiple: "tags"},
        '.chosen-select-deselect': {allow_single_deselect: true},
        '.chosen-select-no-single': {disable_search_threshold: 10},
        '.chosen-select-no-results': {no_results_text: 'Oops, nothing found!'},
        '.chosen-select-width': {width: "100%"}
      };
      for (var selector in config) {
        this.currentModal.find(selector).chosen(config[selector]);
      }
      this.chosenField.on('change', function() {
        self.combinedFilterSearch();
      });
    },

    /**
     * Appends tags to the chosen selector
     */
    appendItemsToChosenSelector: function() {
      var self = this;
      var tagString = '';
      $(this.currentModal.find(this.selectorUpgradeDoc)).each(function() {
        tagString += $(this).data('item-tags') + ',';
      });
      var tagArray = this.trimExplodeAndUnique(',', tagString).sort(function(a, b) {
        // Sort case-insensitive by name
        return a.toLowerCase().localeCompare(b.toLowerCase());
      });
      this.chosenField.prop('disabled', false);
      $.each(tagArray, function(i, tag) {
        self.chosenField.append($('<option>').text(tag));
      });
      this.chosenField.trigger('chosen:updated');
    },

    combinedFilterSearch: function() {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var $items = modalContent.find('div.item');
      if (this.chosenField.val().length < 1 && this.fulltextSearchField.val().length < 1) {
        this.currentModal.find('.panel-version .panel-collapse.in').collapse('hide');
        $items.removeClass('hidden searchhit filterhit');
        return false;
      }
      $items.addClass('hidden').removeClass('searchhit filterhit');

      // apply tags
      if (this.chosenField.val().length > 0) {
        $items
          .addClass('hidden')
          .removeClass('filterhit');
        var orTags = [];
        var andTags = [];
        $.each(this.chosenField.val(), function(index, item) {
          var tagFilter = '[data-item-tags*="' + item + '"]';
          if (item.indexOf(':') > 0) {
            orTags.push(tagFilter);
          } else {
            andTags.push(tagFilter);
          }
        });
        var andString = andTags.join('');
        var tags = [];
        if (orTags.length) {
          for (var i = 0; i < orTags.length; i++) {
            tags.push(andString + orTags[i]);
          }
        } else {
          tags.push(andString);
        }
        var tagSelection = tags.join(',');
        modalContent.find(tagSelection)
          .removeClass('hidden')
          .addClass('searchhit filterhit');
      } else {
        $items
          .addClass('filterhit')
          .removeClass('hidden');
      }
      // apply fulltext search
      var typedQuery = this.fulltextSearchField.val();
      modalContent.find('div.item.filterhit').each(function() {
        var $item = $(this);
        if ($(':contains(' + typedQuery + ')', $item).length > 0 || $('input[value*="' + typedQuery + '"]', $item).length > 0) {
          $item.removeClass('hidden').addClass('searchhit');
        } else {
          $item.removeClass('searchhit').addClass('hidden');
        }
      });

      modalContent.find('.searchhit').closest('.panel-collapse').collapse('show');

      //check for empty panels
      modalContent.find('.panel-version').each(function() {
        if ($(this).find('.searchhit', '.filterhit').length < 1) {
          $(this).find(' > .panel-collapse').collapse('hide');
        }
      });
    },

    renderTags: function($upgradeDocumentContainer) {
      var $tagContainer = $upgradeDocumentContainer.find('.t3js-tags');
      if ($tagContainer.children().length === 0) {
        var tags = $upgradeDocumentContainer.data('item-tags').split(',');
        tags.forEach(function (value) {
          $tagContainer.append($('<span />', {'class': 'label'}).text(value));
        });
      }
    },

    /**
     * Moves all documents that are either read or not affected
     *
     * @param {JQuery} $container
     */
    moveNotRelevantDocuments: function($container) {
      $container.find('[data-item-state="read"]').appendTo(this.currentModal.find('.panel-body-read'));
      $container.find('[data-item-state="notAffected"]').appendTo(this.currentModal.find('.panel-body-not-affected'));
    },

    markRead: function(element) {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('upgrade-docs-mark-read-token');
      var $button = $(element).closest('a');
      $button.toggleClass('t3js-upgradeDocs-unmarkRead t3js-upgradeDocs-markRead');
      $button.find('i').toggleClass('fa-check fa-ban');
      $button.closest('.panel').appendTo(this.currentModal.find('.panel-body-read'));
      $.ajax({
        method: 'POST',
        url: Router.getUrl(),
        data: {
          'install': {
            'ignoreFile': $button.data('filepath'),
            'token': executeToken,
            'action': 'upgradeDocsMarkRead'
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    unmarkRead: function(element) {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('upgrade-docs-unmark-read-token');
      var $button = $(element).closest('a');
      var version = $button.closest('.panel').data('item-version');
      $button.toggleClass('t3js-upgradeDocs-markRead t3js-upgradeDocs-unmarkRead');
      $button.find('i').toggleClass('fa-check fa-ban');
      $button.closest('.panel').appendTo(this.currentModal.find('*[data-group-version="' + version + '"] .panel-body'));
      $.ajax({
        method: 'POST',
        url: Router.getUrl(),
        data: {
          'install': {
            'ignoreFile': $button.data('filepath'),
            'token': executeToken,
            action: 'upgradeDocsUnmarkRead'
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    trimExplodeAndUnique: function(delimiter, string) {
      var result = [];
      var items = string.split(delimiter);
      for (var i = 0; i < items.length; i++) {
        var item = items[i].trim();
        if (item.length > 0) {
          if ($.inArray(item, result) === -1) {
            result.push(item);
          }
        }
      }
      return result;
    }
  };
});
