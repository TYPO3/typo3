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

import {lll} from '@typo3/core/lit-helper';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import $ from 'jquery';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import Notification from '@typo3/backend/notification';
import Modal from '@typo3/backend/modal';
import Md5 from '@typo3/backend/hashing/md5';

/**
 * Module: @typo3/filelist/context-menu-actions
 *
 * JavaScript to handle filelist actions from context menu
 * @exports @typo3/filelist/context-menu-actions
 */
class ContextMenuActions {
  public static getReturnUrl(): string {
    return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
  }

  public static triggerFileDownload(downloadUrl: string, fileName: string, revokeObjectURL: boolean = false): void {
    const anchorTag = document.createElement('a');
    anchorTag.href = downloadUrl;
    anchorTag.download = fileName;
    document.body.appendChild(anchorTag);
    anchorTag.click();
    if (revokeObjectURL) {
      URL.revokeObjectURL(downloadUrl);
    }
    document.body.removeChild(anchorTag);
    // Add notification about successful preparation
    Notification.success(lll('file_download.success'), '', 2);
  }

  public static renameFile(table: string, uid: string): void {
    const actionUrl: string = $(this).data('action-url');
    top.TYPO3.Backend.ContentContainer.setUrl(
      actionUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl()
    );
  }

  public static editFile(table: string, uid: string): void {
    const actionUrl: string = $(this).data('action-url');
    top.TYPO3.Backend.ContentContainer.setUrl(
      actionUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static editMetadata(): void {
    const metadataUid: string = $(this).data('metadata-uid');
    if (!metadataUid) {
      return;
    }
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FormEngine.moduleUrl
      + '&edit[sys_file_metadata][' + parseInt(metadataUid, 10) + ']=edit'
      + '&returnUrl=' + ContextMenuActions.getReturnUrl()
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
    const actionUrl: string = $(this).data('action-url');
    top.TYPO3.Backend.ContentContainer.setUrl(
      actionUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static createFile(table: string, uid: string): void {
    const actionUrl: string = $(this).data('action-url');
    top.TYPO3.Backend.ContentContainer.setUrl(
      actionUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static downloadFile(): void {
    ContextMenuActions.triggerFileDownload($(this).data('url'), $(this).data('name'));
  }

  public static downloadFolder(table: string, uid: string): void {
    // Add notification about the download being prepared
    Notification.info(lll('file_download.prepare'), '', 2);
    const actionUrl: string = $(this).data('action-url');
    (new AjaxRequest(actionUrl)).post({items: [uid]})
      .then(async (response): Promise<any> => {
        let fileName = response.response.headers.get('Content-Disposition');
        if (!fileName) {
          const data = await response.resolve();
          if (data.success === false && data.status) {
            Notification.warning(lll('file_download.' + data.status), lll('file_download.' + data.status + '.message'), 10);
          } else {
            Notification.error(lll('file_download.error'));
          }
          return;
        }
        fileName = fileName.substring(fileName.indexOf(' filename=') + 10);
        const data = await response.raw().arrayBuffer();
        const blob = new Blob([data], {type: response.raw().headers.get('Content-Type')});
        ContextMenuActions.triggerFileDownload(URL.createObjectURL(blob), fileName, true);
      })
      .catch(() => {
        Notification.error(lll('file_download.error'));
      });
  }

  public static createFilemount(table: string, uid: string): void {
    const parts: Array<string> = uid.split(':');
    if (parts.length !== 2) {
      return;
    }
    top.TYPO3.Backend.ContentContainer.setUrl(
      top.TYPO3.settings.FormEngine.moduleUrl
      + '&edit[sys_filemounts][0]=new'
      + '&defVals[sys_filemounts][base]=' + encodeURIComponent(parts[0])
      + '&defVals[sys_filemounts][path]=' + encodeURIComponent(parts[1])
      + '&returnUrl=' + ContextMenuActions.getReturnUrl()
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
    const md5 = Md5.hash(uid);
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    const queryArguments = {
      CB: {
        el: {
          ['_FILE%7C' + md5]: uid
        },
        setCopyMode: '1'
      }
    };
    (new AjaxRequest(url)).withQueryArguments(queryArguments).get().finally((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static copyReleaseFile(table: string, uid: string): void {
    const md5 = Md5.hash(uid);
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    const queryArguments = {
      CB: {
        el: {
          ['_FILE%7C' + md5]: '0'
        },
        setCopyMode: '1'
      }
    };
    (new AjaxRequest(url)).withQueryArguments(queryArguments).get().finally((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static cutFile(table: string, uid: string): void {
    const md5 = Md5.hash(uid);
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    const queryArguments = {
      CB: {
        el: {
          ['_FILE%7C' + md5]: uid
        }
      }
    };
    (new AjaxRequest(url)).withQueryArguments(queryArguments).get().finally((): void => {
      top.TYPO3.Backend.ContentContainer.refresh(true);
    });
  }

  public static cutReleaseFile(table: string, uid: string): void {
    const md5 = Md5.hash(uid);
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard;
    const queryArguments = {
      CB: {
        el: {
          ['_FILE%7C' + md5]: '0'
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
}

export default ContextMenuActions;
