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

import {html, TemplateResult} from 'lit';
import {renderNodes} from '@typo3/core/lit-helper';
import * as d3drag from 'd3-drag';
import {SvgTree} from '../svg-tree';
import {TreeNode} from '@typo3/backend/tree/tree-node';

/**
 * Contains basic types for allowing dragging + dropping in trees
 */

/**
 * Generates a template for dragged node
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

export enum DraggablePositionEnum {
  INSIDE = 'inside',
  BEFORE = 'before',
  AFTER = 'after'
}

export interface DragDropTargetPosition {
  target: TreeNode,
  position: DraggablePositionEnum
}

export interface DragDropHandler {
  dragStarted: boolean;
  startPageX: number;
  startPageY: number;
  onDragStart(event: MouseEvent, draggingNode: TreeNode|null): boolean;
  onDragOver(event: MouseEvent, draggingNode: TreeNode|null): boolean;
  onDrop(event: MouseEvent, draggingNode: TreeNode|null): boolean;
}

/**
 * Contains the information about drag+drop of one tree instance, contains common
 * functionality used for drag+drop.
 */
export class DragDrop {
  protected tree: SvgTree;
  private timeout: any = {};
  private minimalDistance: number = 10;
  /**
   * This keeps an info, if the draggable / container has the "nodrop" CSS class, if so, this is false
   */
  private allowedDropFromLastUpdate: boolean = false;

  public static setDragStart(): void {
    document.querySelectorAll('iframe').forEach((htmlElement: HTMLIFrameElement) => htmlElement.style.pointerEvents = 'none' );
  }

  public static setDragEnd(): void {
    document.querySelectorAll('iframe').forEach((htmlElement: HTMLIFrameElement) => htmlElement.style.pointerEvents = '' );
  }

  constructor(svgTree: SvgTree) {
    this.tree = svgTree;
  }

  /**
   * Creates a new drag instance and initializes the clickDistance setting to
   * prevent clicks from being wrongly detected as drag attempts.
   */
  public connectDragHandler(dragHandler: DragDropHandler) {
    return d3drag
      .drag()
      .filter((event) => { return event instanceof MouseEvent; })
      .clickDistance(5)
      .on('start', function(evt: d3drag.D3DragEvent<any, any, any>) { dragHandler.onDragStart(evt.sourceEvent, evt.subject) && DragDrop.setDragStart(); })
      .on('drag', function(evt: d3drag.D3DragEvent<any, any, any>) { dragHandler.onDragOver(evt.sourceEvent, evt.subject); })
      .on('end', function(evt: d3drag.D3DragEvent<any, any, any>) { DragDrop.setDragEnd(); dragHandler.onDrop(evt.sourceEvent, evt.subject); })
  }

  /**
   * Create the "shadowed" element around the SVG tree as HTML element.
   */
  public createDraggable(icon: string, name: string)
  {
    let svg = this.tree.svg.node() as SVGElement;
    const draggable = renderNodes(DraggableTemplate.get(icon, name));
    svg.after(...draggable);
    this.tree.svg.node().querySelector('.nodes-wrapper')?.classList.add('nodes-wrapper--dragging');
  }

  /**
   * Create the "shadowed" element around the SVG tree as HTML from an existing node.
   * This is especially helpful to also mark the existing node as "node-bg-dragging" currently.
   */
  public createDraggableFromExistingNode(node: TreeNode)
  {
    this.createDraggable(this.tree.getIconId(node), node.name);
    const nodeBg = this.tree.svg.node().querySelector('.node-bg[data-state-id="' + node.stateIdentifier + '"]');
    nodeBg?.classList.add('node-bg--dragging');
  }

  /**
   * Returns the HTML element (if exists) that is used as "shadowed", from "createDraggable".
   */
  public getDraggable(): HTMLElement|null
  {
    let draggable = this.tree.svg.node().parentNode.querySelector('.node-dd') as HTMLElement;
    return draggable || null;
  }

  public updateDraggablePosition(evt: MouseEvent): void {
    let left = 18;
    let top = 15;
    if (evt && evt.pageX) {
      left += evt.pageX;
    }

    if (evt && evt.pageY) {
      top += evt.pageY;
    }
    document.querySelectorAll('.node-dd').forEach((draggable: HTMLElement) => {
      draggable.style.top = top + 'px';
      draggable.style.left = left + 'px';
      draggable.style.display = 'block';
    });
  }

