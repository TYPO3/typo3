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
 * Module: TYPO3/CMS/Backend/ContextMenuActions
 * Click menu actions for db records including tt_content and pages
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function($, Modal, Severity) {
  /**
   *
   * @exports TYPO3/CMS/Backend/ContextMenuActions
   */
  var ContextMenuActions = {};

  ContextMenuActions.getReturnUrl = function() {
    return top.rawurlencode(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
  };

  ContextMenuActions.editRecord = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FormEngine.moduleUrl + '&edit[' + table + '][' + uid + ']=edit&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.viewRecord = function(table, uid) {
    var $viewUrl = $(this).data('preview-url');
    if ($viewUrl) {
      var previewWin = window.open($viewUrl, 'newTYPO3frontendWindow');
      previewWin.focus();
    }
  };

  ContextMenuActions.openInfoPopUp = function(table, uid) {
    top.launchView(table, uid);
  };

  ContextMenuActions.mountAsTreeRoot = function(table, uid) {
    // see actions.js -> mountAsTreeRoot
    if (table === 'pages' && typeof top.Ext.getCmp('typo3-pagetree') !== 'undefined') {
      var app = top.Ext.getCmp('typo3-pagetree-tree').app;
      var node = app.getTree().getRootNode().findChild('realId', uid, true);
      if (node === null) {
        return false;
      }

      var useNode = {
        attributes: {
          nodeData: {
            id: uid
          }
        }
      };
      top.TYPO3.Components.PageTree.Actions.mountAsTreeRoot(useNode, node.ownerTree);
    }
  };

  ContextMenuActions.newPageWizard = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.NewRecord.moduleUrl + '&id=' + uid + '&pagesOnly=1&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.newContentWizard = function(table, uid) {
    var $wizardUrl = $(this).data('new-wizard-url');
    if ($wizardUrl) {
      $wizardUrl += '&returnUrl=' + ContextMenuActions.getReturnUrl();
      top.TYPO3.Backend.ContentContainer.setUrl($wizardUrl);
    }
  };

  ContextMenuActions.newRecord = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FormEngine.moduleUrl + '&edit[' + table + '][-' + uid + ']=new&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.openHistoryPopUp = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.RecordHistory.moduleUrl + '&element=' + table + ':' + uid + '&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  };

  ContextMenuActions.openListModule = function(table, uid) {
    var pageId = table === 'pages' ? uid : $(this).data('page-uid');
    top.TYPO3.ModuleMenu.App.showModule('web_list', 'id=' + pageId);
  };

  ContextMenuActions.disableRecord = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.RecordCommit.moduleUrl + '&data[' + table + '][' + uid + '][hidden]=1&prErr=1&redirect=' + ContextMenuActions.getReturnUrl()
    ).one('load', function() {
      top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
    });
  };

  ContextMenuActions.enableRecord = function(table, uid) {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.RecordCommit.moduleUrl + '&data[' + table + '][' + uid + '][hidden]=0&prErr=1&redirect=' + ContextMenuActions.getReturnUrl()
    ).one('load', function() {
      top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
    });
  };

  ContextMenuActions.deleteRecord = function(table, uid) {
    var $anchorElement = $(this);
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
        top.TYPO3.Backend.ContentContainer.setUrl(
          top.TYPO3.settings.RecordCommit.moduleUrl + '&redirect=' + ContextMenuActions.getReturnUrl() + '&cmd[' + table + '][' + uid + '][delete]=1&prErr=1'
        ).one('load', function() {
          if (table === 'pages' && top.TYPO3.Backend.NavigationContainer.PageTree) {
            top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
          }
        });
      }
      Modal.dismiss();
    });
  };

  ContextMenuActions.copy = function(table, uid) {
    var url = TYPO3.settings.ajaxUrls['contextmenu_clipboard'];
    url += '&CB[el][' + table + '%7C' + uid + ']=1' + '&CB[setCopyMode]=1';
    $.ajax(url).always(function() {
      top.list_frame.location.reload(true);
    });
  };

  ContextMenuActions.clipboardRelease = function(table, uid) {
    var url = TYPO3.settings.ajaxUrls['contextmenu_clipboard'];
    url += '&CB[el][' + table + '%7C' + uid + ']=0';
    $.ajax(url).always(function() {
      top.list_frame.location.reload(true);
    });
  };

  ContextMenuActions.cut = function(table, uid) {
    var url = TYPO3.settings.ajaxUrls['contextmenu_clipboard'];
    url += '&CB[el][' + table + '%7C' + uid + ']=1' + '&CB[setCopyMode]=0';
    $.ajax(url).always(function() {
      top.list_frame.location.reload(true);
    });
  };

  /**
   * Clear cache for given page uid
   *
   * @param {string} table pages table
   * @param {int} uid of the page
   */
  ContextMenuActions.clearCache = function(table, uid) {
    var url = top.TYPO3.settings.WebLayout.moduleUrl;
    url += '&id=' + uid + '&clear_cache=1';
    $.ajax(url);
  };

  /**
   * Paste db record after another
   *
   * @param {string} table any db table except sys_file
   * @param {int} uid of the record after which record from the cliboard will be pasted
   */
  ContextMenuActions.pasteAfter = function(table, uid) {
    ContextMenuActions.pasteInto.bind($(this))(table, -uid);
  };

  /**
   * Paste page into another page
   *
   * @param {string} table any db table except sys_file
   * @param {int} uid of the record after which record from the cliboard will be pasted
   */
  ContextMenuActions.pasteInto = function(table, uid) {
    var $anchorElement = $(this);
    var title = $anchorElement.data('title');
    var performPaste = function() {
      var url = '&CB[paste]=' + table + '%7C' + uid
        + '&CB[pad]=normal&prErr=1&uPT=1'
        + '&redirect=' + ContextMenuActions.getReturnUrl();

      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.RecordCommit.moduleUrl + url
      ).one('load', function() {
        if (table === 'pages' && top.TYPO3.Backend.NavigationContainer.PageTree) {
          top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
        }
      });
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

  return ContextMenuActions;
});
