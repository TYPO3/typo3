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

		me.$body = $('#typo3-inner-docbody');
		me.$body.append('<div id="dragInformation" />');
		me.$element = $('#dragInformation');
		me.$element.addClass('DragUpload-DropZone').addClass('t3-dropzone');
		me.$queue = $('<div id="dragUploadQueue" />').hide();
		me.$queue.appendTo(me.$body);
		me.fileQueueSize = 0;

		me.fileDenyPattern = $('[data-file-deny-pattern]').attr('data-file-deny-pattern');
		me.maxFileSize = parseInt($('[data-max-file-size]').attr('data-max-file-size'));
		me.target = $('[data-target-folder]').attr('data-target-folder');

		me.msg = {
			dropzone: TYPO3.l10n.localize('file_upload.dropzonehint'),
			uploading: TYPO3.l10n.localize('file_upload.upload-in-progress')
		};

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
			me.$element.html(me.msg.dropzone);
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
		me.addFileToQueue = function(file) {
			me.$queue.show();

			var _file = file, queueElement = $('<div />').addClass('file');
			$('<span />').text(file.name).addClass('filename').appendTo(queueElement)
			if (file.sizeOk == false) {
				$('<span />').addClass('progress error').text('File size to big').appendTo(queueElement);
			} else if (file.nameAllowed == false) {
				$('<span />').addClass('progress error').text('File name is not allowed').appendTo(queueElement);
			} else {
				$('<span />').addClass('progress').appendTo(queueElement);
			}
			$('<span />').addClass('controll').text('abort').click(function() {
				if(confirm('are you sure?')) {
					if(file.xhr) {
						_file.xhr.abort();
						queueElement.find('.progress').text('Upload canceled');
						$(this).hide();
					} else {
						queueElement.remove();
					}
				}
			}).appendTo(queueElement);
			queueElement.appendTo(me.$queue)

			return queueElement;
		}
		me.handleDrop = function (event) {

			if (event.stopPropagation) {
				event.stopPropagation(); // stops the browser from redirecting.
			}
			me.ignoreDrop(event);

			// ask user if we should override files
			var override = confirm('Do you want to overwrite existing files?');

			if (uploadTarget = $(this).parent('[data-target-folder]').length > 0) {
				me.target = uploadTarget;
			}
			// create queue element and upload each file
			$.each(event.dataTransfer.files, function(i, file) {
				file.sizeOk = true;
				file.nameAllowed = true;

				// check filesize, fileextension
				if (file.size > me.maxFileSize) {
					file.sizeOk = false;
					me.addFileToQueue(file);

				// check filename/extension
				// todo: fix pattern match
//				} else if (!file.name.match(me.fileDenyPattern)) {
//					file.nameAllowed = false;
//					me.addFileToQueue(file);

				} else {

					file.formData = new FormData();
					file.formData.append('file[upload][1][target]', me.target);
					file.formData.append('file[upload][1][data]', '1');
					if(override) {
						file.formData.append('overwriteExistingFiles', '1');
					}
					file.formData.append('redirect', '');

					file.formData.append('upload_1[]', file);

					// now post a new XHR request
					file.xhr = new XMLHttpRequest();
					file.xhr.open('POST', 'tce_file.php');

					file.xhr.onload = function () {
						me.fileQueueSize--;
						if (file.xhr.status === 200) {
							window.location = window.location;
						} else {
							// handle error
						}
						me.$body.removeClass('uploadInProgress')
					};
					file.xhr.onerror = function() {
						// todo: update progress to error in queueElement
						alert('error');
					};
					file.xhr.onprogress = function(progressEvent) {
						// todo: update progress in queueElement
					}
					me.fileQueueSize++;

					// create progress list
					me.addFileToQueue(file)
					file.xhr.send(file.formData);
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
			var $uploadButton = $('#button_upload');
			if ($uploadButton.length > 0) {
				$uploadButton.hide();
			}
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