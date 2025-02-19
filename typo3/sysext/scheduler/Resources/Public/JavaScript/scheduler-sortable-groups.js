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
import n from"sortablejs";import l from"@typo3/backend/ajax-data-handler.js";class i{constructor(){this.container=".t3js-group-draggable-container",this.dragHandle=".t3js-group-draggable-handle",this.initialize()}initialize(){const r=document.querySelector(this.container);r&&(new n(r,{handle:this.dragHandle,onMove:e=>"taskGroupId"in e.related.dataset&&Number(e.related.dataset.taskGroupId)!==0,onSort:e=>{const o=e.target.children[e.newDraggableIndex-1];let d=0;o&&(d=+("-"+o.dataset.taskGroupId));const t=Number(e.item.dataset.taskGroupId),a="tx_scheduler_task_group",s={component:"contextmenu",action:"delete",table:a,uid:t};l.process("cmd["+a+"]["+t+"][move][action]=paste&cmd["+a+"]["+t+"][move][target]="+d+"&cmd["+a+"]["+t+"][move][update][colPos]=0&cmd["+a+"]["+t+"][move][update][sys_language_uid]=0",s)}}),document.querySelectorAll(this.dragHandle).forEach(e=>{e.disabled=!1}))}}var c=new i;export{c as default};
