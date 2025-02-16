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

import Sortable, { type SortableEvent } from 'sortablejs';
import AjaxDataHandler from '@typo3/backend/ajax-data-handler';

/**
 * Module: @typo3/scheduler/scheduler-sortable-groups
 * @exports @typo3/scheduler/scheduler-sortable-groups
 */
class SchedulerSortableGroups {
  container: string = '.t3js-group-draggable-container';
  dragHandle: string = '.t3js-group-draggable-handle';

  constructor() {
    this.initialize();
  }

  public initialize(): void {
    const element = document.querySelector(this.container) as HTMLElement;
    if(element) {
      new Sortable(element, {
        handle: this.dragHandle,
        onMove: (event): boolean => {
          return ('taskGroupId' in event.related.dataset) && Number(event.related.dataset.taskGroupId) !== 0;
        },
        onSort: (event: SortableEvent): void => {
          const previousItem = event.target.children[event.newDraggableIndex - 1];
          let letMoveTarget = 0;
          if(previousItem) {
            const previousUid: string = (<HTMLElement>previousItem).dataset.taskGroupId;
            letMoveTarget = Number('-' + previousUid);
          }

          const uid: number = Number(event.item.dataset.taskGroupId);
          const table: string = 'tx_scheduler_task_group';
          const eventData = { component: 'contextmenu', action: 'delete', table, uid };

          AjaxDataHandler.process('cmd[' + table + '][' + uid + '][move][action]=paste&cmd[' + table + '][' + uid + '][move][target]=' + letMoveTarget + '&cmd[' + table + '][' + uid + '][move][update][colPos]=0&cmd[' + table + '][' + uid + '][move][update][sys_language_uid]=0', eventData);
        },
      });
      (document.querySelectorAll(this.dragHandle) as NodeListOf<HTMLButtonElement>).forEach(handle => {
        handle.disabled = false;
      });
    }
  }
}

export default new SchedulerSortableGroups();
