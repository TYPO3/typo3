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

import {D3DragEvent} from 'd3-drag';
import {DragDropHandler, DragDrop, DraggablePositionEnum} from './DragDrop';
import Modal = require('../Modal');
import Severity = require('../Severity');
import Notification = require('../Notification');
import {FileStorageTree} from './FileStorageTree';
import {TreeNode} from 'TYPO3/CMS/Backend/Tree/TreeNode';
import AjaxRequest from 'TYPO3/CMS/Core/Ajax/AjaxRequest';

interface NodePositionOptions {
  node: TreeNode,
  target: TreeNode,
  identifier: string,
  position: DraggablePositionEnum
}

interface NodeTargetPosition {
  target: TreeNode,
  position: DraggablePositionEnum
}

/**
 * Extends Drag&Drop functionality for File Storage Tree positioning when dropping
 */
export class FileStorageTreeActions extends DragDrop {
  public changeNodePosition(droppedNode: TreeNode): null|NodePositionOptions {
    const nodes = this.tree.nodes;
    const identifier = this.tree.settings.nodeDrag.identifier;
    let position = this.tree.settings.nodeDragPosition;
    let target = droppedNode || this.tree.settings.nodeDrag;

    if (identifier === target.identifier) {
      return null;
    }

    if (position === DraggablePositionEnum.BEFORE) {
      const index = nodes.indexOf(droppedNode);
      const positionAndTarget = this.setNodePositionAndTarget(index);
      if (positionAndTarget === null) {
        return null;
      }
      position = positionAndTarget.position;
      target = positionAndTarget.target;
    }

    return {
      node: this.tree.settings.nodeDrag,
      identifier: identifier, // dragged node id
      target: target, // hovered node
      position: position // before, in, after
    }
  }

  /**
   * Returns position and target node
   */
  public setNodePositionAndTarget(index: number): null|NodeTargetPosition {
    const nodes = this.tree.nodes;
    const nodeOver = nodes[index];
    const nodeOverDepth = nodeOver.depth;
    if (index > 0) {
      index--;
    }
    const nodeBefore = nodes[index];
    const nodeBeforeDepth = nodeBefore.depth;
    const target = this.tree.nodes[index];

    if (nodeBeforeDepth === nodeOverDepth) {
      return {position: DraggablePositionEnum.AFTER, target};
    } else if (nodeBeforeDepth < nodeOverDepth) {
      return {position: DraggablePositionEnum.INSIDE, target};
    } else {
      for (let i = index; i >= 0; i--) {
        if (nodes[i].depth === nodeOverDepth) {
          return {position: DraggablePositionEnum.AFTER, target: this.tree.nodes[i]};
        } else if (nodes[i].depth < nodeOverDepth) {
          return {position: DraggablePositionEnum.AFTER, target: nodes[i]};
        }
      }
    }
    return null;
  }

  public changeNodeClasses(event: any): void {
    const elementNodeBg = this.tree.svg.select('.node-over');
    const svg = this.tree.svg.node() as SVGElement;
    const nodeDd = svg.parentNode.querySelector('.node-dd') as HTMLElement;

    if (elementNodeBg.size() && this.tree.isOverSvg) {
      this.tree.nodesBgContainer
        .selectAll('.node-bg__border')
        .style('display', 'none');
      this.addNodeDdClass(nodeDd, 'ok-append');
      this.tree.settings.nodeDragPosition = DraggablePositionEnum.INSIDE;
    }
  }

}


/**
 * Drag and drop for nodes (copy/move)
 */
export class FileStorageTreeNodeDragHandler implements DragDropHandler {
  public startDrag: boolean = false;
  public startPageX: number = 0;
  public startPageY: number = 0;
  private isDragged: boolean = false;
  private tree: FileStorageTree;
  private actionHandler: FileStorageTreeActions;

  constructor(tree: FileStorageTree, actionHandler: FileStorageTreeActions) {
    this.tree = tree;
    this.actionHandler = actionHandler;
  }

  public dragStart(event: D3DragEvent<any, any, any>): boolean {
    let node = event.subject;
    if (node.depth === 0) {
      return false;
    }
    this.startPageX = event.sourceEvent.pageX;
    this.startPageY = event.sourceEvent.pageY;
    this.startDrag = false;
    return true;
  };

