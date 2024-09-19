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

import { html, css, LitElement, TemplateResult, nothing } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import '@typo3/backend/element/icon-element';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface';
import Notification from '@typo3/backend/notification';

@customElement('typo3-scheduler-editable-group-name')
export class EditableGroupName extends LitElement {
  static styles = css`
    :host {
      display: block;
      --input-border-color: #bebebe;
      --input-hover-border-color: #bebebe;
      --input-focus-border-color: #bebebe;
      --button-border-radius:
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

    .label {
      display: block;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      white-space: nowrap;
      text-overflow: ellipsis;
      overflow: hidden;
      padding: calc(1px + .16rem)  0;
      margin: 0;
    }

    input {
      outline: none;
      background: transparent;
      font-weight: inherit;
      font-size: inherit;
      font-family: inherit;
      line-height: inherit;
      padding: .16rem 0;
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
      padding-inline-end: 2.5em;
    }

    form.wrapper {
      padding-inline-end: 5em;
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
      width: 2em;
      position: absolute;
      top: 0;
      border-radius: 2px;
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
      inset-inline-end: calc(2em + 2px);
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
  @property({ type: String }) groupName: string = '';
  @property({ type: Number }) groupId: number = 0;
  @property({ type: Boolean }) editable: boolean = false;
  @state() _isEditing: boolean = false;
  @state() _isSubmitting: boolean = false;

  protected labels: Record<string, string> = {
    input: TYPO3?.lang?.['editableGroupName.input.field.label'] || 'Field',
    edit: TYPO3?.lang?.['editableGroupName.button.edit.label'] || 'Edit',
    save: TYPO3?.lang?.['editableGroupName.button.save.label'] || 'Save',
    cancel: TYPO3?.lang?.['editableGroupName.button.cancel.label'] || 'Cancel',
  }

  async startEditing(): Promise<void> {
    if (this.isEditable()) {
      this._isEditing = true;
      await this.updateComplete;
      this.shadowRoot.querySelector('input')?.focus();
    }
  }

  protected render(): TemplateResult | symbol {
    if (this.groupName === '') {
      return nothing;
    }

    if (!this.isEditable()) {
      return html`
        <div class="wrapper"><div class="label">${this.groupName}</div></div>`;
    }

    let content;

    if (!this._isEditing) {
      content = html`
        <div class="wrapper">
          <div class="label" @dblclick="${(): void => { this.startEditing(); }}">${this.groupName}</div>
          ${this.composeEditButton()}
        </div>`;
    } else {
      content = this.composeEditForm();
    }

    return content;
  }

  private isEditable(): boolean {
    return this.editable && this.groupId > 0;
  }

  private endEditing(): void {
    if (this.isEditable()) {
      this._isEditing = false;
    }
  }

  private updateGroupName(e: SubmitEvent): void {
    e.preventDefault();

    const formData = new FormData(e.target as HTMLFormElement);
    const submittedData = Object.fromEntries(formData);
    const newGroupName = submittedData.newGroupName.toString();

    if (this.groupName === newGroupName) {
      this.endEditing();
      return;
    }

    this._isSubmitting = true;

    const params = '&data[tx_scheduler_task_group][' + this.groupId + '][groupName]=' + encodeURIComponent(newGroupName) + '&redirect=' + encodeURIComponent(document.location.href);
    (new AjaxRequest(TYPO3.settings.ajaxUrls.record_process)).post(params, {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
    }).then(async (response: AjaxResponse): Promise<ResponseInterface> => {
      return await response.resolve();
    }).then((result: ResponseInterface): ResponseInterface => {
      result.messages.forEach((message) => {
        Notification.info(message.title, message.message);
        // Reload to avoid inconsistent UI, in case the
        // renamed group name is not unique
        window.location.href = result.redirect;
      });

      return result;
    }).then(() => {
      this.groupName = newGroupName;
    }).finally(() => {
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
      <form class="wrapper" @submit="${ this.updateGroupName }">
        <label class="screen-reader" for="input">${this.labels.input}</label>
        <input
          autocomplete="off"
          id="input"
          name="newGroupName"
          required
          value="${this.groupName}"
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
    'typo3-scheduler-editable-group-name': EditableGroupName;
  }
}
