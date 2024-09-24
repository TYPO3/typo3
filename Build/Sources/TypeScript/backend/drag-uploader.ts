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
import { DateTime } from 'luxon';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { SeverityEnum } from './enum/severity';
import { MessageUtility } from './utility/message-utility';
import NProgress from 'nprogress';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { default as Modal, ModalElement, Sizes as ModalSizes } from './modal';
import Notification from './notification';
import ImmediateAction from '@typo3/backend/action-button/immediate-action';
import Md5 from '@typo3/backend/hashing/md5';
import '@typo3/backend/element/icon-element';
import RegularEvent from '@typo3/core/event/regular-event';
import DomHelper from '@typo3/backend/utility/dom-helper';
import { KeyTypesEnum } from '@typo3/backend/enum/key-types';
import '@typo3/backend/element/progress-bar-element';
import type { ProgressBarElement } from '@typo3/backend/element/progress-bar-element';

/**
 * Possible actions for conflicts w/ existing files
 */
enum Action {
  OVERRIDE = 'replace',
  RENAME = 'rename',
  SKIP = 'cancel',
  USE_EXISTING = 'useExisting',
}

/**
 * Properties of a file as returned from the AJAX action; essential, this is a serialized instance of
 * \TYPO3\CMS\Core\Resource\File plus some extra properties (see FileController::flattenResultDataValue())
 */
interface UploadedFile {
  name: string;
  id: number;
  uid: number;
  icon: string;
  extension: string;
  permissions: { read: boolean; write: boolean };
  size: number;
  // formatted as ddmmyy
  date: string;

  mtime: number;
  thumbUrl: string;
  type: string;
  path: string;
}

interface FileConflict {
  original: UploadedFile;
  uploaded: File;
  action: Action;
}

interface FlashMessage {
  title: string,
  message: string,
  severity: number
}

export default class DragUploader {
  public irreObjectUid: string;
  public fileList: HTMLElement;
  public fileListColumnCount: number;
  public filesExtensionsAllowed: string;
  public filesExtensionsDisallowed: string;
  public fileDenyPattern: RegExp | null;
  public maxFileSize: number;
  public trigger: HTMLElement;
  public target: string;
  public reloadUrl: string;
  public manualTable: boolean;

  /**
   * Array of files which are asked for being overridden
   */
  private askForOverride: Array<FileConflict> = [];

  private percentagePerFile: number = 1;

  private readonly body: HTMLElement;
  private readonly element: HTMLElement;
  private readonly dropzone: HTMLElement;
  private readonly dropzoneMask: HTMLElement;
  private readonly fileInput: HTMLInputElement;
  private readonly browserCapabilities: { fileReader: boolean; DnD: boolean; Progress: boolean };
  private readonly dropZoneInsertBefore: boolean;
  private queueLength: number;
  private readonly defaultAction: Action;
  private manuallyTriggered: boolean;

  /**
   * This property controls whether a drag was started within the document. If true, dropzones become unavailable.
   */
  private dragStartedInDocument: boolean = false;

