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
 * Module: TYPO3/CMS/Rtehtmlarea/AddImage
 */
define(['jquery', 'TYPO3/CMS/Rtehtmlarea/SelectImage', 'TYPO3/CMS/Backend/LegacyTree'], function($, SelectImage, Tree) {
	'use strict';

	/**
	 * @type {{elements: Object, toggle: Function, handle: Function}}
	 * @exports TYPO3/CMS/Rtehtmlarea/AddImage
	 */
	var AddImage = {
		elements: {},

		toggle: function(event) {
			event.preventDefault();
			var items = AddImage.getItems();
			if (items.length) {
				items.each(function(position, item) {
					item.checked = (item.checked ? null : 'checked');
				});
			}
		},

		handle: function(e) {
			e.preventDefault();
			var items = AddImage.getItems();
			var selectedItems = [];
			if (items.length) {
				items.each(function(position, item) {
					if (item.checked && item.name) {
						selectedItems.push(AddImage.elements[item.name].uid);
					}
				});
				SelectImage.setImagesInRTE(selectedItems.join('|'));
			}
		},

		getItems: function () {
			return $('#typo3-filelist').find('.typo3-bulk-item');
		}
	};

	Tree.ajaxID = 'sc_alt_file_navframe_expandtoggle';

	$(function () {
		$.extend(AddImage.elements, $('body').data('elements'));

		$('[data-close]').on('click', function (e) {
			e.preventDefault();
			SelectImage.setImagesInRTE(AddImage.elements['file_' + $(this).data('fileIndex')].uid);
		});

		$('#t3js-importSelection').on('click', AddImage.handle);
		$('#t3js-toggleSelection').on('click', AddImage.toggle);
	});

	return AddImage;
});
