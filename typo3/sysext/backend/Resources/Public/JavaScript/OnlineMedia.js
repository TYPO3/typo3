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
 * Module: TYPO3/CMS/Backend/OnlineMedia
 * Javascript for show the online media dialog
 */
define(['jquery',
		'nprogress',
		'TYPO3/CMS/Backend/Modal',
		'TYPO3/CMS/Backend/Severity',
		'TYPO3/CMS/Lang/Lang'
	   ], function($, NProgress, Modal, Severity) {
	'use strict';

	/**
	 *
	 * @param element
	 * @constructor
	 * @exports TYPO3/CMS/Backend/OnlineMedia
	 */
	var OnlineMediaPlugin = function(element) {
		var me = this;
		me.$btn = $(element);
		me.target = me.$btn.data('target-folder');
		me.irreObjectUid = me.$btn.data('file-irre-object');
		me.allowed = me.$btn.data('online-media-allowed');
		me.btnSubmit = me.$btn.data('data-btn-submit') || 'Add';
		me.placeholder = me.$btn.data('placeholder') || 'Paste media url here...';

		/**
		 *
		 * @param {String} url
		 */
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
						var $confirm = Modal.confirm(
							'ERROR',
							data.error,
							Severity.error,
							[{
								text: TYPO3.lang['button.ok'] || 'OK',
								btnClass: 'btn-' + Modal.getSeverityClass(Severity.error),
								name: 'ok',
								active: true
							}]
						).on('confirm.button.ok', function() {
							$confirm.modal('hide');
						});
					}
					NProgress.done();
				}
			);
		};

		/**
		 * Trigger the modal
		 */
		me.triggerModal = function() {
			var $modal = Modal.show(
				me.$btn.attr('title'),
				'<div class="form-control-wrap">' +
					'<input type="text" class="form-control online-media-url" placeholder="' + me.placeholder + '" />' +
				'</div>',
				Severity.notice,
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

			$modal.on('shown.bs.modal', function() {
				// focus the input field
				$(this).find('input.online-media-url').first().focus().on('keydown', function(e) {
					if (e.keyCode === 13) {
						$modal.find('button[name="ok"]').trigger('click');
					}
				});
			});
		};

		return {triggerModal: me.triggerModal};
	};

	$(document).on('click', '.t3js-online-media-add-btn', function(evt) {
		evt.preventDefault();
		var $this = $(this),
			onlineMediaPlugin = $this.data('OnlineMediaPlugin');
		if (!onlineMediaPlugin) {
			$this.data('OnlineMediaPlugin', (onlineMediaPlugin = new OnlineMediaPlugin(this)));
		}
		onlineMediaPlugin.triggerModal();
	});

});
