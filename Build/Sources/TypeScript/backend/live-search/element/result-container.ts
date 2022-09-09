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
import {lll} from '@typo3/core/lit-helper';
import '@typo3/backend/element/spinner-element';
import './result-item';
import {ResultItemInterface} from './result-item';

@customElement('typo3-backend-live-search-result-container')
export class ResultContainer extends LitElement {
  @property({type: Object}) results: ResultItemInterface[]|null = null;
  @property({type: Boolean}) loading: boolean = false;

  public connectedCallback() {
    super.connectedCallback();
    this.addEventListener('keydown', this.handleKeyDown);
  }

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    let content: TemplateResult = null;

    if (this.loading) {
      content = html`<div class="d-flex justify-content-center mt-2"><typo3-backend-spinner size="large"></typo3-backend-spinner></div>`;
    }

    if (this.results !== null) {
      if (this.results.length === 0) {
        content = html`<div class="alert alert-info">${lll('liveSearch_listEmptyText')}</div>`;
      } else {
        content = html`${this.results.map((result: ResultItemInterface) => this.renderResultItem(result))}`;
      }
    }

    return html`<typo3-backend-live-search-result-list>${content}</typo3-backend-live-search-result-list>`;
  }

  private renderResultItem(result: ResultItemInterface): TemplateResult {
    return html`<typo3-backend-live-search-result-item
      tabindex="1"
      editLink="${result.editLink}"
      icon="${JSON.stringify(result.icon)}"
      uid="${result.uid}"
      pid="${result.pid}"
      title="${result.typeLabel}: ${result.title} - uid:${result.uid}"
      itemTitle="${result.title}"
      typeLabel="${result.typeLabel}">
    </typo3-backend-live-search-result-item>`;
  }

  private handleKeyDown(e: KeyboardEvent): void {
    e.preventDefault();

    if (!['ArrowDown', 'ArrowUp'].includes(e.key)) {
      return;
    }

    if (document.activeElement.tagName.toLowerCase() !== 'typo3-backend-live-search-result-item') {
      return;
    }

    let focusableCandidate;
    if (e.key === 'ArrowDown') {
      focusableCandidate = document.activeElement.nextElementSibling
    } else {
      focusableCandidate = document.activeElement.previousElementSibling;
      if (focusableCandidate === null) {
        // No possible candidate found, fall back to search input
        focusableCandidate = (document.getElementById('backend-live-search').querySelector('input[type="search"]'));
      }
    }

    if (focusableCandidate !== null) {
      (focusableCandidate as HTMLElement).focus();
    }
  }
}

@customElement('typo3-backend-live-search-result-list')
export class ResultList extends LitElement {
  static styles = css`
    :host {
      display: block;
    }
  `;

  protected render(): TemplateResult {
    return html`<slot></slot>`;
  }
}
