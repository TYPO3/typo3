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

import $ from 'jquery';
import Tablesort from 'tablesort';
import DocumentSaveActions from '@typo3/backend/document-save-actions';
import RegularEvent from '@typo3/core/event/regular-event';
import Modal from '@typo3/backend/modal';
import Icons from '@typo3/backend/icons';
import { MessageUtility } from '@typo3/backend/utility/message-utility';
import {ActionEventDetails} from '@typo3/backend/multi-record-selection-action';
import PersistentStorage from '@typo3/backend/storage/persistent';
import DateTimePicker from '@typo3/backend/date-time-picker';

interface TableNumberMapping {
  [s: string]: number;
}

/**
 * Module: @typo3/scheduler/scheduler
 * @exports @typo3/scheduler/scheduler
 */
class Scheduler {
  private static updateElementBrowserTriggers(): void {
    const triggers = document.querySelectorAll('.t3js-element-browser');

    triggers.forEach((el: HTMLAnchorElement): void => {
      const triggerField = <HTMLInputElement>document.getElementById(el.dataset.triggerFor);
      el.dataset.params = triggerField.name + '|||pages';
    });
  }

  private static resolveDefaultNumberOfDays(): TableNumberMapping|null {
    const element = document.getElementById('task_tableGarbageCollection_numberOfDays');
    if (element === null || typeof element.dataset.defaultNumberOfDays === 'undefined') {
      return null;
    }
    return JSON.parse(element.dataset.defaultNumberOfDays) as TableNumberMapping
  }

  /**
   * Store task group collapse state in UC
   */
  private static storeCollapseState(table: string, isCollapsed: boolean): void {
    let storedModuleData = {};

    if (PersistentStorage.isset('moduleData.scheduler')) {
      storedModuleData = PersistentStorage.get('moduleData.scheduler');
    }

    const collapseConfig: any = {};
    collapseConfig[table] = isCollapsed ? 1 : 0;

    $.extend(storedModuleData, collapseConfig);
    PersistentStorage.set('moduleData.scheduler', storedModuleData);
  }

