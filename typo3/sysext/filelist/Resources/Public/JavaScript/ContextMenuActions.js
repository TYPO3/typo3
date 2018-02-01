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
 * Module: TYPO3/CMS/Filelist/ContextMenuActions
 *
 * JavaScript to handle filelist actions from context menu
 * @exports TYPO3/CMS/Filelist/ContextMenuActions
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function($, Modal, Severity) {
  'use strict';

  /**
   * @exports TYPO3/CMS/Filelist/ContextMenuActions
   */
  var ContextMenuActions = {};
  ContextMenuActions.getReturnUrl = function() {
    return top.rawurlencode(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
  };

  ContextMenuActions.renameFile = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileRename.moduleUrl + '&target=' + top.rawurlencode(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.editFile = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileEdit.moduleUrl + '&target=' + top.rawurlencode(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.editFileStorage = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FormEngine.moduleUrl + '&edit[sys_file_storage][' + parseInt(uid, 10) + ']=edit&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.openInfoPopUp = function(table, uid) {
    if (table === 'sys_file_storage') {
      top.launchView(table, uid);
    } else {
      //files and folders
      top.launchView('_FILE', uid);
    }
  };

  ContextMenuActions.uploadFile = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileUpload.moduleUrl + '&target=' + top.rawurlencode(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.createFile = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileCreate.moduleUrl + '&target=' + top.rawurlencode(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.deleteFile = function(table, uid) {
    var $anchorElement = $(this);
    var performDelete = function() {
      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.FileCommit.moduleUrl + '&file[delete][0][data]=' + top.rawurlencode(uid) + '&redirect=' + ContextMenuActions.getReturnUrl()
      );
    };
    if (!$anchorElement.data('title')) {
      performDelete();
      return;
    }

    var $modal = Modal.confirm(
      $anchorElement.data('title'),
      $anchorElement.data('message'),
      Severity.warning, [
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
      if (e.target.name === 'delete') {
        performDelete();
      }
      Modal.dismiss();
    });
  };

  ContextMenuActions.copyFile = function(table, uid) {
    var shortMD5 = top.MD5(uid).substring(0, 10);
    var url = TYPO3.settings.ajaxUrls['contextmenu_clipboard'];
    url += '&CB[el][_FILE%7C' + shortMD5 + ']=' + top.rawurlencode(uid) + '&CB[setCopyMode]=1';
    $.ajax(url).always(function() {
      top.list_frame.location.reload(true);
    });
  };

  ContextMenuActions.copyReleaseFile = function(table, uid) {
    var shortMD5 = top.MD5(uid).substring(0, 10);
    var url = TYPO3.settings.ajaxUrls['contextmenu_clipboard'];
    url += '&CB[el][_FILE%7C' + shortMD5 + ']=0&CB[setCopyMode]=1';
    $.ajax(url).always(function() {
      top.list_frame.location.reload(true);
    });
  };

  ContextMenuActions.cutFile = function(table, uid) {
    var shortMD5 = top.MD5(uid).substring(0, 10);
    var url = TYPO3.settings.ajaxUrls['contextmenu_clipboard'];
    url += '&CB[el][_FILE%7C' + shortMD5 + ']=' + top.rawurlencode(uid);
    $.ajax(url).always(function() {
      top.list_frame.location.reload(true);
    });
  };

  ContextMenuActions.cutReleaseFile = function(table, uid) {
    var shortMD5 = top.MD5(uid).substring(0, 10);
    var url = TYPO3.settings.ajaxUrls['contextmenu_clipboard'];
    url += '&CB[el][_FILE%7C' + shortMD5 + ']=0';
    $.ajax(url).always(function() {
      top.list_frame.location.reload(true);
    });
  };

  ContextMenuActions.pasteFileInto = function(table, uid) {
    var $anchorElement = $(this);
    var title = $anchorElement.data('title');
    var performPaste = function() {
      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.FileCommit.moduleUrl + '&prErr=1&uPT=1&CB[paste]=FILE|' + top.rawurlencode(uid) + '&CB[pad]=normal&redirect=' + ContextMenuActions.getReturnUrl()
      );
    };
    if (!$anchorElement.data('title')) {
      performPaste();
      return;
    }
    var $modal = Modal.confirm(
      $anchorElement.data('title'),
      $anchorElement.data('message'),
      Severity.warning, [
        {
          text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel'
        },
        {
          text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-warning',
          name: 'ok'
        }
      ]);

    $modal.on('button.clicked', function(e) {
      if (e.target.name === 'ok') {
        performPaste();
      }
      Modal.dismiss();
    });
  };


  ContextMenuActions.dropInto = function(table, uid, mode) {
    var target = $(this).data('drop-target');
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileCommit.moduleUrl
      + '&file[' + mode + '][0][data]=' + top.rawurlencode(uid)
      + '&file[' + mode + '][0][target]=' + top.rawurlencode(target)
      + '&redirect=' + ContextMenuActions.getReturnUrl()
      + '&prErr=1'
    );
  };
  ContextMenuActions.dropMoveInto = function(table, uid) {
    ContextMenuActions.dropInto.bind($(this))(table, uid, 'move');
  };
  ContextMenuActions.dropCopyInto = function(table, uid) {
    ContextMenuActions.dropInto.bind($(this))(table, uid, 'copy');
  };
  return ContextMenuActions;
});
