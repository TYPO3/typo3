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
import $ from 'jquery';
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

/**
 * Possible actions for conflicts w/ existing files
 */
enum Action {
  OVERRIDE = 'replace',
  RENAME = 'rename',
  SKIP = 'cancel',
  USE_EXISTING = 'useExisting',
}

declare global {
  interface JQuery {
    dragUploader(options?: DragUploaderOptions | string): JQuery;
  }
}

interface JQueryTypedEvent<T extends Event> extends JQueryEventObject {
  originalEvent: T;
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

interface DragUploaderOptions {
  /**
   * CSS selector for the element where generated messages are inserted. (required)
   */
  outputSelector: string;
  /**
   * Color of the message text. (optional)
   */
  outputColor?: string;
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

class DragUploaderPlugin {
  public irreObjectUid: number;
  public $fileList: JQuery;
  public fileListColumnCount: number;
  public filesExtensionsAllowed: string;
  public filesExtensionsDisallowed: string;
  public fileDenyPattern: RegExp | null;
  public maxFileSize: number;
  public $trigger: JQuery;
  public target: string;
  public reloadUrl: string;
  public manualTable: boolean;

  /**
   * Array of files which are asked for being overridden
   */
  private askForOverride: Array<FileConflict> = [];

  private percentagePerFile: number = 1;

  private readonly $body: JQuery;
  private readonly $element: JQuery;
  private readonly $dropzone: JQuery;
  private readonly $dropzoneMask: JQuery;
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
    this.$body = $('body');
    this.$element = $(element);
    const hasTrigger = this.$element.data('dropzoneTrigger') !== undefined;
    this.$trigger = $(this.$element.data('dropzoneTrigger'));
    this.defaultAction = this.$element.data('defaultAction') || Action.SKIP;
    this.$dropzone = $('<div />').addClass('dropzone').hide();
    this.irreObjectUid = this.$element.data('fileIrreObject');

    const dropZoneEscapedTarget = this.$element.data('dropzoneTarget');
    if (this.irreObjectUid && this.$element.nextAll(dropZoneEscapedTarget).length !== 0) {
      this.dropZoneInsertBefore = true;
      this.$dropzone.insertBefore(dropZoneEscapedTarget);
    } else {
      this.dropZoneInsertBefore = false;
      this.$dropzone.insertAfter(dropZoneEscapedTarget);
    }
    this.$dropzoneMask = $('<div />').addClass('dropzone-mask').appendTo(this.$dropzone);
    this.fileInput = <HTMLInputElement>document.createElement('input');
    this.fileInput.setAttribute('type', 'file');
    this.fileInput.setAttribute('multiple', 'multiple');
    this.fileInput.setAttribute('name', 'files[]');
    this.fileInput.classList.add('upload-file-picker');
    this.$body.append(this.fileInput);

    this.$fileList = $(this.$element.data('progress-container'));
    this.fileListColumnCount = $('thead tr:first th', this.$fileList).length + 1;
    this.filesExtensionsAllowed = this.$element.data('file-allowed');
    this.filesExtensionsDisallowed = this.$element.data('file-disallowed');
    this.fileDenyPattern = this.$element.data('file-deny-pattern') ? new RegExp(this.$element.data('file-deny-pattern'), 'i') : null;
    this.maxFileSize = parseInt(this.$element.data('max-file-size'), 10);
    this.target = this.$element.data('target-folder');
    this.reloadUrl = this.$element.data('reload-url');

    this.browserCapabilities = {
      fileReader: typeof FileReader !== 'undefined',
      DnD: 'draggable' in document.createElement('span'),
      Progress: 'upload' in new XMLHttpRequest,
    };


    if (!this.browserCapabilities.DnD) {
      console.warn('Browser has no Drag and drop capabilities; cannot initialize DragUploader');
      return;
    }

