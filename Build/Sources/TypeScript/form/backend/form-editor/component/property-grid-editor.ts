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

import { html, LitElement, nothing, type PropertyValues, type TemplateResult } from 'lit';
import { repeat } from 'lit/directives/repeat';
import { classMap } from 'lit/directives/class-map';
import { live } from 'lit/directives/live';
import { customElement, property, state } from 'lit/decorators';
import '@typo3/backend/element/icon-element';

export interface PropertyGridEditorEntry {
  id: string;
  label: string;
  value: string;
  selected: boolean;
}

export class PropertyGridEditorUpdateEvent extends Event {
  static readonly eventName = 'typo3:backend:form-editor:component:property-grid-editor:update';
  constructor(public readonly data: PropertyGridEditorEntry[]) {
    super(PropertyGridEditorUpdateEvent.eventName);
  }
}

/**
 * Module: @typo3/form/backend/form-editor/component/property-grid-editor
 */
@customElement('typo3-form-property-grid-editor')
export class PropertyGridEditor extends LitElement {

  @property({ type: Array, attribute: 'entries' }) entries: PropertyGridEditorEntry[] = [];

  @property({ type: String, attribute: 'label-label' }) labelLabel: string = 'Label';
  @property({ type: String, attribute: 'label-value' }) labelValue: string = 'Value';
  @property({ type: String, attribute: 'label-selected' }) labelSelected: string = 'Selected';
  @property({ type: String, attribute: 'label-add' }) labelAdd: string = 'Add';
  @property({ type: String, attribute: 'label-remove' }) labelRemove: string = 'Remove';
  @property({ type: String, attribute: 'label-move' }) labelMove: string = 'Move';

  @property({ type: Boolean }) enableAddRow: boolean = false;
  @property({ type: Boolean }) enableDeleteRow: boolean = false;
  @property({ type: Boolean }) enableSelection: boolean = true;
  @property({ type: Boolean }) enableMultiSelection: boolean = false;
  @property({ type: Boolean }) enableSorting: boolean = false;
  @property({ type: Boolean }) enableLabelAsFallbackValue: boolean = false;

  @state() private draggedEntry: PropertyGridEditorEntry | null = null;
  @state() private movedEntry: PropertyGridEditorEntry | null = null;

  private activeElementRef: HTMLElement | null = null;

  protected override createRenderRoot(): HTMLElement | ShadowRoot {
    return this;
  }

  protected override updated(changedProperties: PropertyValues): void {
    if (this.activeElementRef) {
      this.activeElementRef.focus();
      this.activeElementRef = null;
    }
    if (changedProperties.has('entries')) {
      const oldEntries = changedProperties.get('entries') as PropertyGridEditorEntry[] | undefined;
      if (oldEntries !== undefined && JSON.stringify(oldEntries) !== JSON.stringify(this.entries)) {
        this.dispatchEvent(new PropertyGridEditorUpdateEvent(this.entries));
      }
    }
  }

  protected override render(): TemplateResult {
    return html`
      <div class="property-grid-editor">
        ${this.entries?.length ? html`
          <div class="property-grid-editor__entries">
            ${repeat(this.entries, entry => entry.id, entry => this.renderEntry(entry))}
          </div>
        ` : nothing}
        ${this.enableAddRow ? html`
          <div class="property-grid-editor__actions">
            <button
              class="btn btn-sm btn-default"
              title=${this.labelAdd}
              @click=${this.handleCreate}
            >
              <typo3-backend-icon identifier="actions-plus" size="small"></typo3-backend-icon>
              <span class="btn-label">${this.labelAdd}</span>
            </button>
          </div>
        ` : nothing}
      </div>
    `;
  }

  protected renderEntry(entry: PropertyGridEditorEntry): TemplateResult {
    return html`
      <div
        class=${classMap({ 'property-grid-editor__entry': true, moving: this.movedEntry === entry, dragging: this.draggedEntry === entry })}
        @dragover=${(event: DragEvent) => this.handleDragOver(event)}
        @dragenter=${(event: DragEvent) => this.handleDragEnter(event, entry)}
        @drop=${(event: DragEvent) => this.handleDrop(event)}
        @dragend=${(event: DragEvent) => this.handleDragEnd(event)}
      >
        <div class="property-grid-editor__entry-inputs">
          <div class="form-group">
            <label for="${entry.id}-label" class="form-label">${this.labelLabel}</label>
            <input
              id="${entry.id}-label"
              class="form-control form-control-sm"
              type="text"
              @change=${(event: Event) => this.handleChange(event, 'label', entry)}
              @keyup=${(event: Event) => this.handleChange(event, 'label', entry)}
              @focusout=${(event: FocusEvent) => this.handleFocusOut(event, entry)}
              .value=${live(entry.label)}
            />
          </div>
          <div class="form-group">
            <label for="${entry.id}-value" class="form-label">${this.labelValue}</label>
            <input
              id="${entry.id}-value"
              class="form-control form-control-sm"
              type="text"
              @change=${(event: Event) => this.handleChange(event, 'value', entry)}
              @keyup=${(event: Event) => this.handleChange(event, 'value', entry)}
              .value=${live(entry.value)}
            />
          </div>
          ${(this.enableSelection || this.enableMultiSelection) ? html`
            <div class="form-check">
              <input
                id="${entry.id}-selected"
                class="form-check-input"
                type="checkbox"
                @change=${(event: Event) => this.handleChange(event, 'selected', entry)}
                .checked=${live(entry.selected)}
              />
              <label for="${entry.id}-selected" class="form-check-label">${this.labelSelected}</label>
            </div>
          ` : nothing}
        </div>
        ${(this.enableSorting || this.enableDeleteRow) ? html`
          <div class="property-grid-editor__entry-buttons">
            ${this.enableSorting ? html`
              <button
                class="btn btn-sm btn-default"
                title=${this.labelMove}
                draggable="true"
                @click=${(e: Event) => this.handleMoveClick(e, entry)}
                @keydown=${this.handleMoveKeyDown}
                @dragstart=${(e: DragEvent) => this.handleDragStart(e, entry)}
              >
                <typo3-backend-icon identifier=${this.movedEntry === entry ? 'actions-thumbtack' : 'actions-move-move'} size="small"></typo3-backend-icon>
              </button>
            ` : nothing}
            ${this.enableDeleteRow ? html`
              <button
                class="btn btn-sm btn-default"
                title=${this.labelRemove}
                @click=${() => this.handleRemove(entry)}
              >
                <typo3-backend-icon identifier="actions-delete" size="small"></typo3-backend-icon>
              </button>
            ` : nothing}
          </div>
        ` : nothing}
      </div>
    `;
  }

