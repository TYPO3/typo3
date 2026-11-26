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

import { html, css, LitElement, type TemplateResult, nothing } from 'lit';
import { customElement, property, state } from 'lit/decorators.js';
import '@typo3/backend/element/icon-element';
import AjaxDataHandler from '../ajax-data-handler';
import commonLabels from '~labels/core.common';
import layoutLabels from '~labels/backend.layout';
import { topDocument } from '@typo3/core/utility/top-window';

@customElement('typo3-backend-editable-page-title')
export class EditablePageTitle extends LitElement {
  static override styles = css`
    :host {
      display: block;
      --input-color: var(--typo3-input-color);
      --input-bg: var(--typo3-input-bg);
      --input-border-width: var(--typo3-input-border-width);
      --input-border-radius: calc(var(--button-size) / 3);
      --input-border-color: var(--typo3-input-border-color);
      --input-hover-border-color: var(--typo3-input-hover-border-color);
      --input-focus-border-color: var(--typo3-input-focus-border-color);
      --input-padding-x: .25em;
      --button-size: calc(1lh + 2 * var(--input-border-width));
      --button-border-radius: calc(var(--button-size) / 3);
      --button-color: var(--typo3-state-default-color);
      --button-bg: var(--typo3-state-default-bg);
      --button-border-color: var(--typo3-state-default-border-color);
      --button-hover-color: var(--typo3-state-default-hover-color);
      --button-hover-bg: var(--typo3-state-default-hover-bg);
      --button-hover-border-color: var(--typo3-state-default-hover-border-color);
      --button-focus-color: var(--typo3-state-default-focus-color);
      --button-focus-bg: var(--typo3-state-default-focus-bg);
      --button-focus-border-color: var(--typo3-state-default-focus-border-color);
    }

    .wrapper {
      position: relative;
      margin: calc(-1 * var(--input-border-width)) 0;
    }

    .page-title {
      display: flex;
      align-items: center;
      gap: .25em;
      min-width: 0;
    }

    h1 {
      flex: 0 1 auto;
      min-width: 0;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      padding: var(--input-border-width) 0;
      margin: 0;
    }

    .wrapper:has(> form) .page-title {
      visibility: hidden;
      pointer-events: none;
    }

    .wrapper > form {
      position: absolute;
      inset-block: 0;
      inset-inline-start: calc(-1 * (var(--input-padding-x) + var(--input-border-width)));
      inset-inline-end: 0;
      display: flex;
      align-items: center;
      gap: .25em;
    }

    input {
      flex: 1 1 auto;
      min-width: 0;
      box-sizing: border-box;
      height: var(--button-size);
      margin: 0;
      padding: 0 var(--input-padding-x);
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      color: var(--input-color);
      background-color: var(--input-bg);
      border: var(--input-border-width) solid var(--input-border-color);
      border-radius: var(--input-border-radius);
      outline: none;
      outline-offset: 0;
      transition: border-color .15s ease-in-out, outline-color .15s ease-in-out;
    }

    input:hover {
      --input-border-color: var(--input-hover-border-color);
    }

    input:focus {
      --input-border-color: var(--input-focus-border-color);
    }

    input:focus-visible {
      outline: .25rem solid color-mix(in srgb, var(--input-border-color), transparent 25%);
    }

    button {
      cursor: pointer;
      display: inline-flex;
      flex: 0 0 auto;
      align-items: center;
      justify-content: center;
      box-sizing: border-box;
      width: var(--button-size);
      height: var(--button-size);
      padding: 0;
      margin: 0;
      font-size: inherit;
      line-height: inherit;
      border-radius: var(--button-border-radius);
      overflow: hidden;
      outline: none;
      color: var(--button-color);
      background: var(--button-bg);
      border: var(--input-border-width) solid var(--button-border-color);
      outline-offset: 0;
      transition: all .15s ease-in-out;
    }

    button[data-action="edit"] {
      --button-bg: transparent;
      --button-border-color: transparent;
      opacity: .8;
    }

    button:hover {
      opacity: 1;
      --button-color: var(--button-hover-color);
      --button-bg: var(--button-hover-bg);
      --button-border-color: var(--button-hover-border-color);
    }

    button:focus {
      opacity: 1;
      --button-color: var(--button-focus-color);
      --button-bg: var(--button-focus-bg);
      --button-border-color: var(--button-focus-border-color);
    }

    button:focus-visible {
      outline: .25rem solid color-mix(in srgb, var(--button-border-color), transparent 25%);
    }

    .screen-reader {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0,0,0,0);
      white-space: nowrap;
      border: 0
    }
    `;
  @property({ type: String }) pageTitle: string = '';
  @property({ type: Number }) pageId: number = 0;
  @property({ type: Number }) localizedPageId: number = 0;
  @property({ type: Boolean }) editable: boolean = false;
  @state() _isEditing: boolean = false;
  @state() _isSubmitting: boolean = false;

