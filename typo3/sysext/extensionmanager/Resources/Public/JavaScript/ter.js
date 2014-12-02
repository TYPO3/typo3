// IIFE for faster access to $ and save $ use
(function ($) {

	$(document).ready(function() {
		$('#terTable').DataTable({
			'jQueryUI': true,
			'lengthChange': false,
			'pageLength': 15,
			'stateSave': false,
			'info': false,
			'paging': false,
			'searching': false,
			'ordering': false,
			'drawCallback': bindDownload
		});

		$('#terVersionTable').DataTable({
			'jQueryUI': true,
			'lengthChange': false,
			'pageLength': 15,
			'stateSave': false,
			'info': false,
			'paging': false,
			'searching': false,
			'drawCallback': bindDownload,
			'order': [
				[2, 'asc']
			],
			'columns': [
				{ 'orderable': false },
				null,
				{ 'type': 'version' },
				null,
				null,
				null
			]
		});

		$('#terSearchTable').DataTable({
			'paging': false,
			'jQueryUI': true,
			'lengthChange': false,
			'stateSave': false,
			'searching': false,
			'language': {
				'search': 'Filter results:'
			},
			'ordering': false,
			'drawCallback': bindDownload
		});

		bindDownload();
		bindSearchFieldResetter();
	});

	function bindDownload() {
		var installButtons = $('.downloadFromTer form.download button[type=submit]');
		installButtons.off('click');
		installButtons.on('click', function(event) {
			event.preventDefault();
			$('.typo3-extension-manager').mask();
			var url = $(event.currentTarget.form).attr('data-href');
			downloadPath = $(event.currentTarget.form).find('input.downloadPath:checked').val();
			$.ajax({
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
				$('.typo3-extension-manager').unmask();
				top.TYPO3.Flashmessage.display(top.TYPO3.Severity.error, data.title, data.message, 15);
			} else {
				var button = 'yes';
				var dialog = [];
				var dummy = '';
				dialog['url'] = data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + downloadPath;
				getResolveDependenciesAndInstallResult(button, dummy, dialog);
			}
		}
		return false;
	}

	function getResolveDependenciesAndInstallResult(button, dummy, dialog) {
		if (button == 'yes') {
			var newUrl = dialog.url;
			$.ajax({
				url: newUrl,
				dataType: 'json',
				success: function (data) {
					$('.typo3-extension-manager').unmask();
					if (data.errorCount > 0) {
						TYPO3.Dialog.QuestionDialog({
							title: data.errorTitle,
							msg: data.errorMessage,
							url: data.skipDependencyUri,
							fn: function (button, dummy, dialog) {
								if (button == 'yes') {
									$('.typo3-extension-manager').mask();
									getResolveDependenciesAndInstallResult('yes', dummy, dialog);
								}
							}
						});
					} else {
						var successMessage = TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadSuccess.message').replace(/\{0\}/g, data.extension) + ' <br />';
						successMessage += '<br /><h3>' + TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadSuccess.header') + ':</h3>';
						$.each(data.result, function(index, value) {
							successMessage += TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadSuccess.item') + ' ' + index + ':<br /><ul>';
							$.each(value, function(extkey, extdata) {
								successMessage += '<li>' + extkey + '</li>';
							});
							successMessage += '</ul>';
						});
						top.TYPO3.Flashmessage.display(top.TYPO3.Severity.info, TYPO3.l10n.localize('extensionList.dependenciesResolveFlashMessage.title').replace(/\{0\}/g, data.extension), successMessage, 15);
					}
				}
			});
		} else {
			$('.typo3-extension-manager').unmask();
		}
	}

	function bindSearchFieldResetter() {

		var $searchFields = $('.typo3-extensionmanager-searchTerForm input[type="text"]');
		var searchResultShown = '' !== $searchFields.first().val();

		$searchFields.clearable(
			{
				onClear: function() {
					if (searchResultShown) {
						$(this).parents('form').first().submit();
					}
				}
			}
		);
	}
}(jQuery));
