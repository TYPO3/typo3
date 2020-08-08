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

import {SeverityEnum} from 'TYPO3/CMS/Backend/Enum/Severity';
import $ from 'jquery';
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Md5 = require('TYPO3/CMS/Backend/Hashing/Md5');

/**
 * Module: TYPO3/CMS/Filelist/ContextMenuActions
 *
 * JavaScript to handle filelist actions from context menu
 * @exports TYPO3/CMS/Filelist/ContextMenuActions
 */
class ContextMenuActions {
  public static getReturnUrl(): string {
    return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
  }

  public static renameFile(table: string, uid: string): void {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileRename.moduleUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static editFile(table: string, uid: string): void {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileEdit.moduleUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static editFileStorage(table: string, uid: string): void {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FormEngine.moduleUrl
      + '&edit[sys_file_storage][' + parseInt(uid, 10) + ']=edit&returnUrl='
      + ContextMenuActions.getReturnUrl(),
    );
  }

  public static openInfoPopUp(table: string, uid: string): void {
    if (table === 'sys_file_storage') {
      top.TYPO3.InfoWindow.showItem(table, uid);
    } else {
      // Files and folders
      top.TYPO3.InfoWindow.showItem('_FILE', uid);
    }
  }

  public static uploadFile(table: string, uid: string): void {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileUpload.moduleUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static createFile(table: string, uid: string): void {
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileCreate.moduleUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static deleteFile(table: string, uid: string): void {
    const $anchorElement = $(this);
    const performDelete = () => {
      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.FileCommit.moduleUrl
        + '&data[delete][0][data]=' + encodeURIComponent(uid)
        + '&data[delete][0][redirect]=' + ContextMenuActions.getReturnUrl(),
      );
    };
    if (!$anchorElement.data('title')) {
      performDelete();
      return;
    }

    const $modal = Modal.confirm(
      $anchorElement.data('title'),
      $anchorElement.data('message'),
      SeverityEnum.warning, [
        {
          text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: $(this).data('button-ok-text') || TYPO3.lang['button.delete'] || 'Delete',
          btnClass: 'btn-warning',
          name: 'delete',
        },
      ]);

    $modal.on('button.clicked', (e: JQueryEventObject): void => {
      const $element: HTMLInputElement = <HTMLInputElement>e.target;
      if ($element.name === 'delete') {
        performDelete();
      }
      Modal.dismiss();
    });
  }

  public static copyFile(table: string, uid: string): void {
    const shortMD5 = Md5.hash(uid).substring(0, 10);
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    const queryArguments = {
      CB: {
        el: {
          ['_FILE%7C' + shortMD5]: uid
        },
        setCopyMode: '1'
      }
    };
    (new AjaxRequest(url)).withQueryArguments(queryArguments).get().finally((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static copyReleaseFile(table: string, uid: string): void {
    const shortMD5 = Md5.hash(uid).substring(0, 10);
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    const queryArguments = {
      CB: {
        el: {
          ['_FILE%7C' + shortMD5]: '0'
        },
        setCopyMode: '1'
      }
    };
    (new AjaxRequest(url)).withQueryArguments(queryArguments).get().finally((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static cutFile(table: string, uid: string): void {
    const shortMD5 = Md5.hash(uid).substring(0, 10);
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    const queryArguments = {
      CB: {
        el: {
          ['_FILE%7C' + shortMD5]: uid
        }
      }
    };
    (new AjaxRequest(url)).withQueryArguments(queryArguments).get().finally((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static cutReleaseFile(table: string, uid: string): void {
    const shortMD5 = Md5.hash(uid).substring(0, 10);
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    const queryArguments = {
      CB: {
        el: {
          ['_FILE%7C' + shortMD5]: '0'
        }
      }
    };
    (new AjaxRequest(url)).withQueryArguments(queryArguments).get().finally((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static pasteFileInto(table: string, uid: string): void {
    const $anchorElement = $(this);
    const title = $anchorElement.data('title');
    const performPaste = (): void => {
      top.TYPO3.Backend.ContentContainer.setUrl(
        top.TYPO3.settings.FileCommit.moduleUrl
        + '&CB[paste]=FILE|' + encodeURIComponent(uid)
        + '&CB[pad]=normal&redirect=' + ContextMenuActions.getReturnUrl(),
      );
    };
    if (!title) {
      performPaste();
      return;
    }
    const $modal = Modal.confirm(
      title,
      $anchorElement.data('message'),
      SeverityEnum.warning, [
        {
          text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-warning',
          name: 'ok',
        },
      ]);

    $modal.on('button.clicked', (e: JQueryEventObject): void => {
      const $element: HTMLInputElement = <HTMLInputElement>e.target;
      if ($element.name === 'ok') {
        performPaste();
      }
      Modal.dismiss();
    });
  }

  public static dropInto(table: string, uid: string, mode: string): void {
    const target = $(this).data('drop-target');
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FileCommit.moduleUrl
      + '&file[' + mode + '][0][data]=' + encodeURIComponent(uid)
      + '&file[' + mode + '][0][target]=' + encodeURIComponent(target)
      + '&redirect=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static dropMoveInto(table: string, uid: string): void {
    ContextMenuActions.dropInto.bind($(this))(table, uid, 'move');
  }

  public static dropCopyInto(table: string, uid: string): void {
    ContextMenuActions.dropInto.bind($(this))(table, uid, 'copy');
  }
}

export = ContextMenuActions;
