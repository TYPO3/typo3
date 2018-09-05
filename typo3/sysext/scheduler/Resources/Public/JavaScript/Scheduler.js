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
 * Module: TYPO3/CMS/Scheduler/Scheduler
 */
define(['jquery',
  'TYPO3/CMS/Backend/SplitButtons',
  'datatables'
], function($, SplitButtons) {

  /**
   *
   * @type {{}}
   * @exports TYPO3/CMS/Scheduler/Scheduler
   */
  var Scheduler = {};

  var allCheckedStatus = false;

  /**
   * This method reacts on changes to the task class
   * It switches on or off the relevant extra fields
   *
   * @param {Object} theSelector
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
   * This method reacts on changes to the type of a task, i.e. single or recurring
   */
  Scheduler.actOnChangedTaskType = function() {
    Scheduler.toggleFieldsByTaskType($(this).val());
  };

  /**
   * This method reacts on field changes of all table field for table garbage collection task
   *
   * @param {Object} theCheckbox
   */
  Scheduler.actOnChangeSchedulerTableGarbageCollectionAllTables = function(theCheckbox) {
    var $numberOfDays = $('#task_tableGarbageCollection_numberOfDays');
    var $taskTableGarbageCollectionTable = $('#task_tableGarbageCollection_table');
    if (theCheckbox.prop('checked')) {
      $taskTableGarbageCollectionTable.prop('disabled', true);
      $numberOfDays.prop('disabled', true);
    } else {
      // Get number of days for selected table
      var numberOfDays = parseInt($numberOfDays.val());
      if (numberOfDays < 1) {
        var selectedTable = $taskTableGarbageCollectionTable.val();
        if (typeof(defaultNumberOfDays[selectedTable]) !== 'undefined') {
          numberOfDays = defaultNumberOfDays[selectedTable];
        }
      }

      $taskTableGarbageCollectionTable.prop('disabled', false);
      if (numberOfDays > 0) {
        $numberOfDays.prop('disabled', false);
      }
    }
  };

  /**
   * This methods set the 'number of days' field to the default expire period
   * of the selected table
   *
   * @param {Object} theSelector
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
   *
   * @param {Object} theSelector
   * @returns {Boolean}
   */
  Scheduler.checkOrUncheckAllCheckboxes = function(theSelector) {
    theSelector.parents('.tx_scheduler_mod1_table').find(':checkbox').prop('checked', !allCheckedStatus);
    allCheckedStatus = !allCheckedStatus;
    return false;
  };

  /**
   * Toggle the relevant form fields by task type
   *
   * @param {Integer} taskType
   */
  Scheduler.toggleFieldsByTaskType = function(taskType) {
    // Single task option = 1, Recurring task option = 2
    taskType = parseInt(taskType);
    $('#task_end_col').toggle(taskType === 2);
    $('#task_frequency_row').toggle(taskType === 2);
  };

  /**
   * Toggle the visibility of task groups by clicking anywhere on the
   * task group header
   *
   * @param {Object} theSelector
   */
  Scheduler.toggleTaskGroups = function(theSelector) {
    var taskGroup = theSelector.data('task-group-id');
    $('#recordlist-task-group-' + taskGroup).collapse('toggle');
  };

  /**
   * Registers listeners
   */
  Scheduler.initializeEvents = function() {
    $('.checkall').on('click', function() {
      Scheduler.checkOrUncheckAllCheckboxes($(this));
    });

    $('#task_class').change(function() {
      Scheduler.actOnChangedTaskClass($(this));
    });

    $('#task_type').change(Scheduler.actOnChangedTaskType);

    $('#task_tableGarbageCollection_allTables').change(function() {
      Scheduler.actOnChangeSchedulerTableGarbageCollectionAllTables($(this));
    });

    $('#task_tableGarbageCollection_table').change(function() {
      Scheduler.actOnChangeSchedulerTableGarbageCollectionTable($(this));
    });

    $('.taskGroup').on('click', function() {
      Scheduler.toggleTaskGroups($(this));
    });

    $('table.taskGroup-table').DataTable({
      "paging": false,
      "searching": false
    });
  };

  /**
   * Initialize default states
   */
  Scheduler.initializeDefaultStates = function() {
    var $taskType = $('#task_type');
    if ($taskType.length) {
      Scheduler.toggleFieldsByTaskType($taskType.val());
    }
    var $taskClass = $('#task_class');
    if ($taskClass.length) {
      Scheduler.actOnChangedTaskClass($taskClass);
    }
  };

  $(Scheduler.initializeEvents);
  $(Scheduler.initializeDefaultStates);

  SplitButtons.addPreSubmitCallback(function() {
    var taskClass = $('#task_class').val();
    taskClass = taskClass.toLowerCase().replace(/\\/g, '-');

    $('.extraFields').appendTo($('#extraFieldsHidden'));
    $('.extra_fields_' + taskClass).appendTo($('#extraFieldsSection'));
  });

  return Scheduler;
});
