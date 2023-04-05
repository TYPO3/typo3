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
import { ResourceInterface } from '@typo3/backend/resource/resource';
import Viewport from '@typo3/backend/viewport';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';
import { FileListActionSelector, FileListActionUtility } from '@typo3/filelist/file-list-actions';

export interface FileListDragDropDetail {
  action: string;
  resources: ResourceInterface[];
  target: ResourceInterface;
}

export enum FileListDragDropEvent {
  transfer = 'typo3:filelist:resource:dragdrop:transfer',
}

type Coordinates = { left: number, top: number };

class FileListDragDrop {
  private dragPreviewId: string = 'dragpreview';
  private rootDocument: Document;
  private currentAnimationRequestId: number | null = null;
  private previewSize: number = 32;

  constructor() {
    this.rootDocument = top.document;
    const selector = FileListActionSelector.elementSelector + '[draggable="true"]';

    // This creates a ghost image we are using as drag preview.
    //
    // We are building a custom preview, so this is a transparent image
    // to prevent the default behaviour of the browser to show a snapshot
    // of the dragged element.
    //
    // This only accepts drag images that are preloaded.
    // So we are creating this image early in the process.
    const ghostImage = new Image();
    ghostImage.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=';

    new RegularEvent('dragstart', (event: DragEvent, target: HTMLElement): void => {
      const selectedItems: ResourceInterface[] = [];

      const checkedItems = document.querySelectorAll(MultiRecordSelectionSelectors.checkboxSelector + ':checked') as NodeListOf<HTMLInputElement>;
      if (checkedItems.length) {
        checkedItems.forEach((checkbox: HTMLInputElement) => {
          if (checkbox.checked) {
            const element = checkbox.closest(FileListActionSelector.elementSelector) as HTMLInputElement;
            element.dataset.filelistDragdropTransferItem = 'true';
            const resource = FileListActionUtility.getResourceForElement(element);
            selectedItems.push(resource);
          }
        });
      } else {
        const element = target.closest(FileListActionSelector.elementSelector) as HTMLElement;
        element.dataset.filelistDragdropTransferItem = 'true';
        const resource = FileListActionUtility.getResourceForElement(element);
        selectedItems.push(resource);
      }

      const preview = this.createPreview(selectedItems);
      event.dataTransfer.setDragImage(ghostImage, 0, 0);
      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData('application/json', JSON.stringify(selectedItems));

      const calculatedPosition = this.determinePreviewPosition(event);
      this.updatePreviewPosition(preview, calculatedPosition);
    }).delegateTo(document, selector);

    new RegularEvent('drag', (event: DragEvent): void => {
      event.stopPropagation();
    }, { capture: true, passive: true }).delegateTo(document, selector);

    new RegularEvent('dragover', (event: DragEvent, target: HTMLElement): void => {
      event.stopPropagation();
      const resource = FileListActionUtility.getResourceForElement(target);
      if (this.isDropAllowedOnResoruce(resource)) {
        event.dataTransfer.dropEffect = 'move';
        event.preventDefault();
        target.classList.add('success');
      }
    }, { capture: true }).delegateTo(document, selector);

    new RegularEvent('drop', (event: DragEvent, target: HTMLElement): void => {
      event.stopPropagation();
      const detail: FileListDragDropDetail = {
        action: 'transfer',
        resources: JSON.parse(event.dataTransfer.getData('application/json') ?? '{}'),
        target: FileListActionUtility.getResourceForElement(target),
      };
      top.document.dispatchEvent(new CustomEvent(FileListDragDropEvent.transfer, { detail: detail }));
    }, { capture: true, passive: true }).delegateTo(document, selector);

    new RegularEvent('dragend', (event: DragEvent): void => {
      event.stopPropagation();
      this.reset();
    }, { capture: true, passive: true }).delegateTo(document, selector);

    new RegularEvent('dragleave', (event: DragEvent, target: HTMLElement): void => {
      event.stopPropagation();
      target.classList.remove('success');
    }, { capture: true, passive: true }).delegateTo(document, selector);

    new RegularEvent('dragover', (event: DragEvent) : void => {
      if (event.screenX === 0 && event.screenY === 0) {
        return;
      }

      const preview = this.rootDocument.getElementById(this.dragPreviewId);
      if (!preview) {
        return;
      }
      const calculatedPosition = this.determinePreviewPosition(event);
      const currentPosition = preview.getBoundingClientRect();
      if (calculatedPosition.left === currentPosition.left && calculatedPosition.top === currentPosition.top) {
        return;
      }
      this.updatePreviewPosition(preview, calculatedPosition);
    }, { capture: true, passive: true }).bindTo(window);
  }

  private createPreview(selectedItems: ResourceInterface[]): HTMLDivElement {
    // Container
    this.rootDocument.getElementById(this.dragPreviewId)?.remove();
    const preview = document.createElement('div');
    preview.id = this.dragPreviewId;
    preview.setAttribute('inert', 'true');
    preview.classList.add('resource-dragpreview');
    this.rootDocument.body.appendChild(preview);

    // Previews
    const previewItems = selectedItems
      .filter((item: ResourceInterface): boolean => item.thumbnail !== null)
      .slice(0, 3);
    if (previewItems.length > 0) {
      const thumbnails = document.createElement('div');
      thumbnails.classList.add('resource-dragpreview-thumbnails');
      preview.appendChild(thumbnails);
      previewItems.forEach((item: ResourceInterface): void => {
        const preview = new Image();
        preview.src = item.thumbnail;
        preview.height = this.previewSize;
        preview.width = this.previewSize;
        thumbnails.appendChild(preview);
      });
    }

    // Counter
    const count = selectedItems.length - previewItems.length;
    if (count > 0) {
      const counter = document.createElement('div');
      counter.classList.add('resource-dragpreview-counter');
      counter.textContent = (previewItems.length > 0 ? '+' : '') + count.toString();
      preview.appendChild(counter);
    }

    return preview;
  }

  private updatePreviewPosition(preview: HTMLElement, position: Coordinates): void {
    if (this.currentAnimationRequestId) {
      window.cancelAnimationFrame(this.currentAnimationRequestId);
    }
    this.currentAnimationRequestId = window.requestAnimationFrame(() => {
      preview.style.transform = 'translate(' + Math.round(position.left) + 'px, ' + Math.round(position.top) + 'px)';
    });
  }

  private determinePreviewPosition(event: DragEvent): Coordinates {
    let left = event.clientX + 16;
    let top = event.clientY + 16;

    const contentWindow = Viewport.ContentContainer.get();
    if (event.view === contentWindow) {
      const clientRect = contentWindow.frameElement.getBoundingClientRect();
      left += clientRect.left;
      top += clientRect.top;
    }

    return { left, top };
  }

  private reset(): void {
    document.querySelectorAll(FileListActionSelector.elementSelector).forEach((element: HTMLElement) => {
      delete element.dataset.filelistDragdropTransferItem;
      element.classList.remove('success');
    });

    this.rootDocument.getElementById(this.dragPreviewId)?.remove();
  }

  private isDropAllowedOnResoruce(resource: ResourceInterface): boolean {
    const element = document.querySelector(FileListActionSelector.elementSelector + '[data-filelist-identifier="' + resource.identifier + '"]') as HTMLElement;
    return !('filelistDragdropTransferItem' in element.dataset) && resource.type === 'folder';
  }
}

export default new FileListDragDrop();
