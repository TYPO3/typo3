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
 * Module: @typo3/form/backend/form-editor/core
 */
import $ from 'jquery';

export type EditorConfiguration = {
  identifier: string,
  additionalElementPropertyPaths?: string[],
  browsableType?: string,
  buttonLabel?: string,
  configurationOptions?: {
    numbersOfColumnsToUse?: {
      label: string,
      propertyPath: string,
      fieldExplanationText: string,
    },
    validationErrorMessage?: {
      label: string,
      propertyPath: string,
      fieldExplanationText?: string,
      errorCodes?: string[]
    },
    viewPorts?: Array<{
      viewPortIdentifier: string,
      label: string
    }>
  },
  doNotSetIfPropertyValueIsEmpty?: boolean,
  enableAddRow?: boolean,
  enableDeleteRow?: boolean,
  enableFormelementSelectionButton?: boolean,
  errorCodes?: string[],
  fieldExplanationText?: string,
  gridColumns?: Array<{
    name: string,
    title: string,
  }>,
  iconIdentifier?: string,
  isSortable?: boolean,
  label?: string,
  multiSelection?: boolean,
  placeholder?: string,
  propertyPath?: string,
  propertyValue?: string,
  propertyValidators?: ValidatorsConfig,
  propertyValidatorsMode?: 'OR' | 'AND',
  removeLastAvailableRowFlashMessageTitle?: string,
  removeLastAvailableRowFlashMessageMessage?: string,
  shouldShowPreselectedValueColumn?: 'single' | 'multiple',
  selectOptions?: Array<{
    value: string,
    label: string,
  }>
  templateName?: string,
  useLabelAsFallbackValue?: boolean,
  validatorIdentifier?: string,
};

export type BaseFormElementDefinition = {
  type: string,
  identifier: string,
  label: string,
  group?: string,
  renderables?: FormElementDefinition[]
  groupSorting?: number,
  iconIdentifier?: string,
  predefinedDefaults?: Record<string, unknown>,
  editors?: Array<EditorConfiguration>
  propertyCollections?: {
    // @todo: be explicit about key being 'finishers' | 'validators' (?)
    [key: string]: Array<{
      identifier: string,
      editors: Array<EditorConfiguration>
    }>
  },
  _isTopLevelFormElement?: boolean,
  _isCompositeFormElement?: boolean,
  _isGridRowFormElement?: boolean,
};

type RootFormElementDefinition = {
  inspectorEditorFormElementSelectorNoElements: string,
  modalCloseCancelButton: string,
  modalCloseConfirmButton: string,
  modalCloseDialogMessage: string,
  modalCloseDialogTitle: string,
  modalInsertElementsDialogTitle: string,
  modalInsertPagesDialogTitle: string,
  modalRemoveElementCancelButton: string,
  modalRemoveElementConfirmButton: string,
  modalRemoveElementDialogMessage: string,
  modalRemoveElementDialogTitle: string,
  modalRemoveElementLastAvailablePageFlashMessageMessage: string,
  modalRemoveElementLastAvailablePageFlashMessageTitle: string,
  modalValidationErrorsConfirmButton: string,
  modalValidationErrorsDialogTitle: string,
  paginationTitle: string,
  saveErrorFlashMessageMessage: string,
  saveErrorFlashMessageTitle: string,
  saveSuccessFlashMessageMessage: string,
  saveSuccessFlashMessageTitle: string,
};

export type FormElementDefinition = BaseFormElementDefinition & Partial<RootFormElementDefinition>;

export interface AjaxRequests {
  saveForm?: JQueryXHR;
  renderFormDefinitionPage?: JQueryXHR;
}

export interface Endpoints {
  saveForm?: string;
  formPageRenderer?: string;
}

export type FinisherDefinition = {
  label: string
  iconIdentifier?: string,
  predefinedDefaults?: Record<string, unknown>,
};

export type FormElementPropertyValidatorDefinition = {
  errorMessage: string
};

type ValidatorDefinition = {
  label: string
  iconIdentifier?: string,
};

export type FormEditorDefinitions = {
  finishers: Record<string, FinisherDefinition>,
  formElementPropertyValidators: Record<string, FormElementPropertyValidatorDefinition>
  formElements: Record<string, FormElementDefinition>
  validators: Record<string, ValidatorDefinition>
};

type ModelData<T extends object> = T & {
  __parentRenderable: FormElement,
  __identifierPath: string,
  renderables: boolean | null | Array<Model<T, ModelData<T>>>
};

type PartialFormElementModelData = Partial<Omit<FormElementDefinition, 'renderables'>>;
type FormElementModelData = PartialFormElementModelData & ModelData<PartialFormElementModelData>;

export type FormElement = Model<PartialFormElementModelData, ModelData<PartialFormElementModelData>>;
export type RootFormElement = FormElement;

export type Validator = (formElement: FormElement, propertyPath: string) => string | undefined;

type ValidatorsConfig = string[];

type Validators = {
  [key: string]: Validator;
};

export type ValidationResults = string[];

export type ValidationResultsWithPath = Array<{
  propertyPath: string,
  validationResults: ValidationResults,
}>;

export type ValidationResultsRecursive = Array<{
  formElementIdentifierPath: string,
  validationResults: ValidationResultsWithPath,
}>;

type PropertyValidatorConfiguration = {
  propertyValidatorsMode: 'OR' | 'AND';
};

export type CollectionEntry = {
  identifier: string,
  editors?: Array<EditorConfiguration>,
};

export type Collection = CollectionEntry[];

/* Factory */

// @todo
export type CollectionElementConfiguration = {
  identifier: string,
};

// @todo
export type PropertyCollectionElement = CollectionElementConfiguration;

export interface ApplicationState {
  formDefinition?: RootFormElement;
  currentlySelectedPageIndex?: number;
  currentlySelectedFormElementIdentifierPath?: string;
  propertyValidationServiceRegisteredValidators?: Record<string, Record<string, {validators: ValidatorsConfig, configuration: PropertyValidatorConfiguration}>>
}
type ApplicationStateType = keyof ApplicationState;

type PublisherSubscriberTopic = keyof PublisherSubscriberTopicArgumentsMap;

interface PublisherSubscriberFunction<T extends PublisherSubscriberTopic> {
  (topic: T, args: NoInfer<PublisherSubscriberTopicArgumentsMap[T]>): void;
}

interface PublisherSubscriberEntry<T extends PublisherSubscriberTopic> {
  token: string
  func: PublisherSubscriberFunction<T>,
}

type PublisherSubscriberTopics = {
  [T in PublisherSubscriberTopic]: PublisherSubscriberEntry<T>[];
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

export class Utility {

  public assert(test: boolean|(() => boolean), message: string, messageCode: number): void {
    assert(test, message, messageCode);
  }

  public isUndefinedOrNull(value: Array<unknown> | object | undefined | null | string | number | boolean | symbol): boolean {
    return value === undefined || value === null;
  }

  public isNonEmptyArray(value: Array<unknown> | object | undefined | null | string | number | boolean | symbol): boolean {
    return Array.isArray(value) && value.length > 0;
  }

  public isNonEmptyString(value: Array<unknown> | object | undefined | null | string | number | boolean | symbol): boolean {
    return typeof value === 'string' && value.length > 0;
  }

  public canBeInterpretedAsInteger(value: Array<unknown> | object | undefined | null | string | number | boolean | symbol): boolean {
    if (typeof value === 'number') {
      return true;
    }

    if (typeof value !== 'string') {
      return false;
    }

    const v: string = value;
    return (v as unknown as number * 1).toString() === v.toString() && v.toString().indexOf('.') === -1;
  }

  /**
   * @throws 1475412569
   * @throws 1475412570
   * @throws 1475415988
   * @throws 1475663210
   */
  public buildPropertyPath(
    propertyPath: string,
    collectionElementIdentifier: string,
    collectionName: string,
    formElement: FormElement,
    allowEmptyReturnValue?: boolean
  ): string {
    let newPropertyPath = '';

    allowEmptyReturnValue = !!allowEmptyReturnValue;
    if (this.isNonEmptyString(collectionElementIdentifier) || this.isNonEmptyString(collectionName)) {
      assert(this.isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475412569);
      assert(this.isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475412570);
      newPropertyPath = collectionName + '.' + repository.getIndexFromPropertyCollectionElementByIdentifier(collectionElementIdentifier, collectionName, formElement);
    } else {
      newPropertyPath = '';
    }

    if (!this.isUndefinedOrNull(propertyPath)) {
      assert(this.isNonEmptyString(propertyPath), 'Invalid parameter "propertyPath"', 1475415988);
      if (this.isNonEmptyString(newPropertyPath)) {
        newPropertyPath = newPropertyPath + '.' + propertyPath;
      } else {
        newPropertyPath = propertyPath;
      }
    }

    if (!allowEmptyReturnValue) {
      assert(this.isNonEmptyString(newPropertyPath), 'The property path could not be resolved', 1475663210);
    }
    return newPropertyPath;
  }

  /**
   * @throws 1475377782
   */
  public convertToSimpleObject(formElement: object): object {
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475377782);

    const simpleObject: Record<string, unknown> & { renderables? : Array<object> } = {};
    const objectData = ('getObjectData' in formElement && typeof formElement.getObjectData === 'function') ? formElement.getObjectData() : formElement;
    const childFormElements = objectData.renderables;
    delete objectData.renderables;

    for (const [key, value] of Object.entries(objectData)) {
      if (key.match(/^__/)) {
        continue;
      }

      if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
        simpleObject[key] = this.convertToSimpleObject(value);
      } else if ('function' !== $.type(value) && 'undefined' !== $.type(value)) {
        simpleObject[key] = value;
      }
    }

    if ('array' === $.type(childFormElements)) {
      simpleObject.renderables = [];
      for (let i = 0, len = childFormElements.length; i < len; ++i) {
        simpleObject.renderables.push(this.convertToSimpleObject(childFormElements[i]));
      }
    }

    return simpleObject;
  }
}

