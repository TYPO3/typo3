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
			"fnDrawCallback": bindDownload
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
			"fnDrawCallback": bindDownload
		});

		bindDownload();
		bindSearchFieldResetter();
	});

	$.fn.dataTableExt.oSort['version-asc'] = function(a, b) {
		var result = compare(a,b);
		result = result * -1;
		return result;
	};

	$.fn.dataTableExt.oSort['version-desc'] = function(a, b) {
		var result = compare(a,b);
		return result;
	};

	function compare(a, b) {
		if (a === b) {
			return 0;
		}

		var a_components = a.split(".");
		var b_components = b.split(".");

		var len = Math.min(a_components.length, b_components.length);

		// loop while the components are equal
		for (var i = 0; i < len; i++) {
			// A bigger than B
			if (parseInt(a_components[i]) > parseInt(b_components[i])) {
				return 1;
			}

			// B bigger than A
			if (parseInt(a_components[i]) < parseInt(b_components[i])) {
				return -1;
			}
		}

		// If one's a prefix of the other, the longer one is greater.
		if (a_components.length > b_components.length) {
			return 1;
		}

		if (a_components.length < b_components.length) {
			return -1;
		}
		// Otherwise they are the same.
		return 0;
	}

	function bindDownload() {
		var installButtons = $('.downloadFromTer form.download input[type=submit]');
		installButtons.off('click');
		installButtons.on('click', function(event) {
			event.preventDefault();
			var url = $(event.currentTarget.form).attr('href');
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
				TYPO3.Flashmessage.display(TYPO3.Severity.error, data.title, data.message, 10);
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
						TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.l10n.localize('extensionList.dependenciesResolveDownloadError.title'), data.errorMessage, 5);
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
		var $searchFieldWrapper = $('.typo3-extensionmanager-searchTerFieldWrapper');
		var $searchField = $searchFieldWrapper.find('input[type="text"]');
		var $resetter = $searchFieldWrapper.find('.t3-tceforms-input-clearer');

		$searchFieldWrapper.mouseover(function() {
			if ('' !== $searchField.val()) {
				$resetter.show();
			}
		});

		$searchFieldWrapper.mouseout(function() {
			$resetter.hide();
		});

		$resetter.click(function() {
			$searchField.val('');
			$searchField.focus()
		});
		$resetter.hide();
	}
}(jQuery));
