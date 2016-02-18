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
 * Module: TYPO3/CMS/Backend/Localization
 * UI for localization workflow.
 */
define([
	'jquery',
	'TYPO3/CMS/Backend/AjaxDataHandler',
	'TYPO3/CMS/Backend/Modal',
	'TYPO3/CMS/Backend/Icons',
	'TYPO3/CMS/Backend/Severity',
	'bootstrap'
], function($, DataHandler, Modal, Icons, Severity) {
	'use strict';

	/**
	 * @type {{identifier: {triggerButton: string}, actions: {translate: $, copy: $}, settings: {}, records: []}}
	 * @exports TYPO3/CMS/Backend/Localization
	 */
	var Localization = {
		identifier: {
			triggerButton: '.t3js-localize'
		},
		actions: {
			translate: $('<label />', {
				class: 'btn btn-block btn-default t3js-option',
				'data-helptext': '.t3js-helptext-translate'
			}).html('<br>Translate').prepend(
				$('<input />', {
					type: 'radio',
					name: 'mode',
					id: 'mode_translate',
					value: 'localize',
					style: 'display: none'
				})
			),
			copy: $('<label />', {
				class: 'btn btn-block btn-default t3js-option',
				'data-helptext': '.t3js-helptext-copy'
			}).html('<br>Copy').prepend(
				$('<input />', {
					type: 'radio',
					name: 'mode',
					id: 'mode_copy',
					value: 'copyFromLanguage',
					style: 'display: none'
				})
			)
		},
		settings: {},
		records: []
	};

	Localization.initialize = function() {
		Icons.getIcon('actions-localize', Icons.sizes.large).done(function(localizeIconMarkup) {
			Icons.getIcon('actions-edit-copy', Icons.sizes.large).done(function(copyIconMarkup) {
				Localization.actions.translate.prepend(localizeIconMarkup);
				Localization.actions.copy.prepend(copyIconMarkup);
				$(Localization.identifier.triggerButton).prop('disabled', false);
			});
		});

		$(document).on('click', Localization.identifier.triggerButton, function() {
			var $triggerButton = $(this),
				actions = [],
				modalContent =
					'<div id="localization-carousel" class="carousel slide" data-ride="carousel" data-interval="false">'
						+ '<div class="carousel-inner" role="listbox">'
							+ '<div class="item active">'
								+ '<div data-toggle="buttons">';

			if ($triggerButton.data('allowTranslate')) {
				actions.push(
					'<div class="row">'
						+ '<div class="btn-group col-sm-3">' + Localization.actions.translate[0].outerHTML + '</div>'
						+ '<div class="col-sm-9">'
							+ '<p class="t3js-helptext t3js-helptext-translate text-muted">' + TYPO3.lang['localize.educate.translate'] + '</p>'
						+ '</div>'
					+ '</div>'
				);
			}

			if ($triggerButton.data('allowCopy')) {
				actions.push(
					'<div class="row">'
						+ '<div class="col-sm-3 btn-group">' + Localization.actions.copy[0].outerHTML + '</div>'
						+ '<div class="col-sm-9">'
							+ '<p class="t3js-helptext t3js-helptext-copy text-muted">' + TYPO3.lang['localize.educate.copy'] + '</p>'
						+ '</div>'
					+ '</div>'
				);
			}

			modalContent += actions.join('<hr>');

			modalContent += 	'</div>'
							+ '</div>'
							+ '<div class="item">'
								+ '<h4>' + TYPO3.lang['localize.view.chooseLanguage'] + '</h4>'
								+ '<div class="t3js-available-languages">'
								+ '</div>'
							+ '</div>'
							+ '<div class="item">'
								+ '<h4>' + TYPO3.lang['localize.view.summary'] + '</h4>'
								+ '<div class="t3js-summary">'
								+ '</div>'
							+ '</div>'
							+ '<div class="item">'
								+ '<h4>' + TYPO3.lang['localize.view.processing'] + '</h4>'
								+ '<div class="t3js-processing">'
								+ '</div>'
							+ '</div>'
						+ '</div>'
					+ '</div>';

			var $modal = Modal.confirm(
				TYPO3.lang['localize.wizard.header'].replace('{0}', $triggerButton.data('colposName')).replace('{1}', $triggerButton.data('languageName')),
				modalContent,
				Severity.info, [
					{
						text: TYPO3.lang['localize.wizard.button.cancel'] || 'Cancel',
						active: true,
						btnClass: 'btn-default',
						name: 'cancel',
						trigger: function() {
							Modal.currentModal.trigger('modal-dismiss');
						}
					}, {
						text: TYPO3.lang['localize.wizard.button.next'] || 'Next',
						btnClass: 'btn-info',
						name: 'next'
					}
				], [
					'localization-wizard'
				]
			);

			var $carousel = $modal.find('#localization-carousel'),
				slideCount = Math.max(1, $modal.find('#localization-carousel .item').length),
				initialStep = Math.round(100 / slideCount),
				$modalFooter = $modal.find('.modal-footer'),
				$nextButton = $modalFooter.find('button[name="next"]');

			$carousel.data('slideCount', slideCount);
			$carousel.data('currentSlide', 1);

			// Append progress bar to modal footer
			$modalFooter.prepend(
				$('<div />', {class: 'progress'}).append(
					$('<div />', {
						role: 'progressbar',
						class: 'progress-bar',
						'aria-valuemin': 0,
						'aria-valuenow': initialStep,
						'aria-valuemax': 100
					}).width(initialStep + '%').text(TYPO3.lang['localize.progress.step'].replace('{0}', '1').replace('{1}', slideCount))
				)
			);

			// Disable "next" button on initialization and bind "click" event
			$nextButton.prop('disabled', true).on('click', function() {
				Localization.synchronizeSlidesHeight($carousel);
				$carousel.carousel('next');
			});

			// Register "click" event on options
			$modal.on('click', '.t3js-option', function() {
				var $me = $(this),
					$radio = $me.find('input[type="radio"]');

				if ($me.data('helptext')) {
					$modal.find('.t3js-helptext').addClass('text-muted');
					$modal.find($me.data('helptext')).removeClass('text-muted');
				}
				if ($radio.length > 0) {
					Localization.settings[$radio.attr('name')] = $radio.val();
				}
				$nextButton.prop('disabled', false);
			});

			$carousel.on('slide.bs.carousel', function(e) {
				var nextSlideNumber = $carousel.data('currentSlide') + 1,
					$modalFooter = $carousel.parent().next();

				$carousel.data('currentSlide', nextSlideNumber);
				$modalFooter.find('.progress-bar')
					.width(initialStep * nextSlideNumber + '%')
					.text(TYPO3.lang['localize.progress.step'].replace('{0}', nextSlideNumber).replace('{1}', slideCount));

				// Disable next button again
				$nextButton.prop('disabled', true);
			}).on('slid.bs.carousel', function(e) {
				var $activeSlide = $(e.relatedTarget),
					$modalFooter = $carousel.parent().next(),
					$languageView = $activeSlide.find('.t3js-available-languages'),
					$summaryView = $activeSlide.find('.t3js-summary'),
					$processingView = $activeSlide.find('.t3js-processing');

				if ($languageView.length > 0) {
					// Prepare language view
					Icons.getIcon('spinner-circle-dark', Icons.sizes.large).done(function(markup) {
						$languageView.html(
							$('<div />', {class: 'text-center'}).append(markup)
						);
						Localization.loadAvailableLanguages(
							$triggerButton.data('pageId'),
							$triggerButton.data('colposId'),
							$triggerButton.data('languageId')
						).done(function(result) {
							if (result.length === 1) {
								// We only have one result, auto select the record and continue
								Localization.settings.language = result[0].uid + ''; // we need a string
								$carousel.carousel('next');
								return;
							}

							var $languageButtons = $('<div />', {class: 'row', 'data-toggle': 'buttons'});

							$.each(result, function(_, languageObject) {
								$languageButtons.append(
									$('<div />', {class: 'col-sm-4'}).append(
										$('<label />', {class: 'btn btn-default btn-block t3js-option option'}).text(' ' + languageObject.title).prepend(
											languageObject.flagIcon
										).prepend(
											$('<input />', {
												type: 'radio',
												name: 'language',
												id: 'language' + languageObject.uid,
												value: languageObject.uid,
												style: 'display: none;'
											})
										)
									)
								);
							});
							$languageView.html($languageButtons);
						});
					});
				} else if ($summaryView.length > 0) {
					Icons.getIcon('spinner-circle-dark', Icons.sizes.large).done(function(markup) {
						$summaryView.html(
							$('<div />', {class: 'text-center'}).append(markup)
						);

						Localization.getSummary(
							$triggerButton.data('pageId'),
							$triggerButton.data('colposId'),
							$triggerButton.data('languageId')
						).done(function(result) {
							var $summary = $('<div />', {class: 'row'});
							Localization.records = [];

							$.each(result, function(_, record) {
								Localization.records.push(record.uid);
								$summary.append(
									$('<div />', {class: 'col-sm-6'}).text(' (' + record.uid + ') ' + record.title).prepend(record.icon)
								);
							});
							$summaryView.html($summary);

							// Unlock button as we don't have an option
							$nextButton.prop('disabled', false);
							$nextButton.text(TYPO3.lang['localize.wizard.button.process'])
						});
					});
				} else if ($processingView.length > 0) {
					// Point of no return - hide modal footer disable any closing ability
					$modal.find('.modal-header .close').remove();
					$modalFooter.slideUp();

					Icons.getIcon('spinner-circle-dark', Icons.sizes.large).done(function(markup) {
						$processingView.html(
							$('<div />', {class: 'text-center'}).append(markup)
						);

						Localization.localizeRecords(
							$triggerButton.data('pageId'),
							$triggerButton.data('languageId'),
							Localization.records
						).done(function() {
							Modal.dismiss();
							document.location.reload();
						});
					});
				}
			});
		});

		/**
		 * Synchronize height of slides
		 *
		 * @param {$} $carousel
		 */
		Localization.synchronizeSlidesHeight = function($carousel) {
			var $slides = $carousel.find('.item'),
				maxHeight = 0;

			$slides.each(function(_, slide) {
				var height = $(slide).height();
				if (height > maxHeight) {
					maxHeight = height;
				}
			});
			$slides.height(maxHeight);
		};

		/**
		 * Load available languages from page and colPos
		 *
		 * @param {Integer} pageId
		 * @param {Integer} colPos
		 * @param {Integer} languageId
		 * @return {Promise}
		 */
		Localization.loadAvailableLanguages = function(pageId, colPos, languageId) {
			return $.ajax({
				url: TYPO3.settings.ajaxUrls['languages_page_colpos'],
				data: {
					pageId: pageId,
					colPos: colPos,
					languageId: languageId
				}
			});
		};

		/**
		 * Get summary for record processing
		 *
		 * @param {Integer} pageId
		 * @param {Integer} colPos
		 * @param {Integer} languageId
		 * @return {Promise}
		 */
		Localization.getSummary = function(pageId, colPos, languageId) {
			return $.ajax({
				url: TYPO3.settings.ajaxUrls['records_localize_summary'],
				data: {
					pageId: pageId,
					colPos: colPos,
					destLanguageId: languageId,
					languageId: Localization.settings.language
				}
			});
		};

		/**
		 * Localize records
		 *
		 * @param {Integer} pageId
		 * @param {Integer} languageId
		 * @param {Array} uidList
		 * @return {Promise}
		 */
		Localization.localizeRecords = function(pageId, languageId, uidList) {
			return $.ajax({
				url: TYPO3.settings.ajaxUrls['records_localize'],
				data: {
					pageId: pageId,
					srcLanguageId: Localization.settings.language,
					destLanguageId: languageId,
					action: Localization.settings.mode,
					uidList: uidList
				}
			});
		};
	};

	$(Localization.initialize);

	return Localization;
});
