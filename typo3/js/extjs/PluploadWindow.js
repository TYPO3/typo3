/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Christian M?ller <christian@kitsunet.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

Ext.onReady(function() {
	/**
	 * Upload Window based on plupload.
	 *
	 * @author	Christian Müller <christian@kitsunet.de>
	 */

	TYPO3.PluploadWindow = new Ext.Window({
			reloadWindow: false,
			width: 600,
			height: 350,
			center: true,
			modal: true,
			id: 't3-plupload-window',
			layout: 'fit',
			title: String.format(top.TYPO3.LLL.fileUpload.windowTitle),
			shadow: false,
			hideBorders: true,
			closeAction: 'hide',
			items:[{
				xtype: 'pluploadpanel',
				id: 't3-plupload-panel',
				layout:'fit',
				url: top.TYPO3.configuration.PATH_typo3 + 'ajax.php',
				runtimes: 'html5,flash,gears,html4',
				multipart: true,
				multipart_params: { },
				file_data_name: 'upload_1',
				max_file_size: '1mb',
				flash_swf_url : top.TYPO3.configuration.PATH_typo3 + 'contrib/plupload/js/plupload.flash.swf',
				runtime_visible: false,
				addButtonText: top.TYPO3.LLL.fileUpload.buttonSelectFiles,
				uploadButtonText: top.TYPO3.LLL.fileUpload.buttonStartUpload,
				cancelButtonText: top.TYPO3.LLL.fileUpload.buttonCancelAll,
				progressText: top.TYPO3.LLL.fileUpload.progressText,
				emptyText: top.TYPO3.LLL.fileUpload.infoFileQueueEmpty,
				statusQueuedText: top.TYPO3.LLL.fileUpload.infoFileQueued,
				statusUploadingText: top.TYPO3.LLL.fileUpload.infoFileUploading,
				statusFailedText: top.TYPO3.LLL.fileUpload.errorUploadFailed,
				statusDoneText: top.TYPO3.LLL.fileUpload.infoFileFinished,
				listeners: {
					uploadfile: function(uploadpanel, uploader, file) {
						var parameters = {
							'file[upload][1][name]': file.name,
							'file[upload][1][target]': top.TYPO3.configuration.FileUpload.targetDirectory,
							'file[upload][1][data]': 1,
							'file[upload][1][charset]': 'utf-8',
							'ajaxID': 'TYPO3_tcefile::process',
							'uploaderType': 'plupload',
							"vC": top.TYPO3.configuration.veriCode
						};
						uploader.settings.max_file_size = top.TYPO3.configuration.FileUpload.maxFileSize+'b';
						uploader.settings.multipart_params = parameters;
					}
				}
			}],
			listeners:{
				hide: function(uploadwindow) {
					Ext.getCmp('t3-plupload-panel').onCancel();
					Ext.getCmp('t3-plupload-panel').onDeleteAll();
				},
				afterrender: function(uploadwindow) {
					Ext.getCmp('t3-plupload-panel').uploader.settings.max_file_size = top.TYPO3.configuration.FileUpload.maxFileSize+'b';
					Ext.getCmp('t3-plupload-panel').on('uploadcomplete', this.onUploadComplete);
				}
			},
			onUploadComplete: function(uploadPanel, success, failed) {
				if (failed.length == 0) {
					var windowComponent = Ext.getCmp('t3-plupload-window');
					windowComponent.hide();
					if (windowComponent.reloadWindow) {
						windowComponent.reloadWindow.location.reload();
					}
				}
			}
	});

});
