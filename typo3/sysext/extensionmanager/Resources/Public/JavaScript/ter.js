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
	var installButtons = jQuery('.downloadFromTer form.download input[type=submit]');
	installButtons.off('click');
	installButtons.on('click', function(event) {
		event.preventDefault();
		var url = jQuery(event.currentTarget.form).attr('href');
		downloadPath = jQuery(event.currentTarget.form).find('input.downloadPath:checked').val();
		jQuery.ajax({
			url: url,
			dataType: 'json',
			success: getDependencies
		});
	});
}

function getDependencies(data) {
	if (data.hasDependencies) {
		TYPO3.Dialog.QuestionDialog({
			title: data.title,
			msg: data.message,
			url: data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + downloadPath,
			fn: getResolveDependenciesAndInstallResult
		});
	} else {
		if(data.hasErrors) {
			TYPO3.Flashmessage.display(TYPO3.Severity.error, data.title, data.message, 10);
		} else {
			var button = 'yes';
			var dialog = [];
			var dummy = '';
			dialog['url'] = data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + downloadPath;
			getResolveDependenciesAndInstallResult(button, dummy, dialog)
		}
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
					TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadError.title'), data.errorMessage, 5);
				} else {
					var successMessage = TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadSuccess.message').replace(/\{0\}/g, data.extension) + ' <br />';
					successMessage += '<br /><h3>' + TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadSuccess.header') + ':</h3>';
					jQuery.each(data.result, function(index, value) {
						successMessage += TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadSuccess.item') + ' ' + index + ':<br /><ul>';
						jQuery.each(value, function(extkey, extdata) {
							successMessage += '<li>' + extkey + '</li>';
						});
						successMessage += '</ul>';
					});
					TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.l10n.localize('extensionList.dependenciesResolveFlashMessage.title').replace(/\{0\}/g, data.extension), successMessage, 15);
				}
			}
		});
	} else {
		jQuery('#typo3-extension-manager').unmask();
	}
}