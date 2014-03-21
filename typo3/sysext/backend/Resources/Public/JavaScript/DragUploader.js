/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
 *  All rights reserved
 *
 *  Released under GNU/GPL2+ (see license file in the main directory)
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  This copyright notice MUST APPEAR in all copies of this script
 *
 ***************************************************************/
/**
 * JavaScript RequireJS module called "TYPO3/CMS/Backend/DragUploader"
 *
 */
define('TYPO3/CMS/Backend/DragUploader', ['jquery'], function($) {

	/*
	 * part 1: a generic jQuery plugin "$.dragUploader"
	 */

	// register the constructor
	var DragUploaderPlugin = function(element) {
		var me = this;
		me.$body = $('body');
		me.$element = $(element);
		me.$trigger = $(me.$element.data('dropzone-trigger'));
		me.$dropzone = $('<div />').addClass('t3-dropzone').hide();
		if (me.$element.data('file-irre-object') && me.$element.nextAll(me.$element.data('dropzone-target')).length !== 0) {
			me.dropZoneInsertBefore = true;
			me.$dropzone.insertBefore(me.$element.data('dropzone-target'));
		} else {
			me.dropZoneInsertBefore = false;
			me.$dropzone.insertAfter(me.$element.data('dropzone-target'));
		}
		me.$dropzoneMask = $('<div />').addClass('t3-dropzone-mask').appendTo(me.$dropzone);
		me.$fileInput = $('<input type="file" multiple name="files[]" />').addClass('t3-upload-file-picker').appendTo(me.$body);
		me.$fileList = $(me.$element.data('progress-container'));
		me.fileListColumnCount = $('thead tr:first td', me.$fileList).length;
		me.filesExtensionsAllowed = me.$element.data('file-allowed');
		me.fileDenyPattern = me.$element.data('file-deny-pattern') ? new RegExp(me.$element.data('file-deny-pattern'), 'i') : false;
		me.maxFileSize = parseInt(me.$element.data('max-file-size'));
		me.target = me.$element.data('target-folder');

		me.browserCapabilities = {
			fileReader: typeof FileReader != 'undefined',
			DnD: 'draggable' in document.createElement('span'),
			FormData: !!window.FormData,
			Progress: "upload" in new XMLHttpRequest
		};

		me.showDropzone = function() {
			me.$dropzone.show();
		};

		me.hideDropzone = function(event) {
			event.stopPropagation();
			event.preventDefault();
			me.$dropzone.hide();
		};

		me.dragFileIntoDocument = function(event) {
			event.stopPropagation();
			event.preventDefault();
			me.$body.addClass('t3-drop-in-progress');
			me.showDropzone();
			return false;
		};

		me.dragAborted = function(event) {
			event.stopPropagation();
			event.preventDefault();
			me.$body.removeClass('t3-drop-in-progress');
			return false;
		};

		me.ignoreDrop = function(event) {
			// stops the browser from redirecting.
			event.stopPropagation();
			event.preventDefault();
			me.dragAborted(event);
			return false;
		};

		me.handleDrop = function (event) {
			me.ignoreDrop(event);
			me.processFiles(event.originalEvent.dataTransfer.files);
			me.$dropzone.removeClass('t3-dropzone-drop-ok');
		};

		me.processFiles = function (files) {
			me.queueLength = 0;

			// ask user if we should override files
			var override = confirm(TYPO3.l10n.localize('file_upload.overwriteExistingFiles'));
			if (!me.$fileList.is(':visible')) {
				me.$fileList.show();
			}
			// Add each file to queue and start upload
			$.each(files, function(i, file) {
				me.queueLength++;
				new FileQueueItem(me, file, override);
			});
		};

		me.fileInDropzone = function(event) {
			me.$dropzone.addClass('t3-dropzone-drop-ok');
		};

		me.fileOutOfDropzone = function(event) {
			me.$dropzone.removeClass('t3-dropzone-drop-ok');
		};

		// bind file picker to default upload button
		me.bindUploadButton = function(button) {
			button.click(function(event) {
				event.preventDefault();
				me.$fileInput.click();
				me.showDropzone();
			});
		};

		if (me.browserCapabilities.DnD) {
			me.$element.show();
			me.$body.on('dragover', me.dragFileIntoDocument);
			me.$body.on('dragend', me.dragAborted);
			me.$body.on('drop', me.ignoreDrop);

			me.$dropzone.on('dragenter', me.fileInDropzone);
			me.$dropzoneMask.on('dragenter', me.fileInDropzone);
			me.$dropzoneMask.on('dragleave', me.fileOutOfDropzone);
			me.$dropzoneMask.on('drop', me.handleDrop);

			me.$dropzone.prepend('<h4>'+TYPO3.l10n.localize('file_upload.dropzonehint.title')+'</h4><p>'+TYPO3.l10n.localize('file_upload.dropzonehint.message')+'</p>')
				.click(function(){me.$fileInput.click()});
			$('<span />').addClass('t3-icon t3-icon-actions t3-icon-actions-close t3-dropzone-close').html('&nbsp;').click(me.hideDropzone).appendTo(me.$dropzone);

			// no filelist then create own progress table
			if (me.$fileList.length === 0) {
				me.$fileList = $('<table />').attr('id', 'typo3-filelist').addClass('t3-table t3-upload-queue').html('<tbody></tbody>').hide();
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

		me.decrementQueueLength = function() {
			if (me.queueLength > 0) {
				me.queueLength--;
				if (me.queueLength == 0) {
					$.ajax({
						url: TYPO3.settings.ajaxUrls['DocumentTemplate::getFlashMessages'],
						cache: false,
						success: function(data) {
							var messages = $('#typo3-messages');
							if (messages.length == 0) {
								$('#typo3-inner-docbody').prepend(data);
							} else {
								messages.replaceWith(data);
							}
						}
					});
				}
			}
		}
	};

	var FileQueueItem = function(dragUploader, file, override) {

		var me = this;
		me.dragUploader = dragUploader;
		me.file = file;
		me.override = override;

		me.$row = $('<tr />').addClass('t3-upload-queue-item uploading');
		me.$iconCol = $('<td />').addClass('col-icon').appendTo(me.$row);
		me.$fileName = $('<td />').text(file.name).appendTo(me.$row);
		me.$progress = $('<td />').addClass('t3-upload-queue-progress')
								  .attr('colspan', me.dragUploader.fileListColumnCount-2).appendTo(me.$row);
		me.$progressContainer = $('<div />').addClass('t3-upload-queue-progress').appendTo(me.$progress);
		me.$progressBar = $('<div />').addClass('t3-upload-queue-progress-bar').appendTo(me.$progressContainer);
		me.$progressPercentage = $('<span />').addClass('t3-upload-queue-progress-percentage').appendTo(me.$progressContainer);
		me.$progressMessage = $('<span />').addClass('t3-upload-queue-progress-message').appendTo(me.$progressContainer);

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
		};

		me.uploadError = function(response) {
			me.updateMessage(TYPO3.l10n.localize('file_upload.uploadFailed').replace(/\{0\}/g, me.file.name));
			var error = $(response.responseText);
			if (error.is('t3err')) {
				me.$progressPercentage.text(error.text());
			} else {
				me.$progressPercentage.text('(' + response.statusText + ')');
			}
			me.$row.addClass('error');
			dragUploader.decrementQueueLength();
		};

		me.updateProgress = function(event) {
			var percentage = Math.round((event.loaded / event.total) * 100) + '%'
			me.$progressBar.width(percentage);
			me.$progressPercentage.text(percentage);
		};

		me.uploadComplete = function(data) {
			if (data.result.upload) {
				me.dragUploader.decrementQueueLength();
				me.$row.removeClass('uploading');
				me.$fileName.text(data.result.upload[0].name);
				me.$progressPercentage.text('');
				me.$progressMessage.text('100%');
				me.$progressBar.width('100%');

				// replace file icon
				if (data.result.upload[0].iconClasses) {
					me.$iconCol.html('<span class="' + data.result.upload[0].iconClasses + '">&nbsp;</span>');
				}

				if (me.dragUploader.$element.data('file-irre-object')) {
					inline.importElement(
						me.dragUploader.$element.data('file-irre-object'),
						'sys_file',
						data.result.upload[0].uid,
						'file'
					);
					setTimeout(function() {
						me.$row.remove();
						if ($('tr', me.dragUploader.$fileList).length === 0) {
							me.dragUploader.$fileList.hide();
						}
					}, 3000);


				} else {
					setTimeout(function() {me.showFileInfo(data.result.upload[0])}, 3000);
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
			$('<td />').text(me.fileSizeAsString(fileInfo.size)).appendTo(me.$row);
			var permissions = '';
			if (fileInfo.permissions.read) {
				permissions += '<span class="typo3-red"><strong>R</strong></span>';
			}
			if (fileInfo.permissions.write) {
				permissions += '<span class="typo3-red"><strong>W</strong></span>';
			}
			$('<td />').html(permissions).appendTo(me.$row);
			$('<td />').text('-').appendTo(me.$row);
		};

		me.fileSizeAsString = function(size) {
			var string = "";
			var sizeKB = size / 1024;
			if (parseInt(sizeKB) > 1024) {
				var sizeMB = sizeKB / 1024;
				string = sizeMB.toFixed(1) + " MB";
			} else {
				string = sizeKB.toFixed(1) + " KB";
			}
			return string;
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
		}

		// position queue item in file list
		if ($('tbody tr.t3-upload-queue-item', me.dragUploader.$fileList).length === 0) {
			me.$row.prependTo($('tbody', me.dragUploader.$fileList));
			me.$row.addClass('last');
		} else {
			me.$row.insertBefore($('tbody tr.t3-upload-queue-item:first', me.dragUploader.$fileList));
		}

		// set dummy file icon
		me.$iconCol.html('<span class="t3-icon t3-icon-mimetypes t3-icon-other-other">&nbsp;</span>')

		// check file size
		if (me.file.size > me.dragUploader.maxFileSize) {
			me.updateMessage(TYPO3.l10n.localize('file_upload.maxFileSizeExceeded')
				.replace(/\{0\}/g, me.file.name)
				.replace(/\{1\}/g, me.fileSizeAsString(me.dragUploader.maxFileSize)));
			me.$row.addClass('error');

		// check filename/extension against deny pattern
		} else if (me.dragUploader.fileDenyPattern && me.file.name.match(me.dragUploader.fileDenyPattern)) {
			me.updateMessage(TYPO3.l10n.localize('file_upload.fileNotAllowed').replace(/\{0\}/g, me.file.name));
			me.$row.addClass('error');

		} else if (!me.checkAllowedExtensions()) {
			me.updateMessage(TYPO3.l10n.localize('file_upload.fileExtensionExpected')
				.replace(/\{0\}/g, me.dragUploader.filesExtensionsAllowed)
			);
			me.$row.addClass('error');
		} else {
			me.updateMessage('- ' + me.fileSizeAsString(me.file.size));

			var formData = new FormData();
			formData.append('file[upload][1][target]', me.dragUploader.target);
			formData.append('file[upload][1][data]', '1');
			if(me.override) {
				formData.append('overwriteExistingFiles', '1');
			}
			formData.append('redirect', '');
			formData.append('upload_1', me.file);

			var s = $.extend(true, {}, $.ajaxSettings, {
				url: TYPO3.settings.ajaxUrls['TYPO3_tcefile::process'],
				contentType: false,
				processData: false,
				data: formData,
				cache: false,
				type: 'POST',
				success: me.uploadComplete,
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

	}

	/**
	 * part 2: The main module of this file
	 * - initialize the DragUploader module and register
	 * the jQuery plugin in the jQuery global object
	 * when initializing the DragUploader module
	 */
	var DragUploader = {};

	DragUploader.options = {
	};

	DragUploader.initialize = function() {
		var
			me = this
			,opts = me.options;

		// register the jQuery plugin "DragUploaderPlugin"
		$.fn.dragUploader = function(option) {
			return this.each(function() {
				var $this = $(this)
					, data = $this.data('DragUploaderPlugin');
				if (!data) {
					$this.data('DragUploaderPlugin', (data = new DragUploaderPlugin(this)));
				}
				if (typeof option == 'string') {
					data[option]();
				}
			})
		};

		$('.t3-drag-uploader').dragUploader();

	};



	/**
     * part 3: initialize the RequireJS module, require possible post-initialize hooks,
	 * and return the main object
	 */
	var initialize = function() {

		DragUploader.initialize();

		// load required modules to hook in the post initialize function
		if (undefined !== TYPO3.settings && undefined !== TYPO3.settings.RequireJS && undefined !== TYPO3.settings.RequireJS.PostInitializationModules && undefined !== TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/DragUploader']) {
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