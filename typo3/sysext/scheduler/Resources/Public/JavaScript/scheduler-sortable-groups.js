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
import Sortable from"sortablejs";import AjaxDataHandler from"@typo3/backend/ajax-data-handler.js";class SchedulerSortableGroups{constructor(){this.container=".t3js-group-draggable",this.dragHandle=".t3js-group-draggable-item",this.initialize()}initialize(){const e=document.querySelector(this.container);e&&new Sortable(e,{handle:this.dragHandle,ignore:"input",filter:"typo3-scheduler-editable-group-name, a, button, .t3js-scheduler-sorting-disabled",preventOnFilter:!1,onMove:function(e){return-1===e.related.className.indexOf("disabled")},onSort:e=>{const t=e.target.children[e.newDraggableIndex-1];let a=0;if(t){const e=t.querySelector("[data-task-group-id]").dataset.taskGroupId;a=Number("-"+e)}const r=e.item.querySelector("[data-task-group-id]"),o=Number(r.dataset.taskGroupId),d="tx_scheduler_task_group",n={component:"contextmenu",action:"delete",table:d,uid:o};AjaxDataHandler.process("cmd["+d+"]["+o+"][move][action]=paste&cmd["+d+"]["+o+"][move][target]="+a+"&cmd["+d+"]["+o+"][move][update][colPos]=0&cmd["+d+"]["+o+"][move][update][sys_language_uid]=0",n)}})}}export default new SchedulerSortableGroups;