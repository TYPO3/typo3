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
 * Module: @typo3/backend/layout-module/drag-drop
 * this JS code does the drag+drop logic for the Layout module (Web => Page)
 */
import DocumentService from '@typo3/core/document-service';
import DataHandler from '../ajax-data-handler';
import Icons from '../icons';
import RegularEvent from '@typo3/core/event/regular-event';
import { DataTransferTypes } from '@typo3/backend/enum/data-transfer-types';
import BroadcastService from '@typo3/backend/broadcast-service';
import { BroadcastMessage } from '@typo3/backend/broadcast-message';
import type ResponseInterface from '../ajax-data-handler/response-interface';
import type { DragDropThumbnail, DragTooltipMetadata } from '@typo3/backend/drag-tooltip';
import DragDropUtility from '@typo3/backend/utility/drag-drop-utility';

interface Parameters {
  cmd?: { [key: string]: { [key: string]: any } };
  data?: { [key: string]: { [key: string]: any } };
  CB?: { paste: string, update: { colPos: number | boolean, sys_language_uid: number } };
}

export type ContentElementDragDropData = {
  pid: number;
  uid: number;
  language: number;
  content: string;
  moveElementUrl: string;
}

enum Identifiers {
  content = '.t3js-page-ce',
  draggableContentHandle = '.t3js-page-ce-header[draggable="true"]',
  dropZone = '.t3js-page-ce-dropzone-available',
  column = '.t3js-page-column',
  addContent = '.t3js-page-new-ce',
}

enum Classes {
  validDropZoneClass = 'active',
  dropPossibleHoverClass = 't3-page-ce-dropzone-possible',
}

class DragDrop {
  constructor() {
    DocumentService.ready().then((): void => {
      this.initialize();
    });
  }

  /**
   * initializes Drag+Drop for all content elements on the page
   */
  public initialize(): void {
    new RegularEvent('mousedown', (e: MouseEvent, target: HTMLElement): void => {
      const closestDenyElement = (e.target as HTMLElement).closest('a,img');
      if (closestDenyElement !== null && target.contains(closestDenyElement)) {
        // Do not enable drag&drop when event is triggered on an anchor element
        return;
      }
    }).delegateTo(document, Identifiers.draggableContentHandle);

    new RegularEvent(
      'dragstart',
      this.onDragStart.bind(this)
    ).delegateTo(document, Identifiers.draggableContentHandle);

    new RegularEvent(
      'dragenter',
      this.onDragEnter.bind(this)
    ).delegateTo(document, Identifiers.draggableContentHandle);

    new RegularEvent(
      'dragend',
      this.onDragEnd.bind(this)
    ).delegateTo(document, Identifiers.draggableContentHandle);

    new RegularEvent('dragenter', (event: DragEvent, target: HTMLElement): void => {
      target.classList.add(Classes.dropPossibleHoverClass);
      DragDropUtility.updateEventAndTooltipToReflectCopyMoveIntention(event);
    }).delegateTo(document, Identifiers.dropZone);

    new RegularEvent('dragover', (event: DragEvent): void => {
      event.preventDefault();
      DragDropUtility.updateEventAndTooltipToReflectCopyMoveIntention(event);
    }).delegateTo(document, Identifiers.dropZone);

    new RegularEvent('dragleave', (event: DragEvent, target: HTMLElement): void => {
      event.preventDefault();
      target.classList.remove(Classes.dropPossibleHoverClass);
    }).delegateTo(document, Identifiers.dropZone);

    new RegularEvent('drop', this.onDrop.bind(this), { capture: true, passive: true }).delegateTo(document, Identifiers.dropZone);

    new RegularEvent('typo3:page-layout-drag-drop:elementChanged', this.onBroadcastElementChanged.bind(this)).bindTo(top.document);
  }

  protected onDragEnter(event: DragEvent): void {
    event.preventDefault();
    DragDropUtility.updateEventAndTooltipToReflectCopyMoveIntention(event);
    this.showDropZones();
  }

