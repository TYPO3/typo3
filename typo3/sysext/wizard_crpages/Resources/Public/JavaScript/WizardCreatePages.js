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
 * JavaScript functions for creating multiple pages
 */
define('TYPO3/CMS/WizardCrpages/WizardCreatePages', ['jquery'], function($) {

	var WizardCreatePages = {
		lineCounter: 9,
		containerSelector: '#formFieldContainerBody',
		addMoreFieldsButtonSelector: '#createNewFormFields'
	};

	WizardCreatePages.createNewFormFields = function() {
		for (i = 0; i < 5; i++) {
			var label = this.lineCounter + i + 1;
			var line = String.format(tpl, (this.lineCounter + i), label);
			$(line).appendTo(this.containerSelector);
		}
		this.lineCounter += 5;
	};

	WizardCreatePages.actOnTypeSelectChange = function($selectElement) {
		var $optionElement = $selectElement.find(':selected');
		$selectElement.css('background-image', $optionElement.css('background-image'));
	};

	/**
	 * Register listeners
	 */
	WizardCreatePages.initializeEvents = function() {
		$(this.addMoreFieldsButtonSelector).on('click', function() {
			WizardCreatePages.createNewFormFields();
		});

		$(document).on('change', '.icon-select', function() {
			WizardCreatePages.actOnTypeSelectChange($(this));
		});
	};

	return function() {
		$(document).ready(function() {
			WizardCreatePages.initializeEvents();
		});

		TYPO3.WizardCreatePages = WizardCreatePages;
		return WizardCreatePages;
	}();
});