export class PropertyValidationService {
  private validators: Validators = {};

  /**
   * @throws 1475661025
   * @throws 1475661026
   * @throws 1479238074
   */
  public addValidatorIdentifiersToFormElementProperty(
    formElement: FormElement,
    validators: ValidatorsConfig,
    propertyPath: string,
    collectionElementIdentifier: string,
    collectionName: string,
    configuration: PropertyValidatorConfiguration
  ): void {
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475661025);
    assert('array' === $.type(validators), 'Invalid parameter "validators"', 1475661026);
    assert('array' === $.type(validators), 'Invalid parameter "validators"', 1479238074);

    const formElementIdentifierPath = formElement.get('__identifierPath');
    propertyPath = utility.buildPropertyPath(propertyPath, collectionElementIdentifier, collectionName, formElement);

    const propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
    if (utility.isUndefinedOrNull(propertyValidationServiceRegisteredValidators[formElementIdentifierPath])) {
      propertyValidationServiceRegisteredValidators[formElementIdentifierPath] = {};
    }
    if (utility.isUndefinedOrNull(propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath])) {
      propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath] = {
        validators: <ValidatorsConfig>[],
        configuration: configuration
      };
    }
    for (const validator of validators) {
      if (propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath].validators.indexOf(validator) === -1) {
        propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath].validators.push(validator);
      }
    }
    getApplicationStateStack().setCurrentState('propertyValidationServiceRegisteredValidators', propertyValidationServiceRegisteredValidators);
  }

  /**
   * @throws 1475700618
   * @throws 1475706896
   */
  public removeValidatorIdentifiersFromFormElementProperty(
    formElement: FormElement,
    propertyPath: string
  ): void {
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475700618);
    assert(utility.isNonEmptyString(propertyPath), 'Invalid parameter "propertyPath"', 1475706896);

    const formElementIdentifierPath = formElement.get('__identifierPath');

    const registeredValidators: {[key: string]: {validators: ValidatorsConfig, configuration: PropertyValidatorConfiguration}} = {};
    const propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
    if (formElementIdentifierPath in propertyValidationServiceRegisteredValidators) {
      for (const registeredPropertyPath of Object.keys(propertyValidationServiceRegisteredValidators[formElementIdentifierPath] || {})) {
        if (registeredPropertyPath.indexOf(propertyPath) > -1) {
          continue;
        }
        registeredValidators[registeredPropertyPath] = propertyValidationServiceRegisteredValidators[formElementIdentifierPath][registeredPropertyPath];
      }
    }
    propertyValidationServiceRegisteredValidators[formElementIdentifierPath] = registeredValidators;
    getApplicationStateStack().setCurrentState('propertyValidationServiceRegisteredValidators', propertyValidationServiceRegisteredValidators);
  }

  /**
   * @throws 1475668189
   */
  public removeAllValidatorIdentifiersFromFormElement(formElement: FormElement): void {
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475668189);

    const registeredValidators: Record<string, {[key: string]: {validators: ValidatorsConfig, configuration: PropertyValidatorConfiguration}}> = {};
    const propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
    for (const formElementIdentifierPath of Object.keys(propertyValidationServiceRegisteredValidators || {})) {
      if (
        formElementIdentifierPath === formElement.get('__identifierPath')
        || formElementIdentifierPath.indexOf(formElement.get('__identifierPath') + '/') > -1
      ) {
        continue;
      }
      registeredValidators[formElementIdentifierPath] = propertyValidationServiceRegisteredValidators[formElementIdentifierPath];
    }
    getApplicationStateStack().setCurrentState('propertyValidationServiceRegisteredValidators', registeredValidators);
  }

  /**
   * @throws 1475669143
   * @throws 1475669144
   * @throws 1475669145
   */
  public addValidator(validatorIdentifier: string, func: Validator): void {
    assert(utility.isNonEmptyString(validatorIdentifier), 'Invalid parameter "validatorIdentifier"', 1475669143);
    assert('function' === $.type(func), 'Invalid parameter "func"', 1475669144);
    assert('function' !== $.type(this.validators[validatorIdentifier]), 'The validator "' + validatorIdentifier + '" is already registered', 1475669145);

    this.validators[validatorIdentifier] = func;
  }

  /**
   * @throws 1475676517
   * @throws 1475676518
   */
  public validateFormElementProperty(
    formElement: FormElement,
    propertyPath: string
  ): ValidationResults {
    let configuration;
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475676517);
    assert(utility.isNonEmptyString(propertyPath), 'Invalid parameter "propertyPath"', 1475676518);

    const formElementIdentifierPath = formElement.get('__identifierPath');

    const validationResults: ValidationResults = [];
    const propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
    configuration = {
      propertyValidatorsMode: 'AND'
    };

    if (
      !utility.isUndefinedOrNull(propertyValidationServiceRegisteredValidators[formElementIdentifierPath])
      && 'object' === $.type(propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath])
      && 'array' === $.type(propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath].validators)
    ) {
      configuration = propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath].configuration;
      for (let i = 0, len = propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath].validators.length; i < len; ++i) {
        const validatorIdentifier = propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath].validators[i];
        if ('function' !== $.type(this.validators[validatorIdentifier])) {
          continue;
        }
        const validationResult = this.validators[validatorIdentifier](formElement, propertyPath);

        if (utility.isNonEmptyString(validationResult)) {
          validationResults.push(validationResult);
        }
      }
    }

    if (
      validationResults.length > 0
      && configuration.propertyValidatorsMode === 'OR'
      && validationResults.length !== propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath].validators.length
    ) {
      return [];
    }

    return validationResults;
  }

  /**
   * @throws 1475749668
   */
  public validateFormElement(formElement: FormElement): ValidationResultsWithPath {
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475749668);

    const formElementIdentifierPath = formElement.get('__identifierPath');
    const validationResults: ValidationResultsWithPath = [];
    const propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
    if (!utility.isUndefinedOrNull(propertyValidationServiceRegisteredValidators[formElementIdentifierPath])) {
      for (const registeredPropertyPath of Object.keys(propertyValidationServiceRegisteredValidators[formElementIdentifierPath])) {
        validationResults.push({
          propertyPath: registeredPropertyPath,
          validationResults: this.validateFormElementProperty(formElement, registeredPropertyPath)
        });
      }
    }
    return validationResults;
  }

  /**
   * @throws 1478613477
   */
  public validationResultsHasErrors(
    validationResults: ValidationResultsRecursive
  ): boolean {
    assert('array' === $.type(validationResults), 'Invalid parameter "validationResults"', 1478613477);

    for (let i = 0, len = validationResults.length; i < len; ++i) {
      for (let j = 0, len2 = validationResults[i].validationResults.length; j < len2; ++j) {
        if (
          validationResults[i].validationResults[j].validationResults
          && validationResults[i].validationResults[j].validationResults.length > 0
        ) {
          return true;
        }
      }
    }
    return false;
  }

  /**
   * @throws 1475749668
   */
  public validateFormElementRecursive(
    formElement: FormElement,
    returnAfterFirstMatch: boolean,
    validationResults?: ValidationResultsRecursive
  ): ValidationResultsRecursive {
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475756764);
    returnAfterFirstMatch = !!returnAfterFirstMatch;

    validationResults = validationResults || <ValidationResultsRecursive>[];
    validationResults.push({
      formElementIdentifierPath: formElement.get('__identifierPath'),
      validationResults: this.validateFormElement(formElement)
    });

    if (returnAfterFirstMatch && this.validationResultsHasErrors(validationResults)) {
      return validationResults;
    }

    const formElements = formElement.get('renderables');
    if ('array' === $.type(formElements)) {
      for (let i = 0, len = formElements.length; i < len; ++i) {
        this.validateFormElementRecursive(formElements[i], returnAfterFirstMatch, validationResults);
        if (returnAfterFirstMatch && this.validationResultsHasErrors(validationResults)) {
          return validationResults;
        }
      }
    }

    return validationResults;
  }

  /**
   * @throws 1475707334
   */
  public addValidatorIdentifiersFromFormElementPropertyCollections(
    formElement: FormElement
  ): void {
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475707334);

    const formElementTypeDefinition = repository.getFormEditorDefinition('formElements', formElement.get('type'));

    if (!utility.isUndefinedOrNull(formElementTypeDefinition.propertyCollections)) {
      for (const collectionName of Object.keys(formElementTypeDefinition.propertyCollections)) {
        if (
          !Array.isArray(formElementTypeDefinition.propertyCollections[collectionName])
        ) {
          continue;
        }
        for (let i = 0, len1 = formElementTypeDefinition.propertyCollections[collectionName].length; i < len1; ++i) {
          if (
            'array' !== $.type(formElementTypeDefinition.propertyCollections[collectionName][i].editors)
            || repository.getIndexFromPropertyCollectionElementByIdentifier(formElementTypeDefinition.propertyCollections[collectionName][i].identifier, collectionName, formElement) === -1
          ) {
            continue;
          }
          for (let j = 0, len2 = formElementTypeDefinition.propertyCollections[collectionName][i].editors.length; j < len2; ++j) {
            if ('array' !== $.type(formElementTypeDefinition.propertyCollections[collectionName][i].editors[j].propertyValidators)) {
              continue;
            }
            const propertyValidatorConfiguration: PropertyValidatorConfiguration = {
              propertyValidatorsMode: 'AND'
            };
            if (
              !utility.isUndefinedOrNull(formElementTypeDefinition.propertyCollections[collectionName][i].editors[j].propertyValidatorsMode)
              && formElementTypeDefinition.propertyCollections[collectionName][i].editors[j].propertyValidatorsMode === 'OR'
            ) {
              propertyValidatorConfiguration.propertyValidatorsMode = 'OR';
            }
            this.addValidatorIdentifiersToFormElementProperty(
              formElement,
              formElementTypeDefinition.propertyCollections[collectionName][i].editors[j].propertyValidators,
              formElementTypeDefinition.propertyCollections[collectionName][i].editors[j].propertyPath,
              formElementTypeDefinition.propertyCollections[collectionName][i].identifier,
              collectionName,
              propertyValidatorConfiguration
            );
          }
        }
      }
    }
  }
}

