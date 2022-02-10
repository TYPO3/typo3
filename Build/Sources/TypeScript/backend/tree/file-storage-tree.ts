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
import {SvgTree} from '../svg-tree';
import {TreeNode} from '../tree/tree-node';
import {AjaxResponse} from '@typo3/core/ajax/ajax-response';

/**
 * A tree for folders / storages
 */
export class FileStorageTree extends SvgTree {
  public constructor() {
    super();
    this.settings.defaultProperties = {
      hasChildren: false,
      nameSourceField: 'title',
      itemType: 'sys_file',
      prefix: '',
      suffix: '',
      locked: false,
      loaded: false,
      overlayIcon: '',
      selectable: true,
      expanded: false,
      checked: false,
      backgroundColor: '',
      class: '',
      readableRootline: ''
    };
  }

  public showChildren(node: TreeNode): void {
    this.loadChildrenOfNode(node);
    super.showChildren(node);
  }

  protected getNodeTitle(node: TreeNode): string {
    return decodeURIComponent(node.name);
  }

  /**
   * Loads child nodes via Ajax (used when expanding a collapsed node)
   */
  private loadChildrenOfNode(parentNode: TreeNode): void {
    if (parentNode.loaded) {
      this.prepareDataForVisibleNodes();
      this.updateVisibleNodes();
      return;
    }
    this.nodesAddPlaceholder();

    (new AjaxRequest(this.settings.dataUrl + '&parent=' + parentNode.identifier + '&currentDepth=' + parentNode.depth))
      .get({cache: 'no-cache'})
      .then((response: AjaxResponse) => response.resolve())
      .then((json: any) => {
        let nodes = Array.isArray(json) ? json : [];
        const index = this.nodes.indexOf(parentNode) + 1;
        //adding fetched node after parent
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
        this.errorNotification(error, false);
        this.nodesRemovePlaceholder();
        throw error;
      });
  }
}
