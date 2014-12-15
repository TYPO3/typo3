/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * main logic holding everything together, consists of multiple parts
 * ExtensionManager => Various functions for displaying the extension list / sorting
 * Repository => Various AJAX functions for TER downloads
 * ExtensionManager.Update => Various AJAX functions to display updates
 * ExtensionManager.uploadForm => helper to show the upload form
 */
define(['jquery', 'datatables', 'jquery/jquery.clearable'], function($) {
	var ExtensionManager = {
		identifier: {
			extensionlist: '#typo3-extension-list',
			searchField: '#Tx_Extensionmanager_extensionkey',
			extensionManager: '.typo3-extension-manager'
		}
	};

	ExtensionManager.manageExtensionListing = function() {
		var $searchField = $(this.identifier.searchField),
			dataTable = $(this.identifier.extensionlist).DataTable({
				paging: false,
				jQueryUI: true,
				dom: 'lrtip',
				lengthChange: false,
				pageLength: 15,
				stateSave: true,
				drawCallback: this.bindExtensionListActions,
				columns: [
					null,
					null,
					null,
					null,
					{
						type: 'version'
					}, {
						orderable: false
					},
					null
				]
			});

		$searchField.parents('form').on('submit', function() {
			return false;
		});

		var getVars = ExtensionManager.getUrlVars();

		// restore filter
		var currentSearch = (getVars['search'] ? getVars['search'] : dataTable.search());
		$searchField.val(currentSearch);

		$searchField.on('input', function(e) {
			dataTable.search($(this).val()).draw();
		});

		return dataTable;
	};

	ExtensionManager.bindExtensionListActions = function() {
		$('.removeExtension').not('.transformed').each(function() {
			var $me = $(this);
			$me.data('href', $me.attr('href'));
			$me.attr('href', '#');
			$me.addClass('transformed');
			$me.click(function() {
				var $extManager = $(ExtensionManager.identifier.extensionManager);
				TYPO3.Dialog.QuestionDialog({
					title: TYPO3.l10n.localize('extensionList.removalConfirmation.title'),
					msg: TYPO3.l10n.localize('extensionList.removalConfirmation.question'),
					url: $me.data('href'),
					fn: function(button, dummy, dialog) {
						if (button == 'yes') {
							$extManager.mask();
							$.ajax({
								url: dialog.url,
								success: function() {
									location.reload();
								},
								error: function() {
									$extManager.unmask();
								}
							});
						}
					}
				});
			});
		});

		$('.t3-icon-system-extension-update').parent().each(function() {
			var $me = $(this);
			$me.data('href', $me.attr('href'));
			$me.attr('href', '#');
			$me.addClass('transformed');
			$me.click(function() {
				$(ExtensionManager.identifier.extensionManager).mask();
				$.ajax({
					url: $(this).data('href'),
					dataType: 'json',
					success: ExtensionManager.updateExtension
				});
			});
		});
	};

	ExtensionManager.getUrlVars = function() {
		var vars = [], hash;
		var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
		for (var i = 0; i < hashes.length; i++) {
			hash = hashes[i].split('=');
			vars.push(hash[0]);
			vars[hash[0]] = hash[1];
		}
		return vars;
	};

	$.fn.dataTableExt.oSort['version-asc'] = function(a, b) {
		var result = ExtensionManager.compare(a, b);
		return result * -1;
	};

	$.fn.dataTableExt.oSort['version-desc'] = function(a, b) {
		return ExtensionManager.compare(a, b);
	};

	ExtensionManager.compare = function(a, b) {
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

		// If one's a prefix of the other, the longer one is greaRepository.
		if (a_components.length > b_components.length) {
			return 1;
		}

		if (a_components.length < b_components.length) {
			return -1;
		}
		// Otherwise they are the same.
		return 0;
	};

	ExtensionManager.updateExtension = function(data) {
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
				var $extManager = $(ExtensionManager.identifier.extensionManager);
				if (button == 'yes') {
					$.ajax({
						url: dialog.url,
						dataType: 'json',
						success: function(data) {
							if (data.hasErrors) {
								top.TYPO3.Flashmessage.display(
									top.TYPO3.Severity.error,
									TYPO3.l10n.localize('downloadExtension.updateExtension.error'),
									data.errorMessage,
									15
								);
							} else {
								top.TYPO3.Flashmessage.display(
									top.TYPO3.Severity.info,
									TYPO3.l10n.localize('extensionList.updateFlashMessage.title'),
									TYPO3.l10n.localize('extensionList.updateFlashMessage.message').replace(/\{0\}/g, data.extension),
									15
								);
							}
							$extManager.unmask();
						},
						error: function(jqXHR, textStatus, errorThrown) {
							// Create an error message with diagnosis info.
							var errorMessage = textStatus + '(' + errorThrown + '): ' + jqXHR.responseText;

							top.TYPO3.Flashmessage.display(
								top.TYPO3.Severity.error,
								TYPO3.l10n.localize('downloadExtension.updateExtension.error'),
								errorMessage,
								15
							);
							$extManager.unmask();
						}
					});
				} else {
					$extManager.unmask();
				}
			}
		});
	};

	/**
	 * configuration properties
	 */
	ExtensionManager.configurationFieldSupport = function() {
		$('.offset').each(function() {
			var $me = $(this),
				$parent = $me.parent();
			$me.hide();

			var val = $me.attr('value');
			var valArr = val.split(',');

			$me.wrap('<div class="offsetSelector"></div>');
			$parent.append('x: <input value="' + $.trim(valArr[0]) + '" class="tempOffset1 tempOffset">');
			$parent.append('<span>, </span>');
			$parent.append('y: <input value="' + $.trim(valArr[1]) + '" class="tempOffset2 tempOffset">');

			$me.siblings('.tempOffset').keyup(function() {
				$me.siblings('.offset').attr(
					'value',
					$parent.children('.tempOffset1').attr('value') + ',' + $parent.children('.tempOffset2').attr('value'));
			});
		});

		$('.wrap').each(function() {
			var $me = $(this),
				$parent = $me.parent();
			$me.hide();

			var val = $me.attr('value');
			var valArr = val.split('|');

			$me.wrap('<div class="wrapSelector"></div>');
			$parent.append('<input value="' + $.trim(valArr[0]) + '" class="tempWrap1 tempWrap">');
			$parent.append('<span>|</span>');
			$parent.append('<input value="' + $.trim(valArr[1]) + '" class="tempWrap2 tempWrap">');

			$me.siblings('.tempWrap').keyup(function() {
				$me.siblings('.wrap').attr(
					'value',
					$parent.children('.tempWrap1').attr('value') + '|' + $parent.children('.tempWrap2').attr('value'));
			});
		});
	};

	var Repository = {
		downloadPath: '',
		identifier: {
			extensionManager: '.typo3-extension-manager'
		}
	};

	Repository.initDom = function() {
		$('#terTable').DataTable({
			jQueryUI: true,
			lengthChange: false,
			pageLength: 15,
			stateSave: false,
			info: false,
			paging: false,
			searching: false,
			ordering: false,
			drawCallback: Repository.bindDownload
		});

		$('#terVersionTable').DataTable({
			jQueryUI: true,
			lengthChange: false,
			pageLength: 15,
			stateSave: false,
			info: false,
			paging: false,
			searching: false,
			drawCallback: Repository.bindDownload,
			order: [
				[2, 'asc']
			],
			columns: [
				{orderable: false},
				null,
				{type: 'version'},
				null,
				null,
				null
			]
		});

		$('#terSearchTable').DataTable({
			paging: false,
			jQueryUI: true,
			lengthChange: false,
			stateSave: false,
			searching: false,
			language: {
				search: 'Filter results:'
			},
			ordering: false,
			drawCallback: Repository.bindDownload
		});

		Repository.bindDownload();
		Repository.bindSearchFieldResetter();
	};

	Repository.bindDownload = function() {
		var installButtons = $('.downloadFromTer form.download button[type=submit]');
		installButtons.off('click');
		installButtons.on('click', function(event) {
			event.preventDefault();
			$(Repository.identifier.extensionManager).mask();
			var url = $(event.currentTarget.form).attr('data-href');
			Repository.downloadPath = $(event.currentTarget.form).find('input.downloadPath:checked').val();
			$.ajax({
				url: url,
				dataType: 'json',
				success: Repository.getDependencies
			});
		});
	};

	Repository.getDependencies = function(data) {
		if (data.hasDependencies) {
			TYPO3.Dialog.QuestionDialog({
				title: data.title,
				msg: data.message,
				url: data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + Repository.downloadPath,
				fn: Repository.getResolveDependenciesAndInstallResult
			});
		} else {
			if(data.hasErrors) {
				$(Repository.identifier.extensionManager).unmask();
				top.TYPO3.Flashmessage.display(top.TYPO3.Severity.error, data.title, data.message, 15);
			} else {
				var button = 'yes';
				var dialog = [];
				var dummy = '';
				dialog['url'] = data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + Repository.downloadPath;
				Repository.getResolveDependenciesAndInstallResult(button, dummy, dialog);
			}
		}
		return false;
	};

	Repository.getResolveDependenciesAndInstallResult = function(button, dummy, dialog) {
		var $emViewport = $(Repository.identifier.extensionManager);
		if (button === 'yes') {
			var newUrl = dialog.url;
			$.ajax({
				url: newUrl,
				dataType: 'json',
				success: function (data) {
					$emViewport.unmask();
					if (data.errorCount > 0) {
						TYPO3.Dialog.QuestionDialog({
							title: data.errorTitle,
							msg: data.errorMessage,
							url: data.skipDependencyUri,
							fn: function (button, dummy, dialog) {
								if (button == 'yes') {
									$emViewport.mask();
									Repository.getResolveDependenciesAndInstallResult('yes', dummy, dialog);
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
			$emViewport.unmask();
		}
	};

	Repository.bindSearchFieldResetter = function() {
		var $searchFields = $('.typo3-extensionmanager-searchTerForm input[type="text"]');
		var searchResultShown = ('' !== $searchFields.first().val());

		$searchFields.clearable(
			{
				onClear: function() {
					if (searchResultShown) {
						$(this).parents('form').first().submit();
					}
				}
			}
		);
	};

	ExtensionManager.Update = {
		identifier: {
			terUpdateAction: '.update-from-ter',
			pagination: '#typo3-dblist-pagination',
			splashscreen: '.splash-receivedata',
			terTableWrapper: '#terTableWrapper',
			terTableDataTableWrapper: '#terTableWrapper .dataTables_wrapper'
		}
	};

	// Register "update from ter" action
	ExtensionManager.Update.initializeEvents = function() {
		$(ExtensionManager.Update.identifier.terUpdateAction).each(function() {

			// "this" is the form which updates the extension list from
			// TER on submit
			var $me = $(this),
				updateURL = $(this).attr('action');

			$me.attr('action', '#');
			$me.submit(function() {
				// Force update on click.
				ExtensionManager.Update.updateFromTer(updateURL, 1);

				// Prevent normal submit action.
				return false;
			});

			// This might give problems when there are more "update"-buttons,
			// each one would trigger a TER-ExtensionManager.Update.
			ExtensionManager.Update.updateFromTer(updateURL, 0);
		});
	};

	ExtensionManager.Update.updateFromTer = function(url, forceUpdate) {
		if (forceUpdate == 1) {
			url = url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5BforceUpdateCheck%5D=1';
		}

		// Hide triggers for TER update
		$(ExtensionManager.Update.identifier.terUpdateAction).addClass('is-hidden');

		// Show loaders
		$(ExtensionManager.Update.identifier.splashscreen).addClass('is-shown');
		$(ExtensionManager.Update.identifier.terTableDataTableWrapper).addClass('is-loading');
		$(ExtensionManager.Update.identifier.pagination).addClass('is-loading');

		$.ajax({
			url: url,
			dataType: 'json',
			cache: false,
			success: function(data) {
				// Something went wrong, show message
				if (data.errorMessage.length) {
					top.TYPO3.Flashmessage.display(top.TYPO3.Severity.warning, TYPO3.l10n.localize('extensionList.updateFromTerFlashMessage.title'), data.errorMessage, 10);
				}

				// Message with latest updates
				var $lastUpdate = $(ExtensionManager.Update.identifier.terUpdateAction + ' .time-since-last-update');
				$lastUpdate.text(data.timeSinceLastUpdate);
				$lastUpdate.attr(
					'title',
					TYPO3.l10n.localize('extensionList.updateFromTer.lastUpdate.timeOfLastUpdate') + data.lastUpdateTime
				);

				if (data.updated) {
					$.ajax({
						url: window.location.href + '&tx_extensionmanager_tools_extensionmanagerextensionmanager%5Bformat%5D=json',
						dataType: 'json',
						success: function(data) {
							$(ExtensionManager.Update.identifier.terTableWrapper).html(data);
							ExtensionManager.Update.transformPaginatorToAjax();
						}
					});
				}
			},
			error: function(jqXHR, textStatus, errorThrown) {
				// Create an error message with diagnosis info.
				var errorMessage = textStatus + '(' + errorThrown + '): ' + jqXHR.responseText;

				top.TYPO3.Flashmessage.display(
					top.TYPO3.Severity.warning,
					TYPO3.l10n.localize('extensionList.updateFromTerFlashMessage.title'),
					errorMessage,
					10
				);
			},
			complete: function() {
				// Hide loaders
				$(ExtensionManager.Update.identifier.splashscreen).removeClass('is-shown');
				$(ExtensionManager.Update.identifier.terTableDataTableWrapper).removeClass('is-loading');
				$(ExtensionManager.Update.identifier.pagination).removeClass('is-loading');

				// Show triggers for TER-update
				$(ExtensionManager.Update.identifier.terUpdateAction).removeClass('is-hidden');
			}
		});
	};

	ExtensionManager.Update.transformPaginatorToAjax = function () {
		$(ExtensionManager.Update.identifier.pagination + ' a').each(function() {
			var $me = $(this);
			$me.data('href', $(this).attr('href'));
			$me.attr('href', '#');
			$me.click(function() {
				var $terTableWrapper = $(ExtensionManager.Update.identifier.terTableWrapper);
				$terTableWrapper.mask();
				$.ajax({
					url: $(this).data('href'),
					dataType: 'json',
					success: function(data) {
						$terTableWrapper.html(data);
						$terTableWrapper.unmask();
						ExtensionManager.Update.transformPaginatorToAjax();
					}
				});
			});
		});
	};

	/**
	 * show the uploading form
	 */
	ExtensionManager.UploadForm = {
		expandedUploadFormClass: 'transformed'
	};

	ExtensionManager.UploadForm.initializeEvents = function() {
		// Show upload form
		$(document).on('click', '#upload-button-wrap > a', function(event) {
			var $me = $(this),
				$uploadForm = $('.uploadForm');

			event.preventDefault();
			if($me.hasClass(ExtensionManager.UploadForm.expandedUploadFormClass)) {
				$uploadForm.stop().slideUp();
				$me.removeClass(ExtensionManager.UploadForm.expandedUploadFormClass);
			} else {
				$me.addClass(ExtensionManager.UploadForm.expandedUploadFormClass);
				$uploadForm.stop().slideDown();

				$.ajax({
					url: $me.attr('href'),
					dataType: 'html',
					success: function (data) {
						$uploadForm.html(data);
					}
				});
			}
		});
	};

	return function() {
		$(document).ready(function() {
			var dataTable = ExtensionManager.manageExtensionListing();

			$('#typo3-extension-configuration-forms .tabs').tabs();

			$(document).on('click', '.onClickMaskExtensionManager', function() {
				$(ExtensionManager.identifier.extensionManager).mask();
			});

			$(ExtensionManager.identifier.searchField).clearable({
				onClear: function() {
					dataTable.search('').draw();
				}
			});

			$('.expandable').expander({
				expandEffect: 'slideDown',
				collapseEffect: 'slideUp',
				beforeExpand: function() {
					$(this).parent().css('z-index', 199);
				},
				afterCollapse: function() {
					$(this).parent().css('z-index', 1);
				}
			});

			$(document).on('click', '.t3-button-action-installdistribution', function() {
				$(ExtensionManager.identifier.extensionManager).mask();
			});

			ExtensionManager.configurationFieldSupport();
			var $validate = $('.validate');
			$validate.validate();
			$(document).on('click', '.t3-icon-document-save-close', function() {
				$validate.append($('<input />', {type: 'hidden', name: 'tx_extensionmanager_tools_extensionmanagerextensionmanager[action]', value: 'saveAndClose'})).submit();
			});

			// initialize the repository
			Repository.initDom();

			ExtensionManager.Update.initializeEvents();
			ExtensionManager.UploadForm.initializeEvents();
		});

		if (typeof TYPO3.ExtensionManager === 'undefined') {
			TYPO3.ExtensionManager = ExtensionManager;
		}
	}();
});