/**
 * Implements the "Publish/Subscribe Pattern"
 * @credits Addy Osmani https://addyosmani.com/resources/essentialjsdesignpatterns/book/#highlighter_634280
 */
export class PublisherSubscriber {

  private topics: PublisherSubscriberTopics = <PublisherSubscriberTopics>{};

  private subscriberUid: number = -1;

  /**
   * @throws 1475358066
   */
  public publish<T extends PublisherSubscriberTopic>(
    topic: T,
    args?: NoInfer<PublisherSubscriberTopicArgumentsMap[T]>
  ): void {
    assert(utility.isNonEmptyString(topic), 'Invalid parameter "topic"', 1475358066);
    if (utility.isUndefinedOrNull(this.topics[topic])) {
      return;
    }

    const topicFunctions = this.topics[topic];
    for (const entry of topicFunctions) {
      entry.func(topic, args);
    }
  }

  /**
   * @throws 1475358067
   */
  public subscribe<T extends PublisherSubscriberTopic>(
    topic: T,
    func: NoInfer<PublisherSubscriberFunction<T>>
  ): string {
    assert(utility.isNonEmptyString(topic), 'Invalid parameter "topic"', 1475358067);
    assert('function' === $.type(func), 'Invalid parameter "func"', 1475411986);

    if (utility.isUndefinedOrNull(this.topics[topic])) {
      this.topics[topic] = <PublisherSubscriberTopics[T]>[];
    }

    const token = (++this.subscriberUid).toString();
    this.topics[topic].push({
      token: token,
      //func: func as PublisherSubscriberFunction<U, U>
      func: func
      //func: func as F
    });
    return token;
  }

  /**
   * @throws 1475358068
   */
  public unsubscribe(token: string): null | string {
    assert(utility.isNonEmptyString(token), 'Invalid parameter "token"', 1475358068);

    for (const tmp of Object.values(this.topics)) {
      const entries = <PublisherSubscriberEntry<PublisherSubscriberTopic>[]>tmp;
      for (let i = 0, len = entries.length; i < len; ++i) {
        if (entries[i].token === token) {
          entries.splice(i, 1);
          return token;
        }
      }
    }
    return null;
  }
}

/**
 * @throws 1474640022
 * @throws 1475358069
 * @throws 1475358070
 * @publish core/formElement/somePropertyChanged
 */
function extendModel<D extends object, T extends ModelData<D>>(
  modelToExtend: Model<D, T>,
  modelExtension: T | [] | Record<string, never>,
  pathPrefix: string,
  disablePublishersOnSet: boolean
): void {
  assert('object' === $.type(modelToExtend), 'Invalid parameter "modelToExtend"', 1475358069);
  assert('object' === $.type(modelExtension) || 'array' === $.type(modelExtension), 'Invalid parameter "modelExtension"', 1475358070);

  disablePublishersOnSet = !!disablePublishersOnSet;
  pathPrefix = pathPrefix || '';

  if ($.isEmptyObject(modelExtension)) {
    assert('' !== pathPrefix, 'Empty path is not allowed', 1474640022);
    modelToExtend.on(pathPrefix, 'core/formElement/somePropertyChanged');
    modelToExtend.set(pathPrefix, modelExtension, disablePublishersOnSet);
  } else {
    const _modelExtension = { ...modelExtension } as Record<string, T | [] | Record<string, never>>;
    for (const key of Object.keys(_modelExtension)) {
      const path = (pathPrefix === '') ? key : pathPrefix + '.' + key;

      modelToExtend.on(path, 'core/formElement/somePropertyChanged');

      if (_modelExtension[key] !== null && (typeof (_modelExtension[key]) === 'object' || Array.isArray(_modelExtension[key]))) {
        extendModel(modelToExtend, _modelExtension[key], path, disablePublishersOnSet);
      } else if (pathPrefix === 'properties.options') {
        modelToExtend.set(pathPrefix, modelExtension, disablePublishersOnSet);
      } else {
        modelToExtend.set(path, _modelExtension[key], disablePublishersOnSet);
      }
    }
  }
}

export class Model<D extends object, T extends ModelData<D>> {

  private readonly objectData: T = <T>{};

  private publisherTopics: {[key: string]: string[]} = {};

  /**
   * @throws 1475361755
   */
  public get(key: string): any | undefined {
    let firstPartOfPath: string;
    let obj: Record<string, unknown>;
    assert(utility.isNonEmptyString(key), 'Invalid parameter "key"', 1475361755);

    obj = this.objectData;
    while (key.indexOf('.') > 0) {
      firstPartOfPath = key.slice(0, key.indexOf('.'));
      key = key.slice(firstPartOfPath.length + 1);
      if (!(firstPartOfPath in obj)) {
        return undefined;
      }
      obj = obj[firstPartOfPath] as Record<string, unknown>;
    }

    return obj[key];
  }

  /**
   * @throws 1475361756
   * @publish mixed
   */
  public set(key: string, value: unknown, disablePublishersOnSet?: boolean): void {
    let path: string;
    let firstPartOfPath: string | number;
    let nextPartOfPath: string | number;
    let index: number;
    let obj: Record<string, unknown>;
    assert(utility.isNonEmptyString(key), 'Invalid parameter "key"', 1475361756);
    disablePublishersOnSet = !!disablePublishersOnSet;

    const oldValue = this.get(key);
    obj = this.objectData;
    path = key;

    while (path.indexOf('.') > 0) {
      firstPartOfPath = path.slice(0, path.indexOf('.'));
      path = path.slice(firstPartOfPath.length + 1);

      if ($.isNumeric(firstPartOfPath)) {
        firstPartOfPath = parseInt(firstPartOfPath, 10);
      }

      index = path.indexOf('.');
      nextPartOfPath = index === -1 ? path : path.slice(0, index);

      // initialize objects case they are undefined by looking up the type
      // of the next path segment, the target type is guessed(!), thus e.g.
      // "key" results in having an object, "123" results in having an array
      if ('undefined' === $.type(obj[firstPartOfPath])) {
        if ($.isNumeric(nextPartOfPath)) {
          obj[firstPartOfPath] = [];
        } else {
          obj[firstPartOfPath] = {};
        }
      // in case the previous guess was wrong, the initialized array
      // is converted to an object when a non-numeric path segment is found
      } else if (false === $.isNumeric(nextPartOfPath) && 'array' === $.type(obj[firstPartOfPath])) {
        obj[firstPartOfPath] = { ...(obj[firstPartOfPath] as Array<unknown>) };
      }
      obj = obj[firstPartOfPath] as Record<string, unknown>;
    }
    obj[path] = value;

    if (!utility.isUndefinedOrNull(this.publisherTopics[key]) && !disablePublishersOnSet) {
      for (let i = 0, len = this.publisherTopics[key].length; i < len; ++i) {
        publisherSubscriber.publish<'core/formElement/somePropertyChanged'>(
          this.publisherTopics[key][i] as 'core/formElement/somePropertyChanged',
          [key, value, oldValue, this.objectData.__identifierPath]
        );
      }
    }
  }