  protected handleFocusOut(event: FocusEvent, entry: PropertyGridEditorEntry): void {
    if (this.enableLabelAsFallbackValue && entry.value === '') {
      this.setEntryProperty(entry, 'value', entry.label);
    }
  }

  protected handleChange(event: Event, property: string, entry: PropertyGridEditorEntry): void {
    const target = event.target as HTMLInputElement;
    const value = target.type === 'checkbox' ? target.checked : target.value;
    this.setEntryProperty(entry, property, value);
  }

  protected handleRemove(entry: PropertyGridEditorEntry): void {
    this.entries = this.entries.filter(item => item !== entry);
  }

  protected handleCreate(): void {
    const newEntry: PropertyGridEditorEntry = {
      id: 'fe' + Math.floor(Math.random() * 42) + Date.now(),
      label: '',
      value: '',
      selected: false,
    };
    this.entries = [...this.entries, newEntry];
  }

  protected handleDragStart(event: DragEvent, entry: PropertyGridEditorEntry): void {
    event.stopImmediatePropagation();
    this.draggedEntry = entry;
    event.dataTransfer?.setData('text/plain', 'dragging');
    event.dataTransfer?.setDragImage(new Image(), 0, 0);
  }

  protected handleDragOver(event: DragEvent): void {
    event.preventDefault();
    event.stopImmediatePropagation();
  }

  protected handleDragEnter(event: DragEvent, targetEntry: PropertyGridEditorEntry): void {
    event.preventDefault();
    event.stopImmediatePropagation();
    if (!this.draggedEntry || this.draggedEntry === targetEntry) {
      return;
    }

    const entriesCopy = [...this.entries];
    const fromIndex = entriesCopy.indexOf(this.draggedEntry);
    const toIndex = entriesCopy.indexOf(targetEntry);
    entriesCopy.splice(fromIndex, 1);

    const insertIndex = fromIndex < toIndex ? toIndex : toIndex;
    entriesCopy.splice(insertIndex, 0, this.draggedEntry);

    this.entries = entriesCopy;
  }

  protected handleDrop(event: DragEvent): void {
    event.preventDefault();
    event.stopImmediatePropagation();
    this.draggedEntry = null;
  }

  protected handleDragEnd(event: DragEvent): void {
    event.stopImmediatePropagation();
    this.draggedEntry = null;
  }

  protected handleMoveClick(event: Event, entry: PropertyGridEditorEntry): void {
    if (this.movedEntry === entry) {
      this.movedEntry = null;
    } else {
      this.movedEntry = entry;
    }
  }

  protected handleMoveKeyDown(event: KeyboardEvent): void {
    if (this.movedEntry === null) {
      return;
    }

    const handledKeys = [
      'ArrowDown',
      'ArrowUp',
      'Home',
      'End',
      'Enter',
      'Space',
      'Escape',
      'Tab',
    ];
    if (!handledKeys.includes(event.code) || event.altKey || event.ctrlKey) {
      return;
    }

    event.preventDefault();

    let direction: number;
    switch (event.code) {
      case 'Escape':
      case 'Enter':
      case 'Space':
        this.movedEntry = null;
        return;
      case 'ArrowUp':
        direction = -1;
        break;
      case 'ArrowDown':
        direction = 1;
        break;
      default:
        return;
    }

    const entriesCopy = [...this.entries];
    const fromIndex = entriesCopy.indexOf(this.movedEntry);
    const toIndex = fromIndex + direction;
    console.log(fromIndex, toIndex);
    if (toIndex < 0 || toIndex >= entriesCopy.length) {
      return;
    }

    entriesCopy.splice(fromIndex, 1);
    const insertIndex = fromIndex < toIndex ? toIndex : toIndex;
    entriesCopy.splice(insertIndex, 0, this.movedEntry);
    this.entries = entriesCopy;

    this.activeElementRef = (event.target as HTMLElement).closest('button');
  }

  protected setEntryProperty(entry: PropertyGridEditorEntry, property: string, value: string|boolean): void {
    const index = this.entries.indexOf(entry);
    if (index === -1) {
      return;
    }

    const updatedEntry = { ...entry };
    if (property === 'label') {
      updatedEntry.label = String(value);
    }
    if (property === 'value') {
      updatedEntry.value = String(value);
    }
    if (property === 'selected') {
      updatedEntry.selected = !!value;

      if (updatedEntry.selected === true && !this.enableMultiSelection) {
        // Deselect others
        this.entries = this.entries.map((item, i) =>
          i === index ? updatedEntry : { ...item, selected: false }
        );
        return;
      }
    }

    this.entries = this.entries.map((item, i) => (i === index ? updatedEntry : item));
  }
}

declare global {
  interface HTMLElementTagNameMap {
    'typo3-form-property-grid-editor': PropertyGridEditor;
  }
}
