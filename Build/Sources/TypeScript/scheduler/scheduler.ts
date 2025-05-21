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
import RegularEvent from '@typo3/core/event/regular-event';
import Icons from '@typo3/backend/icons';
import type { ActionEventDetails } from '@typo3/backend/multi-record-selection-action';
import PersistentStorage from '@typo3/backend/storage/persistent';
import { MultiRecordSelectionSelectors } from '@typo3/backend/multi-record-selection';
import DocumentService from '@typo3/core/document-service';

/**
 * Module: @typo3/scheduler/scheduler
 * @exports @typo3/scheduler/scheduler
 */
class Scheduler {
  constructor() {
    DocumentService.ready().then((): void => {
      this.initializeEvents();
    });
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
   * Registers listeners
   */
  private initializeEvents(): void {
    document.querySelectorAll('[data-scheduler-table]').forEach((table: HTMLTableElement) => {
      new SortableTable(table);
    });
    new RegularEvent('show.bs.collapse', this.toggleCollapseIcon.bind(this)).bindTo(document);
    new RegularEvent('hide.bs.collapse', this.toggleCollapseIcon.bind(this)).bindTo(document);
    new RegularEvent('multiRecordSelection:action:go', this.executeTasks.bind(this)).bindTo(document);
    new RegularEvent('multiRecordSelection:action:go_cron', this.executeTasks.bind(this)).bindTo(document);
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
}

export default new Scheduler();
