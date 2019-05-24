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
import * as $ from 'jquery';
import Modal = require('TYPO3/CMS/Backend/Modal');

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
      const $element: HTMLInputElement = <HTMLInputElement>e.currentTarget;
      if ($element.name === 'delete') {
        performDelete();
      }
      Modal.dismiss();
    });
  }

  public static copyFile(table: string, uid: string): void {
    const shortMD5 = top.MD5(uid).substring(0, 10);
    let url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    url += '&CB[el][_FILE%7C' + shortMD5 + ']=' + encodeURIComponent(uid) + '&CB[setCopyMode]=1';
    $.ajax(url).always((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static copyReleaseFile(table: string, uid: string): void {
    const shortMD5 = top.MD5(uid).substring(0, 10);
    let url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    url += '&CB[el][_FILE%7C' + shortMD5 + ']=0&CB[setCopyMode]=1';
    $.ajax(url).always((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static cutFile(table: string, uid: string): void {
    const shortMD5 = top.MD5(uid).substring(0, 10);
    let url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    url += '&CB[el][_FILE%7C' + shortMD5 + ']=' + encodeURIComponent(uid);
    $.ajax(url).always((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static cutReleaseFile(table: string, uid: string): void {
    const shortMD5 = top.MD5(uid).substring(0, 10);
    let url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    url += '&CB[el][_FILE%7C' + shortMD5 + ']=0';
    $.ajax(url).always((): void => {
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
    if (!$anchorElement.data('title')) {
      performPaste();
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
