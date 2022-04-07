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

import AjaxRequest from '@typo3/core/ajax/ajax-request';
import {SvgTree, TreeNodeSelection} from '../svg-tree';
import {TreeNode} from '../tree/tree-node';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';

/**
 * A Tree based on SVG for pages, which has a AJAX-based loading of the tree
 * and also handles search + filter via AJAX.
 */
export class PageTree extends SvgTree
{
  protected networkErrorTitle: string = TYPO3.lang.pagetree_networkErrorTitle;
  protected networkErrorMessage: string = TYPO3.lang.pagetree_networkErrorDesc;

  public constructor() {
    super();
    this.settings.defaultProperties = {
      hasChildren: false,
      nameSourceField: 'title',
      itemType: 'pages',
      prefix: '',
      suffix: '',
      locked: false,
      loaded: false,
      overlayIcon: '',
      selectable: true,
      expanded: false,
      checked: false,
      backgroundColor: '',
      stopPageTree: false,
      class: '',
      readableRootline: '',
      isMountPoint: false,
    };
  }

  public showChildren(node: TreeNode) {
    this.loadChildrenOfNode(node);
    super.showChildren(node);
  }

  public nodesUpdate(nodes: TreeNodeSelection): TreeNodeSelection {
    nodes = super.nodesUpdate(nodes);

    // append the stop element
    let nodeStop = nodes
      .append('svg')
      .attr('class', 'node-stop')
      .attr('y', (super.settings.icon.size / 2 * -1))
      .attr('x', (super.settings.icon.size / 2 * -1))
      .attr('height', super.settings.icon.size)
      .attr('width', super.settings.icon.size)
      .attr('visibility', (node: TreeNode) => node.stopPageTree && node.depth !== 0 ? 'visible' : 'hidden')
      .on('click', (evt: MouseEvent, node: TreeNode) => {
        document.dispatchEvent(new CustomEvent('typo3:pagetree:mountPoint', {detail: {pageId: parseInt(node.identifier, 10)}}));
      });
    nodeStop.append('rect')
      .attr('height', super.settings.icon.size)
      .attr('width', super.settings.icon.size)
      .attr('fill', 'rgba(0,0,0,0)');
    nodeStop.append('use')
      .attr('transform-origin', '50% 50%')
      .attr('href', '#icon-actions-caret-right');

    return nodes;
  }

  protected getToggleVisibility(node: TreeNode): string {
    if (node.stopPageTree && node.depth !== 0) {
      return 'hidden';
    }

    return node.hasChildren ? 'visible' : 'hidden';
  }

  /**
   * Loads child nodes via Ajax (used when expanding a collapsed node)
   */
  protected loadChildrenOfNode(parentNode: TreeNode) {
    if (parentNode.loaded) {
      return;
    }

    this.nodesAddPlaceholder();
    (new AjaxRequest(this.settings.dataUrl + '&pid=' + parentNode.identifier + '&mount=' + parentNode.mountPoint + '&pidDepth=' + parentNode.depth))
      .get({cache: 'no-cache'})
      .then((response: AjaxResponse) => response.resolve())
      .then((json: any) => {
        let nodes = Array.isArray(json) ? json : [];
        // first element is a parent
        nodes.shift();
        const index = this.nodes.indexOf(parentNode) + 1;
        // adding fetched node after parent
        nodes.forEach((node: TreeNode, offset: number) => {
          this.nodes.splice(index + offset, 0, node);
        });

        parentNode.loaded = true;
        this.setParametersNode();
        this.prepareDataForVisibleNodes();
        this.updateVisibleNodes();
        this.nodesRemovePlaceholder();

        this.focusNode(parentNode);
      })
      .catch((error: any) => {
        this.errorNotification(error, false)
        this.nodesRemovePlaceholder();
        throw error;
      });
  }
}
