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

import '@typo3/backend/element/spinner-element';
import LiveSearchConfigurator from '@typo3/backend/live-search/live-search-configurator';
import { css, html, LitElement, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators';
import { until } from 'lit/directives/until';
import '../../provider/default-result-item';
import { type Item, type ResultItemActionInterface, type ResultItemInterface } from './item';
import type { InvokeActionEventData, RequestActionsEventData } from '@typo3/backend/live-search/element/result/result-container';

type GroupedResultItems = { [key: string ]: ResultItemInterface[] };

export const componentName = 'typo3-backend-live-search-result-item-container';

@customElement('typo3-backend-live-search-result-item-container')
export class ItemContainer extends LitElement {
  @property({ type: Object, attribute: false }) results: ResultItemInterface[]|null = null;

  public override connectedCallback(): void {
    super.connectedCallback();
    this.addEventListener('scroll', this.onScroll);
  }

  public override disconnectedCallback(): void {
    this.removeEventListener('scroll', this.onScroll);
    super.disconnectedCallback();
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected override render(): TemplateResult {
    const groupedResults: GroupedResultItems = {};
    const filteredResults = this.results.filter((result: ResultItemInterface): boolean => result !== null);
    if (filteredResults.length !== this.results.length) {
      console.warn(
        'The result set contained "null" values, indicating something went wrong while building the search results. Affected values were removed to no break the user interface.'
      );
    }
    filteredResults.forEach((result: ResultItemInterface): void => {
      if (!(result.typeLabel in groupedResults)) {
        groupedResults[result.typeLabel] = [result];
      } else {
        groupedResults[result.typeLabel].push(result);
      }
    });

    return html`<typo3-backend-live-search-result-list>
      ${this.renderGroupedResults(groupedResults)}
    </typo3-backend-live-search-result-list>`;
  }

  private renderGroupedResults(groupedResults: GroupedResultItems): TemplateResult {
    const items = [];
    for (const [type, results] of Object.entries(groupedResults)) {
      const countElements = results.length;
      items.push(html`<h6 class="livesearch-result-item-group-label">${type} (${countElements})</h6>`);
      items.push(...results.map((result: ResultItemInterface) => html`${until(
        this.renderResultItem(result),
        html`<typo3-backend-spinner></typo3-backend-spinner>`
      )}`));
    }

    return html`${items}`;
  }

  private async renderResultItem(resultItem: ResultItemInterface): Promise<TemplateResult> {
    const renderers = LiveSearchConfigurator.getRenderers();
    let innerResultItemComponent;

    if (renderers[resultItem.provider] !== undefined) {
      await import(renderers[resultItem.provider].module);
      innerResultItemComponent = renderers[resultItem.provider].callback(resultItem);
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
      .resultItem="${resultItem}"
      @click="${() => this.invokeAction(resultItem, resultItem.actions[0])}"
      @focus="${() => this.requestActions(resultItem)}">
      ${innerResultItemComponent}
    </typo3-backend-live-search-result-item>`;
  }

  private requestActions(resultItem: ResultItemInterface) {
    this.parentElement.dispatchEvent(new CustomEvent<RequestActionsEventData>('livesearch:request-actions', {
      detail: {
        resultItem: resultItem
      }
    }));
  }

  private invokeAction(resultItem: ResultItemInterface, action: ResultItemActionInterface): void {
    this.parentElement.dispatchEvent(new CustomEvent<InvokeActionEventData>('livesearch:invoke-action', {
      detail: {
        resultItem: resultItem,
        action: action
      }
    }));
  }

  private onScroll(e: Event): void {
    this.querySelectorAll('.livesearch-result-item-group-label').forEach((groupLabel: HTMLElement): void => {
      groupLabel.classList.toggle('sticky', groupLabel.offsetTop <= (e.target as HTMLElement).scrollTop);
    });
  }
}

@customElement('typo3-backend-live-search-result-list')
export class ResultList extends LitElement {
  static override styles = css`
    :host {
      display: block;
    }
  `;

  private parentContainer: HTMLElement;
  private resultItemDetailContainer: HTMLElement;

  public override connectedCallback(): void {
    this.parentContainer = this.closest('typo3-backend-live-search-result-container');
    this.resultItemDetailContainer = this.parentContainer.querySelector('typo3-backend-live-search-result-item-detail-container');

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
    if (!['ArrowDown', 'ArrowUp', 'ArrowRight'].includes(e.key)) {
      return;
    }

    const expectedTagName = 'typo3-backend-live-search-result-item';
    if (document.activeElement.tagName.toLowerCase() !== expectedTagName) {
      return;
    }

    e.preventDefault();

    let focusableCandidate;
    if (e.key === 'ArrowDown') {
      let nextSibling = document.activeElement.nextElementSibling;
      while (nextSibling !== null && nextSibling.tagName.toLowerCase() !== expectedTagName) {
        nextSibling = nextSibling.nextElementSibling;
      }
      focusableCandidate = nextSibling;
    } else if (e.key === 'ArrowUp') {
      let prevSibling = document.activeElement.previousElementSibling;
      while (prevSibling !== null && prevSibling.tagName.toLowerCase() !== expectedTagName) {
        prevSibling = prevSibling.previousElementSibling;
      }
      focusableCandidate = prevSibling;
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
    this.parentContainer.dispatchEvent(new CustomEvent<InvokeActionEventData>('livesearch:invoke-action', {
      detail: {
        resultItem: item,
        action: item.actions[0]
      }
    }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-live-search-result-item-container': ItemContainer;
    'typo3-backend-live-search-result-list': ResultList;
  }
}
