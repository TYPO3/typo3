import type { WizardStepInterface } from '@typo3/backend/wizard/steps/wizard-step-interface';
import AjaxRequest from '@typo3/core/ajax/ajax-request';
import type { AjaxResponse } from '@typo3/core/ajax/ajax-response';
import type { JavaScriptItemPayload } from '@typo3/core/java-script-item-processor';

export type DynamicWizardStepConfigurationData = {
  html?: string;
  key: string;
  title: string;
  modules?: JavaScriptItemPayload[];
  labels?: Record<string, string>;
};

type WizardConfiguration = {
  steps: {
    module: string;
    configurationData?: DynamicWizardStepConfigurationData
  }[];
};

interface WizardContextInterface {
  getDataStore: () => Readonly<object>;
}

type WizardStepModule = {
  default: {
    new(context: WizardContextInterface, configuration: DynamicWizardStepConfigurationData): WizardStepInterface
  }
};

/**
 * Dynamically loads and instantiates wizard steps based on the provided wizard type and context.
 *
 * 1. Sends an AJAX request to fetch the configuration for the specified wizard type.
 * 2. Resolves the response into a WizardConfiguration object containing step definitions.
 * 3. Iterates over each step configuration:
 *    - Validates that the module path exists.
 *    - Dynamically imports the step module.
 *    - Instantiates the step class and merges the context and step-specific configuration.
 * 4. Returns a Promise that resolves with an array of fully initialized WizardStepInterface instances.
 */
export function loadDynamicSteps(wizardType: string, context: WizardContextInterface): Promise<WizardStepInterface[]> {
  return new AjaxRequest(TYPO3.settings.ajaxUrls.wizard_config).withQueryArguments({ mode: wizardType, data: context.getDataStore() }).get()
    .then((response: AjaxResponse) => response.resolve())
    .then(async (config: WizardConfiguration ) => {
      return await Promise.all(config.steps.map(async (step) => {
        if (!step.module) {
          throw new Error('Step data does not contain a module path');
        }

        const { default: StepClass } = await import(step.module) as WizardStepModule;

        if (!StepClass) {
          throw new Error(`Step module ${step.module} does not export a default class`);
        }

        return new StepClass(context, step.configurationData!);
      }));
    });
}
