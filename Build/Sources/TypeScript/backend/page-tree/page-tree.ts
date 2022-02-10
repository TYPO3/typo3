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
    nodes
      .append('text')
      .text('+')
      .attr('class', 'node-stop')
      .attr('dx', 30)
      .attr('dy', 5)
      .attr('visibility', (node: TreeNode) => node.stopPageTree && node.depth !== 0 ? 'visible' : 'hidden')
      .on('click', (evt: MouseEvent, node: TreeNode) => {
        document.dispatchEvent(new CustomEvent('typo3:pagetree:mountPoint', {detail: {pageId: parseInt(node.identifier, 10)}}));
      });
    return nodes;
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

        this.switchFocusNode(parentNode);
      })
      .catch((error: any) => {
        this.errorNotification(error, false)
        this.nodesRemovePlaceholder();
        throw error;
      });
  }

  /**
   * Changed text position if there is 'stop page tree' option
   */
  protected appendTextElement(nodes: TreeNodeSelection): TreeNodeSelection {
    return super.appendTextElement(nodes)
      .attr('dx', (node) => {
        let position = this.textPosition;
        if (node.stopPageTree && node.depth !== 0) {
          position += 15;
        }
        if (node.locked) {
          position += 15;
        }
        return position;
      });
  };
}
