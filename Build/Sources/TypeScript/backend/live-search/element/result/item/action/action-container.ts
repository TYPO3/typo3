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

import { customElement, property } from 'lit/decorators';
import { css, html, LitElement, type TemplateResult } from 'lit';
import { type Action } from './action';
import type { ResultItemActionInterface, ResultItemInterface } from '../item';
import type { InvokeActionEventData } from '@typo3/backend/live-search/element/result/result-container';

export const componentName = 'typo3-backend-live-search-result-item-action-container';

@customElement('typo3-backend-live-search-result-item-action-container')
export class ActionContainer extends LitElement {
  @property({ type: Object, attribute: false }) resultItem: ResultItemInterface|null = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected override render(): TemplateResult {
    return html`<typo3-backend-live-search-result-action-list>
      ${this.resultItem.actions.map((action: ResultItemActionInterface) => this.renderActionItem(this.resultItem, action))}
    </typo3-backend-live-search-result-action-list>`;
  }

  private renderActionItem(resultItem: ResultItemInterface, action: ResultItemActionInterface): TemplateResult {
    return html`<typo3-backend-live-search-result-item-action
      .resultItem="${resultItem}"
      .resultItemAction="${action}"
      @click="${() => this.invokeAction(this.resultItem, action)}">
    </typo3-backend-live-search-result-item-action>`;
  }

  private invokeAction(resultItem: ResultItemInterface, action: ResultItemActionInterface): void {
    this.closest('typo3-backend-live-search-result-container').dispatchEvent(new CustomEvent<InvokeActionEventData>('livesearch:invoke-action', {
      detail: {
        resultItem: resultItem,
        action: action
      }
    }));
  }
}

@customElement('typo3-backend-live-search-result-action-list')
export class ActionList extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }
  `;

  private parentContainer: HTMLElement;
  private resultItemContainer: HTMLElement;

  public override connectedCallback(): void {
    this.parentContainer = this.closest('typo3-backend-live-search-result-container');
    this.resultItemContainer = this.parentContainer.querySelector('typo3-backend-live-search-result-item-container');

    super.connectedCallback();
    this.addEventListener('keydown', this.handleKeyDown);
    this.addEventListener('keyup', this.handleKeyUp);
  }

  public override disconnectedCallback(): void {
    this.removeEventListener('keydown', this.handleKeyDown);
    this.removeEventListener('keyup', this.handleKeyUp);

    super.disconnectedCallback();
  }

  protected override render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private handleKeyDown(e: KeyboardEvent): void {
    if (!['ArrowDown', 'ArrowUp', 'ArrowLeft'].includes(e.key)) {
      return;
    }
    if (document.activeElement.tagName.toLowerCase() !== 'typo3-backend-live-search-result-item-action') {
      return;
    }

    e.preventDefault();

    let focusableCandidate;
    if (e.key === 'ArrowDown') {
      focusableCandidate = document.activeElement.nextElementSibling;
    } else if (e.key === 'ArrowUp') {
      focusableCandidate = document.activeElement.previousElementSibling;
    } else if (e.key === 'ArrowLeft') {
      focusableCandidate = this.resultItemContainer.querySelector('typo3-backend-live-search-result-item.active');
    }

    if (focusableCandidate !== null) {
      (focusableCandidate as HTMLElement).focus();
    }
  }

  private handleKeyUp(e: KeyboardEvent): void {
    if (!['Enter', ' '].includes(e.key)) {
      return;
    }

    e.preventDefault();

    const actionElement = e.target as Action;
    this.parentContainer.dispatchEvent(new CustomEvent<InvokeActionEventData>('livesearch:invoke-action', {
      detail: {
        resultItem: actionElement.resultItem,
        action: actionElement.resultItemAction
      }
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-result-item-action-container': ActionContainer;
    'typo3-backend-live-search-result-action-list': ActionList;
  }
}
