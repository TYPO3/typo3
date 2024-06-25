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

import SortableTable from '@typo3/backend/sortable-table';
import DocumentSaveActions from '@typo3/backend/document-save-actions';
import RegularEvent from '@typo3/core/event/regular-event';
import Modal from '@typo3/backend/modal';
import Icons from '@typo3/backend/icons';
import { MessageUtility } from '@typo3/backend/utility/message-utility';
import { ActionEventDetails } from '@typo3/backend/multi-record-selection-action';
import PersistentStorage from '@typo3/backend/storage/persistent';
import DateTimePicker from '@typo3/backend/date-time-picker';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';
import Severity from '@typo3/backend/severity';
import DocumentService from '@typo3/core/document-service';

interface TableNumberMapping {
  [s: string]: number;
}

/**
 * Module: @typo3/scheduler/scheduler
 * @exports @typo3/scheduler/scheduler
 */
class Scheduler {
  constructor() {
    DocumentService.ready().then((): void => {
      this.initializeEvents();
      this.initializeDefaultStates();
      this.initializeCloseConfirm();
    });

    DocumentSaveActions.getInstance().addPreSubmitCallback((): void => {
      // Move all hidden extra fields out of the form
      // @todo: perhaps delete them instead?
      document.querySelector('#extraFieldsHidden').append(...document.querySelectorAll('.extraFields[hidden]'));
    });
  }

  private static updateClearableInputs(): void {
    const clearables = document.querySelectorAll('.t3js-clearable') as NodeListOf<HTMLInputElement>;
    if (clearables.length > 0) {
      import('@typo3/backend/input/clearable').then(function() {
        clearables.forEach(clearableField => clearableField.clearable());
      });
    }
  }

