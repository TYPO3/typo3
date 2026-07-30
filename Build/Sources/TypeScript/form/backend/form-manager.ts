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

type ViewModel = typeof import('@typo3/form/backend/form-manager/view-model');

export interface LabelValuePair {
  label: string;
  value: string;
}

export interface StorageAdapter {
  label: string;
  description: string;
  typeIdentifier: string;
  iconIdentifier: string;
  options?: {
    allowedStorageLocations?: LabelValuePair[],
  };
}

export interface TemplateOption {
  label: string;
  description?: string;
  templatePath: string;
  prototypeIdentifier: string;
  iconIdentifier: string;
}

export interface PrototypeTemplateGroup {
  label: string;
  identifier: string;
  templates: TemplateOption[];
}

export type FormManagerConfiguration = {
  selectablePrototypesConfiguration?: Array<{
    label: string;
    identifier: string;
    newFormTemplates: Array<{
      label: string;
      description?: string;
      templatePath: string;
      iconIdentifier?: string;
    }>;
  }>,
  endpoints?: {
    [key: string]: string;
  },
  accessibleStorageAdapters?: StorageAdapter[],
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
  private readonly viewModel: ViewModel;

  public constructor(
    configuration: FormManagerConfiguration,
    viewModel: ViewModel
  ) {
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

  public getTemplatesGroupedByPrototype(): PrototypeTemplateGroup[] {
    if (!Array.isArray(this.configuration.selectablePrototypesConfiguration)) {
      return [];
    }

    return this.configuration.selectablePrototypesConfiguration
      .filter((proto) => Array.isArray(proto.newFormTemplates) && proto.newFormTemplates.length > 0)
      .map((proto): PrototypeTemplateGroup => ({
        label: proto.label,
        identifier: proto.identifier,
        templates: proto.newFormTemplates.map((tpl): TemplateOption => ({
          label: tpl.label,
          description: tpl.description,
          templatePath: tpl.templatePath,
          prototypeIdentifier: proto.identifier,
          iconIdentifier: tpl.iconIdentifier ?? 'form-page',
        })),
      }));
  }

  public getAccessibleStorageAdapters(): StorageAdapter[] {
    if (!Array.isArray(this.configuration.accessibleStorageAdapters)) {
      return [];
    }

    return this.configuration.accessibleStorageAdapters;
  }

  public getAccessibleStorageLocationsForAdapter(storageAdapter: StorageAdapter): LabelValuePair[] {
    const adapter = this.configuration.accessibleStorageAdapters?.find(
      (adapter) => adapter.typeIdentifier === storageAdapter.typeIdentifier
    );

    if (!adapter || !adapter.options?.allowedStorageLocations) {
      return [];
    }

    return adapter.options.allowedStorageLocations;
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
export function getInstance(
  configuration: FormManagerConfiguration,
  viewModel: ViewModel
): FormManager {
  if (formManagerInstance === null) {
    formManagerInstance = new FormManager(configuration, viewModel);
  }
  return formManagerInstance;
}
