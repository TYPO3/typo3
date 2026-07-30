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
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import formManagerLabels from '~labels/form.form_manager_javascript';
import type { FormWizardContext } from '@typo3/form/backend/form-wizard/form-wizard';
import type { TemplateOption, PrototypeTemplateGroup } from '@typo3/form/backend/form-manager';
import { Categories, type DataCategoriesInterface, type DataItemInterface } from '@typo3/backend/new-record-wizard';
import '@typo3/backend/new-record-wizard';

export class ModeStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'template';
  readonly title = formManagerLabels.get('formManager.newFormWizard.step1.progressLabel');
  readonly autoAdvance = true;
  readonly hideActions = true;

  private selected: TemplateOption | null = null;
  private readonly groups: PrototypeTemplateGroup[] = [];
  private readonly wizardCategories: Categories;
  private readonly templateMap = new Map<string, TemplateOption>();

  constructor(private readonly context: FormWizardContext) {
    this.groups = this.context.formManager.getTemplatesGroupedByPrototype();

    const categoriesData: DataCategoriesInterface = {};
    this.groups.forEach((group, groupIndex) => {
      categoriesData[String(groupIndex)] = {
        identifier: group.identifier,
        label: group.label,
        items: group.templates.map((tpl) => {
          const id = `${tpl.prototypeIdentifier}::${tpl.templatePath}`;
          this.templateMap.set(id, tpl);
          const item: DataItemInterface = {
            identifier: id,
            label: tpl.label,
            description: tpl.description ?? '',
            icon: tpl.iconIdentifier,
            iconOverlay: null,
            url: null,
            requestType: 'event',
            defaultValues: undefined,
            saveAndClose: undefined,
            event: 'form-template-selected',
          };
          return item;
        }),
      };
    });
    this.wizardCategories = Categories.fromData(categoriesData);
  }

  public isComplete(): boolean {
    return this.selected !== null;
  }

  public render(): TemplateResult {
    return html`
      <typo3-backend-new-record-wizard
        .categories=${this.wizardCategories}
        displaymenu="false"
        displayfilter="false"
        @form-template-selected=${(e: CustomEvent) => { e.preventDefault(); this.handleSelection(e.detail.item.identifier); }}
      ></typo3-backend-new-record-wizard>
    `;
  }

  public reset(): void {
    this.selected = null;
    this.context.clearStoreData(this.key);
  }

  public getValue(): TemplateOption | null {
    return this.selected;
  }

  public setValue(value: TemplateOption | null): void {
    this.selected = value;
    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue());
  }

  public getSummaryData(): SummaryItem[] {
    const selected = this.context.getStoreData(this.key) as TemplateOption | undefined;
    if (!selected) {
      return [];
    }

    return [{
      label: formManagerLabels.get('formManager.form_template'),
      value: html`
        <typo3-backend-icon identifier="${selected.iconIdentifier}" size="small" class="me-1"></typo3-backend-icon>
        ${selected.label}
      `
    }];
  }

  private handleSelection(identifier: string): void {
    this.selected = this.templateMap.get(identifier) ?? null;
    this.context.setStoreData(this.key, this.getValue());
    this.context.dispatchAutoAdvance();
  }
}

export default ModeStep;
