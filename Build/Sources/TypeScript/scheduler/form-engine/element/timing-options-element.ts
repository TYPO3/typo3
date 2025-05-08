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

import FormEngine from '@typo3/backend/form-engine';
import { selector } from '@typo3/core/literals';
import RegularEvent from '@typo3/core/event/regular-event';

enum RunningType {
  single = '1',
  recurring = '2',
}

/**
 * FormEngine element for renderType="schedulerTimingOptions" to disable
 * the end date and frequency fields if the running type is not set to "recurring" (value 2)
 */
class TimingOptions extends HTMLElement {
  private fieldPrefix: string;

  public async connectedCallback(): Promise<void> {
    await FormEngine.ready();
    this.fieldPrefix = this.getAttribute('fieldPrefix');
    this.registerEventHandler();
  }

  private registerEventHandler(): void {
    new RegularEvent('change', (): void => {
      this.toggleRunningType();
    }).delegateTo(this, selector`input[name='${this.fieldPrefix}[runningType]']`);

    this.toggleRunningType();
  }

  private toggleRunningType(): void {
    const selectedElement = <HTMLInputElement>this.querySelector(selector`input[name='${this.fieldPrefix}[runningType]']:checked`);
    const runningType = <string>selectedElement.value;

    this.querySelectorAll('.t3js-timing-options-end, .t3js-timing-options-parallel, .t3js-timing-options-frequency').forEach((el: HTMLElement) => {
      el.style.display = runningType === RunningType.recurring ? 'block' : 'none';

      if (el.classList.contains('t3js-timing-options-frequency')) {
        const input: HTMLInputElement = el.querySelector('input[data-formengine-validation-rules]');
        let validationRulesToggleCondition;
        let validationRules;
        if (runningType === RunningType.recurring) {
          validationRulesToggleCondition = input.getAttribute('data-formengine-validation-rules') === '[]';
          validationRules = '[{"type":"required"}]';
        } else {
          validationRulesToggleCondition = input.getAttribute('data-formengine-validation-rules') !== '[]';
          validationRules = '[]';
        }

        if (validationRulesToggleCondition) {
          input.setAttribute('data-formengine-validation-rules', validationRules);
          TYPO3.FormEngine.Validation.initializeInputField(input.dataset.formengineInputName);
          TYPO3.FormEngine.Validation.validate();
        }
      }
    });
  }
}

window.customElements.define('typo3-formengine-element-timing-options', TimingOptions);

