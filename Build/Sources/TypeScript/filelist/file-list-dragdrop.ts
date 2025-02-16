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
import type { ResourceInterface } from '@typo3/backend/resource/resource';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';
import { FileListActionSelector, FileListActionUtility } from '@typo3/filelist/file-list-actions';
import { DataTransferTypes } from '@typo3/backend/enum/data-transfer-types';
import type { DragTooltipMetadata, DragDropThumbnail } from '@typo3/backend/drag-tooltip';

export interface FileListDragDropDetail {
  action: string;
  resources: ResourceInterface[];
  target: ResourceInterface;
}

export enum FileListDragDropEvent {
  transfer = 'typo3:filelist:resource:dragdrop:transfer',
}

class FileListDragDrop {
  private readonly previewSize: number = 32;

  constructor() {
    const selector = FileListActionSelector.elementSelector + '[draggable="true"]';

    new RegularEvent('dragstart', (event: DragEvent, target: HTMLElement): void => {
      const selectedItems: ResourceInterface[] = [];
      let icon = '';
      let label = '';

      const checkedItems = document.querySelectorAll(MultiRecordSelectionSelectors.checkboxSelector + ':checked') as NodeListOf<HTMLInputElement>;
      if (checkedItems.length) {
        checkedItems.forEach((checkbox: HTMLInputElement) => {
          if (checkbox.checked) {
            const element = checkbox.closest(FileListActionSelector.elementSelector) as HTMLInputElement;
            element.dataset.filelistDragdropTransferItem = 'true';
            const resource = FileListActionUtility.getResourceForElement(element);
            selectedItems.push(resource);
            label = element.dataset.filelistName;
            icon = element.dataset.filelistIcon;
          }
        });
      } else {
        const element = target.closest(FileListActionSelector.elementSelector) as HTMLElement;
        element.dataset.filelistDragdropTransferItem = 'true';
        const resource = FileListActionUtility.getResourceForElement(element);
        selectedItems.push(resource);
        label = element.dataset.filelistName;
        icon = element.dataset.filelistIcon;
      }

      event.dataTransfer.effectAllowed = 'move';
      event.dataTransfer.setData(DataTransferTypes.falResources, JSON.stringify(selectedItems));

      const metadata: DragTooltipMetadata = {
        tooltipIconIdentifier: selectedItems.length > 1 ? 'apps-clipboard-images' : icon,
        tooltipLabel: selectedItems.length > 1 ? this.getPreviewLabel(selectedItems) : label,
        thumbnails: this.getPreviewItems(selectedItems),
      };
      event.dataTransfer.setData(DataTransferTypes.dragTooltip, JSON.stringify(metadata));
    }).delegateTo(document, selector);

    new RegularEvent('dragover', (event: DragEvent, target: HTMLElement): void => {
      const resource = FileListActionUtility.getResourceForElement(target);
      if (this.isDropAllowedOnResoruce(resource)) {
        event.dataTransfer.dropEffect = 'move';
        event.preventDefault();
        target.classList.add('success');
      }
    }, { capture: true }).delegateTo(document, selector);

    new RegularEvent('drop', (event: DragEvent, target: HTMLElement): void => {
      const detail: FileListDragDropDetail = {
        action: 'transfer',
        resources: JSON.parse(event.dataTransfer.getData(DataTransferTypes.falResources) ?? '{}'),
        target: FileListActionUtility.getResourceForElement(target),
      };
      top.document.dispatchEvent(new CustomEvent(FileListDragDropEvent.transfer, { detail: detail }));
    }, { capture: true, passive: true }).delegateTo(document, selector);

    new RegularEvent('dragend', (/*event: DragEvent*/): void => {
      this.reset();
    }, { capture: true, passive: true }).delegateTo(document, selector);

    new RegularEvent('dragleave', (event: DragEvent, target: HTMLElement): void => {
      target.classList.remove('success');
    }, { capture: true, passive: true }).delegateTo(document, selector);
  }

  private getPreviewItems(selectedItems: ResourceInterface[]): DragDropThumbnail[] {
    return selectedItems
      .filter((item: ResourceInterface): boolean => item.thumbnail !== null)
      .map((item: ResourceInterface) => {
        return {
          src: item.thumbnail,
          width: this.previewSize,
          height: this.previewSize,
        };
      });
  }

  private getPreviewLabel(selectedItems: ResourceInterface[]): string {
    // Counter
    const previewItems = selectedItems.filter((item: ResourceInterface): boolean => item.thumbnail !== null)
    const count = selectedItems.length - previewItems.length;
    if (count > 0) {
      return (previewItems.length > 0 ? '+' : '') + count.toString();
    }
    return '';
  }

  private reset(): void {
    document.querySelectorAll(FileListActionSelector.elementSelector).forEach((element: HTMLElement) => {
      delete element.dataset.filelistDragdropTransferItem;
      element.classList.remove('success');
    });
  }

  private isDropAllowedOnResoruce(resource: ResourceInterface): boolean {
    const element = document.querySelector(FileListActionSelector.elementSelector + '[data-filelist-identifier="' + resource.identifier + '"]') as HTMLElement;
    return !('filelistDragdropTransferItem' in element.dataset) && resource.type === 'folder';
  }
}

export default new FileListDragDrop();
