jQuery(document).ready(function() {
	manageExtensionListing();
	jQuery('th[title]').tooltip({offset: [-10, -30], position: 'bottom right', tipClass: 'headerTooltip'})
	jQuery('td[title]').tooltip({offset: [-10, -60], position: 'bottom right'});
	jQuery("#typo3-extension-configuration-forms ul").tabs("div.category");

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
	datatable = jQuery('#typo3-extension-list').dataTable({
		"sPaginationType":"full_numbers",
		"bJQueryUI":true,
		"bLengthChange":false,
		'iDisplayLength':15,
		"bStateSave":true,
		"fnDrawCallback": bindActions
	});

	var getVars = getUrlVars();

	// restore filter
	if(datatable.length && getVars['search']) {
		datatable.fnFilter(getVars['search']);
	}
}

function bindActions() {
	jQuery('td[title], tr[title]').tooltip({offset: [-10, -60], position: 'bottom right'});
	jQuery('.removeExtension').not('.transformed').each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).addClass('transformed');
		jQuery(this).click(function() {
			if (jQuery(this).hasClass('isLoadedWarning')) {
				TYPO3.Dialog.QuestionDialog({
					title: TYPO3.l10n.localize('extensionList.removalConfirmation.title'),
					msg: TYPO3.l10n.localize('extensionList.removalConfirmation.message'),
					url: jQuery(this).data('href'),
					fn: function(button, dummy, dialog) {
						if (button == 'yes') {
							confirmDeletionAndDelete(dialog.url)
						}
					}
				});
			} else {
				confirmDeletionAndDelete(jQuery(this).data('href'));
			}
		})
	});

	jQuery('.t3-icon-system-extension-update').parent().each(function() {
		jQuery(this).data('href', jQuery(this).attr('href'));
		jQuery(this).attr('href', 'javascript:void(0);');
		jQuery(this).addClass('transformed');
		jQuery(this).click(function() {
			jQuery('#typo3-extension-manager').mask();
			jQuery.ajax({
				url: jQuery(this).data('href'),
				dataType: 'json',
				success: updateExtension
			});
		});
	})
}

function updateExtension(data) {
	var message = '<h1>' + TYPO3.l10n.localize('extensionList.updateConfirmation.title') + '</h1>';
	message += '<h2>' + TYPO3.l10n.localize('extensionList.updateConfirmation.message') + '</h2>';
	jQuery.each(data.updateComments, function(version, comment) {
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
				jQuery.ajax({
					url: dialog.url,
					dataType: 'json',
					success: function(data) {
						jQuery('#typo3-extension-manager').unmask();
						TYPO3.Flashmessage.display(TYPO3.Severity.information, TYPO3.l10n.localize('extensionList.updateFlashMessage.title'),
								TYPO3.l10n.localize('extensionList.updateFlashMessage.message').replace(/\{0\}/g, data.extension), 15);
					}
				});
			} else {
				jQuery('#typo3-extension-manager').unmask();
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
				jQuery('#typo3-extension-manager').mask();
				jQuery.ajax({
					url: dialog.url,
					dataType: 'json',
					success: removeExtension
				});
			}
		}
	});
}

function removeExtension(data) {
	jQuery('#typo3-extension-manager').unmask();
	if (data.success) {
		datatable.fnDeleteRow(datatable.fnGetPosition(document.getElementById(data.extension)));
	} else {
		TYPO3.Flashmessage.display(TYPO3.Severity.error, TYPO3.l10n.localize('extensionList.removalConfirmation.title'), data.message, 15);
	}
}