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

import RegularEvent from '@typo3/core/event/regular-event';
import DocumentService from '@typo3/core/document-service';
import AjaxDataHandler from '@typo3/backend/ajax-data-handler';
import Modal from '@typo3/backend/modal';
import ModuleMenu from '@typo3/backend/module-menu';
import Notification from '@typo3/backend/notification';
import ImmediateAction from '@typo3/backend/action-button/immediate-action';
import { lll } from '@typo3/core/lit-helper';

export class MovePage {
  public constructor() {
    this.initialize();
  }

  private async initialize(): Promise<void> {
    await DocumentService.ready();
    this.registerEvents(document.querySelector('.element-browser-body') as HTMLElement);
  }

  private registerEvents(container: HTMLElement): void {
    const recordTitle = (document.querySelector('#elementRecordTitle') as HTMLInputElement).value;
    const url = new URL(window.location.href);

    new RegularEvent('click', async (e: Event, actionElement: HTMLElement): Promise<void> => {
      const isCopyAction = (document.querySelector('#makeCopy') as HTMLInputElement).checked;
      const action = isCopyAction ? 'copy' : 'move';
      const parameters = {
        cmd: {
          [url.searchParams.get('table')]: {
            [url.searchParams.get('uid')]: {
              [action]: actionElement.dataset.position
            }
          }
        }
      };
      AjaxDataHandler.process(parameters).then((): void => {
        Modal.dismiss();

        Notification.success(
          lll(isCopyAction ? 'movePage.notification.pageCopied.title' : 'movePage.notification.pageMoved.title'),
          lll(isCopyAction ? 'movePage.notification.pageCopied.message' : 'movePage.notification.pageMoved.message', recordTitle),
          10,
          [
            {
              label: lll('movePage.notification.pagePasted.action.dismiss'),
            },
            {
              label: lll('movePage.notification.pagePasted.action.open', recordTitle),
              action: new ImmediateAction((): void => {
                ModuleMenu.App.showModule('web_list', 'id=' + url.searchParams.get('uid'));
              })
            }
          ]
        );

        top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
        ModuleMenu.App.showModule('web_list', 'id=' + url.searchParams.get('expandPage'));
      });
    }).delegateTo(container, '[data-action="paste"]');
  }
}
