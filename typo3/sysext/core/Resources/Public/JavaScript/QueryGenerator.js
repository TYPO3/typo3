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
 * Module: TYPO3/CMS/Core/QueryGenerator
 * This module handle the QueryGenerator forms.
 */
define(['jquery', 'TYPO3/CMS/Backend/DateTimePicker', 'TYPO3/CMS/Backend/jquery.clearable'], function ($) {

	/**
	 * The QueryGenerator AMD module
	 *
	 * @type {{form: null, limitField: null}}
	 * @exports TYPO3/CMS/Core/QueryGenerator
	 */
	var QueryGenerator = {
		form: null,
		limitField: null
	};

	/**
	 * Initialize the QueryGenerator object
	 */
	QueryGenerator.initialize = function() {
		QueryGenerator.form = $('form[name="queryform"]');
		QueryGenerator.limitField = $('#queryLimit');
		QueryGenerator.form.on('click', '.t3js-submit-click', function(e) {
			e.preventDefault();
			QueryGenerator.doSubmit();
		});
		QueryGenerator.form.on('change', '.t3js-submit-change', function(e) {
			e.preventDefault();
			QueryGenerator.doSubmit();
		});
		QueryGenerator.form.on('click', '.t3js-limit-submit button', function(e) {
			e.preventDefault();
			QueryGenerator.setLimit($(this).data('value'));
			QueryGenerator.doSubmit();
		});
		QueryGenerator.form.on('click', '.t3js-addfield', function(e) {
			e.preventDefault();
			QueryGenerator.addValueToField($(this).data('field'), $(this).val());
		});
		QueryGenerator.form.find('.t3js-clearable').clearable({
			onClear: function() {
				QueryGenerator.doSubmit();
			}
		});
	};

	/**
	 * Submit the form
	 */
	QueryGenerator.doSubmit = function() {
		QueryGenerator.form.submit();
	};

	/**
	 * Set query limit
	 *
	 * @param {String} value
	 */
	QueryGenerator.setLimit = function(value) {
		QueryGenerator.limitField.val(value);
	};

	/**
	 * Add value to text field
	 *
	 * @param {String} field the name of the field
	 * @param {String} value the value to add
	 */
	QueryGenerator.addValueToField = function(field, value) {
		var $target = QueryGenerator.form.find('[name="' + field + '"]');
		var currentValue = $target.val();
		$target.val(currentValue + ',' + value);
	};

	// Initialize
	QueryGenerator.initialize();
	return QueryGenerator;
});
