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
import DocumentService from '@typo3/core/document-service';
import Notification from '@typo3/backend/notification';
import InfoWindow from '@typo3/backend/info-window';
import {BroadcastMessage} from '@typo3/backend/broadcast-message';
import broadcastService from '@typo3/backend/broadcast-service';
import Tooltip from '@typo3/backend/tooltip';
import NProgress from 'nprogress';
import Icons from '@typo3/backend/icons';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';
import RegularEvent from '@typo3/core/event/regular-event';
import {ModuleStateStorage} from '@typo3/backend/storage/module-state-storage';
import {ActionConfiguration, ActionEventDetails} from '@typo3/backend/multi-record-selection-action';
import {default as Modal, ModalElement} from '@typo3/backend/modal';
import {SeverityEnum} from '@typo3/backend/enum/severity';
import Severity from '@typo3/backend/severity';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';
import {DragDropDataTransferItem} from '@typo3/backend/drag-drop/drag-drop';

type QueryParameters = {[key: string]: string};
type DragImageCanvasOperation = {
  offsetX: number,
  offsetY: number,
  reference: HTMLImageElement,
};
interface DragImageCanvasConfiguration {
  width: number,
  height: number,
  operations: DragImageCanvasOperation[],
}

interface EditFileMetadataConfiguration extends ActionConfiguration{
  table: string;
  returnUrl: string;
}
interface DownloadConfiguration extends ActionConfiguration{
  fileIdentifier: string;
  folderIdentifier: string;
  downloadUrl: string;
}

interface DeleteFileMetadataConfiguration {
  ok: string;
  title: string;
  content: string;
}

enum Selectors {
  fileListFormSelector = 'form[name="fileListForm"]',
  commandSelector = 'input[name="cmd"]',
  searchFieldSelector = 'input[name="searchTerm"]',
  pointerFieldSelector = 'input[name="pointer"]'
}

/**
 * @internal
 */
export const fileListOpenElementBrowser = 'typo3:filelist:openElementBrowser';

/**
 * Module: @typo3/filelist/filelist
 * @exports @typo3/filelist/filelist
 */
export default class Filelist {
  public static submitClipboardFormWithCommand(cmd: string, target: HTMLButtonElement): void {
    const fileListForm: HTMLFormElement = target.closest(Selectors.fileListFormSelector);
    if (!fileListForm) {
      return;
    }
    const commandField: HTMLInputElement = fileListForm.querySelector(Selectors.commandSelector);
    if (!commandField) {
      return;
    }
    commandField.value = cmd;
    // In case we just change elements on the clipboard, we try to fetch a possible pointer from the query
    // parameters, so after the form submit, we get to the same view as before. This is not done for delete
    // commands, since this may lead to empty sites, in case all elements from the current site are deleted.
    if (cmd === 'copyMarked' || cmd === 'removeMarked') {
      const pointerField: HTMLInputElement = fileListForm.querySelector(Selectors.pointerFieldSelector);
      const pointerValue: string = Filelist.parseQueryParameters(document.location).pointer;
      if (pointerField && pointerValue) {
        pointerField.value = pointerValue;
      }
    }
    fileListForm.submit();
  }

  protected static openInfoPopup(type: string, identifier: string): void {
    InfoWindow.showItem(type, identifier);
  }

  private static processTriggers(): void {
    const mainElement: HTMLElement = document.querySelector('.filelist-main');
    if (mainElement === null) {
      return
    }
    // update ModuleStateStorage to the current folder identifier
    const id = encodeURIComponent(mainElement.dataset.filelistCurrentIdentifier);
    ModuleStateStorage.update('file', id, true, undefined);
    // emit event for currently shown folder so the folder tree gets updated
    Filelist.emitTreeUpdateRequest(
      mainElement.dataset.filelistCurrentIdentifier
    );
  }

  private static emitTreeUpdateRequest(identifier: string): void {
    const message = new BroadcastMessage(
      'filelist',
      'treeUpdateRequested',
      {type: 'folder', identifier: identifier}
    );
    broadcastService.post(message);
  }

