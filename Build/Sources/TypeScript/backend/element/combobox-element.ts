/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

import { html, LitElement, css, type TemplateResult } from 'lit';
import { customElement, property, state } from 'lit/decorators';
import type { PropertyValues } from '@lit/reactive-element';
import '@typo3/backend/element/icon-element';

export interface ComboboxOption {
  value: string;
  label: string;
  disabled?: boolean;
  icon?: string;
}

const Action = {
  INPUT: 'input',
  NAVIGATION: 'navigation',
} as const;
type ACTION = typeof Action[keyof typeof Action];

const SelectionIntent = {
  NONE: 'none',
  CURRENT: 'current',
} as const;
type SELECTIONINTENT = typeof SelectionIntent[keyof typeof SelectionIntent];

let idCounter = 0;

/**
 * Individual choice element for combobox
 *
 * @example
 * <typo3-backend-combobox-choice value="1" icon="actions-add">Option 1</typo3-backend-combobox-choice>
 */
@customElement('typo3-backend-combobox-choice')
export class ComboboxChoiceElement extends LitElement {
  static override styles = css`
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    :host {
      display: flex;
      align-items: center;
      width: 100%;
      user-select: none;
    }
  `;

  @property({ type: String }) value: string = '';
  @property({ type: String }) icon: string = '';
  @property({ type: Boolean }) disabled: boolean = false;
  @state() private isSelected: boolean = false;

  public override connectedCallback(): void {
    super.connectedCallback();
    this.setAttribute('role', 'option');
    this.setAttribute('aria-selected', 'false');

    // If no value is provided, use the text content
    if (!this.value && this.textContent) {
      this.value = this.textContent.trim();
    }

    this.updateAriaLabel();
  }

  public setSelected(selected: boolean): void {
    this.isSelected = selected;
  }

  protected override updated(changedProperties: Map<string | number | symbol, unknown>): void {
    if (changedProperties.has('value')) {
      this.updateAriaLabel();
    }
  }

  protected override render(): TemplateResult {
    const label = this.getLabel();
    const showValue = this.value && this.value !== label;

    return html`
      <span class="indicator" part="indicator">
        <typo3-backend-icon identifier="${this.isSelected ? 'actions-check' : 'miscellaneous-placeholder'}" size="small" aria-hidden="true"></typo3-backend-icon>
      </span>
      ${this.icon ? html`
        <typo3-backend-icon identifier="${this.icon}" size="small" part="icon"></typo3-backend-icon>` : ''}
      <span class="content" part="content">
        ${label}
        ${showValue ? html`<span class="value" part="value">(${this.value})</span>` : ''}
      </span>
    `;
  }

  private getLabel(): string {
    return this.textContent?.trim() || this.value;
  }

  private updateAriaLabel(): void {
    const label = this.getLabel();
    const valueText = this.value && this.value !== label ? ` (${this.value})` : '';
    this.setAttribute('aria-label', `${label}${valueText}`);
  }
}

/**
 * @example Enhancing an existing input:
 * <typo3-backend-combobox>
 *   <input type="text" class="form-control" name="example" placeholder="Select or type...">
 *   <typo3-backend-combobox-choice value="1">Option 1</typo3-backend-combobox-choice>
 *   <typo3-backend-combobox-choice value="2">Option 2</typo3-backend-combobox-choice>
 * </typo3-backend-combobox>
 */
@customElement('typo3-backend-combobox')
export class ComboboxElement extends LitElement {

  static override styles = css`
    *,
    *::before,
    *::after {
      box-sizing: border-box;
    }

    :host {
      position: relative;
      display: block;
      width: 100%;
    }

    .controls {
      position: absolute;
      top: 50%;
      inset-inline-end: 0;
      transform: translateY(-50%);
      display: flex;
      align-items: center;
      gap: 0.25rem;
      padding-inline-end: var(--typo3-form-combobox-padding-x, 0.75rem);
    }

    .clear-button {
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: inherit;
      opacity: 0.3;
      transition: opacity 0.2s ease;
    }

    .clear-button:hover {
      opacity: .5;
    }

    .separator {
      width: 1px;
      height: 1.25rem;
      background-color: var(--typo3-input-border-color, currentColor);
    }

    .indicator {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .listbox {
      position: absolute;
      top: 100%;
      left: 0;
      width: 100%;
      z-index: 1000;
    }
  `;

