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
import {html, LitElement, TemplateResult} from 'lit';
import {customElement, property, state} from 'lit/decorators';
import './icon-element';
import AjaxDataHandler from '../ajax-data-handler';

@customElement('typo3-backend-editable-page-title')
class EditablePageTitle extends LitElement {
  @property({type: String}) pageTitle: string = '';
  @property({type: Number}) pageId: number = 0;
  @property({type: Number}) localizedPageId: number = 0;
  @property({type: Boolean}) editable: boolean = false;
  @state() _isEditing: boolean = false;
  @state() _isSubmitting: boolean = false;

  public createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid shadow DOM for Bootstrap CSS to be applied
    return this;
  }

  protected render(): TemplateResult {
    if (this.pageTitle === '') {
      return html``;
    }

    const pageTitleHeadline = html`<h1 @dblclick="${(): void => { this.startEditing(); }}">${this.pageTitle}</h1>`;
    if (!this.isEditable()) {
      return pageTitleHeadline;
    }

    let content;
    if (!this._isEditing) {
      content = html`<div class="row">
        <div class="col-md-auto">
          ${pageTitleHeadline}
        </div>
        <div class="col">
          ${this.composeEditButton()}
        </div>
      </div>`;
    } else {
      content = this.composeEditForm();
    }

    return content;
  }

  private isEditable(): boolean {
    return this.editable && this.pageId > 0;
  }

  private startEditing(): void {
    if (this.isEditable()) {
      this._isEditing = true;
    }
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
    return html`<button type="button" class="btn btn-link" aria-label="${lll('editPageTitle')}" @click="${(): void => { this.startEditing(); }}">
      <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
    </button>`;
  }

  private composeEditForm(): TemplateResult {
    return html`<form class="t3js-title-edit-form" @submit="${ this.updatePageTitle }">
      <div class="form-group">
        <div class="input-group input-group-lg">
          <input class="form-control" name="newPageTitle" ?disabled="${this._isSubmitting}" value="${this.pageTitle}" @keydown="${(e: KeyboardEvent): void => { if (e.key === 'Escape') { this.endEditing(); } }}">
          <button class="btn btn-default" type="submit" ?disabled="${this._isSubmitting}">
            <typo3-backend-icon identifier="actions-save" size="small"></typo3-backend-icon>
          </button>
          <button class="btn btn-default" type="button" ?disabled="${this._isSubmitting}" @click="${(): void => { this.endEditing(); }}">
            <typo3-backend-icon identifier="actions-close" size="small"></typo3-backend-icon>
          </button>
        </div>
      </div>
    </form>`;
  }
}
