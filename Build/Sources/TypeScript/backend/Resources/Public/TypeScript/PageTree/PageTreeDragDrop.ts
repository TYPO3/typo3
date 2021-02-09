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

import {DragDrop, DragDropHandler, DraggablePositionEnum} from '../Tree/DragDrop';
import {D3DragEvent} from 'd3-drag';
import {TreeNode} from '../Tree/TreeNode';
import * as d3selection from 'd3-selection';
import Modal = require('../Modal');
import Severity = require('../Severity');
import {TreeWrapperSelection} from '../SvgTree';

type TreeNodeDragEvent = D3DragEvent<SVGElement, any, TreeNode>;

interface NodeCreationOptions {
  type: string,
  name: string,
  title?: string;
  tooltip: string,
  icon: string,
  position: DraggablePositionEnum,
  target: TreeNode
}

interface NodePositionOptions {
  node: TreeNode,
  target: TreeNode,
  uid: string,
  position: DraggablePositionEnum,
  command: string
}

interface NodeTargetPosition {
  target: TreeNode,
  position: DraggablePositionEnum
}

/**
 * Extends Drag&Drop functionality for Page Tree positioning when dropping
 */
export class PageTreeDragDrop extends DragDrop {
  public changeNodePosition(droppedNode: TreeNode, command: string = ''): null|NodePositionOptions {
    const nodes = this.tree.nodes;
    const uid = this.tree.settings.nodeDrag.identifier;
    let position = this.tree.settings.nodeDragPosition;
    let target = droppedNode || this.tree.settings.nodeDrag;

    if (uid === target.identifier && command !== 'delete') {
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
      uid: uid, // dragged node id
      target: target, // hovered node
      position: position, // before, in, after
      command: command // element is copied or moved
    }
  }

  /**
   * Returns Array of position and target node
   *
   * @param {number} index of node which is over mouse
   * @returns {Array} [position, target]
   * @todo this should be moved into PageTree.js
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
}

/**
 * Main Handler for the toolbar when creating new items
 */
export class ToolbarDragHandler implements DragDropHandler {
  public startDrag: boolean = false;
  public startPageX: number = 0;
  public startPageY: number = 0;
  private readonly id: string = '';
  private readonly name: string = '';
  private readonly tooltip: string = '';
  private readonly icon: string = '';
  private isDragged: boolean = false;
  private dragDrop: PageTreeDragDrop;
  private tree: any;

  constructor(item: any, tree: any, dragDrop: PageTreeDragDrop) {
    this.id = item.nodeType;
    this.name = item.title;
    this.tooltip = item.tooltip;
    this.icon = item.icon;
    this.tree = tree;
    this.dragDrop = dragDrop;
  }

  public dragStart(event: TreeNodeDragEvent): boolean {
    this.isDragged = false;
    this.startDrag = false;
    this.startPageX = event.sourceEvent.pageX;
    this.startPageY = event.sourceEvent.pageY;
    return true;
  }

  public dragDragged(event: TreeNodeDragEvent): boolean {
    if (this.dragDrop.isDragNodeDistanceMore(event, this)) {
      this.startDrag = true;
    } else {
      return false;
    }

    // Add the draggable element
    if (this.isDragged === false) {
      this.isDragged = true;
      this.dragDrop.createDraggable('#icon-' + this.icon, this.name);
    }
    this.dragDrop.openNodeTimeout();
    this.dragDrop.updateDraggablePosition(event);
    this.dragDrop.changeNodeClasses(event);
    return true;
  }

  public dragEnd(event: TreeNodeDragEvent): boolean {
    if (!this.startDrag) {
      return false;
    }

    this.isDragged = false;
    this.dragDrop.removeNodeDdClass();
    if (this.tree.settings.isDragAnDrop !== true || !this.tree.hoveredNode || !this.tree.isOverSvg) {
      return false;
    }
    if (this.tree.settings.canNodeDrag) {
      this.addNewNode({
        type: this.id,
        name: this.name,
        tooltip: this.tooltip,
        icon: this.icon,
        position: this.tree.settings.nodeDragPosition,
        target: this.tree.hoveredNode
      });
    }
    return true;
  }

