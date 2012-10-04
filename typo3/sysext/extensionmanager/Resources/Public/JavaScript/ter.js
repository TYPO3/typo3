jQuery(document).ready(function() {
	jQuery('#terTable').dataTable({
		"bJQueryUI":true,
		"bLengthChange": false,
		'iDisplayLength': 15,
		"bStateSave": false,
		"bInfo": false,
		"bPaginate": false,
		"bFilter": false,
		"fnDrawCallback": bindDownload
	});

	jQuery('#terVersionTable').dataTable({
		"bJQueryUI":true,
		"bLengthChange":false,
		'iDisplayLength':15,
		"bStateSave":false,
		"bInfo":false,
		"bPaginate":false,
		"bFilter":false,
		"aaSorting":[[0, 'desc']],
		"fnDrawCallback":bindDownload
	});

	jQuery('#terSearchTable').dataTable({
		"sPaginationType":"full_numbers",
		"bJQueryUI":true,
		"bLengthChange": false,
		'iDisplayLength': 15,
		"bStateSave": false,
		"oLanguage": {
			"sSearch": "Filter results:"
		},
		"aaSorting": [],
		"fnDrawCallback": bindDownload
	});
	bindDownload();
});

function bindDownload() {
	jQuery('.downloadFromTer form').each(function() {
		jQuery(this).submit(function(){
			var url = jQuery(this).attr('href');
				// do this because else form gets send twice - why?
			jQuery(this).attr('href', 'javascript:void();');
			downloadPath = jQuery(this).find('input.downloadPath:checked').val();
			jQuery.ajax({
				url: url,
				dataType: 'json',
				success: getDependencies
			});
			return false;
		});
	});
}

function getDependencies(data) {
	if (data.dependencies.length) {
		TYPO3.Dialog.QuestionDialog({
			title: 'Dependencies',
			msg: data.message,
			url: data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + downloadPath,
			fn: getResolveDependenciesAndInstallResult
		});
	} else {
		var button = 'yes';
		var dialog = new Array();
		var dummy = '';
		dialog['url'] = data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + downloadPath;
		getResolveDependenciesAndInstallResult(button, dummy, dialog)
	}
	return false;
}

function getResolveDependenciesAndInstallResult(button, dummy, dialog) {
	if (button == 'yes') {
		var newUrl = dialog.url;
		jQuery.ajax({
			url: newUrl,
			dataType: 'json',
			success: function (data) {
				jQuery('#typo3-extension-manager').unmask();
				if (data.errorMessage.length) {
					TYPO3.Flashmessage.display(TYPO3.Severity.error, 'Download Error', data.errorMessage, 5);
				} else {
					var successMessage = 'Your installation of ' + data.extension + ' was successfull. <br />';
					successMessage += '<br /><h3>Log:</h3>';
					jQuery.each(data.result, function(index, value) {
						successMessage += 'Extensions ' + index + ':<br /><ul>';
						jQuery.each(value, function(extkey, extdata) {
							successMessage += '<li>' + extkey + '</li>';
						});
						successMessage += '</ul>';
					});
					TYPO3.Flashmessage.display(TYPO3.Severity.information, data.extension + ' installed.', successMessage, 15);
				}
			}
		});
	} else {
		jQuery('#typo3-extension-manager').unmask();
	}
}