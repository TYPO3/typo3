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
import { MODE } from '@typo3/form/backend/form-wizard/steps/mode-step';
import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import type { WizardStepValueInterface } from '@typo3/backend/wizard/steps/wizard-step-value-interface';
import type { WizardStepSummaryInterface } from '@typo3/backend/wizard/steps/wizard-step-summary-interface';
import type { SummaryItem } from '@typo3/backend/wizard/steps/summary-item-interface';
import formManagerLabels from '~labels/form.form_manager_javascript';
import type { FormWizardContext } from '@typo3/form/backend/form-wizard/form-wizard';

export interface FormSettings {
  formName?: string,
  savePath?: string,
  prototype?: string,
  template?: string,
}

export class SettingsStep implements WizardStepInterface, WizardStepValueInterface, WizardStepSummaryInterface {
  readonly key = 'settings';
  readonly title = formManagerLabels.get('formManager.newFormWizard.step2.title');
  readonly autoAdvance = true;

  private data: FormSettings = {
    formName: '',
    savePath: '',
    prototype: '',
    template: '',
  };

  constructor(private readonly context: FormWizardContext) {
    this.reset();
  }

  public isComplete(): boolean {
    return this.getValue()?.formName !== '';
  }

  public render(): TemplateResult {
    return html`
      ${this.renderPredefinedFormFields()}
      ${this.renderSavePath()}
      ${this.renderFormNameInput()}
    `;
  }

  public reset(): void {
    this.setValue({
      formName: '',
      savePath: this.context.formManager.getAccessibleFormStorageFolders()[0]?.value ?? '',
    });
    this.setPrototype(this.context.formManager.getPrototypes()[0]?.value ?? '');
    this.context.clearStoreData(this.key);
  }

  public getValue(): FormSettings {
    return this.data;
  }

  public setValue(value: FormSettings): void {
    this.data = {
      ...this.data,
      ...value
    };

    this.context.wizard.requestUpdate();
  }

  public beforeAdvance(): void {
    this.context.setStoreData(this.key, this.getValue());
  }

  getSummaryData(): SummaryItem[] {
    const config = this.context.getStoreData(this.key);

    const prototypeLabel = this.context.formManager.getPrototypes()
      .find(p => p.value = config.prototype)?.label;

    const templateLabel = this.context.formManager.getTemplatesForPrototype(config.prototype)
      .find(t => t.value === config.template)
      ?.label;

    const isPredefined = this.context.getStoreData('mode') === MODE.Predefined;

    return [
      ...(isPredefined
        ? [
          {
            value: prototypeLabel,
            label: formManagerLabels.get('formManager.form_prototype')
          },
          {
            value: templateLabel,
            label: formManagerLabels.get('formManager.form_template')
          }
        ]
        : []),
      {
        value: config.formName,
        label: formManagerLabels.get('formManager.form_name')
      },
      {
        value: config.savePath,
        label: formManagerLabels.get('formManager.form_save_path')
      }
    ];
  }

  private renderSavePath(): TemplateResult | typeof nothing {
    const storageFolders = this.context.formManager.getAccessibleFormStorageFolders() ?? [];

    if (storageFolders.length <= 1) {
      return nothing;
    }

    return html `
      <div class="form-group">
        <label class="form-label" for="new-form-save-path">${formManagerLabels.get('formManager.form_save_path')}</label>
        <div class="form-description">${formManagerLabels.get('formManager.form_save_path_description')}</div>
        <select class="new-form-save-path form-select" id="new-form-save-path" data-identifier="newFormSavePath">
          ${storageFolders.map(option => html`
            <option
              value=${option.label}
              ?selected=${option.value === this.data.savePath}
              @change=${(e: Event) => this.setValue({ savePath: (e.target as HTMLOptionElement).value })}
            >
              ${option.label}
            </option>
        `)}
        </select>
      </div>`;
  }

  private renderFormNameInput(): TemplateResult {
    this.focusInput('#new-form-name');
    return html `
      <div class="form-group">
        <label class="form-label" for="new-form-name">${formManagerLabels.get('formManager.form_name')}</label>
        <div class="form-description">${formManagerLabels.get('formManager.form_name_description')}</div>
        <input class="form-control ${!this.isComplete() ? 'has-error' : ''}"
               id="new-form-name"
               data-identifier="newFormName"
               name="newFormName"
               .value=${this.data.formName}
               @input=${(e: Event) => this.setValue({ formName: (e.target as HTMLInputElement).value })}
        />
      </div>`;
  }

  private renderPredefinedFormFields(): TemplateResult | typeof nothing {
    const prototypes = this.context.formManager.getPrototypes() ?? [];

    if (this.context.getStoreData('mode') !== MODE.Predefined || prototypes.length < 1) {
      return nothing;
    }
    const currentPrototype = this.data.prototype;

    const templates = this.context.formManager.getTemplatesForPrototype(currentPrototype);

    let templatesFormGroup: TemplateResult | typeof nothing = nothing;
    if (templates.length > 0) {
      templatesFormGroup = html `
        <div class="form-group">
          <label class="form-label" for="new-form-template">${formManagerLabels.get('formManager.form_template')}</label>
          <select class="new-form-template form-select"
                  id="new-form-template"
                  data-identifier="newFormTemplate"
                  @change=${(e: Event) => this.setValue({ template: (e.target as HTMLOptionElement).value })}
          >
            ${templates.map(option => html`
              <option
                ?selected=${option.value === this.data.template}
                value=${option.value}
              >
                ${option.label}
              </option>
            `)}
          </select>
        </div>`;
    }

    return html `
      <div class="form-group">
        <label class="form-label" for="new-form-prototype-name">${formManagerLabels.get('formManager.form_prototype')}</label>
        <select class="new-form-prototype-name form-select"
                id="new-form-prototype-name"
                data-identifier="newFormPrototype"
                @change=${(e: Event) => this.setPrototype((e.currentTarget as HTMLSelectElement).value)}
        >
          ${prototypes.map(option => html`
            <option
              value=${option.value}
              ?selected=${option.value === this.data.prototype}
            >
              ${option.label}
            </option>
          `)}
        </select>
      </div>
      ${templatesFormGroup}`;
  }

  private setPrototype(currentPrototype: string ): void {
    const currentTemplates = this.context.formManager.getTemplatesForPrototype(currentPrototype);

    this.setValue({
      prototype: currentPrototype,
      template: (currentTemplates[0]?.value ?? '')
    });
  }

  private focusInput(selector: string): void {
    this.context.wizard.updateComplete.then(() => {
      const input = this.context.wizard.renderRoot.querySelector<HTMLInputElement>(selector);
      if (input) {
        input.focus();
      }
    });
  }
}

export default SettingsStep;
