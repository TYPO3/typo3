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
 * Module: TYPO3/CMS/Backend/DragUploader
 *
 */
define(['jquery',
  'moment',
  'nprogress',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Notification',
  'TYPO3/CMS/Backend/Severity',
  'TYPO3/CMS/Lang/Lang'
], function($, moment, NProgress, Modal, Notification, Severity) {

  /**
   * Array of files which are asked for being overridden
   *
   * @type {array}
   */
  var askForOverride = [],
    percentagePerFile = 1;

  /**
   * File actions
   */
  var actions = {
    OVERRIDE: 'replace',
    RENAME: 'rename',
    SKIP: 'cancel',
    USE_EXISTING: 'useExisting'
  };

  /*
   * part 1: a generic jQuery plugin "$.dragUploader"
   */

  // register the constructor
  /**
   *
   * @param {HTMLElement} element
   * @constructor
   * @exports TYPO3/CMS/Backend/DragUploader
   */
  var DragUploaderPlugin = function(element) {
    var me = this;
    me.$body = $('body');
    me.$element = $(element);
    me.$trigger = $(me.$element.data('dropzoneTrigger'));
    me.$dropzone = $('<div />').addClass('dropzone').hide();
    me.irreObjectUid = me.$element.data('fileIrreObject');
    var dropZoneEscapedTarget = me.$element.data('dropzoneTarget');
    if (me.irreObjectUid && me.$element.nextAll(dropZoneEscapedTarget).length !== 0) {
      me.dropZoneInsertBefore = true;
      me.$dropzone.insertBefore(dropZoneEscapedTarget);
    } else {
      me.dropZoneInsertBefore = false;
      me.$dropzone.insertAfter(dropZoneEscapedTarget);
    }
    me.$dropzoneMask = $('<div />').addClass('dropzone-mask').appendTo(me.$dropzone);
    me.$fileInput = $('<input type="file" multiple name="files[]" />').addClass('upload-file-picker').appendTo(me.$body);
    me.$fileList = $(me.$element.data('progress-container'));
    me.fileListColumnCount = $('thead tr:first th', me.$fileList).length;
    me.filesExtensionsAllowed = me.$element.data('file-allowed');
    me.fileDenyPattern = me.$element.data('file-deny-pattern') ? new RegExp(me.$element.data('file-deny-pattern'), 'i') : false;
    me.maxFileSize = parseInt(me.$element.data('max-file-size'));
    me.target = me.$element.data('target-folder');

    me.browserCapabilities = {
      fileReader: typeof FileReader !== 'undefined',
      DnD: 'draggable' in document.createElement('span'),
      FormData: !!window.FormData,
      Progress: "upload" in new XMLHttpRequest
    };

    /**
     *
     */
    me.showDropzone = function() {
      me.$dropzone.show();
    };

    /**
     *
     * @param {Event} event
     */
    me.hideDropzone = function(event) {
      event.stopPropagation();
      event.preventDefault();
      me.$dropzone.hide();
    };

    /**
     *
     * @param {Event} event
     * @returns {Boolean}
     */
    me.dragFileIntoDocument = function(event) {
      event.stopPropagation();
      event.preventDefault();
      me.$body.addClass('drop-in-progress');
      me.showDropzone();
      return false;
    };

    /**
     *
     * @param {Event} event
     * @returns {Boolean}
     */
    me.dragAborted = function(event) {
      event.stopPropagation();
      event.preventDefault();
      me.$body.removeClass('drop-in-progress');
      return false;
    };

    /**
     *
     * @param {Event} event
     * @returns {Boolean}
     */
    me.ignoreDrop = function(event) {
      // stops the browser from redirecting.
      event.stopPropagation();
      event.preventDefault();
      me.dragAborted(event);
      return false;
    };

    /**
     *
     * @param {Event} event
     */
    me.handleDrop = function(event) {
      me.ignoreDrop(event);
      me.processFiles(event.originalEvent.dataTransfer.files);
      me.$dropzone.removeClass('drop-status-ok');
    };

    /**
     *
     * @param {Array} files
     */
    me.processFiles = function(files) {
      me.queueLength = files.length;

      if (!me.$fileList.is(':visible')) {
        me.$fileList.show();
      }

      NProgress.start();
      percentagePerFile = 1 / files.length;

      // Check for each file if is already exist before adding it to the queue
      var ajaxCalls = [];
      $.each(files, function(i, file) {

        ajaxCalls[i] = $.ajax({
          url: TYPO3.settings.ajaxUrls['file_exists'],
          data: {
            fileName: file.name,
            fileTarget: me.target
          },
          cache: false,
          success: function(response) {
            var fileExists = response !== false;
            if (fileExists) {
              askForOverride.push({
                original: response,
                uploaded: file,
                action: me.irreObjectUid ? actions.USE_EXISTING : actions.SKIP
              });
              NProgress.inc(percentagePerFile);
            } else {
              new FileQueueItem(me, file, 'cancel');
            }
          }
        });
      });

      $.when.apply($, ajaxCalls).done(function() {
        me.drawOverrideModal();
        NProgress.done();
      });

      delete ajaxCalls;
      me.$fileInput.val('');
    };

    /**
     *
     * @param {Event} event
     */
    me.fileInDropzone = function(event) {
      me.$dropzone.addClass('drop-status-ok');
    };

    /**
     *
     * @param {Event} event
     */
    me.fileOutOfDropzone = function(event) {
      me.$dropzone.removeClass('drop-status-ok');
    };

    /**
     * bind file picker to default upload button
     *
     * @param {Object} button
     */
    me.bindUploadButton = function(button) {
      button.click(function(event) {
        event.preventDefault();
        me.$fileInput.click();
        me.showDropzone();
      });
    };

    if (me.browserCapabilities.DnD) {
      me.$body.on('dragover', me.dragFileIntoDocument);
      me.$body.on('dragend', me.dragAborted);
      me.$body.on('drop', me.ignoreDrop);

      me.$dropzone.on('dragenter', me.fileInDropzone);
      me.$dropzoneMask.on('dragenter', me.fileInDropzone);
      me.$dropzoneMask.on('dragleave', me.fileOutOfDropzone);
      me.$dropzoneMask.on('drop', me.handleDrop);

      me.$dropzone.prepend(
        '<div class="dropzone-hint">' +
        '<div class="dropzone-hint-media">' +
        '<div class="dropzone-hint-icon"></div>' +
        '</div>' +
        '<div class="dropzone-hint-body">' +
        '<h3 class="dropzone-hint-title">' +
        TYPO3.lang['file_upload.dropzonehint.title'] +
        '</h3>' +
        '<p class="dropzone-hint-message">' +
        TYPO3.lang['file_upload.dropzonehint.message'] +
        '</p>' +
        '</div>' +
        '</div>').click(function() {
        me.$fileInput.click()
      });
      $('<span />').addClass('dropzone-close').click(me.hideDropzone).appendTo(me.$dropzone);

      // no filelist then create own progress table
      if (me.$fileList.length === 0) {
        me.$fileList = $('<table />')
          .attr('id', 'typo3-filelist')
          .addClass('table table-striped table-hover upload-queue')
          .html('<tbody></tbody>').hide();
        if (me.dropZoneInsertBefore) {
          me.$fileList.insertAfter(me.$dropzone);
        } else {
          me.$fileList.insertBefore(me.$dropzone);
        }
        me.fileListColumnCount = 7;
      }

      me.$fileInput.on('change', function() {
        me.processFiles(this.files);
      });

      me.bindUploadButton(me.$trigger.length ? me.$trigger : me.$element);
    }

    /**
     *
     */
    me.decrementQueueLength = function() {
      if (me.queueLength > 0) {
        me.queueLength--;
        if (me.queueLength === 0) {
          $.ajax({
            url: TYPO3.settings.ajaxUrls['flashmessages_render'],
            cache: false,
            success: function(data) {
              $.each(data, function(index, flashMessage) {
                Notification.showMessage(flashMessage.title, flashMessage.message, flashMessage.severity);
              });
            }
          });
        }
      }
    };

    /**
     *
     */
    me.drawOverrideModal = function() {
      var amountOfItems = Object.keys(askForOverride).length;
      if (amountOfItems === 0) {
        return;
      }
      var $modalContent = $('<div/>').append(
        $('<p/>').text(TYPO3.lang['file_upload.existingfiles.description']),
        $('<table/>', {class: 'table'}).append(
          $('<thead/>').append(
            $('<tr />').append(
              $('<th/>'),
              $('<th/>').text(TYPO3.lang['file_upload.header.originalFile']),
              $('<th/>').text(TYPO3.lang['file_upload.header.uploadedFile']),
              $('<th/>').text(TYPO3.lang['file_upload.header.action'])
            )
          )
        )
      );

      for (var i = 0; i < amountOfItems; ++i) {
        var $record = $('<tr />').append(
          $('<td />').append(
            (askForOverride[i].original.thumbUrl !== ''
                ? $('<img />', {src: askForOverride[i].original.thumbUrl, height: 40})
                : $(askForOverride[i].original.icon)
            )
          ),
          $('<td />').html(
            askForOverride[i].uploaded.name + ' (' + (DragUploader.fileSizeAsString(askForOverride[i].uploaded.size)) + ')' +
            '<br>' + moment(askForOverride[i].uploaded.lastModified, 'x').format('YYYY-MM-DD HH:mm')
          ),
          $('<td />').html(
            askForOverride[i].uploaded.name + ' (' + (DragUploader.fileSizeAsString(askForOverride[i].original.size)) + ')' +
            '<br>' + moment(askForOverride[i].original.mtime, 'X').format('YYYY-MM-DD HH:mm')
          ),
          $('<td />').append(
            $('<select />', {class: 'form-control t3js-actions', 'data-override': i}).append(
              (me.irreObjectUid ? $('<option/>').val(actions.USE_EXISTING).text(TYPO3.lang['file_upload.actions.use_existing']) : ''),
              $('<option />').val(actions.SKIP).text(TYPO3.lang['file_upload.actions.skip']),
              $('<option />').val(actions.RENAME).text(TYPO3.lang['file_upload.actions.rename']),
              $('<option />').val(actions.OVERRIDE).text(TYPO3.lang['file_upload.actions.override'])
            )
          )
        );
        $modalContent.find('table').append('<tbody />').append($record);
      }

      var $modal = Modal.confirm(TYPO3.lang['file_upload.existingfiles.title'], $modalContent, Severity.warning, [
        {
          text: $(this).data('button-close-text') || TYPO3.lang['file_upload.button.cancel'] || 'Cancel',
          active: true,
          btnClass: 'btn-default',
          name: 'cancel'
        },
        {
          text: $(this).data('button-ok-text') || TYPO3.lang['file_upload.button.continue'] || 'Continue with selected actions',
          btnClass: 'btn-warning',
          name: 'continue'
        }
      ], ['modal-inner-scroll']);
      $modal.find('.modal-dialog').addClass('modal-lg');

      $modal.find('.modal-footer').prepend(
        $('<span/>').addClass('form-inline').append(
          $('<label/>').text(TYPO3.lang['file_upload.actions.all.label']),
          $('<select/>', {class: 'form-control t3js-actions-all'}).append(
            $('<option/>').val('').text(TYPO3.lang['file_upload.actions.all.empty']),
            (me.irreObjectUid ? $('<option/>').val(actions.USE_EXISTING).text(TYPO3.lang['file_upload.actions.all.use_existing']) : ''),
            $('<option/>').val(actions.SKIP).text(TYPO3.lang['file_upload.actions.all.skip']),
            $('<option/>').val(actions.RENAME).text(TYPO3.lang['file_upload.actions.all.rename']),
            $('<option/>').val(actions.OVERRIDE).text(TYPO3.lang['file_upload.actions.all.override'])
          )
        )
      );

      $modal.on('change', '.t3js-actions-all', function() {
        var $me = $(this),
          value = $me.val();

        if (value !== '') {
          // mass action was selected, apply action to every file
          $modal.find('.t3js-actions').each(function(i, select) {
            var $select = $(select),
              index = parseInt($select.data('override'));
            $select.val(value).prop('disabled', 'disabled');
            askForOverride[index].action = $select.val();
          });
        } else {
          $modal.find('.t3js-actions').removeProp('disabled');
        }
      }).on('change', '.t3js-actions', function() {
        var $me = $(this),
          index = parseInt($me.data('override'));
        askForOverride[index].action = $me.val();
      }).on('button.clicked', function(e) {
        if (e.target.name === 'cancel') {
          askForOverride = [];
          Modal.dismiss();
        } else if (e.target.name === 'continue') {
          $.each(askForOverride, function(key, fileInfo) {
            if (fileInfo.action === actions.USE_EXISTING) {
              DragUploader.addFileToIrre(
                me.irreObjectUid,
                fileInfo.original
              );
            } else if (fileInfo.action !== actions.SKIP) {
              new FileQueueItem(me, fileInfo.uploaded, fileInfo.action);
            }
          });
          askForOverride = [];
          Modal.dismiss();
        }
      }).on('hidden.bs.modal', function() {
        askForOverride = [];
      });
    }
  };

  var FileQueueItem = function(dragUploader, file, override) {
    var me = this;
    me.dragUploader = dragUploader;
    me.file = file;
    me.override = override;

    me.$row = $('<tr />').addClass('upload-queue-item uploading');
    me.$iconCol = $('<td />').addClass('col-icon').appendTo(me.$row);
    me.$fileName = $('<td />').text(file.name).appendTo(me.$row);
    me.$progress = $('<td />').attr('colspan', me.dragUploader.fileListColumnCount - 2).appendTo(me.$row);
    me.$progressContainer = $('<div />').addClass('upload-queue-progress').appendTo(me.$progress);
    me.$progressBar = $('<div />').addClass('upload-queue-progress-bar').appendTo(me.$progressContainer);
    me.$progressPercentage = $('<span />').addClass('upload-queue-progress-percentage').appendTo(me.$progressContainer);
    me.$progressMessage = $('<span />').addClass('upload-queue-progress-message').appendTo(me.$progressContainer);

    me.updateMessage = function(message) {
      me.$progressMessage.text(message);
    };

    me.removeProgress = function() {
      if (me.$progress) {
        me.$progress.remove();
      }
    };

    me.uploadStart = function() {
      me.$progressPercentage.text('(0%)');
      me.$progressBar.width('1%');
      me.dragUploader.$trigger.trigger('uploadStart', [me]);
    };

    me.uploadError = function(response) {
      me.updateMessage(TYPO3.lang['file_upload.uploadFailed'].replace(/\{0\}/g, me.file.name));
      var error = $(response.responseText);
      if (error.is('t3err')) {
        me.$progressPercentage.text(error.text());
      } else {
        me.$progressPercentage.text('(' + response.statusText + ')');
      }
      me.$row.addClass('error');
      me.dragUploader.decrementQueueLength();
      me.dragUploader.$trigger.trigger('uploadError', [me, response]);
    };

    me.updateProgress = function(event) {
      var percentage = Math.round((event.loaded / event.total) * 100) + '%';
      me.$progressBar.outerWidth(percentage);
      me.$progressPercentage.text(percentage);
      me.dragUploader.$trigger.trigger('updateProgress', [me, percentage, event]);
    };

    me.uploadSuccess = function(data) {
      if (data.upload) {
        me.dragUploader.decrementQueueLength();
        me.$row.removeClass('uploading');
        me.$fileName.text(data.upload[0].name);
        me.$progressPercentage.text('');
        me.$progressMessage.text('100%');
        me.$progressBar.outerWidth('100%');

        // replace file icon
        if (data.upload[0].icon) {
          me.$iconCol.html('<a href="#" class="t3js-contextmenutrigger" data-uid="' + data.upload[0].id + '" data-table="sys_file">' + data.upload[0].icon + '&nbsp;</span></a>');
        }

        if (me.dragUploader.irreObjectUid) {
          DragUploader.addFileToIrre(
            me.dragUploader.irreObjectUid,
            data.upload[0]
          );
          setTimeout(function() {
            me.$row.remove();
            if ($('tr', me.dragUploader.$fileList).length === 0) {
              me.dragUploader.$fileList.hide();
              me.dragUploader.$trigger.trigger('uploadSuccess', [me, data]);
            }
          }, 3000);
        } else {
          setTimeout(function() {
            me.showFileInfo(data.upload[0]);
            me.dragUploader.$trigger.trigger('uploadSuccess', [me, data]);
          }, 3000);
        }
      }
    };

    me.showFileInfo = function(fileInfo) {
      me.removeProgress();
      // add spacing cells when clibboard and/or extended view is enabled
      for (i = 7; i < me.dragUploader.fileListColumnCount; i++) {
        $('<td />').text('').appendTo(me.$row);
      }
      $('<td />').text(fileInfo.extension.toUpperCase()).appendTo(me.$row);
      $('<td />').text(fileInfo.date).appendTo(me.$row);
      $('<td />').text(DragUploader.fileSizeAsString(fileInfo.size)).appendTo(me.$row);
      var permissions = '';
      if (fileInfo.permissions.read) {
        permissions += '<strong class="text-danger">' + TYPO3.lang['permissions.read'] + '</strong>';
      }
      if (fileInfo.permissions.write) {
        permissions += '<strong class="text-danger">' + TYPO3.lang['permissions.write'] + '</strong>';
      }
      $('<td />').html(permissions).appendTo(me.$row);
      $('<td />').text('-').appendTo(me.$row);
    };

    me.checkAllowedExtensions = function() {
      if (!me.dragUploader.filesExtensionsAllowed) {
        return true;
      }
      var extension = me.file.name.split('.').pop();
      var allowed = me.dragUploader.filesExtensionsAllowed.split(',');
      if ($.inArray(extension.toLowerCase(), allowed) !== -1) {
        return true;
      }
      return false;
    };

    // position queue item in filelist
    if ($('tbody tr.upload-queue-item', me.dragUploader.$fileList).length === 0) {
      me.$row.prependTo($('tbody', me.dragUploader.$fileList));
      me.$row.addClass('last');
    } else {
      me.$row.insertBefore($('tbody tr.upload-queue-item:first', me.dragUploader.$fileList));
    }

    // set dummy file icon
    me.$iconCol.html('<span class="t3-icon t3-icon-mimetypes t3-icon-other-other">&nbsp;</span>')

    // check file size
    if (me.dragUploader.maxFileSize > 0 && me.file.size > me.dragUploader.maxFileSize) {
      me.updateMessage(TYPO3.lang['file_upload.maxFileSizeExceeded']
        .replace(/\{0\}/g, me.file.name)
        .replace(/\{1\}/g, DragUploader.fileSizeAsString(me.dragUploader.maxFileSize)));
      me.$row.addClass('error');

      // check filename/extension against deny pattern
    } else if (me.dragUploader.fileDenyPattern && me.file.name.match(me.dragUploader.fileDenyPattern)) {
      me.updateMessage(TYPO3.lang['file_upload.fileNotAllowed'].replace(/\{0\}/g, me.file.name));
      me.$row.addClass('error');

    } else if (!me.checkAllowedExtensions()) {
      me.updateMessage(TYPO3.lang['file_upload.fileExtensionExpected']
        .replace(/\{0\}/g, me.dragUploader.filesExtensionsAllowed)
      );
      me.$row.addClass('error');
    } else {
      me.updateMessage('- ' + DragUploader.fileSizeAsString(me.file.size));

      var formData = new FormData();
      formData.append('file[upload][1][target]', me.dragUploader.target);
      formData.append('file[upload][1][data]', '1');
      formData.append('overwriteExistingFiles', me.override);
      formData.append('redirect', '');
      formData.append('upload_1', me.file);

      var s = $.extend(true, {}, $.ajaxSettings, {
        url: TYPO3.settings.ajaxUrls['file_process'],
        contentType: false,
        processData: false,
        data: formData,
        cache: false,
        type: 'POST',
        success: me.uploadSuccess,
        error: me.uploadError
      });

      s.xhr = function() {
        var xhr = $.ajaxSettings.xhr();
        xhr.upload.addEventListener('progress', me.updateProgress);
        return xhr;
      };

      // start upload
      me.upload = $.ajax(s);
    }
  };

  /**
   * part 2: The main module of this file
   * - initialize the DragUploader module and register
   * the jQuery plugin in the jQuery global object
   * when initializing the DragUploader module
   */
  var DragUploader = {};

  DragUploader.options = {};

  DragUploader.fileSizeAsString = function(size) {
    var string = '',
      sizeKB = size / 1024;

    if (parseInt(sizeKB) > 1024) {
      var sizeMB = sizeKB / 1024;
      string = sizeMB.toFixed(1) + ' MB';
    } else {
      string = sizeKB.toFixed(1) + ' KB';
    }
    return string;
  };

  DragUploader.addFileToIrre = function(irre_object, file) {
    inline.delayedImportElement(
      irre_object,
      'sys_file',
      file.uid,
      'file'
    );
  };

  DragUploader.initialize = function() {
    var me = this,
      opts = me.options;

    // register the jQuery plugin "DragUploaderPlugin"
    $.fn.dragUploader = function(option) {
      return this.each(function() {
        var $this = $(this),
          data = $this.data('DragUploaderPlugin');
        if (!data) {
          $this.data('DragUploaderPlugin', (data = new DragUploaderPlugin(this)));
        }
        if (typeof option === 'string') {
          data[option]();
        }
      });
    };

    $(function() {
      $('.t3js-drag-uploader').dragUploader();
    });
  };


  /**
   * part 3: initialize the RequireJS module, require possible post-initialize hooks,
   * and return the main object
   */
  var initialize = function() {

    DragUploader.initialize();

    // load required modules to hook in the post initialize function
    if (
      'undefined' !== typeof TYPO3.settings
      && 'undefined' !== typeof TYPO3.settings.RequireJS
      && 'undefined' !== typeof TYPO3.settings.RequireJS.PostInitializationModules
      && 'undefined' !== typeof TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/DragUploader']
    ) {
      $.each(TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/DragUploader'], function(pos, moduleName) {
        require([moduleName]);
      });
    }

    // return the object in the global space
    return DragUploader;
  };

  // call the main initialize function and execute the hooks
  return initialize();

});
