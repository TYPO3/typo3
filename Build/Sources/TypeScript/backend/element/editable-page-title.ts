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

import {lll} from '@typo3/core/lit-helper';
import {html, css, LitElement, TemplateResult, nothing} from 'lit';
import {customElement, property, state} from 'lit/decorators';
import './icon-element';
import AjaxDataHandler from '../ajax-data-handler';

@customElement('typo3-backend-editable-page-title')
class EditablePageTitle extends LitElement {
  static styles = css`
    :host {
      display: block;
      --border-color: #bebebe;
      --hover-bg: #cacaca;
      --hover-border-color: #bebebe;
      --focus-bg: #cacaca;
      --focus-border-color: #bebebe;
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
      border-bottom: 1px dashed var(--border-color);
      margin: 0;
      width: 100%;
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

    div.wrapper {
      padding-right: 1.5em;
    }

    form.wrapper {
      padding-right: 2.5em;
    }

    button {
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
      border-radius: 2px;
      overflow: hidden;
      outline: none;
      border: 1px solid transparent;
      background: transparent;
      opacity: .3;
      transition: all .2s ease-in-out;
    }

    button:hover {
      opacity: 1;
      background: var(--hover-bg);
      border-color: var(--hover-border-color);
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
      right: calc(1em + 2px);
    }

    button[data-action="close"] {
      right: 0;
    }
    `;
  @property({type: String}) pageTitle: string = '';
  @property({type: Number}) pageId: number = 0;
  @property({type: Number}) localizedPageId: number = 0;
  @property({type: Boolean}) editable: boolean = false;
  @state() _isEditing: boolean = false;
  @state() _isSubmitting: boolean = false;

  async startEditing(): Promise<void> {
    if (this.isEditable()) {
      this._isEditing = true;
      await this.updateComplete;
      this.shadowRoot.querySelector('input')?.focus();
    }
  }

  protected render(): TemplateResult | symbol {
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

    let parameters: { [k: string]: any } = {};
    let recordUid;
    if (this.localizedPageId > 0) {
      recordUid = this.localizedPageId;
    } else {
      recordUid = this.pageId;
    }

    parameters.data = {
      pages: {
        [recordUid]: {
          title: newPageTitle
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
      <button data-action="edit" type="button" aria-label="${lll('editPageTitle')}" @click="${(): void => { this.startEditing(); }}">
        <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
      </button>`;
  }

  private composeEditForm(): TemplateResult {
    return html`
      <form class="wrapper" @submit="${ this.updatePageTitle }">
        <input autocomplete="off" name="newPageTitle" ?disabled="${this._isSubmitting}" value="${this.pageTitle}" @keydown="${(e: KeyboardEvent): void => { if (e.key === 'Escape') { this.endEditing(); } }}">
        <button data-action="save" type="submit" ?disabled="${this._isSubmitting}">
          <typo3-backend-icon identifier="actions-check" size="small"></typo3-backend-icon>
        </button>
        <button data-action="close" type="button" ?disabled="${this._isSubmitting}" @click="${(): void => { this.endEditing(); }}">
          <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
        </button>
      </form>`;
  }
}
