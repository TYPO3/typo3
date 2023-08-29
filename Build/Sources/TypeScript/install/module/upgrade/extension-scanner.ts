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

import 'bootstrap';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import { AbstractInteractableModule } from '../abstract-interactable-module';
import Modal from '@typo3/backend/modal';
import Notification from '@typo3/backend/notification';
import AjaxQueue from '../../ajax/ajax-queue';
import Router from '../../router';
import RegularEvent from '@typo3/core/event/regular-event';
import type { ModalElement } from '@typo3/backend/modal';

enum Identifiers {
  extensionContainer = '.t3js-extensionScanner-extension',
  numberOfFiles = '.t3js-extensionScanner-number-of-files',
  scanSingleTrigger = '.t3js-extensionScanner-scan-single',
  extensionScanButton = '.t3js-extensionScanner-scan-all'
}

interface FileData {
  success: boolean;
  matches: Array<Match>;
  isFileIgnored: boolean;
  effectiveCodeLines: number;
  ignoredLines: number;
}

interface Match {
  uniqueId: string;
  message: string;
  indicator: string;
  silenced: boolean;
  lineContent: string;
  line: number;
  restFiles: Array<RestFile>;
}

interface RestFile {
  uniqueId: string;
  version: string;
  headline: string;
  content: string;
  class: string;
  file_hash: string;
}

class ExtensionScanner extends AbstractInteractableModule {
  private readonly listOfAffectedRestFileHashes: string[] = [];

