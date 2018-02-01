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
 * Module: TYPO3/CMS/Backend/AjaxDataHandler
 * AjaxDataHandler - Javascript functions to work with AJAX and interacting with tce_db.php
 */
define(['jquery',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Icons',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Backend/Severity'
], function($, Modal, Icons, Notification, Severity) {
  'use strict';

  /**
   *
   * @type {{identifier: {hide: string, delete: string, icon: string}}}
   * @exports TYPO3/CMS/Backend/AjaxDataHandler
   */
  var AjaxDataHandler = {
    identifier: {
      hide: '.t3js-record-hide',
      delete: '.t3js-record-delete',
      icon: '.t3js-icon'
    }
  };

  /**
   * generic function to call from the outside the script and validate directly showing errors
   *
   * @param {Object} parameters
   * @return {Promise<Object>} a jQuery deferred object (promise)
   */
  AjaxDataHandler.process = function(parameters) {
    return AjaxDataHandler._call(parameters).done(function(result) {
      if (result.hasErrors) {
        AjaxDataHandler.handleErrors(result);
      }
    });
  };

  /**
   *
   */
  AjaxDataHandler.initialize = function() {

    // HIDE/UNHIDE: click events for all action icons to hide/unhide
    $(document).on('click', AjaxDataHandler.identifier.hide, function(evt) {
      evt.preventDefault();
      var $anchorElement = $(this);
      var $iconElement = $anchorElement.find(AjaxDataHandler.identifier.icon);
      var $rowElement = $anchorElement.closest('tr[data-uid]');
      var params = $anchorElement.data('params');

      // add a spinner
      AjaxDataHandler._showSpinnerIcon($iconElement);

      // make the AJAX call to toggle the visibility
      AjaxDataHandler._call(params).done(function(result) {
        // print messages on errors
        if (result.hasErrors) {
          AjaxDataHandler.handleErrors(result);
        } else {
          // adjust overlay icon
          AjaxDataHandler.toggleRow($rowElement);
        }
      });
    });

    // DELETE: click events for all action icons to delete
    $(document).on('click', AjaxDataHandler.identifier.delete, function(evt) {
      evt.preventDefault();
      var $anchorElement = $(this);
      var $modal = Modal.confirm($anchorElement.data('title'), $anchorElement.data('message'), Severity.warning, [
        {
          text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel'
        },
        {
          text: $(this).data('button-ok-text') || TYPO3.lang['button.delete'] || 'Delete',
          btnClass: 'btn-warning',
          name: 'delete'
        }
      ]);
      $modal.on('button.clicked', function(e) {
        if (e.target.name === 'cancel') {
          Modal.dismiss();
        } else if (e.target.name === 'delete') {
          Modal.dismiss();
          AjaxDataHandler.deleteRecord($anchorElement);
        }
      });
    });
  };

  /**
   * Toggle row visibility after record has been changed
   *
   * @param {Object} $rowElement
   */
  AjaxDataHandler.toggleRow = function($rowElement) {
    var $anchorElement = $rowElement.find(AjaxDataHandler.identifier.hide);
    var table = $anchorElement.closest('table[data-table]').data('table');
    var params = $anchorElement.data('params');
    var nextParams, nextState, iconName;

    if ($anchorElement.data('state') === 'hidden') {
      nextState = 'visible';
      nextParams = params.replace('=0', '=1');
      iconName = 'actions-edit-hide';
    } else {
      nextState = 'hidden';
      nextParams = params.replace('=1', '=0');
      iconName = 'actions-edit-unhide';
    }
    $anchorElement.data('state', nextState).data('params', nextParams);

    // Update tooltip title
    $anchorElement.tooltip('hide').one('hidden.bs.tooltip', function() {
      var nextTitle = $anchorElement.data('toggleTitle');
      // Bootstrap Tooltip internally uses only .attr('data-original-title')
      $anchorElement
        .data('toggleTitle', $anchorElement.attr('data-original-title'))
        .attr('data-original-title', nextTitle)
        .tooltip('show');
    });

    var $iconElement = $anchorElement.find(AjaxDataHandler.identifier.icon);
    Icons.getIcon(iconName, Icons.sizes.small).done(function(icon) {
      $iconElement.replaceWith(icon);
    });

    // Set overlay for the record icon
    var $recordIcon = $rowElement.find('.col-icon ' + AjaxDataHandler.identifier.icon);
    if (nextState === 'hidden') {
      Icons.getIcon('miscellaneous-placeholder', Icons.sizes.small, 'overlay-hidden').done(function(icon) {
        $recordIcon.append($(icon).find('.icon-overlay'));
      });
    } else {
      $recordIcon.find('.icon-overlay').remove();
    }

    $rowElement.fadeTo('fast', 0.4, function() {
      $rowElement.fadeTo('fast', 1);
    });
    if (table === 'pages') {
      AjaxDataHandler.refreshPageTree();
    }
  };

  /**
   * delete record by given element (icon in table)
   * don't call it directly!
   *
   * @param {HTMLElement} element
   */
  AjaxDataHandler.deleteRecord = function(element) {
    var $anchorElement = $(element);
    var params = $anchorElement.data('params');
    var $iconElement = $anchorElement.find(AjaxDataHandler.identifier.icon);

    // add a spinner
    AjaxDataHandler._showSpinnerIcon($iconElement);

    // make the AJAX call to toggle the visibility
    AjaxDataHandler._call(params).done(function(result) {
      // revert to the old class
      Icons.getIcon('actions-edit-delete', Icons.sizes.small).done(function(icon) {
        $iconElement = $anchorElement.find(AjaxDataHandler.identifier.icon);
        $iconElement.replaceWith(icon);
      });
      // print messages on errors
      if (result.hasErrors) {
        AjaxDataHandler.handleErrors(result);
      } else {
        var $table = $anchorElement.closest('table[data-table]');
        var $panel = $anchorElement.closest('.panel');
        var $panelHeading = $panel.find('.panel-heading');
        var table = $table.data('table');
        var $rowElements = $anchorElement.closest('tr[data-uid]');
        var uid = $rowElements.data('uid');
        var $translatedRowElements = $table.find('[data-l10nparent=' + uid + ']').closest('tr[data-uid]');
        $rowElements = $rowElements.add($translatedRowElements);

        $rowElements.fadeTo('slow', 0.4, function() {
          $rowElements.slideUp('slow', 0, function() {
            $rowElements.remove();
            if ($table.find('tbody tr').length === 0) {
              $panel.slideUp('slow');
            }
          });
        });
        if ($anchorElement.data('l10parent') === '0' || $anchorElement.data('l10parent') === '') {
          var count = Number($panelHeading.find('.t3js-table-total-items').html());
          $panelHeading.find('.t3js-table-total-items').html(count - 1);
        }

        if (table === 'pages') {
          AjaxDataHandler.refreshPageTree();
        }
      }
    });
  };

  /**
   * handle the errors from result object
   *
   * @param {Object} result
   * @private
   */
  AjaxDataHandler.handleErrors = function(result) {
    $.each(result.messages, function(position, message) {
      Notification.error(message.title, message.message);
    });
  };

  /**
   * refresh the page tree
   * @private
   */
  AjaxDataHandler.refreshPageTree = function() {
    if (top.TYPO3 && top.TYPO3.Backend && top.TYPO3.Backend.NavigationContainer && top.TYPO3.Backend.NavigationContainer.PageTree) {
      top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
    }
  };

  /**
   * AJAX call to tce_db.php
   * returns a jQuery Promise to work with
   *
   * @param {Object} params
   * @returns {Object}
   * @private
   */
  AjaxDataHandler._call = function(params) {
    return $.getJSON(TYPO3.settings.ajaxUrls['record_process'], params);
  };

  /**
   * Replace the given icon with a spinner icon
   *
   * @param {Object} $iconElement
   * @private
   */
  AjaxDataHandler._showSpinnerIcon = function($iconElement) {
    Icons.getIcon('spinner-circle-dark', Icons.sizes.small).done(function(icon) {
      $iconElement.replaceWith(icon);
    });
  };

  $(AjaxDataHandler.initialize);

  return AjaxDataHandler;
});
