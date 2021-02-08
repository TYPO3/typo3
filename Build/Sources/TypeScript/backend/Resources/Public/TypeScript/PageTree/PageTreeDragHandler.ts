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

/** @ts-ignore */
import $ from 'jquery';
import {html, TemplateResult} from 'lit-element';
import {renderNodes} from 'TYPO3/CMS/Core/lit-helper';
import {D3DragEvent} from 'd3-drag';
import Modal = require('../Modal');
import Severity = require('../Severity');

/**
 * Currently this library has a lot of cross-cutting functionality
 * because it touches "PageTreeDragDrop" and "PageTree" directly, also setting
 * options for tree options, which the tree evaluates again.
 */

export interface DragDropHandler {
  startDrag: boolean;
  startPageX: number;
  startPageY: number;
  dragStart(event: D3DragEvent<any, any, any>): boolean;
  dragDragged(event: D3DragEvent<any, any, any>): boolean;
  dragEnd(event: D3DragEvent<any, any, any>): boolean;
}

/**
 * Returns template for dragged node
 */
class DraggableTemplate {
  public static get(icon: string, name: string): TemplateResult {
    return html`<div class="node-dd node-dd--nodrop">
        <div class="node-dd__ctrl-icon"></div>
        <div class="node-dd__text">
            <span class="node-dd__icon">
                <svg aria-hidden="true" style="width: 16px; height: 16px">
                    <use xlink:ref="${icon}"></use>
                </svg>
            </span>
            <span class="node-dd__name">${name}</span>
        </div>
    </div>`;
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
  private dragDrop: any;
  private tree: any;

  constructor(item: any, tree: any, dragDrop: any) {
    this.id = item.nodeType;
    this.name = item.title;
    this.tooltip = item.tooltip;
    this.icon = item.icon;
    this.tree = tree;
    this.dragDrop = dragDrop;
  }

  public dragStart(event: D3DragEvent<any, any, any>): boolean {
    this.isDragged = false;
    this.startDrag = false;
    this.startPageX = event.sourceEvent.pageX;
    this.startPageY = event.sourceEvent.pageY;
    return true;
  };

  public dragDragged(event: D3DragEvent<any, any, any>): boolean {
    if (this.dragDrop.isDragNodeDistanceMore(event, this)) {
      this.startDrag = true;
    } else {
      return false;
    }

    // Add the draggable element
    if (this.isDragged === false) {
      this.isDragged = true;
      let $svg = $(this.tree.svg.node());
      $svg.after($(renderNodes(DraggableTemplate.get('#icon-' + this.icon, this.name))));
      $svg.find('.nodes-wrapper').addClass('nodes-wrapper--dragging');
    }

    let left = 18;
    let top = 15;
    if (event.sourceEvent && event.sourceEvent.pageX) {
      left += event.sourceEvent.pageX;
    }

    if (event.sourceEvent && event.sourceEvent.pageY) {
      top += event.sourceEvent.pageY;
    }

    this.dragDrop.openNodeTimeout();
    $(document).find('.node-dd').css({
      left: left,
      top: top,
      display: 'block'
    });
    this.dragDrop.changeNodeClasses(event);
    return true;
  };

  public dragEnd(event: D3DragEvent<any, any, any>): boolean {
    if (!this.startDrag) {
      return false;
    }

    let $svg = $(this.tree.svg.node());
    let $nodesBg = $svg.find('.nodes-bg');
    let $nodesWrap = $svg.find('.nodes-wrapper');

    this.isDragged = false;
    this.dragDrop.addNodeDdClass($nodesWrap, null, '', true);

    $nodesBg.find('.node-bg.node-bg--dragging').removeClass('node-bg--dragging');
    $svg.siblings('.node-dd').remove();

    this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');

    if (this.tree.settings.isDragAnDrop !== true || !this.tree.hoveredNode || !this.tree.isOverSvg) {
      return false;
    }

    if (this.tree.settings.canNodeDrag) {
      this.tree.addNewNode({
        type: this.id,
        name: this.name,
        tooltip: this.tooltip,
        icon: this.icon,
        position: this.tree.settings.nodeDragPosition,
        target: this.tree.hoveredNode
      });
    }
    return true;
  };
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
  private dropZoneDelete: any;
  private isDragged: boolean = false;
  private tree: any;
  private dragDrop: any;
  private nodeIsOverDelete: boolean = false;

  constructor(tree: any, dragDrop: any) {
    this.tree = tree;
    this.dragDrop = dragDrop;
  }

  public dragStart(event: D3DragEvent<any, any, any>): boolean {
    let node = event.subject;
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

  public dragDragged(event: D3DragEvent<any, any, any>): boolean {
    let node = event.subject;
    if (this.dragDrop.isDragNodeDistanceMore(event, this)) {
      this.startDrag = true;
    } else {
      return false;
    }

    if (this.tree.settings.isDragAnDrop !== true || node.depth === 0) {
      return false;
    }

    this.tree.settings.nodeDrag = node;

    let $svg = $(event.sourceEvent.target).closest('svg');
    let $nodesBg = $svg.find('.nodes-bg');
    let $nodesWrap = $svg.find('.nodes-wrapper');
    let $nodeBg = $nodesBg.find('.node-bg[data-state-id=' + node.stateIdentifier + ']');
    let $nodeDd = $svg.siblings('.node-dd');

    // Create the draggable
    if ($nodeBg.length && !this.isDragged) {
      this.tree.settings.dragging = true;
      this.isDragged = true;

      $svg.after($(renderNodes(DraggableTemplate.get(this.tree.getIconId(node), node.name))));
      $nodeBg.addClass('node-bg--dragging');
      $svg.find('.nodes-wrapper').addClass('nodes-wrapper--dragging');
    }

    let left = 18;
    let top = 15;

    if (event.sourceEvent && event.sourceEvent.pageX) {
      left += event.sourceEvent.pageX;
    }

    if (event.sourceEvent && event.sourceEvent.pageY) {
      top += event.sourceEvent.pageY;
    }

    this.tree.settings.nodeDragPosition = false;
    this.dragDrop.openNodeTimeout();
    $(document).find('.node-dd').css({
      left: left,
      top: top,
      display: 'block'
    });

    if (node.isOver
      || (this.tree.hoveredNode && this.tree.hoveredNode.parentsStateIdentifier.indexOf(node.stateIdentifier) !== -1)
      || !this.tree.isOverSvg) {

      this.dragDrop.addNodeDdClass($nodesWrap, $nodeDd, 'nodrop');

      if (!this.tree.isOverSvg) {
        this.tree.nodesBgContainer
          .selectAll('.node-bg__border')
          .style('display', 'none');
      }

      if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open !== 'true' && this.tree.isOverSvg) {
        this.animateDropZone('show', this.dropZoneDelete.node(), node);
      }
    } else if (!this.tree.hoveredNode) {
      this.dragDrop.addNodeDdClass($nodesWrap, $nodeDd, 'nodrop');
      this.tree.nodesBgContainer
        .selectAll('.node-bg__border')
        .style('display', 'none');
    } else {
      if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open !== 'false') {
        this.animateDropZone('hide', this.dropZoneDelete.node(), node);
      }
      this.dragDrop.changeNodeClasses(event);
    }
    return true;
  }