  /**
   * @throws 1489321637
   * @throws 1489319753
   * @publish mixed
   */
  public unset(key: string, disablePublishersOnSet?: boolean): void {
    let parentPropertyData, parentPropertyPath, propertyToRemove;
    assert(utility.isNonEmptyString(key), 'Invalid parameter "key"', 1489321637);
    disablePublishersOnSet = !!disablePublishersOnSet;

    const oldValue = this.get(key);

    if (key.indexOf('.') > 0) {
      parentPropertyPath = key.split('.');
      propertyToRemove = parentPropertyPath.pop();
      parentPropertyPath = parentPropertyPath.join('.');
      parentPropertyData = this.get(parentPropertyPath);
      if (typeof parentPropertyData !== 'undefined') {
        delete parentPropertyData[propertyToRemove];
      }
    } else {
      assert(false, 'remove toplevel properties is not supported', 1489319753);
    }

    if (!utility.isUndefinedOrNull(this.publisherTopics[key]) && !disablePublishersOnSet) {
      for (let i = 0, len = this.publisherTopics[key].length; i < len; ++i) {
        publisherSubscriber.publish<'core/formElement/somePropertyChanged'>(
          this.publisherTopics[key][i] as 'core/formElement/somePropertyChanged',
          [key, undefined, oldValue, this.objectData.__identifierPath]
        );
      }
    }
  }

  /**
   * @throws 1475361757
   * @throws 1475361758
   */
  public on(key: string, topicName: string): void {
    assert(utility.isNonEmptyString(key), 'Invalid parameter "key"', 1475361757);
    assert(utility.isNonEmptyString(topicName), 'Invalid parameter "topicName"', 1475361758);

    if ('array' !== $.type(this.publisherTopics[key])) {
      this.publisherTopics[key] = [];
    }
    if (this.publisherTopics[key].indexOf(topicName) === -1) {
      this.publisherTopics[key].push(topicName);
    }
  }

  /**
   * @throws 1475361759
   * @throws 1475361760
   */
  public off(key: string, topicName: string): void {
    assert(utility.isNonEmptyString(key), 'Invalid parameter "key"', 1475361759);
    assert(utility.isNonEmptyString(topicName), 'Invalid parameter "topicName"', 1475361760);

    if ('array' === $.type(this.publisherTopics[key])) {
      this.publisherTopics[key] = this.publisherTopics[key].filter(
        (currentTopicName) => topicName !== currentTopicName
      );
    }
  }

  public getObjectData(): T {
    // Return dereferenced object
    return $.extend(true, {}, this.objectData);
  }

  public toString(): string {
    const objectData = this.getObjectData();

    const { renderables, __parentRenderable, ...restObjectData } = objectData;
    const childFormElements = renderables || null;

    let parentRenderable: string = null;
    if (!utility.isUndefinedOrNull(__parentRenderable)) {
      parentRenderable = __parentRenderable.getObjectData().__identifierPath + ' (filtered)';
    }

    const myObjectData: Omit<T, 'renderables' | '__parentRenderable'> & { renderables?: object[], __parentRenderable?: string} = restObjectData;

    if (parentRenderable !== null) {
      myObjectData.__parentRenderable = parentRenderable;
    }

    if (childFormElements !== null && Array.isArray(childFormElements)) {
      const renderables: object[] = [];
      for (let i = 0, len = childFormElements.length; i < len; ++i) {
        const childFormElement = childFormElements[i];
        renderables.push(JSON.parse(childFormElement.toString()));
      }
      myObjectData.renderables = renderables;
    }

    return JSON.stringify(myObjectData, null, 2);
  }

  public clone(): Model<D, T> {
    const objectData = this.getObjectData();
    const childFormElements = objectData.renderables || null;
    delete objectData.renderables;
    delete objectData.__parentRenderable;
    objectData.renderables = (childFormElements) ? true : null;

    const newModel = new Model<D, T>();
    extendModel(newModel, objectData, '', true);

    if (null !== childFormElements && Array.isArray(childFormElements)) {
      const newRenderables = [];
      for (let i = 0, len = childFormElements.length; i < len; ++i) {
        let childFormElement = childFormElements[i];

        childFormElement = childFormElement.clone();
        childFormElement.set('__parentRenderable', newModel, true);
        newRenderables.push(childFormElement);
      }
      newModel.set('renderables', newRenderables, true);
    }

    return newModel;
  }
}

function createModel<D extends object, T extends ModelData<D>>(modelExtension: T): Model<D, T> {
  modelExtension = modelExtension || <T>{};

  const newModel = new Model<D, T>();
  extendModel(newModel, modelExtension, '', true);

  return newModel;
}

export class Repository {

  public formEditorDefinitions: FormEditorDefinitions;

  /**
   * @throws 1475364394
   */
  public setFormEditorDefinitions(formEditorDefinitions: FormEditorDefinitions): void {
    assert('object' === $.type(formEditorDefinitions), 'Invalid parameter "formEditorDefinitions"', 1475364394);

    for (const _key1 of Object.keys(formEditorDefinitions)) {
      const key1 = _key1 as keyof FormEditorDefinitions;
      if (formEditorDefinitions[key1] !== null && typeof formEditorDefinitions[key1] !== 'object') {
        continue;
      }
      for (const key2 of Object.keys(formEditorDefinitions[key1])) {
        if (
          formEditorDefinitions[key1][key2] === null ||
          typeof formEditorDefinitions[key1][key2] !== 'object'
        ) {
          formEditorDefinitions[key1][key2] = {} as (FormElementDefinition | FinisherDefinition | FormElementPropertyValidatorDefinition | ValidatorDefinition);
        }
      }
    }
    this.formEditorDefinitions = formEditorDefinitions;
  }

  /**
   * @throws 1475364952
   * @throws 1475364953
   */
  public getFormEditorDefinition<D extends keyof FormEditorDefinitions, S extends keyof FormEditorDefinitions[D]>(
    definitionName: D,
    subject: S
  ): FormEditorDefinitions[D][S] {
    assert(utility.isNonEmptyString(definitionName), 'Invalid parameter "definitionName"', 1475364952);
    assert(utility.isNonEmptyString(subject), 'Invalid parameter "subject"', 1475364953);
    // Return dereferenced object
    return $.extend(true, {}, this.formEditorDefinitions[definitionName][subject]);
  }

  public getRootFormElement(): RootFormElement {
    return getApplicationStateStack().getCurrentState('formDefinition');
  }

  /**
   * @throws 1475436224
   * @throws 1475364956
   */
  public addFormElement(
    formElement: FormElement,
    referenceFormElement: FormElement,
    registerPropertyValidators: boolean,
    disablePublishersOnSet: boolean
  ): FormElement {
    let enclosingCompositeFormElement, parentFormElementsArray, referenceFormElementElements;
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475436224);
    assert('object' === $.type(referenceFormElement), 'Invalid parameter "referenceFormElement"', 1475364956);

    if (utility.isUndefinedOrNull(disablePublishersOnSet)) {
      disablePublishersOnSet = true;
    }
    disablePublishersOnSet = !!disablePublishersOnSet;

