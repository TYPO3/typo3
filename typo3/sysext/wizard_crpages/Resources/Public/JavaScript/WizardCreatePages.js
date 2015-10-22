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
define(['jquery'], function($) {
	'use strict';

	var WizardCreatePages = {
		lineCounter: 5,
		containerSelector: '.t3js-wizardcrpages-container',
		addMoreFieldsButtonSelector: '.t3js-wizardcrpages-createnewfields',
		doktypeSelector: '.t3js-wizardcrpages-select-doktype'
	};

	WizardCreatePages.createNewFormFields = function() {
		for (var i = 0; i < 5; i++) {
			var label = WizardCreatePages.lineCounter + i + 1;
			var line = tpl
				.replace(/\{0\}/g, (WizardCreatePages.lineCounter + i))
				.replace(/\{1\}/g, label);

			$(line).appendTo(WizardCreatePages.containerSelector);
		}
		WizardCreatePages.lineCounter += 5;
	};

	WizardCreatePages.actOnTypeSelectChange = function($selectElement) {
		var $optionElement = $selectElement.find(':selected');
		var $target = $($selectElement.data('target'));
		$target.html($optionElement.data('icon'));
	};

	/**
	 * Register listeners
	 */
	WizardCreatePages.initializeEvents = function() {
		$(WizardCreatePages.addMoreFieldsButtonSelector).on('click', function() {
			WizardCreatePages.createNewFormFields();
		});

		$(document).on('change', WizardCreatePages.doktypeSelector, function() {
			WizardCreatePages.actOnTypeSelectChange($(this));
		});
	};

	$(WizardCreatePages.initializeEvents);

	return WizardCreatePages;
});
