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
import {html, LitElement, TemplateResult} from 'lit';
import '@typo3/backend/element/icon-element';

export interface ResultItemInterface {
  editLink: string;
  icon: { [key: string]: string };
  uid: number;
  pid: number;
  title: string;
  typeLabel: string;
}

@customElement('typo3-backend-live-search-result-item')
export class ResultItem extends LitElement {
  @property({type: String}) editLink: string;
  @property({type: Object}) icon: { [key: string]: string };
  @property({type: Number}) uid: number;
  @property({type: Number}) pid: number;
  @property({type: String}) itemTitle: string;
  @property({type: String}) typeLabel: string;

  public connectedCallback() {
    super.connectedCallback();

    this.addEventListener('click', (e: PointerEvent): void => {
      e.preventDefault();
      this.dispatchItemChosenEvent();
    });
    this.addEventListener('keyup', (e: KeyboardEvent): void => {
      e.preventDefault();

      // Trigger item selection when pressing ENTER or SPACE
      if (['Enter', ' '].includes(e.key)) {
        this.dispatchItemChosenEvent();
      }
    });
  }

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    return html`
      <div class="livesearch-result-item-icon">
        <typo3-backend-icon title="${this.icon.title}" identifier="${this.icon.identifier}" overlay="${this.icon.overlay}" size="small"></typo3-backend-icon>
      </div>
      <div class="livesearch-result-item-title">
        ${this.itemTitle} <small>- uid:${this.uid}</small>
      </div>
      <div class="livesearch-result-item-type">
        ${this.typeLabel}
      </div>
    `;
  }

  private dispatchItemChosenEvent(): void {
    document.dispatchEvent(new CustomEvent('live-search:item-chosen', {
      detail: {
        callback: (): void => {
          TYPO3.Backend.ContentContainer.setUrl(this.editLink);
        }
      }
    }));
  }
}