    this.$body.on('dragstart', (): void => {
      this.dragStartedInDocument = true;
    });
    this.$body.on('dragover', this.dragFileIntoDocument);
    this.$body.on('dragend', this.dragAborted);
    this.$body.on('drop', this.ignoreDrop);

    this.$dropzone.on('dragenter', this.fileInDropzone);
    this.$dropzoneMask.on('dragenter', this.fileInDropzone);
    this.$dropzoneMask.on('dragleave', this.fileOutOfDropzone);
    this.$dropzoneMask.on('drop', (ev: JQueryEventObject) => this.handleDrop(<JQueryTypedEvent<DragEvent>>ev));

    this.$dropzone.prepend('<button type="button" class="dropzone-hint" aria-labelledby="dropzone-title">' +
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
      '</div>',
    ).on('click', () => {
      this.fileInput.click();
    });
    $('<button type="button" />')
      .addClass('dropzone-close')
      .attr('aria-label', TYPO3.lang['file_upload.dropzone.close'])
      .on('click', this.hideDropzone).appendTo(this.$dropzone);

    // no filelist then create own progress table
    if (this.$fileList.length === 0) {
      this.$fileList = $('<table />')
        .attr('id', 'typo3-filelist')
        .addClass('table table-striped table-hover upload-queue')
        .html('<tbody></tbody>');
      const $tableContainer = $('<div/>', { 'class': 'table-fit' }).hide()
        .append(this.$fileList);

      if (this.dropZoneInsertBefore) {
        $tableContainer.insertAfter(this.$dropzone);
      } else {
        $tableContainer.insertBefore(this.$dropzone);
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
      if (event.code === 'Escape' && this.$dropzone.is(':visible')) {
        this.hideDropzone(event);
      }
    });

    this.bindUploadButton(hasTrigger === true ? this.$trigger : this.$element);
  }

  public showDropzone(): void {
    this.$dropzone.show();
  }

  /**
   *
   * @param {Event} event
   */
  public hideDropzone = (event: Event): void => {
    event.stopPropagation();
    event.preventDefault();
    this.$dropzone.hide();
    this.$dropzone.removeClass('drop-status-ok');
    // User manually hides the dropzone, so we can reset the flag
    this.manuallyTriggered = false;
  };

