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
 * Module: TYPO3/CMS/Install/ExtensionScanner
 */
define(['jquery',
  'TYPO3/CMS/Install/Router',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Install/AjaxQueue'
], function($, Router, Notification, AjaxQueue) {
  'use strict';

  return {
    selectorModalBody: '.t3js-modal-body',
    selectorModuleContent: '.t3js-module-content',
    listOfAffectedRestFileHashes: [],
    selectorExtensionContainer: '.t3js-extensionScanner-extension',
    selectorNumberOfFiles: '.t3js-extensionScanner-number-of-files',
    selectorScanSingleTrigger: '.t3js-extensionScanner-scan-single',

    initialize: function(currentModal) {
      var self = this;
      this.currentModal = currentModal;
      this.getData();

      currentModal.on('show.bs.collapse', self.selectorExtensionContainer, function(e) {
        // Scan a single extension by opening the panel
        var $me = $(e.currentTarget);
        if (typeof $me.data('scanned') === 'undefined') {
          var extension = $me.data('extension');
          self.scanSingleExtension(extension);
          $me.data('scanned', true);
        }
      }).on('hide.bs.modal', function() {
        AjaxQueue.flush();
      }).on('click', self.selectorScanSingleTrigger, function(e) {
        // Scan a single extension by clicking "Rescan"
        e.preventDefault();

        var extension = $(e.currentTarget).closest(self.selectorExtensionContainer).data('extension');
        self.scanSingleExtension(extension);
      }).on('click', '.t3js-extensionScanner-scan-all', function(e) {
        // Scan all button
        e.preventDefault();
        var $extensions = currentModal.find(self.selectorExtensionContainer);
        self.scanAll($extensions);
      });
    },

    getData: function() {
      var modalContent = this.currentModal.find(this.selectorModalBody);
      $.ajax({
        url: Router.getUrl('extensionScannerGetData'),
        cache: false,
        success: function(data) {
          if (data.success === true) {
            modalContent.empty().append(data.html);
          } else {
            Notification.error('Something went wrong');
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    },

    /**
     * @param {string} extension
     * @returns {string}
     */
    getExtensionSelector: function(extension) {
      return this.selectorExtensionContainer + '-' + extension;
    },

    /**
     * @param {JQuery} $extensions
     */
    scanAll: function($extensions) {
      var self = this;
      this.currentModal.find(this.selectorExtensionContainer)
        .removeClass('panel-danger panel-warning panel-success')
        .find('.panel-progress-bar')
        .css('width', 0)
        .attr('aria-valuenow', 0)
        .find('span')
        .text('0%');
      this.setProgressForAll();
      $extensions.each(function() {
        var $me = $(this);
        var extension = $me.data('extension');
        self.scanSingleExtension(extension);
        $me.data('scanned', true);
      });
    },

    /**
     * @param {string} extension
     * @param {number} doneFiles
     * @param {number} numberOfFiles
     */
    setStatusMessageForScan: function(extension, doneFiles, numberOfFiles) {
      this.currentModal.find(this.getExtensionSelector(extension))
        .find(this.selectorNumberOfFiles)
        .text('Checked ' + doneFiles + ' of ' + numberOfFiles + ' files');
    },

    /**
     * @param {string} extension
     * @param {number} doneFiles
     * @param {number} numberOfFiles
     */
    setProgressForScan: function(extension, doneFiles, numberOfFiles) {
      var percent = (doneFiles / numberOfFiles) * 100;
      this.currentModal.find(this.getExtensionSelector(extension))
        .find('.panel-progress-bar')
        .css('width', percent + '%')
        .attr('aria-valuenow', percent)
        .find('span')
        .text(percent + '%');
    },

    /**
     * Update main progress bar
     */
    setProgressForAll: function() {
      var self = this;
      // var numberOfExtensions = $(this.selectorExtensionContainer).length;
      var numberOfExtensions = this.currentModal.find(this.selectorExtensionContainer).length;
      var numberOfSuccess = this.currentModal.find(this.selectorExtensionContainer + '.t3js-extensionscan-finished.panel-success').length;
      var numberOfWarning = this.currentModal.find(this.selectorExtensionContainer + '.t3js-extensionscan-finished.panel-warning').length;
      var numberOfError = this.currentModal.find(this.selectorExtensionContainer + '.t3js-extensionscan-finished.panel-danger').length;
      var numberOfScannedExtensions = numberOfSuccess + numberOfWarning + numberOfError;
      var percent = (numberOfScannedExtensions / numberOfExtensions) * 100;
      var modalContent = this.currentModal.find(this.selectorModalBody);
      this.currentModal.find('.t3js-extensionScanner-progress-all-extension .progress-bar')
        .css('width', percent + '%')
        .attr('aria-valuenow', percent)
        .find('span')
        .text(numberOfScannedExtensions + ' of ' + numberOfExtensions + ' scanned');

      if (numberOfScannedExtensions === numberOfExtensions) {
        Notification.success('Scan finished', 'All extensions have been scanned');
        $.ajax({
          url: Router.getUrl(),
          method: 'POST',
          data: {
            'install': {
              'action': 'extensionScannerMarkFullyScannedRestFiles',
              'token': self.currentModal.find(self.selectorModuleContent).data('extension-scanner-mark-fully-scanned-rest-files-token'),
              'hashes': self.uniqueArray(this.listOfAffectedRestFileHashes)
            }
          },
          cache: false,
          success: function(data) {
            if (data.success === true) {
              Notification.success('Marked not affected files', 'Marked ' + data.markedAsNotAffected + ' ReST files as not affected.');
            }
          },
          error: function(xhr) {
            Router.handleAjaxError(xhr, modalContent);
          }
        });
      }
    },

    /**
     * Helper method removing duplicate entries from an array
     *
     * @param {Array} anArray
     * @returns {Array}
     */
    uniqueArray: function(anArray) {
      return anArray.filter(function(value, index, self) {
        return self.indexOf(value) === index;
      });
    },

    /**
     * Handle a single extension scan
     *
     * @param {string} extension
     */
    scanSingleExtension: function(extension) {
      var self = this;
      var executeToken = this.currentModal.find(this.selectorModuleContent).data('extension-scanner-files-token');
      var modalContent = this.currentModal.find(this.selectorModalBody);
      var $extensionContainer = this.currentModal.find(this.getExtensionSelector(extension));
      var hitTemplate = '#t3js-extensionScanner-file-hit-template';
      var restTemplate = '#t3js-extensionScanner-file-hit-rest-template';
      var hitFound = false;
      $extensionContainer.removeClass('panel-danger panel-warning panel-success t3js-extensionscan-finished');
      $extensionContainer.data('hasRun', 'true');
      $extensionContainer.find('.t3js-extensionScanner-scan-single').text('Scanning...').attr('disabled', 'disabled');
      $extensionContainer.find('.t3js-extensionScanner-extension-body-loc').empty().text('0');
      $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-files').empty().text('0');
      $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-lines').empty().text('0');
      this.setProgressForAll();
      $.ajax({
        url: Router.getUrl(),
        method: 'POST',
        data: {
          'install': {
            'action': 'extensionScannerFiles',
            'token': executeToken,
            'extension': extension
          }
        },
        cache: false,
        success: function(data) {
          if (data.success === true && Array.isArray(data.files)) {
            var numberOfFiles = data.files.length;
            if (numberOfFiles > 0) {
              self.setStatusMessageForScan(extension, 0, numberOfFiles);
              $extensionContainer.find('.t3js-extensionScanner-extension-body').text('');
              var doneFiles = 0;
              data.files.forEach(function(file) {
                AjaxQueue.add(
                  {
                    method: 'POST',
                    data: {
                      'install': {
                        'action': 'extensionScannerScanFile',
                        'token': self.currentModal.find(self.selectorModuleContent).data('extension-scanner-scan-file-token'),
                        'extension': extension,
                        'file': file
                      }
                    },
                    url: Router.getUrl(),
                    cache: false,
                    success: function(fileData) {
                      doneFiles = doneFiles + 1;
                      self.setStatusMessageForScan(extension, doneFiles, numberOfFiles);
                      self.setProgressForScan(extension, doneFiles, numberOfFiles);
                      if (fileData.success && $.isArray(fileData.matches)) {
                        $(fileData.matches).each(function() {
                          hitFound = true;
                          var match = this;
                          var aMatch = modalContent.find(hitTemplate).clone();
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
                              .append('<span class="badge" title="Match has been annotated by extension author as false positive match">silenced</span>');
                          }
                          aMatch.find('.t3js-extensionScanner-hit-file-lineContent').empty().text(match.lineContent);
                          aMatch.find('.t3js-extensionScanner-hit-file-line').empty().text(match.line + ': ');
                          if ($.isArray(match.restFiles)) {
                            $(match.restFiles).each(function() {
                              var restFile = this;
                              var aRest = modalContent.find(restTemplate).clone();
                              aRest.find('.t3js-extensionScanner-hit-rest-panel-head').attr('href', '#collapse' + restFile.uniqueId);
                              aRest.find('.t3js-extensionScanner-hit-rest-panel-head .badge').empty().text(restFile.version);
                              aRest.find('.t3js-extensionScanner-hit-rest-panel-body').attr('id', 'collapse' + restFile.uniqueId);
                              aRest.find('.t3js-extensionScanner-hit-rest-headline').text(restFile.headline);
                              aRest.find('.t3js-extensionScanner-hit-rest-body').text(restFile.content);
                              aRest.addClass('panel-' + restFile.class);
                              aMatch.find('.t3js-extensionScanner-hit-file-rest-container').append(aRest);
                              self.listOfAffectedRestFileHashes.push(restFile.file_hash);
                            });
                          }
                          var panelClass =
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
                        var currentLinesOfCode = parseInt($extensionContainer.find('.t3js-extensionScanner-extension-body-loc').text());
                        $extensionContainer.find('.t3js-extensionScanner-extension-body-loc').empty().text(currentLinesOfCode + parseInt(fileData.effectiveCodeLines));
                        if (fileData.isFileIgnored) {
                          var currentIgnoredFiles = parseInt($extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-files').text());
                          $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-files').empty().text(currentIgnoredFiles + 1);
                        }
                        var currentIgnoredLines = parseInt($extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-lines').text());
                        $extensionContainer.find('.t3js-extensionScanner-extension-body-ignored-lines').empty().text(currentIgnoredLines + parseInt(fileData.ignoredLines));
                      }
                      if (doneFiles === numberOfFiles) {
                        if (!hitFound) {
                          $extensionContainer.addClass('panel-success');
                        }
                        $extensionContainer.addClass('t3js-extensionscan-finished');
                        self.setProgressForAll();
                        $extensionContainer.find('.t3js-extensionScanner-scan-single').text('Rescan').attr('disabled', null);
                      }
                    },
                    error: function(data) {
                      doneFiles = doneFiles + 1;
                      self.setStatusMessageForScan(extension, doneFiles, numberOfFiles);
                      self.setProgressForScan(extension, doneFiles, numberOfFiles);
                      self.setProgressForAll();
                      console.error(data);
                    }
                  }
                );
              });
            } else {
              Notification.warning('No files found', 'The extension EXT:' + extension + ' contains no files we can scan');
            }
          } else {
            Notification.error('Oops, an error occurred', 'Please look at the browser console output for details');
            console.error(data);
          }
        },
        error: function(xhr) {
          Router.handleAjaxError(xhr, modalContent);
        }
      });
    }
  };
});