  @state()
  public isOpen: boolean = false;

  @property({ type: Boolean, reflect: true, attribute: 'data-has-value' })
  private inputHasValue: boolean = false;

  @state()
  private highlightedIndex: number = -1;

  @state()
  private inputIsDisabledOrReadonly: boolean = false;

  private choiceIdCounter: number = 0;
  private delayedCloseTimeout: number = null;
  private internalId: string = null;
  private lastAction: ACTION | null = null;
  private lastHighlightedIndex: number | null = null;
  private userSelectionIntent: SELECTIONINTENT | null = null;

  constructor() {
    super();
    this.addEventListener('click', this.handleChoiceClick);
  }

  public override focus(options?: FocusOptions): void {
    this.getInput()?.focus(options);
  }

  public override blur(): void {
    this.getInput()?.blur();
  }

  protected override willUpdate(changedProperties: PropertyValues<this>): void {
    if (
      changedProperties.has('isOpen') &&
      this.isOpen &&
      this.userSelectionIntent !== SelectionIntent.NONE
    ) {
      const currentChoice = this.findCurrentSelectionChoice();
      if (currentChoice) {
        const choices = this.getChoiceElements();
        const index = choices.indexOf(currentChoice);
        this.setHighlightedIndex(index);
      }
    }
  }

  protected override render(): TemplateResult {
    const input = this.getInput();

    if (input) {
      input.setAttribute('aria-expanded', this.isOpen.toString());
      input.setAttribute('aria-activedescendant', this.getActiveDescendantId());
    }

    return html`
      <slot @slotchange=${this.handleSlotChange}></slot>
      ${!this.inputIsDisabledOrReadonly ? html`
        <div class="controls" part="controls">
          ${this.inputHasValue ? html`
            <span
              class="clear-button"
              part="clear-button"
              @click=${this.handleClearClick}
              aria-label="Clear value"
              tabindex="-1"
            >
              <typo3-backend-icon identifier="actions-close" size="small" aria-hidden="true"></typo3-backend-icon>
            </span>
            <span class="separator" part="separator"></span>
          ` : ''}
          <span class="indicator" part="indicator" tabindex="-1" @click="${this.handleIndicatorClick}" style="cursor: pointer">
            <typo3-backend-icon identifier="actions-chevron-expand" size="small" aria-hidden="true"></typo3-backend-icon>
          </span>
        </div>
        <div class="listbox" part="listbox" role="listbox" id="${this.getId()}-listbox" ?hidden=${!this.isOpen}>
          <slot name="choices"></slot>
        </div>
      ` : ''}
    `;
  }

  protected override firstUpdated(): void {
    // Set up input element attributes for accessibility
    this.setup();
  }

  protected override updated(changedProperties: PropertyValues<this>): void {
    // Position dropdown when it opens
    if (changedProperties.has('isOpen')) {
      if (this.isOpen) {
        this.positionDropdown();
        this.scrollToHighlightedOption();
      } else {
        this.setHighlightedIndex(-1);
        this.lastAction = null;
        this.userSelectionIntent = null;
      }
    }
  }

  private getId(): string {
    this.internalId ??= this.id !== '' ? this.id : 'combobox-' + idCounter++;
    return this.internalId;
  }