  protected onDragStart(event: DragEvent, target: HTMLElement): void {
    const content = target.closest(Identifiers.content) as HTMLElement;

    event.dataTransfer.setData(DataTransferTypes.content, JSON.stringify({
      pid: this.getCurrentPageId(),
      uid: parseInt(content.dataset.uid, 10),
      language: parseInt(content.dataset.languageUid, 10),
      content: content.outerHTML,
      moveElementUrl: content.dataset.moveElementUrl,
    } as ContentElementDragDropData));

    const metadata: DragTooltipMetadata = this.getDragTooltipMetadataFromContentElement(content);
    event.dataTransfer.setData(DataTransferTypes.dragTooltip, JSON.stringify(metadata));
    event.dataTransfer.effectAllowed = 'copyMove';
    DragDropUtility.updateEventAndTooltipToReflectCopyMoveIntention(event);

    (content.querySelector(Identifiers.dropZone) as HTMLElement).hidden = true;
  }

  protected onDragEnd(): void {
    this.hideDropZones();
  }

  /**
   * this method does the whole logic when a draggable is dropped on to a dropzone
   * sending out the request and afterwards move the HTML element in the right place.
   *
   * @param {DropEvent} event
   */
  protected onDrop(event: DragEvent, dropContainer: HTMLElement): void {
    let draggedElement: HTMLElement;

    dropContainer.classList.remove(Classes.dropPossibleHoverClass);

    if (!event.dataTransfer.types.includes(DataTransferTypes.content)) {
      // Dropped element is not accepted
      return;
    }

    const newColumn = this.getColumnPositionForElement(dropContainer);
    const contentElementDragDropData = JSON.parse(event.dataTransfer.getData(DataTransferTypes.content)) as ContentElementDragDropData;

    draggedElement = document.querySelector(`${Identifiers.content}[data-uid="${contentElementDragDropData.uid}"]`);
    if (!draggedElement) {
      draggedElement = document.createRange().createContextualFragment(contentElementDragDropData.content).firstElementChild as HTMLElement;
    }

    if (typeof (contentElementDragDropData.uid) === 'number' && contentElementDragDropData.uid > 0) {
      const parameters: Parameters = {};
      // add the information about a possible column position change
      const targetFound = (dropContainer.closest(Identifiers.content) as HTMLElement).dataset.uid;
      // the item was moved to the top of the colPos, so the page ID is used here
      let targetPid: number;
      if (targetFound === undefined) {
        // the actual page is needed. Read it from the container into which the element was dropped.
        targetPid = parseInt((dropContainer.closest('[data-page]') as HTMLElement).dataset.page, 10);
      } else {
        // the negative value of the content element after where it should be moved
        targetPid = 0 - parseInt(targetFound, 10);
      }

      // the dragged elements language uid
      let language = contentElementDragDropData.language;
      if (language !== -1) {
        // new elements language must be the same as the column the element is dropped in if element is not -1
        language = parseInt((dropContainer.closest('[data-language-uid]') as HTMLElement).dataset.languageUid, 10);
      }

      let colPos: number | boolean = 0;
      if (targetPid !== 0) {
        colPos = newColumn;
      }
      const isCopyAction = (DragDropUtility.isCopyModifierFromEvent(event) || dropContainer.classList.contains('t3js-paste-copy'));
      const datahandlerCommand = isCopyAction ? 'copy' : 'move';
      parameters.cmd = {
        tt_content: {
          [contentElementDragDropData.uid]: {
            [datahandlerCommand]: {
              action: 'paste',
              target: targetPid,
              update: {
                colPos: colPos,
                sys_language_uid: language,
              },
            }
          }
        }
      };

      this.ajaxAction(parameters, isCopyAction).then((): void => {
        // insert draggable on the new position
        if (!dropContainer.parentElement.classList.contains(Identifiers.content.substring(1))) {
          dropContainer.closest(Identifiers.dropZone).after(draggedElement);
        } else {
          dropContainer.closest(Identifiers.content).after(draggedElement);
        }

        this.broadcast('elementChanged', {
          pid: contentElementDragDropData.pid,
          uid: contentElementDragDropData.uid,
          targetPid: this.getCurrentPageId(),
          action: isCopyAction ? 'copy' : 'move',
        });

        const languageDescriber = document.querySelector(`.t3-page-column-lang-name[data-language-uid="${language}"]`) as HTMLElement;
        if (languageDescriber === null) {
          return;
        }

        const newFlagIdentifier = languageDescriber.dataset.flagIdentifier;
        const newLanguageTitle = languageDescriber.dataset.languageTitle;

        Icons.getIcon(newFlagIdentifier, Icons.sizes.small).then((markup: string): void => {
          const flagIcon = draggedElement.querySelector('.t3js-flag') as HTMLElement;
          flagIcon.title = newLanguageTitle;
          flagIcon.innerHTML = markup;
        });
      });
    }
  }

