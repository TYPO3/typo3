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
		me.$body.append('<div id="dragInformation" />');
		me.$element = $('#dragInformation');
		me.$element.addClass('DragUpload-DropZone').addClass('t3-dropzone');

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

		me.handleDrop = function (event) {
			if (event.stopPropagation) {
				event.stopPropagation(); // stops the browser from redirecting.
			}
			me.ignoreDrop(event);

			if (uploadTarget = $(this).parent('[data-target-folder]').length > 0) {
				me.target = uploadTarget;
			}
			// now do something with:
			var files = event.dataTransfer.files;
			if (files.length > 0) {
				me.$element.html(me.msg.uploading);
				var formData = new FormData();
				formData.append('file[upload][1][target]', me.target);
				formData.append('file[upload][1][data]', '1');
				formData.append('overwriteExistingFiles', '1');
				formData.append('redirect', '');
				for (var i = 0; i < files.length; i++) {
					formData.append('upload_1[]', files[i]);
				}


				// now post a new XHR request
				var xhr = new XMLHttpRequest();
				xhr.open('POST', 'tce_file.php');
				xhr.onload = function () {
					if (xhr.status === 200) {
						window.location = window.location;
					} else {
					}
					me.$body.removeClass('uploadInProgress')
				};

				me.$body.addClass('uploadInProgress');
				xhr.send(formData);
			}
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