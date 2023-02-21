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
  metaUid: number | null;
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
  rename = 'typo3:filelist:resource:action:rename',
  select = 'typo3:filelist:resource:action:select',
  download = 'typo3:filelist:resource:action:download',
}

export enum FileListActionSelector {
  elementSelector = '[data-filelist-element]',
  actionSelector = '[data-filelist-action]',
}

export class FileListActionUtility {
  public static createResourceFromContextDataset(dataset: DOMStringMap): FileListActionResource {
    const resource: FileListActionResource = {
      type: dataset.filecontextType,
      identifier: dataset.filecontextIdentifier,
      stateIdentifier: dataset.filecontextIdentifier,
      name: dataset.filecontextName,
      uid: dataset.filecontextUid ? parseInt(dataset.filecontextUid, 10) : null,
      metaUid: dataset.filecontextMetaUid ? parseInt(dataset.filecontextMetaUid, 10) : null,
      origin: null,
    };

    return resource;
  }

  public static getResourceForElement(element: HTMLElement): FileListActionResource
  {
    const resource: FileListActionResource = {
      type: element.dataset.filelistType,
      identifier: element.dataset.filelistIdentifier,
      stateIdentifier: element.dataset.filelistStateIdentifier,
      name: element.dataset.filelistName,
      uid: element.dataset.filelistUid ? parseInt(element.dataset.filelistUid, 10) : null,
      metaUid: element.dataset.filelistMetaUid ? parseInt(element.dataset.filelistMetaUid, 10) : null,
      origin: element
    };

    return resource;
  }
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
          case 'rename':
            document.dispatchEvent(new CustomEvent(FileListActionEvent.rename, { detail: detail }));
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
      resource: FileListActionUtility.getResourceForElement(element),
      url: target.dataset.filelistActionUrl ?? null,
    }
    return detail;
  }
}

export default new FileListActions();