  protected labels: Record<string, string> = {
    input: layoutLabels.get('editPageTitle.input.field.label'),
    edit: layoutLabels.get('editPageTitle'),
    save: commonLabels.get('save'),
    cancel: commonLabels.get('cancel'),
  };

  async startEditing(): Promise<void> {
    if (this.isEditable()) {
      this._isEditing = true;
      await this.updateComplete;
      const input = this.shadowRoot!.querySelector('input');
      input?.focus();
      input?.setSelectionRange(input.value.length, input.value.length);
    }
  }

  protected override render(): TemplateResult | symbol {
    if (this.pageTitle === '') {
      return nothing;
    }

    if (!this.isEditable()) {
      return html`<div class="wrapper"><h1>${this.pageTitle}</h1></div>`;
    }

    return html`
      <div class="wrapper">
        <div class="page-title">
          <h1 @dblclick="${(): void => { this.startEditing(); }}">${this.pageTitle}</h1>
          ${this.composeEditButton()}
        </div>
        ${this._isEditing ? this.composeEditForm() : nothing}
      </div>
    `;
  }

  private isEditable(): boolean {
    return this.editable && this.pageId > 0;
  }

  private async endEditing(): Promise<void> {
    if (this.isEditable()) {
      this._isEditing = false;
      await new Promise<void>((resolve) => requestAnimationFrame(() => requestAnimationFrame(() => resolve())));
      this.shadowRoot!.querySelector<HTMLButtonElement>('button[data-action="edit"]')?.focus();
    }
  }

  private updatePageTitle(e: SubmitEvent): void {
    e.preventDefault();

    const newPageTitle = new FormData(e.target as HTMLFormElement).get('newPageTitle');
    if (typeof newPageTitle !== 'string') {
      return;
    }

    if (this.pageTitle === newPageTitle) {
      // Page title didn't change, no need to update anything
      this.endEditing();
      return;
    }

    this._isSubmitting = true;

    let recordUid = this.pageId;
    if (this.localizedPageId > 0) {
      recordUid = this.localizedPageId;
    }

    const parameters = {
      data: {
        pages: {
          [recordUid]: {
            title: newPageTitle
          }
        }
      }
    };

    AjaxDataHandler.process(parameters).then((): void => {
      this.pageTitle = newPageTitle;
      topDocument.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
    }).finally((): void => {
      this.endEditing();
      this._isSubmitting = false;
    });
  }

  private composeEditButton(): TemplateResult {
    return html`
      <button
        data-action="edit"
        type="button"
        title="${this.labels.edit}"
        @click="${(): void => { this.startEditing(); }}"
      >
        <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
        <span class="screen-reader">${this.labels.edit}</span>
      </button>`;
  }

  private composeEditForm(): TemplateResult {
    return html`
      <form @submit="${ this.updatePageTitle }">
        <label class="screen-reader" for="input">${this.labels.input}</label>
        <input
          autocomplete="off"
          id="input"
          name="newPageTitle"
          required
          value="${this.pageTitle}"
          ?disabled="${this._isSubmitting}"
          @keydown="${(e: KeyboardEvent): void => { if (e.key === 'Escape') { this.endEditing(); } }}"
        >
        <button
          data-action="save"
          type="submit"
          title="${this.labels.save}"
          ?disabled="${this._isSubmitting}"
        >
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
          <span class="screen-reader">${this.labels.save}</span>
        </button>
        <button
          data-action="close"
          type="button"
          title="${this.labels.cancel}"
          ?disabled="${this._isSubmitting}"
          @click="${(): void => { this.endEditing(); }}"
        >
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
          <span class="screen-reader">${this.labels.cancel}</span>
        </button>
      </form>`;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-editable-page-title': EditablePageTitle;
  }
}
