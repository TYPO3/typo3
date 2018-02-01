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
  'TYPO3/CMS/Backend/Notification'
], function($, Router, Notification) {
  'use strict';

  return {
    listOfAffectedRestFileHashes: [],
    selectorFilesToken: '#t3js-extensionScanner-files-token',
    selectorScanFileToken: '#t3js-extensionScanner-scan-file-token',
    selectorMarkFullyScannedRestFilesToken: '#t3js-extensionScanner-mark-fully-scanned-rest-files-token',
    selectorExtensionContainer: '.t3js-extensionScanner-extension',
    selectorNumberOfFiles: '.t3js-extensionScanner-number-of-files',
    selectorScanSingleTrigger: '.t3js-extensionScanner-scan-single',

    initialize: function() {
      var self = this;
      $(document).on('click', this.selectorScanSingleTrigger, function(e) {
        // Scan a single extension
        var extension = $(e.target).data('extension');
        e.preventDefault();
        self.scanSingleExtension(extension);
        return false;
      });
      $(document).on('show.bs.collapse', this.selectorExtensionContainer, function(e) {
        // Trigger extension scan on opening a extension collapsible
        if ($(e.target).closest(self.selectorExtensionContainer).data('hasRun') !== 'true') {
          $(this).find(self.selectorScanSingleTrigger).click();
        }
      });
      $(document).on('click', '.t3js-extensionScanner-scan-all', function(e) {
        // Scann all button
        e.preventDefault();
        var $extensions = $(self.selectorExtensionContainer);
        self.scanAll($extensions);
        return false;
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
      $(this.selectorExtensionContainer)
        .removeClass('panel-danger panel-warning panel-success')
        .find('.panel-progress-bar')
        .css('width', 0)
        .attr('aria-valuenow', 0)
        .find('span')
        .text('0%');
      self.setProgressForAll();
      $extensions.each(function() {
        var extension = $(this).data('extension');
        self.scanSingleExtension(extension);
      });
    },

    /**
     * @param {string} extension
     * @param {number} doneFiles
     * @param {number} numberOfFiles
     */
    setStatusMessageForScan: function(extension, doneFiles, numberOfFiles) {
      $(this.getExtensionSelector(extension))
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
      $(this.getExtensionSelector(extension))
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
      var numberOfExtensions = $(this.selectorExtensionContainer).length;
      var numberOfSuccess = $(this.selectorExtensionContainer + '.t3js-extensionscan-finished.panel-success').length;
      var numberOfWarning = $(this.selectorExtensionContainer + '.t3js-extensionscan-finished.panel-warning').length;
      var numberOfError = $(this.selectorExtensionContainer + '.t3js-extensionscan-finished.panel-danger').length;
      var numberOfScannedExtensions = numberOfSuccess + numberOfWarning + numberOfError;
      var percent = (numberOfScannedExtensions / numberOfExtensions) * 100;
      $('.t3js-extensionScanner-progress-all-extension .progress-bar')
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
              'token': $(self.selectorMarkFullyScannedRestFilesToken).text(),
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
            Router.handleAjaxError(xhr);
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
      var $extensionContainer = $(this.getExtensionSelector(extension));
      var hitTemplate = $('#t3js-extensionScanner-file-hit-template').html();
      var restTemplate = $('#t3js-extensionScanner-file-hit-rest-template').html();
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
            'token': $(self.selectorFilesToken).text(),
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
                $.ajax({
                  method: 'POST',
                  data: {
                    'install': {
                      'action': 'extensionScannerScanFile',
                      'token': $(self.selectorScanFileToken).text(),
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
                        var aMatch = $(hitTemplate).clone();
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
                            var aRest = $(restTemplate).clone();
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
                    Notification.error('Oops, an error occurred', 'Please look at the console output for details');
                    console.error(data);
                  }
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
        error: function(xhr) {
          Router.handleAjaxError(xhr);
        }
      });
    }
  };
});
