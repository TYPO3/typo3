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
 * Module: TYPO3/CMS/Opendocs/OpendocsMenu
 * main JS part taking care of
 *  - navigating to the documents
 *  - updating the menu
 */
define(['jquery', 'TYPO3/CMS/Backend/Icons'], function($, Icons) {
	'use strict';

	/**
	 *
	 * @type {{options: {containerSelector: string, hashDataAttributeName: string, closeSelector: string, menuContainerSelector: string, menuItemSelector: string, toolbarIconSelector: string, openDocumentsItemsSelector: string, counterSelector: string}}}
	 * @exports TYPO3/CMS/Opendocs/OpendocsMenu
	 */
	var OpendocsMenu = {
		options: {
			containerSelector: '#typo3-cms-opendocs-backend-toolbaritems-opendocstoolbaritem',
			hashDataAttributeName: 'opendocsidentifier',
			closeSelector: '.dropdown-list-link-close',
			menuContainerSelector: '.dropdown-menu',
			menuItemSelector: '.dropdown-menu li a',
			toolbarIconSelector: '.dropdown-toggle span.icon',
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
		var $toolbarItemIcon = $(OpendocsMenu.options.toolbarIconSelector, OpendocsMenu.options.containerSelector),
			$existingIcon = $toolbarItemIcon.clone();

		Icons.getIcon('spinner-circle-light', Icons.sizes.small).done(function(spinner) {
			$toolbarItemIcon.replaceWith(spinner);
		});

		$.ajax({
			url: TYPO3.settings.ajaxUrls['opendocs_menu'],
			type: 'post',
			cache: false,
			success: function(data) {
				$(OpendocsMenu.options.containerSelector).find(OpendocsMenu.options.menuContainerSelector).html(data);
				OpendocsMenu.updateNumberOfDocs();
				$(OpendocsMenu.options.toolbarIconSelector, OpendocsMenu.options.containerSelector).replaceWith($existingIcon);
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
	 * @param {String} md5sum
	 */
	OpendocsMenu.closeDocument = function(md5sum) {
		$.ajax({
			url: TYPO3.settings.ajaxUrls['opendocs_closedoc'],
			type: 'post',
			cache: false,
			data: {
				md5sum: md5sum
			},
			success: function(data) {
				$(OpendocsMenu.options.menuContainerSelector, OpendocsMenu.options.containerSelector).html(data);
				OpendocsMenu.updateNumberOfDocs();
				// Re-open the menu after closing a document
				$(OpendocsMenu.options.containerSelector).toggleClass('open');
			}
		});
	};

	/**
	 * closes the menu (e.g. when clicked on an item)
	 */
	OpendocsMenu.toggleMenu = function() {
		$(OpendocsMenu.options.containerSelector).toggleClass('open');
	};

	$(function() {
		OpendocsMenu.initializeEvents();
		OpendocsMenu.updateMenu();
	});

	// expose to global
	TYPO3.OpendocsMenu = OpendocsMenu;

	return OpendocsMenu;
});