    registerPropertyValidators = !!registerPropertyValidators;
    const formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));
    const referenceFormElementTypeDefinition = this.getFormEditorDefinition('formElements', referenceFormElement.get('type'));

    // formElement != Page / SummaryPage && referenceFormElement == Page / Fieldset / GridRow
    if (!formElementTypeDefinition._isTopLevelFormElement && referenceFormElementTypeDefinition._isCompositeFormElement) {
      if ('array' !== $.type(referenceFormElement.get('renderables'))) {
        referenceFormElement.set('renderables', [], disablePublishersOnSet);
      }

      formElement.set('__parentRenderable', referenceFormElement, disablePublishersOnSet);
      formElement.set('__identifierPath', referenceFormElement.get('__identifierPath') + '/' + formElement.get('identifier'), disablePublishersOnSet);
      referenceFormElement.get('renderables').push(formElement);
    } else {
      // referenceFormElement == root form element
      if (referenceFormElement.get('__identifierPath') === getApplicationStateStack().getCurrentState('formDefinition').get('__identifierPath')) {
        referenceFormElementElements = referenceFormElement.get('renderables');
        // referenceFormElement = last page
        referenceFormElement = referenceFormElementElements[referenceFormElementElements.length - 1];
        // if formElement == Page / SummaryPage && referenceFormElement != Page / SummaryPage
      } else if (formElementTypeDefinition._isTopLevelFormElement && !referenceFormElementTypeDefinition._isTopLevelFormElement) {
        // referenceFormElement = parent Page
        referenceFormElement = this.findEnclosingCompositeFormElementWhichIsOnTopLevel(referenceFormElement);
        // formElement == Page / SummaryPage / Fieldset / GridRow
      } else if (formElementTypeDefinition._isCompositeFormElement) {
        enclosingCompositeFormElement = this.findEnclosingCompositeFormElementWhichIsNotOnTopLevel(referenceFormElement);
        if (enclosingCompositeFormElement) {
          // referenceFormElement = parent Fieldset / GridRow
          referenceFormElement = enclosingCompositeFormElement;
        }
      }

      formElement.set('__parentRenderable', referenceFormElement.get('__parentRenderable'), disablePublishersOnSet);
      formElement.set('__identifierPath', referenceFormElement.get('__parentRenderable').get('__identifierPath') + '/' + formElement.get('identifier'), disablePublishersOnSet);
      parentFormElementsArray = referenceFormElement.get('__parentRenderable').get('renderables');
      parentFormElementsArray.splice(parentFormElementsArray.indexOf(referenceFormElement) + 1, 0, formElement);
    }

    if (registerPropertyValidators) {
      if ('array' === $.type(formElementTypeDefinition.editors)) {
        for (let i = 0, len1 = formElementTypeDefinition.editors.length; i < len1; ++i) {
          if ('array' !== $.type(formElementTypeDefinition.editors[i].propertyValidators)) {
            continue;
          }

          const propertyValidatorConfiguration: PropertyValidatorConfiguration = {
            propertyValidatorsMode: 'AND'
          };
          if (
            !utility.isUndefinedOrNull(formElementTypeDefinition.editors[i].propertyValidatorsMode)
            && formElementTypeDefinition.editors[i].propertyValidatorsMode === 'OR'
          ) {
            propertyValidatorConfiguration.propertyValidatorsMode = 'OR';
          }

          propertyValidationService.addValidatorIdentifiersToFormElementProperty(
            formElement,
            formElementTypeDefinition.editors[i].propertyValidators,
            formElementTypeDefinition.editors[i].propertyPath,
            undefined,
            undefined,
            propertyValidatorConfiguration
          );
        }
      }
    }

    return formElement;
  }

  /**
   * @throws 1472553024
   * @throws 1475364957
   */
  public removeFormElement(
    formElement: FormElement,
    removeRegisteredPropertyValidators: boolean,
    disablePublishersOnSet?: boolean
  ): void {
    if (utility.isUndefinedOrNull(disablePublishersOnSet)) {
      disablePublishersOnSet = true;
    }
    disablePublishersOnSet = !!disablePublishersOnSet;
    removeRegisteredPropertyValidators = !!removeRegisteredPropertyValidators;

    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364957);
    assert('object' === $.type(formElement.get('__parentRenderable')), 'Removing the root element is not allowed', 1472553024);

    const parentFormElementElements = formElement.get('__parentRenderable').get('renderables');
    parentFormElementElements.splice(parentFormElementElements.indexOf(formElement), 1);
    formElement.get('__parentRenderable').set('renderables', parentFormElementElements, disablePublishersOnSet);

    if (removeRegisteredPropertyValidators) {
      propertyValidationService.removeAllValidatorIdentifiersFromFormElement(formElement);
    }
  }

  /**
   * @throws 1475364958
   * @throws 1475364959
   * @throws 1475364960
   * @throws 1475364961
   * @throws 1475364962
   * @throws 1476993731
   * @throws 1476993732
   */
  public moveFormElement(
    formElementToMove: FormElement,
    position: string,
    referenceFormElement: FormElement,
    disablePublishersOnSet: boolean
  ): FormElement {
    let referenceFormElementParentElements,
      referenceFormElementElements, referenceFormElementIndex;
    assert('object' === $.type(formElementToMove), 'Invalid parameter "formElementToMove"', 1475364958);
    assert('after' === position || 'before' === position || 'inside' === position, 'Invalid position "' + position + '"', 1475364959);
    assert('object' === $.type(referenceFormElement), 'Invalid parameter "referenceFormElement"', 1475364960);

    if (utility.isUndefinedOrNull(disablePublishersOnSet)) {
      disablePublishersOnSet = true;
    }
    disablePublishersOnSet = !!disablePublishersOnSet;

    const formElementToMoveTypeDefinition = this.getFormEditorDefinition('formElements', formElementToMove.get('type'));
    const referenceFormElementTypeDefinition = this.getFormEditorDefinition('formElements', referenceFormElement.get('type'));

    this.removeFormElement(formElementToMove, false);
    const reSetIdentifierPath = (formElement: FormElement, pathPrefix: string): void => {
      assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364961);
      assert(utility.isNonEmptyString(pathPrefix), 'Invalid parameter "pathPrefix"', 1475364962);

      const oldIdentifierPath = formElement.get('__identifierPath');
      const newIdentifierPath = pathPrefix + '/' + formElement.get('identifier');

      const propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
      if (!utility.isUndefinedOrNull(propertyValidationServiceRegisteredValidators[oldIdentifierPath])) {
        propertyValidationServiceRegisteredValidators[newIdentifierPath] = propertyValidationServiceRegisteredValidators[oldIdentifierPath];
        delete propertyValidationServiceRegisteredValidators[oldIdentifierPath];
      }
      getApplicationStateStack().setCurrentState('propertyValidationServiceRegisteredValidators', propertyValidationServiceRegisteredValidators);

      formElement.set('__identifierPath', newIdentifierPath, disablePublishersOnSet);
      const formElements = formElement.get('renderables');
      if ('array' === $.type(formElements)) {
        for (let i = 0, len = formElements.length; i < len; ++i) {
          reSetIdentifierPath(formElements[i], formElement.get('__identifierPath'));
        }
      }
    };

    /**
     * This is true on:
     * * Drag a Element on a Page Element (tree)
     * * Drag a Element on a Section Element (tree)
     */
    if (position === 'inside') {
      // formElementToMove == Page / SummaryPage
      assert(!formElementToMoveTypeDefinition._isTopLevelFormElement, 'This move is not allowed', 1476993731);
      // referenceFormElement != Page / Fieldset / GridRow
      assert(referenceFormElementTypeDefinition._isCompositeFormElement, 'This move is not allowed', 1476993732);

      formElementToMove.set('__parentRenderable', referenceFormElement, disablePublishersOnSet);
      reSetIdentifierPath(formElementToMove, referenceFormElement.get('__identifierPath'));

      referenceFormElementElements = referenceFormElement.get('renderables');
      if (utility.isUndefinedOrNull(referenceFormElementElements)) {
        referenceFormElementElements = [];
      }
      referenceFormElementElements.splice(0, 0, formElementToMove);
      referenceFormElement.set('renderables', referenceFormElementElements, disablePublishersOnSet);
    } else {
      /**
       * This is true on:
       * * Drag a Page before another Page (tree)
       * * Drag a Page after another Page (tree)
       */
      if (formElementToMoveTypeDefinition._isTopLevelFormElement && referenceFormElementTypeDefinition._isTopLevelFormElement) {
        referenceFormElementParentElements = referenceFormElement.get('__parentRenderable').get('renderables');
        referenceFormElementIndex = referenceFormElementParentElements.indexOf(referenceFormElement);

        if (position === 'after') {
          referenceFormElementParentElements.splice(referenceFormElementIndex + 1, 0, formElementToMove);
        } else {
          referenceFormElementParentElements.splice(referenceFormElementIndex, 0, formElementToMove);
        }

        referenceFormElement.get('__parentRenderable').set('renderables', referenceFormElementParentElements, disablePublishersOnSet);
      } else {
        /**
         * This is true on:
         * * Drag a Element before another Element within the same level (tree)
         * * Drag a Element after another Element within the same level (tree)
         * * Drag a Element before another Element (stage)
         * * Drag a Element after another Element (stage)
         */
        if (formElementToMove.get('__parentRenderable').get('identifier') === referenceFormElement.get('__parentRenderable').get('identifier')) {
          referenceFormElementParentElements = referenceFormElement.get('__parentRenderable').get('renderables');
          referenceFormElementIndex = referenceFormElementParentElements.indexOf(referenceFormElement);
        } else {
          /**
           * This is true on:
           * * Drag a Element before an Element on another page (tree / stage)
           * * Drag a Element after an Element on another page (tree / stage)
           */
          formElementToMove.set('__parentRenderable', referenceFormElement.get('__parentRenderable'), disablePublishersOnSet);
          reSetIdentifierPath(formElementToMove, referenceFormElement.get('__parentRenderable').get('__identifierPath'));

          referenceFormElementParentElements = referenceFormElement.get('__parentRenderable').get('renderables');
          referenceFormElementIndex = referenceFormElementParentElements.indexOf(referenceFormElement);
        }

        if (position === 'after') {
          referenceFormElementParentElements.splice(referenceFormElementIndex + 1, 0, formElementToMove);
        } else {
          referenceFormElementParentElements.splice(referenceFormElementIndex, 0, formElementToMove);
        }

        referenceFormElement.get('__parentRenderable').set('renderables', referenceFormElementParentElements, disablePublishersOnSet);
      }
    }

    return formElementToMove;
  }

  /**
   * @throws 1475364963
   */
  public getIndexForEnclosingCompositeFormElementWhichIsOnTopLevelForFormElement(
    formElement: FormElement
  ): number {
    let enclosingCompositeFormElementWhichIsOnTopLevel;
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364963);

    const formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));

    if (formElementTypeDefinition._isTopLevelFormElement && formElementTypeDefinition._isCompositeFormElement) {
      enclosingCompositeFormElementWhichIsOnTopLevel = formElement;
    } else if (formElement.get('__identifierPath') === getApplicationStateStack().getCurrentState('formDefinition').get('__identifierPath')) {
      enclosingCompositeFormElementWhichIsOnTopLevel = getApplicationStateStack().getCurrentState('formDefinition').get('renderables')[0];
    } else {
      enclosingCompositeFormElementWhichIsOnTopLevel = this.findEnclosingCompositeFormElementWhichIsOnTopLevel(formElement);
    }
    return enclosingCompositeFormElementWhichIsOnTopLevel.get('__parentRenderable').get('renderables').indexOf(enclosingCompositeFormElementWhichIsOnTopLevel);
  }

  /**
   * @throws 1472556223
   * @throws 1475364964
   */
  public findEnclosingCompositeFormElementWhichIsOnTopLevel(
    formElement: FormElement
  ): FormElement {
    let formElementTypeDefinition;
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364964);
    assert('object' === $.type(formElement.get('__parentRenderable')), 'The root element is never encloused by anything', 1472556223);

    formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));
    while (!formElementTypeDefinition._isTopLevelFormElement) {
      formElement = formElement.get('__parentRenderable');
      formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));
    }

    return formElement;
  }

  /**
   * @throws 1490520271
   */
  public findEnclosingGridRowFormElement(
    formElement: FormElement
  ): FormElement | null {
    let formElementTypeDefinition;
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1490520271);

    formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));
    while (!formElementTypeDefinition._isGridRowFormElement) {
      if (formElementTypeDefinition._isTopLevelFormElement) {
        return null;
      }
      formElement = formElement.get('__parentRenderable');
      formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));
    }
    if (formElementTypeDefinition._isTopLevelFormElement) {
      return null;
    }
    return formElement;
  }

  /**
   * @throws 1475364965
   */
  public findEnclosingCompositeFormElementWhichIsNotOnTopLevel(
    formElement: FormElement
  ): FormElement | null {
    let formElementTypeDefinition;
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364965);

    formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));
    while (!formElementTypeDefinition._isCompositeFormElement) {
      if (formElementTypeDefinition._isTopLevelFormElement) {
        return null;
      }
      formElement = formElement.get('__parentRenderable');
      formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));
    }
    if (formElementTypeDefinition._isTopLevelFormElement) {
      return null;
    }
    return formElement;
  }

  public getNonCompositeNonToplevelFormElements(): FormElement[] {
    const nonCompositeNonToplevelFormElements: FormElement[] = [];
    const collect = (formElement: FormElement): void => {
      assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364961);

      const formElementTypeDefinition = this.getFormEditorDefinition('formElements', formElement.get('type'));

      if (!formElementTypeDefinition._isTopLevelFormElement && !formElementTypeDefinition._isCompositeFormElement) {
        nonCompositeNonToplevelFormElements.push(formElement);
      }

      const formElements = formElement.get('renderables');
      if ('array' === $.type(formElements)) {
        for (let i = 0, len = formElements.length; i < len; ++i) {
          collect(formElements[i]);
        }
      }
    };

    collect(this.getRootFormElement());
    return nonCompositeNonToplevelFormElements;
  }

  /**
   * @throws 1475364966
   */
  public isFormElementIdentifierUsed(identifier: string): boolean {
    let identifierFound: boolean;
    assert(utility.isNonEmptyString(identifier), 'Invalid parameter "identifier"', 1475364966);

    const checkIdentifier = (formElement: FormElement): void => {
      let formElements;

      if (formElement.get('identifier') === identifier) {
        identifierFound = true;
      }

      if (!identifierFound) {
        formElements = formElement.get('renderables');
        if ('array' === $.type(formElements)) {
          for (let i = 0, len = formElements.length; i < len; ++i) {
            checkIdentifier(formElements[i]);
            if (identifierFound) {
              break;
            }
          }
        }
      }
    };

    checkIdentifier(getApplicationStateStack().getCurrentState('formDefinition'));
    return identifierFound;
  }

  /**
   * @throws 1475373676
   */
  public getNextFreeFormElementIdentifier(formElementType: string): string {
    let i;
    assert(utility.isNonEmptyString(formElementType), 'Invalid parameter "formElementType"', 1475373676);

    const prefix = formElementType.toLowerCase().replace(/[^a-z0-9]/g, '-') + '-';
    i = 1;
    while (this.isFormElementIdentifierUsed(prefix + i)) {
      i++;
    }
    return prefix + i;
  }

  /**
   * @throws 1472424333
   * @throws 1472424334
   * @throws 1472424330
   * @throws 1475373677
   */
  public findFormElementByIdentifierPath(identifierPath: string): FormElement {
    let obj, formElements;

    assert(utility.isNonEmptyString(identifierPath), 'Invalid parameter "identifierPath"', 1475373677);

    let formElement: FormElement = getApplicationStateStack().getCurrentState('formDefinition');
    const pathParts = identifierPath.split('/');
    const pathPartsLength = pathParts.length;

    for (let i = 0; i < pathPartsLength; ++i) {
      const key = pathParts[i];
      if (i === 0 || i === pathPartsLength) {
        assert(key === formElement.get('identifier'), '"' + key + '" does not exist in path "' + identifierPath + '"', 1472424333);
        continue;
      }

      formElements = formElement.get('renderables');
      if (Array.isArray(formElements)) {
        obj = null;
        for (let j = 0, len = formElements.length; j < len; ++j) {
          if (key === formElements[j].get('identifier')) {
            obj = formElements[j];
            break;
          }
        }

        assert('null' !== $.type(obj), 'Could not find form element "' + key + '" in path "' + identifierPath + '"', 1472424334);
        formElement = obj;
      } else {
        assert(false, 'No form elements found', 1472424330);
      }
    }
    return formElement;
  }

  public findFormElement(formElement: FormElement | string): FormElement {
    if (typeof formElement === 'object') {
      formElement = <string>formElement.get('__identifierPath');
    }
    return this.findFormElementByIdentifierPath(formElement);
  }

  /**
   * @throws 1475375281
   * @throws 1475375282
   */
  public findCollectionElementByIdentifierPath(
    collectionElementIdentifier: string,
    collection: Collection
  ): undefined | CollectionEntry {
    assert(utility.isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475375281);
    assert('array' === $.type(collection), 'Invalid parameter "collection"', 1475375282);

    for (let i = 0, len = collection.length; i < len; ++i) {
      if (collection[i].identifier === collectionElementIdentifier) {
        return collection[i];
      }
    }

    return undefined;
  }

  /**
   * @throws 1475375283
   * @throws 1475375284
   * @throws 1475375285
   */
  public getIndexFromPropertyCollectionElementByIdentifier(
    collectionElementIdentifier: string,
    collectionName: string,
    formElement: FormElement
  ): number {
    assert(utility.isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475375283);
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475375284);
    assert(utility.isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475375285);

    const collection = formElement.get(collectionName);
    if ('array' === $.type(collection)) {
      for (let i = 0, len = collection.length; i < len; ++i) {
        if (collection[i].identifier === collectionElementIdentifier) {
          return i;
        }
      }
    }
    return -1;
  }

  /**
   * @throws 1475375686
   * @throws 1475375687
   * @throws 1475375688
   * @throws 1477413154
   */
  public addPropertyCollectionElement(
    collectionElementToAdd: CollectionEntry,
    collectionName: string,
    formElement: FormElement,
    referenceCollectionElementIdentifier: string,
    disablePublishersOnSet?: boolean
  ): FormElement {
    let collection, newCollectionElementIndex;
    assert('object' === $.type(collectionElementToAdd), 'Invalid parameter "collectionElementToAdd"', 1475375686);
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475375687);
    assert(utility.isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475375688);

    if (utility.isUndefinedOrNull(disablePublishersOnSet)) {
      disablePublishersOnSet = true;
    }
    disablePublishersOnSet = !!disablePublishersOnSet;

    collection = formElement.get(collectionName);
    if ('array' !== $.type(collection)) {
      extendModel(formElement, [], collectionName, true);
      collection = formElement.get(collectionName);
    }

    if (utility.isUndefinedOrNull(referenceCollectionElementIdentifier)) {
      newCollectionElementIndex = 0;
    } else {
      newCollectionElementIndex = this.getIndexFromPropertyCollectionElementByIdentifier(referenceCollectionElementIdentifier, collectionName, formElement) + 1;
      assert(-1 < newCollectionElementIndex, 'Could not find collection element ' + referenceCollectionElementIdentifier + ' within collection ' + collectionName, 1477413154);
    }

    collection.splice(newCollectionElementIndex, 0, collectionElementToAdd);
    formElement.set(collectionName, collection, true);

    propertyValidationService.removeValidatorIdentifiersFromFormElementProperty(formElement, collectionName);

    for (let i = 0, len = collection.length; i < len; ++i) {
      extendModel(formElement, collection[i], collectionName + '.' + i, true);
    }

    formElement.set(collectionName, collection, true);
    propertyValidationService.addValidatorIdentifiersFromFormElementPropertyCollections(formElement);
    formElement.set(collectionName, collection, disablePublishersOnSet);

    return formElement;
  }

  /**
   * @throws 1475375689
   * @throws 1475375690
   * @throws 1475375691
   * @throws 1475375692
   */
  public removePropertyCollectionElementByIdentifier(
    formElement: FormElement,
    collectionElementIdentifier: string,
    collectionName: string,
    disablePublishersOnSet?: boolean
  ): void {
    assert(utility.isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475375689);
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475375690);
    assert(utility.isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475375691);

    const collection = formElement.get(collectionName);
    assert('array' === $.type(collection), 'The collection "' + collectionName + '" does not exist', 1475375692);

    if (utility.isUndefinedOrNull(disablePublishersOnSet)) {
      disablePublishersOnSet = true;
    }
    disablePublishersOnSet = !!disablePublishersOnSet;

    propertyValidationService.removeValidatorIdentifiersFromFormElementProperty(formElement, collectionName);
    const collectionElementIndex = this.getIndexFromPropertyCollectionElementByIdentifier(collectionElementIdentifier, collectionName, formElement);
    collection.splice(collectionElementIndex, 1);
    formElement.set(collectionName, collection, disablePublishersOnSet);
    propertyValidationService.addValidatorIdentifiersFromFormElementPropertyCollections(formElement);
  }

  /**
   * @throws 1477404484
   * @throws 1477404485
   * @throws 1477404486
   * @throws 1477404488
   * @throws 1477404489
   * @throws 1477404490
   */
  public movePropertyCollectionElement(
    collectionElementToMoveIdentifier: string,
    position: string,
    referenceCollectionElementIdentifier: string,
    collectionName: string,
    formElement: FormElement,
    disablePublishersOnSet: boolean
  ): void {
    let referenceCollectionElement;

    assert('after' === position || 'before' === position, 'Invalid position "' + position + '"', 1477404485);
    assert('string' === $.type(referenceCollectionElementIdentifier), 'Invalid parameter "referenceCollectionElementIdentifier"', 1477404486);
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1477404488);

    const collection = formElement.get(collectionName);
    assert('array' === $.type(collection), 'The collection "' + collectionName + '" does not exist', 1477404490);

    const collectionElementToMove = this.findCollectionElementByIdentifierPath(collectionElementToMoveIdentifier, collection);
    assert('object' === $.type(collectionElementToMove), 'Invalid parameter "collectionElementToMove"', 1477404484);

    this.removePropertyCollectionElementByIdentifier(formElement, collectionElementToMoveIdentifier, collectionName);

    const referenceCollectionElementIndex = this.getIndexFromPropertyCollectionElementByIdentifier(referenceCollectionElementIdentifier, collectionName, formElement);
    assert(-1 < referenceCollectionElementIndex, 'Could not find collection element ' + referenceCollectionElementIdentifier + ' within collection ' + collectionName, 1477404489);

    if ('before' === position) {
      referenceCollectionElement = collection[referenceCollectionElementIndex - 1];
      if (utility.isUndefinedOrNull(referenceCollectionElement)) {
        referenceCollectionElementIdentifier = undefined;
      } else {
        referenceCollectionElementIdentifier = referenceCollectionElement.identifier;
      }
    }

    this.addPropertyCollectionElement(collectionElementToMove, collectionName, formElement, referenceCollectionElementIdentifier, disablePublishersOnSet);
  }
}


