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
import interact from 'interactjs';
import {Interactable} from '@interactjs/core/Interactable';
import {DragEvent} from '@interactjs/actions/drag/plugin';
import {DropEvent} from '@interactjs/actions/drop/DropEvent';
import DocumentService from '@typo3/core/document-service';
import DataHandler from '../ajax-data-handler';
import Icons from '../icons';
import ResponseInterface from '../ajax-data-handler/response-interface';
import RegularEvent from '@typo3/core/event/regular-event';

interface Parameters {
  cmd?: { [key: string]: { [key: string]: any } };
  data?: { [key: string]: { [key: string]: any } };
  CB?: { paste: string, update: { colPos: number | boolean, sys_language_uid: number }};
}

class DragDrop {
  private static readonly contentIdentifier: string = '.t3js-page-ce';
  private static readonly draggableContentIdentifier: string = '.t3js-page-ce-sortable';
  private static readonly dropZoneIdentifier: string = '.t3js-page-ce-dropzone-available';
  private static readonly columnIdentifier: string = '.t3js-page-column';
  private static readonly validDropZoneClass: string = 'active';
  private static readonly dropPossibleHoverClass: string = 't3-page-ce-dropzone-possible';
  private static readonly addContentIdentifier: string = '.t3js-page-new-ce';

  /**
   * initializes Drag+Drop for all content elements on the page
   */
  public static initialize(): void {
    const moduleBody = document.querySelector('.module') as HTMLElement;

    // Pipe scroll attempt to parent element
    new RegularEvent('wheel', (e: WheelEvent): void => {
      moduleBody.scrollLeft += e.deltaX;
      moduleBody.scrollTop += e.deltaY;
    }).delegateTo(document, '.draggable-dragging');

    interact(DragDrop.draggableContentIdentifier).draggable({
      onstart: DragDrop.onDragStart,
      onmove: DragDrop.onDragMove,
      onend: DragDrop.onDragEnd,
    });

    interact(DragDrop.dropZoneIdentifier).dropzone({
      accept: this.draggableContentIdentifier,
      ondrop: DragDrop.onDrop,
      checker: (
        dragEvent: DragEvent,
        event: MouseEvent,
        dropped: boolean,
        dropzone: Interactable,
        dropElement: HTMLElement
      ): boolean => {
        const dropzoneRect = dropElement.getBoundingClientRect();

        return (event.pageX >= dropzoneRect.left && event.pageX <= dropzoneRect.left + dropzoneRect.width) // is cursor in boundaries of x-axis
          && (event.pageY >= dropzoneRect.top && event.pageY <= dropzoneRect.top + dropzoneRect.height)  // is cursor in boundaries of y-axis;
      }
    }).on('dragenter', (e: DropEvent): void => {
      e.target.classList.add(DragDrop.dropPossibleHoverClass);
    }).on('dragleave', (e: DropEvent): void => {
      e.target.classList.remove(DragDrop.dropPossibleHoverClass);
    });
  }

  private static onDragStart(e: DragEvent): void {
    e.target.dataset.dragStartX = (e.client.x - e.rect.left).toString();
    e.target.dataset.dragStartY = (e.client.y - e.rect.top).toString();

    // Configure styling of element
    e.target.style.width = getComputedStyle(e.target).getPropertyValue('width');
    e.target.classList.add('draggable-dragging');

    const copyMessage = document.createElement('div');
    copyMessage.classList.add('draggable-copy-message');
    copyMessage.textContent = TYPO3.lang['dragdrop.copy.message'];
    e.target.append(copyMessage);

    e.target.closest(DragDrop.columnIdentifier).classList.remove('active');
    (e.target.querySelector(DragDrop.dropZoneIdentifier) as HTMLElement).hidden = true;

    document.querySelectorAll(DragDrop.dropZoneIdentifier).forEach((element: HTMLElement): void => {
      const addContentButton = element.parentElement.querySelector(DragDrop.addContentIdentifier) as HTMLElement;
      if (addContentButton !== null) {
        addContentButton.hidden = true;
        element.classList.add(DragDrop.validDropZoneClass);
      }
    });
  }

  private static onDragMove(e: DragEvent): void {
    const scrollSensitivity = 20;
    const scrollSpeed = 20;
    const moduleContainer = document.querySelector('.module') as HTMLElement;

    // Re-calculate position of draggable element
    e.target.style.left = `${e.client.x - parseInt(e.target.dataset.dragStartX, 10)}px`;
    e.target.style.top = `${e.client.y - parseInt(e.target.dataset.dragStartY, 10)}px`;

    // Scroll when draggable leaves the viewport
    if (e.delta.x < 0 && e.pageX - scrollSensitivity < 0) {
      // Scroll left
      moduleContainer.scrollLeft -= scrollSpeed;
    } else if (e.delta.x > 0 && e.pageX + scrollSensitivity > moduleContainer.offsetWidth) {
      // Scroll right
      moduleContainer.scrollLeft += scrollSpeed;
    }

    if (e.delta.y < 0 && e.pageY - scrollSensitivity - document.querySelector('.t3js-module-docheader').clientHeight < 0) {
      // Scroll up
      moduleContainer.scrollTop -= scrollSpeed;
    } else if (e.delta.y > 0 && e.pageY + scrollSensitivity > moduleContainer.offsetHeight) {
      // Scroll down
      moduleContainer.scrollTop += scrollSpeed;
    }
  }

