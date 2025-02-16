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
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { ResourceInterface } from '@typo3/backend/resource/resource';
import { FileListActionEvent, type FileListActionDetail } from '@typo3/filelist/file-list-actions';
import InfoWindow from '@typo3/backend/info-window';

class CreateFolder {
  constructor() {
    new RegularEvent(FileListActionEvent.primary, (event: CustomEvent): void => {
      event.preventDefault();
      const detail: FileListActionDetail = event.detail;
      detail.action = FileListActionEvent.select;
      document.dispatchEvent(new CustomEvent(FileListActionEvent.select, { detail: detail }));
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.select, (event: CustomEvent): void => {
      event.preventDefault();
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      if (resource.type === 'folder') {
        this.loadContent(resource);
      }
    }).bindTo(document);

    new RegularEvent(FileListActionEvent.show, (event: CustomEvent): void => {
      event.preventDefault();
      const detail: FileListActionDetail = event.detail;
      const resource = detail.resources[0];
      InfoWindow.showItem('_' + resource.type.toUpperCase(), resource.identifier);
    }).bindTo(document);
  }

  private loadContent(resource: ResourceInterface): void
  {
    if (resource.type !== 'folder') {
      return;
    }
    const contentsUrl = document.location.href + '&contentOnly=1&expandFolder=' + resource.identifier;
    (new AjaxRequest(contentsUrl)).get()
      .then((response: AjaxResponse) => response.resolve())
      .then((response) => {
        const contentContainer = document.querySelector('.element-browser-main-content .element-browser-body') as HTMLElement;
        contentContainer.innerHTML = response;
      });
  }
}

export default new CreateFolder();