  protected onBroadcastElementChanged(event: CustomEvent<{ payload: { pid: number, uid: number, targetPid: number, action: string } }>): void {
    if (event.detail.payload.pid !== this.getCurrentPageId()) {
      return;
    }

    if (event.detail.payload.targetPid === event.detail.payload.pid) {
      return;
    }

    if (event.detail.payload.action === 'move') {
      document.querySelector(`${Identifiers.content}[data-uid="${event.detail.payload.uid}"]`).remove();
    }
  }

  /**
   * this method does the actual AJAX request for both, the move and the copy action.
   *
   * @param {Parameters} parameters
   * @param {boolean} isCopyAction
   * @private
   */
  protected ajaxAction(parameters: Parameters, isCopyAction: boolean): Promise<void> {
    const table: string = Object.keys(parameters.cmd).shift();
    const uid: number = parseInt(Object.keys(parameters.cmd[table]).shift(), 10);
    const eventData = { component: 'dragdrop', action: isCopyAction ? 'copy' : 'move', table, uid };
    const gridContainer = document.querySelector('.t3-grid-container') as HTMLDivElement|null;

    return DataHandler.process(parameters, eventData).then((result: ResponseInterface): void => {
      if (result.hasErrors) {
        throw result.messages;
      }

      if (isCopyAction || (gridContainer?.dataset.defaultLanguageBinding === '1')) {
        self.location.reload();
      }
    });
  }

  /**
   * returns the next "upper" container colPos parameter inside the code
   * @param element HTMLElement
   * @return int|null the colPos
   */
  protected getColumnPositionForElement(element: HTMLElement): number | boolean {
    const columnContainer = element.closest('[data-colpos]') as HTMLElement;
    if (columnContainer !== null && columnContainer.dataset.colpos !== undefined) {
      return parseInt(columnContainer.dataset.colpos, 10);
    }
    return false;
  }

  protected getDragTooltipMetadataFromContentElement(contentElement: HTMLElement): DragTooltipMetadata {
    let description, iconIdentifier;
    const thumbnails: DragDropThumbnail[] = [];
    const contentElementTitle = contentElement.querySelector('.t3-page-ce-header-title') as HTMLElement;
    const title = contentElementTitle.innerText;

    const contentElementPreview = contentElement.querySelector('.element-preview') as HTMLElement;
    if (contentElementPreview) {
      description = contentElementPreview.innerText;

      if (description.length > 80) {
        description = description.substring(0, 80) + '...';
      }
    }

    const contentElementIcon = contentElement.querySelector('.t3js-icon') as HTMLElement;
    if (contentElementIcon) {
      iconIdentifier = contentElementIcon.dataset.identifier;
    }

    const contentImagePreviews = contentElement.querySelectorAll('.preview-thumbnails-element-image img');
    if (contentImagePreviews.length > 0) {
      contentImagePreviews.forEach((image: HTMLImageElement): void => {
        thumbnails.push({
          src: image.src,
          height: image.height,
          width: image.width,
        });
      })
    }

    return {
      statusIconIdentifier: 'actions-move',
      tooltipIconIdentifier: iconIdentifier,
      tooltipLabel: title,
      tooltipDescription: description,
      thumbnails: thumbnails,
    };
  }

  protected getCurrentPageId(): number {
    return parseInt((document.querySelector('[data-page]') as HTMLElement).dataset.page, 10);
  }

  protected broadcast(eventName: string, payload?: unknown) {
    BroadcastService.post(new BroadcastMessage('page-layout-drag-drop', eventName, payload || {}));
  }

  protected showDropZones(): void {
    document.querySelectorAll(Identifiers.dropZone).forEach((element: HTMLElement): void => {
      element.hidden = false
      const addContentButton = element.parentElement.querySelector(Identifiers.addContent) as HTMLElement;
      if (addContentButton !== null) {
        addContentButton.hidden = true;
        element.classList.add(Classes.validDropZoneClass);
      }
    });
  }

  protected hideDropZones(): void {
    document.querySelectorAll(Identifiers.dropZone).forEach((element: HTMLElement): void => {
      element.hidden = true;
      const addContentButton = element.parentElement.querySelector(Identifiers.addContent) as HTMLElement;
      if (addContentButton !== null) {
        addContentButton.hidden = false;
      }
      element.classList.remove(Classes.validDropZoneClass);
    });
  }
}

export default new DragDrop();
