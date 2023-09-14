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
import Sortable from"sortablejs";import AjaxDataHandler from"@typo3/backend/ajax-data-handler.js";class SchedulerSortableGroups{constructor(){this.container=".t3js-group-draggable-container",this.dragHandle=".t3js-group-draggable-handle",this.initialize()}initialize(){const e=document.querySelector(this.container);e&&(new Sortable(e,{handle:this.dragHandle,onMove:e=>"taskGroupId"in e.related.dataset&&0!==Number(e.related.dataset.taskGroupId),onSort:e=>{const t=e.target.children[e.newDraggableIndex-1];let a=0;if(t){const e=t.dataset.taskGroupId;a=Number("-"+e)}const r=Number(e.item.dataset.taskGroupId),o="tx_scheduler_task_group",d={component:"contextmenu",action:"delete",table:o,uid:r};AjaxDataHandler.process("cmd["+o+"]["+r+"][move][action]=paste&cmd["+o+"]["+r+"][move][target]="+a+"&cmd["+o+"]["+r+"][move][update][colPos]=0&cmd["+o+"]["+r+"][move][update][sys_language_uid]=0",d)}}),document.querySelectorAll(this.dragHandle).forEach((e=>{e.disabled=!1})))}}export default new SchedulerSortableGroups;