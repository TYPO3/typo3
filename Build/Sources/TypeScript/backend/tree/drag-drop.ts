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
import * as d3selection from 'd3-selection';
import {SvgTree, SvgTreeWrapper} from '../svg-tree';

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

export interface DragDropHandler {
  startDrag: boolean;
  startPageX: number;
  startPageY: number;
  dragStart(event: d3drag.D3DragEvent<any, any, any>): boolean;
  dragDragged(event: d3drag.D3DragEvent<any, any, any>): boolean;
  dragEnd(event: d3drag.D3DragEvent<any, any, any>): boolean;
}

/**
 * Contains the information about drag+drop of one tree instance, contains common
 * functionality used for drag+drop.
 */
export class DragDrop {
  protected tree: SvgTree;
  private timeout: any = {};
  private minimalDistance: number = 10;

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
      .clickDistance(5)
      .on('start', function(evt: d3drag.D3DragEvent<any, any, any>) { dragHandler.dragStart(evt) && DragDrop.setDragStart(); })
      .on('drag', function(evt: d3drag.D3DragEvent<any, any, any>) { dragHandler.dragDragged(evt); })
      .on('end', function(evt: d3drag.D3DragEvent<any, any, any>) { DragDrop.setDragEnd(); dragHandler.dragEnd(evt); })
  }

  public createDraggable(icon: string, name: string)
  {
    let svg = this.tree.svg.node() as SVGElement;
    const draggable = renderNodes(DraggableTemplate.get(icon, name));
    svg.after(...draggable);
    this.tree.svg.node().querySelector('.nodes-wrapper')?.classList.add('nodes-wrapper--dragging');
  }

  public updateDraggablePosition(evt: d3drag.D3DragEvent<any, any, any>): void {
    let left = 18;
    let top = 15;
    if (evt.sourceEvent && evt.sourceEvent.pageX) {
      left += evt.sourceEvent.pageX;
    }

    if (evt.sourceEvent && evt.sourceEvent.pageY) {
      top += evt.sourceEvent.pageY;
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

  public changeNodeClasses(event: any): void {
    const elementNodeBg = this.tree.svg.select('.node-over');
    const svg = this.tree.svg.node() as SVGElement;
    const nodeDd = svg.parentNode.querySelector('.node-dd') as HTMLElement;
    type NodeBgBorderSelection = d3selection.Selection<SVGElement, any, SVGElement, any>
    | d3selection.Selection<SVGElement, any, SvgTreeWrapper, any>;
    let nodeBgBorder: NodeBgBorderSelection = this.tree.nodesBgContainer.selectAll('.node-bg__border');

    if (elementNodeBg.size() && this.tree.isOverSvg) {
      // line between nodes
      if (nodeBgBorder.empty()) {
        nodeBgBorder = this.tree.nodesBgContainer
          .append('rect')
          .attr('class', 'node-bg__border')
          .attr('height', '1px')
          .attr('width', '100%');
      }

      const coordinates = d3selection.pointer(event, elementNodeBg.node());
      let y = coordinates[1];

      if (y < 3) {
        const attr = nodeBgBorder.attr('transform', 'translate(-8, ' + (this.tree.hoveredNode.y - 10) + ')') as NodeBgBorderSelection;
        attr.style('display', 'block');

        if (this.tree.hoveredNode.depth === 0) {
          this.addNodeDdClass(nodeDd, 'nodrop');
        } else if (this.tree.hoveredNode.firstChild) {
          this.addNodeDdClass(nodeDd, 'ok-above');
        } else {
          this.addNodeDdClass(nodeDd, 'ok-between');
        }

        this.tree.settings.nodeDragPosition = DraggablePositionEnum.BEFORE;
      } else if (y > 17) {
        nodeBgBorder.style('display', 'none');

        if (this.tree.hoveredNode.expanded && this.tree.hoveredNode.hasChildren) {
          this.addNodeDdClass(nodeDd, 'ok-append');
          this.tree.settings.nodeDragPosition = DraggablePositionEnum.INSIDE;
        } else {
          const attr = nodeBgBorder.attr('transform', 'translate(-8, ' + (this.tree.hoveredNode.y + 10) + ')') as NodeBgBorderSelection;
          attr.style('display', 'block');

          if (this.tree.hoveredNode.lastChild) {
            this.addNodeDdClass(nodeDd, 'ok-below');
          } else {
            this.addNodeDdClass(nodeDd, 'ok-between');
          }

          this.tree.settings.nodeDragPosition = DraggablePositionEnum.AFTER;
        }
      } else {
        nodeBgBorder.style('display', 'none');
        this.addNodeDdClass(nodeDd, 'ok-append');
        this.tree.settings.nodeDragPosition = DraggablePositionEnum.INSIDE;
      }
    } else {
      this.tree.nodesBgContainer
        .selectAll('.node-bg__border')
        .style('display', 'none');

      this.addNodeDdClass(nodeDd, 'nodrop');
    }
  }

  public addNodeDdClass(nodeDd: HTMLElement|null, className: string): void {
    const nodesWrap = this.tree.svg.node().querySelector('.nodes-wrapper') as SVGElement;
    if (nodeDd) {
      this.applyNodeClassNames(nodeDd, 'node-dd--', className);
    }
    if (nodesWrap) {
      this.applyNodeClassNames(nodesWrap, 'nodes-wrapper--', className);
    }
    this.tree.settings.canNodeDrag = className !== 'nodrop';
  }

  // Clean up after a finished drag+drop move
  public removeNodeDdClass(): void {
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
    this.tree.nodesBgContainer.selectAll('.node-bg__border').style('display', 'none');
    this.tree.svg.node().parentNode.querySelector('.node-dd').remove();
  }

  /**
   * Check if node is dragged at least @distance
   */
  public isDragNodeDistanceMore(event: d3drag.D3DragEvent<any, any, any>, dragHandler: DragDropHandler): boolean {
    return (dragHandler.startDrag ||
      (((dragHandler.startPageX - this.minimalDistance) > event.sourceEvent.pageX) ||
        ((dragHandler.startPageX + this.minimalDistance) < event.sourceEvent.pageX) ||
        ((dragHandler.startPageY - this.minimalDistance) > event.sourceEvent.pageY) ||
        ((dragHandler.startPageY + this.minimalDistance) < event.sourceEvent.pageY)));
  }

  private applyNodeClassNames(target: HTMLElement|SVGElement, prefix: string, className: string): void {
    const classNames = ['nodrop', 'ok-append', 'ok-below', 'ok-between', 'ok-above', 'dragging'];
    // remove any existing classes
    classNames.forEach((className: string) => target.classList.remove(prefix + className));
    // apply new class
    target.classList.add(prefix + className);
  }
}
