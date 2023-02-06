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

import DocumentService from '@typo3/core/document-service';
import RegularEvent from '@typo3/core/event/regular-event';

export interface FileListActionResource {
  type: string;
  identifier: string;
  stateIdentifier: string;
  name: string;
  uid: number | null;
  origin: HTMLElement
}

export interface FileListActionDetail {
  event: Event;
  trigger: HTMLElement;
  action: string;
  resource: FileListActionResource;
  url: string | null;
}

export enum FileListActionEvent {
  primary = 'typo3:filelist:resource:action:primary',
  primaryContextmenu = 'typo3:filelist:resource:action:primaryContextmenu',
  show = 'typo3:filelist:resource:action:show',
  select = 'typo3:filelist:resource:action:select',
  download = 'typo3:filelist:resource:action:download',
}

export enum FileListActionSelector {
  elementSelector = '[data-filelist-element]',
  actionSelector = '[data-filelist-action]',
}

export function FileListActionResourceFromElement(element: HTMLElement): FileListActionResource
{
  const resource: FileListActionResource = {
    type: element.dataset.filelistType,
    identifier: element.dataset.filelistIdentifier,
    stateIdentifier: element.dataset.filelistStateIdentifier,
    name: element.dataset.filelistName,
    uid: element.dataset.filelistUid ? parseInt(element.dataset.filelistUid, 10) : null,
    origin: element
  };

  return resource;
}

class FileListActions {
  constructor() {
    DocumentService.ready().then((): void => {

      new RegularEvent('contextmenu', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        const detail: FileListActionDetail = this.getActionDetail(event, target);
        switch (detail.action) {
          case 'primary':
            document.dispatchEvent(new CustomEvent(FileListActionEvent.primaryContextmenu, { detail: detail }));
            break;
          default:
            break;
        }
      }).delegateTo(document, FileListActionSelector.actionSelector);

      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        const detail: FileListActionDetail = this.getActionDetail(event, target);
        switch (detail.action) {
          case 'primary':
            document.dispatchEvent(new CustomEvent(FileListActionEvent.primary, { detail: detail }));
            break;
          case 'show':
            document.dispatchEvent(new CustomEvent(FileListActionEvent.show, { detail: detail }));
            break;
          case 'select':
            document.dispatchEvent(new CustomEvent(FileListActionEvent.select, { detail: detail }));
            break;
          case 'download':
            document.dispatchEvent(new CustomEvent(FileListActionEvent.download, { detail: detail }));
            break;
          default:
            break;
        }
      }).delegateTo(document, FileListActionSelector.actionSelector);

    });
  }

  private getActionDetail(event: Event, target: HTMLElement): FileListActionDetail {
    const action = target.dataset.filelistAction;
    const element = target.closest(FileListActionSelector.elementSelector) as HTMLElement;
    const detail: FileListActionDetail = {
      event: event,
      trigger: target,
      action: action,
      resource: FileListActionResourceFromElement(element),
      url: target.dataset.filelistActionUrl ?? null,
    }
    return detail;
  }
}

export default new FileListActions();