  public dragEnd(event: D3DragEvent<any, any, any>): boolean {
    let node = event.subject;
    if (this.dropZoneDelete && this.dropZoneDelete.node().dataset.open === 'true') {
      let dropZone = this.dropZoneDelete;
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

    let $svg = $(event.sourceEvent.target).closest('svg');
    let $nodesBg = $svg.find('.nodes-bg');
    let droppedNode = this.tree.hoveredNode;
    this.isDragged = false;

    this.dragDrop.addNodeDdClass($svg.find('.nodes-wrapper'), null, '', true);

    $nodesBg.find('.node-bg.node-bg--dragging').removeClass('node-bg--dragging');
    $svg.siblings('.node-dd').remove();
    this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');

    if (
      !(node.isOver
        || (droppedNode && droppedNode.parentsStateIdentifier.indexOf(node.stateIdentifier) !== -1)
        || !this.tree.settings.canNodeDrag
        || !this.tree.isOverSvg
      )
    ) {
      let options = this.dragDrop.changeNodePosition(droppedNode, '');
      let modalText = options.position === 'in' ? TYPO3.lang['mess.move_into'] : TYPO3.lang['mess.move_after'];
      modalText = modalText.replace('%s', options.node.name).replace('%s', options.target.name);

      Modal.confirm(
        TYPO3.lang.move_page,
        modalText,
        Severity.warning, [
          {
            text: $(this).data('button-close-text') || TYPO3.lang['labels.cancel'] || 'Cancel',
            active: true,
            btnClass: 'btn-default',
            name: 'cancel'
          },
          {
            text: $(this).data('button-ok-text') || TYPO3.lang['cm.copy'] || 'Copy',
            btnClass: 'btn-warning',
            name: 'copy'
          },
          {
            text: $(this).data('button-ok-text') || TYPO3.lang['labels.move'] || 'Move',
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
      let options = this.dragDrop.changeNodePosition(droppedNode, 'delete');
      if (this.tree.settings.displayDeleteConfirmation) {
        let $modal = Modal.confirm(
          TYPO3.lang.deleteItem,
          TYPO3.lang['mess.delete'].replace('%s', options.node.name),
          Severity.warning, [
            {
              text: $(this).data('button-close-text') || TYPO3.lang['labels.cancel'] || 'Cancel',
              active: true,
              btnClass: 'btn-default',
              name: 'cancel'
            },
            {
              text: $(this).data('button-ok-text') || TYPO3.lang['cm.delete'] || 'Delete',
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
   *
   * @param node
   * @returns {string}
   */
  private getDropZoneOpenTransform(node: any) {
    const svgWidth = parseFloat(this.tree.svg.style('width')) || 300;
    return 'translate(' + (svgWidth - 58 - node.x) + 'px, -10px)';
  }
  /**
   * Returns deleting drop zone close 'transform' attribute value
   *
   * @param node
   * @returns {string}
   */
  private getDropZoneCloseTransform(node: any) {
    const svgWidth = parseFloat(this.tree.svg.style('width')) || 300;
    return 'translate(' + (svgWidth - node.x) + 'px, -10px)';
  }

  /**
   * Animates the drop zone next to given node
   *
   * @param {string} action
   * @param {SVGElement} dropZone
   * @param {Node} node
   * @param {Function|null} onfinish
   */
  private animateDropZone(action: string, dropZone: SVGElement, node: Node, onfinish: Function = null) {
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