  public dragDragged(event: D3DragEvent<any, any, any>): boolean {
    let node = event.subject;
    if (this.actionHandler.isDragNodeDistanceMore(event, this)) {
      this.startDrag = true;
    } else {
      return false;
    }

    if (node.depth === 0) {
      return false;
    }

    this.tree.settings.nodeDrag = node;

    let nodeBg = this.tree.svg.node().querySelector('.node-bg[data-state-id="' + node.stateIdentifier + '"]');
    let nodeDd = this.tree.svg.node().parentNode.querySelector('.node-dd') as HTMLElement;

    // Create the draggable
    if (!this.isDragged) {
      this.isDragged = true;
      this.actionHandler.createDraggable(this.tree.getIconId(node), node.name);
      nodeBg?.classList.add('node-bg--dragging');
    }

    this.tree.settings.nodeDragPosition = false;
    this.actionHandler.openNodeTimeout();
    this.actionHandler.updateDraggablePosition(event);

    if (node.isOver
      || (this.tree.hoveredNode && this.tree.hoveredNode.parentsStateIdentifier.indexOf(node.stateIdentifier) !== -1)
      || !this.tree.isOverSvg) {

      this.actionHandler.addNodeDdClass(nodeDd, 'nodrop');

      if (!this.tree.isOverSvg) {
        this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');
      }
    }

    if (!this.tree.hoveredNode || this.isInSameParentNode(node, this.tree.hoveredNode)) {
      this.actionHandler.addNodeDdClass(nodeDd, 'nodrop');
      this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');
    } else {
      this.actionHandler.changeNodeClasses(event);
    }
    return true;
  }

  public isInSameParentNode(activeHoveredNode: TreeNode, targetNode: TreeNode): boolean {
    return activeHoveredNode.parentsStateIdentifier[0] == targetNode.parentsStateIdentifier[0]
      || activeHoveredNode.parentsStateIdentifier[0] == targetNode.stateIdentifier;
  }

  public dragEnd(event: D3DragEvent<any, any, any>): boolean {
    let node = event.subject;

    if (!this.startDrag || node.depth === 0) {
      return false;
    }

    let droppedNode = this.tree.hoveredNode;
    this.isDragged = false;
    this.actionHandler.removeNodeDdClass();

    if (
      !(node.isOver
        || (droppedNode && droppedNode.parentsStateIdentifier.indexOf(node.stateIdentifier) !== -1)
        || !this.tree.settings.canNodeDrag
        || !this.tree.isOverSvg
      )
    ) {
      let options = this.actionHandler.changeNodePosition(droppedNode);
      let modalText = options.position === DraggablePositionEnum.INSIDE ? TYPO3.lang['mess.move_into'] : TYPO3.lang['mess.move_after'];
      modalText = modalText.replace('%s', options.node.name).replace('%s', options.target.name);

      Modal.confirm(
        TYPO3.lang.move_folder,
        modalText,
        Severity.warning, [
          {
            text: TYPO3.lang['labels.cancel'] || 'Cancel',
            active: true,
            btnClass: 'btn-default',
            name: 'cancel'
          },
          {
            text: TYPO3.lang['cm.copy'] || 'Copy',
            btnClass: 'btn-warning',
            name: 'copy'
          },
          {
            text: TYPO3.lang['labels.move'] || 'Move',
            btnClass: 'btn-warning',
            name: 'move'
          }
        ])
        .on('button.clicked', (e: JQueryEventObject) => {
          const target = e.target as HTMLInputElement;
          if (target.name === 'move') {
            this.sendChangeCommand('move', options);
          } else if (target.name === 'copy') {
            this.sendChangeCommand('copy', options);
          }
          Modal.dismiss();
        });
    }
    return true;
  }

  /**
   * Used when something a folder was drag+dropped.
   */
  private sendChangeCommand(command: string, data: any): void {
    let params = {
      data: {}
    } as any;

    if (command === 'copy') {
      params.data.copy = [];
      params.copy.push({data: decodeURIComponent(data.identifier), target: decodeURIComponent(data.target.identifier)});
    } else if (command === 'move') {
      params.data.move = [];
      params.data.move.push({data: decodeURIComponent(data.identifier), target: decodeURIComponent(data.target.identifier)});
    } else {
      return;
    }

    this.tree.nodesAddPlaceholder();

    (new AjaxRequest(top.TYPO3.settings.ajaxUrls.file_process + '&includeMessages=1'))
      .post(params)
      .then((response) => {
        return response.resolve();
      })
      .then((response) => {
        if (response && response.hasErrors) {
          this.tree.errorNotification(response.messages, false);
          this.tree.nodesContainer.selectAll('.node').remove();
          this.tree.update();
          this.tree.nodesRemovePlaceholder();
        } else {
          if (response.messages) {
            response.messages.forEach((message: any) => {
              Notification.showMessage(
                message.title || '',
                message.message || '',
                message.severity
              );
            });
          }
          this.tree.refreshOrFilterTree();
        }
      })
      .catch((error) => {
        this.tree.errorNotification(error, true);
      });
  }
}