  constructor(element: HTMLElement) {
    this.body = document.querySelector('body');
    this.element = element;
    const hasTrigger = this.element.dataset.dropzoneTrigger !== undefined;
    this.trigger = document.querySelector(this.element.dataset.dropzoneTrigger);
    this.defaultAction = this.element.dataset.defaultAction as Action || Action.SKIP;
    this.dropzone = document.createElement('div');
    this.dropzone.classList.add('dropzone');
    this.dropzone.setAttribute('hidden', 'hidden');
    this.irreObjectUid = this.element.dataset.fileIrreObject;

    const dropZoneEscapedTarget = document.querySelector(this.element.dataset.dropzoneTarget);
    if (this.irreObjectUid && DomHelper.nextAll(dropZoneEscapedTarget).length !== 0) {
      this.dropZoneInsertBefore = true;
      dropZoneEscapedTarget.before(this.dropzone);
    } else {
      this.dropZoneInsertBefore = false;
      dropZoneEscapedTarget.after(this.dropzone);
    }
    this.fileInput = <HTMLInputElement>document.createElement('input');
    this.fileInput.setAttribute('type', 'file');
    this.fileInput.setAttribute('multiple', 'multiple');
    this.fileInput.setAttribute('name', 'files[]');
    this.fileInput.classList.add('upload-file-picker');
    this.body.append(this.fileInput);

    this.fileList = document.querySelector(this.element.dataset.progressContainer);
    this.fileListColumnCount = this.fileList?.querySelectorAll('thead tr:first-child th').length + 1;
    this.filesExtensionsAllowed = this.element.dataset.fileAllowed;
    this.filesExtensionsDisallowed = this.element.dataset.fileDisallowed;
    this.fileDenyPattern = this.element.dataset.fileDenyPattern ? new RegExp(this.element.dataset.fileDenyPattern, 'i') : null;
    this.maxFileSize = parseInt(this.element.dataset.maxFileSize, 10);
    this.target = this.element.dataset.targetFolder;
    this.reloadUrl = this.element.dataset.reloadUrl;

    this.browserCapabilities = {
      fileReader: typeof FileReader !== 'undefined',
      DnD: 'draggable' in document.createElement('span'),
      Progress: 'upload' in new XMLHttpRequest,
    };


    if (!this.browserCapabilities.DnD) {
      console.warn('Browser has no Drag and drop capabilities; cannot initialize DragUploader');
      return;
    }

    this.body.addEventListener('dragstart', (): void => {
      this.dragStartedInDocument = true;
    });
    this.body.addEventListener('dragover', this.dragFileIntoDocument);
    this.body.addEventListener('dragend', this.dragAborted);
    this.body.addEventListener('drop', this.ignoreDrop);

    this.dropzone.innerHTML = ('<button type="button" class="dropzone-hint" aria-labelledby="dropzone-title">' +
      '<div class="dropzone-hint-media">' +
      '<div class="dropzone-hint-icon"></div>' +
      '</div>' +
      '<div class="dropzone-hint-body">' +
      '<h3 id="dropzone-title" class="dropzone-hint-title">' +
      TYPO3.lang['file_upload.dropzonehint.title'] +
      '</h3>' +
      '<p class="dropzone-hint-message">' +
      TYPO3.lang['file_upload.dropzonehint.message'] +
      '</p>' +
      '</div>' +
      '</div>'
    );

    this.dropzoneMask = document.createElement('div');
    this.dropzoneMask.classList.add('dropzone-mask');
    this.dropzone.append(this.dropzoneMask);

    this.dropzone.addEventListener('dragenter', this.fileInDropzone);
    this.dropzoneMask.addEventListener('dragenter', this.fileInDropzone);
    this.dropzoneMask.addEventListener('dragleave', this.fileOutOfDropzone);
    this.dropzoneMask.addEventListener('drop', (ev: Event) => this.handleDrop(<DragEvent>ev));

    this.dropzone.addEventListener('click', () => {
      this.fileInput.click();
    });

    const dropzoneCloseButton = document.createElement('button');
    dropzoneCloseButton.classList.add('dropzone-close');
    dropzoneCloseButton.type = 'button';
    dropzoneCloseButton.setAttribute('aria-label', TYPO3.lang['file_upload.dropzone.close']);
    dropzoneCloseButton.addEventListener('click', this.hideDropzone);
    this.dropzone.append(dropzoneCloseButton);

    // no filelist then create own progress table
    if (this.fileList === null) {
      this.fileList = document.createElement('table');
      this.fileList.setAttribute('id', 'typo3-filelist');
      this.fileList.classList.add('table', 'table-striped', 'table-hover', 'upload-queue');
      this.fileList.innerHTML = '<tbody></tbody>';
      const $tableContainer = document.createElement('div');
      $tableContainer.classList.add('table-fit');
      $tableContainer.setAttribute('hidden', 'hidden');
      $tableContainer.append(this.fileList);

      if (this.dropZoneInsertBefore) {
        this.dropzone.after($tableContainer);
      } else {
        this.dropzone.before($tableContainer);
      }
      this.fileListColumnCount = 8;
      this.manualTable = true;
    }

    this.fileInput.addEventListener('change', (event: Event) => {
      this.hideDropzone(event);
      this.processFiles(this.fileInput.files);
    });

    // Allow the user to hide the dropzone with the "Escape" key
    document.addEventListener('keydown', (event: KeyboardEvent): void => {
      if (event.key === KeyTypesEnum.ENTER && !this.dropzone.hasAttribute('hidden')) {
        this.hideDropzone(event);
      }
    });

    this.bindUploadButton(hasTrigger === true ? this.trigger : this.element);
  }

