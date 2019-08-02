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
 * Module: TYPO3/CMS/Recordlist/Recordlist
 * Usability improvements for the record list
 */
define(['jquery', 'TYPO3/CMS/Backend/Storage/Persistent', 'TYPO3/CMS/Backend/Icons'], function($, PersistentStorage, Icons) {
  'use strict';

  /**
   * @type {Object}
   * @exports TYPO3/CMS/Recordlist/Recordlist
   */
  var Recordlist = {
    identifier: {
      entity: '.t3js-entity',
      toggle: '.t3js-toggle-recordlist',
      localize: '.t3js-action-localize',
      icons: {
        collapse: 'actions-view-list-collapse',
        expand: 'actions-view-list-expand',
        editMultiple: '.t3js-record-edit-multiple'
      }
    }
  };

  /**
   * @param {MouseEvent} e
   */
  Recordlist.toggleClick = function(e) {
    e.preventDefault();

    var $me = $(this),
      table = $me.data('table'),
      $target = $($me.data('target')),
      isExpanded = $target.data('state') === 'expanded',
      $collapseIcon = $me.find('.collapseIcon'),
      toggleIcon = isExpanded ? Recordlist.identifier.icons.expand : Recordlist.identifier.icons.collapse;

    Icons.getIcon(toggleIcon, Icons.sizes.small).done(function(toggleIcon) {
      $collapseIcon.html(toggleIcon);
    });

    // Store collapse state in UC
    var storedModuleDataList = {};

    if (PersistentStorage.isset('moduleData.list')) {
      storedModuleDataList = PersistentStorage.get('moduleData.list');
    }

    var collapseConfig = {};
    collapseConfig[table] = isExpanded ? 1 : 0;

    $.extend(true, storedModuleDataList, collapseConfig);
    PersistentStorage.set('moduleData.list', storedModuleDataList).done(function() {
      $target.data('state', isExpanded ? 'collapsed' : 'expanded');
    });
  };

  /**
   * Handles editing multiple records.
   *
   * @param {MouseEvent} event
   */
  Recordlist.onEditMultiple = function(event) {
    event.preventDefault();

    var $tableContainer, tableName, entityIdentifiers, uri, patterns;

    $tableContainer = $(this).closest('[data-table]');
    if ($tableContainer.length === 0) {
      return;
    }

    uri = $(this).data('uri');
    tableName = $tableContainer.data('table');
    entityIdentifiers = $tableContainer
      .find(Recordlist.identifier.entity + '[data-uid][data-table="' + tableName + '"]')
      .map(function(index, entity) {
        return $(entity).data('uid');
      })
      .toArray()
      .join(',');

    patterns = uri.match(/{[^}]+}/g);
    $.each(patterns, function(patternIndex, pattern) {
      var expression = pattern.substr(1, pattern.length - 2);
      var pipes = expression.split(':');
      var name = pipes.shift();
      var value;

      switch (name) {
        case 'entityIdentifiers':
          value = entityIdentifiers;
          break;
        case 'T3_THIS_LOCATION':
          value = T3_THIS_LOCATION;
          break;
        default:
          return;
      }

      $.each(pipes, function(pipeIndex, pipe) {
        if (pipe === 'editList') {
          value = editList(tableName, value);
        }
      });

      uri = uri.replace(pattern, value);
    });

    window.location.href = uri;
  };

  Recordlist.disableButton = function (event) {
    var $me = $(event.currentTarget);

    $me.prop('disable', true).addClass('disabled');
  };

  $(document).on('click', Recordlist.identifier.toggle, Recordlist.toggleClick);
  $(document).on('click', Recordlist.identifier.icons.editMultiple, Recordlist.onEditMultiple);
  $(document).on('click', Recordlist.identifier.localize, Recordlist.disableButton);

  return Recordlist;
});