  private static onDragEnd(e: DragEvent): void {
    e.target.dataset.dragStartX = '';
    e.target.dataset.dragStartY = '';

    e.target.classList.remove('draggable-dragging');
    e.target.style.width = 'unset';
    e.target.style.left = 'unset';
    e.target.style.top = 'unset';

    // Show create new element button
    e.target.closest(DragDrop.columnIdentifier).classList.add('active');
    (e.target.querySelector(DragDrop.dropZoneIdentifier) as HTMLElement).hidden = false;
    e.target.querySelector('.draggable-copy-message').remove();

    document.querySelectorAll(DragDrop.dropZoneIdentifier + '.' + DragDrop.validDropZoneClass).forEach((element: HTMLElement): void => {
      const addContentButton = element.parentElement.querySelector(DragDrop.addContentIdentifier) as HTMLElement;
      if (addContentButton !== null) {
        addContentButton.hidden = false;
      }
      element.classList.remove(DragDrop.validDropZoneClass);
    });
  }

  /**
   * this method does the whole logic when a draggable is dropped on to a dropzone
   * sending out the request and afterwards move the HTML element in the right place.
   *
   * @param {DropEvent} e
   */
  private static onDrop(e: DropEvent): void {
    const dropContainer = e.target as HTMLElement;
    const draggedElement = e.relatedTarget as HTMLElement;
    const newColumn = DragDrop.getColumnPositionForElement(dropContainer);
    const contentElementUid: number = parseInt(draggedElement.dataset.uid, 10);

    if (typeof(contentElementUid) === 'number' && contentElementUid > 0) {
      let parameters: Parameters = {};
      // add the information about a possible column position change
      const targetFound = (dropContainer.closest(DragDrop.contentIdentifier) as HTMLElement).dataset.uid;
      // the item was moved to the top of the colPos, so the page ID is used here
      let targetPid;
      if (targetFound === undefined) {
        // the actual page is needed. Read it from the container into which the element was dropped.
        targetPid = parseInt((dropContainer.closest('[data-page]') as HTMLElement).dataset.page, 10);
      } else {
        // the negative value of the content element after where it should be moved
        targetPid = 0 - parseInt(targetFound, 10);
      }

      // the dragged elements language uid
      let language: number = parseInt(draggedElement.dataset.languageUid, 10);
      if (language !== -1) {
        // new elements language must be the same as the column the element is dropped in if element is not -1
        language = parseInt((dropContainer.closest('[data-language-uid]') as HTMLElement).dataset.languageUid, 10);
      }

      let colPos: number | boolean = 0;
      if (targetPid !== 0) {
        colPos = newColumn;
      }
      const isCopyAction = (e.dragEvent.ctrlKey || dropContainer.classList.contains('t3js-paste-copy'));
      const datahandlerCommand = isCopyAction ? 'copy' : 'move';
      parameters.cmd = {
        tt_content: {
          [contentElementUid]: {
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

      DragDrop.ajaxAction(dropContainer, draggedElement, parameters, isCopyAction).then((): void => {
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

  /**
   * this method does the actual AJAX request for both, the move and the copy action.
   *
   * @param {HTMLElement} dropContainer
   * @param {HTMLElement} draggedElement
   * @param {Parameters} parameters
   * @param {boolean} isCopyAction
   * @private
   */
  private static ajaxAction(dropContainer: HTMLElement, draggedElement: HTMLElement, parameters: Parameters, isCopyAction: boolean): Promise<any> {
    const table: string = Object.keys(parameters.cmd).shift();
    const uid: number = parseInt(Object.keys(parameters.cmd[table]).shift(), 10);
    const eventData = {component: 'dragdrop', action: isCopyAction ? 'copy' : 'move', table, uid};

    return DataHandler.process(parameters, eventData).then((result: ResponseInterface): void => {
      if (result.hasErrors) {
        throw result.messages;
      }

      // insert draggable on the new position
      if (!dropContainer.parentElement.classList.contains(DragDrop.contentIdentifier.substring(1))) {
        dropContainer.closest(DragDrop.dropZoneIdentifier).after(draggedElement);
      } else {
        dropContainer.closest(DragDrop.contentIdentifier).after(draggedElement);
      }
      if (isCopyAction) {
        self.location.reload();
      }
    });
  }

  /**
   * returns the next "upper" container colPos parameter inside the code
   * @param element HTMLElement
   * @return int|null the colPos
   */
  private static getColumnPositionForElement(element: HTMLElement): number | boolean {
    const columnContainer = element.closest('[data-colpos]') as HTMLElement;
    if (columnContainer !== null && columnContainer.dataset.colpos !== undefined) {
      return parseInt(columnContainer.dataset.colpos, 10);
    }
    return false;
  }

  constructor() {
    DocumentService.ready().then((): void => {
      DragDrop.initialize();
    });
  }
}

export default new DragDrop();