  private positionDropdown(): void {
    const listbox = this.shadowRoot?.querySelector('.listbox') as HTMLElement;
    if (!listbox) {
      return;
    }

    const hostRect = this.getBoundingClientRect();
    const viewportHeight = window.innerHeight;
    const scrollY = window.scrollY;

    const computedStyle = window.getComputedStyle(listbox);
    const maxHeight = computedStyle.maxHeight !== 'none'
      ? parseFloat(computedStyle.maxHeight)
      : Infinity;

    const naturalHeight = Math.min(listbox.scrollHeight, maxHeight);

    const spaceBelow = viewportHeight - (hostRect.bottom - scrollY);
    const spaceAbove = hostRect.top - scrollY;

    const preferAbove = spaceBelow < naturalHeight && spaceAbove > spaceBelow;
    const availableSpace = preferAbove ? spaceAbove : spaceBelow;

    requestAnimationFrame(() => {
      if (preferAbove) {
        listbox.style.top = 'auto';
        listbox.style.bottom = '100%';
        if (naturalHeight > availableSpace) {
          listbox.style.maxHeight = `${Math.floor(availableSpace)}px`;
        } else {
          listbox.style.removeProperty('max-height');
        }
      } else {
        listbox.style.top = '100%';
        listbox.style.bottom = 'auto';
        if (naturalHeight > availableSpace) {
          listbox.style.maxHeight = `${Math.floor(availableSpace)}px`;
        } else {
          listbox.style.removeProperty('max-height');
        }
      }
    });
  }

  private scrollToHighlightedOption(): void {
    if (this.highlightedIndex < 0) {
      return;
    }

    const choices = this.getChoiceElements();
    const highlightedChoice = choices[this.highlightedIndex];
    if (highlightedChoice) {
      highlightedChoice.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }
  }

  private selectChoiceOption(index: number): void {
    const choices = this.getChoiceElements();
    const choiceElement = choices[index];

    if (!choiceElement || choiceElement.disabled) {
      return;
    }

    const newValue = choiceElement.value;
    const input = this.getInput();
    if (input) {
      input.value = newValue;
      this.dispatchInputEvents(input);
    }

    this.updateInputHasValue();
    this.updateSelectedState();
    this.setHighlightedIndex(index);
  }

  private setHighlightedIndex(index: number): void {
    const choices = this.getChoiceElements();
    choices.forEach((choice, i) => {
      choice.setAttribute('aria-selected', i === index ? 'true' : 'false');
    });
    this.highlightedIndex = index;
    this.scrollToHighlightedOption();
  }

  private getNextHighlightIndex(direction: number, totalOptions: number): number {
    if (this.highlightedIndex < 0) {
      const { lastHighlightedIndex } = this;
      if (lastHighlightedIndex !== null && lastHighlightedIndex !== -1) {
        this.lastHighlightedIndex = null;
        return lastHighlightedIndex;
      }

      const currentChoice = this.findCurrentSelectionChoice();
      if (currentChoice) {
        const choices = this.getChoiceElements();
        return choices.indexOf(currentChoice);
      }
      return direction > 0 ? 0 : totalOptions - 1;
    }
    if (direction > 0) {
      return Math.min(this.highlightedIndex + 1, totalOptions - 1);
    }
    return Math.max(this.highlightedIndex - 1, 0);
  }

  private setup(): void {
    this.setupInputAttributes();
    this.setupChoiceElements();

    this.updateInputHasValue();
    this.updateSelectedState();
  }

  private handleSlotChange(): void {
    // Re-setup when slot content changes
    this.setup();
  }

  private setupChoiceElements(): void {
    const choices = this.querySelectorAll<ComboboxChoiceElement>('typo3-backend-combobox-choice:not([slot="choices"])');
    choices.forEach((choice) => {
      // Move choice elements to the choices slot
      choice.slot = 'choices';
      choice.tabIndex = -1;

      // Ensure unique IDs for accessibility
      if (!choice.id) {
        choice.id = `${this.getId()}-option-${++this.choiceIdCounter}`;
      }

      choice.addEventListener('mouseenter', this.handleChoiceMouseEnter);
    });
  }

  private readonly handleChoiceMouseEnter = (event: Event): void => {
    const choice = event.currentTarget as ComboboxChoiceElement;
    const choices = this.getChoiceElements();
    const index = choices.indexOf(choice);
    if (index >= 0) {
      this.setHighlightedIndex(index);
    }
  };