export class Factory {

  /**
   * @throws 1475375693
   * @throws 1475436040
   * @throws 1475604050
   */
  public createFormElement(
    configuration: FormElementDefinition,
    identifierPathPrefix: string,
    parentFormElement: FormElement,
    registerPropertyValidators: boolean,
    disablePublishersOnSet?: boolean
  ): FormElement {
    let currentChildFormElements;
    assert('object' === $.type(configuration), 'Invalid parameter "configuration"', 1475375693);
    assert(utility.isNonEmptyString(configuration.identifier), '"identifier" must not be empty', 1475436040);
    assert(utility.isNonEmptyString(configuration.type), '"type" must not be empty', 1475604050);

    registerPropertyValidators = !!registerPropertyValidators;
    if (utility.isUndefinedOrNull(disablePublishersOnSet)) {
      disablePublishersOnSet = true;
    }
    disablePublishersOnSet = !!disablePublishersOnSet;

    const formElementTypeDefinition = repository.getFormEditorDefinition('formElements', configuration.type);
    const rawChildFormElements = configuration.renderables;
    delete configuration.renderables;

    const collections: Record<string, Record<string, CollectionElementConfiguration>> = {};
    const predefinedDefaults: Record<string, unknown> = formElementTypeDefinition.predefinedDefaults || {};
    for (const collectionName of Object.keys(configuration)) {
      if (utility.isUndefinedOrNull(repository.formEditorDefinitions[collectionName as keyof FormEditorDefinitions])) {
        continue;
      }

      predefinedDefaults[collectionName] = predefinedDefaults[collectionName] || {};
      collections[collectionName] = <Record<string, CollectionElementConfiguration>>$.extend(
        predefinedDefaults[collectionName] || {},
        configuration[collectionName as keyof typeof configuration]
      );

      delete predefinedDefaults[collectionName];
      delete configuration[collectionName as keyof typeof configuration];
    }

    identifierPathPrefix = identifierPathPrefix || '';
    const identifierPath = (identifierPathPrefix === '') ? configuration.identifier : identifierPathPrefix + '/' + configuration.identifier;

    const concreteConfiguration: FormElementModelData = {
      ...<FormElementDefinition>predefinedDefaults,
      ...<FormElementDefinition>configuration,
      ...{
        renderables: (rawChildFormElements) ? true : null,
        __parentRenderable: null,
        __identifierPath: identifierPath
      }
    };

    const formElement: FormElement = createModel(concreteConfiguration);

    formElement.set('__parentRenderable', parentFormElement || null, disablePublishersOnSet);

    for (const [collectionName, collectionElementConfigurations] of Object.entries(collections)) {
      let i = 0;
      for (const collectionElementConfiguration of Object.values(collectionElementConfigurations)) {
        let previousCreatePropertyCollectionElementIdentifier;
        const propertyCollectionElement = this.createPropertyCollectionElement(
          collectionElementConfiguration.identifier,
          collectionElementConfiguration,
          collectionName as keyof FormEditorDefinitions
        );
        if (i > 0) {
          previousCreatePropertyCollectionElementIdentifier = collections[collectionName][i - 1].identifier;
        }
        repository.addPropertyCollectionElement(
          propertyCollectionElement,
          collectionName,
          formElement,
          previousCreatePropertyCollectionElementIdentifier,
          true
        );
        ++i;
      }
    }

    // Register property change publishers for properties that have not
    // been configured yet, but may be added by inspector components.
    if (Array.isArray(formElementTypeDefinition.editors)) {
      for (const editorConfig of formElementTypeDefinition.editors) {
        if (editorConfig.propertyPath) {
          formElement.on(editorConfig.propertyPath, 'core/formElement/somePropertyChanged');
        }
      }
    }

    if (registerPropertyValidators) {
      if ('array' === $.type(formElementTypeDefinition.editors)) {
        for (let i = 0, len1 = formElementTypeDefinition.editors.length; i < len1; ++i) {
          if ('array' !== $.type(formElementTypeDefinition.editors[i].propertyValidators)) {
            continue;
          }

          const propertyValidatorConfiguration: PropertyValidatorConfiguration = {
            propertyValidatorsMode: 'AND'
          };
          if (
            !utility.isUndefinedOrNull(formElementTypeDefinition.editors[i].propertyValidatorsMode)
            && formElementTypeDefinition.editors[i].propertyValidatorsMode === 'OR'
          ) {
            propertyValidatorConfiguration.propertyValidatorsMode = 'OR';
          }

          propertyValidationService.addValidatorIdentifiersToFormElementProperty(
            formElement,
            formElementTypeDefinition.editors[i].propertyValidators,
            formElementTypeDefinition.editors[i].propertyPath,
            undefined,
            undefined,
            propertyValidatorConfiguration
          );
        }
      }
    }

    if ('array' === $.type(rawChildFormElements)) {
      currentChildFormElements = [];
      for (let i = 0, len = rawChildFormElements.length; i < len; ++i) {
        currentChildFormElements.push(this.createFormElement(rawChildFormElements[i], identifierPath, formElement, registerPropertyValidators, disablePublishersOnSet));
      }
      formElement.set('renderables', currentChildFormElements, disablePublishersOnSet);
    }
    return formElement;
  }

