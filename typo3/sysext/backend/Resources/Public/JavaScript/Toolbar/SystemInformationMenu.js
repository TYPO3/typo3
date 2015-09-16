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
 * System information menu handler
 */
define('TYPO3/CMS/Backend/Toolbar/SystemInformationMenu', ['jquery', 'TYPO3/CMS/Backend/Storage'], function($) {
	var SystemInformationMenu = {
		identifier: {
			containerSelector: '#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem',
			toolbarIconSelector: '.dropdown-toggle span.t3-icon',
			menuContainerSelector: '.dropdown-menu',
			moduleLinks: '.t3js-systeminformation-module'
		},
		elements: {
			$counter: $('#t3js-systeminformation-counter'),
			$spinnerElement: $('<span>', {
				'class': 't3-icon fa fa-circle-o-notch spinner fa-spin'
			})
		}
	};

	/**
	 * Initialize the events
	 */
	SystemInformationMenu.initialize = function() {
		$(SystemInformationMenu.identifier.moduleLinks).on('click', SystemInformationMenu.openModule);
	};

	/**
	 * Updates the menu
	 */
	SystemInformationMenu.updateMenu = function() {
		var $toolbarItemIcon = $(SystemInformationMenu.identifier.toolbarIconSelector, SystemInformationMenu.identifier.containerSelector),
			$spinnerIcon = SystemInformationMenu.elements.$spinnerElement.clone(),
			$existingIcon = $toolbarItemIcon.replaceWith($spinnerIcon),
			$menuContainer = $(SystemInformationMenu.identifier.containerSelector).find(SystemInformationMenu.identifier.menuContainerSelector);

		// hide the menu if it's active
		if ($menuContainer.is(':visible')) {
			$menuContainer.click();
		}

		$.ajax({
			url: TYPO3.settings.ajaxUrls['systeminformation_render'],
			type: 'post',
			cache: false,
			success: function(data) {
				$menuContainer.html(data);
				SystemInformationMenu.updateCounter();
				$spinnerIcon.replaceWith($existingIcon);

				SystemInformationMenu.initialize();
			}
		})
	};

	/**
	 * Updates the counter
	 */
	SystemInformationMenu.updateCounter = function() {
		var $ul = $(SystemInformationMenu.identifier.containerSelector).find(SystemInformationMenu.identifier.menuContainerSelector).find('ul'),
			count = $ul.data('count'),
			badgeClass = $ul.data('severityclass');

		SystemInformationMenu.elements.$counter.text(count).toggle(count > 0);
		SystemInformationMenu.elements.$counter.removeClass();

		if (badgeClass !== '') {
			SystemInformationMenu.elements.$counter.addClass('badge ' + badgeClass);
		}
	};

	/**
	 * Updates the UC and opens the linked module
	 */
	SystemInformationMenu.openModule = function(e) {
		e.preventDefault();
		e.stopPropagation();

		var storedSystemInformationSettings = {},
			moduleStorageObject = {},
			requestedModule = $(e.currentTarget).data('modulename'),
			timestamp = Math.floor((new Date).getTime() / 1000);

		if (TYPO3.Storage.Persistent.isset('systeminformation')) {
			storedSystemInformationSettings = JSON.parse(TYPO3.Storage.Persistent.get('systeminformation'));
		}

		moduleStorageObject[requestedModule] = {lastAccess: timestamp};
		$.extend(true, storedSystemInformationSettings, moduleStorageObject);
		TYPO3.Storage.Persistent.set('systeminformation', JSON.stringify(storedSystemInformationSettings)).done(function() {
			// finally, open the module now
			TYPO3.ModuleMenu.App.showModule(requestedModule);
			SystemInformationMenu.updateMenu();
		});
	};

	/**
	 * Initialize and return the SystemInformationMenu object
	 */
	$(document).ready(function() {
		SystemInformationMenu.updateMenu();
	});

	TYPO3.SystemInformationMenu = SystemInformationMenu;
	return SystemInformationMenu;
});