  private setupInputAttributes(): void {
    const input = this.getInput();
    if (input) {
      // Remove existing listeners to avoid duplicates
      input.removeEventListener('formengine:input:initialized', this.handleInputInit);
      input.removeEventListener('input', this.handleInputInput);
      input.removeEventListener('pointerdown', this.handleInputPointerdown);
      input.removeEventListener('blur', this.handleInputBlur);
      input.removeEventListener('keydown', this.handleInputKeydown);

      input.setAttribute('role', 'combobox');
      input.setAttribute('aria-expanded', 'false');
      input.setAttribute('aria-haspopup', 'listbox');
      input.setAttribute('aria-controls', `${this.getId()}-listbox`);
      input.setAttribute('autocomplete', 'off');

      // Add event listeners to existing input
      input.addEventListener('formengine:input:initialized', this.handleInputInit);
      input.addEventListener('input', this.handleInputInput);
      input.addEventListener('pointerdown', this.handleInputPointerdown);
      input.addEventListener('blur', this.handleInputBlur);
      input.addEventListener('keydown', this.handleInputKeydown);

      // Initialize disabled state
      this.updateInputDisabledState();
    }
  }


  private getInput(): HTMLInputElement | null {
    const slot = this.shadowRoot?.querySelector('slot:not([name])') as HTMLSlotElement;
    if (!slot) {
      return null;
    }

    const assignedElements = slot.assignedElements();
    return assignedElements.find(el => el.tagName === 'INPUT') as HTMLInputElement | null;
  }

  private setOpen(): void {
    const input = this.getInput();
    this.updateInputDisabledState();
    if (input?.disabled || input?.readOnly) {
      return;
    }

    this.isOpen = true;
    if (this.delayedCloseTimeout !== null) {
      clearTimeout(this.delayedCloseTimeout);
      this.delayedCloseTimeout = null;
    }
  }

  private readonly handleInputInit = (): void => {
    this.updateInputHasValue();
    this.updateSelectedState();
  };

  private readonly handleInputInput = (): void => {
    this.lastAction = Action.INPUT;
    if (this.highlightedIndex !== -1) {
      this.lastHighlightedIndex = this.highlightedIndex;
      this.setHighlightedIndex(-1);
    }
    this.updateInputHasValue();
    this.updateSelectedState();
  };

  private readonly handleInputPointerdown = (e: PointerEvent): void => {
    if (e.button !== 0) {
      return;
    }
    this.setOpen();
  };

  private immediateClose(): void {
    this.isOpen = false;
  }

  private dispatchClose(): void {
    if (this.delayedCloseTimeout !== null) {
      return;
    }
    this.delayedCloseTimeout = setTimeout(() => {
      this.immediateClose();
    }, 100);
  }

  private readonly handleInputBlur = (event: FocusEvent): void => {
    if (!this.contains(event.relatedTarget as Node)) {
      this.dispatchClose();
    } else {
      // Regain focus if focus was lost because associated,
      // supportive elements were clicked
      this.getInput()?.focus();
    }
  };