  /**
   * @throws 1475377160
   * @throws 1475377161
   * @throws 1475377162
   */
  public createPropertyCollectionElement(
    collectionElementIdentifier: string,
    collectionElementConfiguration: CollectionElementConfiguration,
    collectionName: keyof FormEditorDefinitions
  ): PropertyCollectionElement {
    let collectionElementPresets;
    assert(utility.isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475377160);
    assert('object' === $.type(collectionElementConfiguration), 'Invalid parameter "collectionElementConfiguration"', 1475377161);
    assert(utility.isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475377162);

    collectionElementConfiguration.identifier = collectionElementIdentifier;
    const collectionDefinition = repository.getFormEditorDefinition(collectionName, collectionElementIdentifier);
    if ('predefinedDefaults' in collectionDefinition && collectionDefinition.predefinedDefaults) {
      collectionElementPresets = collectionDefinition.predefinedDefaults;
    } else {
      collectionElementPresets = {};
    }

    return $.extend(collectionElementPresets, collectionElementConfiguration);
  }
}

export class DataBackend {
  private endpoints: Endpoints = {};
  private prototypeName: string = null;
  private persistenceIdentifier: string = null;

  /**
   * @throws 1475377488
   */
  public setEndpoints(endpoints: Endpoints): void {
    assert('object' === $.type(endpoints), 'Invalid parameter "endpoints"', 1475377488);
    this.endpoints = endpoints;
  }

  /**
   * @throws 1475377489
   */
  public setPrototypeName(prototypeName: string): void {
    assert(utility.isNonEmptyString(prototypeName), 'Invalid parameter "prototypeName"', 1475928095);
    this.prototypeName = prototypeName;
  }

  /**
   * @throws 1475377489
   */
  public setPersistenceIdentifier(persistenceIdentifier: string): void {
    assert(utility.isNonEmptyString(persistenceIdentifier), 'Invalid parameter "persistenceIdentifier"', 1475377489);
    this.persistenceIdentifier = persistenceIdentifier;
  }

