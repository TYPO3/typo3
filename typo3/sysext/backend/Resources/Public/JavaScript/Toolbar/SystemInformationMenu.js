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
define('TYPO3/CMS/Backend/Toolbar/SystemInformationMenu', ['jquery'], function($) {

	var SystemInformationMenu = {
		identifier: {
			containerSelector: '#typo3-cms-backend-backend-toolbaritems-systeminformationtoolbaritem',
			toolbarIconSelector: '.dropdown-toggle span.t3-icon',
			menuContainerSelector: '.dropdown-menu'
		},
		elements: {
			$counter: $('#t3js-systeminformation-counter'),
			$spinnerElement: $('<span>', {
				'class': 't3-icon fa fa-circle-o-notch spinner fa-spin'
			})
		}
	};

	/**
	 * Updates the menu
	 */
	SystemInformationMenu.updateMenu = function() {
		var $toolbarItemIcon = $(SystemInformationMenu.identifier.toolbarIconSelector, SystemInformationMenu.identifier.containerSelector),
			$spinnerIcon = SystemInformationMenu.elements.$spinnerElement.clone(),
			$existingIcon = $toolbarItemIcon.replaceWith($spinnerIcon);

		$.ajax({
			url: TYPO3.settings.ajaxUrls['SystemInformationMenu::load'],
			type: 'post',
			cache: false,
			success: function(data) {
				$(SystemInformationMenu.identifier.containerSelector).find(SystemInformationMenu.identifier.menuContainerSelector).html(data);
				SystemInformationMenu.updateCounter();
				$spinnerIcon.replaceWith($existingIcon);
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
	 * Initialize and return the SystemInformationMenu object
	 */
	$(document).ready(function() {
		SystemInformationMenu.updateMenu();
	});

	TYPO3.SystemInformationMenu = SystemInformationMenu;
	return SystemInformationMenu;
});
