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
import { customElement, property, state } from 'lit/decorators.js';
import labels from '~labels/form.relative_date_editor';

export class DateEditorChangeEvent extends Event {
  static readonly eventName = 'typo3:backend:form-editor:component:date-editor:change';
  constructor(public readonly value: string) {
    super(DateEditorChangeEvent.eventName);
  }
}

type DateMode = 'no' | 'absolute' | 'relative' | 'today' | 'custom';

/**
 * Module: @typo3/form/backend/form-editor/component/date-editor
 *
 * Web component for structured date constraint input in the TYPO3 form editor.
 * Supports five modes:
 * - "no":       No constraint (empty value)
 * - "today":    Sets value to "today"
 * - "absolute": Standard Y-m-d date input
 * - "relative": Structured input producing e.g. "-18 years"
 * - "custom":   Free-text input for arbitrary relative date expressions (e.g. "+1 month +3 days")
 *
 */
@customElement('typo3-form-date-editor')
export class DateEditor extends LitElement {

  @property({ type: String, attribute: 'absolute-pattern' }) absolutePattern!: string;

  @state() private mode: DateMode = 'no';
  @state() private absoluteDate: string = '';
  @state() private direction: string = '-';
  @state() private amount: number = 0;
  @state() private unit: string = 'years';
  @state() private customValue: string = '';

  /** The current value. Set from outside before connecting; updated internally on each change. */
  value: string = '';

  private _absoluteDateRegex: RegExp | null = null;

  private get absoluteDateRegex(): RegExp {
    if (!this._absoluteDateRegex) {
      this._absoluteDateRegex = new RegExp(this.absolutePattern);
    }
    return this._absoluteDateRegex;
  }

  protected override firstUpdated(): void {
    if (!this.absolutePattern) {
      throw new Error('typo3-form-date-editor: absolute-pattern attribute is required.');
    }
    this.parseValue(this.value);
  }

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override render(): TemplateResult {
    return html`
      <div class="mb-2">
        <select
          class="form-select"
          @change=${this.handleModeChange}
          .value=${this.mode}
        >
          <option value="no" ?selected=${this.mode === 'no'}>${labels.get('mode.no')}</option>
          <option value="today" ?selected=${this.mode === 'today'}>${labels.get('mode.today')}</option>
          <option value="absolute" ?selected=${this.mode === 'absolute'}>${labels.get('mode.absolute')}</option>
          <option value="relative" ?selected=${this.mode === 'relative'}>${labels.get('mode.relative')}</option>
          <option value="custom" ?selected=${this.mode === 'custom'}>${labels.get('mode.custom')}</option>
        </select>
      </div>
      ${this.mode === 'absolute' ? this.renderAbsolute() : nothing}
      ${this.mode === 'relative' ? this.renderRelative() : nothing}
      ${this.mode === 'custom' ? this.renderCustom() : nothing}
    `;
  }

  private renderAbsolute(): TemplateResult {
    return html`
      <div class="mb-2">
        <input
          type="date"
          class="form-control"
          .value=${this.absoluteDate}
          @change=${this.handleAbsoluteChange}
        >
      </div>
    `;
  }

  private renderRelative(): TemplateResult {
    return html`
      <div class="input-group">
        <select
          class="form-select"
          .value=${this.direction}
          @change=${this.handleDirectionChange}
        >
          <option value="-" ?selected=${this.direction === '-'}>${labels.get('direction.past')}</option>
          <option value="+" ?selected=${this.direction === '+'}>${labels.get('direction.future')}</option>
        </select>
        <input
          type="number"
          min="1"
          class="form-control"
          style="max-width:80px;"
          .value=${this.amount.toString()}
          @input=${this.handleAmountChange}
        >
        <select
          class="form-select"
          .value=${this.unit}
          @change=${this.handleUnitChange}
        >
          <option value="days" ?selected=${this.unit === 'days'}>${labels.get('unit.days', { count: this.amount })}</option>
          <option value="weeks" ?selected=${this.unit === 'weeks'}>${labels.get('unit.weeks', { count: this.amount })}</option>
          <option value="months" ?selected=${this.unit === 'months'}>${labels.get('unit.months', { count: this.amount })}</option>
          <option value="years" ?selected=${this.unit === 'years'}>${labels.get('unit.years', { count: this.amount })}</option>
        </select>
      </div>
    `;
  }

  private renderCustom(): TemplateResult {
    return html`
      <div class="mb-2">
        <input
          type="text"
          class="form-control"
          placeholder=${labels.get('custom.placeholder')}
          .value=${this.customValue}
          @input=${this.handleCustomChange}
        >
      </div>
    `;
  }

  private parseValue(value: string): void {
    if (!value) {
      this.mode = 'no';
      return;
    }

    if (value.toLowerCase() === 'today') {
      this.mode = 'today';
      return;
    }

    if (this.absoluteDateRegex.test(value)) {
      this.mode = 'absolute';
      this.absoluteDate = value;
      return;
    }

    const structuredMatch = value.match(/^([+-]?)\s*(\d+)\s+(days?|weeks?|months?|years?)$/i);
    if (structuredMatch) {
      this.mode = 'relative';
      this.direction = structuredMatch[1] === '+' ? '+' : '-';
      this.amount = parseInt(structuredMatch[2], 10);
      const parsedUnit = structuredMatch[3].toLowerCase();
      this.unit = parsedUnit.endsWith('s') ? parsedUnit : `${parsedUnit}s`;
      return;
    }

    // Fallback: any other non-empty string is treated as a custom free-text
    // expression (e.g. "last sunday", "first day of next month", "+1 month +3 days").
    // Actual validation is performed server-side by PHP's DateTime parser.
    this.mode = 'custom';
    this.customValue = value;
  }

  private composeValue(): string {
    switch (this.mode) {
      case 'today': {
        return 'today';
      }
      case 'absolute': {
        return this.absoluteDate;
      }
      case 'relative': {
        if (this.amount === 0) {
          return '';
        }
        return `${this.direction}${this.amount} ${this.unit}`;
      }
      case 'custom': {
        return this.customValue.trim();
      }
      default: {
        return '';
      }
    }
  }

  private emitChange(): void {
    const composedValue = this.composeValue();
    this.value = composedValue;
    this.dispatchEvent(new DateEditorChangeEvent(composedValue));
  }

  private handleModeChange(event: Event): void {
    const newMode = (event.target as HTMLSelectElement).value as DateMode;
    // When switching from relative to custom, carry over the composed value
    if (newMode === 'custom' && this.mode === 'relative') {
      const currentRelative = this.composeValue();
      if (currentRelative) {
        this.customValue = currentRelative;
      }
    }
    this.mode = newMode;
    if (this.mode === 'relative' && this.amount === 0) {
      this.amount = 1;
    }
    this.emitChange();
  }

  private handleAbsoluteChange(event: Event): void {
    this.absoluteDate = (event.target as HTMLInputElement).value;
    this.emitChange();
  }

  private handleDirectionChange(event: Event): void {
    this.direction = (event.target as HTMLSelectElement).value;
    this.emitChange();
  }

  private handleAmountChange(event: Event): void {
    this.amount = parseInt((event.target as HTMLInputElement).value, 10) || 0;
    this.emitChange();
  }

  private handleUnitChange(event: Event): void {
    this.unit = (event.target as HTMLSelectElement).value;
    this.emitChange();
  }

  private handleCustomChange(event: Event): void {
    this.customValue = (event.target as HTMLInputElement).value;
    this.emitChange();
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-form-date-editor': DateEditor;
  }
}

