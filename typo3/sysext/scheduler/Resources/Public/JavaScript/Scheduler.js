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

define('TYPO3/CMS/Scheduler/Scheduler', ['jquery'], function($) {

	var Scheduler = {};

	var allCheckedStatus = false;

	/**
	 * This method reacts on changes to the task class
	 * It switches on or off the relevant extra fields
	 */
	Scheduler.actOnChangedTaskClass = function(theSelector) {
		var taskClass = theSelector.val();
		taskClass = taskClass.toLowerCase().replace(/\\/g, '-');

		// Hide all extra fields
		$('.extraFields').hide();
		// Show only relevant extra fields
		$('.extra_fields_' + taskClass).show();
	};

	/**
	 * This method reacts on changes to the type of a task, i.e. single or recurring,
	 * by showing or hiding the relevant form fields
	 */
	Scheduler.actOnChangedTaskType = function(theSelector) {
		// Get task type from selected value, or set default value
		// Single taskType = 1, Recurring task = 0
		var taskType = parseInt(theSelector.val()) == 1 ? 0 : 1;
		$('#task_end_row').toggle(taskType);
		$('#task_frequency_row').toggle(taskType);
		$('#task_multiple_row').toggle(taskType);
	};

	/**
	 * This method reacts on field changes of all table field for table garbage collection task
	 */
	Scheduler.actOnChangeSchedulerTableGarbageCollectionAllTables = function(theCheckbox) {
		var $numberOfDays = $('#task_tableGarbageCollection_numberOfDays');
		if (theCheckbox.prop('checked')) {
			$('#task_tableGarbageCollection_table').prop('disabled', true);
			$numberOfDays.prop('disabled', true);
		} else {
			// Get number of days for selected table
			var numberOfDays = parseInt($numberOfDays.val());
			if (numberOfDays < 1) {
				var selectedTable = $('#task_tableGarbageCollection_table').val();
				if (typeof(defaultNumberOfDays[selectedTable]) != 'undefined') {
					numberOfDays = defaultNumberOfDays[selectedTable];
				}
			}

			$('#task_tableGarbageCollection_table').prop('disabled', false);
			if (numberOfDays > 0) {
				$numberOfDays.prop('disabled', false);
			}
		}
	};

	/**
	 * This methods set the 'number of days' field to the default expire period
	 * of the selected table
	 */
	Scheduler.actOnChangeSchedulerTableGarbageCollectionTable = function(theSelector) {
		var $numberOfDays = $('#task_tableGarbageCollection_numberOfDays');
		if (defaultNumberOfDays[theSelector.val()] > 0) {
			$numberOfDays.prop('disabled', false);
			$numberOfDays.val(defaultNumberOfDays[theSelector.val()]);
		} else {
			$numberOfDays.prop('disabled', true);
			$numberOfDays.val(0);
		}
	};

	/**
	 * Check or uncheck all checkboxes
	 */
	Scheduler.checkOrUncheckAllCheckboxes = function(theSelector) {
		theSelector.parent().parent().parent().parent().find(':checkbox').prop('checked', !allCheckedStatus);
		allCheckedStatus = !allCheckedStatus;
		return false;
	};

	/**
	 * Change icon when running a single task
	 */
	Scheduler.runSingleTask = function(theSelector) {
		var checkbox = theSelector.parent().parent().parent().find(':checkbox');
		var idParts = checkbox.attr('id').split('_');
		$('#executionstatus_' + idParts[1]).attr('src', TYPO3.settings.scheduler.runningIcon);
	}

	/**
	 * Handle click event on a table row
	 */
	Scheduler.handleTableRowClick = function(theSelector, event) {
		var checkbox = theSelector.find('input.checkboxes');
		if (!$(event.target).is('input')) {
			if (checkbox.prop('checked')) {
				checkbox.prop('checked', false);
			} else {
				checkbox.prop('checked', true);
			}
		}
	}

	/**
	 * Execute selected task(s)
	 */
	Scheduler.executeSelected = function() {
		// Set the status icon all to same status: running
		$('.checkboxes:checked').each(function(index) {
			var idParts = $(this).attr('id').split('_');
			$('#executionstatus_' + idParts[1]).attr('src', TYPO3.settings.scheduler.runningIcon);
		});
	}

	/**
	 * Registers listeners
	 */
	Scheduler.initializeEvents = function() {
		$('#scheduler_executeselected').on('click', function() {
			Scheduler.executeSelected();
		});

		$('.tx_scheduler_mod1 tbody tr').on('click', function(event) {
			Scheduler.handleTableRowClick($(this), event);
		});

		$('.fa-play-circle').on('click', function() {
			Scheduler.runSingleTask($(this));
		});

		$('#checkall').on('click', function() {
			Scheduler.checkOrUncheckAllCheckboxes($(this));
		});

		$('#task_class').change(function() {
			Scheduler.actOnChangedTaskClass($(this));
		});

		$('#task_type').change(function() {
			Scheduler.actOnChangedTaskType($(this));
		});

		$('#task_tableGarbageCollection_allTables').change(function() {
			Scheduler.actOnChangeSchedulerTableGarbageCollectionAllTables($(this));
		});

		$('#task_tableGarbageCollection_table').change(function() {
			Scheduler.actOnChangeSchedulerTableGarbageCollectionTable($(this));
		});
	};

	// intialize and return the Scheduler object
	return function() {
		$(document).ready(function() {
			Scheduler.initializeEvents();
		});

		TYPO3.Scheduler = Scheduler;
		return Scheduler;
	}();
});