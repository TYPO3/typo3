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
import {css, html, LitElement, TemplateResult} from 'lit';
import './item';
import '../../provider/default-result-item';
import {Item, ResultItemActionInterface, ResultItemInterface} from './item';

export const componentName = 'typo3-backend-live-search-result-item-container';

@customElement(componentName)
export class ItemContainer extends LitElement {
  @property({type: Object, attribute: false}) results: ResultItemInterface[]|null = null;
  @property({type: Object, attribute: false}) renderers: { [key: string]: Function } = {};

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    return html`<typo3-backend-live-search-result-list>
      ${this.results.map((result: ResultItemInterface) => this.renderResultItem(result))}
    </typo3-backend-live-search-result-list>`;
  }

  private renderResultItem(resultItem: ResultItemInterface): TemplateResult {
    let innerResultItemComponent;

    if (typeof this.renderers[resultItem.provider] === 'function') {
      innerResultItemComponent = this.renderers[resultItem.provider](resultItem);
    } else {
      innerResultItemComponent = html`<typo3-backend-live-search-result-item-default
        title="${resultItem.typeLabel}: ${resultItem.itemTitle}"
        .icon="${resultItem.icon}"
        .itemTitle="${resultItem.itemTitle}"
        .typeLabel="${resultItem.typeLabel}"
        .extraData="${resultItem.extraData}">
      </typo3-backend-live-search-result-item-default>`;
    }

    return html`<typo3-backend-live-search-result-item
      tabindex="1"
      .resultItem="${resultItem}"
      @click="${() => this.invokeAction(resultItem, resultItem.actions[0])}"
      @focus="${() => this.requestActions(resultItem)}">
      ${innerResultItemComponent}
    </typo3-backend-live-search-result-item>`;
  }

  private requestActions(resultItem: ResultItemInterface) {
    this.parentElement.dispatchEvent(new CustomEvent('livesearch:request-actions', {
      detail: {
        resultItem: resultItem
      }
    }));
  }

  private invokeAction(resultItem: ResultItemInterface, action: ResultItemActionInterface): void {
    this.parentElement.dispatchEvent(new CustomEvent('livesearch:invoke-action', {
      detail: {
        resultItem: resultItem,
        action: action
      }
    }));
  }
}

@customElement('typo3-backend-live-search-result-list')
export class ResultList extends LitElement {
  static styles = css`
    :host {
      display: block;
    }
  `;

  private parentContainer: HTMLElement;
  private resultItemDetailContainer: HTMLElement;

  public connectedCallback() {
    this.parentContainer = this.closest('typo3-backend-live-search-result-container');
    this.resultItemDetailContainer = this.parentContainer.querySelector('typo3-backend-live-search-result-item-detail-container');

    super.connectedCallback();
    this.addEventListener('keydown', this.handleKeyDown);
    this.addEventListener('keyup', this.handleKeyUp);
  }

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }

  private handleKeyDown(e: KeyboardEvent): void {
    if (!['ArrowDown', 'ArrowUp', 'ArrowRight'].includes(e.key)) {
      return;
    }
    if (document.activeElement.tagName.toLowerCase() !== 'typo3-backend-live-search-result-item') {
      return;
    }

    e.preventDefault();

    let focusableCandidate;
    if (e.key === 'ArrowDown') {
      focusableCandidate = document.activeElement.nextElementSibling
    } else if (e.key === 'ArrowUp') {
      focusableCandidate = document.activeElement.previousElementSibling;
      if (focusableCandidate === null) {
        // No possible candidate found, fall back to search input
        focusableCandidate = (document.querySelector('typo3-backend-live-search').querySelector('input[type="search"]'));
      }
    } else if (e.key === 'ArrowRight') {
      focusableCandidate = this.resultItemDetailContainer.querySelector('typo3-backend-live-search-result-item-action');
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

    const resultItem = (e.target as Item).resultItem;
    this.invokeAction(resultItem);
  }

  private invokeAction(item: ResultItemInterface): void {
    this.parentContainer.dispatchEvent(new CustomEvent('livesearch:invoke-action', {
      detail: {
        resultItem: item,
        action: item.actions[0]
      }
    }));
  }
}
