Ext.ns('TYPO3.Components', 'TYPO3.Components.filelist');

Ext.onReady(function() {
	var plp = new Ext.ux.PluploadPanel({
		region: 'center',
		split: true,
		width: 500,
		height: 350,
		layout:'fit',
		unstyled:true,
		url: TYPO3.configuration.PATH_typo3 + 'ajax.php',
		runtimes: 'html5,flash,silverlight,gears,html4',
		multipart: false,
		multipart_params: { },
		file_data_name: 'upload_1',

		max_file_size: '100mb',

		/* image resizing */
		/*resize: {width : 640, height : 480, quality : 60},*/

		flash_swf_url : TYPO3.configuration.PATH_typo3 + 'sysext/fal/contrib/plupload/js/plupload.flash.swf',
		silverlight_xap_url : TYPO3.configuration.PATH_typo3 + 'sysext/fal/contrib/plupload/js/plupload.silverlight.xap',


		runtime_visible: true,

		addButtonCls: 'silk-add',
		uploadButtonCls: 'silk-arrow-up',
		cancelButtonCls: 'silk-stop',
		deleteButtonCls: 'silk-cross',

		addButtonText: 'Add files',
		uploadButtonText: 'Upload',
		cancelButtonText: 'Cancel upload',
		deleteButtonText: 'Remove',
		deleteSelectedText: '<b>Remove selected</b>',
		deleteUploadedText: 'Remove uploaded',
		deleteAllText: 'Remove ALL',

		statusQueuedText: 'Queued',
		statusUploadingText: 'Uploading ({0}%)',
		statusFailedText: '<span style="color: red">FAILED</span>',
		statusDoneText: '<span style="color: green">DONE</span>',

		statusInvalidSizeText: 'Too big',
		statusInvalidExtensionText: 'Invalid file type',

		emptyText: '<div class="plupload_emptytext"><span>Upload queue is empty</span></div>',
		emptyDropText: '<div class="plupload_emptytext"><span>Drop files here</span></div>',

		progressText: '{0}/{1} ({3} failed) ({5}/s)',

		listeners: {
			beforestart: function(uploadpanel) {
				// As TYPO3_tcefile::process expects this parameter dependent on the file (because of the parameter name)
				// we have to update the upload url for every single file (and not in "beforestart" as in the original file
				var parameters = {
					"file": "upload_1",
					"file[upload][1][name]": uploadpanel.uploader.files[0].name,
					"file[upload][1][target]": top.TYPO3.configuration.FileUpload.targetDirectory,
					"file[upload][1][data]": 1,
					"file[upload][1][charset]": "utf-8",
					"ajaxID": "PLUPLOAD::process",
					"vC": top.TYPO3.configuration.veriCode
				};

				uploadpanel.uploader.settings.url = this.url + '?' + Ext.urlEncode(parameters);
			},
			uploadfile: function(uploadpanel, uploader, file) {

				// As TYPO3_tcefile::process expects this parameter dependent on the file (because of the parameter name)
				// we have to update the upload url for every single file (and not in "beforestart" as in the original file
				var parameters = {
					"file": "upload_1",
					"file[upload][1][name]": file.name,
					"file[upload][1][target]": top.TYPO3.configuration.FileUpload.targetDirectory,
					"file[upload][1][data]": 1,
					"file[upload][1][charset]": "utf-8",
					"ajaxID": "PLUPLOAD::process",
					"vC": top.TYPO3.configuration.veriCode
				};
				uploader.settings.url = this.url + '?' + Ext.urlEncode(parameters);
			},
			uploadstarted: function(uploadpanel) {

			},
			uploadcomplete: function(uploadpanel, success, failures) {
			}
		}
	});

	var tree = {region:'west', hidden:true};
	if (TYPO3.Components.filelist.FolderTree) {
		tree = new TYPO3.Components.filelist.FolderTree({
			id: 't3-upload-window-tree',
			region: 'west',
			layout: 'fit',
			width: 180,
			minWidth: 20,
			floatable: true,
			animCollapse: false,
			split: true,
			collapsible: true,
			collapseMode: 'mini',
			hideCollapseTool: true,
			stateful:true,
			autoScroll: true,
			border: false,
			hidden:true,
			listeners:{
				click: {
					fn: function() {

					}
				}
			}
		});
	}

	TYPO3.FileUploadWindow = new Ext.Window({
		width: 600,
		height: 350,
		center: true,
		modal: true,
		id: 't3-upload-window',
		layout:'border',
		title: String.format(TYPO3.LLL.fileUpload.windowTitle),
		shadow: false,
		hideBorders: true,
		closeAction:'hide',
		maximizable:true,
		items:[tree,plp],
		listeners:{
			hide:{
				fn: function() {
					plp.onDeleteAll();
				}
			}
		},
		bbar:['->',{
			text:'OK',
			handler: function() {
				TYPO3.FileUploadWindow.hide();
			}
		}],
		showFilelistUploadDialog: function(target) {
			top.TYPO3.configuration.FileUpload = {targetDirectory:target};
			var tree = Ext.getCmp('t3-upload-window-tree');
			if (tree) {
				tree.hide();
			}
			TYPO3.FileUploadWindow.show();
			return false;
		},
		showFalTceFormsUploadDialog: function(field, allowed) {
			if (!top.TYPO3.configuration.FileUpload) {
				top.TYPO3.configuration.FileUpload = {targetDirectory:'fileadmin/_temp_/'};
			}
			var tree = Ext.getCmp('t3-upload-window-tree');
			if (tree) {
				tree.show();
			}
			TYPO3.FileUploadWindow.show();
			return false;
		}
	});
});
Ext.onReady(function() {
	Ext.QuickTips.init();
	Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
});