  public static init(): void {
    DocumentService.ready().then((): void => {
      document.querySelectorAll('.t3js-drag-uploader').forEach((element: HTMLElement): void => {
        new DragUploader(element);
      });
    });
  }

  public static fileSizeAsString(size: number): string {
    const sizeKB: number = size / 1024;
    let str = '';

    if (sizeKB > 1024) {
      str = (sizeKB / 1024).toFixed(1) + ' MB';
    } else {
      str = sizeKB.toFixed(1) + ' KB';
    }
    return str;
  }

  /**
   * @param {string} irreObject
   * @param {UploadedFile} file
   */
  public static addFileToIrre(irreObject: string, file: UploadedFile): void {
    const message = {
      actionName: 'typo3:foreignRelation:insert',
      objectGroup: irreObject,
      table: 'sys_file',
      uid: file.uid,
    };
    MessageUtility.send(message);
  }

  public showDropzone(): void {
    this.dropzone.removeAttribute('hidden');
  }

  /**
   *
   * @param {Event} event
   */
  public hideDropzone = (event: Event): void => {
    event.stopPropagation();
    event.preventDefault();
    this.dropzone.setAttribute('hidden', 'hidden');
    this.dropzone.classList.remove('drop-status-ok');
    // User manually hides the dropzone, so we can reset the flag
    this.manuallyTriggered = false;
  };

  /**
   * @param {Event} event
   * @returns {boolean}
   */
  public dragFileIntoDocument = (event: DragEvent): boolean => {
    if (this.dragStartedInDocument) {
      return false;
    }

    if (!event.dataTransfer.types.includes('Files')) {
      // Not a file upload (item drag)
      return false;
    }

    event.stopPropagation();
    event.preventDefault();
    (event.currentTarget as HTMLElement).classList.add('drop-in-progress');
    // Only show dropzone in case $element is currently visible. This prevents
    // use cases, such as opening the dropzone in a non visible tab in FormEngine.
    if (this.element.offsetParent) {
      this.showDropzone();
    }
    return false;
  };

  /**
   *
   * @param {Event} event
   * @returns {Boolean}
   */
  public dragAborted = (event: Event): boolean => {
    event.stopPropagation();
    event.preventDefault();
    (event.currentTarget as HTMLElement).classList.remove('drop-in-progress');
    this.dragStartedInDocument = false;
    return false;
  };

  public ignoreDrop = (event: Event): boolean => {
    // stops the browser from redirecting.
    event.stopPropagation();
    event.preventDefault();
    this.dragAborted(event);
    return false;
  };

  public handleDrop = (event: DragEvent): void => {
    this.ignoreDrop(event);
    this.hideDropzone(event);
    this.processFiles(event.dataTransfer.files);
  };

  /**
   * @param {FileList} files
   */
  public processFiles(files: FileList): void {
    this.queueLength = files.length;

    if (this.fileList.parentElement.hasAttribute('hidden')) {
      // Show the filelist (table)
      this.fileList.parentElement.removeAttribute('hidden');
      // Remove hidden state from table container (also makes column selection etc. visible)
      this.fileList.closest('.t3-filelist-table-container')?.classList.remove('hidden');
      // Hide the information container
      this.fileList.closest('form')?.querySelector('.t3-filelist-info-container')?.setAttribute('hidden', 'hidden');
    }

    NProgress.start();
    this.percentagePerFile = 1 / files.length;

    // Check for each file if is already exist before adding it to the queue
    const ajaxCalls: Promise<void>[] = [];
    Array.from(files).forEach((file: File) => {
      const request = new AjaxRequest(TYPO3.settings.ajaxUrls.file_exists).withQueryArguments({
        fileName: file.name,
        fileTarget: this.target,
      }).get({ cache: 'no-cache' }).then(async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        const fileExists = typeof data.uid !== 'undefined';
        if (fileExists) {
          this.askForOverride.push({
            original: data,
            uploaded: file,
            action: this.irreObjectUid ? Action.USE_EXISTING : this.defaultAction,
          });
          NProgress.inc(this.percentagePerFile);
        } else {
          new FileQueueItem(this, file, Action.SKIP);
        }
      });
      ajaxCalls.push(request);
    });

