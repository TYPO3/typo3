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

import { Tree } from '@typo3/backend/tree/tree';
import { TreeNodeInterface } from '@typo3/backend/tree/tree-node';
import { TemplateResult, html } from 'lit';

/**
 * A Tree based on for pages, which has a AJAX-based loading of the tree
 * and also handles search + filter via AJAX.
 */
export class PageTree extends Tree
{
  public constructor() {
    super();
    this.settings.defaultProperties = {
      hasChildren: false,
      nameSourceField: 'title',
      prefix: '',
      suffix: '',
      locked: false,
      loaded: false,
      overlayIcon: '',
      selectable: true,
      expanded: false,
      checked: false,
      stopPageTree: false,
    };
  }

  public getDataUrl(parentNode: TreeNodeInterface|null = null): string {
    if (parentNode === null) {
      return this.settings.dataUrl;
    }

    return this.settings.dataUrl + '&parent=' + parentNode.identifier + '&mount=' + parentNode.mountPoint + '&depth=' + parentNode.depth
  }

  protected createNodeToggle(node: TreeNodeInterface): TemplateResult {
    const nodeStopIconIdentifier = this.isRTL() ? 'actions-caret-left' : 'actions-caret-right';
    return html`${node.stopPageTree && node.depth !== 0
      ? html`
          <span class="node-stop" @click="${(event: PointerEvent) => { event.preventDefault(); event.stopImmediatePropagation(); document.dispatchEvent(new CustomEvent('typo3:pagetree:mountPoint', { detail: { pageId: parseInt(node.identifier, 10) } })); }}">
            <typo3-backend-icon identifier="${nodeStopIconIdentifier}" size="small"></typo3-backend-icon>
          </span>
        `
      : super.createNodeToggle(node)
    }`;
  }
}
