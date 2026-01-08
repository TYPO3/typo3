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

import { html, nothing, type TemplateResult } from 'lit';
import { live } from 'lit/directives/live';
import { styleMap } from 'lit/directives/style-map';
import { repeat } from 'lit/directives/repeat';
import { Task, TaskStatus } from '@lit/task';
import { lll } from '@typo3/core/lit-helper';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { LocalizationContext, LocalizationStepInterface, LocalizationStepValueInterface, LocalizationStepSummaryInterface } from '@typo3/backend/localization/localization-wizard';
import '@typo3/backend/element/alert-element';
import '@typo3/backend/element/icon-element';
import '@typo3/backend/element/spinner-element';

type ContentRecord = {
  uid: number;
  title: string;
  icon: string;
};

type LayoutColumn = {
  position: number;
  label: string;
  records: ContentRecord[];
  colspan?: number;
  rowspan?: number;
  identifier?: string | null;
};

type LayoutRow = {
  columns: LayoutColumn[];
};

type BackendLayoutStructure = {
  title: string;
  identifier: string;
  colCount: number;
  rowCount: number;
  elementCount: number;
  rows: LayoutRow[];
};

type PageRecordSelection = {
  layout: BackendLayoutStructure;
};

export class ContentRecordSelectionStep implements LocalizationStepInterface, LocalizationStepValueInterface, LocalizationStepSummaryInterface {
  readonly key = 'contentRecordSelection';
  readonly title = lll('step.content_selection.title');
  readonly autoAdvance = true;
  private readonly task: Task<[number, number, number], PageRecordSelection>;
  private lastTargetLanguage: number | null = null;
  private lastSourceLanguage: number | null = null;
  private hasDispatchedAutoAdvance: boolean = false;
  private hasUserInteracted: boolean = false;
  private selectedRecordUids: number[] = [];

