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
 * Module: TYPO3/CMS/Backend/Modal
 * API for modal windows powered by Twitter Bootstrap.
 */
define(['jquery',
		'TYPO3/CMS/Backend/Severity',
		'bootstrap'
	   ], function($, Severity) {
	'use strict';

	try {
		// fetch from parent
		if (parent && parent.window.TYPO3 && parent.window.TYPO3.Modal) {
			// we need to trigger the event capturing again, in order to make sure this works inside iframes
			parent.window.TYPO3.Modal.initializeMarkupTrigger(document);
			return parent.window.TYPO3.Modal;
		}

		// fetch object from outer frame
		if (top && top.TYPO3.Modal) {
			// we need to trigger the event capturing again, in order to make sure this works inside iframes
			top.TYPO3.Modal.initializeMarkupTrigger(document);
			return top.TYPO3.Modal;
		}
	} catch (e) {
		// This only happens if the opener, parent or top is some other url (eg a local file)
		// which loaded the current window. Then the browser's cross domain policy jumps in
		// and raises an exception.
		// For this case we are safe and we can create our global object below.
	}

	/**
	 * The main object of the modal API
	 *
	 * @type {{instances: Array, currentModal: null, template: (*|jQuery|HTMLElement)}}
	 * @exports TYPO3/CMS/Backend/Modal
	 */
	var Modal = {
		instances: [],
		currentModal: null,
		template: $(
			'<div class="t3-modal modal fade">' +
				'<div class="modal-dialog">' +
					'<div class="modal-content">' +
						'<div class="modal-header">' +
							'<button class="close">' +
								'<span aria-hidden="true">&times;</span>' +
								'<span class="sr-only"></span>' +
							'</button>' +
							'<h4 class="modal-title"></h4>' +
						'</div>' +
						'<div class="modal-body"></div>' +
						'<div class="modal-footer"></div>' +
					'</div>' +
				'</div>' +
			'</div>'
		)
	};

	/**
	 * Get the correct css class for given severity
	 *
	 * @param {int} severity use constants from Severity.*
	 * @returns {String}
	 * @private
	 */
	Modal.getSeverityClass = function(severity) {
		var severityClass;
		switch (severity) {
			case Severity.notice:
				severityClass = 'notice';
				break;
			case Severity.ok:
				severityClass = 'success';
				break;
			case Severity.warning:
				severityClass = 'warning';
				break;
			case Severity.error:
				severityClass = 'danger';
				break;
			case Severity.info:
			default:
				severityClass = 'info';
				break;
		}
		return severityClass;
	};

	/**
	 * Shows a confirmation dialog
	 * Events:
	 * - button.clicked
	 * - confirm.button.cancel
	 * - confirm.button.ok
	 *
	 * @param {String} title the title for the confirm modal
	 * @param {String} content the content for the conform modal, e.g. the main question
	 * @param {int} [severity=Severity.warning] severity default Severity.warning
	 * @param {array} [buttons] an array with buttons, default no buttons
	 * @param {array} [additionalCssClasses=''] additional css classes to add to the modal
	 */
	Modal.confirm = function(title, content, severity, buttons, additionalCssClasses) {
		severity = (typeof severity !== 'undefined' ? severity : Severity.warning);
		buttons = buttons || [
				{
					text: $(this).data('button-close-text') || TYPO3.lang['button.cancel'] || 'Cancel',
					active: true,
					btnClass: 'btn-default',
					name: 'cancel'
				},
				{
					text: $(this).data('button-ok-text') || TYPO3.lang['button.ok'] || 'OK',
					btnClass: 'btn-' + Modal.getSeverityClass(severity),
					name: 'ok'
				}
			];
		additionalCssClasses = additionalCssClasses || [];
		var $modal = Modal.show(title, content, severity, buttons, additionalCssClasses);
		$modal.on('button.clicked', function(e) {
			if (e.target.name === 'cancel') {
				$(this).trigger('confirm.button.cancel');
			} else if (e.target.name === 'ok') {
				$(this).trigger('confirm.button.ok');
			}
		});
		return $modal;
	};

	/**
	 * load URL with AJAX, append the content to the modal-body
	 * and trigger the callback
	 *
	 * @param {String} title
	 * @param {int} severity
	 * @param {array} buttons
	 * @param {String} url
	 * @param {String} target
	 * @param {function} callback
	 */
	Modal.loadUrl = function(title, severity, buttons, url, callback, target) {
		$.get(url, function(response) {
			Modal.currentModal.find(target ? target : '.modal-body').empty().append(response);
			if (callback) {
				callback();
			}
			Modal.currentModal.trigger('modal-loaded');
		}, 'html');
		return Modal.show(title, '<p class="loadmessage"><i class="fa fa-spinner fa-spin fa-5x "></i></p>', severity, buttons);
	};


	/**
	 * Shows a dialog
	 * Events:
	 * - button.clicked
	 *
	 * @param {String} title the title for the confirm modal
	 * @param {String} content the content for the conform modal, e.g. the main question
	 * @param {int} severity default Severity.info
	 * @param {array} buttons an array with buttons, default no buttons
	 * @param {array} additionalCssClasses additional css classes to add to the modal
	 */
	Modal.show = function(title, content, severity, buttons, additionalCssClasses) {
		var i;

		severity = (typeof severity !== 'undefined' ? severity : Severity.info);
		buttons = buttons || [];
		additionalCssClasses = additionalCssClasses || [];

		var currentModal = Modal.template.clone();
		if (additionalCssClasses.length) {
			for (i = 0; i < additionalCssClasses.length; i++) {
				currentModal.addClass(additionalCssClasses[i]);
			}
		}
		currentModal.attr('tabindex', '-1');
		currentModal.find('.modal-title').text(title);
		currentModal.find('.modal-header .close').on('click', function() {
			currentModal.modal('hide');
		});

		if (typeof content === 'object') {
			currentModal.find('.modal-body').append(content);
		} else {
			// we need html, check if we have to wrap content in <p>
			if (!/^<[a-z][\s\S]*>/i.test(content)) {
				content = $('<p />').text(content);
			}
			currentModal.find('.modal-body').html(content);
		}

		currentModal.addClass('t3-modal-' + Modal.getSeverityClass(severity));
		if (buttons.length > 0) {
			for (i = 0; i<buttons.length; i++) {
				var button = buttons[i];
				var $button = $('<button />', {class: 'btn'});
				$button.html(button.text);
				if (button.active) {
					$button.addClass('t3js-active');
				}
				if (button.btnClass) {
					$button.addClass(button.btnClass);
				}
				if (button.name) {
					$button.attr('name', button.name);
				}
				if (button.trigger) {
					$button.on('click', button.trigger);
				}
				currentModal.find('.modal-footer').append($button);
			}
			currentModal
				.find('.modal-footer button')
				.on('click', function() {
					$(this).trigger('button.clicked');
				});

		} else {
			currentModal.find('.modal-footer').remove();
		}
		currentModal.on('shown.bs.modal', function(e) {
			// focus the button which was configured as active button
			$(this).find('.modal-footer .t3js-active').first().focus();
		});
		// Remove modal from Modal.instances when hidden
		currentModal.on('hidden.bs.modal', function(e) {
			if (Modal.instances.length > 0) {
				var lastIndex = Modal.instances.length-1;
				Modal.instances.splice(lastIndex, 1);
				Modal.currentModal = Modal.instances[lastIndex-1];
			}
			$(this).remove();
			// Keep class modal-open on body tag as long as open modals exist
			if (Modal.instances.length > 0) {
				top.TYPO3.jQuery('body').addClass('modal-open');
			}
		});
		// When modal is opened/shown add it to Modal.instances and make it Modal.currentModal
		currentModal.on('show.bs.modal', function(e) {
			Modal.currentModal = $(this);
			Modal.instances.push(Modal.currentModal);
			Modal.center();
		});
		currentModal.on('modal-dismiss', function(e) {
			// Hide modal, the bs.modal events will clean up Modal.instances
			$(this).modal('hide');
		});

		return currentModal.modal();
	};

	/**
	 * Close the current open modal
	 */
	Modal.dismiss = function() {
		if (Modal.currentModal) {
			Modal.currentModal.modal('hide');
		}
	};

	/**
	 * Center the modal windows
	 */
	Modal.center = function() {
		$(window).off('resize', Modal.center);
		if (Modal.instances.length > 0) {
			$(window).on('resize', Modal.center);
			$(Modal.instances).each(function() {
				var $me = $(this),
					$clone = $me.clone().css('display', 'block').appendTo('body'),
					top = Math.max(0, Math.round(($clone.height() - $clone.find('.modal-content').height()) / 2));

				if ($me.hasClass('modal-inner-scroll')) {
					var maxHeight = $(window).height() - $clone.find('.modal-header').height() - $clone.find('.modal-footer').height() - 100;
					$me.find('.modal-body').css({'max-height': maxHeight, 'overflow-y': 'auto'});
				}

				$clone.remove();
				$me.find('.modal-content').css('margin-top', top);
			});
		}
	};

	/**
	 * Initialize markup with data attributes
	 *
	 * @param {object} theDocument
	 */
	Modal.initializeMarkupTrigger = function(theDocument) {
		$(theDocument).on('click', '.t3js-modal-trigger', function(evt) {
			evt.preventDefault();
			var $element = $(this);
			var url = $element.data('url') || null;
			var title = $element.data('title') || 'Alert';
			var content = $element.data('content') || 'Are you sure?';
			var severity = (typeof Severity[$element.data('severity')] !== 'undefined') ? Severity[$element.data('severity')] : Severity.info;
			var buttons = [
				{
					text: $element.data('button-close-text') || 'Close',
					active: true,
					btnClass: 'btn-default',
					trigger: function() {
						Modal.currentModal.trigger('modal-dismiss');
					}
				},
				{
					text: $element.data('button-ok-text') || 'OK',
					btnClass: 'btn-' + Modal.getSeverityClass(severity),
					trigger: function() {
						Modal.currentModal.trigger('modal-dismiss');
						evt.target.ownerDocument.location.href = $element.data('href') || $element.attr('href');
					}
				}
			];
			if (url !== null) {
				var separator = (url.indexOf('?') > -1) ? '&' : '?';
				var params = $.param({data: $element.data()});
				Modal.loadUrl(title, severity, buttons, url + separator + params);
			} else {
				Modal.show(title, content, severity, buttons);
			}
		});
	};

	/**
	 * Custom event, fired if modal gets closed
	 */
	$(document).on('modal-dismiss', Modal.dismiss);

	Modal.initializeMarkupTrigger(document);

	// expose as global object
	TYPO3.Modal = Modal;

	return Modal;
});