  /**
   * @param {Event} event
   * @returns {boolean}
   */
  public dragFileIntoDocument = (event: JQueryTypedEvent<DragEvent>): boolean => {
    if (this.dragStartedInDocument) {
      return false;
    }

    event.stopPropagation();
    event.preventDefault();
    $(event.currentTarget).addClass('drop-in-progress');
    // Only show dropzone in case $element is currently visible. This prevents
    // use cases, such as opening the dropzone in a non visible tab in FormEngine.
    if (this.$element.get(0)?.offsetParent) {
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
    $(event.currentTarget).removeClass('drop-in-progress');
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

  public handleDrop = (event: JQueryTypedEvent<DragEvent>): void => {
    this.ignoreDrop(event);
    this.hideDropzone(event);
    this.processFiles(event.originalEvent.dataTransfer.files);
  };

  /**
   * @param {FileList} files
   */
  public processFiles(files: FileList): void {
    this.queueLength = files.length;

    if (!this.$fileList.parent().is(':visible')) {
      // Show the filelist (table)
      this.$fileList.parent().show();
      // Remove hidden state from table container (also makes column selection etc. visible)
      this.$fileList.closest('.t3-filelist-container')?.removeClass('hidden');
      // Hide the information container
      this.$fileList.closest('.filelist-main')?.find('.t3-filelist-info-container')?.hide();
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
    this.$dropzone.addClass('drop-status-ok');
  };

  public fileOutOfDropzone = (): void => {
    this.$dropzone.removeClass('drop-status-ok');
    // In case dropzone was not manually triggered and this is no manual (fake) table, hide it when leaving
    if (!this.manuallyTriggered) {
      this.$dropzone.hide();
    }
  };

  /**
   * Bind file picker to default upload button
   *
   * @param {Object} button
   */
  public bindUploadButton(button: JQuery): void {
    button.on('click', (event: Event) => {
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
    const $modalContent = $('<div/>').append(
      $('<p/>').text(TYPO3.lang['file_upload.existingfiles.description']),
      $('<table/>', { class: 'table' }).append(
        $('<thead/>').append(
          $('<tr />').append(
            $('<th/>'),
            $('<th/>').text(TYPO3.lang['file_upload.header.originalFile']),
            $('<th/>').text(TYPO3.lang['file_upload.header.uploadedFile']),
            $('<th/>').text(TYPO3.lang['file_upload.header.action']),
          ),
        ),
      ),
    );
    for (let i = 0; i < amountOfItems; ++i) {
      const $record = $('<tr />').append(
        $('<td />').append(
          (this.askForOverride[i].original.thumbUrl !== ''
            ? $('<img />', { src: this.askForOverride[i].original.thumbUrl, height: 40 })
            : $(this.askForOverride[i].original.icon)
          ),
        ),
        $('<td />').html(
          this.askForOverride[i].original.name + ' (' + (DragUploader.fileSizeAsString(this.askForOverride[i].original.size)) + ')' +
          '<br>' + DateTime.fromSeconds(this.askForOverride[i].original.mtime).toLocaleString(DateTime.DATETIME_MED)
        ),
        $('<td />').html(
          this.askForOverride[i].uploaded.name + ' (' + (DragUploader.fileSizeAsString(this.askForOverride[i].uploaded.size)) + ')' +
          '<br>' +
          DateTime.fromMillis(this.askForOverride[i].uploaded.lastModified).toLocaleString(DateTime.DATETIME_MED)
        ),
        $('<td />').append(
          $('<select />', { class: 'form-select t3js-actions', 'data-override': i }).append(
            (this.irreObjectUid ? $('<option/>').val(Action.USE_EXISTING).text(TYPO3.lang['file_upload.actions.use_existing']) : ''),
            $('<option />', { 'selected': this.defaultAction === Action.SKIP })
              .val(Action.SKIP).text(TYPO3.lang['file_upload.actions.skip']),
            $('<option />', { 'selected': this.defaultAction === Action.RENAME })
              .val(Action.RENAME).text(TYPO3.lang['file_upload.actions.rename']),
            $('<option />', { 'selected': this.defaultAction === Action.OVERRIDE })
              .val(Action.OVERRIDE).text(TYPO3.lang['file_upload.actions.override']),
          ),
        ),
      );
      $modalContent.find('table').append('<tbody />').append($record);
    }

    const modal = Modal.advanced({
      title: TYPO3.lang['file_upload.existingfiles.title'],
      content: $modalContent,
      severity: SeverityEnum.warning,
      buttons: [
        {
          text: $(this).data('button-close-text') || TYPO3.lang['file_upload.button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel',
        },
        {
          text: $(this).data('button-ok-text') || TYPO3.lang['file_upload.button.continue'] || 'Continue with selected actions',
          btnClass: 'btn-warning',
          name: 'continue',
        },
      ],
      additionalCssClasses: ['modal-inner-scroll'],
      size: ModalSizes.large,
      callback: (modal: ModalElement): void => {
        $(modal).find('.modal-footer').prepend(
          $('<label/>').text(TYPO3.lang['file_upload.actions.all.label']),
          $('<span/>').append(
            $('<select/>', { class: 'form-select t3js-actions-all' }).append(
              $('<option/>').val('').text(TYPO3.lang['file_upload.actions.all.empty']),
              (this.irreObjectUid ? $('<option/>').val(Action.USE_EXISTING).text(TYPO3.lang['file_upload.actions.all.use_existing']) : ''),
              $('<option/>', { 'selected': this.defaultAction === Action.SKIP })
                .val(Action.SKIP).text(TYPO3.lang['file_upload.actions.all.skip']),
              $('<option/>', { 'selected': this.defaultAction === Action.RENAME })
                .val(Action.RENAME).text(TYPO3.lang['file_upload.actions.all.rename']),
              $('<option/>', { 'selected': this.defaultAction === Action.OVERRIDE })
                .val(Action.OVERRIDE).text(TYPO3.lang['file_upload.actions.all.override']),
            ),
          ),
        );
      }
    });

    const $modal = $(modal);
    $modal.on('change', '.t3js-actions-all', (e: JQueryEventObject): void => {
      const $this = $(e.currentTarget),
        value = $this.val();

      if (value !== '') {
        // mass action was selected, apply action to every file
        for (const select of modal.querySelectorAll('.t3js-actions') as NodeListOf<HTMLSelectElement>) {
          const index = parseInt(select.dataset.override, 10);
          select.value = value;
          select.disabled = true;
          this.askForOverride[index].action = <Action>select.value;
        }
      } else {
        modal.querySelectorAll('.t3js-actions').forEach((select: HTMLSelectElement) => select.disabled = false);
      }
    });

    $modal.on('change', '.t3js-actions', (e: JQueryEventObject): void => {
      const $this = $(e.currentTarget),
        index = parseInt($this.data('override'), 10);
      this.askForOverride[index].action = <Action>$this.val();
    });

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
  private readonly $row: JQuery;
  private readonly $progress: JQuery;
  private readonly $progressContainer: JQuery;
  private readonly file: File;
  private readonly override: Action;
  private readonly $selector: JQuery;
  private readonly $iconCol: JQuery;
  private readonly $fileName: JQuery;
  private readonly $progressBar: JQuery;
  private readonly $progressPercentage: JQuery;
  private readonly $progressMessage: JQuery;
  private readonly dragUploader: DragUploaderPlugin;

  constructor(dragUploader: DragUploaderPlugin, file: File, override: Action) {
    this.dragUploader = dragUploader;
    this.file = file;
    this.override = override;

    this.$row = $('<tr />').addClass('upload-queue-item uploading');
    if (!this.dragUploader.manualTable) {
      // Add selector cell, if this is a real table (e.g. not in FormEngine)
      this.$selector = $('<td />').addClass('col-checkbox').appendTo(this.$row);
    }
    this.$iconCol = $('<td />', { 'class': 'col-icon' }).appendTo(this.$row);
    this.$fileName = $('<td />', { 'class': 'col-title col-responsive' }).text(file.name).appendTo(this.$row);
    this.$progress = $('<td />').attr('colspan', this.dragUploader.fileListColumnCount - this.$row.find('td').length).appendTo(this.$row);
    this.$progressContainer = $('<div />').addClass('upload-queue-progress').appendTo(this.$progress);
    this.$progressBar = $('<div />').addClass('upload-queue-progress-bar').appendTo(this.$progressContainer);
    this.$progressPercentage = $('<span />').addClass('upload-queue-progress-percentage').appendTo(this.$progressContainer);
    this.$progressMessage = $('<span />').addClass('upload-queue-progress-message').appendTo(this.$progressContainer);


    // position queue item in filelist
    if ($('tbody tr.upload-queue-item', this.dragUploader.$fileList).length === 0) {
      this.$row.prependTo($('tbody', this.dragUploader.$fileList));
      this.$row.addClass('last');
    } else {
      this.$row.insertBefore($('tbody tr.upload-queue-item:first', this.dragUploader.$fileList));
    }

    // Set a disabled checkbox to the selector column, if available
    if (this.$selector) {
      this.$selector.html(
        '<span class="form-check form-check-type-toggle">' +
        '<input type="checkbox" class="form-check-input t3js-multi-record-selection-check" disabled/>' +
        '</span>'
      );
    }

    // set dummy file icon
    this.$iconCol.html('<typo3-backend-icon identifier="mimetypes-other-other" />');

    // check file size
    if (this.dragUploader.maxFileSize > 0 && this.file.size > this.dragUploader.maxFileSize) {
      this.updateMessage(TYPO3.lang['file_upload.maxFileSizeExceeded']
        .replace(/\{0\}/g, this.file.name)
        .replace(/\{1\}/g, DragUploader.fileSizeAsString(this.dragUploader.maxFileSize)));
      this.$row.addClass('error');

      // check filename/extension against deny pattern
    } else if (this.dragUploader.fileDenyPattern && this.file.name.match(this.dragUploader.fileDenyPattern)) {
      this.updateMessage(TYPO3.lang['file_upload.fileNotAllowed'].replace(/\{0\}/g, this.file.name));
      this.$row.addClass('error');

    } else if (!this.checkAllowedExtensions()) {
      this.updateMessage(TYPO3.lang['file_upload.fileExtensionExpected']
        .replace(/\{0\}/g, this.dragUploader.filesExtensionsAllowed),
      );
      this.$row.addClass('error');
    } else if (!this.checkDisallowedExtensions()) {
      this.updateMessage(TYPO3.lang['file_upload.fileExtensionDisallowed']
        .replace(/\{0\}/g, this.dragUploader.filesExtensionsDisallowed),
      );
      this.$row.addClass('error');
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
    this.$progressMessage.text(message);
  }

  /**
   * Remove the progress bar
   */
  public removeProgress(): void {
    if (this.$progress) {
      this.$progress.remove();
    }
  }

  public uploadStart(): void {
    this.$progressPercentage.text('(0%)');
    this.$progressBar.width('1%');
    this.dragUploader.$trigger.trigger('uploadStart', [this]);
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
      this.$progressPercentage.text('');
      if (messages && messages.length) {
        for (const flashMessage of messages) {
          Notification.showMessage(flashMessage.title, flashMessage.message, flashMessage.severity, 10);
        }
      }
    } catch {
      // do nothing in case JSON could not be parsed
    }

    this.$row.addClass('error');
    this.dragUploader.decrementQueueLength();
    this.dragUploader.$trigger.trigger('uploadError', [this, response]);
  }

  /**
   * @param {ProgressEvent} event
   */
  public updateProgress(event: ProgressEvent): void {
    const percentage = Math.round((event.loaded / event.total) * 100) + '%';
    this.$progressBar.outerWidth(percentage);
    this.$progressPercentage.text(percentage);
    this.dragUploader.$trigger.trigger('updateProgress', [this, percentage, event]);
  }

  /**
   * @param {{upload?: UploadedFile[]}} data
   */
  public uploadSuccess(data: { upload?: UploadedFile[], messages?: FlashMessage[] }): void {
    if (data.upload) {
      this.dragUploader.decrementQueueLength(data.messages);
      this.$row.removeClass('uploading');
      this.$row.prop('data-type', 'file');
      this.$row.prop('data-file-uid', data.upload[0].uid);
      this.$fileName.text(data.upload[0].name);
      this.$progressPercentage.text('');
      this.$progressMessage.text('100%');
      this.$progressBar.outerWidth('100%');

      const combinedIdentifier: string = String(data.upload[0].id);

      // Enable checkbox, if available
      if (this.$selector) {
        const checkbox: HTMLInputElement = <HTMLInputElement>this.$selector.find('input')?.get(0);
        if (checkbox) {
          checkbox.removeAttribute('disabled');
          checkbox.setAttribute('name', 'CBC[_FILE|' + Md5.hash(combinedIdentifier) + ']');
          checkbox.setAttribute('value', combinedIdentifier);
        }
      }

      // replace file icon
      if (data.upload[0].icon) {
        this.$iconCol
          .html(
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
            this.$row.remove();
            if ($('tr', this.dragUploader.$fileList).length === 0) {
              this.dragUploader.$fileList.hide();
              this.dragUploader.$fileList.closest('.t3-filelist-container')?.addClass('hidden');
              this.dragUploader.$trigger.trigger('uploadSuccess', [this, data]);
            }
          },
          3000);
      } else {
        setTimeout(
          () => {
            this.showFileInfo(data.upload[0]);
            this.dragUploader.$trigger.trigger('uploadSuccess', [this, data]);
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
      $('<td />').text(fileInfo.path).appendTo(this.$row);
    }
    // Controls column is deliberately empty
    $('<td />', { 'class': 'col-control' }).text('').appendTo(this.$row);
    $('<td />').text(TYPO3.lang['type.file'] + ' (' + fileInfo.extension.toUpperCase() + ')').appendTo(this.$row);
    $('<td />').text(DragUploader.fileSizeAsString(fileInfo.size)).appendTo(this.$row);
    let permissions = '';
    if (fileInfo.permissions.read) {
      permissions += '<strong class="text-danger">' + TYPO3.lang['permissions.read'] + '</strong>';
    }
    if (fileInfo.permissions.write) {
      permissions += '<strong class="text-danger">' + TYPO3.lang['permissions.write'] + '</strong>';
    }
    $('<td />').html(permissions).appendTo(this.$row);
    $('<td />').text('-').appendTo(this.$row);

    // add spacing cells when more columns are displayed (column selector)
    for (let i = this.$row.find('td').length; i < this.dragUploader.fileListColumnCount; i++) {
      $('<td />').text('').appendTo(this.$row);
    }
  }

  public checkAllowedExtensions(): boolean {
    if (!this.dragUploader.filesExtensionsAllowed) {
      return true;
    }
    const extension = this.file.name.split('.').pop();
    const allowed = this.dragUploader.filesExtensionsAllowed.split(',');

    return $.inArray(extension.toLowerCase(), allowed) !== -1;
  }

  public checkDisallowedExtensions(): boolean {
    if (!this.dragUploader.filesExtensionsDisallowed) {
      return true;
    }
    const extension = this.file.name.split('.').pop();
    const disallowed = this.dragUploader.filesExtensionsDisallowed.split(',');

    return $.inArray(extension.toLowerCase(), disallowed) === -1;
  }
}

class DragUploader {
  private static readonly options: DragUploaderOptions;
  public fileListColumnCount: number;
  public filesExtensionsAllowed: string;
  public fileDenyPattern: string;

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
   * @param {number} irre_object
   * @param {UploadedFile} file
   */
  public static addFileToIrre(irre_object: number, file: UploadedFile): void {
    const message = {
      actionName: 'typo3:foreignRelation:insert',
      objectGroup: irre_object,
      table: 'sys_file',
      uid: file.uid,
    };
    MessageUtility.send(message);
  }

  public static init(): void {
    const options = this.options;

    // register the jQuery plugin "DragUploaderPlugin"
    $.fn.extend({
      dragUploader: function (options?: DragUploaderOptions | string): JQuery {
        return this.each((index: number, element: HTMLElement): void => {
          const $element = $(element);
          let data = $element.data('DragUploaderPlugin');
          if (!data) {
            $element.data('DragUploaderPlugin', (data = new DragUploaderPlugin(element)));
          }
          if (typeof options === 'string') {
            data[options]();
          }
        });
      },
    });

    DocumentService.ready().then((): void => {
      $('.t3js-drag-uploader').dragUploader(options);
    });

    // @todo Refactor the FormEngine integration of the uploader to instance new uploaders via event handlers
    const observer = new MutationObserver((): void => {
      $('.t3js-drag-uploader').dragUploader(options);
    });
    observer.observe(document, { childList: true, subtree: true });
  }
}

export const initialize = function (): void {
  DragUploader.init();

  // load required modules to hook in the post initialize function
  if (
    'undefined' !== typeof TYPO3.settings
    && 'undefined' !== typeof TYPO3.settings.RequireJS
    && 'undefined' !== typeof TYPO3.settings.RequireJS.PostInitializationModules
    && 'undefined' !== typeof TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/DragUploader']
  ) {
    for (const moduleName of TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/DragUploader']) {
      window.require([moduleName]);
    }
  }
};

initialize();