  constructor() {
    this.initializeEvents();
    this.initializeDefaultStates();

    DocumentSaveActions.getInstance().addPreSubmitCallback((): void => {
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
  public actOnChangedTaskClass(theSelector: JQuery): void {
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
  public actOnChangedTaskType(evt: JQueryEventObject): void {
    this.toggleFieldsByTaskType($(evt.currentTarget).val());
  }

  /**
   * This method reacts on field changes of all table field for table garbage collection task
   */
  public actOnChangeSchedulerTableGarbageCollectionAllTables(theCheckbox: JQuery): void {
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
        const defaultNumberOfDays = Scheduler.resolveDefaultNumberOfDays();
        if (defaultNumberOfDays !== null) {
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
  public actOnChangeSchedulerTableGarbageCollectionTable(theSelector: JQuery): void {
    let $numberOfDays = $('#task_tableGarbageCollection_numberOfDays');
    const defaultNumberOfDays = Scheduler.resolveDefaultNumberOfDays();
    if (defaultNumberOfDays !== null && defaultNumberOfDays[theSelector.val()] > 0) {
      $numberOfDays.prop('disabled', false);
      $numberOfDays.val(defaultNumberOfDays[theSelector.val()]);
    } else {
      $numberOfDays.prop('disabled', true);
      $numberOfDays.val(0);
    }
  }

  /**
   * Toggle the relevant form fields by task type
   */
  public toggleFieldsByTaskType(taskType: number): void {
    // Single task option = 1, Recurring task option = 2
    taskType = parseInt(taskType + '', 10);
    $('#task_end_col').toggle(taskType === 2);
    $('#task_frequency_row').toggle(taskType === 2);
  }

  /**
   * Registers listeners
   */
  public initializeEvents(): void {
    $('#task_class').on('change', (evt: JQueryEventObject): void => {
      this.actOnChangedTaskClass($(evt.currentTarget));
    });

    $('#task_type').on('change', this.actOnChangedTaskType.bind(this));

    $('#task_tableGarbageCollection_allTables').on('change', (evt: JQueryEventObject): void => {
      this.actOnChangeSchedulerTableGarbageCollectionAllTables($(evt.currentTarget));
    });

    $('#task_tableGarbageCollection_table').on('change', (evt: JQueryEventObject): void => {
      this.actOnChangeSchedulerTableGarbageCollectionTable($(evt.currentTarget));
    });

    $('[data-update-task-frequency]').on('change', (evt: JQueryEventObject): void => {
      const $target = $(evt.currentTarget);
      const $taskFrequency = $('#task_frequency');
      $taskFrequency.val($target.val());
      $target.val($target.attr('value')).trigger('blur');
    });

    const taskGroupTable = document.querySelector('table.taskGroup-table');
    if (taskGroupTable !== null) {
      new Tablesort(taskGroupTable);
    }

    (<NodeListOf<HTMLInputElement>>document.querySelectorAll('#tx_scheduler_form .t3js-datetimepicker')).forEach(
      (dateTimePickerElement: HTMLInputElement) => DateTimePicker.initialize(dateTimePickerElement)
    );

    $(document).on('click', '.t3js-element-browser', (e: JQueryEventObject): void => {
      e.preventDefault();

      const el = <HTMLAnchorElement>e.currentTarget;
      Modal.advanced({
        type: Modal.types.iframe,
        content: el.href + '&mode=' + el.dataset.mode + '&bparams=' + el.dataset.params,
        size: Modal.sizes.large
      });
    });

    new RegularEvent('show.bs.collapse', this.toggleCollapseIcon.bind(this)).bindTo(document);
    new RegularEvent('hide.bs.collapse', this.toggleCollapseIcon.bind(this)).bindTo(document);
    new RegularEvent('multiRecordSelection:action:go', this.executeTasks.bind(this)).bindTo(document);
    new RegularEvent('multiRecordSelection:action:go_cron', this.executeTasks.bind(this)).bindTo(document);

    window.addEventListener('message', this.listenOnElementBrowser.bind(this));
  }

  /**
   * Initialize default states
   */
  public initializeDefaultStates(): void {
    let $taskType = $('#task_type');
    if ($taskType.length) {
      this.toggleFieldsByTaskType($taskType.val());
    }
    let $taskClass = $('#task_class');
    if ($taskClass.length) {
      this.actOnChangedTaskClass($taskClass);
      Scheduler.updateElementBrowserTriggers();
    }
  }

  private listenOnElementBrowser(e: MessageEvent): void {
    if (!MessageUtility.verifyOrigin(e.origin)) {
      throw 'Denied message sent by ' + e.origin;
    }

    if (e.data.actionName === 'typo3:elementBrowser:elementAdded') {
      if (typeof e.data.fieldName === 'undefined') {
        throw 'fieldName not defined in message';
      }

      if (typeof e.data.value === 'undefined') {
        throw 'value not defined in message';
      }

      const result = e.data.value.split('_');
      const field = <HTMLInputElement>document.querySelector('input[name="' + e.data.fieldName + '"]');
      field.value = result[1];
    }
  }

  private toggleCollapseIcon(e: Event): void {
    const isCollapsed: boolean = e.type === 'hide.bs.collapse';
    const collapseIcon: HTMLElement = document.querySelector('.t3js-toggle-table[data-bs-target="#' + (e.target as HTMLElement).id + '"] .collapseIcon');
    if (collapseIcon !== null) {
      Icons
        .getIcon((isCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'), Icons.sizes.small)
        .then((icon: string): void => {
          collapseIcon.innerHTML = icon;
        });
    }
    Scheduler.storeCollapseState($(e.target).data('table'), isCollapsed);
  }

  private executeTasks(e: CustomEvent): void {
    const form: HTMLFormElement = document.querySelector('#tx_scheduler_form');
    if (form === null) {
      return;
    }
    const taskIds: Array<string> = [];
    ((e.detail as ActionEventDetails).checkboxes as NodeListOf<HTMLInputElement>).forEach((checkbox: HTMLInputElement) => {
      const checkboxContainer: HTMLElement = checkbox.closest('tr');
      if (checkboxContainer !== null && checkboxContainer.dataset.taskId) {
        taskIds.push(checkboxContainer.dataset.taskId);
      }
    });
    if (taskIds.length) {
      if (e.type === 'multiRecordSelection:action:go_cron') {
        // Schedule selected tasks for next cron run
        const goCron: HTMLInputElement = document.createElement('input');
        goCron.setAttribute('type', 'hidden');
        goCron.setAttribute('name', 'scheduleCron');
        goCron.setAttribute('value', taskIds.join(','));
        form.append(goCron);
      } else {
        // Execute selected tasks directly
        const executeTasks: HTMLInputElement = document.createElement('input');
        executeTasks.setAttribute('type', 'hidden');
        executeTasks.setAttribute('name', 'execute');
        executeTasks.setAttribute('value', taskIds.join(','));
        form.append(executeTasks);
      }

      form.submit();
    }
  }
}

export default new Scheduler();
