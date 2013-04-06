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
		me = this;
		me.$element = $(element);
		me.target = me.$element.attr('data-target-folder');
		me.msg = {
			uploading: me.$element.attr('data-msg-uploading'),
			dropzone: me.$element.attr('data-msg-drop-here')
		};

		var doc = document.documentElement;
		doc.ondragover = me.dragFileIntoDocument;
		doc.ondragleave = me.dragAborted;

		doc.ondragend = me.dragAborted;
		doc.ondrop = me.ignoreDrop;

		me.$element.get(0).ondragenter = me.fileInDropzone;
		me.$element.get(0).ondragleave = me.fileOutOfDropzone;
		me.$element.get(0).ondrop = me.handleDrop;
	};

	// define the logic for
	DragUploaderPlugin.prototype = {
		dragFileIntoDocument: function(event) {
			event.preventDefault && event.preventDefault();
			event.dataTransfer.dropEffect = 'copy';
			$(document.documentElement).addClass('dropInProgess');
			me.$element.html(me.msg.dropzone);
			return false;
		},
		dragAborted: function(event) {
			event.preventDefault && event.preventDefault();
			$(document.documentElement).removeClass('dropInProgess');
			return false;
		},
		ignoreDrop: function(event) {
			if (event.stopPropagation) {
				event.stopPropagation(); // stops the browser from redirecting.
			}
			me.dragAborted(event);
			return false;
		},
		handleDrop: function (event) {
			if (event.stopPropagation) {
				event.stopPropagation(); // stops the browser from redirecting.
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
				};

				xhr.send(formData);
			}
			return false;
		},
		fileInDropzone: function(event) {
			me.$element.addClass('t3-dropzone-dropReceiveOK');
		},
		fileOutOfDropzone: function(event) {
			me.$element.removeClass('t3-dropzone-dropReceiveOK');
		},


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
				// only apply the tabmenu to an item that does not have the tabmenu yet
				if (!data) {
					$this.data('DragUploaderPlugin', (data = new DragUploaderPlugin(this)));
				}
				if (typeof option == 'string') {
					data[option]();
				}
			})
		};

		$('.DragUpload-DropZone').dragUploader();

	};



	/**
     * part 3: initialize the RequireJS module, require possible post-initialize hooks,
	 * and return the main object
	 */
	var initialize = function(options) {

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