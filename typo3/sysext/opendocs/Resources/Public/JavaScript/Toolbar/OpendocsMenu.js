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
 * main JS part taking care of
 *  - navigating to the documents
 *  - updating the menu
 */
define('TYPO3/CMS/Opendocs/Toolbar/OpendocsMenu', ['jquery'], function($) {

	var OpendocsMenu = {
		$spinnerElement: $('<span>', {
			'class': 't3-icon fa fa-circle-o-notch spinner fa-spin'
		}),
		options: {
			containerSelector: '#typo3-cms-opendocs-backend-toolbaritems-opendocstoolbaritem',
			hashDataAttributeName: 'opendocsidentifier',
			closeSelector: '.dropdown-list-link-close',
			menuContainerSelector: '.dropdown-menu',
			menuItemSelector: '.dropdown-menu li a',
			toolbarIconSelector: '.dropdown-toggle span.t3-icon',
			openDocumentsItemsSelector: 'li.opendoc',
			counterSelector: '#tx-opendocs-counter'
		}
	};

	/**
	 * register event handlers
	 */
	OpendocsMenu.initializeEvents = function() {
		// send a request when removing an opendoc
		$(OpendocsMenu.options.containerSelector).on('click', OpendocsMenu.options.closeSelector, function(evt) {
			evt.preventDefault();
			var md5 = $(this).data(OpendocsMenu.options.hashDataAttributeName);
			if (md5) {
				OpendocsMenu.closeDocument(md5);
			}
		});
	};

	/**
	 * Displays the menu and does the AJAX call to the TYPO3 backend
	 */
	OpendocsMenu.updateMenu = function() {
		var $toolbarItemIcon = $(OpendocsMenu.options.toolbarIconSelector, OpendocsMenu.options.containerSelector);

		var $spinnerIcon = OpendocsMenu.$spinnerElement.clone();
		var $existingIcon = $toolbarItemIcon.replaceWith($spinnerIcon);

		$.ajax({
			url: TYPO3.settings.ajaxUrls['TxOpendocs::renderMenu'],
			type: 'post',
			cache: false,
			success: function(data) {
				$(OpendocsMenu.options.containerSelector).find(OpendocsMenu.options.menuContainerSelector).html(data);
				OpendocsMenu.updateNumberOfDocs();
				$spinnerIcon.replaceWith($existingIcon);
			}
		});
	};

	/**
	 * Updates the number of open documents in the toolbar according to the
	 * number of items in the menu bar.
	 */
	OpendocsMenu.updateNumberOfDocs = function() {
		var num = $(OpendocsMenu.options.containerSelector).find(OpendocsMenu.options.openDocumentsItemsSelector).length;
		$(OpendocsMenu.options.counterSelector).text(num).toggle(num > 0);
	};

	/**
	 * Closes an open document
	 *
	 * @param string md5sum
	 */
	OpendocsMenu.closeDocument = function(md5sum) {
		$.ajax({
			url: TYPO3.settings.ajaxUrls['TxOpendocs::closeDocument'],
			type: 'post',
			cache: false,
			data: {
				md5sum: md5sum
			},
			success: function(data) {
				$(OpendocsMenu.options.menuContainerSelector, OpendocsMenu.options.containerSelector).html(data);
				OpendocsMenu.updateNumberOfDocs();
			}
		});
	};

	/**
	 * closes the menu (e.g. when clicked on an item)
	 */
	OpendocsMenu.toggleMenu = function() {
		$(OpendocsMenu.options.containerSelector).toggleClass('open');
	};

	/**
	 * initialize and return the Opendocs object
	 */
	return function() {
		$(document).ready(function() {
			OpendocsMenu.initializeEvents();
			OpendocsMenu.updateMenu();
		});

		TYPO3.OpendocsMenu = OpendocsMenu;
		return OpendocsMenu;
	}();
});
