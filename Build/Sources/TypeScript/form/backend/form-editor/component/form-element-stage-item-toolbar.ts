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

import { html, LitElement, nothing, type TemplateResult } from 'lit';
import { customElement, property } from 'lit/decorators.js';
import labels from '~labels/form.form_editor_javascript';
import '@typo3/backend/element/icon-element';

/**
 * Module: @typo3/form/backend/form-editor/component/form-element-stage-item-toolbar
 *
 * Standalone toolbar web component form element stage templates.
 *
 * The edit button has no handler of its own: its click bubbles to the stage,
 * which selects the element and opens it in the inspector.
 *
 * Dispatches (bubbling):
 *   - toolbar-new-element-before
 *   - toolbar-new-element-after
 *   - toolbar-remove-element
 */
@customElement('typo3-form-form-element-stage-item-toolbar')
export class FormElementStageItemToolbar extends LitElement {

  @property({ type: Boolean, reflect: true })
  active: boolean = false;

  @property({ type: String, attribute: 'icon-identifier' }) iconIdentifier: string = '';
  @property({ type: String, attribute: 'element-type' }) elementType: string = '';
  @property({ type: String, attribute: 'element-identifier' }) elementIdentifier: string = '';
  @property({ type: Boolean, attribute: 'is-hidden' }) isHidden: boolean = false;
  @property({ type: Boolean, attribute: 'is-invalid' }) isInvalid: boolean = false;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult | typeof nothing {
    if (!this.active) {
      return nothing;
    }
    return html`
      <div class="formeditor-element-toolbar">
        <div class="formeditor-element-toolbar-left">
          <typo3-backend-icon
            identifier="${this.iconIdentifier}"
            size="small"
            overlay="${this.isHidden ? 'overlay-hidden' : (this.isInvalid ? 'overlay-missing' : '')}">
          </typo3-backend-icon>
        </div>
        <div class="formeditor-element-toolbar-title">
          ${this.elementType}
        </div>
        <div class="formeditor-element-toolbar-right">
          <div class="btn-toolbar">
            <div class="btn-group btn-group-sm" role="group">
              <button
                  type="button"
                  class="btn btn-default btn-borderless formeditor-element-toolbar-edit"
                  title="${labels.get('formEditor.stage.toolbar.edit')}">
                <typo3-backend-icon identifier="actions-open" size="small"></typo3-backend-icon>
              </button>
              <a
                  class="btn btn-default btn-borderless"
                  href="#"
                  title="${labels.get('formEditor.stage.toolbar.new_element.before')}"
                  @click="${this.handleNewElementBefore}">
                <typo3-backend-icon identifier="actions-form-insert-before" size="small"></typo3-backend-icon>
              </a>
              <a
                  class="btn btn-default btn-borderless"
                  href="#"
                  title="${labels.get('formEditor.stage.toolbar.new_element.after')}"
                  @click="${this.handleNewElementAfter}">
                <typo3-backend-icon identifier="actions-form-insert-after" size="small"></typo3-backend-icon>
              </a>
              <a
                class="btn btn-default btn-borderless"
                href="#"
                title="${labels.get('formEditor.stage.toolbar.remove')}"
                @click="${this.handleRemoveElement}">
                <typo3-backend-icon identifier="actions-edit-delete" size="small"></typo3-backend-icon>
              </a>
            </div>
          </div>
        </div>
      </div>
    `;
  }

  private handleNewElementBefore(event: Event): void {
    event.preventDefault();
    this.dispatchEvent(new CustomEvent('toolbar-new-element-before', { bubbles: true, composed: true }));
  }

  private handleNewElementAfter(event: Event): void {
    event.preventDefault();
    this.dispatchEvent(new CustomEvent('toolbar-new-element-after', { bubbles: true, composed: true }));
  }

  private handleRemoveElement(event: Event): void {
    event.preventDefault();
    this.dispatchEvent(new CustomEvent('toolbar-remove-element', { bubbles: true, composed: true }));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-form-form-element-stage-item-toolbar': FormElementStageItemToolbar;
  }
}