  /**
   * Add new node to the tree (used in drag+drop)
   *
   * @type {Object} options
   * @private
   */
  private addNewNode(options: NodeCreationOptions): void {
    const target = options.target;
    let index = this.tree.nodes.indexOf(target);
    const newNode = {} as TreeNode;
    newNode.command = 'new';
    newNode.type = options.type;
    newNode.identifier = '-1';
    newNode.target = target;
    newNode.parents = target.parents;
    newNode.parentsStateIdentifier = target.parentsStateIdentifier;
    newNode.depth = target.depth;
    newNode.position = options.position;
    newNode.name = (typeof options.title !== 'undefined') ? options.title : TYPO3.lang['tree.defaultPageTitle'];
    newNode.y = newNode.y || newNode.target.y;
    newNode.x = newNode.x || newNode.target.x;

    this.tree.nodeIsEdit = true;

    if (options.position === DraggablePositionEnum.INSIDE) {
      newNode.depth++;
      newNode.parents.unshift(index);
      newNode.parentsStateIdentifier.unshift(this.tree.nodes[index].stateIdentifier);
      this.tree.nodes[index].hasChildren = true;
      this.tree.showChildren(this.tree.nodes[index]);
    }

    if (options.position === DraggablePositionEnum.INSIDE || options.position === DraggablePositionEnum.AFTER) {
      index++;
    }

    if (options.icon) {
      newNode.icon = options.icon;
    }

    if (newNode.position === DraggablePositionEnum.AFTER) {
      const positionAndTarget = this.dragDrop.setNodePositionAndTarget(index);
      // @todo Check whether an error should be thrown in case of `null`
      if (positionAndTarget !== null) {
        newNode.position = positionAndTarget.position;
        newNode.target = positionAndTarget.target;
      }
    }

    this.tree.nodes.splice(index, 0, newNode);
    this.tree.setParametersNode();
    this.tree.prepareDataForVisibleNodes();
    this.tree.update();
    this.tree.removeEditedText();

    d3selection.select(this.tree.svg.node().parentNode as HTMLElement)
      .append('input')
      .attr('class', 'node-edit')
      .style('top', newNode.y + this.tree.settings.marginTop + 'px')
      .style('left', newNode.x + this.tree.textPosition + 5 + 'px')
      .style('width', this.tree.settings.width - (newNode.x + this.tree.textPosition + 20) + 'px')
      .style('height', this.tree.settings.nodeHeight + 'px')
      .attr('text', 'text')
      .attr('value', newNode.name)
      .on('keydown', (evt: KeyboardEvent) => {
        const target = evt.target as HTMLInputElement;
        const code = evt.keyCode;
        if (code === 13 || code === 9) { // enter || tab
          this.tree.nodeIsEdit = false;
          const newName = target.value.trim();
          if (newName.length) {
            newNode.name = newName;
            this.tree.removeEditedText();
            this.tree.sendChangeCommand(newNode);
          } else {
            this.tree.removeNode(newNode);
          }
        } else if (code === 27) { // esc
          this.tree.nodeIsEdit = false;
          this.tree.removeNode(newNode);
        }
      })
      .on('blur', (evt: FocusEvent) => {
        if (this.tree.nodeIsEdit && (this.tree.nodes.indexOf(newNode) > -1)) {
          const target = evt.target as HTMLInputElement;
          const newName = target.value.trim();
          if (newName.length) {
            newNode.name = newName;
            this.tree.removeEditedText();
            this.tree.sendChangeCommand(newNode);
          } else {
            this.tree.removeNode(newNode);
          }
        }
      })
      .node()
      .select();
  }

}

/**
 * Drag and drop for nodes (copy/move) including the deleting / drop functionality.
 */
export class PageTreeNodeDragHandler implements DragDropHandler {
  public startDrag: boolean = false;
  public startPageX: number = 0;
  public startPageY: number = 0;

