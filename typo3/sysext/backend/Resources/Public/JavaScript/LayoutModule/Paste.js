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
 * Module: TYPO3/CMS/Backend/LayoutModule/Paste
 * this JS code does the paste logic for the Layout module (Web => Page)
 * based on jQuery UI
 */
define(['jquery',
  'TYPO3/CMS/Backend/AjaxDataHandler',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Severity'
], function($, DataHandler, Modal, Severity) {
  'use strict';

  /**
   *
   * @type {{}}
   * @exports TYPO3/CMS/Backend/LayoutModule/Paste
   */
  var Paste = {
    openedPopupWindow: [],
    elementIdentifier: '.t3js-page-ce'
  };

  /**
   * initializes paste icons for all content elements on the page
   */
  Paste.initialize = function() {
    if ($('.t3js-page-columns').length) {
      Paste.activatePasteIcons();
    }
  };

  /**
   * activates the paste into / paste after icons outside of the context menus
   */
  Paste.activatePasteIcons = function() {
    $('.t3-page-ce-wrapper-new-ce').each(function() {
      if (!$(this).find('.t3js-toggle-new-content-element-wizard').length) {
        return true;
      }
      $('.t3js-page-lang-column .t3-page-ce > .t3-page-ce').removeClass('t3js-page-ce');
      if (top.pasteAfterLinkTemplate && top.pasteIntoLinkTemplate) {
        var parent = $(this).parent();
        if (parent.data('page')) {
          $(this).append(top.pasteIntoLinkTemplate);
        } else {
          $(this).append(top.pasteAfterLinkTemplate);
        }
        $(this).find('.t3js-paste').on('click', function(evt) {
          evt.preventDefault();
          Paste.activatePasteModal($(this));
        });
      }
    });
  };

  /**
   * generates the paste into / paste after modal
   */
  Paste.activatePasteModal = function(element) {
    var $element = $(element);
    var url = $element.data('url') || null;
    var title = (TYPO3.lang['paste.modal.title.paste'] || 'Paste record') + ': "' + $element.data('title') + '"';
    var severity = (typeof top.TYPO3.Severity[$element.data('severity')] !== 'undefined') ? top.TYPO3.Severity[$element.data('severity')] : top.TYPO3.Severity.info;
    if ($element.hasClass('t3js-paste-copy')) {
      var content = TYPO3.lang['paste.modal.pastecopy'] || 'Do you want to copy the record to this position?';
      var buttons = [
        {
          text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          trigger: function() {
            Modal.currentModal.trigger('modal-dismiss');
          }
        },
        {
          text: TYPO3.lang['paste.modal.button.pastecopy'] || 'Copy',
          btnClass: 'btn-' + Severity.getCssClass(severity),
          trigger: function() {
            Modal.currentModal.trigger('modal-dismiss');
            Paste.execute($element);
          }
        }
      ];
    } else {
      var content = TYPO3.lang['paste.modal.paste'] || 'Do you want to move the record to this position?';
      var buttons = [
        {
          text: TYPO3.lang['paste.modal.button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          trigger: function() {
            Modal.currentModal.trigger('modal-dismiss');
          }
        },
        {
          text: TYPO3.lang['paste.modal.button.paste'] || 'Move',
          btnClass: 'btn-' + Severity.getCssClass(severity),
          trigger: function() {
            Modal.currentModal.trigger('modal-dismiss');
            Paste.execute($element);
          }
        }
      ];
    }
    if (url !== null) {
      var separator = (url.indexOf('?') > -1) ? '&' : '?';
      var params = $.param({data: $element.data()});
      Modal.loadUrl(title, severity, buttons, url + separator + params);
    } else {
      Modal.show(title, content, severity, buttons);
    }
  };

  /**
   * @param {jQuery} $element
   * @returns number
   */
  Paste.determineColumn = function($element) {
    const $columnContainer = $element.closest('[data-colpos]');
    if ($columnContainer.length && $columnContainer.data('colpos') !== 'undefined') {
      return $columnContainer.data('colpos');
    }

    return 0;
  };

  /**
   * Send an AJAX requst via the AjaxDataHandler
   *
   * @param {jQuery} $element
   */
  Paste.execute = function($element) {
    const colPos = Paste.determineColumn($element);
    const closestElement = $element.closest(Paste.elementIdentifier);
    const targetFound = closestElement.data('uid');
    let targetPid;
    if (typeof targetFound === 'undefined') {
      targetPid = parseInt(closestElement.data('page'), 10);
    } else {
      targetPid = 0 - parseInt(targetFound, 10);
    }
    const language = parseInt($element.closest('[data-language-uid]').data('language-uid'), 10);
    const parameters = {
      CB: {
        paste: 'tt_content|' + targetPid,
        update: {
          colPos: colPos,
          sys_language_uid: language
        }
      }
    };

    DataHandler.process(parameters).done(function(result) {
      if (result.hasErrors) {
        return;
      }

      window.location.reload(true);
    });
  };

  $(Paste.initialize);
  return Paste;
});
