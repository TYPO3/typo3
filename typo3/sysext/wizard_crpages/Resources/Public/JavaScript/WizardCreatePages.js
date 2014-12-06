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

define('TYPO3/CMS/WizardCrpages/WizardCreatePages', ['jquery'], function($) {

	var WizardCreatePages = {};

	var lineCounter = 9;

	WizardCreatePages.createNewFormFields = function() {
		for (i = 0; i < 5; i++) {
			var label = lineCounter + i + 1;
			var line = String.format(tpl, (lineCounter + i), label);
			$(line).appendTo('#formFieldContainerBody');
		}
		lineCounter += 5;
	};

	WizardCreatePages.actOnTypeSelectChange = function(element) {
		var selectedElement = element.find(':selected');
		element.css('background-image', selectedElement.css('background-image'));
	};

	/**
	 * Register listeners
	 */
	WizardCreatePages.initializeEvents = function() {
		$('#createNewFormFields').on('click', function() {
			WizardCreatePages.createNewFormFields();
		});

		$('#type-select').change(function() {
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