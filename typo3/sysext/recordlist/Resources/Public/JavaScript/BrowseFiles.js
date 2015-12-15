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
 * Module: TYPO3/CMS/Recordlist/BrowseFiles
 * File selection
 */
define(['jquery', 'TYPO3/CMS/Recordlist/ElementBrowser', 'TYPO3/CMS/Backend/LegacyTree'], function($, ElementBrowser, Tree) {
	'use strict';

	/**
	 *
	 * @type {{elements: {}}}
	 * @exports TYPO3/CMS/Recordlist/BrowseFiles
	 */
	var BrowseFiles = {
		elements: {}
	};

	/**
	 * when selecting one or multiple files, this action is called
	 *
	 * @type {{insertElement: Function, insertElementMultiple: Function}}
	 */
	BrowseFiles.File = {
		insertElement: function(index, close) {
			var result = false;
			if (typeof BrowseFiles.elements[index] !== 'undefined') {
				var element = BrowseFiles.elements[index];
				result = ElementBrowser.insertElement(element.table, element.uid, element.type, element.fileName, element.filePath, element.fileExt, element.fileIcon, '', close);
			}
			return result;
		},
		insertElementMultiple: function(list) {
			var uidList = [];
			for (var i = 0, n = list.length; i < n; i++) {
				if (typeof BrowseFiles.elements[list[i]] !== 'undefined') {
					var element = BrowseFiles.elements[list[i]];
					uidList.push(element.uid);
				}
			}
			ElementBrowser.insertMultiple('sys_file', uidList);
		}
	};

	/**
	 * Selector when using "Import selection" and "Toggle selection"
	 */
	BrowseFiles.Selector = {
		// Toggle selection button is pressed
		toggle: function(e) {
			e.preventDefault();
			var items = BrowseFiles.Selector.getItems();
			if (items.length) {
				items.each(function(position, item) {
					item.checked = (item.checked ? null : 'checked');
				});
			}
		},
		// Import selection button is pressed
		handle: function(e) {
			e.preventDefault();
			var items = BrowseFiles.Selector.getItems();
			var selectedItems = [];
			if (items.length) {
				items.each(function(position, item) {
					if (item.checked && item.name) {
						selectedItems.push(item.name);
					}
				});
				if (selectedItems.length > 0) {
					if (ElementBrowser.hasActionMultipleCode) {
						BrowseFiles.File.insertElementMultiple(selectedItems);
					} else {
						for (var i = 0; i < selectedItems.length; i++) {
							BrowseFiles.File.insertElement(selectedItems[i]);
						}
					}
				}
				ElementBrowser.focusOpenerAndClose(true);
			}
		},
		getItems: function() {
			return $('#typo3-filelist').find('.typo3-bulk-item');
		}
	};

	Tree.ajaxID = 'sc_alt_file_navframe_expandtoggle';

	$(function() {
		$.extend(BrowseFiles.elements, $('body').data('elements'));

		$('[data-close]').on('click', function (e) {
			e.preventDefault();
			BrowseFiles.File.insertElement('file_' + $(this).data('fileIndex'), $(this).data('close'));
		});

		$('#t3js-importSelection').on('click', BrowseFiles.Selector.handle);
		$('#t3js-toggleSelection').on('click', BrowseFiles.Selector.toggle);
	});

	return BrowseFiles;
});
