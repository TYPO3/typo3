var BrowseLinks = {
	elements: {},
	addElements: function(elements) {
		BrowseLinks.elements = $H(BrowseLinks.elements).merge(elements);
	},
	focusOpenerAndClose: function(close) {
		if (close) {
			parent.window.opener.focus();
			parent.close();
		}
	}
}

BrowseLinks.File = {
	insertElement: function(index, close) {
		var result = false;
		if (typeof BrowseLinks.elements[index] != 'undefined') {
			var element = BrowseLinks.elements[index];
			result = insertElement(
				'', element.md5, element.type,
				element.fileName, element.filePath, element.fileExt,
				element.fileIcon, '', close
			);
		}
		return result;
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
		if (items.length) {
			items.each(function(item) {
				if (item.checked && item.name) {
					BrowseLinks.File.insertElement(item.name);
				}
			});
			BrowseLinks.focusOpenerAndClose(true);
		}
	},
	getParentElement: function(element) {
		element = $(element);
		return (element ? element : $(this.element));
	},
	getItems: function(element) {
		element = this.getParentElement(element);
		return Element.getElementsByClassName(element, 'typo3-bulk-item');
	}
};