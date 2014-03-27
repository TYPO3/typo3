// IIFE for faster access to $ and save $ use
(function ($) {

	$(document).ready(function() {
		$('#terTable').dataTable({
			"bJQueryUI":true,
			"bLengthChange": false,
			'iDisplayLength': 15,
			"bStateSave": false,
			"bInfo": false,
			"bPaginate": false,
			"bFilter": false,
			"bSort": false,
			"fnDrawCallback": bindDownload,
			"fnCookieCallback": function (sNameFile, oData, sExpires, sPath) {
				// append mod.php to cookiePath to avoid sending cookie-data to images etc. without reason
				return sNameFile + "=" + encodeURIComponent($.fn.dataTableExt.oApi._fnJsonString(oData)) + "; expires=" + sExpires +"; path=" + sPath + "mod.php";
			}
		});

		$('#terVersionTable').dataTable({
			"bJQueryUI":true,
			"bLengthChange":false,
			'iDisplayLength':15,
			"bStateSave":false,
			"bInfo":false,
			"bPaginate":false,
			"bFilter":false,
			"fnDrawCallback":bindDownload,
			"fnCookieCallback": function (sNameFile, oData, sExpires, sPath) {
				// append mod.php to cookiePath to avoid sending cookie-data to images etc. without reason
				return sNameFile + "=" + encodeURIComponent($.fn.dataTableExt.oApi._fnJsonString(oData)) + "; expires=" + sExpires +"; path=" + sPath + "mod.php";
			},
			"aaSorting": [
				[2, 'asc']
			],
			'aoColumns': [
				{ 'bSortable': false },
				null,
				{ 'sType': 'version' },
				null,
				null,
				null
			]
		});

		$('#terSearchTable').dataTable({
			"bPaginate": false,
			"bJQueryUI":true,
			"bLengthChange": false,
			'iDisplayLength': 15,
			"bStateSave": false,
			"bFilter": false,
			"oLanguage": {
				"sSearch": "Filter results:"
			},
			"bSort": false,
			"fnDrawCallback": bindDownload,
			"fnCookieCallback": function (sNameFile, oData, sExpires, sPath) {
				// append mod.php to cookiePath to avoid sending cookie-data to images etc. without reason
				return sNameFile + "=" + encodeURIComponent($.fn.dataTableExt.oApi._fnJsonString(oData)) + "; expires=" + sExpires +"; path=" + sPath + "mod.php";
			}
		});

		bindDownload();
		bindSearchFieldResetter();
	});

	function bindDownload() {
		var installButtons = $('.downloadFromTer form.download input[type=submit]');
		installButtons.off('click');
		installButtons.on('click', function(event) {
			event.preventDefault();
			$('.typo3-extension-manager').mask();
			var url = $(event.currentTarget.form).attr('href');
			downloadPath = $(event.currentTarget.form).find('input.downloadPath:checked').val();
			$.ajax({
				url: url,
				dataType: 'json',
				success: getDependencies
			});
		});
	}

	function bindSkipSystemDependencyCheck() {
		$('.skipSystemDependencyCheck').not('.transformed').each(function() {
			$(this).data('href', $(this).attr('href'));
			$(this).attr('href', '#');
			$(this).addClass('transformed');
			$(this).click(function () {
				TYPO3.Dialog.QuestionDialog({
					title: TYPO3.l10n.localize('extensionList.skipSystemDependencyCheckConfirmation.title'),
					msg: TYPO3.l10n.localize('extensionList.skipSystemDependencyCheckConfirmation.message'),
					url: $(this).data('href'),
					fn: function (button, dummy, dialog) {
						if (button == 'no') {
							$('.typo3-extension-manager').mask();
							getResolveDependenciesAndInstallResult('yes', dummy, dialog);
						}
					}
				});
			})
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
				TYPO3.Flashmessage.display(TYPO3.Severity.error, data.title, data.message, 15);
				bindSkipSystemDependencyCheck();
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
					if (data.errorMessage.length) {
						TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadError.title'), data.errorMessage, 15);
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
						TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.l10n.localize('extensionList.dependenciesResolveFlashMessage.title').replace(/\{0\}/g, data.extension), successMessage, 15);
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
