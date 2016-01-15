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
 * Module: TYPO3/CMS/Filelist/FileDelete
 * JavaScript for file delete
 */
define(['jquery', 'TYPO3/CMS/Backend/Modal', 'TYPO3/CMS/Backend/Severity'], function ($, Modal, Severity) {

	$(document).on('click', '.t3js-filelist-delete', function(e) {
		e.preventDefault();
		var $anchorElement = $(this);
		var redirectUrl = $anchorElement.data('redirectUrl');
		if (redirectUrl) {
			redirectUrl = top.rawurlencode(redirectUrl);
		} else {
			redirectUrl = top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search);
		}
		var identifier = $anchorElement.data('identifier');
		var veriCode = $anchorElement.data('veriCode');
		var deleteUrl = $anchorElement.data('deleteUrl') + '&file[delete][0][data]=' + encodeURIComponent(identifier) + '&vC=' + encodeURIComponent(veriCode);
		if ($anchorElement.data('check')) {
			var $modal = Modal.confirm($anchorElement.data('title'), $anchorElement.data('content'), Severity.warning, [
				{
					text: TYPO3.lang['buttons.confirm.delete_file.no'] || 'Cancel',
					active: true,
					btnClass: 'btn-default',
					name: 'no'
				},
				{
					text: TYPO3.lang['buttons.confirm.delete_file.yes'] || 'Yes, delete this file',
					btnClass: 'btn-warning',
					name: 'yes'
				}
			]);
			$modal.on('button.clicked', function(e) {
				if (e.target.name === 'no') {
					Modal.dismiss();
				} else if (e.target.name === 'yes') {
					Modal.dismiss();
					top.content.list_frame.location.href = deleteUrl + '&redirect=' + redirectUrl;
				}
			});
		} else {
			top.content.list_frame.location.href = deleteUrl + '&redirect=' + redirectUrl;
		}
	});

});
