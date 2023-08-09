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

/**
 * Module: @typo3/form/backend/form-manager
 */

// @todo once view-model.js is migrated:
// replace ViewModelLike with typeof import('@typo3/form/backend/form-manager/view-model')
interface ViewModelLike {
  bootstrap: (formManager: FormManager) => void;
}

export interface LabelValuePair {
  label: string;
  value: string;
}

export type FormManagerConfiguration = {
  selectablePrototypesConfiguration?: Array<{
    label: string;
    identifier: string;
    newFormTemplates: Array<{
      label: string;
      templatePath: string;
    }>;
  }>,
  accessibleFormStorageFolders?: LabelValuePair[],
  endpoints?: {
    [key: string]: string;
  },
};

export function assert(test: boolean|(() => boolean), message: string, messageCode: number): void {
  if (typeof test === 'function') {
    test = (test() !== false);
  }
  if (!test) {
    message = message || 'Assertion failed';
    if (messageCode) {
      message = message + ' (' + messageCode + ')';
    }
    if ('undefined' !== typeof Error) {
      throw new Error(message);
    }
    throw message;
  }
}

export class FormManager {
  private isRunning: boolean = false;
  private configuration: FormManagerConfiguration;
  private viewModel: ViewModelLike;

  public constructor(configuration: FormManagerConfiguration, viewModel: ViewModelLike) {
    this.configuration = configuration;
    this.viewModel = viewModel;
  }

  /**
   * @todo deprecate, use exported `assert()` method instead
   */
  public assert(test: boolean|(() => boolean), message: string, messageCode: number): void {
    assert(test, message, messageCode);
  }

  public getPrototypes(): LabelValuePair[] {
    if (!Array.isArray(this.configuration.selectablePrototypesConfiguration)) {
      return [];
    }

    return this.configuration.selectablePrototypesConfiguration.map((selectablePrototype): LabelValuePair => {
      return {
        label: selectablePrototype.label,
        value: selectablePrototype.identifier,
      };
    });
  }

  public getTemplatesForPrototype(prototypeName: string): LabelValuePair[] {
    assert('string' === typeof prototypeName, 'Invalid parameter "prototypeName"', 1475945286);
    if (!Array.isArray(this.configuration.selectablePrototypesConfiguration)) {
      return [];
    }

    const templates: LabelValuePair[] = [];
    this.configuration.selectablePrototypesConfiguration.forEach((selectablePrototype): void => {
      if (!Array.isArray(selectablePrototype.newFormTemplates)) {
        return;
      }

      if (selectablePrototype.identifier !== prototypeName) {
        return;
      }
      selectablePrototype.newFormTemplates.forEach((newFormTemplate): void => {
        templates.push({
          label: newFormTemplate.label,
          value: newFormTemplate.templatePath,
        });
      });
    });

    return templates;
  }

  public getAccessibleFormStorageFolders(): LabelValuePair[] {
    if (!Array.isArray(this.configuration.accessibleFormStorageFolders)) {
      return [];
    }

    return this.configuration.accessibleFormStorageFolders.map((folder): LabelValuePair => {
      return {
        label: folder.label,
        value: folder.value,
      };
    });
  }

  /**
   * @throws 1477506508
   */
  public getAjaxEndpoint(endpointName: string): string {
    assert(typeof this.configuration.endpoints[endpointName] !== 'undefined', 'Endpoint ' + endpointName + ' does not exist', 1477506508);

    return this.configuration.endpoints[endpointName];
  }

  /**
   * @throws 1475942618
   */
  public run(): FormManager {
    if (this.isRunning) {
      throw 'You can not run the app twice (1475942618)';
    }

    this.bootstrap();
    this.isRunning = true;
    return this;
  }

  /**
   * @throws 1475942906
   */
  private viewSetup(): void {
    assert('function' === typeof this.viewModel.bootstrap, 'The view model does not implement the method "bootstrap"', 1475942906);
    this.viewModel.bootstrap(this);
  }

  /**
   * @throws 1477506504
   */
  private bootstrap(): void {
    this.configuration = this.configuration || {};
    assert('object' === typeof this.configuration.endpoints, 'Invalid parameter "endpoints"', 1477506504);
    this.viewSetup();
  }
}

let formManagerInstance: FormManager = null;
/**
 * Return a singleton instance of a "FormManager" object.
 */
export function getInstance(configuration: FormManagerConfiguration, viewModel: ViewModelLike): FormManager {
  if (formManagerInstance === null) {
    formManagerInstance = new FormManager(configuration, viewModel);
  }
  return formManagerInstance;
}