  private static parseQueryParameters (location: Location): QueryParameters {
    let queryParameters: QueryParameters = {};
    if (location && Object.prototype.hasOwnProperty.call(location, 'search')) {
      let parameters = location.search.substr(1).split('&');
      for (let i = 0; i < parameters.length; i++) {
        const parameter = parameters[i].split('=');
        queryParameters[decodeURIComponent(parameter[0])] = decodeURIComponent(parameter[1]);
      }
    }
    return queryParameters;
  }

  private static getReturnUrl(returnUrl: string): string {
    if (returnUrl === '') {
      returnUrl = top.list_frame.document.location.pathname + top.list_frame.document.location.search;
    }
    return encodeURIComponent(returnUrl);
  }

  constructor() {
    Filelist.processTriggers();

    new RegularEvent(fileListOpenElementBrowser, (e: CustomEvent): void => {
      const url = new URL(e.detail.actionUrl, window.location.origin);

      url.searchParams.set('expandFolder', e.detail.identifier);
      url.searchParams.set('mode', e.detail.mode);

      const modal = Modal.advanced({
        type: Modal.types.iframe,
        content: url.toString(),
        size: Modal.sizes.large
      });
      modal.addEventListener('typo3-modal-hidden', (): void => {
        // @todo: this needs to be done when a folder was created. Apparently, backend user signals are not parsed in
        //        the modal's context. The best solution is probably to reload the "document space" via AJAX.
        top.list_frame.document.location.reload();
      });
    }).bindTo(document);

    DocumentService.ready().then((): void => {
      const fileListContainer = document.querySelector('.t3-filelist-container');

      new RegularEvent('click', (e: Event, trigger: HTMLAnchorElement): void => {
        e.preventDefault();

        document.dispatchEvent(new CustomEvent(fileListOpenElementBrowser, {
          detail: {
            actionUrl: trigger.href,
            identifier: trigger.dataset.identifier,
            mode: trigger.dataset.mode,
          }
        }));
      }).delegateTo(document, '.t3js-element-browser');

      Tooltip.initialize('.table-fit a[title]');
      // file index events
      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        Filelist.openInfoPopup(
          target.dataset.filelistShowItemType,
          target.dataset.filelistShowItemIdentifier
        );
      }).delegateTo(document, '[data-filelist-show-item-identifier][data-filelist-show-item-type]');

      // file search events
      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        Filelist.openInfoPopup('_FILE', target.dataset.identifier);
      }).delegateTo(document, 'a.filelist-file-info');

      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        Filelist.openInfoPopup('_FILE', target.dataset.identifier);
      }).delegateTo(document, 'a.filelist-file-references');

      new RegularEvent('click', (event: Event, target: HTMLElement): void => {
        event.preventDefault();
        const url = target.getAttribute('href');
        let redirectUrl = (url)
          ? encodeURIComponent(url)
          : encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        top.list_frame.location.href = url + '&redirect=' + redirectUrl;
      }).delegateTo(document, 'a.filelist-file-copy');

      new RegularEvent('dragstart', (event: DragEvent, target: HTMLElement): void => {
        const dragCollection = [];
        const allCheckedItems = document.querySelectorAll(MultiRecordSelectionSelectors.checkboxSelector + ':checked') as NodeListOf<HTMLInputElement>;
        if (allCheckedItems.length > 0) {
          // Build a drag collection for all checked items
          for (let checkedItem of allCheckedItems) {
            const draggableElement = checkedItem.closest('[data-filelist-draggable-container]') as HTMLElement;
            dragCollection.push({
              type: draggableElement.dataset.type,
              identifier: draggableElement.dataset.identifier,
              name: draggableElement.dataset.name,
              extra: {
                referencedElement: (draggableElement.querySelector('img') as HTMLImageElement|null),
                stateIdentifier: draggableElement.dataset.stateIdentifier,
              }
            });
          }
        } else {
          // Only drag current item
          const containerElement = target.closest('[data-filelist-draggable-container]') as HTMLElement;
          dragCollection.push({
            type: containerElement.dataset.type,
            identifier: containerElement.dataset.identifier,
            name: containerElement.dataset.name,
            extra: {
              referencedElement: (containerElement.querySelector('img') as HTMLImageElement|null),
              stateIdentifier: containerElement.dataset.stateIdentifier,
            }
          });
        }

        this.drawDataTransferPreview(event.dataTransfer, dragCollection);
        event.dataTransfer.setData('application/json', JSON.stringify(dragCollection));
      }).delegateTo(document, '.t3-filelist-container [draggable="true"]');
    });

    // Respond to multi record selection action events
    new RegularEvent('multiRecordSelection:action:edit', this.editFileMetadata).bindTo(document);
    new RegularEvent('multiRecordSelection:action:delete', this.deleteMultiple).bindTo(document);
    new RegularEvent('multiRecordSelection:action:download', this.downloadFilesAndFolders).bindTo(document);
    new RegularEvent('click', this.downloadFolder).delegateTo(document, 'button[data-folder-download]');
    new RegularEvent('multiRecordSelection:action:copyMarked', (event: CustomEvent): void => {
      Filelist.submitClipboardFormWithCommand('copyMarked', event.target as HTMLButtonElement)
    }).bindTo(document);
    new RegularEvent('multiRecordSelection:action:removeMarked', (event: CustomEvent): void => {
      Filelist.submitClipboardFormWithCommand('removeMarked', event.target as HTMLButtonElement)
    }).bindTo(document);

    new RegularEvent('multiRecordSelection:checkbox:state:changed', (event: CustomEvent): void => {
      const checkbox = event.target as HTMLInputElement;
      const checkboxContainer: HTMLElement = checkbox.closest(MultiRecordSelectionSelectors.elementSelector);
      if (checkboxContainer !== null) {
        checkboxContainer.draggable = checkbox.checked;
      }

      if (checkbox.checked) {
        // Disable dragging for all unchecked items
        const allUncheckedItems = document.querySelectorAll(MultiRecordSelectionSelectors.checkboxSelector + ':not(:checked)') as NodeListOf<HTMLInputElement>;
        allUncheckedItems.forEach((checkboxElement: HTMLElement): void => {
          const draggableElement = checkboxElement.closest('[data-filelist-draggable]') as HTMLElement;
          draggableElement.draggable = false;
          draggableElement.querySelectorAll('[data-filelist-draggable]').forEach((nestedDraggableElement: HTMLElement) => {
            nestedDraggableElement.draggable = false;
          })
        });
      } else {
        // Check if all checkboxes are unchecked => set draggable to true again
        const allCheckedItems = document.querySelectorAll(MultiRecordSelectionSelectors.checkboxSelector + ':checked') as NodeListOf<HTMLInputElement>;
        if (allCheckedItems.length === 0) {
          document.querySelectorAll('.t3-filelist-container [data-filelist-draggable]').forEach((draggableElement: HTMLElement): void => {
            draggableElement.draggable = true;
            draggableElement.querySelectorAll('[data-filelist-draggable]').forEach((nestedDraggableElement: HTMLElement) => {
              nestedDraggableElement.draggable = true;
            })
          });
        }
      }
    }).bindTo(document);

    // Respond to browser related clearable event
    const activeSearch: boolean = (document.querySelector([Selectors.fileListFormSelector, Selectors.searchFieldSelector].join(' ')) as HTMLInputElement)?.value !== '';
    new RegularEvent('search', (event: Event): void => {
      const searchField: HTMLInputElement = event.target as HTMLInputElement;
      if (searchField.value === '' && activeSearch) {
        (searchField.closest(Selectors.fileListFormSelector) as HTMLFormElement)?.submit();
      }
    }).delegateTo(document, Selectors.searchFieldSelector);
  }

  /**
   * Draws a new ghost image for the dataTransfer drag.
   */
  private drawDataTransferPreview(dataTransfer: DataTransfer|null, dragCollection: DragDropDataTransferItem[]): void {
    if (dataTransfer === null || dragCollection.length === 0) {
      return;
    }

    const canvasId = 'dragstart-canvas';
    document.getElementById(canvasId)?.remove();

    const canvas = document.createElement('canvas') as HTMLCanvasElement;
    const ctx = canvas.getContext('2d');

    // Check if the collection contains references to non-HTMLImageElement elements.
    // If so, do not draw a fancy ghost image, the Canvas API cannot consume plain SVG objects.
    const collectionContainsNonImageReference = dragCollection.some((item: DragDropDataTransferItem): boolean => {
      return !(item.extra.referencedElement instanceof HTMLImageElement);
    });
    if (!collectionContainsNonImageReference && dragCollection.length <= 5) {
      const canvasOperations = this.calculateDrawImageCanvasConfiguration(dragCollection);
      canvas.width = canvasOperations.width;
      canvas.height = canvasOperations.height;

      for (let op of canvasOperations.operations) {
        ctx.drawImage(op.reference, op.offsetX, op.offsetY);
      }
    } else {
      const strokeWidth = 1;
      const ghostText = dragCollection.length.toString(10);

      // Draw counter
      ctx.font = '16px verdana, arial, sans-serif';
      const textMeasurement = ctx.measureText(ghostText);

      const width = Math.max(Math.ceil(textMeasurement.width)) + 32;
      const height = 32;
      canvas.width = width;
      canvas.height = height;

      // Draw rect
      ctx.beginPath();
      ctx.rect(0, 0, width, height)
      ctx.fillStyle = '#f2f8fe';
      ctx.fill();
      ctx.lineWidth = strokeWidth;
      ctx.strokeStyle = '#3393eb';
      ctx.stroke();

      // Draw counter;
      ctx.fillStyle = '#313131'
      ctx.textAlign = 'center';
      ctx.textBaseline = 'middle';
      ctx.font = '16px verdana, arial, sans-serif';
      ctx.fillText(ghostText, width / 2, height / 2);
    }

    const img = document.createElement('img') as HTMLImageElement
    img.id = canvasId;
    img.src = canvas.toDataURL('data/png');
    document.body.appendChild(img);

    // @todo This is ugly right now, browsers draw this semi-transparent, being non-configurable - we probably need
    //       something like https://stackoverflow.com/a/31177307/4828813 instead
    dataTransfer.setDragImage(img, 0, 0);
  }

  private calculateDrawImageCanvasConfiguration(dragCollection: DragDropDataTransferItem[]): DragImageCanvasConfiguration {
    let width = 0;
    let height = 0;
    let offsetX = 0;
    let offsetY = 0;
    let operations = [];

    // Remove items with any non-image reference
    dragCollection = dragCollection.filter((item: DragDropDataTransferItem): boolean => {
      return item.extra.referencedElement instanceof HTMLImageElement;
    });

    for (let i = 0; i < dragCollection.length; ++i) {
      const referencedElement = dragCollection[i].extra.referencedElement;
      if (i > 0) {
        offsetX += Math.max(20, Math.floor(referencedElement.width / 100 * 20));
        offsetY += Math.max(20, Math.floor(referencedElement.height / 100 * 20));
      }

      if (referencedElement.width + offsetX > width) {
        width = referencedElement.width + offsetX;
      }
      if (referencedElement.height + offsetY > height) {
        height = referencedElement.height + offsetY;
      }

      operations.push({
        offsetX,
        offsetY,
        reference: referencedElement
      });
    }

    return {
      width,
      height,
      operations
    };
  }

  private deleteMultiple(e: CustomEvent): void {
    e.preventDefault();
    const eventDetails: ActionEventDetails = e.detail as ActionEventDetails;
    const configuration: DeleteFileMetadataConfiguration = eventDetails.configuration;
    Modal.advanced({
      title: configuration.title || 'Delete',
      content: configuration.content || 'Are you sure you want to delete those files and folders?',
      severity: SeverityEnum.warning,
      buttons: [
        {
          text: TYPO3.lang['button.close'] || 'Close',
          active: true,
          btnClass: 'btn-default',
          trigger: (modalEvent: Event, modal: ModalElement) => modal.hideModal(),
        },
        {
          text: configuration.ok || TYPO3.lang['button.ok'] || 'OK',
          btnClass: 'btn-' + Severity.getCssClass(SeverityEnum.warning),
          trigger: (modalEvent: Event, modal: ModalElement) => {
            Filelist.submitClipboardFormWithCommand('delete', e.target as HTMLButtonElement)
            modal.hideModal();
          }
        }
      ]
    });
  }

  private editFileMetadata(e: CustomEvent): void {
    e.preventDefault();
    const eventDetails: ActionEventDetails = e.detail;
    const configuration: EditFileMetadataConfiguration = eventDetails.configuration;
    if (!configuration || !configuration.idField || !configuration.table) {
      return;
    }
    const list: Array<string> = [];
    (eventDetails.checkboxes as NodeListOf<HTMLInputElement>).forEach((checkbox: HTMLInputElement) => {
      const checkboxContainer: HTMLElement = checkbox.closest(MultiRecordSelectionSelectors.elementSelector);
      if (checkboxContainer !== null && checkboxContainer.dataset[configuration.idField]) {
        list.push(checkboxContainer.dataset[configuration.idField]);
      }
    });

    if (list.length) {
      window.location.href = top.TYPO3.settings.FormEngine.moduleUrl
        + '&edit[' + configuration.table + '][' + list.join(',') + ']=edit'
        + '&returnUrl=' + Filelist.getReturnUrl(configuration.returnUrl || '');
    } else {
      Notification.warning('The selected elements can not be edited.');
    }
  }

  private downloadFilesAndFolders = (e: CustomEvent): void => {
    const target: HTMLButtonElement = e.target as HTMLButtonElement;
    const eventDetails: ActionEventDetails = (e.detail as ActionEventDetails);
    const configuration: DownloadConfiguration = (eventDetails.configuration as DownloadConfiguration);

    const filesAndFolders: Array<string> = [];
    eventDetails.checkboxes.forEach((checkbox: HTMLInputElement) => {
      const checkboxContainer: HTMLElement = checkbox.closest(MultiRecordSelectionSelectors.elementSelector);
      if (checkboxContainer?.dataset[configuration.folderIdentifier]) {
        filesAndFolders.push(checkboxContainer.dataset[configuration.folderIdentifier]);
      } else if (checkboxContainer?.dataset[configuration.fileIdentifier]) {
        filesAndFolders.push(checkboxContainer.dataset[configuration.fileIdentifier]);
      }
    });
    if (filesAndFolders.length) {
      this.triggerDownload(filesAndFolders, configuration.downloadUrl, target);
    } else {
      Notification.warning(lll('file_download.invalidSelection'));
    }
  }


  private downloadFolder = (e: MouseEvent): void => {
    const target: HTMLButtonElement = e.target as HTMLButtonElement;
    const folderIdentifier = target.dataset.folderIdentifier;
    this.triggerDownload([folderIdentifier], target.dataset.folderDownload, target);
  }

  private triggerDownload(items: Array<string>, downloadUrl: string, button: HTMLElement): void {
    // Add notification about the download being prepared
    Notification.info(lll('file_download.prepare'), '', 2);
    // Store the targets' (button) content and replace with a spinner
    // icon, while the download is being prepared. Also disable the
    // button for this time to prevent the user from triggering it again.
    const targetContent: string = button.innerHTML;
    button.setAttribute('disabled', 'disabled');
    Icons.getIcon('spinner-circle-dark', Icons.sizes.small).then((spinner: string): void => {
      button.innerHTML = spinner;
    });
    // Configure and start the progress bar, while preparing
    NProgress
      .configure({parent: '#typo3-filelist', showSpinner: false})
      .start();
    (new AjaxRequest(downloadUrl)).post({items: items})
      .then(async (response: AjaxResponse): Promise<any> => {
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
        const downloadUrl = URL.createObjectURL(blob);
        const anchorTag = document.createElement('a');
        anchorTag.href = downloadUrl;
        anchorTag.download = fileName;
        document.body.appendChild(anchorTag);
        anchorTag.click();
        URL.revokeObjectURL(downloadUrl);
        document.body.removeChild(anchorTag);
        // Add notification about successful preparation
        Notification.success(lll('file_download.success'), '', 2);
      })
      .catch(() => {
        Notification.error(lll('file_download.error'));
      })
      .finally(() => {
        // Remove progress bar and restore target (button)
        NProgress.done();
        button.removeAttribute('disabled');
        button.innerHTML = targetContent;
      });
  }
}