  public initialize(currentModal: ModalElement): void {
    super.initialize(currentModal);
    this.getData();

    new RegularEvent('typo3-modal-hide', (): void => {
      AjaxQueue.flush();
    }).bindTo(currentModal);

    new RegularEvent('click', (event: Event, target: HTMLElement): void => {
      // Scan a single extension by clicking "Rescan"
      event.preventDefault();
      const extension = target.closest<HTMLElement>(Identifiers.extensionContainer).dataset.extension;
      this.scanSingleExtension(extension);
    }).delegateTo(currentModal, Identifiers.scanSingleTrigger);

    new RegularEvent('click', (event: Event): void => {
      // Scan all button
      event.preventDefault();
      this.setModalButtonsState(false);
      const extensions = currentModal.querySelectorAll<HTMLElement>(Identifiers.extensionContainer);
      this.scanAll(extensions);
    }).delegateTo(currentModal, Identifiers.extensionScanButton);
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    (new AjaxRequest(Router.getUrl('extensionScannerGetData'))).get().then(
      async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true) {
          modalContent.innerHTML = data.html;
          Modal.setButtons(data.buttons);
          this.setupEventListeners();
        } else {
          Notification.error('Something went wrong', 'The request was not processed successfully. Please check the browser\'s console and TYPO3\'s log.');
        }
      },
      (error: AjaxResponse): void => {
        Router.handleAjaxError(error, modalContent);
      }
    );
  }

  private setupEventListeners(): void {
    this.currentModal.querySelectorAll(Identifiers.extensionContainer).forEach((extensionContainer: HTMLElement) => {
      new RegularEvent('show.bs.collapse', (event: Event): void => {
        // Scan a single extension by opening the panel
        const target = event.currentTarget as HTMLElement;
        console.log(target);
        if (typeof target.dataset.scanned === 'undefined') {
          const extension = target.dataset.extension;
          this.scanSingleExtension(extension);
          target.dataset.scanned = String(true);
        }
      }).bindTo(extensionContainer);
    });
  }

  private getExtensionSelector(extension: string): string {
    return Identifiers.extensionContainer + '-' + extension;
  }

  private scanAll(extensions: NodeListOf<HTMLElement>): void {
    extensions.forEach(extensionContainer => {
      extensionContainer.classList.remove('panel-danger', 'panel-warning', 'panel-success');

      const panelProgress = extensionContainer.querySelector<HTMLElement>('.panel-progress-bar');
      panelProgress.style.width = String(0);
      panelProgress.setAttribute('aria-valuenow', String(0));
      panelProgress.querySelector('span').innerText = '0%';
    });

    this.setProgressForAll();

    extensions.forEach((element: HTMLElement) => {
      const extension = element.dataset.extension;
      this.scanSingleExtension(extension);
      element.dataset.scanned = String(true);
    });
  }

  private setStatusMessageForScan(extension: string, doneFiles: number, numberOfFiles: number): void {
    const extensionContainer = this.findInModal(this.getExtensionSelector(extension));
    const numberOfFilesElement = extensionContainer.querySelector<HTMLElement>(Identifiers.numberOfFiles);
    numberOfFilesElement.innerText = 'Checked ' + doneFiles + ' of ' + numberOfFiles + ' files';
  }

  private setProgressForScan(extension: string, doneFiles: number, numberOfFiles: number): void {
    const percent = (doneFiles / numberOfFiles) * 100;
    const extensionContainer = this.findInModal(this.getExtensionSelector(extension));
    const panelProgress = extensionContainer.querySelector<HTMLElement>('.panel-progress-bar');
    panelProgress.style.width = percent + '%';
    panelProgress.setAttribute('aria-valuenow', String(percent));
    panelProgress.querySelector('span').innerText = percent + '%';
  }

  private setProgressForAll(): void {
    const numberOfExtensions: number = this.currentModal.querySelectorAll(Identifiers.extensionContainer).length;
    const numberOfSuccess: number = this.currentModal.querySelectorAll(Identifiers.extensionContainer
      + '.t3js-extensionscan-finished.panel-success').length;
    const numberOfWarning: number = this.currentModal.querySelectorAll(Identifiers.extensionContainer
      + '.t3js-extensionscan-finished.panel-warning').length;
    const numberOfError: number = this.currentModal.querySelectorAll(Identifiers.extensionContainer
      + '.t3js-extensionscan-finished.panel-danger').length;
    const numberOfScannedExtensions: number = numberOfSuccess + numberOfWarning + numberOfError;
    const percent: number = (numberOfScannedExtensions / numberOfExtensions) * 100;
    const modalContent = this.getModalBody();
    const extensionProgress = this.findInModal('.t3js-extensionScanner-progress-all-extension .progress-bar');
    extensionProgress.style.width = percent + '%';
    extensionProgress.setAttribute('aria-valuenow', String(percent));
    extensionProgress.querySelector('span').innerText = numberOfScannedExtensions + ' of ' + numberOfExtensions + ' scanned';

    if (numberOfScannedExtensions === numberOfExtensions) {
      const scanButton = this.findInModal(Identifiers.extensionScanButton) as HTMLButtonElement;
      scanButton.classList.remove('disabled');
      scanButton.disabled = false;
      Notification.success('Scan finished', 'All extensions have been scanned.');

      (new AjaxRequest(Router.getUrl())).post({
        install: {
          action: 'extensionScannerMarkFullyScannedRestFiles',
          token: this.getModuleContent().dataset.extensionScannerMarkFullyScannedRestFilesToken,
          hashes: Array.from(new Set(this.listOfAffectedRestFileHashes)),
        },
      }).then(
        async (response: AjaxResponse): Promise<void> => {
          const data = await response.resolve();
          if (data.success === true) {
            Notification.success('Marked not affected files', 'Marked ' + data.markedAsNotAffected + ' ReST files as not affected.');
          }
        },
        (error: AjaxResponse): void => {
          Router.handleAjaxError(error, modalContent);
        }
      );
    }
  }

  /**
   * Handle a single extension scan
   */
  private scanSingleExtension(extension: string): void {
    const executeToken = this.getModuleContent().dataset.extensionScannerFilesToken;
    const modalContent = this.getModalBody();
    const extensionContainer = this.findInModal(this.getExtensionSelector(extension));
    const hitTemplate = '#t3js-extensionScanner-file-hit-template';
    const restTemplate = '#t3js-extensionScanner-file-hit-rest-template';
    let hitFound = false;
    extensionContainer.classList.add('panel-default');
    extensionContainer.classList.remove('panel-danger', 'panel-warning', 'panel-success', 't3js-extensionscan-finished');
    extensionContainer.dataset.hasRun = String('true');

    const scanSingle = extensionContainer.querySelector<HTMLButtonElement>('.t3js-extensionScanner-scan-single');
    scanSingle.innerText = 'Scanning...';
    scanSingle.disabled = true;

    extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-loc').innerText = '0';
    extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-ignored-files').innerText = '0';
    extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-ignored-lines').innerText = '0';

    this.setProgressForAll();

    (new AjaxRequest(Router.getUrl())).post({
      install: {
        action: 'extensionScannerFiles',
        token: executeToken,
        extension: extension,
      },
    }).then(
      async (response: AjaxResponse): Promise<void> => {
        const data = await response.resolve();
        if (data.success === true && Array.isArray(data.files)) {
          const numberOfFiles = data.files.length;
          if (numberOfFiles <= 0) {
            Notification.warning('No files found', 'The extension ' + extension + ' contains no scannable files');
            return;
          }

          this.setStatusMessageForScan(extension, 0, numberOfFiles);
          extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body').innerText = '';
          extensionContainer.classList.add('panel-has-progress');
          let doneFiles = 0;
          data.files.forEach((file: string): void => {
            AjaxQueue.add({
              method: 'POST',
              data: {
                install: {
                  action: 'extensionScannerScanFile',
                  token: this.getModuleContent().dataset.extensionScannerScanFileToken,
                  extension: extension,
                  file: file,
                },
              },
              url: Router.getUrl(),
              onfulfilled: async (response: AjaxResponse): Promise<void> => {
                const fileData: FileData = await response.resolve();
                doneFiles++;
                this.setStatusMessageForScan(extension, doneFiles, numberOfFiles);
                this.setProgressForScan(extension, doneFiles, numberOfFiles);
                if (fileData.success && Array.isArray(fileData.matches)) {
                  fileData.matches.forEach((match: Match): void => {
                    hitFound = true;
                    const aMatch = modalContent.querySelector(hitTemplate + ' .panel').cloneNode(true) as HTMLElement;
                    aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-file-panel-head').setAttribute('href', '#collapse' + match.uniqueId);
                    aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-file-panel-body').setAttribute('id', 'collapse' + match.uniqueId);
                    aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-filename').innerText = file;
                    aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-message').innerText = match.message;
                    if (match.indicator === 'strong') {
                      aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-file-panel-head .badges')
                        .innerHTML += '<span class="badge badge-danger" title="Reliable match, false positive unlikely">strong</span>';
                    } else {
                      aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-file-panel-head .badges')
                        .innerHTML += '<span class="badge badge-warning" title="Probable match, but can be a false positive">weak</span>';
                    }
                    if (match.silenced === true) {
                      aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-file-panel-head .badges')
                        .innerHTML += '<span class="badge badge-info" title="Match has been annotated by extension author' +
                          ' as false positive match">silenced</span>';
                    }
                    aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-file-lineContent').innerText = match.lineContent;
                    aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-file-line').innerText = match.line + ': ';
                    if (Array.isArray(match.restFiles)) {
                      match.restFiles.forEach((restFile: RestFile): void => {
                        const aRest = modalContent.querySelector(restTemplate + ' .panel').cloneNode(true) as HTMLElement;
                        aRest.querySelector<HTMLElement>('.t3js-extensionScanner-hit-rest-panel-head').setAttribute('href', '#collapse' + restFile.uniqueId);
                        aRest.querySelector<HTMLElement>('.t3js-extensionScanner-hit-rest-panel-head .badge').innerText = restFile.version;
                        aRest.querySelector<HTMLElement>('.t3js-extensionScanner-hit-rest-panel-body').setAttribute('id', 'collapse' + restFile.uniqueId);
                        aRest.querySelector<HTMLElement>('.t3js-extensionScanner-hit-rest-headline').innerText = restFile.headline;
                        aRest.querySelector<HTMLElement>('.t3js-extensionScanner-hit-rest-body').innerText = restFile.content;
                        aRest.classList.add('panel-' + restFile.class);
                        aMatch.querySelector<HTMLElement>('.t3js-extensionScanner-hit-file-rest-container').append(aRest);
                        this.listOfAffectedRestFileHashes.push(restFile.file_hash);
                      });
                    }
                    const panelClass =
                      aMatch.querySelectorAll('.panel-breaking, .t3js-extensionScanner-hit-file-rest-container').length > 0
                        ? 'panel-danger'
                        : 'panel-warning';
                    aMatch.classList.add(panelClass);
                    aMatch.classList.remove('panel-default');
                    const extensionBody = extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body');
                    extensionBody.classList.remove('hide');
                    extensionBody.append(aMatch);
                    extensionContainer.classList.remove('panel-default');
                    if (panelClass === 'panel-danger') {
                      extensionContainer.classList.remove('panel-warning');
                      extensionContainer.classList.add(panelClass);
                    }
                    if (panelClass === 'panel-warning' && !extensionContainer.classList.contains('panel-danger')) {
                      extensionContainer.classList.add(panelClass);
                    }
                  });
                }
                if (fileData.success) {
                  const currentLinesOfCode = parseInt(extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-loc').innerText, 10);
                  extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-loc')
                    .innerText = String(currentLinesOfCode + fileData.effectiveCodeLines);
                  if (fileData.isFileIgnored) {
                    const currentIgnoredFiles = parseInt(
                      extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-ignored-files').innerText,
                      10,
                    );
                    extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-ignored-files').innerText = String(currentIgnoredFiles + 1);
                  }
                  const currentIgnoredLines = parseInt(
                    extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-ignored-lines').innerText,
                    10,
                  );
                  extensionContainer.querySelector<HTMLElement>('.t3js-extensionScanner-extension-body-ignored-lines')
                    .innerText = String(currentIgnoredLines + fileData.ignoredLines);
                }
                if (doneFiles === numberOfFiles) {
                  if (!hitFound) {
                    extensionContainer.classList.remove('panel-default');
                    extensionContainer.classList.add('panel-success');
                  }
                  extensionContainer.classList.add('t3js-extensionscan-finished');
                  extensionContainer.classList.remove('panel-has-progress');
                  this.setProgressForAll();
                  const scanSingle = extensionContainer.querySelector<HTMLButtonElement>('.t3js-extensionScanner-scan-single');
                  scanSingle.innerText = 'Rescan';
                  scanSingle.disabled = false;
                }
              },
              onrejected: (reason: string): void => {
                doneFiles = doneFiles + 1;
                this.setStatusMessageForScan(extension, doneFiles, numberOfFiles);
                this.setProgressForScan(extension, doneFiles, numberOfFiles);
                extensionContainer.classList.remove('panel-has-progress');
                this.setProgressForAll();
                console.error(reason);
              },
            });
          });
        } else {
          Notification.error('Oops, an error occurred', 'Please look at the browser console output for details');
          console.error(data);
        }
      },
      (error: AjaxResponse): void => {
        Router.handleAjaxError(error, modalContent);
      }
    );
  }
}

export default new ExtensionScanner();
