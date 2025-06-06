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
import { customElement, property, state } from 'lit/decorators';
import '@typo3/backend/element/icon-element';
import AjaxDataHandler from '../ajax-data-handler';

@customElement('typo3-backend-editable-page-title')
export class EditablePageTitle extends LitElement {
  static override styles = css`
    :host {
      display: block;
      --input-border-color: #bebebe;
      --input-hover-border-color: #bebebe;
      --input-focus-border-color: #bebebe;
      --button-border-radius: 2px;
      --button-color: inherit;
      --button-bg: transparent;
      --button-border-color: transparent;
      --button-hover-color: inherit;
      --button-hover-bg: #cacaca;
      --button-hover-border-color: #bebebe;
      --button-focus-color: inherit;
      --button-focus-bg: #cacaca;
      --button-focus-border-color: #bebebe;
    }

    h1 {
      display: block;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      padding: 1px 0;
      margin: 0;
    }

    input {
      outline: none;
      background: transparent;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      padding: 0;
      border: 0;
      border-top: 1px solid transparent;
      border-bottom: 1px dashed var(--input-border-color);
      margin: 0;
      width: 100%;
      outline-offset: 0;
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

    .wrapper {
      position: relative;
      margin: -1px 0;
    }

    div.wrapper {
      padding-inline-end: 1.5em;
    }

    form.wrapper {
      padding-inline-end: 2.5em;
    }

    button {
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: inherit;
      line-height: inherit;
      border: 0;
      padding: 0;
      height: 100%;
      width: 1em;
      position: absolute;
      top: 0;
      border-radius: var(--button-border-radius);
      overflow: hidden;
      outline: none;
      color: var(--button-color);
      background: var(--button-bg);
      border: 1px solid var(--button-border-color);
      opacity: .3;
      outline-offset: 0;
      transition: all .2s ease-in-out;
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

    button[data-action="edit"] {
      inset-inline-end: 0;
    }

    button[data-action="save"] {
      inset-inline-end: calc(1em + 2px);
    }

    button[data-action="close"] {
      inset-inline-end: 0;
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
    input: TYPO3?.lang?.['editablePageTitle.input.field.label'] || 'Field',
    edit: TYPO3?.lang?.['editablePageTitle.button.edit.label'] || 'Edit',
    save: TYPO3?.lang?.['editablePageTitle.button.save.label'] || 'Save',
    cancel: TYPO3?.lang?.['editablePageTitle.button.cancel.label'] || 'Cancel',
  };

  async startEditing(): Promise<void> {
    if (this.isEditable()) {
      this._isEditing = true;
      await this.updateComplete;
      this.shadowRoot.querySelector('input')?.focus();
    }
  }

  protected override render(): TemplateResult | symbol {
    if (this.pageTitle === '') {
      return nothing;
    }

    if (!this.isEditable()) {
      return html`<div class="wrapper"><h1>${this.pageTitle}</h1></div>`;
    }

    let content;
    if (!this._isEditing) {
      content = html`
        <div class="wrapper">
          <h1 @dblclick="${(): void => { this.startEditing(); }}">${this.pageTitle}</h1>
          ${this.composeEditButton()}
        </div>`;
    } else {
      content = this.composeEditForm();
    }

    return content;
  }

  private isEditable(): boolean {
    return this.editable && this.pageId > 0;
  }

  private endEditing(): void {
    if (this.isEditable()) {
      this._isEditing = false;
    }
  }

  private updatePageTitle(e: SubmitEvent): void {
    e.preventDefault();

    const formData = new FormData(e.target as HTMLFormElement);
    const submittedData = Object.fromEntries(formData);
    const newPageTitle = submittedData.newPageTitle.toString();

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
      top.document.dispatchEvent(new CustomEvent('typo3:pagetree:refresh'));
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
      <form class="wrapper" @submit="${ this.updatePageTitle }">
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
