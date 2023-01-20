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
import{DraggablePositionEnum}from"@typo3/backend/tree/drag-drop.js";export class DragDropOperation{constructor(r,t,o=DraggablePositionEnum.INSIDE,a={}){this.source=r,this.type=t,this.position=o,this.extra=a}}export class DragDropOperationCollection{constructor(r,t){this.operations=r,this.target=t}static fromDataTransfer(r,t){return DragDropOperationCollection.fromArray(JSON.parse(r.getData("application/json")),t)}static fromArray(r,t){const o=[];for(let t of r)o.push(new DragDropOperation({identifier:t.identifier,name:t.name},t.type,DraggablePositionEnum.INSIDE,t.extra));return new DragDropOperationCollection(o,t)}}