  private readonly handleInputKeydown = (event: KeyboardEvent): void => {
    const input = this.getInput();
    if (input?.disabled || input?.readOnly) {
      return;
    }

    const choiceElements = this.getChoiceElements();
    const totalOptions = choiceElements.length;

    switch (event.key) {
      case 'ArrowDown':
        event.preventDefault();
        event.stopPropagation();
        if (event.altKey && !this.isOpen) {
          this.userSelectionIntent = SelectionIntent.NONE;
        } else {
          this.lastAction = Action.NAVIGATION;
          this.setHighlightedIndex(this.getNextHighlightIndex(1, totalOptions));
        }
        this.setOpen();
        break;
      case 'ArrowUp':
        event.preventDefault();
        event.stopPropagation();
        if (event.altKey && !this.isOpen) {
          this.userSelectionIntent = SelectionIntent.NONE;
        } else {
          this.lastAction = Action.NAVIGATION;
          this.setHighlightedIndex(this.getNextHighlightIndex(-1, totalOptions));
        }
        this.setOpen();
        break;
      case 'Enter':
        if (this.isOpen) {
          if (this.highlightedIndex >= 0 && this.lastAction === Action.NAVIGATION) {
            event.preventDefault();
            this.selectChoiceOption(this.highlightedIndex);
          }
          this.immediateClose();
        }
        break;
      case 'Tab':
        if (this.isOpen) {
          if (this.highlightedIndex >= 0 && this.lastAction === Action.NAVIGATION) {
            this.selectChoiceOption(this.highlightedIndex);
          }
          this.immediateClose();
          if (!event.shiftKey) {
            event.preventDefault();
          }
        }
        break;
      case 'Escape':
        event.preventDefault();
        this.lastAction = Action.NAVIGATION;
        if (this.isOpen) {
          // First ESC closes the dropdown
          this.isOpen = false;
          event.stopPropagation();
        } else {
          // Second ESC (or ESC when dropdown is closed) clears the value
          const input = this.getInput();
          if (input && input.value && !input.readOnly && !input.disabled) {
            this.clearInput();
            // Prevent dropdown from opening after clearing
            this.isOpen = false;
            event.stopPropagation();
          }
        }
        break;
      default:
        break;
    }
  };

  private getChoiceElements(): ComboboxChoiceElement[] {
    return Array.from(this.querySelectorAll('typo3-backend-combobox-choice'));
  }

  private findCurrentSelectionChoice(): ComboboxChoiceElement | null {
    const input = this.getInput();
    if (!input?.value) {
      return null;
    }

    const choiceElements = this.getChoiceElements();
    return choiceElements.find((element) => element.value === input.value) || null;
  }


  private getActiveDescendantId(): string {
    const choices = this.getChoiceElements();
    return this.highlightedIndex >= 0 ? choices[this.highlightedIndex]?.id || '' : '';
  }

  private updateSelectedState(): void {
    const input = this.getInput();
    const choiceElements = this.getChoiceElements();
    choiceElements.forEach((element) => {
      const isSelected = element.value === input?.value;
      element.setSelected(isSelected);
    });
  }

  private updateInputHasValue(): void {
    const input = this.getInput();
    this.inputHasValue = (input?.value || '').length > 0;
  }

  private updateInputDisabledState(): void {
    const input = this.getInput();
    this.inputIsDisabledOrReadonly = input?.disabled || input?.readOnly || false;
  }

  private dispatchInputEvents(input: HTMLInputElement): void {
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
  }

  private clearInput(): void {
    const input = this.getInput();
    if (input) {
      input.value = '';
      this.dispatchInputEvents(input);
      this.updateInputHasValue();
      this.setHighlightedIndex(-1);
    }
  }

  private readonly handleClearClick = (event: Event): void => {
    event.stopPropagation();
    this.clearInput();
    this.getInput()?.focus();
  };

  private readonly handleIndicatorClick = (event: Event): void => {
    event.stopPropagation();
    if (this.isOpen) {
      this.immediateClose();
    } else {
      this.setOpen();
      this.getInput()?.focus();
    }
  };

  private readonly handleChoiceClick = (event: Event): void => {
    const target = event.target as HTMLElement;
    const choice = target.closest('typo3-backend-combobox-choice');
    const input = this.getInput();

    if (choice && !choice.disabled) {
      const choices = this.getChoiceElements();
      const index = choices.indexOf(choice);
      if (index >= 0) {
        this.selectChoiceOption(index);
        this.immediateClose();
        input.focus();
        return;
      }
    }

    // Handle clicks on the host to focus input and open the listbox
    if (input && target !== input) {
      input.focus();
      this.setOpen();
    }
  };

}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-backend-combobox': ComboboxElement;
    'typo3-backend-combobox-choice': ComboboxChoiceElement;
  }
}
