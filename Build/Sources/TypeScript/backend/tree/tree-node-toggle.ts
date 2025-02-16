/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import { customElement, property } from 'lit/decorators';
import { html, LitElement, type TemplateResult } from 'lit';
import '@typo3/backend/element/icon-element';

@customElement('typo3-backend-tree-node-toggle')
export default class TreeNodeToggle extends LitElement {
  @property({ type: String, reflect: true, attribute: 'aria-expanded' }) expanded: string = 'false';

  protected override render(): TemplateResult | symbol {
    return html`<typo3-backend-icon size="small" identifier="${this.expanded === 'true' ? 'actions-chevron-down' : 'actions-chevron-right'}"></typo3-backend-icon>`;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-tree-node-toggle': TreeNodeToggle;
  }
}
