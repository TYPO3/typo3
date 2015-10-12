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
 * Javascript for show the online media dialog
 */
define(['jquery', 'nprogress', 'TYPO3/CMS/Lang/Lang', 'TYPO3/CMS/Backend/Modal'], function($, NProgress) {
	"use strict";

	var OnlineMediaPlugin = function(element) {
		var me = this;
		me.$btn = $(element);
		me.target = me.$btn.data('target-folder');
		me.irreObjectUid = me.$btn.data('file-irre-object');
		me.allowed = me.$btn.data('online-media-allowed');
		me.btnSubmit = me.$btn.data('data-btn-submit') || 'Add';
		me.placeholder = me.$btn.data('placeholder') || 'Paste media url here...';

		// No IRRE element found then hide input+button
		if (!me.irreObjectUid) {
			me.$btn.hide();
			return;
		}

		me.addOnlineMedia = function(url) {
			NProgress.start();
			$.post(TYPO3.settings.ajaxUrls['online_media_create'],
				{
					url: url,
					targetFolder: me.target,
					allowed: me.allowed
				},
				function(data) {
					if (data.file) {
						inline.delayedImportElement(
							me.irreObjectUid,
							'sys_file',
							data.file,
							'file'
						);
					} else {
						var $confirm = top.TYPO3.Modal.confirm(
							'ERROR',
							data.error,
							top.TYPO3.Severity.error,
							[{
								text: TYPO3.lang['button.ok'] || 'OK',
								btnClass: 'btn-' + top.TYPO3.Modal.getSeverityClass(top.TYPO3.Severity.error),
								name: 'ok'
							}]
						).on('confirm.button.ok', function() {
							$confirm.modal('hide');
						});
					}
					NProgress.done();
				}
			);
		};

		// Bind key press enter event
		me.$btn.on('click', function(evt) {
			evt.preventDefault();

			var $modal = top.TYPO3.Modal.show(
				me.$btn.attr('title'),
				'<div class="form-control-wrap">' +
					'<input type="text" class="form-control online-media-url" placeholder="' + me.placeholder + '" />' +
				'</div>',
				top.TYPO3.Severity.notice,
				[{
					text: me.btnSubmit,
					btnClass: 'btn',
					name: 'ok',
					trigger: function() {
						var url = $modal.find('input.online-media-url').val();
						if (url) {
							$modal.modal('hide');
							me.addOnlineMedia(url);
						}
					}
				}]
			);

			$modal.on('shown.bs.modal', function(e) {
				// focus the input field
				$(this).find('input.online-media-url').first().focus();
			});
		});
	};

	// register the jQuery plugin "OnlineMediaPlugin"
	$.fn.onlineMedia = function(option) {
		return this.each(function() {
			var $this = $(this),
				data = $this.data('OnlineMediaPlugin');
			if (!data) {
				$this.data('OnlineMediaPlugin', (data = new OnlineMediaPlugin(this)));
			}
			if (typeof option === 'string') {
				data[option]();
			}
		});
	};

	$(function() {
		$('.t3js-online-media-add-btn').onlineMedia();
	});
});
