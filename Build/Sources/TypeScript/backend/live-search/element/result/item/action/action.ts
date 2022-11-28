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

import {customElement, property} from 'lit/decorators';
import {ifDefined} from 'lit/directives/if-defined';
import {html, LitElement, TemplateResult} from 'lit';
import '@typo3/backend/element/icon-element';
import {ResultItemActionInterface, ResultItemInterface} from '../item';

@customElement('typo3-backend-live-search-result-item-action')
export class Action extends LitElement {
  @property({type: Object, attribute: false}) resultItem: ResultItemInterface;
  @property({type: Object, attribute: false}) resultItemAction: ResultItemActionInterface;

  public connectedCallback() {
    super.connectedCallback();

    if (!this.hasAttribute('tabindex')) {
      this.setAttribute('tabindex', '0');
    }
  }

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    return html`
      <div>
        <div class="livesearch-result-item-icon">
          <typo3-backend-icon identifier="${ifDefined(this.resultItemAction.icon.identifier || 'actions-arrow-right')}" overlay="${this.resultItemAction.icon.overlay}" size="small"></typo3-backend-icon>
        </div>
        <div class="livesearch-result-item-title">
          ${this.resultItemAction.label}
        </div>
      </div>
    `;
  }
}
