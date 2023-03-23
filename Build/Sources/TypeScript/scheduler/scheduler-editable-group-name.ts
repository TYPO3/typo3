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

import { lll } from '@typo3/core/lit-helper';
import { html, css, LitElement, TemplateResult, nothing } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import '../backend/element/icon-element';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import ResponseInterface from '@typo3/backend/ajax-data-handler/response-interface';
import Notification from '@typo3/backend/notification';

@customElement('typo3-scheduler-editable-group-name')
export class EditableGroupName extends LitElement {
  static styles = css`
    :host {
      display: inline-block;
      --border-color: #bebebe;
      --hover-bg: #cacaca;
      --hover-border-color: #bebebe;
      --focus-bg: #cacaca;
      --focus-border-color: #bebebe;
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
      border-bottom: 1px dashed var(--border-color);
      margin: 0;
    }

    input:hover {
      border-bottom: 1px dashed var(--hover-border-color);
    }

    input:focus {
      border-bottom: 1px dashed var(--focus-border-color);
    }

    .wrapper {
      position: relative;
      margin: -1px 0;
    }

    button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: inherit;
      line-height: inherit;
      border: 0;
      padding: 10px;
      height: 1em;
      width: 1em;
      top: 0;
      border-radius: 2px;
      overflow: hidden;
      outline: none;
      border: 1px solid transparent;
      background: transparent;
      opacity: 1;
      transition: all .2s ease-in-out;
    }

    button:hover {
      background: var(--hover-bg);
      border-color: var(--hover-border-color);
      cursor: pointer;
    }

    button:focus {
      opacity: 1;
      background: var(--focus-bg);
      border-color: var(--focus-border-color);
    }

    button[data-action="edit"] {
      right: 0;
    }

    button[data-action="save"] {
      right: calc(1em + 10px);
    }

    button[data-action="close"] {
      right: 0;
    }
    `;
  @property({ type: String }) groupName: string = '';
  @property({ type: Number }) groupId: number = 0;
  @property({ type: Boolean }) editable: boolean = false;
  @state() _isEditing: boolean = false;
  @state() _isSubmitting: boolean = false;

  private static updateInputSize(target: EventTarget): void {
    const input = target as HTMLInputElement;
    if (input.value.length < 10) {
      input.size = 10;
    } else {
      input.size = input.value.length + 2;
    }
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
        <div class="wrapper">${this.groupName}</div>`;
    }

    let content;

    if (!this._isEditing) {
      content = html`
        <div class="wrapper">
          <span @dblclick="${(): void => { this.startEditing(); }}">${this.groupName}</span>
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
      <button data-action="edit" type="button" aria-label="${lll('editGroupName')}" @click="${(): void => { this.startEditing(); }}">
        <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
      </button>`;
  }

  private composeEditForm(): TemplateResult {
    return html`
      <form class="wrapper" @submit="${ this.updateGroupName }">
        <input autocomplete="off" name="newGroupName" required size="${this.groupName.length + 2}" ?disabled="${this._isSubmitting}" value="${this.groupName}" @keydown="${(e: KeyboardEvent): void => { EditableGroupName.updateInputSize(e.target); if (e.key === 'Escape') { this.endEditing(); } }}">
        <button data-action="save" type="submit" ?disabled="${this._isSubmitting}">
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
        </button>
        <button data-action="close" type="button" ?disabled="${this._isSubmitting}" @click="${(): void => { this.endEditing(); }}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </button>
      </form>`;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-scheduler-editable-group-name': EditableGroupName;
  }
}
