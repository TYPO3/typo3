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
 * contains all JS functions related to TYPO3 TCEforms/FormEngineValidation
 */
define('TYPO3/CMS/Backend/FormEngineValidation', ['jquery', 'TYPO3/CMS/Backend/FormEngine'], function ($, FormEngine) {

	/**
	 * the main FormEngineValidation object
	 *
	 * @type {{rulesSelector: string, dateTimeSelector: string, groupFieldHiddenElement: string, relatedFieldSelector: string}}
	 */
	var FormEngineValidation = {
		rulesSelector: '[data-formengine-validation-rules]',
		dateTimeSelector: '.t3js-datetimepicker',
		groupFieldHiddenElement: '.t3js-formengine-field-group input[type=hidden]',
		relatedFieldSelector: '[data-relatedfieldname]'
	};

	/**
	 * initialize validation for the first time
	 */
	FormEngineValidation.initialize = function() {
		$(document).find('.has-error').removeClass('has-error');

		// bind to field changes
		$(document).on('change', FormEngineValidation.rulesSelector, function() {
			// we need to wait, because the update of the select field needs some time
			window.setTimeout(function() {
				FormEngineValidation.validate();
			}, 500);
		});

		// bind to datepicker changes
		$(document).on('dp.change', FormEngineValidation.dateTimeSelector, function(event) {
			FormEngineValidation.validate();
		});

		if (typeof RTEarea !== 'undefined') {
			console.log(RTEarea);
		}
	};

	/**
	 * validate the complete form
	 */
	FormEngineValidation.validate = function() {
		$(document).find('.t3js-formengine-validation-marker, .t3js-tabmenu-item')
			.removeClass('has-error')
			.removeClass('has-validation-error');

		$(FormEngineValidation.rulesSelector).each(function() {
			var $field = $(this);
			var $rules = $field.data('formengine-validation-rules');
			var markParent = false;
			var selected = 0;
			$rules.each(function(rule) {
				switch (rule.type) {
					case 'required':
						if ($field.val() === '') {
							markParent = true;
							$field.closest('.t3js-formengine-validation-marker').addClass('has-error');
						}
						break;
					case 'range':
						if (rule.minItems || rule.maxItems) {
							$relatedField = $(document).find('[name="' + $field.data('relatedfieldname') + '"]');
							if ($relatedField.length) {
								selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
								if (selected < rule.minItems || selected > rule.maxItems) {
									markParent = true;
									$field.closest('.t3js-formengine-validation-marker').addClass('has-error');
								}
							} else {
								selected = $field.val();
								if (selected < rule.minItems || selected > rule.maxItems) {
									markParent = true;
									$field.closest('.t3js-formengine-validation-marker').addClass('has-error');
								}

							}
						}
						break;
					case 'select':
						if (rule.minItems || rule.maxItems) {
							$relatedField = $(document).find('[name="' + $field.data('relatedfieldname') + '"]');
							if ($relatedField.length) {
								selected = FormEngineValidation.trimExplode(',', $relatedField.val()).length;
								if (selected < rule.minItems || selected > rule.maxItems) {
									markParent = true;
									$field.closest('.t3js-formengine-validation-marker').addClass('has-error');
								}
							} else {
								selected = $field.find('option:selected').length;
								if (selected < rule.minItems || selected > rule.maxItems) {
									markParent = true;
									$field.closest('.t3js-formengine-validation-marker').addClass('has-error');
								}

							}
						}
						break;
					case 'group':
						if (rule.minItems || rule.maxItems) {
							selected = $field.find('option').length;
							if (selected < rule.minItems || selected > rule.maxItems) {
								markParent = true;
								$field.closest('.t3js-formengine-validation-marker').addClass('has-error');
							}
						}
						break;
					case 'inline':
						if (rule.minItems || rule.maxItems) {
							selected = FormEngineValidation.trimExplode(',', $field.val()).length;
							if (selected < rule.minItems || selected > rule.maxItems) {
								markParent = true;
								$field.closest('.t3js-formengine-validation-marker').addClass('has-error');
							}
						}
						break;
					default:
						FormEngineValidation.log('unknown validation type: ' + rule.type);
				}
			});
			if (markParent) {
				// check tabs
				FormEngineValidation.markParentTab($field);
			}
		});
	};

	/**
	 * helper function to get clean trimmed array from comma list
	 *
	 * @param delimiter
	 * @param string
	 * @returns {Array}
	 */
	FormEngineValidation.trimExplode = function(delimiter, string) {
		var result = [];
		var items = string.split(delimiter);
		for (var i=0; i<items.length; i++) {
			var item = items[i].trim();
			if (item.length > 0) {
				result.push(item);
			}
		}
		return result;
	};

	/**
	 * find tab by field and mark it as has-validation-error
	 *
	 * @param $element
	 */
	FormEngineValidation.markParentTab = function($element) {
		var $panes = $element.parents('.tab-pane');
		$panes.each(function() {
			var $pane = $(this);
			var id = $pane.attr('id');
			$(document)
				.find('a[href="#' + id + '"]')
				.closest('.t3js-tabmenu-item')
				.addClass('has-validation-error');
		});
	};

	/**
	 * helper function for console.log message
	 *
	 * @param msg
	 */
	FormEngineValidation.log = function(msg) {
		if (typeof console !== 'undefined') {
			console.log(msg);
		}
	};

	/**
	 * initialize function
	 */
	FormEngineValidation.initialize();
	// Start first validation after one second, because all fields are initial empty (typo3form.fieldSet)
	window.setTimeout(function() {
		FormEngineValidation.validate();
	}, 1000);

	FormEngine.Validation = FormEngineValidation;
});
