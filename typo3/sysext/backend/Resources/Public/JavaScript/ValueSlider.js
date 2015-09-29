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
 * ValueSlider
 */
define('TYPO3/CMS/Backend/ValueSlider', ['jquery', 'twbs/bootstrap-slider'], function($) {
	/**
	 * ValueSlider object
	 *
	 * @type {{selector: string}}
	 */
	var ValueSlider = {
		selector: '[data-slider-id]'
	};

	/**
	 * initialize all slider elements
	 */
	ValueSlider.initializeSlider = function() {
		var $sliders = $(ValueSlider.selector);
		if ($sliders.length > 0) {
			$sliders.slider({
				formatter: ValueSlider.renderTooltipValue
			});
			$sliders.on('slide', ValueSlider.updateValue);
		}
	};

	/**
	 * update value of slider element
	 *
	 * @param {Event} e
	 */
	ValueSlider.updateValue = function(e) {
		var $slider = $(e.currentTarget),
			$foreignField = $('[data-formengine-input-name="' + $slider.data('sliderItemName') + '"]'),
			elementType = $slider.data('sliderElementType'),
			sliderField = $slider.data('sliderField'),
			sliderCallback = $slider.data('sliderCallback');

		switch (elementType) {
			case 'input':
				$foreignField.val(e.value);
				break;
			case 'select':
				$foreignField.find('option').eq(e.value).prop('selected', true);
				break;
		}

		if (sliderField) {
			eval(sliderField);
		}

		if (sliderCallback) {
			eval(sliderCallback);
		}
	};

	/**
	 *
	 * @param {Number} value
	 * @returns {*}
	 */
	ValueSlider.renderTooltipValue = function(value) {
		var renderedValue,
			$slider = $('[data-slider-id="' + $(this).get(0).id + '"]'),
			data = $slider.data();
		switch (data.sliderValueType) {
			case 'array':
				var $foreignField = $('[data-formengine-input-name="' + data.sliderItemName + '"]');
				renderedValue = $foreignField.find('option').eq(value).text();
				break;
			case 'double':
				renderedValue = parseFloat(value).toFixed(2);
				break;
			case 'int':
			default:
				renderedValue = parseInt(value);
		}

		return renderedValue;
	};

	// init if document is ready
	$(function() {
		ValueSlider.initializeSlider();
	});

	return ValueSlider;
});