  /**
   * Open node with children while holding the node/element over this node for 1 second
   */
  public openNodeTimeout(): void {
    if (this.tree.hoveredNode !== null && this.tree.hoveredNode.hasChildren && !this.tree.hoveredNode.expanded) {
      if (this.timeout.node != this.tree.hoveredNode) {
        this.timeout.node = this.tree.hoveredNode;
        clearTimeout(this.timeout.time);
        this.timeout.time = setTimeout(() => {
          if (this.tree.hoveredNode) {
            this.tree.showChildren(this.tree.hoveredNode);
            this.tree.prepareDataForVisibleNodes();
            this.tree.updateVisibleNodes();
          }
        }, 1000);
      }
    } else {
      clearTimeout(this.timeout.time);
    }
  }

  /**
   * Add a CSS Class to the nodes-wrapper container, and to a possible draggable, if it exists.
   */
  public addNodeDdClass(className: string): void {
    const nodesWrap = this.tree.svg.node().querySelector('.nodes-wrapper') as SVGElement;
    const draggableItem = this.getDraggable();
    if (draggableItem) {
      this.applyNodeClassNames(draggableItem, 'node-dd--', className);
    }
    if (nodesWrap) {
      this.applyNodeClassNames(nodesWrap, 'nodes-wrapper--', className);
    }
    this.allowedDropFromLastUpdate = className !== 'nodrop';
  }

  // Clean up after a finished drag+drop move
  public cleanupDrop(): void {
    const nodesWrap = this.tree.svg.node().querySelector('.nodes-wrapper');
    // remove any classes from wrapper
    [
      'nodes-wrapper--nodrop',
      'nodes-wrapper--ok-append',
      'nodes-wrapper--ok-below',
      'nodes-wrapper--ok-between',
      'nodes-wrapper--ok-above',
      'nodes-wrapper--dragging'
    ].forEach((className: string) => nodesWrap.classList.remove(className) );

    this.tree.nodesBgContainer.node().querySelector('.node-bg.node-bg--dragging')?.classList.remove('node-bg--dragging');
    this.hidePositioningLine();
    this.tree.svg.node().parentNode.querySelector('.node-dd').remove();
  }

  /**
   * Creates positioning line is used when drag/drop can be used to add something between two nodes,
   * if it does not exist yet.
   */
  public createPositioningLine(): void
  {
    let nodeBgBorder = this.tree.nodesBgContainer.selectAll('.node-bg__border');
    if (nodeBgBorder.empty()) {
      this.tree.nodesBgContainer
        .append('rect')
        .attr('class', 'node-bg__border')
        .attr('height', '1px')
        .attr('width', '100%');
    }
  }

  /**
   * Update the positioning line and also makes sure it is shown again
   */
  public updatePositioningLine(hoveredNode: TreeNode): void
  {
    this.tree.nodesBgContainer
      .selectAll('.node-bg__border')
      .attr('transform', 'translate(' + (this.tree.settings.indentWidth / 2 * -1) + ', ' + (hoveredNode.y - (this.tree.settings.nodeHeight / 2)) + ')')
      .style('display', 'block');
  }

  /**
   * Hide the positioning line (e.g. when a node is about to be dropped INSIDE the hoveredNode)
   */
  public hidePositioningLine(): void
  {
    this.tree.nodesBgContainer
      .selectAll('.node-bg__border')
      .style('display', 'none');
  }

  public isTheSameNode(targetNode: TreeNode|null, draggingNode: TreeNode): boolean
  {
    return targetNode && targetNode.parentsStateIdentifier.indexOf(draggingNode.stateIdentifier) !== -1;
  }

  /**
   * Check if node is dragged at least @distance
   */
  public isDragNodeDistanceMore(event: MouseEvent, dragHandler: DragDropHandler): boolean {
    return (dragHandler.dragStarted ||
      (((dragHandler.startPageX - this.minimalDistance) > event.pageX) ||
        ((dragHandler.startPageX + this.minimalDistance) < event.pageX) ||
        ((dragHandler.startPageY - this.minimalDistance) > event.pageY) ||
        ((dragHandler.startPageY + this.minimalDistance) < event.pageY)));
  }

  protected _isDropAllowed(): boolean {
    return this.allowedDropFromLastUpdate;
  }

  private applyNodeClassNames(target: HTMLElement|SVGElement, prefix: string, className: string): void {
    const classNames = ['nodrop', 'ok-append', 'ok-below', 'ok-between', 'ok-above'];
    // remove any existing classes
    classNames.forEach((className: string) => target.classList.remove(prefix + className));
    // apply new class
    target.classList.add(prefix + className);
  }
}
