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

import * as $ from 'jquery';
import 'datatables';
import SplitButtons = require('TYPO3/CMS/Backend/SplitButtons');

interface TableNumberMapping {
  [s: string]: number;
}
declare var defaultNumberOfDays: TableNumberMapping;

/**
 * Module: TYPO3/CMS/Scheduler/Scheduler
 * @exports TYPO3/CMS/Scheduler/Scheduler
 */
class Scheduler {
  private allCheckedStatus: boolean = false;

  constructor() {
    this.initializeEvents();
    this.initializeDefaultStates();

    SplitButtons.addPreSubmitCallback((): void => {
      let taskClass = $('#task_class').val();
      taskClass = taskClass.toLowerCase().replace(/\\/g, '-');

      $('.extraFields').appendTo($('#extraFieldsHidden'));
      $('.extra_fields_' + taskClass).appendTo($('#extraFieldsSection'));
    });
  }

  /**
   * This method reacts on changes to the task class
   * It switches on or off the relevant extra fields
   */
  public actOnChangedTaskClass = (theSelector: JQuery): void => {
    let taskClass: string = theSelector.val();
    taskClass = taskClass.toLowerCase().replace(/\\/g, '-');

    // Hide all extra fields
    $('.extraFields').hide();
    // Show only relevant extra fields
    $('.extra_fields_' + taskClass).show();
  }

  /**
   * This method reacts on changes to the type of a task, i.e. single or recurring
   */
  public actOnChangedTaskType = (evt: JQueryEventObject): void => {
    this.toggleFieldsByTaskType($(evt.currentTarget).val());
  }

  /**
   * This method reacts on field changes of all table field for table garbage collection task
   */
  public actOnChangeSchedulerTableGarbageCollectionAllTables = (theCheckbox: JQuery): void => {
    let $numberOfDays = $('#task_tableGarbageCollection_numberOfDays');
    let $taskTableGarbageCollectionTable = $('#task_tableGarbageCollection_table');
    if (theCheckbox.prop('checked')) {
      $taskTableGarbageCollectionTable.prop('disabled', true);
      $numberOfDays.prop('disabled', true);
    } else {
      // Get number of days for selected table
      let numberOfDays = parseInt($numberOfDays.val(), 10);
      if (numberOfDays < 1) {
        let selectedTable = $taskTableGarbageCollectionTable.val();
        if (typeof(defaultNumberOfDays[selectedTable]) !== 'undefined') {
          numberOfDays = defaultNumberOfDays[selectedTable];
        }
      }

      $taskTableGarbageCollectionTable.prop('disabled', false);
      if (numberOfDays > 0) {
        $numberOfDays.prop('disabled', false);
      }
    }
  }

  /**
   * This methods set the 'number of days' field to the default expire period
   * of the selected table
   */
  public actOnChangeSchedulerTableGarbageCollectionTable = (theSelector: JQuery): void => {
    let $numberOfDays = $('#task_tableGarbageCollection_numberOfDays');
    if (defaultNumberOfDays[theSelector.val()] > 0) {
      $numberOfDays.prop('disabled', false);
      $numberOfDays.val(defaultNumberOfDays[theSelector.val()]);
    } else {
      $numberOfDays.prop('disabled', true);
      $numberOfDays.val(0);
    }
  }

  /**
   * Check or uncheck all checkboxes
   */
  public checkOrUncheckAllCheckboxes = (theSelector: JQuery): boolean => {
    theSelector.parents('.tx_scheduler_mod1_table').find(':checkbox').prop('checked', !this.allCheckedStatus);
    this.allCheckedStatus = !this.allCheckedStatus;
    return false;
  }

  /**
   * Toggle the relevant form fields by task type
   */
  public toggleFieldsByTaskType = (taskType: number): void => {
    // Single task option = 1, Recurring task option = 2
    taskType = parseInt(taskType + '', 10);
    $('#task_end_col').toggle(taskType === 2);
    $('#task_frequency_row').toggle(taskType === 2);
  }

  /**
   * Toggle the visibility of task groups by clicking anywhere on the
   * task group header
   */
  public toggleTaskGroups = (theSelector: JQuery): void => {
    let taskGroup = theSelector.data('task-group-id');
    $('#recordlist-task-group-' + taskGroup).collapse('toggle');
  }

  /**
   * Registers listeners
   */
  public initializeEvents = (): void => {
    $('.checkall').on('click', (evt: JQueryEventObject): void => {
      this.checkOrUncheckAllCheckboxes($(evt.currentTarget));
    });

    $('#task_class').change((evt: JQueryEventObject): void => {
      this.actOnChangedTaskClass($(evt.currentTarget));
    });

    $('#task_type').change(this.actOnChangedTaskType);

    $('#task_tableGarbageCollection_allTables').change((evt: JQueryEventObject): void => {
      this.actOnChangeSchedulerTableGarbageCollectionAllTables($(evt.currentTarget));
    });

    $('#task_tableGarbageCollection_table').change((evt: JQueryEventObject): void => {
      this.actOnChangeSchedulerTableGarbageCollectionTable($(evt.currentTarget));
    });

    $('.taskGroup').on('click', (evt: JQueryEventObject): void => {
      this.toggleTaskGroups($(evt.currentTarget));
    });

    $('table.taskGroup-table').DataTable({
      'paging': false,
      'searching': false,
    });
  }

  /**
   * Initialize default states
   */
  public initializeDefaultStates = (): void => {
    let $taskType = $('#task_type');
    if ($taskType.length) {
      this.toggleFieldsByTaskType($taskType.val());
    }
    let $taskClass = $('#task_class');
    if ($taskClass.length) {
      this.actOnChangedTaskClass($taskClass);
    }
  }
}

export = new Scheduler();
