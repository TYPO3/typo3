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
	var DragUploaderPlugin = function() {
		var me = this;

		me.$body = $('body');
		me.$dragMask = $('<div />').addClass('t3-body-drag-mask').appendTo(me.$body);
		me.$dropzone = $('<div />').addClass('t3-dropzone').hide().insertAfter('#typo3-inner-docbody h1:first');
		me.$dropzoneMask = $('<div />').addClass('t3-dropzone-mask').appendTo(me.$dropzone);
		me.$fileInput = $('<input type="file" multiple name="files[]" />').addClass('t3-upload-file-picker').appendTo(me.$body);
		me.$fileList = $('#typo3-filelist');
		me.fileListColumnCount = $('thead tr:first td', me.$fileList).length;

		me.fileDenyPattern = new RegExp($('[data-file-deny-pattern]').attr('data-file-deny-pattern'), 'i');
		me.maxFileSize = parseInt($('[data-max-file-size]').attr('data-max-file-size'));
		me.target = $('[data-target-folder]').attr('data-target-folder');

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
		}

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

			// ask user if we should override files
			var override = confirm(TYPO3.l10n.localize('file_upload.overwriteExistingFiles'));

			// Add each file to queue and start upload
			$.each(files, function(i, file) {
				new FileQueueItem(me, file, override);
			});
		};

		me.fileInDropzone = function(event) {
			me.$dropzone.addClass('t3-dropzone-drop-ok');
		};

		me.fileOutOfDropzone = function(event) {
			me.$dropzone.removeClass('t3-dropzone-drop-ok');
		};

		if (me.browserCapabilities.DnD) {
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

			me.$fileInput.on('change', function() {
				me.processFiles(this.files);
			});

			// bind file picker to default upload button
			$('#button-upload').click(function(event) {
				event.preventDefault();
				me.$fileInput.click();
				me.showDropzone();
			});
		}
	};

	var FileQueueItem = function(dragUploader, file, override) {
		var me = this;
		me.dragUploader = dragUploader;
		me.file = file;
		me.override = override;

		me.$row = $('<tr />').addClass('file_list_normal t3-upload-queue-item uploading');
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
		};

		me.updateProgress = function(event) {
			var percentage = Math.round((event.loaded / event.total) * 100) + '%'
			me.$progressBar.width(percentage);
			me.$progressPercentage.text(percentage);
		};

		me.uploadComplete = function(data) {
			if (data.result.upload) {
				me.$row.removeClass('uploading');
				me.$fileName.text(data.result.upload[0].name);
				me.$progressPercentage.text('');
				me.$progressMessage.text('100%');
				me.$progressBar.width('100%');

				// replace file icon
				if (data.result.upload[0].iconClasses) {
					me.$iconCol.html('<span class="' + data.result.upload[0].iconClasses + '">&nbsp;</span>');
				}
				setTimeout(function() {me.showFileInfo(data.result.upload[0])}, 3000);
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

		// check filename/extension
		} else if (me.file.name.match(me.dragUploader.fileDenyPattern)) {
			me.updateMessage(TYPO3.l10n.localize('file_upload.fileNotAllowed').replace(/\{0\}/g, me.file.name));
			me.$row.addClass('error');

		} else {
			me.updateMessage('- ' + me.fileSizeAsString(me.file.size));

			var formData = new FormData();
			formData.append('file[upload][1][target]', me.dragUploader.target);
			formData.append('file[upload][1][data]', '1');
			if(me.override) {
				formData.append('overwriteExistingFiles', '1');
			}
			formData.append('ajaxID', 'TYPO3_tcefile::process');
			formData.append('redirect', '');
			formData.append('upload_1', me.file);

			var s = $.extend(true, {}, $.ajaxSettings, {
				url: 'ajax.php',
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

		$('body').dragUploader();

	};



	/**
     * part 3: initialize the RequireJS module, require possible post-initialize hooks,
	 * and return the main object
	 */
	var initialize = function() {

		DragUploader.initialize();

		// load required modules to hook in the post initialize function
		if (undefined !== TYPO3.settings && undefined !== TYPO3.settings.RequireJS.PostInitializationModules && undefined !== TYPO3.settings.RequireJS.PostInitializationModules['TYPO3/CMS/Backend/DragUploader']) {
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