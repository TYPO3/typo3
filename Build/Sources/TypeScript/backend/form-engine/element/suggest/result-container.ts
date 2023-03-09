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
import { css, html, LitElement, TemplateResult } from 'lit';
import { lll } from '@typo3/core/lit-helper';
import './result-item';
import { ResultItemInterface } from './result-item';

@customElement('typo3-backend-formengine-suggest-result-container')
export class ResultContainer extends LitElement {
  @property({ type: Object }) results: ResultItemInterface[]|null = null;

  public connectedCallback(): void {
    super.connectedCallback();

    this.addEventListener('keydown', this.handleKeyDown);
  }

  public disconnectedCallback(): void {
    this.removeEventListener('keydown', this.handleKeyDown);

    super.disconnectedCallback();
  }

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    let content;
    if (this.results !== null) {
      if (this.results.length === 0) {
        content = html`<div class="alert alert-info">${lll('search.no_records_found')}</div>`;
      } else {
        content = html`${this.results.map((result: ResultItemInterface) => this.renderResultItem(result))}`;
      }
    }

    return html`<typo3-backend-formengine-suggest-result-list>${content}</typo3-backend-formengine-suggest-result-list>`;
  }

  private renderResultItem(result: ResultItemInterface): TemplateResult {
    return html`<typo3-backend-formengine-suggest-result-item
      tabindex="1"
      icon="${JSON.stringify(result.icon)}"
      uid="${result.uid}"
      table="${result.table}"
      label="${result.label}"
      path="${result.path}">
    </typo3-backend-formengine-suggest-result-item>`;
  }

  private handleKeyDown(e: KeyboardEvent): void {
    e.preventDefault();

    if (e.key === 'Escape') {
      (this.closest('.t3-form-suggest-container').querySelector('input[type="search"]') as HTMLInputElement).focus();
      this.hidden = true;

      return;
    }

    if (!['ArrowDown', 'ArrowUp'].includes(e.key)) {
      return;
    }

    if (document.activeElement.tagName.toLowerCase() !== 'typo3-backend-formengine-suggest-result-item') {
      return;
    }

    let focusableCandidate;
    if (e.key === 'ArrowDown') {
      focusableCandidate = document.activeElement.nextElementSibling;
    } else {
      focusableCandidate = document.activeElement.previousElementSibling;
      if (focusableCandidate === null) {
        // No possible candidate found, fall back to search input
        focusableCandidate = this.closest('.t3-form-suggest-container').querySelector('input[type="search"]');
      }
    }

    if (focusableCandidate !== null) {
      (focusableCandidate as HTMLElement).focus();
    }
  }
}

@customElement('typo3-backend-formengine-suggest-result-list')
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