  /**
   * @publish core/ajax/saveFormDefinition/success
   * @publish core/ajax/error
   * @throws 1475520918
   */
  public saveFormDefinition(): void {
    assert(utility.isNonEmptyString(this.endpoints.saveForm), 'The endpoint "saveForm" is not configured', 1475520918);

    if (runningAjaxRequests.saveForm) {
      runningAjaxRequests.saveForm.abort();
    }

    runningAjaxRequests.saveForm = $.post(this.endpoints.saveForm, {
      formPersistenceIdentifier: this.persistenceIdentifier,
      formDefinition: JSON.stringify(utility.convertToSimpleObject(getApplicationStateStack().getCurrentState('formDefinition')))
    }, (data, textStatus, jqXHR): void => {
      if (runningAjaxRequests.saveForm !== jqXHR) {
        return;
      }
      runningAjaxRequests.saveForm = null;
      if (data.status === 'success') {
        publisherSubscriber.publish('core/ajax/saveFormDefinition/success', [data]);
      } else {
        publisherSubscriber.publish('core/ajax/saveFormDefinition/error', [data]);
      }
    });
    runningAjaxRequests.saveForm.fail((jqXHR, textStatus, errorThrown): void => {
      publisherSubscriber.publish('core/ajax/error', [jqXHR, textStatus, errorThrown]);
    });
  }

  /**
   * @publish core/ajax/renderFormDefinitionPage/success
   * @publish core/ajax/error
   * @throws 1473447677
   * @throws 1475377781
   * @throws 1475377782
   */
  public renderFormDefinitionPage(pageIndex: number): void {
    assert($.isNumeric(pageIndex), 'Invalid parameter "pageIndex"', 1475377781);
    assert(utility.isNonEmptyString(this.endpoints.formPageRenderer), 'The endpoint "formPageRenderer" is not configured', 1473447677);

    if (runningAjaxRequests.renderFormDefinitionPage) {
      runningAjaxRequests.renderFormDefinitionPage.abort();
    }

    runningAjaxRequests.renderFormDefinitionPage = $.post(this.endpoints.formPageRenderer, {
      formDefinition: JSON.stringify(utility.convertToSimpleObject(getApplicationStateStack().getCurrentState('formDefinition'))),
      pageIndex: pageIndex,
      prototypeName: this.prototypeName
    }, (data: string, textStatus, jqXHR): void => {
      if (runningAjaxRequests.renderFormDefinitionPage !== jqXHR) {
        return;
      }
      runningAjaxRequests.renderFormDefinitionPage = null;
      publisherSubscriber.publish('core/ajax/renderFormDefinitionPage/success', [data, pageIndex]);
    });
    runningAjaxRequests.renderFormDefinitionPage.fail((jqXHR, textStatus, errorThrown): void => {
      publisherSubscriber.publish('core/ajax/error', [jqXHR, textStatus, errorThrown]);
    });
  }
}

export class ApplicationStateStack {
  private stackSize: number = 10;
  private stackPointer: number = 0;
  private readonly stack: ApplicationState[] = [];

  /**
   * @publish core/applicationState/add
   * @throws 1477847415
   */
  public add(
    applicationState: ApplicationState,
    disablePublishersOnSet: boolean
  ): void {
    assert('object' === $.type(applicationState), 'Invalid parameter "applicationState"', 1477847415);
    disablePublishersOnSet = !!disablePublishersOnSet;

    $.extend(applicationState, {
      propertyValidationServiceRegisteredValidators: $.extend(true, {}, this.getCurrentState('propertyValidationServiceRegisteredValidators'))
    });

    this.stack.splice(0, 0, applicationState);
    if (this.stack.length > this.stackSize) {
      this.stack.splice(this.stackSize - 1, (this.stack.length - this.stackSize));
    }

    if (!disablePublishersOnSet) {
      publisherSubscriber.publish('core/applicationState/add', [
        applicationState,
        this.getCurrentStackPointer(),
        this.getCurrentStackSize()
      ]);
    }
  }

  /**
   * @publish core/applicationState/add
   * @throws 1477872641
   */
  public addAndReset(
    applicationState: ApplicationState,
    disablePublishersOnSet?: boolean
  ): void {
    assert('object' === $.type(applicationState), 'Invalid parameter "applicationState"', 1477872641);

    if (this.stackPointer > 0) {
      this.stack.splice(0, this.stackPointer);
    }

    this.stackPointer = 0;
    this.add(applicationState, true);

    if (!disablePublishersOnSet) {
      publisherSubscriber.publish(
        'core/applicationState/add',
        [
          this.getCurrentState<undefined>(),
          this.getCurrentStackPointer(),
          this.getCurrentStackSize()
        ]
      );
    }
  }

  /**
   * @throws 1477932754
   */
  public getCurrentState<T extends ApplicationStateType | undefined, R = T extends ApplicationStateType ? ApplicationState[T] : ApplicationState>(
    type?: T
  ): R | undefined {
    if (type === undefined) {
      return this.stack[this.stackPointer] as R || undefined;
    }
    assert(
      'formDefinition' === type
      || 'currentlySelectedPageIndex' === type
      || 'currentlySelectedFormElementIdentifierPath' === type
      || 'propertyValidationServiceRegisteredValidators' === type,

      'Invalid parameter "type"', 1477932754
    );

    if ('undefined' === $.type(this.stack[this.stackPointer])) {
      return undefined;
    }
    return (this.stack[this.stackPointer][type]) as R;
  }

  /**
   * @throws 1477934111
   */
  public setCurrentState<T extends ApplicationStateType>(
    type: T,
    value: ApplicationState[T]
  ): void {
    assert(
      'formDefinition' === type
      || 'currentlySelectedPageIndex' === type
      || 'currentlySelectedFormElementIdentifierPath' === type
      || 'propertyValidationServiceRegisteredValidators' === type,

      'Invalid parameter "type"', 1477934111
    );
    this.stack[this.stackPointer][type] = value;
  }

  /**
   * @throws 1477846933
   */
  public setMaximalStackSize(stackSize: number): void {
    assert('number' === $.type(stackSize), 'Invalid parameter "size"', 1477846933);
    this.stackSize = stackSize;
  }

  public getMaximalStackSize(): number {
    return this.stackSize;
  }

  public getCurrentStackSize(): number {
    return this.stack.length;
  }

  public getCurrentStackPointer(): number {
    return this.stackPointer;
  }

  /**
   * @throws 1477852138
   */
  public setCurrentStackPointer(stackPointer: number): void {
    assert('number' === $.type(stackPointer), 'Invalid parameter "size"', 1477852138);
    if (stackPointer < 0) {
      this.stackPointer = 0;
    } else if (stackPointer > this.stack.length - 1) {
      this.stackPointer = this.stack.length - 1;
    } else {
      this.stackPointer = stackPointer;
    }
  }

  public decrementCurrentStackPointer(): void {
    this.setCurrentStackPointer(--this.stackPointer);
  }

  public incrementCurrentStackPointer(): void {
    this.setCurrentStackPointer(++this.stackPointer);
  }
}

/**
 * @throws 1475358064
 */
export function getRunningAjaxRequest<T extends keyof AjaxRequests>(
  ajaxRequestIdentifier: keyof AjaxRequests
): AjaxRequests[T] | null {
  assert(utility.isNonEmptyString(ajaxRequestIdentifier), 'Invalid parameter "ajaxRequestIdentifier"', 1475358064);
  return runningAjaxRequests[ajaxRequestIdentifier] || null;
}

const utility = new Utility();
const dataBackend = new DataBackend();
const runningAjaxRequests: AjaxRequests = {};
const propertyValidationService = new PropertyValidationService();
const applicationStateStack = new ApplicationStateStack();
const publisherSubscriber = new PublisherSubscriber();
const repository = new Repository();
const factory = new Factory();

export function getUtility(): Utility {
  return utility;
}

export function getDataBackend(): DataBackend {
  return dataBackend;
}

export function getPropertyValidationService(): PropertyValidationService {
  return propertyValidationService;
}

export function getApplicationStateStack(): ApplicationStateStack {
  return applicationStateStack;
}

export function getPublisherSubscriber(): PublisherSubscriber {
  return publisherSubscriber;
}

export function getFactory(): Factory {
  return factory;
}

export function getRepository(): Repository {
  return repository;
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'core/applicationState/add': readonly [
      applicationState: ApplicationState,
      currentStackPointer: number,
      currentStackSize: number
    ];
    'core/ajax/error': readonly [
      jqXHR: JQueryXHR,
      textStatus: string,
      errorThrown: string
    ];
    'core/ajax/renderFormDefinitionPage/success': readonly [
      htmldata: string,
      pageIndex: number
    ];
    'core/ajax/saveFormDefinition/error': readonly [
      data: {
        status: string,
        message: string,
        code: number
      }
    ];
    'core/ajax/saveFormDefinition/success': readonly [
      data: {
        status: string,
        formDefinition: FormElementDefinition,
      }
    ],
    'core/formElement/somePropertyChanged': readonly [
      propertyPath: string,
      value?: unknown | undefined,
      oldValue?: unknown | undefined,
      formElementIdentifierPath?: string,
    ];
  }
}
