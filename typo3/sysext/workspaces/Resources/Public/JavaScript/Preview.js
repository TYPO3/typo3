/*
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
 * RequireJS module for workspace preview
 */
define([
	'jquery',
	'TYPO3/CMS/Workspaces/Workspaces',
	'TYPO3/CMS/Backend/Severity',
	'TYPO3/CMS/Backend/Modal',
	'twbs/bootstrap-slider'
], function($, Workspaces, Severity, Modal) {
	'use strict';

	var Preview = {
		identifiers: {
			topbar: '#typo3-topbar',
			workspacePanel: '.workspace-panel',
			liveView: '#live-view',
			workspaceTabs: '.t3js-workspace-tabs [data-toggle="tab"]',
			workspaceActions: '.t3js-workspace-actions',
			stageSlider: '#workspace-stage-slider',
			workspaceView: '#workspace-view',
			workspaceList: '#workspace-list',
			sendToStageAction: '[data-action="send-to-stage"]',
			discardAction: '[data-action="discard"]',
			stageButtonsContainer: '.t3js-stage-buttons',
			previewModeContainer: '.t3js-preview-mode',
			activePreviewMode: '.t3js-active-preview-mode',
			workspacePreview: '.t3js-workspace-preview'
		},
		currentSlidePosition: 100,
		elements: {} // filled in Preview.getElements()
	};

	/**
	 * Initializes the preview module
	 */
	Preview.initialize = function() {
		Preview.getElements();
		Preview.resizeViews();

		Preview.adjustPreviewModeSelectorWidth();
		Preview.elements.$stageSlider.slider();

		Preview.registerEvents();
	};

	/**
	 * Fetches and stores often required elements
	 */
	Preview.getElements = function() {
		Preview.elements.$liveView = $(Preview.identifiers.liveView);
		Preview.elements.$workspacePanel = $(Preview.identifiers.workspacePanel);
		Preview.elements.$workspaceTabs = $(Preview.identifiers.workspaceTabs);
		Preview.elements.$workspaceActions = $(Preview.identifiers.workspaceActions);
		Preview.elements.$stageSlider = $(Preview.identifiers.stageSlider);
		Preview.elements.$workspaceView = $(Preview.identifiers.workspaceView);
		Preview.elements.$workspaceList = $(Preview.identifiers.workspaceList);
		Preview.elements.$stageButtonsContainer = $(Preview.identifiers.stageButtonsContainer);
		Preview.elements.$previewModeContainer = $(Preview.identifiers.previewModeContainer);
		Preview.elements.$activePreviewMode = $(Preview.identifiers.activePreviewMode);
		Preview.elements.$workspacePreview = $(Preview.identifiers.workspacePreview);
	};

	/**
	 * Registers the events
	 */
	Preview.registerEvents = function() {
		$(window).on('resize', function() {
			Preview.resizeViews();
		});
		$(document)
			.on('click', Preview.identifiers.discardAction, Preview.renderDiscardWindow)
			.on('click', Preview.identifiers.sendToStageAction, Preview.renderSendPageToStageWindow)
		;

		Preview.elements.$workspaceTabs.on('show.bs.tab', function() {
			Preview.elements.$workspaceActions.toggle($(this).data('actions'));
		});
		Preview.elements.$stageSlider.on('change', Preview.updateSlidePosition);
		Preview.elements.$previewModeContainer.find('[data-preview-mode]').on('click', Preview.changePreviewMode);
	};

	/**
	 * Renders the staging buttons
	 *
	 * @param {String} buttons
	 */
	Preview.renderStageButtons = function(buttons) {
		Preview.elements.$stageButtonsContainer.html(buttons);
	};

	/**
	 * Calculate the available space based on the viewport height
	 *
	 * @returns {Number}
	 */
	Preview.getAvailableSpace = function() {
		var $viewportHeight = $(window).height(),
			$topbarHeight = $(Preview.identifiers.topbar).outerHeight();

		return $viewportHeight - $topbarHeight;
	};

	/**
	 * Updates the position of the comparison slider
	 *
	 * @param {Event} e
	 */
	Preview.updateSlidePosition = function(e) {
		Preview.currentSlidePosition = e.value.newValue;
		Preview.resizeViews();
	};

	/**
	 * Resize the views based on the current viewport height and slider position
	 */
	Preview.resizeViews = function() {
		var availableSpace = Preview.getAvailableSpace(),
			relativeHeightOfLiveView = (Preview.currentSlidePosition - 100) * -1,
			absoluteHeightOfLiveView = Math.round(Math.abs(availableSpace * relativeHeightOfLiveView / 100)),
			outerHeightDifference = Preview.elements.$liveView.outerHeight() - Preview.elements.$liveView.height();

		Preview.elements.$workspacePreview.height(availableSpace);

		if (Preview.elements.$activePreviewMode.data('activePreviewMode') === 'slider') {
			Preview.elements.$liveView.height(absoluteHeightOfLiveView - outerHeightDifference);
		}
		Preview.elements.$workspaceList.height(availableSpace);
	};

	/**
	 * Renders the discard window
	 *
	 * @private
	 */
	Preview.renderDiscardWindow = function() {
		var $modal = Modal.confirm(
			TYPO3.lang['window.discardAll.title'],
			TYPO3.lang['window.discardAll.message'],
			Severity.warning,
			[
				{
					text: TYPO3.lang['cancel'],
					active: true,
					btnClass: 'btn-default',
					name: 'cancel',
					trigger: function() {
						$modal.modal('hide');
					}
				}, {
				text: TYPO3.lang['ok'],
				btnClass: 'btn-warning',
				name: 'ok'
			}
			]
		);
		$modal.on('button.clicked', function(e) {
			if (e.target.name === 'ok') {
				Workspaces.sendExtDirectRequest([
					Workspaces.generateExtDirectActionsPayload('discardStagesFromPage', [TYPO3.settings.Workspaces.id]),
					Workspaces.generateExtDirectActionsPayload('updateStageChangeButtons', [TYPO3.settings.Workspaces.id])
				]).done(function(response) {
					$modal.modal('hide');
					Preview.renderStageButtons(response[1].result);
					// Reloading live view and and workspace list view IFRAME
					Preview.elements.$workspaceView.attr('src', Preview.elements.$workspaceView.attr('src'));
					Preview.elements.$workspaceList.attr('src', Preview.elements.$workspaceList.attr('src'));
				});
			}
		});
	};

	/**
	 * Adjusts the width of the preview mode selector to avoid jumping around due to different widths of the labels
	 */
	Preview.adjustPreviewModeSelectorWidth = function() {
		var $btnGroup = Preview.elements.$previewModeContainer.find('.btn-group'),
			maximumWidth = 0;

		$btnGroup.addClass('open');
		Preview.elements.$previewModeContainer.find('li > a > span').each(function(_, el) {
			var width = $(el).width();
			if (maximumWidth < width) {
				maximumWidth = width;
			}
		});
		$btnGroup.removeClass('open');
		Preview.elements.$activePreviewMode.width(maximumWidth);
	};

	/**
	 * Renders the "send page to stage" window
	 *
	 * @private
	 */
	Preview.renderSendPageToStageWindow = function() {
		var $me = $(this),
			direction = $me.data('direction'),
			actionName;

		if (direction === 'prev') {
			actionName = 'sendPageToPreviousStage';
		} else if (direction === 'next') {
			actionName = 'sendPageToNextStage';
		} else {
			throw 'Invalid direction ' + direction + ' requested.';
		}

		Workspaces.sendExtDirectRequest(
			Workspaces.generateExtDirectActionsPayload(actionName, [TYPO3.settings.Workspaces.id])
		).done(function(response) {
			var $modal = Workspaces.renderSendToStageWindow(response);
			$modal.on('button.clicked', function (e) {
				if (e.target.name === 'ok') {
					var $form = $(e.currentTarget).find('form'),
						serializedForm = $form.serializeObject();

					serializedForm.affects = response[0].result.affects;
					serializedForm.stageId = $me.data('stageId');

					Workspaces.sendExtDirectRequest([
						Workspaces.generateExtDirectActionsPayload('sentCollectionToStage', [serializedForm]),
						Workspaces.generateExtDirectActionsPayload('updateStageChangeButtons', [TYPO3.settings.Workspaces.id])
					]).done(function(response) {
						$modal.modal('hide');

						Preview.renderStageButtons(response[1].result);
					});
				}
			});
		});
	};

	/**
	 * Changes the preview mode
	 *
	 * @param {Event} e
	 */
	Preview.changePreviewMode = function(e) {
		e.preventDefault();

		var $trigger = $(this),
			currentPreviewMode = Preview.elements.$activePreviewMode.data('activePreviewMode'),
			newPreviewMode = $trigger.data('previewMode');

		Preview.elements.$activePreviewMode.text($trigger.text()).data('activePreviewMode', newPreviewMode);
		Preview.elements.$workspacePreview.parent()
			.removeClass('preview-mode-' + currentPreviewMode)
			.addClass('preview-mode-' + newPreviewMode);

		if (newPreviewMode === 'slider') {
			Preview.elements.$stageSlider.parent().toggle(true);
			Preview.resizeViews();
		} else {
			Preview.elements.$stageSlider.parent().toggle(false);

			if (newPreviewMode === 'vbox') {
				Preview.elements.$liveView.height('100%');
			} else {
				Preview.elements.$liveView.height('50%');
			}
		}

	};

	/**
	 * Serialize a form to a JavaScript object
	 *
	 * @see http://stackoverflow.com/a/1186309/4828813
	 * @return {Object}
	 */
	$.fn.serializeObject = function() {
		var o = {};
		var a = this.serializeArray();
		$.each(a, function() {
			if (typeof o[this.name] !== 'undefined') {
				if (!o[this.name].push) {
					o[this.name] = [o[this.name]];
				}
				o[this.name].push(this.value || '');
			} else {
				o[this.name] = this.value || '';
			}
		});
		return o;
	};

	$(document).ready(function() {
		Preview.initialize();
	});
});