  /**
   * SVG <g> container for deleting drop zone
   *
   * @type {Selection}
   */
  private dropZoneDelete: null|TreeWrapperSelection<SVGGElement>;
  private isDragged: boolean = false;
  private tree: any;
  private dragDrop: PageTreeDragDrop;
  private nodeIsOverDelete: boolean = false;

  constructor(tree: any, dragDrop: PageTreeDragDrop) {
    this.tree = tree;
    this.dragDrop = dragDrop;
  }

  public dragStart(event: TreeNodeDragEvent): boolean {
    const node = event.subject;
    if (this.tree.settings.isDragAnDrop !== true || node.depth === 0) {
      return false;
    }
    this.dropZoneDelete = null;

    if (node.allowDelete) {
      this.dropZoneDelete = this.tree.nodesContainer
        .select('.node[data-state-id="' + node.stateIdentifier + '"]')
        .append('g')
        .attr('class', 'nodes-drop-zone')
        .attr('height', this.tree.settings.nodeHeight);
      this.nodeIsOverDelete = false;
      this.dropZoneDelete.append('rect')
        .attr('height', this.tree.settings.nodeHeight)
        .attr('width', '50px')
        .attr('x', 0)
        .attr('y', 0)
        .on('mouseover', () => {
          this.nodeIsOverDelete = true;
        })
        .on('mouseout', () => {
          this.nodeIsOverDelete = false;
        });

      this.dropZoneDelete.append('text')
        .text(TYPO3.lang.deleteItem)
        .attr('dx', 5)
        .attr('dy', 15);

      this.dropZoneDelete.node().dataset.open = 'false';
      this.dropZoneDelete.node().style.transform = this.getDropZoneCloseTransform(node);
    }

    this.startPageX = event.sourceEvent.pageX;
    this.startPageY = event.sourceEvent.pageY;
    this.startDrag = false;
    return true;
  };

