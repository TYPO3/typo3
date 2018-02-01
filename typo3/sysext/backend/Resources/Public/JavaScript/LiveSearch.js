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
 * Module: TYPO3/CMS/Backend/LiveSearch
 * Global search to deal with everything in the backend that is search-related
 * @exports TYPO3/CMS/Backend/LiveSearch
 */
define([
  'jquery',
  'TYPO3/CMS/Backend/Viewport',
  'jquery/autocomplete',
  'TYPO3/CMS/Backend/jquery.clearable'
], function($, Viewport) {
  'use strict';

  var containerSelector = '#typo3-cms-backend-backend-toolbaritems-livesearchtoolbaritem';
  var toolbarItem = '.t3js-toolbar-item-search';
  var dropdownToggle = '.t3js-toolbar-search-dropdowntoggle';
  var searchFieldSelector = '.t3js-topbar-navigation-search-field';
  var formSelector = '.t3js-topbar-navigation-search';
  var url = TYPO3.settings.ajaxUrls['livesearch'];

  Viewport.Topbar.Toolbar.registerEvent(function() {
    $(searchFieldSelector).autocomplete({
      // ajax options
      serviceUrl: url,
      paramName: 'q',
      dataType: 'json',
      minChars: 2,
      width: '100%',
      groupBy: 'typeLabel',
      containerClass: toolbarItem.substr(1, toolbarItem.length),
      appendTo: containerSelector + ' .dropdown-menu',
      forceFixPosition: false,
      preserveInput: true,
      showNoSuggestionNotice: true,
      triggerSelectOnValidInput: false,
      preventBadQueries: false,
      noSuggestionNotice: '<h3 class="dropdown-headline">' + TYPO3.LLL.liveSearch.listEmptyText + '</h3>'
      + '<p>' + TYPO3.LLL.liveSearch.helpTitle + '</p>'
      + '<hr>'
      + '<p>' + TYPO3.LLL.liveSearch.helpDescription + '<br>' + TYPO3.LLL.liveSearch.helpDescriptionPages + '</p>',
      // put the AJAX results in the right format
      transformResult: function(response) {
        return {
          suggestions: $.map(response, function(dataItem) {
            return {value: dataItem.title, data: dataItem};
          })
        };
      },
      formatGroup: function(suggestion, category, i) {
        var html = '';
        // add a divider if it's not the first group
        if (i > 0) {
          html = '<hr>';
        }
        return html + '<h3 class="dropdown-headline">' + category + '</h3>';
      },
      // Rendering of each item
      formatResult: function(suggestion, value, i) {
        return ''
          + '<div class="dropdown-table">'
          + '<div class="dropdown-table-row">'
          + '<div class="dropdown-table-column dropdown-table-icon">' + suggestion.data.iconHTML + '</div>'
          + '<div class="dropdown-table-column dropdown-table-title">'
          + '<a class="dropdown-table-title-ellipsis dropdown-list-link" href="#" data-pageid="' + suggestion.data.pageId + '" data-target="' + suggestion.data.editLink + '">'
          + suggestion.data.title
          + '</a>'
          + '</div>'
          + '</div>'
          + '</div>'
          + '';
      },
      onSearchComplete: function(query, suggestions) {
        if (!$(toolbarItem).hasClass('open') && $(searchFieldSelector).val().length > 1) {
          $(dropdownToggle).dropdown('toggle');
          $(searchFieldSelector).focus();
        }
      },
      beforeRender: function(container) {
        container.append('<hr><div>' +
          '<a href="#" class="btn btn-primary pull-right t3js-live-search-show-all">' +
          TYPO3.LLL.liveSearch.showAllResults +
          '</a>' +
          '</div>');
        if (!$(toolbarItem).hasClass('open')) {
          $(dropdownToggle).dropdown('toggle');
          $(searchFieldSelector).focus();
        }
      },
      onHide: function() {
        if ($(toolbarItem).hasClass('open')) {
          $(dropdownToggle).dropdown('toggle');
        }
      }
    });

    // set up the events
    $(containerSelector).on('click', '.t3js-live-search-show-all', function(evt) {
      evt.preventDefault();
      TYPO3.ModuleMenu.App.showModule('web_list', 'id=0&search_levels=-1&search_field=' + encodeURIComponent($(searchFieldSelector).val()));
      $(searchFieldSelector).val('').trigger('change');
    });
    if ($(searchFieldSelector).length) {
      $('.' + $(searchFieldSelector).autocomplete().options.containerClass).on('click.autocomplete', '.dropdown-list-link', function(evt) {
        evt.preventDefault();
        jump($(this).data('target'), 'web_list', 'web', $(this).data('pageid'));
        $(searchFieldSelector).val('').trigger('change');
      });
    }

    // Unset height, width and z-index
    $(toolbarItem).removeAttr('style');

    $(searchFieldSelector).clearable({
      onClear: function() {
        if ($(toolbarItem).hasClass('open')) {
          $(dropdownToggle).dropdown('toggle');
        }
      }
    });

    // Prevent submitting the search form
    $(formSelector).submit(function(evt) {
      evt.preventDefault();
    });
  });

});
