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
import {DragDropHandler} from './PageTreeDragHandler';
import * as d3drag from 'd3-drag';
import * as d3selection from 'd3-selection';

/**
 * Mixture class. Contains the information about drag+drop of one tree instance.
 *
 * @exports TYPO3/CMS/Backend/PageTree/PageTreeDragDrop
 */
export class PageTreeDragDrop {
  private timeout: any = {};
  private tree: any;

  public static setDragStart(): void {
    $('body iframe').css({'pointer-events': 'none'});
  }

  public static setDragEnd(): void {
    $('body iframe').css({'pointer-events': ''});
  }

  constructor(svgTree: any) {
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
      .on('start', function(evt: d3drag.D3DragEvent<any, any, any>) { dragHandler.dragStart(evt) && PageTreeDragDrop.setDragStart(); })
      .on('drag', function(evt: d3drag.D3DragEvent<any, any, any>) { dragHandler.dragDragged(evt); })
      .on('end', function(evt: d3drag.D3DragEvent<any, any, any>) { PageTreeDragDrop.setDragEnd(); dragHandler.dragEnd(evt); })
  }


  /**
   * Open node with children while holding the node/element over this node for 1 second
   */
  public openNodeTimeout(): void {
    if (this.tree.settings.nodeOver.node && this.tree.settings.nodeOver.node.hasChildren && !this.tree.settings.nodeOver.node.expanded) {
      if (this.timeout.node != this.tree.settings.nodeOver.node) {
        this.timeout.node = this.tree.settings.nodeOver;
        clearTimeout(this.timeout.time);
        this.timeout.time = setTimeout(() => {
          if (this.tree.settings.nodeOver.node) {
            this.tree.showChildren(this.tree.settings.nodeOver.node);
            this.tree.prepareDataForVisibleNodes();
            this.tree.update();
          }
        }, 1000);
      }
    } else {
      clearTimeout(this.timeout.time);
    }
  }

  public changeNodeClasses(event: any): void {
    const elementNodeBg = this.tree.svg.select('.node-over');
    const $svg = $(this.tree.svg.node());
    const $nodesWrap = $svg.find('.nodes-wrapper');
    const $nodeDd = $svg.siblings('.node-dd');
    let nodeBgBorder = this.tree.nodesBgContainer.selectAll('.node-bg__border');

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
        nodeBgBorder
          .attr('transform', 'translate(-8, ' + (this.tree.settings.nodeOver.node.y - 10) + ')')
          .style('display', 'block');

        if (this.tree.settings.nodeOver.node.depth === 0) {
          this.addNodeDdClass($nodesWrap, $nodeDd, 'nodrop');
        } else if (this.tree.settings.nodeOver.node.firstChild) {
          this.addNodeDdClass($nodesWrap, $nodeDd, 'ok-above');
        } else {
          this.addNodeDdClass($nodesWrap, $nodeDd, 'ok-between');
        }

        this.tree.settings.nodeDragPosition = 'before';
      } else if (y > 17) {
        nodeBgBorder.style('display', 'none');

        if (this.tree.settings.nodeOver.node.expanded && this.tree.settings.nodeOver.node.hasChildren) {
          this.addNodeDdClass($nodesWrap, $nodeDd, 'ok-append');
          this.tree.settings.nodeDragPosition = 'in';
        } else {
          nodeBgBorder
            .attr('transform', 'translate(-8, ' + (this.tree.settings.nodeOver.node.y + 10) + ')')
            .style('display', 'block');

          if (this.tree.settings.nodeOver.node.lastChild) {
            this.addNodeDdClass($nodesWrap, $nodeDd, 'ok-below');

          } else {
            this.addNodeDdClass($nodesWrap, $nodeDd, 'ok-between');
          }

          this.tree.settings.nodeDragPosition = 'after';
        }
      } else {
        nodeBgBorder.style('display', 'none');

        this.addNodeDdClass($nodesWrap, $nodeDd, 'ok-append');
        this.tree.settings.nodeDragPosition = 'in';
      }
    } else {
      this.tree.nodesBgContainer
        .selectAll('.node-bg__border')
        .style('display', 'none');

      this.addNodeDdClass($nodesWrap, $nodeDd, 'nodrop');
    }
  }

  public addNodeDdClass($nodesWrap: any, $nodeDd: any, className: string = '', remove: boolean = false): void {
    const clearClass = ' #prefix#--nodrop #prefix#--ok-append #prefix#--ok-below #prefix#--ok-between #prefix#--ok-above';
    let rmClass = '';
    let addClass = '';
    let options = {
      rmClass: ''
    } as any;
    if (remove === true) {
      options = {
        rmClass: 'dragging',
        setCanNodeDrag: false
      };
    }

    if ($nodeDd) {
      rmClass = (options.rmClass ? ' node-dd--' + options.rmClass : '');
      addClass = (className ? 'node-dd--' + className : '');

      $nodeDd
        .removeClass(clearClass.replace(new RegExp('#prefix#', 'g'), 'node-dd') + rmClass)
        .addClass(addClass);
    }

    if ($nodesWrap) {
      rmClass = (options.rmClass ? ' nodes-wrapper--' + options.rmClass : '');
      addClass = (className ? 'nodes-wrapper--' + className : '');
      $nodesWrap
        .removeClass(clearClass.replace(new RegExp('#prefix#', 'g'), 'nodes-wrapper') + rmClass)
        .addClass(addClass);
    }

    if ((typeof options.setCanNodeDrag === 'undefined') || options.setCanNodeDrag) {
      this.tree.settings.canNodeDrag = !(className === 'nodrop');
    }
  }

  /**
   * Check if node is dragged at least @distance
   *
   * @param {Event} event
   * @param {DragDropHandler} dragHandler
   * @returns {boolean}
   */
  public isDragNodeDistanceMore(event: d3drag.D3DragEvent<any, any, any>, dragHandler: DragDropHandler): boolean {
    const distance = 10;
    return (dragHandler.startDrag ||
      (((dragHandler.startPageX - distance) > event.sourceEvent.pageX) ||
        ((dragHandler.startPageX + distance) < event.sourceEvent.pageX) ||
        ((dragHandler.startPageY - distance) > event.sourceEvent.pageY) ||
        ((dragHandler.startPageY + distance) < event.sourceEvent.pageY)));
  }

  public changeNodePosition(droppedNode: any, command: string = ''): any {
    const nodes = this.tree.nodes;
    const uid = this.tree.settings.nodeDrag.identifier;
    const index = nodes.indexOf(droppedNode);
    let position = this.tree.settings.nodeDragPosition;
    let target = droppedNode || this.tree.settings.nodeDrag;

    if (uid === target.identifier && command !== 'delete') {
      return;
    }

    if (position === 'before') {
      const positionAndTarget = this.setNodePositionAndTarget(index);
      position = positionAndTarget[0];
      target = positionAndTarget[1];
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
  public setNodePositionAndTarget(index: number): any {
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
      return ['after', target];
    } else if (nodeBeforeDepth < nodeOverDepth) {
      return ['in', target];
    } else {
      for (let i = index; i >= 0; i--) {
        if (nodes[i].depth === nodeOverDepth) {
          return ['after', this.tree.nodes[i]];
        } else if (nodes[i].depth < nodeOverDepth) {
          return ['in', nodes[i]];
        }
      }
    }
  }
}