    Promise.all(ajaxCalls).then((): void => {
      this.drawOverrideModal();
      NProgress.done();
    });

    this.fileInput.value = '';
  }

  public fileInDropzone = (): void => {
    this.dropzone.classList.add('drop-status-ok');
  };

  public fileOutOfDropzone = (): void => {
    this.dropzone.classList.remove('drop-status-ok');
    // In case dropzone was not manually triggered and this is no manual (fake) table, hide it when leaving
    if (!this.manuallyTriggered) {
      this.dropzone.setAttribute('hidden', 'hidden');
    }
  };

  /**
   * Bind file picker to default upload button
   *
   * @param {Object} button
   */
  public bindUploadButton(button: HTMLElement): void {
    button.addEventListener('click', (event: Event) => {
      event.preventDefault();
      this.fileInput.click();
      // In case user manually triggers the dropzone, we add a flag
      this.manuallyTriggered = true;
    });
  }

  /**
   * Decrements the queue and renders a flash message if queue is empty
   */
  public decrementQueueLength(messages?: FlashMessage[]): void {
    if (this.queueLength > 0) {
      this.queueLength--;
      if (this.queueLength === 0) {
        const timeout: number = messages && messages.length ? 5000 : 0;
        if (timeout) {
          for (const flashMessage of messages) {
            Notification.showMessage(flashMessage.title, flashMessage.message, flashMessage.severity);
          }
        }

        if (this.reloadUrl) {
          // After 5 seconds (when flash messages have disappeared), provide the user the option to reload the module
          setTimeout(() => {
            Notification.info(
              TYPO3.lang['file_upload.reload.filelist'],
              TYPO3.lang['file_upload.reload.filelist.message'],
              10,
              [
                {
                  label: TYPO3.lang['file_upload.reload.filelist.actions.dismiss'],
                },
                {
                  label: TYPO3.lang['file_upload.reload.filelist.actions.reload'],
                  action: new ImmediateAction((): void => {
                    top.list_frame.document.location.href = this.reloadUrl;
                  })
                }
              ]
            );
          }, timeout);
        }
      }
    }
  }

  /**
   * Renders the modal for existing files
   */
  public drawOverrideModal(): void {
    const amountOfItems = Object.keys(this.askForOverride).length;
    if (amountOfItems === 0) {
      return;
    }
    const $modalContent = document.createElement('div');
    let htmlContent = `
      <p>${TYPO3.lang['file_upload.existingfiles.description']}</p>
      <table class="table">
        <thead>
          <tr>
            <th></th>
            <th>${TYPO3.lang['file_upload.header.originalFile']}</th>
            <th>${TYPO3.lang['file_upload.header.uploadedFile']}</th>
            <th>${TYPO3.lang['file_upload.header.action']}</th>
          </tr>
        </thead>
        <tbody>
    `;
    for (let i = 0; i < amountOfItems; ++i) {
      const $record = `
        <tr>
          <td>
  ${this.askForOverride[i].original.thumbUrl !== ''
    ? `<img src="${this.askForOverride[i].original.thumbUrl}" height="40" />`
    : this.askForOverride[i].original.icon}
          </td>
          <td>
            ${this.askForOverride[i].original.name} (${DragUploader.fileSizeAsString(this.askForOverride[i].original.size)})<br />
            ${DateTime.fromSeconds(this.askForOverride[i].original.mtime).toLocaleString(DateTime.DATETIME_MED)}
          </td>
          <td>
            ${this.askForOverride[i].uploaded.name} (${DragUploader.fileSizeAsString(this.askForOverride[i].uploaded.size)})<br />
            ${DateTime.fromMillis(this.askForOverride[i].uploaded.lastModified).toLocaleString(DateTime.DATETIME_MED)}
          </td>
          <td>
            <select class="form-select t3js-actions" data-override="${i}">
              ${this.irreObjectUid ? `<option value="${Action.USE_EXISTING}">${TYPO3.lang['file_upload.actions.use_existing']}</option>` : ''}
              <option value="${Action.SKIP}" ${this.defaultAction === Action.SKIP ? 'selected' : ''}>${TYPO3.lang['file_upload.actions.skip']}</option>
              <option value="${Action.RENAME}" ${this.defaultAction === Action.RENAME ? 'selected' : ''}>${TYPO3.lang['file_upload.actions.rename']}</option>
              <option value="${Action.OVERRIDE}" ${this.defaultAction === Action.OVERRIDE ? 'selected' : ''}>${TYPO3.lang['file_upload.actions.override']}</option>
            </select>
          </td>
        </tr>
      `;
      htmlContent += $record;
    }

    htmlContent += '</tbody></table>';
    $modalContent.innerHTML = htmlContent;

    const modal = Modal.advanced({
      title: TYPO3.lang['file_upload.existingfiles.title'],
      content: $modalContent,
      severity: SeverityEnum.warning,
      buttons: [
        {
          text: TYPO3.lang['file_upload.button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: TYPO3.lang['file_upload.button.continue'] || 'Continue with selected actions',
          btnClass: 'btn-warning',
          name: 'continue',
        },
      ],
      additionalCssClasses: ['modal-inner-scroll'],
      size: ModalSizes.large,
      callback: (modal: ModalElement): void => {
        const modalFooter = modal.querySelector('.modal-footer');

        const allActionLabel = document.createElement('label');
        allActionLabel.textContent = TYPO3.lang['file_upload.actions.all.label'];

        const allActionSelect = document.createElement('span');
        allActionSelect.innerHTML = `
          <select class="form-select t3js-actions-all">
            <option value="">${TYPO3.lang['file_upload.actions.all.empty']}</option>
            ${this.irreObjectUid ? `<option value="${Action.USE_EXISTING}">${TYPO3.lang['file_upload.actions.all.use_existing']}</option>` : ''}
            <option value="${Action.SKIP}" ${this.defaultAction === Action.SKIP ? 'selected' : ''}>${TYPO3.lang['file_upload.actions.all.skip']}</option>
            <option value="${Action.RENAME}" ${this.defaultAction === Action.RENAME ? 'selected' : ''}>${TYPO3.lang['file_upload.actions.all.rename']}</option>
            <option value="${Action.OVERRIDE}" ${this.defaultAction === Action.OVERRIDE ? 'selected' : ''}>${TYPO3.lang['file_upload.actions.all.override']}</option>
          </select>
        `;

        modalFooter.prepend(allActionLabel, allActionSelect);
      }
    });

    new RegularEvent('change', (event: Event, target: HTMLSelectElement) => {
      if (target.value !== '') {
        // mass action was selected, apply action to every file
        for (const select of modal.querySelectorAll('.t3js-actions') as NodeListOf<HTMLSelectElement>) {
          const index = parseInt(select.dataset.override, 10);
          select.value = target.value;
          select.disabled = true;
          this.askForOverride[index].action = <Action>select.value;
        }
      } else {
        modal.querySelectorAll('.t3js-actions').forEach((select: HTMLSelectElement) => select.disabled = false);
      }
    }).delegateTo(modal, '.t3js-actions-all');

    new RegularEvent('change', (event: Event) => {
      const actionSelect = event.target as HTMLSelectElement,
        index = parseInt(actionSelect.dataset.override, 10);
      this.askForOverride[index].action = <Action>actionSelect.value;
    }).delegateTo(modal, '.t3js-actions');

    modal.addEventListener('button.clicked', (e: Event): void => {
      const button = e.target as HTMLButtonElement;
      if (button.name === 'cancel') {
        this.askForOverride = [];
        Modal.dismiss();
      } else if (button.name === 'continue') {
        for (const fileInfo of this.askForOverride) {
          if (fileInfo.action === Action.USE_EXISTING) {
            DragUploader.addFileToIrre(
              this.irreObjectUid,
              fileInfo.original,
            );
          } else if (fileInfo.action !== Action.SKIP) {
            new FileQueueItem(this, fileInfo.uploaded, fileInfo.action);
          }
        }
        this.askForOverride = [];
        modal.hideModal();
      }
    });

    modal.addEventListener('typo3-modal-hidden', () => {
      this.askForOverride = [];
    });
  }
}

