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

import { html, type TemplateResult } from 'lit';
import { Task, TaskStatus } from '@lit/task';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import type { PageWizardContext } from '@typo3/backend/page-wizard/page-wizard';
import '@typo3/backend/tree/page-position-select';
import {
  InsertPositionChangeEvent,
  insertPositionOptions,
  type Position
} from '@typo3/backend/tree/page-position-select';
import labels from '~labels/core.misc';

import AjaxRequest from '@typo3/core/ajax/ajax-request';

export type PositionData = {
  pageUid?: number;
  insertPosition: Position
};

type PageDetail = {
  uid: number,
  title: string,
  icon: string
};

export class PositionStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'position';
  readonly title = labels.get('selectPosition');
  readonly autoAdvance = true;
  private summaryTask: Task<[pageUid: number], PageDetail>;
  private positionData?: PositionData = null;
  private hasDispatchedAutoAdvance: boolean = false;

  constructor(private readonly context: PageWizardContext) {
    this.initSummaryTask();
  }

  public isComplete(): boolean {
    return Number.isFinite(this.positionData?.pageUid);
  }

  public render(): TemplateResult {
    // Initialize from store if not already set
    if (this.getValue() === null) {
      const storedValue = this.context.getStoreData(this.key);
      const predefinedPositionData = this.context?.configuration?.positionData ?? null;
      if (storedValue != null) {
        this.setValue(storedValue);
      } else if (predefinedPositionData !== null) {
        this.setValue(predefinedPositionData);

        const preventPositionAutoAdvance = this.context?.configuration?.preventPositionAutoAdvance ?? false;
        if (preventPositionAutoAdvance == false && !this.hasDispatchedAutoAdvance) {
          this.hasDispatchedAutoAdvance = true;
          this.context.dispatchAutoAdvance();
          return this.context.wizard.renderLoader();
        }
      }
    }
    const currentValue = this.getValue();

    return html`
      <typo3-backend-component-page-position-select
        .activePageId="${currentValue?.pageUid}"
        .insertPosition="${currentValue?.insertPosition}"
        @typo3:page-position-select-tree:insert-position-change=${(event: CustomEvent) => this.handleInsertPositionChange(event)}
        @typo3:page-position-select-tree:insert-position-confirm=${() => this.handleInsertPositionConfirm()}
      >
      </typo3-backend-component-page-position-select>
    `;
  }

  public reset(): void {
    this.setValue(null);
    this.context.clearStoreData(this.key);
  }

  public getValue(): PositionData {
    return this.positionData;
  }

  public setValue(value: PositionData): void {
    this.positionData = value;
    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue());
    this.initSummaryTask();
  }

  public getSummaryData(): SummaryItem[] {
    const positionData = this.context.getStoreData(this.key);
    const currentPageUid = positionData.pageUid;
    const insertPositionOption = insertPositionOptions.find(
      (item) => item.value === positionData.insertPosition
    );

    if (this.summaryTask.status === TaskStatus.INITIAL) {
      this.summaryTask.run([currentPageUid]);
    }

    const pageHtml = this.summaryTask.render({
      complete: (page: PageDetail) => {
        return html`
          <typo3-backend-icon identifier="${page.icon}" size="small" class="me-1"></typo3-backend-icon>
          ${page.title} <code>[pages:${page.uid}]</code>
        `;
      },
      error: () => this.context.wizard.renderError(labels.get('pageSelectPositionError')),
      pending: () => this.context.wizard.renderLoader()
    });

    return [
      {
        label: labels.get('pageSelectPosition'),
        value: html `${pageHtml} <i>(${insertPositionOption?.label})</i>`
      }
    ];
  }

  private handleInsertPositionChange(event: InsertPositionChangeEvent): void {
    this.setValue({
      pageUid: event.detail.pageUid,
      insertPosition: event.detail.position
    });
  }

  private handleInsertPositionConfirm(): void {
    this.context.dispatchAutoAdvance();
  }

  private initSummaryTask(): void {
    this.summaryTask = new Task(this.context.wizard, {
      task: async ([pageUid]: [number]): Promise<PageDetail> => {
        const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_page_get_page_detail)
          .withQueryArguments({ pageUid: pageUid })
          .get();

        return await response.resolve();
      },
      autoRun: false
    });
  }
}

export default PositionStep;
