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
 * API for modal windows powered by Twitter Bootstrap.
 * This module depends on TYPO3/CMS/Backend/FlashMessages due to TYPO3.Severity.
 */
define('TYPO3/CMS/Backend/Modal', ['jquery', 'TYPO3/CMS/Backend/FlashMessages'], function($) {

	/**
	 * The main object of the modal API
	 * @type {{currentModal: null, template: (*|jQuery)}}
	 */
	var Modal = {
		instances: [],
		currentModal: null,
		template: $('<div />', {class: 't3-modal modal fade'}).append(
			$('<div />', {class: 'modal-dialog'}).append(
				$('<div />', {class: 'modal-content'}).append(
					$('<div />', {class: 'modal-header'}).append(
						$('<button />', {class: 'close'}).append(
							$('<span />', {'aria-hidden': 'true'}).html('&times;'),
							$('<span />', {class: 'sr-only'})
						),
						$('<h4 />', {class: 'modal-title'})
					),
					$('<div />', {class: 'modal-body'}),
					$('<div />', {class: 'modal-footer'})
				)
			)
		)
	};

	/**
	 * Get the correct css class for given severity
	 *
	 * @param {int} severity use constants from TYPO3.Severity.*
	 * @returns {string}
	 * @private
	 */
	Modal.getSeverityClass = function(severity) {
		var severityClass;
		switch (severity) {
			case TYPO3.Severity.notice:
				severityClass = 'notice';
				break;
			case TYPO3.Severity.ok:
				severityClass = 'success';
				break;
			case TYPO3.Severity.warning:
				severityClass = 'warning';
				break;
			case TYPO3.Severity.error:
				severityClass = 'danger';
				break;
			case TYPO3.Severity.info:
			default:
				severityClass = 'info';
				break;
		}
		return 't3-modal-' + severityClass;
	};

	/**
	 * Shows a confirmation dialog
	 *
	 * @param {string} title the title for the confirm modal
	 * @param {string} content the content for the conform modal, e.g. the main question
	 * @param {int} severity default TYPO3.Severity.info
	 * @param {array} buttons an array with buttons, default no buttons
	 */
	Modal.confirm = function(title, content, severity, buttons) {
		severity = (typeof severity !== 'undefined' ? severity : TYPO3.Severity.info);
		buttons = buttons || [];
		Modal.currentModal = Modal.template.clone();
		Modal.currentModal.attr('tabindex', '-1');
		Modal.currentModal.find('.modal-title').text(title);
		Modal.currentModal.find('.modal-header .close').on('click', function() {
			Modal.currentModal.trigger('modal-dismiss');
		});

		if (typeof content === 'object') {
			Modal.currentModal.find('.modal-body').append(content);
		} else {
			// we need html, check if we have to wrap content in <p>
			if (!/^<[a-z][\s\S]*>/i.test(content)) {
				content = $('<p />').text(content);
			}

			Modal.currentModal.find('.modal-body').html(content);
		}

		Modal.currentModal.addClass(Modal.getSeverityClass(severity));
		if (buttons.length > 0) {
			for (var i=0; i<buttons.length; i++) {
				var button = buttons[i];
				var $button = $('<button />', {class: 'btn'});
				$button.on('click', button.trigger);
				$button.html(button.text);
				if (button.active) {
					$button.addClass('t3js-active');
				}
				if (button.btnClass) {
					$button.addClass(button.btnClass);
				}
				Modal.currentModal.find('.modal-footer').append($button);
			}
		} else {
			Modal.currentModal.find('.modal-footer').remove();
		}
		Modal.currentModal.on('shown.bs.modal', function(e) {
			// focus the button which was configured as active button
			$(this).find('.modal-footer .t3js-active').first().focus();
		});
		Modal.currentModal.on('hidden.bs.modal', function(e) {
			if (Modal.instances.length > 0) {
				var lastIndex = Modal.instances.length-1;
				Modal.instances.splice(lastIndex, 1);
				Modal.currentModal = Modal.instances[lastIndex-1];
			}
			$(this).remove();
			// Keep class modal-open on body tag as long as open modals exist
			if (Modal.instances.length > 0) {
				$('body').addClass('modal-open');
			}
		});
		Modal.currentModal.on('show.bs.modal', function(e) {
			Modal.instances.push(Modal.currentModal);
			Modal.center();
		});
		$('body').append(Modal.currentModal);
		Modal.currentModal.modal();

		return Modal.currentModal;
	};

	/**
	 * Close the current open modal
	 */
	Modal.dismiss = function() {
		if (Modal.currentModal) {
			Modal.currentModal.modal('hide');
			Modal.currentModal = null;
		}
	};

	/**
	 * Center the modal windows
	 */
	Modal.center = function() {
		$(window).off('resize', Modal.center);
		if(Modal.instances.length > 0){
			$(window).on('resize', Modal.center);
			$(Modal.instances).each(function() {
				var $me = $(this),
					$clone = $me.clone().css('display', 'block').appendTo('body'),
					top = Math.max(0, Math.round(($clone.height() - $clone.find('.modal-content').height()) / 2));

				$clone.remove();
				$me.find('.modal-content').css('margin-top', top);
			});
		}
	};

	/**
	 * Initialize markup with data attributes
	 */
	Modal.initializeMarkupTrigger = function() {
		$(document).on('click', '.t3js-modal-trigger', function(evt) {
			evt.preventDefault();
			var $element = $(this);
			var title = $element.data('title') || 'Alert';
			var content = $element.data('content') || 'Are you sure?';
			var severity = (typeof TYPO3.Severity[$element.data('severity')] !== 'undefined') ? TYPO3.Severity[$element.data('severity')] : TYPO3.Severity.info;
			var buttons = [
				{
					text: $element.data('button-close-text') || 'Close',
					active: true,
					trigger: function() {
						$element.trigger('modal-dismiss');
					}
				},
				{
					text: $element.data('button-ok-text') || 'OK',
					trigger: function() {
						$element.trigger('modal-dismiss');
						self.location.href = $element.data('href') || $element.attr('href');
					}
				}
			];
			Modal.confirm(title, content, severity, buttons);
		});
	};

	/**
	 * Custom event, fired if modal gets closed
	 */
	$(document).on('modal-dismiss', Modal.dismiss);

	/**
	 * Return the Modal object
	 */
	return function() {
		Modal.initializeMarkupTrigger();
		TYPO3.Modal = Modal;
		return Modal;
	}();
});
