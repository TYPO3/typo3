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

import {AbstractInteractableModule} from '../AbstractInteractableModule';
import * as $ from 'jquery';
import 'bootstrap';
import AjaxQueue = require('../../Ajax/AjaxQueue');
import Router = require('../../Router');
import Modal = require('TYPO3/CMS/Backend/Modal');
import Notification = require('TYPO3/CMS/Backend/Notification');

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
  private listOfAffectedRestFileHashes: Array<any> = [];
  private selectorExtensionContainer: string = '.t3js-extensionScanner-extension';
  private selectorNumberOfFiles: string = '.t3js-extensionScanner-number-of-files';
  private selectorScanSingleTrigger: string = '.t3js-extensionScanner-scan-single';
  private selectorExtensionScanButton: string = '.t3js-extensionScanner-scan-all';

  public initialize(currentModal: JQuery): void {
    this.currentModal = currentModal;
    this.getData();

    currentModal.on('show.bs.collapse', this.selectorExtensionContainer, (e: JQueryEventObject): void => {
      // Scan a single extension by opening the panel
      const $me = $(e.currentTarget);
      if (typeof $me.data('scanned') === 'undefined') {
        const extension = $me.data('extension');
        this.scanSingleExtension(extension);
        $me.data('scanned', true);
      }
    }).on('click', this.selectorScanSingleTrigger, (e: JQueryEventObject): void => {
      // Scan a single extension by clicking "Rescan"
      e.preventDefault();
      const extension = $(e.currentTarget).closest(this.selectorExtensionContainer).data('extension');
      this.scanSingleExtension(extension);
    }).on('click', this.selectorExtensionScanButton, (e: JQueryEventObject): void => {
      // Scan all button
      e.preventDefault();
      $(e.currentTarget).addClass('disabled').prop('disabled', true);
      const $extensions = currentModal.find(this.selectorExtensionContainer);
      this.scanAll($extensions);
    });
  }

  private getData(): void {
    const modalContent = this.getModalBody();
    AjaxQueue.add({
      url: Router.getUrl('extensionScannerGetData'),
      cache: false,
      success: (data: any): void => {
        if (data.success === true) {
          modalContent.empty().append(data.html);
          Modal.setButtons(data.buttons);
        } else {
          Notification.error('Something went wrong');
        }
      },
      error: (xhr: XMLHttpRequest): void => {
        Router.handleAjaxError(xhr, modalContent);
      },
    });
  }

  private getExtensionSelector(extension: string): string {
    return this.selectorExtensionContainer + '-' + extension;
  }

  private scanAll($extensions: JQuery): void {
    this.findInModal(this.selectorExtensionContainer)
      .removeClass('panel-danger panel-warning panel-success')
      .find('.panel-progress-bar')
      .css('width', 0)
      .attr('aria-valuenow', 0)
      .find('span')
      .text('0%');
    this.setProgressForAll();
    $extensions.each((index: number, element: any): void => {
      const $me: JQuery = $(element);
      const extension = $me.data('extension');
      this.scanSingleExtension(extension);
      $me.data('scanned', true);
    });
  }

  private setStatusMessageForScan(extension: string, doneFiles: number, numberOfFiles: number): void {
    this.findInModal(this.getExtensionSelector(extension))
      .find(this.selectorNumberOfFiles)
      .text('Checked ' + doneFiles + ' of ' + numberOfFiles + ' files');
  }

  private setProgressForScan(extension: string, doneFiles: number, numberOfFiles: number): void {
    const percent = (doneFiles / numberOfFiles) * 100;
    this.findInModal(this.getExtensionSelector(extension))
      .find('.panel-progress-bar')
      .css('width', percent + '%')
      .attr('aria-valuenow', percent)
      .find('span')
      .text(percent + '%');
  }

  private setProgressForAll(): void {
    const numberOfExtensions: number = this.findInModal(this.selectorExtensionContainer).length;
    const numberOfSuccess: number = this.findInModal(this.selectorExtensionContainer
      + '.t3js-extensionscan-finished.panel-success').length;
    const numberOfWarning: number = this.findInModal(this.selectorExtensionContainer
      + '.t3js-extensionscan-finished.panel-warning').length;
    const numberOfError: number = this.findInModal(this.selectorExtensionContainer
      + '.t3js-extensionscan-finished.panel-danger').length;
    const numberOfScannedExtensions: number = numberOfSuccess + numberOfWarning + numberOfError;
    const percent: number = (numberOfScannedExtensions / numberOfExtensions) * 100;
    const modalContent: JQuery = this.getModalBody();
    this.findInModal('.t3js-extensionScanner-progress-all-extension .progress-bar')
      .css('width', percent + '%')
      .attr('aria-valuenow', percent)
      .find('span')
      .text(numberOfScannedExtensions + ' of ' + numberOfExtensions + ' scanned');

    if (numberOfScannedExtensions === numberOfExtensions) {
      this.findInModal(this.selectorExtensionScanButton).removeClass('disabled').prop('disabled', false);
      Notification.success('Scan finished', 'All extensions have been scanned');
      AjaxQueue.add({
        url: Router.getUrl(),
        method: 'POST',
        data: {
          'install': {
            'action': 'extensionScannerMarkFullyScannedRestFiles',
            'token': this.getModuleContent().data('extension-scanner-mark-fully-scanned-rest-files-token'),
            'hashes': this.uniqueArray(this.listOfAffectedRestFileHashes),
          },
        },
        cache: false,
        success: (data: any): void => {
          if (data.success === true) {
            Notification.success('Marked not affected files', 'Marked ' + data.markedAsNotAffected + ' ReST files as not affected.');
          }
        },
        error: (xhr: XMLHttpRequest): void => {
          Router.handleAjaxError(xhr, modalContent);
        },
      });
    }
  }

  /**
   * Helper method removing duplicate entries from an array
   */
  private uniqueArray(anArray: Array<any>): Array<any> {
    return anArray.filter((value, index, self): boolean => {
      return self.indexOf(value) === index;
    });
  }

  /**
   * Handle a single extension scan
   */
  private scanSingleExtension(extension: string): void {
    const executeToken = this.getModuleContent().data('extension-scanner-files-token');
    const modalContent = this.getModalBody();
    const $extensionContainer = this.findInModal(this.getExtensionSelector(extension));
    const hitTemplate = '#t3js-extensionScanner-file-hit-template';
    const restTemplate = '#t3js-extensionScanner-file-hit-rest-template';
    let hitFound = false;
    $extensionContainer.removeClass('panel-danger panel-warning panel-success t3js-extensionscan-finished');
    $extensionContainer.data('hasRun', 'true');
    $extensionContainer.find('.t3js-extensionScanner-scan-single').text('Scanning...').attr('disabled', 'disabled');
    $extensionContainer.find('.t3js-extensionScanner-extension-body-loc').empty().text('0');
    $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-files').empty().text('0');
    $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-lines').empty().text('0');
    this.setProgressForAll();
    AjaxQueue.add({
      url: Router.getUrl(),
      method: 'POST',
      data: {
        'install': {
          'action': 'extensionScannerFiles',
          'token': executeToken,
          'extension': extension,
        },
      },
      cache: false,
      success: (data: any): void => {
        if (data.success === true && Array.isArray(data.files)) {
          const numberOfFiles = data.files.length;
          if (numberOfFiles > 0) {
            this.setStatusMessageForScan(extension, 0, numberOfFiles);
            $extensionContainer.find('.t3js-extensionScanner-extension-body').text('');
            let doneFiles = 0;
            data.files.forEach((file: string): void => {
              AjaxQueue.add({
                method: 'POST',
                data: {
                  'install': {
                    'action': 'extensionScannerScanFile',
                    'token': this.getModuleContent().data('extension-scanner-scan-file-token'),
                    'extension': extension,
                    'file': file,
                  },
                },
                url: Router.getUrl(),
                cache: false,
                success: (fileData: FileData): void => {
                  doneFiles++;
                  this.setStatusMessageForScan(extension, doneFiles, numberOfFiles);
                  this.setProgressForScan(extension, doneFiles, numberOfFiles);
                  if (fileData.success && $.isArray(fileData.matches)) {
                    fileData.matches.forEach((match: Match): void => {
                      hitFound = true;
                      const aMatch: any = modalContent.find(hitTemplate).clone();
                      aMatch.find('.t3js-extensionScanner-hit-file-panel-head').attr('href', '#collapse' + match.uniqueId);
                      aMatch.find('.t3js-extensionScanner-hit-file-panel-body').attr('id', 'collapse' + match.uniqueId);
                      aMatch.find('.t3js-extensionScanner-hit-filename').text(file);
                      aMatch.find('.t3js-extensionScanner-hit-message').text(match.message);
                      if (match.indicator === 'strong') {
                        aMatch.find('.t3js-extensionScanner-hit-file-panel-head .badges')
                          .append('<span class="badge" title="Reliable match, false positive unlikely">strong</span>');
                      } else {
                        aMatch.find('.t3js-extensionScanner-hit-file-panel-head .badges')
                          .append('<span class="badge" title="Probable match, but can be a false positive">weak</span>');
                      }
                      if (match.silenced === true) {
                        aMatch.find('.t3js-extensionScanner-hit-file-panel-head .badges')
                          .append('<span class="badge" title="Match has been annotated by extension author' +
                            ' as false positive match">silenced</span>');
                      }
                      aMatch.find('.t3js-extensionScanner-hit-file-lineContent').empty().text(match.lineContent);
                      aMatch.find('.t3js-extensionScanner-hit-file-line').empty().text(match.line + ': ');
                      if ($.isArray(match.restFiles)) {
                        match.restFiles.forEach((restFile: RestFile): void => {
                          const aRest = modalContent.find(restTemplate).clone();
                          aRest.find('.t3js-extensionScanner-hit-rest-panel-head').attr('href', '#collapse' + restFile.uniqueId);
                          aRest.find('.t3js-extensionScanner-hit-rest-panel-head .badge').empty().text(restFile.version);
                          aRest.find('.t3js-extensionScanner-hit-rest-panel-body').attr('id', 'collapse' + restFile.uniqueId);
                          aRest.find('.t3js-extensionScanner-hit-rest-headline').text(restFile.headline);
                          aRest.find('.t3js-extensionScanner-hit-rest-body').text(restFile.content);
                          aRest.addClass('panel-' + restFile.class);
                          aMatch.find('.t3js-extensionScanner-hit-file-rest-container').append(aRest);
                          this.listOfAffectedRestFileHashes.push(restFile.file_hash);
                        });
                      }
                      const panelClass =
                        aMatch.find('.panel-breaking', '.t3js-extensionScanner-hit-file-rest-container').length > 0
                          ? 'panel-danger'
                          : 'panel-warning';
                      aMatch.addClass(panelClass);
                      $extensionContainer.find('.t3js-extensionScanner-extension-body').removeClass('hide').append(aMatch);
                      if (panelClass === 'panel-danger') {
                        $extensionContainer.removeClass('panel-warning').addClass(panelClass);
                      }
                      if (panelClass === 'panel-warning' && !$extensionContainer.hasClass('panel-danger')) {
                        $extensionContainer.addClass(panelClass);
                      }
                    });
                  }
                  if (fileData.success) {
                    const currentLinesOfCode = parseInt($extensionContainer.find('.t3js-extensionScanner-extension-body-loc').text(), 10);
                    $extensionContainer.find('.t3js-extensionScanner-extension-body-loc').empty()
                      .text(currentLinesOfCode + fileData.effectiveCodeLines);
                    if (fileData.isFileIgnored) {
                      const currentIgnoredFiles = parseInt(
                        $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-files').text(),
                        10,
                      );
                      $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-files').empty().text(currentIgnoredFiles + 1);
                    }
                    const currentIgnoredLines = parseInt(
                      $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-lines').text(),
                      10,
                    );
                    $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-lines').empty()
                      .text(currentIgnoredLines + fileData.ignoredLines);
                  }
                  if (doneFiles === numberOfFiles) {
                    if (!hitFound) {
                      $extensionContainer.addClass('panel-success');
                    }
                    $extensionContainer.addClass('t3js-extensionscan-finished');
                    this.setProgressForAll();
                    $extensionContainer.find('.t3js-extensionScanner-scan-single').text('Rescan').attr('disabled', null);
                  }
                },
                error: (xhr: XMLHttpRequest): void => {
                  doneFiles = doneFiles + 1;
                  this.setStatusMessageForScan(extension, doneFiles, numberOfFiles);
                  this.setProgressForScan(extension, doneFiles, numberOfFiles);
                  this.setProgressForAll();
                  Notification.error('Oops, an error occurred', 'Please look at the console output for details');
                  console.error(xhr);
                },
                });
              });
            } else {
              Notification.warning('No files found', 'The extension EXT:' + extension + ' contains no files we can scan');
            }
          } else {
            Notification.error('Oops, an error occurred', 'Please look at the console output for details');
            console.error(data);
          }
        },
        error: (xhr: XMLHttpRequest): void => {
          Router.handleAjaxError(xhr, modalContent);
        },
      },
    );
  }
}

export = new ExtensionScanner();
