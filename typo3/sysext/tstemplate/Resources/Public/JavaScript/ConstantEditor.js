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
 * Module: TYPO3/CMS/Tstemplate/ConstantEditor
 * Various functions related to the Constant Editor
 * e.g. updating the field and working with colors
 */
define(['jquery'], function($) {
	'use strict';

	/**
	 *
	 * @type {{options: {editIconSelector: string, colorSelectSelector: string, colorInputSelector: string}}}
	 * @exports TYPO3/CMS/Tstemplate/ConstantEditor
	 */
	var ConstantEditor = {
		options: {
			editIconSelector: '.t3js-toggle',
			colorSelectSelector: '.t3js-color-select',
			colorInputSelector: '.t3js-color-input'
		}
	};

	/**
	 * initially register event listeners
	 *
	 * @param {Object} $editIcon
	 */
	ConstantEditor.changeProperty = function($editIcon) {
		var constantName = $editIcon.attr('rel');
		var $defaultDiv = $('#defaultTS-' + constantName);
		var $userDiv = $('#userTS-' + constantName);
		var $checkBox = $('#check-' + constantName);
		var toggleState = $editIcon.data('toggle');

		if (toggleState === 'edit') {
			$defaultDiv.hide();
			$userDiv.show();
			$userDiv.find('input').css({background: '#fdf8bd'});
			$checkBox.attr('disabled', false).attr('checked', true);
		} else if (toggleState === 'undo') {
			$userDiv.hide();
			$defaultDiv.show();
			$checkBox.val('').attr('disabled', true);
		}
	};

	/**
	 * updates the color from a dropdown
	 *
	 * @param {Object} $colorSelect
	 */
	ConstantEditor.updateColorFromSelect = function($colorSelect) {
		var constantName = $colorSelect.attr('rel');
		var colorValue = $colorSelect.val();

		$('#input-' + constantName).val(colorValue);
		$('#colorbox-' + constantName).css({background: colorValue});
	};

	/**
	 * updates the color from an input field
	 *
	 * @param {Object} $colorInput
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
		// no DOMready needed since only events for document are registered
		$(document).on('click', ConstantEditor.options.editIconSelector, function() {
			ConstantEditor.changeProperty($(this));
		}).on('change', ConstantEditor.options.colorSelectSelector, function() {
			ConstantEditor.updateColorFromSelect($(this));
		}).on('blur', ConstantEditor.options.colorInputSelector, function() {
			ConstantEditor.updateColorFromInput($(this));
		});
	};

	ConstantEditor.initializeEvents();

	return ConstantEditor;
});
