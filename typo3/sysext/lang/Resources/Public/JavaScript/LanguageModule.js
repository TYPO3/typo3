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
 * Language module class
 *
 * @author Kai Vogel <k.vogel@reply.de>
 */
define('TYPO3/CMS/Lang/LanguageModule', ['jquery', 'datatables', 'TYPO3/CMS/Backend/jquery.clearable', 'moment'], function($) {
	var LanguageModule = {
		me: this,
		context: null,
		table: null,
		topMenu: null,
		currentRequest: null,
		settings: {},
		icons: {},
		labels: {},
		identifiers: {
			searchField: 'div.dataTables_filter input',
			topMenu: 'div.menuItems',
			activateIcon: 'span.activateIcon',
			deactivateIcon: 'span.deactivateIcon',
			downloadIcon: 'span.downloadIcon',
			loadingIcon: 'span.loadingIcon',
			completeIcon: 'span.completeIcon',
			progressBar: 'div.progressBar',
			progressBarText: 'div.progress-text',
			progressBarInner: 'div.progress-bar',
			lastUpdate: 'td.lastUpdate',
			languagePrefix: 'language-',
			extensionPrefix: 'extension-'
		},
		classes: {
			enabled: 'enabled',
			disabled: 'disabled',
			processing: 'processing',
			complete: 'complete',
			extension: 'extensionName',
			actions: 'actions',
			progressBar: 'progressBar',
			loading: 'loading',
			lastUpdate: 'lastUpdate'
		}
	};

	/**
	 * Initialize language table
	 */
	LanguageModule.initializeLanguageTable = function(contextElement, tableElement) {
		LanguageModule.context = $(contextElement);
		LanguageModule.topMenu = $(LanguageModule.identifiers.topMenu);
		LanguageModule.settings = LanguageModule.context.data();
		LanguageModule.icons = LanguageModule.buildIcons();
		LanguageModule.labels = LanguageModule.buildLabels();
		LanguageModule.table = LanguageModule.buildLanguageTable(tableElement);
		LanguageModule.initializeSearchField();
		LanguageModule.initializeEventHandler();
	}

	/**
	 * Initialize translation table
	 */
	LanguageModule.initializeTranslationTable = function(contextElement, tableElement) {
		LanguageModule.context = $(contextElement);
		LanguageModule.topMenu = $(LanguageModule.identifiers.topMenu);
		LanguageModule.settings = LanguageModule.context.data();
		LanguageModule.icons = LanguageModule.buildIcons();
		LanguageModule.labels = LanguageModule.buildLabels();
		LanguageModule.table = LanguageModule.buildTranslationTable(tableElement);
		LanguageModule.initializeSearchField();
		LanguageModule.initializeEventHandler();
	};

	/**
	 * Activate a language
	 */
	LanguageModule.activateLanguageAction = function(triggerElement, parameters) {
		var $row = $(triggerElement).closest('tr'),
			locale = $row.data('locale');

		if ($row.hasClass(LanguageModule.classes.processing)) {
			LanguageModule.abortAjaxRequest();
		}
		LanguageModule.executeAjaxRequest(LanguageModule.settings.activateLanguageUri, {locale: locale}, function(response, status) {
			if (status === 'success' && response.success) {
				$row.removeClass(LanguageModule.classes.disabled).addClass(LanguageModule.classes.enabled);
				LanguageModule.displaySuccess(LanguageModule.labels.languageActivated);
			} else {
				LanguageModule.displayError(LanguageModule.labels.errorOccurred);
			}
		});
	};

	/**
	 * Deactivate a language
	 */
	LanguageModule.deactivateLanguageAction = function(triggerElement, parameters) {
		var $row = $(triggerElement).closest('tr'),
			locale = $row.data('locale');

		if ($row.hasClass(LanguageModule.classes.processing)) {
			LanguageModule.abortAjaxRequest();
		}
		LanguageModule.executeAjaxRequest(LanguageModule.settings.deactivateLanguageUri, {locale: locale}, function(response, status) {
			if (status === 'success' && response.success) {
				$row.removeClass(LanguageModule.classes.enabled).removeClass(LanguageModule.classes.complete).addClass(LanguageModule.classes.disabled);
				LanguageModule.displaySuccess(LanguageModule.labels.languageDeactivated);
			} else {
				LanguageModule.displayError(LanguageModule.labels.errorOccurred);
			}
		});
	};

	/**
	 * Update a language
	 */
	LanguageModule.updateLanguageAction = function(triggerElement, parameters) {
		var $row = $(triggerElement).closest('tr'),
			locale = $row.data('locale'),
			$progressBar = $(LanguageModule.identifiers.progressBar, $row),
			$lastUpdate = $(LanguageModule.identifiers.lastUpdate, $row);

		$row.addClass(LanguageModule.classes.processing);
		LanguageModule.loadTranslationsByLocale(locale, function(status, data, response) {
			if (status === 'success') {
				LanguageModule.setProgress($progressBar, 100);
				LanguageModule.displaySuccess(LanguageModule.labels.updateComplete);
				$row.removeClass(LanguageModule.classes.processing).addClass(LanguageModule.classes.complete);
				$lastUpdate.html(LanguageModule.formatDate(response.timestamp));
			} else if (status === 'progress') {
				LanguageModule.setProgress($progressBar, parseFloat(response.progress));
			} else if (status === 'error') {
				LanguageModule.displayError(LanguageModule.labels.errorOccurred);
			}
		});
	};

	/**
	 * Update all active languages
	 */
	LanguageModule.updateActiveLanguagesAction = function(triggerElement, parameters) {
		var $activeRows = $('tr.' + LanguageModule.classes.enabled, LanguageModule.table.table().container());

		LanguageModule.topMenu.addClass(LanguageModule.classes.processing);
		$activeRows.addClass(LanguageModule.classes.processing);
		LanguageModule.loadTranslationsByRows($activeRows, function(row, status, data, response) {
			var $progressBar = $(LanguageModule.identifiers.progressBar, row),
				$lastUpdate = $(LanguageModule.identifiers.lastUpdate, row);

			if (status === 'success') {
				LanguageModule.setProgress($progressBar, 100);
				row.removeClass(LanguageModule.classes.processing).addClass(LanguageModule.classes.complete);
				$lastUpdate.html(LanguageModule.formatDate(response.timestamp));
			} else if (status === 'progress') {
				LanguageModule.setProgress($progressBar, parseFloat(response.progress));
			} else if (status === 'error') {
				LanguageModule.displayError(LanguageModule.labels.errorOccurred);
			} else if (status === 'finished') {
				LanguageModule.displaySuccess(LanguageModule.labels.updateComplete);
				LanguageModule.topMenu.removeClass(LanguageModule.classes.processing);
			}
		});
	};

	/**
	 * Cancel language update
	 */
	LanguageModule.cancelLanguageUpdateAction = function(triggerElement, parameters) {
		var $activeRows = $('tr.' + LanguageModule.classes.enabled, LanguageModule.table.table().container());

		LanguageModule.topMenu.removeClass(LanguageModule.classes.processing);
		$activeRows.removeClass(LanguageModule.classes.processing);
		LanguageModule.abortAjaxRequest();
	};

	/**
	 * Update an extension translation
	 */
	LanguageModule.updateTranslationAction = function(triggerElement, parameters) {
		var $row = $(triggerElement).closest('tr'),
			$cell = $(triggerElement).closest('td'),
			extension = $row.data('extension'),
			locale = LanguageModule.table.cell($cell).data().locale;

		$cell.addClass(LanguageModule.classes.processing);
		LanguageModule.loadTranslationByExtensionAndLocale(extension, locale, function(status, data, response) {
			if (status === 'success') {
				LanguageModule.displaySuccess(LanguageModule.labels.updateComplete);
				$cell.removeClass(LanguageModule.classes.processing).addClass(LanguageModule.classes.complete);
			} else if (status === 'error') {
				LanguageModule.displayError(LanguageModule.labels.errorOccurred);
			}
		});
	};

	/**
	 * Build icons
	 */
	LanguageModule.buildIcons = function() {
		return {
			activate: $(LanguageModule.identifiers.activateIcon, LanguageModule.context).html(),
			deactivate: $(LanguageModule.identifiers.deactivateIcon, LanguageModule.context).html(),
			download: $(LanguageModule.identifiers.downloadIcon, LanguageModule.context).html(),
			loading: $(LanguageModule.identifiers.loadingIcon, LanguageModule.context).html(),
			complete: $(LanguageModule.identifiers.completeIcon, LanguageModule.context).html(),
			progressBar: $(LanguageModule.identifiers.progressBar, LanguageModule.context).html()
		}
	};

	/**
	 * Build labels
	 */
	LanguageModule.buildLabels = function() {
		return {
			processing: TYPO3.lang['table.processing'],
			search: TYPO3.lang['table.search'],
			loadingRecords: TYPO3.lang['table.loadingRecords'],
			zeroRecords: TYPO3.lang['table.zeroRecords'],
			emptyTable: TYPO3.lang['table.emptyTable'],
			dateFormat: TYPO3.lang['table.dateFormat'],
			errorHeader: TYPO3.lang['flashmessage.error'],
			infoHeader: TYPO3.lang['flashmessage.information'],
			successHeader: TYPO3.lang['flashmessage.success'],
			languageActivated: TYPO3.lang['flashmessage.languageActivated'],
			errorOccurred: TYPO3.lang['flashmessage.errorOccurred'],
			languageDeactivated: TYPO3.lang['flashmessage.languageDeactivated'],
			updateComplete: TYPO3.lang['flashmessage.updateComplete']
		}
	};

	/**
	 * Build language table
	 */
	LanguageModule.buildLanguageTable = function(tableElement) {
		return $(tableElement).DataTable({
			serverSide: false,
			stateSave: true,
			paging: false,
			info: false,
			ordering: true,
			language: LanguageModule.labels,
			order: [[1, 'asc']]
		});
	};

	/**
	 * Initialize translation table
	 */
	LanguageModule.buildTranslationTable = function(tableElement) {
		var languageCount = $(tableElement).data('languageCount'),
			columns = [
				{
					render: function(data, type, row) {
						return LanguageModule.buildImage(data.icon, data.title, data.title, data.width, data.height);
					},
					width: '20px',
					orderable: false,
					targets: 0
				}, {
					render: function(data, type, row) {
						return data.title;
					},
					className: LanguageModule.classes.extension,
					targets: 1
				}
			];

		for (var i = 0; i < languageCount; i++) {
			columns.push({
				render: function(data, type, row) {
					var links = [
						LanguageModule.buildActionLink('updateTranslation', data, LanguageModule.icons.download),
						LanguageModule.buildLoadingIndicator(),
						LanguageModule.buildCompleteIndicator()
					];
					return links.join('');
				},
				className: 'dt-center',
				targets: (i + 2)
			});
		}

		return $(tableElement).DataTable({
			serverSide: false,
			stateSave: true,
			paging: false,
			info: false,
			ordering: true,
			language: LanguageModule.labels,
			ajax: LanguageModule.settings.listTranslationsUri,
			order: [[1, 'asc']],
			columnDefs: columns,
			createdRow: function (row, data, index) {
				var $row = $(row);
				$row.attr('id', LanguageModule.identifiers.extensionPrefix + data[1].key);
				$row.attr('data-extension', data[1].key);
			}
		});
	};

	/**
	 * Initialize search field
	 */
	LanguageModule.initializeSearchField = function() {
		$(LanguageModule.identifiers.searchField, LanguageModule.context).clearable({
			onClear: function() {
				if (LanguageModule.table !== null) {
					LanguageModule.table.search('').draw();
				}
			}
		});
	};

	/**
	 * Initialize event handler, redirect clicks to controller actions
	 */
	LanguageModule.initializeEventHandler = function() {
		$(document).on('click', function(event) {
			var $element = $(event.target);

			if ($element.data('action') !== undefined) {
				LanguageModule.handleActionEvent($element, event);
			} else if ($element.parent().data('action') !== undefined) {
				LanguageModule.handleActionEvent($element.parent(), event);
			} else if ($element.parent().parent().data('action') !== undefined) {
				LanguageModule.handleActionEvent($element.parent().parent(), event);
			}
		});
	};

	/**
	 * Handler for "action" events
	 */
	LanguageModule.handleActionEvent = function(element, event) {
		event.preventDefault();
		var data = element.data();
		var actionName = data.action + 'Action';
		if (actionName in LanguageModule) {
			LanguageModule[actionName](element, data);
		}
	};

	/**
	 * Load translations for all extensions by given locale
	 */
	LanguageModule.loadTranslationsByLocale = function(locale, callback, counter) {
		counter = counter || 0;
		var data = {locale: locale, count: counter};
		LanguageModule.executeAjaxRequest(LanguageModule.settings.updateLanguageUri, data, function(response, status) {
			if (status === 'success' && response.success) {
				if (parseFloat(response.progress) < 100) {
					callback('progress', data, response);
					counter++;
					LanguageModule.loadTranslationsByLocale(locale, callback, counter);
				} else {
					callback('success', data, response);
				}
			} else {
				callback('error', data, response);
			}
		});
	};

	/**
	 * Load translations for all extensions by given rows
	 */
	LanguageModule.loadTranslationsByRows = function(rows, callback) {
		if (rows) {
			rows = $(rows).toArray();
			var $row = $(rows.shift()),
				locale = $row.data('locale');

			LanguageModule.loadTranslationsByLocale(locale, function(status, data, response) {
				callback($row, status, data, response);
				if (status === 'success') {
					if (rows.length) {
						LanguageModule.loadTranslationsByRows(rows, callback);
					} else {
						callback($row, 'finished', data, response);
					}
				}
			});
		}
	};

	/**
	 * Load translation for one extension by given locale
	 */
	LanguageModule.loadTranslationByExtensionAndLocale = function(extension, locale, callback) {
		var data = {extension: extension, locale: locale};
		LanguageModule.executeAjaxRequest(LanguageModule.settings.updateTranslationUri, data, function(response, status) {
			if (status === 'success' && response.success) {
				callback('success', data, response);
			} else {
				callback('error', data, response);
			}
		});
	};

	/**
	 * Execute AJAX request
	 */
	LanguageModule.executeAjaxRequest = function(uri, data, callback) {
		var newData = {};
		newData[LanguageModule.settings.prefix] = {
			data: data
		};
		LanguageModule.currentRequest = $.ajax({
			type: 'POST',
			cache: false,
			url: uri,
			data: newData,
			dataType: 'json',
			success: function(response, status) {
				if (typeof callback === 'function') {
					callback(response, status, '');
				}
			},
			error: function(response, status, error) {
				if (typeof callback === 'function') {
					callback(response, status, error);
				}
			}
		});
	};

	/**
	 * Abort current AJAX request
	 */
	LanguageModule.abortAjaxRequest = function() {
		if (LanguageModule.currentRequest) {
			LanguageModule.currentRequest.abort();
		}
	};

	/**
	 * Display error flash message
	 */
	LanguageModule.displayError = function(label) {
		if (typeof label === 'string' && label !== '') {
			top.TYPO3.Notification.error(LanguageModule.labels.errorHeader, label);
		}
	};

	/**
	 * Display information flash message
	 */
	LanguageModule.displayInformation = function(label) {
		if (typeof label === 'string' && label !== '') {
			top.TYPO3.Notification.info(LanguageModule.labels.infoHeader, label);
		}
	};

	/**
	 * Display success flash message
	 */
	LanguageModule.displaySuccess = function(label) {
		if (typeof label === 'string' && label !== '') {
			top.TYPO3.Notification.success(LanguageModule.labels.successHeader, label);
		}
	};

	/**
	 * Build action link
	 */
	LanguageModule.buildActionLink = function(action, parameters, content) {
		var $link = $('<a>');

		$link.addClass(action + 'Link');
		$link.attr('data-action', action);
		for (var name in parameters) {
			$link.attr('data-' + name, parameters[name]);
		}
		$link.html(content);
		return $link.wrap('<span>').parent().html();
	};

	/**
	 * Build progress bar
	 */
	LanguageModule.buildProgressBar = function() {
		var $span = $('<span>');
		$span.addClass(LanguageModule.classes.progressBar);
		$span.html(LanguageModule.icons.progressBar);
		return $span.wrap('<span>').parent().html();
	};

	/**
	 * Build loading indicator
	 */
	LanguageModule.buildLoadingIndicator = function() {
		var $span = $('<span>');
		$span.addClass(LanguageModule.classes.loading);
		$span.html(LanguageModule.icons.loading);
		return $span.wrap('<span>').parent().html();
	};

	/**
	 * Build complete state indicator
	 */
	LanguageModule.buildCompleteIndicator = function() {
		var $span = $('<span>');
		$span.addClass(LanguageModule.classes.complete);
		$span.html(LanguageModule.icons.complete);
		return $span.wrap('<span>').parent().html();
	};

	/**
	 * Build image
	 */
	LanguageModule.buildImage = function(uri, alt, title, width, heigth) {
		var $image = $('<img>');
		$image.attr('src', uri);
		$image.attr('alt', alt ? alt : '');
		$image.attr('title', title ? title : '');
		$image.attr('style', 'width: ' + width + 'px; height: ' + heigth + 'px;');
		var $span = $('<span>');
		$span.addClass('typo3-app-icon');
		$span.attr('style', 'background: none; text-align: center;');
		$span.html($image);
		return $span.wrap('<span>').parent().html();
	};

	/**
	 * Format date
	 */
	LanguageModule.formatDate = function(timestamp) {
		return moment.unix(timestamp).format(LanguageModule.labels.dateFormat);
	};

	/**
	 * Set progress bar progress
	 */
	LanguageModule.setProgress = function(progressBar, progress) {
		var $inner = $(LanguageModule.identifiers.progressBarInner, progressBar),
			$text = $(LanguageModule.identifiers.progressBarText, progressBar);
		$inner.css({width: progress + '%'});
		$inner.attr('aria-valuenow', progress);
		$text.text(Math.round(progress) + '%');
	};

	return function() {
		$(document).ready(function() {
			if ($('div.typo3-module-lang #typo3-language-list').length) {
				LanguageModule.initializeLanguageTable('div.typo3-module-lang', '#typo3-language-list');
			} else if ($('div.typo3-module-lang #typo3-translation-list').length) {
				LanguageModule.initializeTranslationTable('div.typo3-module-lang', '#typo3-translation-list');
			}
		});

		TYPO3.LanguageModule = LanguageModule;
		return LanguageModule;
	}();
});
