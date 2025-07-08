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
import '@typo3/backend/element/icon-element';

export interface Validator {
  identifier: string;
  label: string;
}

export interface SelectOption {
  label: string;
  value: string;
  selected?: boolean;
}

export interface FileUploadInfo {
  saveToFileMount?: string;
  allowedMimeTypes?: string[];
}

interface MultivalueItem {
  label: string;
  className?: string;
}

export interface ToolbarConfig {
  showToolbar: boolean;
  isCompositeElement: boolean;
  elementTypeLabel: string;
  elementIdentifier: string;
}

/**
 * Module: @typo3/form/backend/form-editor/component/form-element-stage-item
 *
 * Functionality for the form element stage item element
 *
 * @example
 * <typo3-form-form-element-stage-item
 *   element-type="Text"
 *   element-identifier="element-1"
 *   element-label="My Label"
 *   element-icon="form-text"
 *   is-required="false">
 * </typo3-form-form-element-stage-item>
 */
@customElement('typo3-form-form-element-stage-item')
export class FormElementStageItem extends LitElement {
  @property({ type: String, attribute: 'element-type' }) elementType: string = '';
  @property({ type: String, attribute: 'element-identifier' }) elementIdentifier: string = '';
  @property({ type: String, attribute: 'element-label' }) elementLabel: string = '';
  @property({ type: String, attribute: 'element-icon-identifier' }) elementIconIdentifier: string = '';
  @property({ type: Boolean, attribute: 'is-required' }) isRequired: boolean = false;
  @property({ type: Boolean, attribute: 'is-hidden' }) isHidden: boolean = false;
  @property({ type: Array }) validators: Validator[] = [];
  @property({ type: Array }) options: SelectOption[] = [];
  @property({ type: Array }) allowedMimeTypes?: string[];
  @property({ type: String }) content?: string;
  @property({ type: Object }) toolbarConfig?: ToolbarConfig;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    // Avoid Shadow DOM so global styles apply to the element contents
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      ${this.renderToolbar()}
      <div class="formeditor-element-label">
        <span>${this.elementType}</span>: <span>${this.elementIdentifier}</span>
      </div>
      <div class="formeditor-element-body">
        <div class="formeditor-element-icon">
          <typo3-backend-icon 
            identifier="${this.elementIconIdentifier}" 
            size="small" 
            overlay="${this.isHidden ? 'overlay-hidden' : ''}">
          </typo3-backend-icon>
        </div>
        <div class="formeditor-element-info">
          <div class="formeditor-element-info-label">
            <span>${this.elementLabel}</span>
            ${this.isRequired ? html`<span>*</span>` : nothing}
          </div>
          ${this.renderInfoContent()}
        </div>
        ${this.renderValidators()}
      </div>
    `;
  }

  /**
   * Renders the info content section if content items are present
   */
  private renderInfoContent(): TemplateResult | typeof nothing {
    const contentItems = this.renderContentItems();

    if (!contentItems.length) {
      return nothing;
    }

    return html`
      <div class="formeditor-element-info-content">
        ${contentItems}
      </div>
    `;
  }

  /**
   * Renders the element toolbar if configured
   */
  private renderToolbar(): TemplateResult | typeof nothing {
    if (!this.toolbarConfig?.showToolbar) {
      return nothing;
    }

    return html`
      <div class="formeditor-element-toolbar">
        <div class="btn-toolbar">
          ${this.renderToolbarNewElementButton()}
          <div class="btn-group btn-group-sm" role="group">
            <a 
              class="btn btn-default" 
              href="#" 
              title="${TYPO3.lang['formEditor.stage.toolbar.remove']}"
              @click="${this.handleRemoveElement}">
              <typo3-backend-icon identifier="actions-edit-delete" size="small"></typo3-backend-icon>
            </a>
          </div>
        </div>
      </div>
    `;
  }

  /**
   * Renders the "New Element" button(s) based on element type
   */
  private renderToolbarNewElementButton(): TemplateResult {
    if (this.toolbarConfig?.isCompositeElement) {
      return html`
        <div class="btn-group btn-group-sm" role="group">
          <div class="btn-group">
            <button 
              type="button" 
              class="btn btn-sm btn-default dropdown-toggle"
              popovertarget="toggle-menu-new-form-element"
              aria-expanded="false"
              title="${TYPO3.lang['formEditor.stage.toolbar.new_element']}">
              <typo3-backend-icon identifier="actions-document-new" size="small"></typo3-backend-icon>
              <span class="visually-hidden">Toggle Dropdown</span>
            </button>
            <ul id="toggle-menu-new-form-element" class="dropdown-menu dropdown-menu-right" popover>
              <li data-no-sorting>
                <a 
                  href="#"
                  class="dropdown-item"
                  @click="${this.handleNewElementInside}">
                  <span class="dropdown-item-columns">
                    <span class="dropdown-item-column dropdown-item-column-icon">
                      <typo3-backend-icon identifier="actions-form-insert-in" size="small"></typo3-backend-icon>
                    </span>
                    <span class="dropdown-item-column dropdown-item-column-text">
                      ${TYPO3.lang['formEditor.stage.toolbar.new_element.inside']}
                    </span>
                  </span>
                </a>
              </li>
              <li data-no-sorting>
                <a 
                  href="#"
                  class="dropdown-item"
                  @click="${this.handleNewElementAfter}">
                  <span class="dropdown-item-columns">
                    <span class="dropdown-item-column dropdown-item-column-icon">
                      <typo3-backend-icon identifier="actions-form-insert-after" size="small"></typo3-backend-icon>
                    </span>
                    <span class="dropdown-item-column dropdown-item-column-text">
                      ${TYPO3.lang['formEditor.stage.toolbar.new_element.after']}
                    </span>
                  </span>
                </a>
              </li>
            </ul>
          </div>
        </div>
      `;
    }

    return html`
      <div class="btn-group btn-group-sm" role="group">
        <a 
          class="btn btn-default" 
          href="#" 
          title="${TYPO3.lang['formEditor.stage.toolbar.new_element.after']}"
          @click="${this.handleNewElementAfter}">
          <typo3-backend-icon identifier="actions-document-new" size="small"></typo3-backend-icon>
        </a>
      </div>
    `;
  }

  /**
   * Event handler for "New Element After" action
   */
  private handleNewElementAfter(event: Event): void {
    event.preventDefault();
    this.dispatchEvent(new CustomEvent('toolbar-new-element-after', {
      bubbles: true,
      composed: true,
      detail: {
        elementIdentifier: this.elementIdentifier
      }
    }));
  }

  /**
   * Event handler for "New Element Inside" action
   */
  private handleNewElementInside(event: Event): void {
    event.preventDefault();
    this.dispatchEvent(new CustomEvent('toolbar-new-element-inside', {
      bubbles: true,
      composed: true,
      detail: {
        elementIdentifier: this.elementIdentifier
      }
    }));
  }

  /**
   * Event handler for "Remove Element" action
   */
  private handleRemoveElement(event: Event): void {
    event.preventDefault();
    this.dispatchEvent(new CustomEvent('toolbar-remove-element', {
      bubbles: true,
      composed: true,
      detail: {
        elementIdentifier: this.elementIdentifier
      }
    }));
  }

  /**
   * Collects all content items to be rendered in the info section
   */
  private renderContentItems(): TemplateResult[] {
    const items: TemplateResult[] = [];

    // Render text (for elements with text property)
    if (this.content) {
      items.push(html`
        <div class="formeditor-element-info-text">
          ${this.content}
        </div>
      `);
    }

    // Render options (for select elements)
    if (this.options?.length) {
      const multivalueItems: MultivalueItem[] = this.options.map(option => ({
        label: option.label,
        className: option.selected ? 'selected' : undefined,
      }));
      items.push(this.renderMultivalue(multivalueItems));
    }

    // Render allowed mime types (for file upload elements)
    if (this.allowedMimeTypes?.length) {
      const multivalueItems: MultivalueItem[] = this.allowedMimeTypes.map(mimeType => ({
        label: mimeType,
      }));
      items.push(this.renderMultivalue(multivalueItems));
    }

    return items;
  }

  /**
   * Renders a multivalue list with items
   */
  private renderMultivalue(items: MultivalueItem[]): TemplateResult {
    return html`
      <div class="formeditor-element-info-multivalue">
        ${items.map(item => html`
          <div class="formeditor-element-info-multivalue-item${item.className ? ` ${item.className}` : ''}">
            ${item.label}
          </div>
        `)}
      </div>
    `;
  }

  /**
   * Renders the validator section if validators are present
   */
  private renderValidators(): TemplateResult | typeof nothing {
    if (!this.validators?.length) {
      return nothing;
    }

    return html`
      <div class="formeditor-element-validator">
        <div class="formeditor-element-validator-icon">
          <typo3-backend-icon identifier="form-validator" size="small"></typo3-backend-icon>
        </div>
        <div class="formeditor-element-validator-list">
          ${this.validators.map(validator => html`
            <div class="formeditor-element-validator-list-item">
              ${validator.label}
            </div>
          `)}
        </div>
      </div>
    `;
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-form-form-element-stage-item': FormElementStageItem;
  }
}