  private static updateDateTimePickers(): void {
    (document.querySelectorAll('#tx_scheduler_form .t3js-datetimepicker') as NodeListOf<HTMLInputElement>).forEach(
      (dateTimePickerElement: HTMLInputElement) => DateTimePicker.initialize(dateTimePickerElement)
    );
  }

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
    return JSON.parse(element.dataset.defaultNumberOfDays) as TableNumberMapping;
  }

  /**
   * Store task group collapse state in UC
   */
  private static storeCollapseState(table: string, isCollapsed: boolean): void {
    let storedModuleData = {};

    if (PersistentStorage.isset('moduleData.scheduler_manage')) {
      storedModuleData = PersistentStorage.get('moduleData.scheduler_manage');
    }

    const collapseConfig: Record<string, number> = {};
    collapseConfig[table] = isCollapsed ? 1 : 0;

    storedModuleData = { ...storedModuleData, ...collapseConfig };
    PersistentStorage.set('moduleData.scheduler_manage', storedModuleData);
  }

  /**
   * This method reacts on changes to the task class
   * It switches on or off the relevant extra fields
   */
  public actOnChangedTaskClass(taskSelector: HTMLSelectElement): void {
    let taskClass: string = taskSelector.value;
    taskClass = taskClass.toLowerCase().replace(/\\/g, '-');

    // Show only relevant extra fields
    for (const extraField of document.querySelectorAll('.extraFields') as NodeListOf<HTMLElement>) {
      extraField.hidden = !extraField.classList.contains('extra_fields_' + taskClass);
    }
  }

  /**
   * This method reacts on field changes of all table field for table garbage collection task
   */
  public actOnChangeSchedulerTableGarbageCollectionAllTables(checkbox: HTMLInputElement): void {
    const numberOfDaysField = document.querySelector('#task_tableGarbageCollection_numberOfDays') as HTMLInputElement;
    const taskTableGarbageCollectionTableField = document.querySelector('#task_tableGarbageCollection_table') as HTMLSelectElement;

    if (checkbox.checked) {
      taskTableGarbageCollectionTableField.disabled = true;
      numberOfDaysField.disabled = true;
    } else {
      // Get number of days for selected table
      let numberOfDays = parseInt(numberOfDaysField.value, 10);
      if (numberOfDays < 1) {
        const selectedTable = taskTableGarbageCollectionTableField.value;
        const defaultNumberOfDays = Scheduler.resolveDefaultNumberOfDays();
        if (defaultNumberOfDays !== null) {
          numberOfDays = defaultNumberOfDays[selectedTable];
        }
      }

      taskTableGarbageCollectionTableField.disabled = false;
      if (numberOfDays > 0) {
        numberOfDaysField.disabled = false;
      }
    }
  }

  /**
   * This method set the 'number of days' field to the default expire period
   * of the selected table
   */
  public actOnChangeSchedulerTableGarbageCollectionTable(tableSelector: HTMLSelectElement): void {
    const numberOfDaysField = document.querySelector('#task_tableGarbageCollection_numberOfDays') as HTMLInputElement;
    const defaultNumberOfDays = Scheduler.resolveDefaultNumberOfDays();
    if (defaultNumberOfDays !== null && defaultNumberOfDays[tableSelector.value] > 0) {
      numberOfDaysField.disabled = false;
      numberOfDaysField.value = defaultNumberOfDays[tableSelector.value].toString(10);
    } else {
      numberOfDaysField.disabled = true;
      numberOfDaysField.value = '0';
    }
  }

  /**
   * Toggle the relevant form fields by task type
   */
  public toggleFieldsByTaskType(taskType: string|number): void {
    // Single task option = 1, Recurring task option = 2
    taskType = parseInt(taskType + '', 10);
    const taskIsRecurring = taskType === 2;
    (document.querySelector('#task_end_col') as HTMLElement).hidden = !taskIsRecurring;
    (document.querySelector('#task_frequency_row') as HTMLElement).hidden = !taskIsRecurring;
    (document.querySelector('#task_multiple_row') as HTMLElement).hidden = !taskIsRecurring;
  }

  /**
   * Registers listeners
   */
  public initializeEvents(): void {
    const taskClassElement = document.querySelector('#task_class');
    if (taskClassElement) {
      new RegularEvent('change', (evt: Event): void => {
        this.actOnChangedTaskClass(evt.target as HTMLSelectElement);
      }).bindTo(taskClassElement);
    }

    const taskTypeElement = document.querySelector('#task_type');
    if (taskTypeElement) {
      new RegularEvent('change', (evt: Event): void => {
        this.toggleFieldsByTaskType((evt.target as HTMLSelectElement).value);
      }).bindTo(taskTypeElement);
    }

    const taskTableGarbageCollectionAllTablesElement = document.querySelector('#task_tableGarbageCollection_allTables');
    if (taskTableGarbageCollectionAllTablesElement) {
      new RegularEvent('change', (evt: Event): void => {
        this.actOnChangeSchedulerTableGarbageCollectionAllTables(evt.target as HTMLInputElement);
      }).bindTo(taskTableGarbageCollectionAllTablesElement);
    }

    const taskTableGarbageCollectionTableElement = document.querySelector('#task_tableGarbageCollection_table');
    if (taskTableGarbageCollectionTableElement) {
      new RegularEvent('change', (evt: Event): void => {
        this.actOnChangeSchedulerTableGarbageCollectionTable(evt.target as HTMLSelectElement);
      }).bindTo(taskTableGarbageCollectionTableElement);
    }

    const updateTaskFrequencyElement = document.querySelector('[data-update-task-frequency]');
    if (updateTaskFrequencyElement) {
      new RegularEvent('change', (evt: Event): void => {
        const target = evt.target as HTMLSelectElement;
        const taskFrequencyField = document.querySelector('#task_frequency') as HTMLInputElement;

        taskFrequencyField.value = target.value;
        target.value = '';
        target.blur();
      }).bindTo(updateTaskFrequencyElement);
    }

    document.querySelectorAll('[data-scheduler-table]').forEach((table: HTMLTableElement) => {
      new SortableTable(table);
    });

    new RegularEvent('click', (e: Event, target: HTMLAnchorElement): void => {
      e.preventDefault();

      const url = new URL(target.href, window.origin);
      url.searchParams.set('mode', target.dataset.mode);
      url.searchParams.set('bparams', target.dataset.params);

      Modal.advanced({
        type: Modal.types.iframe,
        content: url.toString(),
        size: Modal.sizes.large
      });
    }).delegateTo(document, '.t3js-element-browser');

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
    const taskType = document.querySelector('#task_type') as HTMLSelectElement;
    if (taskType !== null) {
      this.toggleFieldsByTaskType(taskType.value);
    }
    const taskClass = document.querySelector('#task_class') as HTMLSelectElement;
    if (taskClass !== null) {
      this.actOnChangedTaskClass(taskClass);
      Scheduler.updateClearableInputs();
      Scheduler.updateDateTimePickers();
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

      const field = <HTMLInputElement>document.querySelector('input[name="' + e.data.fieldName + '"]');
      field.value = e.data.value.split('_').pop();
    }
  }

  private toggleCollapseIcon(e: Event): void {
    const isCollapsed: boolean = e.type === 'hide.bs.collapse';
    const collapseIcon: HTMLElement = document.querySelector('.t3js-toggle-table[data-bs-target="#' + (e.target as HTMLElement).id + '"] .t3js-icon');
    if (collapseIcon !== null) {
      Icons
        .getIcon((isCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'), Icons.sizes.small)
        .then((icon: string): void => {
          collapseIcon.replaceWith(document.createRange().createContextualFragment(icon));
        });
    }
    Scheduler.storeCollapseState((e.target as HTMLElement).dataset.table, isCollapsed);
  }

  private executeTasks(event: CustomEvent): void {
    const form: HTMLFormElement = document.querySelector('[data-multi-record-selection-form="' + event.detail.identifier + '"]');
    if (form === null) {
      return;
    }
    const taskIds: Array<string> = [];
    ((event.detail as ActionEventDetails).checkboxes as NodeListOf<HTMLInputElement>).forEach((checkbox: HTMLInputElement) => {
      const checkboxContainer: HTMLElement = checkbox.closest(MultiRecordSelectionSelectors.elementSelector);
      if (checkboxContainer !== null && checkboxContainer.dataset.taskId) {
        taskIds.push(checkboxContainer.dataset.taskId);
      }
    });
    if (taskIds.length) {
      if (event.type === 'multiRecordSelection:action:go_cron') {
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

  private initializeCloseConfirm() {
    const schedulerForm: HTMLFormElement = document.querySelector('form[name=tx_scheduler_form]');
    if(!schedulerForm) {
      return;
    }

    const formData = new FormData(schedulerForm);

    new RegularEvent('click', (e: Event): void => {
      const newFormData = new FormData(schedulerForm)
      const formDataObj = Object.fromEntries(formData.entries());
      const newFormDataObj = Object.fromEntries(newFormData.entries());
      const formChanged = JSON.stringify(formDataObj) !== JSON.stringify(newFormDataObj)

      if (formChanged || schedulerForm.querySelector('input[value="add"]')) {
        e.preventDefault();
        const closeUrl = (e.currentTarget as HTMLLinkElement).href
        Modal.confirm(
          TYPO3.lang['label.confirm.close_without_save.title'] || 'Do you want to close without saving?',
          TYPO3.lang['label.confirm.close_without_save.content'] || 'You currently have unsaved changes. Are you sure you want to discard these changes?',
          Severity.warning,
          [
            {
              text: TYPO3.lang['buttons.confirm.close_without_save.no'] || 'No, I will continue editing',
              btnClass: 'btn-default',
              name: 'no',
              trigger: () => Modal.dismiss(),
            },
            {
              text: TYPO3.lang['buttons.confirm.close_without_save.yes'] || 'Yes, discard my changes',
              btnClass: 'btn-default',
              name: 'yes',
              trigger: () => {
                Modal.dismiss();
                window.location.href = closeUrl;
              }
            },
            {
              text: TYPO3.lang['buttons.confirm.save_and_close'] || 'Save and close',
              btnClass: 'btn-primary',
              name: 'save',
              active: true,
              trigger: () => {
                Modal.dismiss();

                const hidden = document.createElement('input')
                hidden.type = 'hidden';
                hidden.value = 'saveclose';
                hidden.name = 'CMD';

                schedulerForm.append(hidden)
                schedulerForm.submit();
              },
            }
          ]
        );
      }
    }).bindTo(document.querySelector('.t3js-scheduler-close'));
  }
}

export default new Scheduler();