class FileQueueItem {
  private readonly row: HTMLElement;
  private readonly progress: HTMLElement;
  private readonly file: File;
  private readonly override: Action;
  private readonly selector: HTMLElement;
  private readonly iconCol: HTMLElement;
  private readonly fileName: HTMLElement;
  private readonly progressBar: ProgressBarElement;
  private readonly dragUploader: DragUploader;

  constructor(dragUploader: DragUploader, file: File, override: Action) {
    this.dragUploader = dragUploader;
    this.file = file;
    this.override = override;

    this.row = document.createElement('tr');
    this.row.classList.add('upload-queue-item');

    if (!this.dragUploader.manualTable) {
      // Add selector cell, if this is a real table (e.g. not in FormEngine)
      this.selector = document.createElement('td');
      this.selector.classList.add('col-checkbox');
      this.row.append(this.selector);
    }
    this.iconCol = document.createElement('td');
    this.iconCol.classList.add('col-icon');
    this.row.append(this.iconCol);

    this.fileName = document.createElement('td');
    this.fileName.classList.add('col-title', 'col-responsive');
    this.fileName.textContent = file.name;
    this.row.append(this.fileName);

    this.progress = document.createElement('td');
    this.progress.classList.add('col-progress');
    this.progress.setAttribute('colspan', String(this.dragUploader.fileListColumnCount - this.row.querySelectorAll('td').length));
    this.row.append(this.progress);

    this.progressBar = document.createElement('typo3-backend-progress-bar');
    this.progress.append(this.progressBar);

    // position queue item in filelist
    if (this.dragUploader.fileList.querySelectorAll('tbody tr.upload-queue-item').length === 0) {
      this.dragUploader.fileList.querySelector('tbody').prepend(this.row);
      this.row.classList.add('last');
    } else {
      this.dragUploader.fileList.querySelector('tbody tr.upload-queue-item:first-child').before(this.row);
    }

    // Set a disabled checkbox to the selector column, if available
    if (this.selector) {
      this.selector.innerHTML = (
        '<span class="form-check form-check-type-toggle">' +
        '<input type="checkbox" class="form-check-input t3js-multi-record-selection-check" disabled/>' +
        '</span>'
      );
    }

    // set dummy file icon
    this.iconCol.innerHTML = '<typo3-backend-icon identifier="mimetypes-other-other" />';

    // check file size
    if (this.dragUploader.maxFileSize > 0 && this.file.size > this.dragUploader.maxFileSize) {
      this.updateMessage(TYPO3.lang['file_upload.maxFileSizeExceeded']
        .replace(/\{0\}/g, this.file.name)
        .replace(/\{1\}/g, DragUploader.fileSizeAsString(this.dragUploader.maxFileSize)));
      this.progressBar.value = 100;
      this.progressBar.severity = SeverityEnum.error;

      // check filename/extension against deny pattern
    } else if (this.dragUploader.fileDenyPattern && this.file.name.match(this.dragUploader.fileDenyPattern)) {
      this.updateMessage(TYPO3.lang['file_upload.fileNotAllowed'].replace(/\{0\}/g, this.file.name));
      this.progressBar.value = 100;
      this.progressBar.severity = SeverityEnum.error;

    } else if (!this.checkAllowedExtensions()) {
      this.updateMessage(TYPO3.lang['file_upload.fileExtensionExpected'].replace(/\{0\}/g, this.dragUploader.filesExtensionsAllowed));
      this.progressBar.value = 100;
      this.progressBar.severity = SeverityEnum.error;
    } else if (!this.checkDisallowedExtensions()) {
      this.updateMessage(TYPO3.lang['file_upload.fileExtensionDisallowed'].replace(/\{0\}/g, this.dragUploader.filesExtensionsDisallowed));
      this.progressBar.value = 100;
      this.progressBar.severity = SeverityEnum.error;
    } else {
      this.updateMessage('- ' + DragUploader.fileSizeAsString(this.file.size));

      const formData = new FormData();
      formData.append('data[upload][1][target]', this.dragUploader.target);
      formData.append('data[upload][1][data]', '1');
      formData.append('overwriteExistingFiles', this.override);
      formData.append('redirect', '');
      formData.append('upload_1', this.file);

      // We use XMLHttpRequest as we need the `progress` event which isn't supported by fetch()
      const xhr = new XMLHttpRequest();
      xhr.onreadystatechange = (): void => {
        if (xhr.readyState === XMLHttpRequest.DONE) {
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (!response.hasErrors) {
                this.uploadSuccess(response);
              } else {
                this.uploadError(xhr);
              }
            }
            catch {
              // In case JSON can not be parsed, the upload failed due to server errors,
              // e.g. "POST Content-Length exceeds limit". Just handle as upload error.
              this.uploadError(xhr);
            }
          } else {
            this.uploadError(xhr);
          }
        }
      };
      xhr.upload.addEventListener('progress', (e: ProgressEvent) => this.updateProgress(e));
      xhr.open('POST', TYPO3.settings.ajaxUrls.file_process);
      xhr.send(formData);
    }
  }

  /**
   * @param {string} message
   */
  public updateMessage(message: string): void {
    this.progressBar.label = message;
  }

  /**
   * Remove the progress bar
   */
  public removeProgress(): void {
    if (this.progress) {
      this.progress.remove();
    }
  }

  /**
   * @param {XMLHttpRequest} response
   */
  public uploadError(response: XMLHttpRequest): void {
    const errorText = TYPO3.lang['file_upload.uploadFailed'].replace(/\{0\}/g, this.file.name);
    this.updateMessage(errorText);
    try {
      const jsonResponse = JSON.parse(response.responseText) as any;
      const messages = jsonResponse.messages as FlashMessage[];
      if (messages && messages.length) {
        for (const flashMessage of messages) {
          Notification.showMessage(flashMessage.title, flashMessage.message, flashMessage.severity, 10);
        }
      }
    } catch {
      // do nothing in case JSON could not be parsed
    }

    this.progressBar.severity = SeverityEnum.error;
    this.dragUploader.decrementQueueLength();
    this.dragUploader.trigger?.dispatchEvent(new CustomEvent('uploadError', { detail: [this, response] }));
  }

  /**
   * @param {ProgressEvent} event
   */
  public updateProgress(event: ProgressEvent): void {
    const percentage = Math.round((event.loaded / event.total) * 100);
    this.progressBar.value = percentage;
    this.progressBar.label = `${TYPO3.lang['file_upload.upload-in-progress']} ${percentage}%`;
    this.dragUploader.trigger?.dispatchEvent(new CustomEvent('updateProgress', { detail: [this, percentage, event] }));
  }

  /**
   * @param {{upload?: UploadedFile[]}} data
   */
  public uploadSuccess(data: { upload?: UploadedFile[], messages?: FlashMessage[] }): void {
    if (data.upload) {
      this.dragUploader.decrementQueueLength(data.messages);
      this.row.setAttribute('data-type', 'file');
      this.row.setAttribute('data-file-uid', String(data.upload[0].uid));
      this.fileName.textContent = data.upload[0].name;
      this.progressBar.value = 100;
      this.progressBar.label = TYPO3.lang['file_upload.uploadSucceeded'];
      this.progressBar.severity = SeverityEnum.ok;

      const combinedIdentifier: string = String(data.upload[0].id);

      // Enable checkbox, if available
      if (this.selector) {
        const checkbox: HTMLInputElement = <HTMLInputElement>this.selector.querySelector('input');
        if (checkbox) {
          checkbox.removeAttribute('disabled');
          checkbox.setAttribute('name', 'CBC[_FILE|' + Md5.hash(combinedIdentifier) + ']');
          checkbox.setAttribute('value', combinedIdentifier);
        }
      }

      // replace file icon
      if (data.upload[0].icon) {
        this.iconCol
          .innerHTML = (
            '<button type="button" class="btn btn-link p-0" data-contextmenu-trigger="click" data-contextmenu-uid="'
            + combinedIdentifier + '" data-contextmenu-table="sys_file" aria-label="'
            + (TYPO3.lang['labels.contextMenu.open'] || 'Open context menu') + '">'
            + data.upload[0].icon + '</span></button>'
          );
      }

      if (this.dragUploader.irreObjectUid) {
        DragUploader.addFileToIrre(
          this.dragUploader.irreObjectUid,
          data.upload[0],
        );
        setTimeout(
          () => {
            this.row.remove();
            if (this.dragUploader.fileList.querySelectorAll('tr').length === 0) {
              this.dragUploader.fileList.setAttribute('hidden', 'hidden');
              this.dragUploader.fileList.closest('.t3-filelist-table-container')?.classList.add('hidden');
              this.dragUploader.trigger?.dispatchEvent(new CustomEvent('uploadSuccess', { detail: [this, data] }));
            }
          },
          3000);
      } else {
        setTimeout(
          () => {
            this.showFileInfo(data.upload[0]);
            this.dragUploader.trigger?.dispatchEvent(new CustomEvent('uploadSuccess', { detail: [this, data] }));
          },
          3000);
      }
    }
  }

  /**
   * @param {UploadedFile} fileInfo
   */
  public showFileInfo(fileInfo: UploadedFile): void {
    this.removeProgress();
    if ((document.querySelector('#filelist-searchterm') as HTMLInputElement)?.value) {
      // When search is active, the PATH column is always present so we add it
      const pathColumn = document.createElement('td');
      pathColumn.textContent = fileInfo.path;
      this.row.append(pathColumn);
    }
    // Controls column is deliberately empty
    const controlsColumn = document.createElement('td');
    controlsColumn.classList.add('col-control');
    this.row.append(controlsColumn);

    const fileExtColumn = document.createElement('td');
    fileExtColumn.textContent = TYPO3.lang['type.file'] + ' (' + fileInfo.extension.toUpperCase() + ')';
    this.row.append(fileExtColumn);

    const fileSizeColumn = document.createElement('td');
    fileSizeColumn.textContent = DragUploader.fileSizeAsString(fileInfo.size);
    this.row.append(fileSizeColumn);

    let permissions = '';
    if (fileInfo.permissions.read) {
      permissions += '<strong class="text-danger">' + TYPO3.lang['permissions.read'] + '</strong>';
    }
    if (fileInfo.permissions.write) {
      permissions += '<strong class="text-danger">' + TYPO3.lang['permissions.write'] + '</strong>';
    }

    const permissionsColumn = document.createElement('td');
    permissionsColumn.innerHTML = permissions;
    this.row.append(permissionsColumn);

    const emptyColumn = document.createElement('td');
    emptyColumn.textContent = '-';
    this.row.append(emptyColumn);

    // add spacing cells when more columns are displayed (column selector)
    for (let i = this.row.querySelectorAll('td').length; i < this.dragUploader.fileListColumnCount; i++) {
      this.row.append(document.createElement('td'));
    }
  }

  public checkAllowedExtensions(): boolean {
    if (!this.dragUploader.filesExtensionsAllowed) {
      return true;
    }
    const extension = this.file.name.split('.').pop();
    const allowed = this.dragUploader.filesExtensionsAllowed.split(',');

    return allowed.includes(extension.toLowerCase());
  }

  public checkDisallowedExtensions(): boolean {
    if (!this.dragUploader.filesExtensionsDisallowed) {
      return true;
    }
    const extension = this.file.name.split('.').pop();
    const disallowed = this.dragUploader.filesExtensionsDisallowed.split(',');

    return disallowed.includes(extension.toLowerCase());
  }
}

DragUploader.init();
