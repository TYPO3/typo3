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
				top.TYPO3.Modal.confirm(
					TYPO3.lang['extensionList.removalConfirmation.title'],
					TYPO3.lang['extensionList.removalConfirmation.question'],
					top.TYPO3.Severity.error,
					[
						{
							text: TYPO3.lang['button.cancel'],
							active: true,
							trigger: function() {
								top.TYPO3.Modal.dismiss();
							}
						}, {
							text: TYPO3.lang['button.remove'],
							btnClass: 'btn-danger',
							trigger: function() {
								ExtensionManager.removeExtensionFromDisk($me);
								top.TYPO3.Modal.dismiss();
							}
						}
					]
				);
			});
		});

		$('.t3-icon-system-extension-update').parent().each(function() {
			var $me = $(this);
			$me.data('href', $me.attr('href'));
			$me.attr('href', '#');
			$me.addClass('transformed');
			$me.click(function() {
				$.ajax({
					url: $(this).data('href'),
					dataType: 'json',
					beforeSend: function() {
						$(ExtensionManager.identifier.extensionManager).mask();
					},
					success: ExtensionManager.updateExtension
				});
			});
		});
	};

	ExtensionManager.removeExtensionFromDisk = function($extension) {
		var $extManager = $(Repository.identifier.extensionManager);
		$extManager.mask();
		$.ajax({
			url: $extension.data('href'),
			success: function() {
				location.reload();
			},
			error: function() {
				$extManager.unmask();
			}
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
		var message = '<h1>' + TYPO3.lang['extensionList.updateConfirmation.title'] + '</h1>';
		message += '<h2>' + TYPO3.lang['extensionList.updateConfirmation.message'] + '</h2>';
		$.each(data.updateComments, function(version, comment) {
			message += '<h3>' + version + '</h3>';
			message += '<div>' + comment + '</div>';
		});

		var $extManager = $(ExtensionManager.identifier.extensionManager);
		$extManager.unmask();

		top.TYPO3.Modal.confirm(
			TYPO3.lang['extensionList.updateConfirmation.questionVersionComments'],
			message,
			top.TYPO3.Severity.warning,
			[
				{
					text: TYPO3.lang['button.cancel'],
					active: true,
					trigger: function() {
						top.TYPO3.Modal.dismiss();
					}
				}, {
					text: TYPO3.lang['button.updateExtension'],
					btnClass: 'btn-warning',
					trigger: function() {
						$.ajax({
							url: data.url,
							dataType: 'json',
							beforeSend: function() {
								$extManager.mask();
							},
							complete: function() {
								location.reload();
							}
						});
						top.TYPO3.Modal.dismiss();
					}
				}
			]
		);
	};

	/**
	 * configuration properties
	 */
	ExtensionManager.configurationFieldSupport = function() {
		$('.t3js-emconf-offset').each(function() {
			var $me = $(this),
				$parent = $me.parent(),
				id = $me.attr('id'),
				val = $me.attr('value'),
				valArr = val.split(',');

			$me.attr('data-offsetfield-x', '#' + id + '_offset_x')
				.attr('data-offsetfield-y', '#' + id + '_offset_y')
				.wrap('<div class="hidden"></div>');

			var elementX = '' +
				'<div class="form-multigroup-item">' +
					'<div class="input-group">' +
						'<div class="input-group-addon">x</div>' +
						'<input id="' + id + '_offset_x" class="form-control t3js-emconf-offsetfield" data-target="#' + id + '" value="' + $.trim(valArr[0]) + '">' +
					'</div>' +
				'</div>';
			var elementY = '' +
				'<div class="form-multigroup-item">' +
					'<div class="input-group">' +
						'<div class="input-group-addon">y</div>' +
						'<input id="' + id + '_offset_y" class="form-control t3js-emconf-offsetfield" data-target="#' + id + '" value="' + $.trim(valArr[1]) + '">' +
					'</div>' +
				'</div>';

			var offsetGroup = '<div class="form-multigroup-wrap">' + elementX + elementY + '</div>';
			$parent.append(offsetGroup);
			$parent.find('.t3js-emconf-offset').keyup(function() {
				var $target = $($(this).data('target'));
				$target.attr(
					'value',
					$($target.data('offsetfield-x')).val() + ',' + $($target.data('offsetfield-y')).val()
				);
			});
		});

		$('.t3js-emconf-wrap').each(function() {
			var $me = $(this),
				$parent = $me.parent(),
				id = $me.attr('id'),
				val = $me.attr('value'),
				valArr = val.split('|');

			$me.attr('data-wrapfield-start', '#' + id + '_wrap_start')
				.attr('data-wrapfield-end', '#' + id + '_wrap_end')
				.wrap('<div class="hidden"></div>');

			var elementStart = '' +
				'<div class="form-multigroup-item">' +
					'<input id="' + id + '_wrap_start" class="form-control t3js-emconf-wrapfield" data-target="#' + id + '" value="' + $.trim(valArr[0]) + '">' +
				'</div>';
			var elementEnd = '' +
				'<div class="form-multigroup-item">' +
					'<input id="' + id + '_wrap_end" class="form-control t3js-emconf-wrapfield" data-target="#' + id + '" value="' + $.trim(valArr[1]) + '">' +
				'</div>';

			var wrapGroup = '<div class="form-multigroup-wrap">' + elementStart + elementEnd + '</div>';
			$parent.append(wrapGroup);
			$parent.find('.t3js-emconf-wrapfield').keyup(function() {
				var $target = $($(this).data('target'));
				$target.attr(
					'value',
					$($target.data('wrapfield-start')).val() + '|' + $($target.data('wrapfield-end')).val()
				);
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
			var url = $(event.currentTarget.form).attr('data-href');
			Repository.downloadPath = $(event.currentTarget.form).find('input.downloadPath:checked').val();
			$.ajax({
				url: url,
				dataType: 'json',
				beforeSend: function() {
					$(Repository.identifier.extensionManager).mask();
				},
				success: Repository.getDependencies
			});
		});
	};

	Repository.getDependencies = function(data) {
		var $extManager = $(Repository.identifier.extensionManager);
		$extManager.unmask();
		if (data.hasDependencies) {
			top.TYPO3.Modal.confirm(data.title, data.message, top.TYPO3.Severity.info, [
				{
					text: TYPO3.lang['button.cancel'],
					active: true,
					trigger: function() {
						top.TYPO3.Modal.dismiss();
					}
				}, {
					text: TYPO3.lang['button.resolveDependencies'],
					btnClass: 'btn-info',
					trigger: function() {
						Repository.getResolveDependenciesAndInstallResult(data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + Repository.downloadPath);
						top.TYPO3.Modal.dismiss();
					}
				}
			]);
		} else {
			if(data.hasErrors) {
				top.TYPO3.Flashmessage.display(top.TYPO3.Severity.error, data.title, data.message, 15);
			} else {
				Repository.getResolveDependenciesAndInstallResult(data.url + '&tx_extensionmanager_tools_extensionmanagerextensionmanager[downloadPath]=' + Repository.downloadPath);
			}
		}
		return false;
	};

	Repository.getResolveDependenciesAndInstallResult = function(url) {
		var $extManager = $(Repository.identifier.extensionManager);
		$.ajax({
			url: url,
			dataType: 'json',
			beforeSend: function() {
				$extManager.mask();
			},
			success: function (data) {
				$extManager.unmask();
				if (data.errorCount > 0) {
					top.TYPO3.Modal.confirm(data.errorTitle, data.errorMessage, top.TYPO3.Severity.warning, [
						{
							text: TYPO3.lang['button.cancel'],
							active: true,
							trigger: function() {
								top.TYPO3.Modal.dismiss();
							}
						}, {
							text: TYPO3.lang['button.resolveDependenciesIgnore'],
							btnClass: 'btn-warning',
							trigger: function() {
								Repository.getResolveDependenciesAndInstallResult(data.skipDependencyUri);
								top.TYPO3.Modal.dismiss();
							}
						}
					]);
				} else {
					var successMessage = TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.message'].replace(/\{0\}/g, data.extension) + ' <br />';
					successMessage += '<br /><h3>' + TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.header'] + ':</h3>';
					$.each(data.result, function(index, value) {
						successMessage += TYPO3.lang['extensionList.dependenciesResolveDownloadSuccess.item'] + ' ' + index + ':<br /><ul>';
						$.each(value, function(extkey, extdata) {
							successMessage += '<li>' + extkey + '</li>';
						});
						successMessage += '</ul>';
					});
					top.TYPO3.Flashmessage.display(top.TYPO3.Severity.info, TYPO3.lang['extensionList.dependenciesResolveFlashMessage.title'].replace(/\{0\}/g, data.extension), successMessage, 15);
					top.TYPO3.ModuleMenu.App.refreshMenu();
				}
			}
		});
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
			pagination: '.pagination-wrap',
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
					top.TYPO3.Flashmessage.display(top.TYPO3.Severity.warning, TYPO3.lang['extensionList.updateFromTerFlashMessage.title'], data.errorMessage, 10);
				}

				// Message with latest updates
				var $lastUpdate = $(ExtensionManager.Update.identifier.terUpdateAction + ' .time-since-last-update');
				$lastUpdate.text(data.timeSinceLastUpdate);
				$lastUpdate.attr(
					'title',
					TYPO3.lang['extensionList.updateFromTer.lastUpdate.timeOfLastUpdate'] + data.lastUpdateTime
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
					TYPO3.lang['extensionList.updateFromTerFlashMessage.title'],
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
