// IIFE for faster access to $ and save $ use
(function ($) {

	$(document).ready(function() {
		manageExtensionListing();
		$("#typo3-extension-configuration-forms ul").tabs("div.category");

		$('.onClickMaskExtensionManager').click(function() {
			$('.typo3-extension-manager').mask();
		});

		// IE 8 needs extra 'change' event on form
		if ($.browser.msie) {
			$('form.onClickMaskExtensionManager').bind("change", function () {
				$('.typo3-extension-manager').mask();
			});
		}

		$('.dataTables_wrapper .dataTables_filter input').clearable({
			onClear: function() {
				datatable.fnFilter('');
			}
		});
	});

	function getUrlVars() {
		var vars = [], hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		for(var i = 0; i < hashes.length; i++) {
			hash = hashes[i].split('=');
			vars.push(hash[0]);
			vars[hash[0]] = hash[1];
		}
		return vars;
	}

	function manageExtensionListing() {
		datatable = $('#typo3-extension-list').dataTable({
			"bPaginate": false,
			"bJQueryUI":true,
			"bLengthChange":false,
			'iDisplayLength':15,
			"bStateSave":true,
			"fnDrawCallback": bindActions,
			'aoColumns': [
				null,
				null,
				null,
				null,
				{ 'sType': 'version' },
				{ 'bSortable': false },
				null
			]
		});

		var getVars = getUrlVars();

		// restore filter
		if(datatable.length && getVars['search']) {
			datatable.fnFilter(getVars['search']);
		}
	}

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

	function bindActions() {
		$('.removeExtension').not('.transformed').each(function() {
			$(this).data('href', $(this).attr('href'));
			$(this).attr('href', '#');
			$(this).addClass('transformed');
			$(this).click(function() {
				if ($(this).hasClass('isLoadedWarning')) {
					TYPO3.Dialog.QuestionDialog({
						title: TYPO3.l10n.localize('extensionList.removalConfirmation.title'),
						msg: TYPO3.l10n.localize('extensionList.removalConfirmation.message'),
						url: $(this).data('href'),
						fn: function(button, dummy, dialog) {
							if (button == 'yes') {
								confirmDeletionAndDelete(dialog.url);
							}
						}
					});
				} else {
					confirmDeletionAndDelete($(this).data('href'));
				}
			});
		});

		$('.t3-icon-system-extension-update').parent().each(function() {
			$(this).data('href', $(this).attr('href'));
			$(this).attr('href', '#');
			$(this).addClass('transformed');
			$(this).click(function() {
				$('.typo3-extension-manager').mask();
				$.ajax({
					url: $(this).data('href'),
					dataType: 'json',
					success: updateExtension
				});
			});
		});

	}

	function updateExtension(data) {
		var message = '<h1>' + TYPO3.l10n.localize('extensionList.updateConfirmation.title') + '</h1>';
		message += '<h2>' + TYPO3.l10n.localize('extensionList.updateConfirmation.message') + '</h2>';
		$.each(data.updateComments, function(version, comment) {
			message += '<h3>' + version + '</h3>';
			message += '<div>' + comment + '</div>';
		});

		TYPO3.Dialog.QuestionDialog({
			title: TYPO3.l10n.localize('extensionList.updateConfirmation.questionVersionComments'),
			msg: message,
			width: 600,
			url: data.url,
			fn: function(button, dummy, dialog) {
				if (button == 'yes') {
					$.ajax({
						url: dialog.url,
						dataType: 'json',
						success: function(data) {
							$('.typo3-extension-manager').unmask();
							TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.l10n.localize('extensionList.updateFlashMessage.title'),
									TYPO3.l10n.localize('extensionList.updateFlashMessage.message').replace(/\{0\}/g, data.extension), 15);
						}
					});
				} else {
					$('.typo3-extension-manager').unmask();
				}
			}
		});
	}

	function confirmDeletionAndDelete(url) {
		TYPO3.Dialog.QuestionDialog({
			title: TYPO3.l10n.localize('extensionList.removalConfirmation.title'),
			msg: TYPO3.l10n.localize('extensionList.removalConfirmation.question'),
			url: url,
			fn: function(button, dummy, dialog) {
				if (button == 'yes') {
					$('.typo3-extension-manager').mask();
					$.ajax({
						url: dialog.url,
						dataType: 'json',
						success: removeExtension
					});
				}
			}
		});
	}

	function removeExtension(data) {
		$('.typo3-extension-manager').unmask();
		if (data.success) {
			datatable.fnDeleteRow(datatable.fnGetPosition(document.getElementById(data.extension)));
		} else {
			TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.l10n.localize('extensionList.removalConfirmation.title'), data.message, 15);
		}
	}
}(jQuery));
