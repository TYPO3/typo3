import { customElement, property } from 'lit/decorators.js';
import { html, LitElement, nothing, type TemplateResult } from 'lit';

export interface FormElementSelectorEntry {
  icon: string;
  label: string;
  value: string;
}

export class FormElementSelectorSelectedEvent extends Event {
  static readonly eventName = 'typo3:backend:form-editor:component:form-element-selector:selected';
  constructor(public readonly value: string) {
    super(FormElementSelectorSelectedEvent.eventName);
  }
}

/**
 * Module: @typo3/form/backend/form-editor/component/form-element-selector
 */
@customElement('typo3-form-element-selector')
export class FormElementSelector extends LitElement {

  @property({ type: Array, attribute: 'elements' }) elements: FormElementSelectorEntry[] = [];
  @property({ type: String }) size: 'small' | '' = '';

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    if (!this.elements?.length) {
      return html`${nothing}`;
    }
    return html`
      <span class="input-group-btn" role="group" data-identifier="inspectorEditorFormElementSelectorControlsWrapper">
        <span class="btn-group" data-identifier="inspectorEditorFormElementSelectorSplitButtonContainer">
          <button type="button" class="btn btn-default dropdown-toggle${this.size === 'small' ? ' btn-sm' : ''}" data-bs-toggle="dropdown" aria-expanded="false" title="{f:translate(key: 'LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.inspector.editor.formelement_selector.title')}">
            <typo3-backend-icon identifier="actions-variable-select"></typo3-backend-icon>
            <span class="visually-hidden">Toggle Dropdown</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-right" data-identifier="inspectorEditorFormElementSelectorSplitButtonListContainer">
            ${this.elements.map(element => this.renderEntry(element))}
          </ul>
        </span>
      </span>
    `;
  }

  protected renderEntry(element: FormElementSelectorEntry): TemplateResult {
    return html`
      <li>
        <a @click=${() => this.onSelect(element.value)} href="#" class="dropdown-item" data-formelement-identifier="${element.value}">
          <span class="dropdown-item-columns">
            <span class="dropdown-item-column dropdown-item-column-icon">
               <typo3-backend-icon identifier=${element.icon} size="small"></typo3-backend-icon>
            </span>
            <span class="dropdown-item-column dropdown-item-column-text">${element.label}</span>
          </span>
        </a>
      </li>
    `;
  }

  protected onSelect(value: string) {
    this.dispatchEvent(new FormElementSelectorSelectedEvent(value));
  }

}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-form-element-selector': FormElementSelector;
  }
}
