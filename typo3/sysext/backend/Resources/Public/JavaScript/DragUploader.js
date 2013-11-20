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
		me.$element = $('<div />').addClass('DragUpload-DropZone t3-dropzone').appendTo(me.$body);
		me.$progress = $('<div />').addClass('DragUpload-ProgressInformation').hide().appendTo(me.$body);
		me.uploadCompletedCount = 0;
		me.fileQueue = [];
		me.filesOnServer = [];

		me.fileDenyPattern = new RegExp($('[data-file-deny-pattern]').attr('data-file-deny-pattern'), 'i');
		me.maxFileSize = parseInt($('[data-max-file-size]').attr('data-max-file-size'));
		me.target = $('[data-target-folder]').attr('data-target-folder');

		me.browserCapabilities = {
			fileReader: typeof FileReader != 'undefined',
			DnD: 'draggable' in document.createElement('span'),
			FormData: !!window.FormData,
			Progress: "upload" in new XMLHttpRequest
		};

		me.dragFileIntoDocument = function(event) {
			event.preventDefault && event.preventDefault();
			event.dataTransfer.dropEffect = 'copy';
			me.$body.addClass('dropInProgess');
			me.$element.html(TYPO3.l10n.localize('file_upload.dropzonehint'));
			return false;
		};

		me.dragAborted = function(event) {
			event.preventDefault && event.preventDefault();
			me.$body.removeClass('dropInProgess');
			return false;
		};

		me.ignoreDrop = function(event) {
			if (event.stopPropagation) {
				event.stopPropagation(); // stops the browser from redirecting.
			}
			me.dragAborted(event);
			return false;
		};

		me.updateProgress = function() {
			var fileCount = me.fileQueue.length;
			if (fileCount > 0 && fileCount === me.uploadCompletedCount) {;
				me.$progress.html('<p><strong>' + TYPO3.l10n.localize('file_upload.upload-finished') + '</strong></p>').show();
				window.location.reload();
			} else {
				me.$progress.html(
					'<p><strong>' + TYPO3.l10n.localize('file_upload.upload-in-progress') + '</strong></p>' +
					'<p>' + TYPO3.l10n.localize('file_upload.upload-progress-info').replace(/\{0\}/g, me.uploadCompletedCount).replace(/\{1\}/g, fileCount) + '</p>'
				).show();
			}
		};

		me.handleDrop = function (event) {

			if (event.stopPropagation) {
				event.stopPropagation(); // stops the browser from redirecting.
			}
			me.ignoreDrop(event);

			// collect files which would be overridden
			var filesToOverride = [];
			$.each(event.dataTransfer.files, function(i, file) {
				if($.inArray(file.name, me.filesOnServer) > -1) {
					filesToOverride.push(file.name);
				}
			});

			var override = false;
			if (filesToOverride.length > 0) {
				var message = TYPO3.l10n.localize('file_upload.overwriteExistingFiles')
					+ "\n\n" + filesToOverride.join("\n");

				// ask user if we should override files
				override = confirm(message);

				if (override === false) {
					// user canceled upload, do not proceed
					return false;
				}
			}

			// Add each file to queue and trigger upload
			$.each(event.dataTransfer.files, function(i, file) {

				// check filesize, fileextension
				if (file.size > me.maxFileSize) {
					TYPO3.Flashmessage.display(
						TYPO3.Severity.error,
						'Error',
						TYPO3.l10n.localize('file_upload.maxFilesizeExceeded').replace(/\{0\}/g, file.name).replace(/\{1\}/g, me.maxFileSize)
					);
				// check filename/extension
				} else if (file.name.match(me.fileDenyPattern)) {
					TYPO3.Flashmessage.display(
						TYPO3.Severity.error,
						'Error',
						TYPO3.l10n.localize('file_upload.fileNotAllowwed').replace(/\{0\}/g, file.name)
					);
				} else {

					var formData = new FormData();
					formData.append('file[upload][1][target]', me.target);
					formData.append('file[upload][1][data]', '1');
					if(override) {
						formData.append('overwriteExistingFiles', '1');
					}
					formData.append('redirect', '');
					formData.append('upload_1[]', file);

					// now post a new XHR request
					var xhr = new XMLHttpRequest();
					xhr.open('POST', 'tce_file.php');

					xhr.onload = function () {
						me.uploadCompletedCount++;
						me.updateProgress();
						me.filesOnServer.push(file.name);
					};
					xhr.onerror = function() {
						TYPO3.Flashmessage.display(
							TYPO3.Severity.error,
							'Error',
							TYPO3.l10n.localize('file_upload.uploadFailed').replace(/\{0\}/g, file.name)
						);
						me.uploadCompletedCount++;
						me.updateProgress();
					};
					xhr.onprogress = function(progressEvent) {
						me.updateProgress();
					};
					me.fileQueue.push(file);
					me.updateProgress();

					// start upload
					xhr.send(formData);
				}
			});
			me.$element.removeClass('t3-dropzone-dropReceiveOK');
			return false;
		};

		me.fileInDropzone = function(event) {
			me.$element.addClass('t3-dropzone-dropReceiveOK');
		};

		me.fileOutOfDropzone = function(event) {
			me.$element.removeClass('t3-dropzone-dropReceiveOK');
		};

		if (me.browserCapabilities.DnD) {
			var doc = document.documentElement;
			me.$body.get(0).ondragover = me.dragFileIntoDocument;

			me.$body.get(0).ondragend = me.dragAborted;
			me.$body.get(0).ondrop = me.ignoreDrop;

			me.$body.get(0).ondragenter = me.fileInDropzone;
			me.$body.get(0).ondragleave = me.dragAborted;
			me.$body.get(0).ondrop = me.handleDrop;

			// if upload button is present, remove it
			var $uploadButton = $('#button-upload');
			me.flashMessageShown = false;
			if ($uploadButton.length > 0) {
				$uploadButton.click(function(event) {
					if (!me.flashMessageShown) {
						event.preventDefault();
						TYPO3.Flashmessage.display(
							TYPO3.Severity.information,
							TYPO3.l10n.localize('file_upload.draginformation.title'),
							TYPO3.l10n.localize('file_upload.draginformation.message')
						);
						me.flashMessageShown = true;
						return false;
					}
				});
			}

			// initialize the files which are already present on server
			$('[data-file-name]').each(function(index, row) {
				me.filesOnServer.push($(row).data('file-name'));
			});
		}


	};

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