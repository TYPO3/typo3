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

import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import {SeverityEnum} from './enum/severity';
import AjaxDataHandler from './ajax-data-handler';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import InfoWindow from './info-window';
import Modal from './modal';
import ModuleMenu from './module-menu';
import Notification from '@typo3/backend/notification';
import Viewport from './viewport';
import {ModuleStateStorage} from './storage/module-state-storage';
import {NewContentElementWizard} from '@typo3/backend/new-content-element-wizard';

/**
 * @exports @typo3/backend/context-menu-actions
 */
class ContextMenuActions {
  /**
   * @returns {string}
   */
  public static getReturnUrl(): string {
    return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
  }

  public static editRecord(table: string, uid: number, dataset: DOMStringMap): void {
    let overrideVals = '',
      pageLanguageId = dataset.pagesLanguageUid;

    if (pageLanguageId) {
      // Disallow manual adjustment of the language field for pages
      overrideVals = '&overrideVals[pages][sys_language_uid]=' + pageLanguageId;
    }

    Viewport.ContentContainer.setUrl(
      top.TYPO3.settings.FormEngine.moduleUrl
        + '&edit[' + table + '][' + uid + ']=edit'
        + overrideVals
        + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static viewRecord(table: string, uid: number, dataset: DOMStringMap): void {
    const viewUrl = dataset.previewUrl;
    if (viewUrl) {
      const previewWin = window.open(viewUrl, 'newTYPO3frontendWindow');
      previewWin.focus();
    }
  }

  public static openInfoPopUp(table: string, uid: number): void {
    InfoWindow.showItem(table, uid);
  }

  public static mountAsTreeRoot(table: string, uid: number): void {
    if (table === 'pages') {
      const event = new CustomEvent('typo3:pagetree:mountPoint', {
        detail: {
          pageId: uid
        },
      });
      top.document.dispatchEvent(event);
    }
  }

  public static newPageWizard(table: string, uid: number, dataset: DOMStringMap): void {
    const moduleUrl: string = dataset.pagesNewWizardUrl;
    Viewport.ContentContainer.setUrl(
      moduleUrl + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static newContentWizard(table: string, uid: number, dataset: DOMStringMap): void {
    let wizardUrl = dataset.newWizardUrl;
    if (wizardUrl) {
      wizardUrl += '&returnUrl=' + ContextMenuActions.getReturnUrl();
      const modal = Modal.advanced({
        title: dataset.title,
        type: Modal.types.ajax,
        size: Modal.sizes.medium,
        content: wizardUrl,
        severity: SeverityEnum.notice,
        ajaxCallback: (): void => {
          if (modal.querySelector('.t3-new-content-element-wizard-inner')) {
            new NewContentElementWizard(modal);
          }
        }
      });
    }
  }

  /**
   * Create new records on the same level. Pages are being inserted "inside".
   */
  public static newRecord(table: string, uid: number): void {
    Viewport.ContentContainer.setUrl(
      top.TYPO3.settings.FormEngine.moduleUrl + '&edit[' + table + '][' + (table !== 'pages' ? '-' : '') + uid + ']=new&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static openHistoryPopUp(table: string, uid: number): void {
    Viewport.ContentContainer.setUrl(
      top.TYPO3.settings.RecordHistory.moduleUrl + '&element=' + table + ':' + uid + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static openListModule(table: string, uid: number, dataset: DOMStringMap): void {
    const pageId = table === 'pages' ? uid : dataset.pageUid;
    ModuleMenu.App.showModule('web_list', 'id=' + pageId);
  }

  public static pagesSort(table: string, uid: number, dataset: DOMStringMap): void {
    const pagesSortUrl = dataset.pagesSortUrl;
    if (pagesSortUrl) {
      Viewport.ContentContainer.setUrl(pagesSortUrl);
    }
  }

  public static pagesNewMultiple(table: string, uid: number, dataset: DOMStringMap): void {
    const pagesSortUrl = dataset.pagesNewMultipleUrl;
    if (pagesSortUrl) {
      Viewport.ContentContainer.setUrl(pagesSortUrl);
    }
  }

  public static disableRecord(table: string, uid: number, dataset: DOMStringMap): void {
    const disableFieldName = dataset.disableField || 'hidden';
    Viewport.ContentContainer.setUrl(
      top.TYPO3.settings.RecordCommit.moduleUrl
      + '&data[' + table + '][' + uid + '][' + disableFieldName + ']=1'
      + '&redirect=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static enableRecord(table: string, uid: number, dataset: DOMStringMap): void {
    const disableFieldName = dataset.disableField || 'hidden';
    Viewport.ContentContainer.setUrl(
      top.TYPO3.settings.RecordCommit.moduleUrl
      + '&data[' + table + '][' + uid + '][' + disableFieldName + ']=0'
      + '&redirect=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static showInMenus(table: string, uid: number): void {
    Viewport.ContentContainer.setUrl(
      top.TYPO3.settings.RecordCommit.moduleUrl
      + '&data[' + table + '][' + uid + '][nav_hide]=0'
      + '&redirect=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static hideInMenus(table: string, uid: number): void {
    Viewport.ContentContainer.setUrl(
      top.TYPO3.settings.RecordCommit.moduleUrl
      + '&data[' + table + '][' + uid + '][nav_hide]=1'
      + '&redirect=' + ContextMenuActions.getReturnUrl(),
    );
  }

  public static deleteRecord(table: string, uid: number, dataset: DOMStringMap): void {
    const modal = Modal.confirm(
      dataset.title,
      dataset.message,
      SeverityEnum.warning, [
        {
          text: dataset.buttonCloseText || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: dataset.buttonOkText || TYPO3.lang['button.delete'] || 'Delete',
          btnClass: 'btn-warning',
          name: 'delete',
        },
      ]);

    modal.addEventListener('button.clicked', (e: Event): void => {
      if ((e.target as HTMLInputElement).getAttribute('name') === 'delete') {
        const eventData = {component: 'contextmenu', action: 'delete', table, uid};
        AjaxDataHandler.process('cmd[' + table + '][' + uid + '][delete]=1', eventData).then((): void => {
          if (table === 'pages') {
            // base on the assumption that the last selected node, is the one that got deleted
            if (ModuleStateStorage.current('web').identifier === uid.toString()) {
              top.document.dispatchEvent(new CustomEvent('typo3:pagetree:selectFirstNode'));
            }
            ContextMenuActions.refreshPageTree();
          } else if (table === 'tt_content') {
            Viewport.ContentContainer.refresh();
          }
        });
      }
      modal.hideModal();
    });
  }

  public static copy(table: string, uid: number): void {
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard
      + '&CB[el][' + table + '%7C' + uid + ']=1'
      + '&CB[setCopyMode]=1';

    (new AjaxRequest(url)).get().finally((): void => {
      ContextMenuActions.triggerRefresh(Viewport.ContentContainer.get().location.href);
    });
  }

  public static clipboardRelease(table: string, uid: number): void {
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard
      + '&CB[el][' + table + '%7C' + uid + ']=0';

    (new AjaxRequest(url)).get().finally((): void => {
      ContextMenuActions.triggerRefresh(Viewport.ContentContainer.get().location.href);
    });
  }

  public static cut(table: string, uid: number): void {
    const url = TYPO3.settings.ajaxUrls.contextmenu_clipboard
      + '&CB[el][' + table + '%7C' + uid + ']=1'
      + '&CB[setCopyMode]=0';

    (new AjaxRequest(url)).get().finally((): void => {
      ContextMenuActions.triggerRefresh(Viewport.ContentContainer.get().location.href);
    });
  }

  public static triggerRefresh(iframeUrl: string): void {
    if (!iframeUrl.includes('record%2Fedit')) {
      Viewport.ContentContainer.refresh();
    }
  }

  /**
   * Clear cache for given page uid
   */
  public static clearCache(table: string, uid: number): void {
    (new AjaxRequest(TYPO3.settings.ajaxUrls.web_list_clearpagecache)).withQueryArguments({id: uid}).get({cache: 'no-cache'}).then(
      async (response: AjaxResponse): Promise<any> => {
        const data = await response.resolve();
        if (data.success === true) {
          Notification.success(data.title, data.message, 1);
        } else {
          Notification.error(data.title, data.message, 1);
        }
      },
      (): void => {
        Notification.error(
          'Clearing page caches went wrong on the server side.',
        );
      }
    );
  }

  /**
   * Paste db record after another
   *
   * @param {string} table any db table except sys_file
   * @param {number} uid uid of the record after which record from the clipboard will be pasted
   * @param {DOMStringMap} dataset The data attributes of the invoked menu item
   */
  public static pasteAfter(table: string, uid: number, dataset: DOMStringMap): void {
    ContextMenuActions.pasteInto(table, -uid, dataset);
  }

  /**
   * Paste page into another page
   *
   * @param {string} table any db table except sys_file
   * @param {number} uid uid of the record after which record from the clipboard will be pasted
   * @param {DOMStringMap} dataset The data attributes of the invoked menu item
   */
  public static pasteInto(table: string, uid: number, dataset: DOMStringMap): void {
    const performPaste = (): void => {
      const url = '&CB[paste]=' + table + '%7C' + uid
        + '&CB[pad]=normal'
        + '&redirect=' + ContextMenuActions.getReturnUrl();

      Viewport.ContentContainer.setUrl(
        top.TYPO3.settings.RecordCommit.moduleUrl + url,
      );
    };
    if (!dataset.title) {
      performPaste();
      return;
    }
    const modal = Modal.confirm(
      dataset.title,
      dataset.message,
      SeverityEnum.warning, [
        {
          text: dataset.buttonCloseText || TYPO3.lang['button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: dataset.buttonOkText || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-warning',
          name: 'ok',
        },
      ]);

    modal.addEventListener('button.clicked', (e: Event): void => {
      if ((e.target as HTMLInputElement).getAttribute('name') === 'ok') {
        performPaste();
      }
      modal.hideModal();
    });
  }

  private static refreshPageTree(): void {
    top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
  }
}

export default ContextMenuActions;
