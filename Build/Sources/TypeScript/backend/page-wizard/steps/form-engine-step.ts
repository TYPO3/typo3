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

import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import '@typo3/backend/tree/page-position-select';
import { html, type TemplateResult } from 'lit';
import type { PageWizardContext } from '@typo3/backend/page-wizard/page-wizard';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import { executeJavaScriptModuleInstruction, type JavaScriptItemPayload } from '@typo3/core/java-script-item-processor';
import type { WizardStepAfterRenderInterface } from '@typo3/backend/wizard/steps/wizard-step-after-render-interface';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { DynamicWizardStepConfigurationData } from '@typo3/backend/wizard/helper/dynamic-steps-loader';

export class FormEngineStep implements WizardStepInterface, WizardStepAfterRenderInterface {
  autoAdvance = false;
  key = '';
  title = '';
  html: string = '';
  modules: JavaScriptItemPayload[] = [];
  labels: Record<string, string> = {};

  private summary: SummaryItem[];

  constructor(
    private readonly context: PageWizardContext,
    configuration: DynamicWizardStepConfigurationData
  ) {
    this.key = configuration.key;
    this.title = configuration.title;
    this.html = configuration.html;
    this.modules = configuration.modules;
    this.labels = configuration.labels;
  }

  public getValue(): Record<string, any> | null {
    const form = this.context.wizard.querySelector<HTMLFormElement>('form[name="editform"]');
    if (!form) {
      return null;
    }

    const fields = this.context.getStoreData('fields') || {};
    const formData = new FormData(form);
    fields[this.key] = Object.fromEntries(
      Array.from(formData.entries()).filter(([, value]) => value !== '')
    );

    return fields;
  }

  public setValue(fields: Record<string, unknown>): void {
    const form = this.context.wizard.querySelector<HTMLFormElement>('form[name="editform"]');
    const values = fields?.[this.key];
    if (!form || !values) {
      return;
    }

    for (const [key, val] of Object.entries(values)) {
      const input = form.elements.namedItem(key) as HTMLInputElement;
      if (input) {
        delete input.dataset.formengineInputInitialized;
        if (input.type === 'checkbox') {
          input.checked = !!val;
        } else {
          input.value = val as string;
        }
      }
    }
  }

  public render(): TemplateResult {
    return html`${unsafeHTML(this.html)}`;
  }

  public getSummaryData(): SummaryItem[] {
    return this.summary;
  }

  public async afterRender(): Promise<void> {
    this.setValue(this.context.getStoreData('fields'));

    if (this.modules.length > 0) {
      await this.loadModules();
    }

    if (TYPO3.FormEngine) {
      TYPO3.FormEngine.reinitialize();

      const form = this.context.wizard.querySelector('form[name="editform"]');
      if (form) {
        form.addEventListener('t3-formengine-postfieldvalidation', () => {
          this.context.wizard.requestUpdate();
        });

        form.addEventListener('submit', (e: Event) => {
          e.preventDefault();
          this.context.wizard.goToNextStep();
        });

        const firstErrorElement = form.querySelector<HTMLInputElement>('.has-error');
        if (firstErrorElement) {
          firstErrorElement.focus();
        }
      }
    }
  }

  isComplete(): boolean {
    if (TYPO3.FormEngine && TYPO3.FormEngine.Validation) {
      return TYPO3.FormEngine.Validation.isValid();
    }

    return true;
  }

  public async beforeAdvance(): Promise<void> {
    const pageUid = (this.context.getStoreData('position')?.pageUid || 0);
    const fields = this.getValue();
    const values = fields[this.key] || {};

    const summaryFields: Record<string, string> = {};
    for (const [key, value] of Object.entries(values)) {
      summaryFields[this.getLabelKey(key)] = value as string;
    }

    const response = await new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_page_get_processed_value)
      .withQueryArguments({ fields: summaryFields, pageUid })
      .get();
    const result: Record<string, string> = await response.resolve();

    this.summary = Object.keys(summaryFields).map((labelKey) => {
      const value = summaryFields[labelKey];
      return {
        label: this.labels[labelKey] || labelKey,
        value: result[labelKey] ?? value
      };
    });

    this.context.setStoreData('fields', fields);
  }

  private async loadModules(): Promise<void> {
    const loadingPromises = this.modules.map(moduleItem => {
      return executeJavaScriptModuleInstruction(moduleItem);
    });

    await Promise.all(loadingPromises);
  }

  private getLabelKey(key: string): string {
    const matches = key.match(/\[([^\]]+)\]$/);
    return matches ? matches[1] : key;
  }
}
export default FormEngineStep;