  constructor(private readonly context: LocalizationContext) {
    this.task = new Task(this.context.wizard, {
      task: async ([pageUid, targetLanguage, sourceLanguage]: [number, number, number]): Promise<PageRecordSelection> => {
        const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_localization_get_content).withQueryArguments({
          pageUid: pageUid,
          targetLanguage: targetLanguage,
          sourceLanguage: sourceLanguage,
        }).get();
        const result: PageRecordSelection = await response.resolve();

        return result;
      },
      args: () => [this.context.recordUid, this.context.getStoreData('targetLanguage'), this.context.getStoreData('sourceLanguage')],
      autoRun: false
    });
  }

  public isComplete(): boolean {
    return true;
  }

  public render(): TemplateResult {
    const currentTargetLanguage = this.context.getStoreData('targetLanguage');
    const currentSourceLanguage = this.context.getStoreData('sourceLanguage');

    // Re-run task if context data has changed
    const shouldRun = this.lastTargetLanguage !== currentTargetLanguage ||
                       this.lastSourceLanguage !== currentSourceLanguage ||
                       this.task.status === TaskStatus.INITIAL;

    if (shouldRun) {
      this.lastTargetLanguage = currentTargetLanguage;
      this.lastSourceLanguage = currentSourceLanguage;
      this.hasDispatchedAutoAdvance = false; // Reset flag when task runs
      this.hasUserInteracted = false; // Reset interaction flag when task runs
      this.task.run();
    }

    return this.task.render({
      complete: (pageRecordSelection: PageRecordSelection) => {
        // Initialize from store if not already set
        if (this.getValue().length === 0 && !this.hasUserInteracted) {
          const storedUids = this.context.getStoreData('selectedRecordUids');
          if (storedUids && storedUids.length > 0) {
            this.setValue(storedUids);
          }
        }

        // Auto-advance if there are no records to select (only once per task run)
        if (pageRecordSelection.layout.elementCount === 0 && !this.hasDispatchedAutoAdvance) {
          this.hasDispatchedAutoAdvance = true;
          this.context.dispatchAutoAdvance();
          return this.context.wizard.renderLoader('localization_wizard.loading');
        }

        const allUids = this.getAllRecordUids(pageRecordSelection.layout);
        // Only auto-select all records if user hasn't interacted yet
        if (this.getValue().length === 0 && allUids.length > 0 && !this.hasUserInteracted) {
          this.setValue(allUids);
        }

        const allSelected = allUids.length > 0 && allUids.every(uid => this.getValue().includes(uid));

        return html`
          <div class="localization-record-selection">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div>
                <h2 class="h4">${lll('step.content_selection.headline')}</h2>
                <p class="mb-0">${lll('step.content_selection.description')}</p>
              </div>
              ${allUids.length > 0 ? html`
                <button
                  type="button"
                  id="selectionToggle"
                  class="btn btn-primary"
                  @click=${() => this.handleSelectionToggle()}
                >
                  ${allSelected ? lll('step.content_selection.button.deselect_all') : lll('step.content_selection.button.select_all')}
                </button>
              ` : nothing}
            </div>
            ${allUids.length === 0 ? html`
              <typo3-backend-alert
                severity="3"
                message="${lll('step.content_selection.no_content.message')}"
                show-icon
              ></typo3-backend-alert>
            ` : html`
              <div class="record-selection-content">
                ${this.renderLayout(pageRecordSelection.layout)}
              </div>
            `}
          </div>
        `;
      },
      error: (error: unknown) => this.context.wizard.renderError(
        'localization_wizard.step.error.title',
        'localization_wizard.step.content_selection.error.message',
        error
      ),
      pending: () => this.context.wizard.renderLoader('localization_wizard.loading')
    });
  }

  public reset(): void {
    this.setValue([]);
    this.context.clearStoreData('selectedRecordUids');
    this.hasUserInteracted = false;
  }

  public getValue(): number[] {
    return this.selectedRecordUids;
  }

  public setValue(value: number[]): void {
    this.selectedRecordUids = value;
    this.context.setStoreData('selectedRecordUids', value);
  }

  public beforeAdvance(): void {
    this.context.setStoreData('selectedRecordUids', this.getValue());
  }

  public getSelectedRecordsWithDetails(): ContentRecord[] {
    const selectedUids = this.getValue();
    const records: ContentRecord[] = [];

    if (this.task.value) {
      this.task.value.layout.rows.forEach((row: LayoutRow) => {
        row.columns.forEach((column: LayoutColumn) => {
          column.records.forEach((record: ContentRecord) => {
            if (selectedUids.includes(record.uid)) {
              records.push(record);
            }
          });
        });
      });
    }

    return records;
  }

  public getSummary(): TemplateResult {
    const selectedRecords = this.getSelectedRecordsWithDetails();
    const count = selectedRecords.length;
    if (count === 0) {
      return html`${nothing}`;
    }

    return html`
      <tr>
        <th class="col-fieldname align-top">
          ${lll('step.content_selection.summary.title')}
        </th>
        <td class="col-word-break">
          <p><strong>${lll('step.content_selection.summary.amount', count)}</strong></p>
          ${selectedRecords.length > 0 ? html`
            <ul class="list-group">
              ${selectedRecords.map(record => html`
                <li class="list-group-item">
                  <span title="id=${record.uid}">
                    <typo3-backend-icon identifier="${record.icon}" size="small" class="me-1"></typo3-backend-icon>
                  </span>
                  ${record.title}
                </li>
              `)}
            </ul>
          ` : nothing}
        </td>
      </tr>
    `;
  }

  private renderLayout(layout: BackendLayoutStructure): TemplateResult {
    let currentRow = 1;

    /* eslint-disable @stylistic/indent */
    return html`
      <div class="pagelayout">
        ${layout.rows.map((row) => {
          const renderedRow = this.renderRow(row, currentRow);
          currentRow += 1;
          return renderedRow;
        })}
      </div>
    `;
  }

  private renderRow(row: LayoutRow, currentRow: number): TemplateResult {
    let currentCol = 1;

    return html`
      ${repeat(row.columns, (column) => column.position, (column) => {
        const renderedColumn = this.renderColumn(column, currentRow, currentCol);
        currentCol += column.colspan || 1;
        return renderedColumn;
      })}
    `;
  }

  private renderColumn(column: LayoutColumn, currentRow: number, currentCol: number): TemplateResult {
    const selectedRecordsInColumn = column.records.filter(record => this.getValue().includes(record.uid));
    const isColumnFullySelected = selectedRecordsInColumn.length === column.records.length;
    const isColumnPartiallySelected = selectedRecordsInColumn.length > 0 && selectedRecordsInColumn.length < column.records.length;

    const style = styleMap({
      '--pagelayout-cell-col': currentCol,
      '--pagelayout-cell-colspan': column.colspan || 1,
      '--pagelayout-cell-row': currentRow,
      '--pagelayout-cell-rowspan': column.rowspan || 1,
    });

    return html`
      <div class="pagelayout-cell" style=${style}>
        <div class="pagelayout-cell-header">
          ${column.records.length > 0 ? html`
            <div class="form-check form-check-type-toggle">
              <input
                class="form-check-input"
                type="checkbox"
                id="records-column-${column.position}"
                .checked=${live(isColumnFullySelected)}
                .indeterminate=${isColumnPartiallySelected}
                @change=${(e: Event) => this.handleColumnToggle(e, column)}
              >
              <label class="form-check-label" for="records-column-${column.position}">
                ${column.label}
              </label>
            </div>
          ` : html`
            <div class="form-check form-check-type-toggle">
              <div class="form-check-text">
                ${column.label}
              </div>
            </div>
          `}
        </div>
        <div class="pagelayout-cell-content">
          ${repeat(column.records, (record) => record.uid, (record) => {
            return this.renderRecord(record);
          })}
        </div>
      </div>
    `;
  }

  private renderRecord(record: ContentRecord): TemplateResult {
    return html`
      <div class="form-check form-check-type-toggle">
        <input
          type="checkbox"
          class="form-check-input"
          id="record-uid-${record.uid}"
          .checked=${live(this.getValue().includes(record.uid))}
          @change=${(e: Event) => this.handleRecordToggle(e, record)}
        >
        <label class="form-check-label" for="record-uid-${record.uid}">
          <span title="id=${record.uid}">
            <typo3-backend-icon identifier="${record.icon}" size="small"></typo3-backend-icon>
          </span>
          ${record.title}
        </label>
      </div>
    `;
  }

  private handleColumnToggle(e: Event, column: LayoutColumn): void {
    const input = e.currentTarget as HTMLInputElement;
    const columnUids = column.records.map(record => record.uid);

    if (input.checked) {
      const newUids = columnUids.filter(uid => !this.getValue().includes(uid));
      this.setValue([...this.getValue(), ...newUids]);
    } else {
      this.setValue(this.getValue().filter(uid => !columnUids.includes(uid)));
    }

    this.hasUserInteracted = true;
  }

  private handleRecordToggle(e: Event, record: ContentRecord): void {
    const input = e.currentTarget as HTMLInputElement;

    if (input.checked) {
      if (!this.getValue().includes(record.uid)) {
        this.setValue([...this.getValue(), record.uid]);
      }
    } else {
      this.setValue(this.getValue().filter(uid => uid !== record.uid));
    }

    this.hasUserInteracted = true;
  }

  private getAllRecordUids(layout: BackendLayoutStructure): number[] {
    const allUids: number[] = [];
    layout.rows.forEach((row: LayoutRow) => {
      row.columns.forEach((column: LayoutColumn) => {
        column.records.forEach((record: ContentRecord) => {
          allUids.push(record.uid);
        });
      });
    });
    return allUids;
  }

  private handleSelectionToggle(): void {
    if (!this.task || this.task.status !== TaskStatus.COMPLETE || !this.task.value) {
      return;
    }

    this.hasUserInteracted = true;

    const allUids = this.getAllRecordUids(this.task.value.layout);
    const shouldSelectAll = this.getValue().length === 0;

    this.setValue(shouldSelectAll ? allUids : []);
  }
}

export default ContentRecordSelectionStep;