  public dragDragged(event: TreeNodeDragEvent): boolean {
    const node = event.subject;
    if (this.dragDrop.isDragNodeDistanceMore(event, this)) {
      this.startDrag = true;
    } else {
      return false;
    }

    if (this.tree.settings.isDragAnDrop !== true || node.depth === 0) {
      return false;
    }

    this.tree.settings.nodeDrag = node;

    const nodeBg = this.tree.svg.node().querySelector('.node-bg[data-state-id="' + node.stateIdentifier + '"]');
    const nodeDd = this.tree.svg.node().parentNode.querySelector('.node-dd');

    // Create the draggable
    if (!this.isDragged) {
      this.isDragged = true;
      this.dragDrop.createDraggable(this.tree.getIconId(node), node.name);
      nodeBg.classList.add('node-bg--dragging');
    }

    this.tree.settings.nodeDragPosition = false;
    this.dragDrop.openNodeTimeout();
    this.dragDrop.updateDraggablePosition(event);

    if (node.isOver
      || (this.tree.hoveredNode && this.tree.hoveredNode.parentsStateIdentifier.indexOf(node.stateIdentifier) !== -1)
      || !this.tree.isOverSvg) {

      this.dragDrop.addNodeDdClass(nodeDd, 'nodrop');

      if (!this.tree.isOverSvg) {
        this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');
      }

      if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open !== 'true' && this.tree.isOverSvg) {
        this.animateDropZone('show', this.dropZoneDelete.node(), node);
      }
    } else if (!this.tree.hoveredNode) {
      this.dragDrop.addNodeDdClass(nodeDd, 'nodrop');
      this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');
    } else if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open !== 'false') {
      this.animateDropZone('hide', this.dropZoneDelete.node(), node);
    }
    this.dragDrop.changeNodeClasses(event);
    return true;
  }

  public dragEnd(event: TreeNodeDragEvent): boolean {
    const node = event.subject;
    if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open === 'true') {
      const dropZone = this.dropZoneDelete;
      this.animateDropZone('hide', this.dropZoneDelete.node(), node, () => {
        dropZone.remove();
        this.dropZoneDelete = null;
      });
    } else {
      this.dropZoneDelete = null;
    }

    if (!this.startDrag || this.tree.settings.isDragAnDrop !== true || node.depth === 0) {
      return false;
    }

    const droppedNode = this.tree.hoveredNode;
    this.isDragged = false;
    this.dragDrop.removeNodeDdClass();

    if (
      !(node.isOver
        || (droppedNode && droppedNode.parentsStateIdentifier.indexOf(node.stateIdentifier) !== -1)
        || !this.tree.settings.canNodeDrag
        || !this.tree.isOverSvg
      )
    ) {
      const options = this.dragDrop.changeNodePosition(droppedNode, '');
      if (options === null) {
        return false;
      }
      let modalText = options.position === DraggablePositionEnum.INSIDE ? TYPO3.lang['mess.move_into'] : TYPO3.lang['mess.move_after'];
      modalText = modalText.replace('%s', options.node.name).replace('%s', options.target.name);

      Modal.confirm(
        TYPO3.lang.move_page,
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
            options.command = 'move';
            this.tree.sendChangeCommand(options);
          } else if (target.name === 'copy') {
            options.command = 'copy';
            this.tree.sendChangeCommand(options);
          }
          Modal.dismiss();
        });
    } else if (this.nodeIsOverDelete) {
      const options = this.dragDrop.changeNodePosition(droppedNode, 'delete');
      if (options === null) {
        return false;
      }
      if (this.tree.settings.displayDeleteConfirmation) {
        const $modal = Modal.confirm(
          TYPO3.lang.deleteItem,
          TYPO3.lang['mess.delete'].replace('%s', options.node.name),
          Severity.warning, [
            {
              text: TYPO3.lang['labels.cancel'] || 'Cancel',
              active: true,
              btnClass: 'btn-default',
              name: 'cancel'
            },
            {
              text: TYPO3.lang['cm.delete'] || 'Delete',
              btnClass: 'btn-warning',
              name: 'delete'
            }
          ]);
        $modal.on('button.clicked', (e: JQueryEventObject) => {
          const target = e.target as HTMLInputElement;
          if (target.name === 'delete') {
            this.tree.sendChangeCommand(options);
          }
          Modal.dismiss();
        });
      } else {
        this.tree.sendChangeCommand(options);
      }
    }
    return true;
  }

  /**
   * Returns deleting drop zone open 'transform' attribute value
   */
  private getDropZoneOpenTransform(node: TreeNode): string {
    const svgWidth = parseFloat(this.tree.svg.style('width')) || 300;
    return 'translate(' + (svgWidth - 58 - node.x) + 'px, -10px)';
  }

  /**
   * Returns deleting drop zone close 'transform' attribute value
   */
  private getDropZoneCloseTransform(node: TreeNode): string {
    const svgWidth = parseFloat(this.tree.svg.style('width')) || 300;
    return 'translate(' + (svgWidth - node.x) + 'px, -10px)';
  }

  /**
   * Animates the drop zone next to given node
   */
  private animateDropZone(action: string, dropZone: SVGElement, node: TreeNode, onfinish: Function = null) {
    dropZone.classList.add('animating');
    dropZone.dataset.open = (action === 'show') ? 'true' : 'false';
    let keyframes = [
      { transform: this.getDropZoneCloseTransform(node) },
      { transform: this.getDropZoneOpenTransform(node) }
    ];
    if (action !== 'show') {
      keyframes = keyframes.reverse();
    }
    const done = function() {
      dropZone.style.transform = keyframes[1].transform;
      dropZone.classList.remove('animating');
      onfinish && onfinish();
    };
    if ('animate' in dropZone) {
      dropZone.animate(keyframes, {
        duration: 300,
        easing: 'cubic-bezier(.02, .01, .47, 1)'
      }).onfinish = done;
    } else {
      done();
    }
  }
}
