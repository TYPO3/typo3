/**
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

var BrowseLinks = {
	elements: {},
	addElements: function(elements) {
		BrowseLinks.elements = $H(BrowseLinks.elements).merge(elements).toObject();
	},
	focusOpenerAndClose: function(close) {
		if (close) {
			parent.window.opener.focus();
			parent.close();
		}
	}
};

	// when selecting one or multiple files, this action is called
BrowseLinks.File = {
	insertElement: function(index, close) {
		var result = false;
		if (typeof BrowseLinks.elements[index] !== undefined) {
			var element = BrowseLinks.elements[index];

			// insertElement takes the following parameters
			// table, uid, type, filename,fp,filetype,imagefile,action, close
			result = insertElement(
					element.table,
					element.uid,
					element.type,
					element.fileName,
					element.filePath,
					element.fileExt,
					element.fileIcon,
					'',
					close
			);
		}
		return result;
	},
	insertElementMultiple: function(list) {
		var uidList = [];
		list.each(function(index) {
			if (typeof BrowseLinks.elements[index] !== undefined) {
				var element = BrowseLinks.elements[index];
				uidList.push(element.uid);
			}
		});
		insertMultiple('sys_file', uidList);
		return true;
	}

};

BrowseLinks.Selector = {
	element: 'typo3-fileList',
	toggle: function(element) {
		var items = this.getItems(element);
		if (items.length) {
			items.each(function(item) {
				item.checked = (item.checked ? null : 'checked');
			});
		}
	},
	handle: function(element) {
		var items = this.getItems(element);
		var selectedItems = [];
		if (items.length) {
			items.each(function(item) {
				if (item.checked && item.name) {
					selectedItems.push(item.name);
				}
			});
			if (selectedItems.length == 1) {
				BrowseLinks.File.insertElement(selectedItems[0]);
			} else {
				BrowseLinks.File.insertElementMultiple(selectedItems);
			}
			BrowseLinks.focusOpenerAndClose(true);
		}
	},
	getParentElement: function(element) {
		element = $(element);
		return (element ? element : $(this.element));
	},
	getItems: function(element) {
		element = this.getParentElement(element);
		return Element.select(element, '.typo3-bulk-item');
	}
};
