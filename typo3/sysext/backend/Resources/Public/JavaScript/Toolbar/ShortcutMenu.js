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
/**
 * shortcut menu logic to add new shortcut, remove a shortcut
 * and edit a shortcut
 */
define('TYPO3/CMS/Backend/Toolbar/ShortcutMenu', ['jquery'], function($) {

	var ShortcutMenu = {
		$spinnerElement: $('<span>', {
			class: 't3-icon fa fa-circle-o-notch fa-spin'
		}),
		options: {
			containerSelector: '#shortcut-menu',
			toolbarIconSelector: '.dropdown-toggle span.t3-icon',
			toolbarMenuSelector: '.dropdown-menu',
			shortcutItemSelector: '.dropdown-menu .shortcut',
			shortcutLabelSelector: '.shortcut-label',
			shortcutDeleteSelector: '.shortcut-delete',
			shortcutEditSelector: '.shortcut-edit',
			shortcutFormTitleSelector: 'input[name="shortcut-title"]',
			shortcutFormGroupSelector: 'select[name="shortcut-group"]',
			shortcutFormSaveSelector: '.shortcut-form-save',
			shortcutFormCancelSelector: '.shortcut-form-cancel'
		}
	};

	/**
	 * build the in-place-editor for a shortcut
	 */
	ShortcutMenu.editShortcut = function($shortcutRecord) {
		$shortcutRecord.find(ShortcutMenu.options.shortcutEditSelector).hide();
		// load the form
		$.ajax({
			url: TYPO3.settings.ajaxUrls['ShortcutMenu::getShortcutEditForm'],
			data: {
				shortcutId: $shortcutRecord.data('shortcutid'),
				shortcutGroup: $shortcutRecord.data('shortcutgroup')
			},
			cache: false
		}).done(function(data) {
			$shortcutRecord.find(ShortcutMenu.options.shortcutLabelSelector).html(data);
		});
	};

	/**
	 * save the data from the in-place-editor for a shortcut
	 */
	ShortcutMenu.saveShortcutForm = function($shortcutRecord) {
		$.ajax({
			url: TYPO3.settings.ajaxUrls['ShortcutMenu::saveShortcut'],
			data: {
				shortcutId: $shortcutRecord.data('shortcutid'),
				shortcutTitle: $shortcutRecord.find(ShortcutMenu.options.shortcutFormTitleSelector).val(),
				shortcutGroup: $shortcutRecord.find(ShortcutMenu.options.shortcutFormGroupSelector).val()
			},
			type: 'post',
			cache: false
		}).done(function(data) {
			// @todo: we can evaluate here, but what to do? a message?
			ShortcutMenu.refreshMenu();
		});
	};

	/**
	 * removes an existing short by sending an AJAX call
	 */
	ShortcutMenu.deleteShortcut = function($shortcutRecord) {
		// @todo: translations
		if (confirm('Do you really want to remove this bookmark?')) {
			$.ajax({
				url: TYPO3.settings.ajaxUrls['ShortcutMenu::delete'],
				data: {
					shortcutId: $shortcutRecord.data('shortcutid')
				},
				type: 'post',
				cache: false
			}).done(function() {
				// a reload is used in order to restore the original behaviour
				// e.g. remove groups that are now empty because the last one in the group
				// was removed
				ShortcutMenu.refreshMenu();
			});
		}
	};

	/**
	 * makes a call to the backend class to create a new shortcut,
	 * when finished it reloads the menu
	 */
	ShortcutMenu.createShortcut = function(moduleName, url, confirmationText) {
		var shouldCreateShortcut = true;
		if (typeof confirmationText !== 'undefined') {
			shouldCreateShortcut = window.confirm(confirmationText);
		}

		if (shouldCreateShortcut) {
			var $toolbarItemIcon = $(ShortcutMenu.options.toolbarIconSelector, ShortcutMenu.options.containerSelector);
			var $spinner = ShortcutMenu.$spinnerElement.clone();
			var $existingItem = $toolbarItemIcon.replaceWith($spinner);

			$.ajax({
				url: TYPO3.settings.ajaxUrls['ShortcutMenu::create'],
				type: 'post',
				data: {
					module: moduleName,
					url: url
				},
				cache: false
			}).done(function() {
				ShortcutMenu.refreshMenu();
				$spinner.replaceWith($existingItem);
			});
		}
	};

	/**
	 * reloads the menu after an update
	 */
	ShortcutMenu.refreshMenu = function() {
		$.ajax({
			url: TYPO3.settings.ajaxUrls['ShortcutMenu::render'],
			type: 'get',
			cache: false
		}).done(function(data) {
			$(ShortcutMenu.options.toolbarMenuSelector, ShortcutMenu.options.containerSelector).html(data);
		});
	};

	/**
	 * Registers listeners
	 */
	ShortcutMenu.initializeEvents = function() {
		$(ShortcutMenu.options.containerSelector).on('click', ShortcutMenu.options.shortcutDeleteSelector, function(evt) {
			evt.preventDefault();
			evt.stopImmediatePropagation();
			ShortcutMenu.deleteShortcut($(this).closest(ShortcutMenu.options.shortcutItemSelector));
		}).on('click', ShortcutMenu.options.shortcutEditSelector, function(evt) {
			evt.preventDefault();
			evt.stopImmediatePropagation();
			ShortcutMenu.editShortcut($(this).closest(ShortcutMenu.options.shortcutItemSelector));
		}).on('click', ShortcutMenu.options.shortcutFormSaveSelector, function(evt) {
			ShortcutMenu.saveShortcutForm($(this).closest(ShortcutMenu.options.shortcutItemSelector));
		}).on('click', ShortcutMenu.options.shortcutFormCancelSelector, function() {
			// re-render the menu on canceling the update of a shortcut
			ShortcutMenu.refreshMenu();
		});
	};

	/**
	 * initialize and return the ShortcutMenu object
	 */
	return function() {
		$(document).ready(function() {
			ShortcutMenu.initializeEvents();
		});

		TYPO3.ShortcutMenu = ShortcutMenu;
		return ShortcutMenu;
	}();
});