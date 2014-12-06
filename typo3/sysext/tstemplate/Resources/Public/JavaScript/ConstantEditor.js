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
 * Various functions related to the Constant Editor
 * e.g. updating the field and working with colors
 */
define('TYPO3/CMS/Tstemplate/ConstantEditor', ['jquery'], function($) {

	var ConstantEditor = {
		options: {
			editIconSelector: '.typo3-tstemplate-ceditor-control',
			colorSelectSelector: '.typo3-tstemplate-ceditor-color-select',
			colorInputSelector: '.typo3-tstemplate-ceditor-color-input'
		}
	};

	/**
	 * initially register event listeners
	 */
	ConstantEditor.changeProperty = function($editIcon) {
		var constantName = $editIcon.attr('rel');
		var $defaultDiv = $('#defaultTS-' + constantName);
		var $userDiv = $('#userTS-' + constantName);
		var $checkBox = $('#check-' + constantName);

		if ($editIcon.hasClass('editIcon')) {
			$defaultDiv.hide();
			$userDiv.show().css({background: '#fdf8bd'});
			$checkBox.attr('disabled', false).attr('checked', true);
		}

		if ($editIcon.hasClass('undoIcon')) {
			$userDiv.hide();
			$defaultDiv.show();
			$checkBox.val('').attr('disabled', true);
		}
	};

	/**
	 * updates the color from a dropdown
	 */
	ConstantEditor.updateColorFromSelect = function($colorSelect) {
		var constantName = $colorSelect.attr('rel');
		var colorValue = $colorSelect.val();

		$('#input-' + constantName).val(colorValue);
		$('#colorbox-' + constantName).css({background: colorValue});
	};

	/**
	 * updates the color from an input field
	 */
	ConstantEditor.updateColorFromInput = function($colorInput) {
		var constantName = $colorInput.attr('rel');
		var colorValue = $colorInput.val();

		$('#colorbox-' + constantName).css({background: colorValue});
		$('#select-' + constantName).children().each(function(option) {
			option.selected = (option.value === colorValue);
		});
	};

	/**
	 * Registers listeners
	 */
	ConstantEditor.initializeEvents = function() {
		$(document).on('click', ConstantEditor.options.editIconSelector, function() {
			ConstantEditor.changeProperty($(this));
		}).on('click', ConstantEditor.options.colorSelectSelector, function() {
			ConstantEditor.updateColorFromSelect($(this));
		}).on('click', ConstantEditor.options.colorInputSelector, function() {
			ConstantEditor.updateColorFromInput($(this));
		});
	};

	/**
	 * initialize and return the ConstantEditor object
	 */
	return function() {
		$(document).ready(function() {
			ConstantEditor.initializeEvents();
		});
		return ConstantEditor;
	}();
});