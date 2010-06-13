/**
 * Javascript functions regarding the inclusion 
 * of a TYPO3-customized version of SWFupload
 *
 * For proper use see file_list.php or the doc_core_api manual
 *
 * (c) 2009-2010 Benjamin Mack
 * All rights reserved
 *
 * This script is part of TYPO3 by
 * Kasper Skaarhoj <kasperYYYYY@typo3.com>
 *
 * Released under GNU/GPL (see license file in tslib/)
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This copyright notice MUST APPEAR in all copies of this script
 */
Ext.onReady(function() {

	/**
	 * This class is a derivative of Ext.Window, with some special
	 * constructor to set some default values and a method to make
	 * sure that it's only instantiated once (see TYPO3.FileUploadWindow.getInstance())
	 * 
	 * It allows an info area (can be overriden if set with the same ID)
	 * and additional multiple components (possible to add via the add() method of Ext.Container)
	 * 
	 * Additionally, there are several events fired in the class that are specific for the upload handling
	 * that can be used in all use-cases
	 */
	TYPO3.FileUploadWindow = Ext.extend(Ext.Window, {
		completedUploads: 0,	// number of successfully completed uploads in this current instance, could be useful for some applications
		activeUploads: {},		// holds all TYPO3.FileUpload instances currently uploading or in queue
		lastError: null,	// last error occured
		swf: null,	// holds the SWFUpload instance
		deniedFileTypes: '',	// internal, local check to see if the uploading file is not allowed
		swfDefaultConfig: {	// includes all default options the SWFUpload needs
			fileUploadWindow: null,	// internal reference to the FileUploadWindow object for calling the event handlers

			flash_url:             TS.PATH_typo3 + "contrib/swfupload/swfupload.swf", // url to the swfupload flash file, should be absolute
			minimum_flash_version: '9.0.28',
			file_size_limit:       '20.480',	// you can either set a number which is in KB or set a unit (like 2 B, KB, MB, GB)
			file_queue_limit:      0,	// maximum files that are queued
			file_upload_limit:     0,	// maximum files that can be uploaded with one instance 
			upload_url:            TS.PATH_typo3 + "ajax.php",	// the destination URL that handles the upload
			file_post_name:        "upload_1",	// Name of the $_FILES key available in PHP (e.g. $_FILES['upload_1'])
			file_types:            "*.*",	// separate multiple file types with a semicolon. e.g. ".jpg;.jpeg"
			file_types_description: "All Files",
			assume_success_timeout: 5, // The number of seconds SWFUpload should wait for Flash to detect the server's response after the file has finished uploading. This setting allows you to work around the Flash Player bugs where long running server side scripts causes Flash to ignore the server response or the Mac Flash Player bug that ignores server responses with no content.
			post_params: {		// additional parameters later available via $_POST
				ajaxID: 'typo3_tcefile::process'
			},

			// SWFupload options that should not be changed, as our FileUploadWindow object is handling all of this
			button_placeholder_id: "t3-file-upload-window-button-selectfiles-placeholder",
			button_window_mode:    SWFUpload.WINDOW_MODE.TRANSPARENT,
			button_action:         SWFUpload.BUTTON_ACTION.SELECT_FILES,
			button_disabled:       false,
			button_width:          80,
			button_height:         22,
			button_cursor:         SWFUpload.CURSOR.HAND,
			// internal, SWFupload event handlers, please use the ones found in the "fileUploadWindow"
			file_dialog_complete_handler: function(numFilesSelected, numFilesQueued, numFilesInQueue) {
				this.fileUploadWindow.uploadSelectFiles(numFilesSelected, numFilesQueued, numFilesInQueue);
			},
			file_queued_handler: function(fileObj) {
				this.fileUploadWindow.uploadQueued(fileObj);
			},
			file_queue_error_handler: function(fileObj, errorCode, message) {
				this.fileUploadWindow.uploadQueuedError(fileObj, errorCode, message);
			},
			upload_start_handler: function(fileObj) {
				this.fileUploadWindow.uploadStart(fileObj);
			},
			upload_progress_handler: function(fileObj, bytesComplete, bytesTotal) {
				this.fileUploadWindow.uploadProgress(fileObj, bytesComplete, bytesTotal);
			},
			upload_error_handler: function(fileObj, errorCode, message) {
				this.fileUploadWindow.uploadError(fileObj, errorCode, message);
			},
			upload_success_handler: function(fileObj, serverData, responseReceived) {
				this.fileUploadWindow.uploadSuccess(fileObj, serverData, responseReceived);
			},
			upload_complete_handler: function(fileObj) {
				this.fileUploadWindow.uploadComplete(fileObj);
			},
			// callback introduced by the swf queue plugin
			queue_complete_handler: function() {
				this.fileUploadWindow.totalComplete();
			}
		},

		/**
		 * actions which are executed when the uploader window should be closed (actually, it's hidden)
		 */
		closeWindow: function() {
			this.cleanup();
			this.hide();
		},

		// component constructor
		// private
		initComponent: function() {
			var initialConfig = {
				layout: 'anchor',
				width: 350,
				height: 'auto',
				center: true,
				modal: true,
				tools: [],
				id: 't3-upload-window',
				title: String.format(TYPO3.LLL.fileUpload.windowTitle),
				shadow: false,
				hideBorders: true,
				tbar: [{
						id: 't3-file-upload-window-button-selectfiles',
						text: String.format(TYPO3.LLL.fileUpload.buttonSelectFiles),
						iconCls: 't3icon-ext-upload'
					}, {
						xtype: 'tbfill'
					}, {
						id: 't3-file-upload-window-button-cancel',
						text: String.format(TYPO3.LLL.fileUpload.buttonCancelAll),
						handler: this.closeWindow,
						scope: this,
						iconCls: 't3icon-ext-cancel'
					}
				]
			};
			// set the default options, if not set yet by the application from outside
			Ext.applyIf(this, initialConfig);

			// set default options that cannot be overriden from outside
			var staticConfig = {
				closable: true,
				closeAction: 'closeWindow',
				resizable: false
			};
			Ext.apply(this, staticConfig);

			TYPO3.FileUploadWindow.superclass.initComponent.call(this);
			this.addEvents(
				/**
				 * @event uploadSelectFiles
				 * Fires after the "select files" dialog has been closed
				 * @param {TYPO3.FileUploadWindow} this
				 * @param {Number} numFilesSelected  Total number of files selected
				 * @param {Number} numFilesQueued Total number of files that are queued
				 * @param {Number} numFilesInQueue Number of files in the queue right now
				 */
				'uploadSelectFiles',
				/**
				 * @event uploadQueued
				 * Fires after one file has been put in the queue.
				 * @param {TYPO3.FileUploadWindow} this
				 * @param {TYPO3.FileUpload} fileObj the instance of the file upload
				 */
				'uploadQueued',
				/**
				 * @event uploadQueuedError
				 * Fires after one file was tried to put in the queue but failed because it did not fit all requirements
				 * @param {TYPO3.FileUploadWindow} this
				 * @param {TYPO3.FileUpload} fileObj the instance of the file upload
				 * @param {Number} errorCode the internal SWFupload error code, see swfupload.js for details
				 * @param {String} message the default (english) message that comes with the error code
				 */
				'uploadQueuedError',
				/**
				 * @event uploadStart
				 * Fires right after the upload of a file was started
				 * @param {TYPO3.FileUploadWindow} this
				 * @param {TYPO3.FileUpload} fileObj the instance of the file upload
				 */
				'uploadStart',
				/**
				 * @event uploadProgress
				 * Fires multiple times during the upload process of one file, used to give a feedback to the user
				 * @param {TYPO3.FileUploadWindow} this
				 * @param {TYPO3.FileUpload} fileObj the instance of the file upload
				 */
				'uploadProgress',
				/**
				 * @event uploadError
				 * Fires if an error happens while uploading, if the upload was canceled or stopped
				 * @param {TYPO3.FileUploadWindow} this
				 * @param {TYPO3.FileUpload} fileObj the instance of the file upload
				 * @param {Number} errorCode the internal SWFupload error code, see swfupload.js for details
				 * @param {String} message the default (english) message that comes with the error code
				 */
				'uploadError',
				/**
				 * @event uploadProgress
				 * Fires after a file was successfully uploaded
				 * @param {TYPO3.FileUploadWindow} this
				 * @param {TYPO3.FileUpload} fileObj the instance of the file upload
				 * @param {String} serverData the data that gets returned from the server (the HTTP response)
				 * @param {String} responseReceived Due to some bugs in the Flash Player the server response may not be acknowledged and no uploadSuccess event is fired by Flash. In this case the assume_success_timeout setting is checked to see if enough time has passed to fire uploadSuccess anyway. In this case the received response parameter will be false.
				 */
				'uploadSuccess',
				/**
				 * @event uploadComplete
				 * Fires after the upload is complete and SWFUpload is ready to start the next file
				 * @param {TYPO3.FileUploadWindow} this
				 * @param {TYPO3.FileUpload} fileObj the instance of the file upload
				 */
				'uploadComplete',
				/**
				 * @event uploadComplete
				 * Fires after all files in the queue have been uploaded
				 * @param {TYPO3.FileUploadWindow} this
				 */
				'totalComplete'
			);
			this.setupWindow();
		},


		/**
		 * private, called once the window is rendered()
		 * instantiates the SWFUpload instance, which in turn replaces a placeholder ID (needed for Flash 10)
		 * with the flash movie
		 */
		setupFlash: function() {
			this.completedUploads = 0;	// reset the completed uploads
			var swfConfig = this.getFlashConfig();

			// add a placeholder div next to the button itself, so it can be detected and replaced by the flash plugin
			if (!Ext.fly(swfConfig.button_placeholder_id)) {
				var button = Ext.DomQuery.selectNode('#t3-file-upload-window-button-selectfiles button');
				Ext.DomHelper.insertBefore(button, '<div id="' + swfConfig.button_placeholder_id + '"></div>');
				// set the width of the swf-button in background according to the user-visible button
				swfConfig.button_width = button.clientWidth;
			}
			this.swf = new SWFUpload(swfConfig);
			this.swf.fileUploadWindow = this;

			// disable the "Cancel all uploads" button
			Ext.getCmp('t3-file-upload-window-button-cancel').disable();

			// add some info to the dialog
			// you can replace this by adding your own component with this ID in the constructor
			if (!this.getComponent('t3-upload-window-infopanel')) {
				var maxFileSize = (swfConfig.file_size_limit.toString().indexOf('B') > 0 ? swfConfig.file_size_limit : (swfConfig.file_size_limit / 1024) + ' MB');
				var txt = String.format(TYPO3.LLL.fileUpload.infoComponentMaxFileSize, maxFileSize) + '<br/>';
				if (swfConfig.file_upload_limit) {
					txt += String.format(TYPO3.LLL.fileUpload.infoComponentFileUploadLimit, swfConfig.file_upload_limit) + '<br/>';
				}
				if (swfConfig.file_types !== '*.*') {
					txt += String.format(TYPO3.LLL.fileUpload.infoComponentFileTypeLimit, swfConfig.file_types);
				}
				this.insert(0, new Ext.Panel({
					autoEl: { tag: 'div' },
					height: 'auto',
					id: 't3-upload-window-infopanel',
					html: txt,
					bodyBorder: false,
					border: false
				}));
			}
		},
		
		/**
		 * the configuration variables that can be set from outside (via uploadUrl etc) are set directly in the FileUploadWindow instance
		 * here we merge these variables from the outside with the default configuration set in the swfDefaultConfig
		 * and return a new config object that SWFupload can use and manipulate
		 * We decided to use camelCase parameters and parameters prepended with "upload" so it is
		 * better to understand than the existing SWFupload parameters
		 */
		getFlashConfig: function() {
			var swfConfig = {};
			Ext.apply(swfConfig, this.swfDefaultConfig);
			swfConfig.upload_url             = Ext.value(this.uploadURL, this.swfDefaultConfig.upload_url);
			swfConfig.file_size_limit        = Ext.value(this.uploadFileSizeLimit, this.swfDefaultConfig.file_size_limit);
			swfConfig.file_queue_limit       = Ext.value(this.uploadMaxFilesQueued, this.swfDefaultConfig.file_queue_limit);
			swfConfig.file_upload_limit      = Ext.value(this.uploadMaxFiles, this.swfDefaultConfig.file_upload_limit);
			swfConfig.file_post_name         = Ext.value(this.uploadFileParam, this.swfDefaultConfig.file_post_name);
			swfConfig.post_params            = Ext.value(this.uploadPostParams, this.swfDefaultConfig.post_params);
			// add the veriCode from the backend.php to verify the session with the flash client
			swfConfig.post_params.vC         = top.TS.veriCode;
			swfConfig.file_types_description = Ext.value(this.uploadFileTypesDescription, this.swfDefaultConfig.file_types_description);
			this.setFileTypeRestrictions(this.uploadFileTypes);
			return swfConfig;
		},
		
		/**
		 * this function merges the values from the TYPO3 style (comma separated) to the format
		 * SWFupload needs it to be (*.jpg;*.gif) and also adds deny file patterns
		 */
		setFileTypeRestrictions: function(typo3FileTypes) {
			if (typo3FileTypes.allow && typo3FileTypes.allow !== '' && typo3FileTypes.allow !== '*') {
				var allowedFiles = TYPO3.helpers.split(typo3FileTypes.allow, ',');
				this.swfDefaultConfig.file_types = '*.' + allowedFiles.join(';*.');
			}
			if (typo3FileTypes.deny && typo3FileTypes.deny !== '') {
				this.deniedFileTypes = typo3FileTypes.deny;
			}
		},

		
		/**
		 * because swfupload does not include a way to explicitly deny certain files, we need to
		 * check the file type of every selected file before it gets uploaded
		 */
		fileTypeIsAllowed: function(filename) {
			var ext = filename.substr(filename.lastIndexOf('.')+1);
			if (ext) {
				var denyTypes = this.deniedFileTypes;
				denyTypes += (denyTypes.length ? ',' : '') + TS.denyFileTypes;
				denyTypes = ',' + denyTypes + ',';
				var reg = new RegExp(',' + ext + ',', 'i');
				if (denyTypes.search(reg) === -1) {
					return true;
				}
			}
			return false;
		},

		/**
		 * sets up the visual information before the window is rendered, it adds some default event handlers
		 * (they have to be re-set all the time, because all of them are purged in the cleanup function)
		 */
		setupWindow: function() {
			this.on('add',    function() { this.doLayout(); }.bind(this));
			this.on('remove', function() { this.doLayout(); }.bind(this));
			this.on('hide',   function() { this.cleanup(); }.bind(this));
			this.on('show',   function() {
				this.setupFlash();
				this.doLayout();
			}.bind(this));
			// show the window, and disable the cancel button (only gets enabled when files are selected)
			this.show();
		},

		/**
		 * is used when the window is closed, then the flash movie is removed and the existing listeners
		 * are removed, all components are removed and the window is then hidden
		 */
		cleanup: function() {
			if (this.swf) {
				this.swf.cancelUpload();
				this.swf.destroy();		
			}
			this.swf = null;
			this.purgeListeners();
			this.removeAll(true);
		},

		// the following functions are event proxies that take the SWF-style events to the extJS handling
		// they also provide basic functionality for the file upload process
		// private
		uploadSelectFiles: function(numFilesSelected, numFilesQueued, numFilesInQueue) {
			if (numFilesSelected > 0) {
				// enable the "Cancel all uploads" button
				Ext.getCmp('t3-file-upload-window-button-cancel').enable();
			}

			this.swf.startUpload();
			this.fireEvent('uploadSelectFiles', this, [numFilesSelected, numFilesQueued, numFilesInQueue]);
		},

		// private
		uploadQueued: function(fileObj) {
			this.activeUploads[fileObj.id] = new TYPO3.FileUpload({file: fileObj});
			if (!this.fileTypeIsAllowed(fileObj.name)) {
				this.activeUploads[fileObj.id].error(SWFUpload.QUEUE_ERROR.INVALID_FILETYPE, '');
				this.fireEvent('uploadError', this, [this.activeUploads[fileObj.id], SWFUpload.QUEUE_ERROR.INVALID_FILETYPE, '']);
				this.swf.cancelUpload(fileObj.id, false);
			} else {
				this.fireEvent('uploadQueued', this, [this.activeUploads[fileObj.id]]);
			}
		},

		// private
		uploadQueuedError: function(fileObj, errorCode, message) {
			if (!this.activeUploads[fileObj.id]) {
				this.activeUploads[fileObj.id] = new TYPO3.FileUpload({file: fileObj});
			}
			this.activeUploads[fileObj.id].error(errorCode, message);
			this.fireEvent('uploadQueuedError', this, [this.activeUploads[fileObj.id], errorCode, message]);
			delete this.activeUploads[fileObj.id];
		},

		// private
		uploadStart: function(fileObj) {
			this.activeUploads[fileObj.id].start();
			this.fireEvent('uploadStart', this, [this.activeUploads[fileObj.id]]);
		},

		// private
		uploadProgress: function(fileObj, bytesComplete, bytesTotal) {
			this.activeUploads[fileObj.id].update(bytesComplete, bytesTotal);
			this.fireEvent('uploadProgress', this, [this.activeUploads[fileObj.id], bytesComplete, bytesTotal]);
		},

		// private
		uploadError: function(fileObj, errorCode, message) {
			this.activeUploads[fileObj.id].error(errorCode, message);
			this.lastError = {'errorCode': errorCode, 'message': message};
			this.fireEvent('uploadError', this, [this.activeUploads[fileObj.id], errorCode, message]);
			delete this.activeUploads[fileObj.id];
		},

		// private
		uploadSuccess: function(fileObj, serverData, responseReceived) {
			this.completedUploads++;
			this.activeUploads[fileObj.id].success();
			this.fireEvent('uploadSuccess', this, [this.activeUploads[fileObj.id], serverData, responseReceived]);	
		},

		// private
		uploadComplete: function(fileObj) {
			this.fireEvent('uploadComplete', this, [this.activeUploads[fileObj.id]]);
			delete this.activeUploads[fileObj.id];
		},

		// private
		totalComplete: function() {
			// disable the "Cancel all uploads" button (for the next use)
			Ext.getCmp('t3-file-upload-window-button-cancel').disable();

			if (this.completedUploads > 0) {
				this.fireEvent('totalComplete', this);
			} else {
					// if all our uploads fail, we try to provide some reasons
				this.totalError();
			}
			this.cleanup();
			this.hide();
		},

		// private
		// this handler is only called by totalComplete, not by swfupload itself
		totalError: function() {
			if (this.lastError === null) {
				return;
			}

			var errorCode = this.lastError.errorCode;
			var message = this.lastError.message;
			var messageText = null;

				// provide a more detailed problem description for the well known bugs
			switch (errorCode) {
				case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
					if (message == '401') {
						messageText = String.format(TYPO3.LLL.fileUpload.allError401);
					}
				break;

				case SWFUpload.UPLOAD_ERROR.IO_ERROR:
					if (message == 'Error #2038') {
						messageText = String.format(TYPO3.LLL.fileUpload.allError2038);
					}
				break;
			}
			Ext.MessageBox.show({
				title: String.format(TYPO3.LLL.fileUpload.allErrorMessageTitle),
				msg: String.format(TYPO3.LLL.fileUpload.allErrorMessageText + (messageText ? messageText : message)),
				buttons: Ext.MessageBox.OK,
				icon: Ext.MessageBox.ERROR
			});
		},
		
		/**
		 * Removes all components from this container.
		 * @param {Boolean} autoDestroy (optional) True to automatically invoke the removed Component's {@link Ext.Component#destroy} function.
		 * Defaults to the value of this Container's {@link #autoDestroy} config.
		 * @return {Array} Array of the destroyed components
		 * 
		 * This function is copied from the Container object of extJS, because it was documented but not correctly implement
		 */
		removeAll: function(autoDestroy) {
			var item, items = [];
			while((item = this.items.last())) {
				items.unshift(this.remove(item, autoDestroy));
			}
			return items;
		}
	});


 	// This function should be used to fetch a FileUploadWindow instance, to make sure
 	// that only one instance is currently running
	TYPO3.FileUploadWindow.getInstance = function(config) {
		var instance = TYPO3.getInstance('FileUploadWindow');
	 	if (instance) {
	 		if (!instance.swf) {
				Ext.apply(instance, config);
				instance.setupWindow();
		 		instance.show();	 		
	 		} else {
	 			// TODO, maybe we can raise an exception
				alert(String.format(TYPO3.LLL.fileUpload.processRunning));
	 		}
	 	} else {
	 		instance = new TYPO3.FileUploadWindow(config);
	 		TYPO3.addInstance('FileUploadWindow', instance);
	 	}
 		return instance;
	};

	// This function checks through the SWFObject if the required flash player is available
	TYPO3.FileUploadWindow.isFlashAvailable = function() {
		return swfobject.hasFlashPlayerVersion(TYPO3.FileUploadWindow.prototype.swfDefaultConfig.minimum_flash_version);
	};

	/**
	 * This class includes one instance of an upload
	 * and is used mainly to display the progress bar Component
	 */
	TYPO3.FileUpload = Ext.extend(Ext.Component, {
		infoPanel:   null,	// Ext.Panel instance holding the progressBar and the cancel button
		cancelButton: null,	// Ext.BoxComponent instance, holding the X in there
		progressBar: null,	// Ext.Component progress bar instance
		parent:      null,	// reference to the TYPO3.FileUploadWindow
		file:        null,	// reference to the SWFupload.File object

		// creates the Ext component, used when a file is queued
		initComponent: function() {
			TYPO3.FileUpload.superclass.initComponent.call(this);
			this.parent = TYPO3.getInstance('FileUploadWindow');

			// the progress bar instance
			this.progressBar = new Ext.ProgressBar({
				id: 'flashupload-progress ' + this.file.id,
				cls: 't3-upload-window-progressbar',
				text: String.format(TYPO3.LLL.fileUpload.uploadWait, this.file.name),
				width: '100%',
				height: 21,
				x: -1,
				disabled: true
			});

			// the cancel button
			this.cancelButton = new Ext.BoxComponent({
				autoEl: {
					tag: 'div',
					'class': 't3icon-ext-cancel t3iconstyle-center'
				},
				hidden: true,
				width: 22,
				height: 18,
				x: -100
			});

			// the panel that holds the progress bar and the button together
			this.infoPanel = new Ext.Panel({
				layout: 'absolute',
				width: '100%',
				hideBorders: true,
				bodyBorder: false,
				border: false,
				items: [this.progressBar, this.cancelButton]
			});

			this.infoPanel.on('show', function() {
				var h = this.progressBar.getBox().height;
				this.infoPanel.setHeight(h);

				// show the cancel button on hover
				this.infoPanel.getEl().on('mouseover', function() {
					this.cancelButton.show();
					this.cancelButton.setPosition(this.progressBar.getBox().width - this.cancelButton.getBox().width, -1);
				}, this);

				// hide the cancel button on hover out
				this.infoPanel.getEl().on('mouseout', function() { 
					this.cancelButton.hide();
				}, this);

				// if the cancel button is clicked, the download is canceled
				this.cancelButton.getEl().on('click', function() {
					if (this.parent.swf) {
						this.parent.swf.cancelUpload(this.file.id);
					}
					this.cleanup(10);
				}, this);
			}, this);
			this.parent.add(this.infoPanel);
			this.infoPanel.show();
		},
 
		// enables the progress bar and sets the text
		start: function() {
			this.progressBar.enable();
			this.progressBar.updateText(String.format(TYPO3.LLL.fileUpload.uploadStarting, this.file.name));
		},

		// updates the progress bar (+ text) to the current progress
		update: function(bytesComplete, bytesTotal) {
			var percent = (bytesComplete / bytesTotal);
			var text = String.format(TYPO3.LLL.fileUpload.uploadProgress, Math.round(100*percent, 1), this.file.name);
			this.progressBar.updateProgress(percent, text);
		},

		// simply updates the text in the bar
		updateText: function(text) {
			this.progressBar.updateText(text);
		},

		// is called if there is an error after queuing a file or while uploading
		// get details for the error code from here: SWFUpload.QUEUE_ERROR or
		// SWFUpload.UPLOAD_ERROR (see contrib/swfupload/swfupload.js)
		// can be further extended to color the progress bar in red colors
		// then calls the cleanup function
		error: function(errorCode, message) {
			var txt = message;
			switch (errorCode) {
				case SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED:
					txt = String.format(TYPO3.LLL.fileUpload.errorQueueLimitExceeded);
				break;
				case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
					txt = String.format(TYPO3.LLL.fileUpload.errorQueueFileSizeLimit, this.file.name);
				break;
				case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
					txt = String.format(TYPO3.LLL.fileUpload.errorQueueZeroByteFile, this.file.name);
				break;
				case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
					txt = String.format(TYPO3.LLL.fileUpload.errorQueueInvalidFiletype, this.file.name);
				break;

				case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadHttp, message);
				break;
				case SWFUpload.UPLOAD_ERROR.MISSING_UPLOAD_URL:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadMissingUrl);
				break;
				case SWFUpload.UPLOAD_ERROR.IO_ERROR:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadIO);
				break;
				case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadSecurityError, message);
				break;
				case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadLimit);
				break;
				case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadFailed);
				break;
				case SWFUpload.UPLOAD_ERROR.SPECIFIED_FILE_ID_NOT_FOUND:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadFileIDNotFound);
				break;
				case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadFileValidation);
				break;
				case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadFileCancelled, this.file.name);
				break;
				case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
					txt = String.format(TYPO3.LLL.fileUpload.errorUploadFileStopped, this.file.name);
				break;
			}
			this.updateText(txt);
			this.cleanup(10000);
		},

		// updates the progressbar to show the full 100% and a text that everything went fine
		// then calls the cleanup function
		success: function() {
			this.progressBar.updateProgress(1, String.format(TYPO3.LLL.fileUpload.uploadSuccess, this.file.name));
			this.cleanup();
		},

		// cleanup function that calls the destroy method right now after a specified delay
		cleanup: function(delay) {
			var cleanup = new Ext.util.DelayedTask(this.destroy, this);
			cleanup.delay((delay ? delay : 2000));
		},

		// internal function to remove the progress Bar and trigger an remove() event after that 
		// in the main window
		destroy: function() {
			if (this.infoPanel && this.infoPanel.id && Ext.get(this.infoPanel.id)) {
				Ext.get(this.infoPanel.id).fadeOut({
					callback: function() {
						this.parent.remove(this.infoPanel);
					}.bind(this)
				});			
			}
		}
	});


});
