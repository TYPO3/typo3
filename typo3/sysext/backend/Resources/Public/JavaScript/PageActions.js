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
 * Module: TYPO3/CMS/Backend/PageActions
 * JavaScript implementations for page actions
 */
define(['jquery', 'TYPO3/CMS/Backend/Storage'], function($, Storage) {
	'use strict';

	/**
	 *
	 * @type {{settings: {pageId: number, language: {pageOverlayId: number}}, identifier: {pageTitle: string, hiddenElements: string}, elements: {$pageTitle: null, $showHiddenElementsCheckbox: null}, documentIsReady: boolean}}
	 * @exports TYPO3/CMS/Backend/PageActions
	 */
	var PageActions = {
		settings: {
			pageId: 0,
			language: {
				pageOverlayId: 0
			}
		},
		identifier: {
			pageTitle: '.t3js-title-inlineedit',
			hiddenElements: '.t3js-hidden-record'
		},
		elements: {
			$pageTitle: null,
			$showHiddenElementsCheckbox: null
		},
		documentIsReady: false
	};

	/**
	 * Initialize page title renaming
	 */
	PageActions.initializePageTitleRenaming = function() {
		if (!PageActions.documentIsReady) {
			$(function() {
				PageActions.initializePageTitleRenaming();
			});
			return;
		}
		if (PageActions.settings.pageId <= 0) {
			return;
		}

		var $editActionLink = $('<a class="hidden" href="#" data-action="edit"><span class="t3-icon fa fa-pencil"></span></a>');
		$editActionLink.on('click', function(e) {
			e.preventDefault();
			PageActions.editPageTitle();
		});
		PageActions.elements.$pageTitle
			.on('dblclick', PageActions.editPageTitle)
			.on('mouseover', function() { $editActionLink.removeClass('hidden'); })
			.on('mouseout', function() { $editActionLink.addClass('hidden'); })
			.append($editActionLink);
	};

	/**
	 * Initialize elements
	 */
	PageActions.initializeElements = function() {
		PageActions.elements.$pageTitle = $(PageActions.identifier.pageTitle + ':first');
		PageActions.elements.$showHiddenElementsCheckbox = $('#checkTt_content_showHidden');
	};

	/**
	 * Initialize events
	 */
	PageActions.initializeEvents = function() {
		PageActions.elements.$showHiddenElementsCheckbox.on('change', PageActions.toggleContentElementVisibility);
	};

	/**
	 * Toggles the "Show hidden content elements" checkbox
	 */
	PageActions.toggleContentElementVisibility = function() {
		var $me = $(this),
			$hiddenElements = $(PageActions.identifier.hiddenElements);

		// show a spinner to show activity
		var $spinner = $('<span />', {class: 'checkbox-spinner fa fa-circle-o-notch fa-spin'});
		$me.hide().after($spinner);

		if ($me.prop('checked')) {
			$hiddenElements.slideDown();
		} else {
			$hiddenElements.slideUp();
		}

		Storage.Persistent.set('moduleData.web_layout.tt_content_showHidden', $me.prop('checked') ? 1 : 0).done(function() {
			$spinner.remove();
			$me.show();
		});
	};

	/**
	 * Changes the h1 to an edit form
	 */
	PageActions.editPageTitle = function() {
		var $inputFieldWrap = $(
				'<form>' +
					'<div class="form-group">' +
						'<div class="input-group input-group-lg">' +
							'<input class="form-control">' +
							'<span class="input-group-btn">' +
								'<button class="btn btn-default" type="button" data-action="submit"><span class="t3-icon fa fa-floppy-o"></span></button> ' +
							'</span>' +
							'<span class="input-group-btn">' +
								'<button class="btn btn-default" type="button" data-action="cancel"><span class="t3-icon fa fa-times"></span></button> ' +
							'</span>' +
						'</div>' +
					'</div>' +
				'</form>'
			),
			$inputField = $inputFieldWrap.find('input');

		$inputFieldWrap.find('[data-action=cancel]').on('click', function() {
			$inputFieldWrap.replaceWith(PageActions.elements.$pageTitle);
			PageActions.initializePageTitleRenaming();
		});

		$inputFieldWrap.find('[data-action=submit]').on('click', function() {
			var newPageTitle = $.trim($inputField.val());
			if (newPageTitle !== '' && PageActions.elements.$pageTitle.text() !== newPageTitle) {
				PageActions.saveChanges($inputField);
			} else {
				$inputFieldWrap.find('[data-action=cancel]').trigger('click');
			}
		});

		// the form stuff is a wacky workaround to prevent the submission of the docheader form
		$inputField.parents('form').on('submit', function(e) {
			e.preventDefault();
			return false;
		});

		var $h1 = PageActions.elements.$pageTitle;
		$h1.children().last().remove();
		$h1.replaceWith($inputFieldWrap);
		$inputField.val($h1.text()).focus();

		$inputField.on('keyup', function(e) {
			switch (e.which) {
				case 13: // enter
					$inputFieldWrap.find('[data-action=submit]').trigger('click');
					break;
				case 27: // escape
					$inputFieldWrap.find('[data-action=cancel]').trigger('click');
					break;
			}
		});
	};

	/**
	 * Set the page id (used in the RequireJS callback)
	 *
	 * @param {Number} pageId
	 */
	PageActions.setPageId = function(pageId) {
		PageActions.settings.pageId = pageId;
	};

	/**
	 * Set the overlay id
	 *
	 * @param {Number} overlayId
	 */
	PageActions.setLanguageOverlayId = function(overlayId) {
		PageActions.settings.language.pageOverlayId = overlayId;
	};

	/**
	 * Save the changes and reload the page tree
	 *
	 * @param {Object} $field
	 */
	PageActions.saveChanges = function($field) {
		var $inputFieldWrap = $field.parents('form');
		$inputFieldWrap.find('button').addClass('disabled');
		$field.attr('disabled', 'disabled');

		var parameters = {},
			pagesTable,
			recordUid;

		if (PageActions.settings.language.pageOverlayId === 0) {
			pagesTable = 'pages';
			recordUid = PageActions.settings.pageId;
		} else {
			pagesTable = 'pages_language_overlay';
			recordUid = PageActions.settings.language.pageOverlayId;
		}

		parameters.data = {};
		parameters.data[pagesTable] = {};
		parameters.data[pagesTable][recordUid] = {title: $field.val()};

		require(['TYPO3/CMS/Backend/AjaxDataHandler'], function(DataHandler) {
			DataHandler.process(parameters).done(function() {
				$inputFieldWrap.find('[data-action=cancel]').trigger('click');
				PageActions.elements.$pageTitle.text($field.val());
				PageActions.initializePageTitleRenaming();
				top.TYPO3.Backend.NavigationContainer.PageTree.refreshTree();
			}).fail(function() {
				$inputFieldWrap.find('[data-action=cancel]').trigger('click');
			});
		});
	};

	$(function() {
		PageActions.initializeElements();
		PageActions.initializeEvents();
		PageActions.documentIsReady = true;
	});

	return PageActions;
});
