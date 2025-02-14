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
 * Module: @typo3/form/backend/form-editor
 */
import $ from 'jquery';
import Notification from '@typo3/backend/notification';
import * as Core from '@typo3/form/backend/form-editor/core';
import type { JavaScriptItemPayload } from '@typo3/core/java-script-item-processor';
import type {
  ApplicationStateStack,
  AjaxRequests,
  Endpoints,
  CollectionElementConfiguration,
  CollectionEntry,
  DataBackend,
  Factory,
  FormEditorDefinitions,
  FormElement,
  FormElementDefinition,
  FormElementPropertyValidatorDefinition,
  PublisherSubscriber,
  PropertyCollectionElement,
  PropertyValidationService,
  Repository,
  RootFormElement,
  Utility,
  ValidationResults,
  ValidationResultsWithPath,
  ValidationResultsRecursive,
  Validator
} from '@typo3/form/backend/form-editor/core';

const assert = Core.assert;

type AdditionalViewModelModules = JavaScriptItemPayload[];

type ViewModel = typeof import('./form-editor/view-model');
type Mediator = typeof import('./form-editor/mediator');

export type FormEditorConfiguration = {
  prototypeName: string,
  endpoints: Endpoints,
  formEditorDefinitions: FormEditorDefinitions,
  formDefinition: FormElementDefinition,
  formPersistenceIdentifier: string,
  additionalViewModelModules: AdditionalViewModelModules,
  maximumUndoSteps: number
};

export class FormEditor {
  private isRunning: boolean = false;
  private unsavedContent: boolean = false;
  private readonly configuration: FormEditorConfiguration;
  private readonly mediator: Mediator;
  private readonly viewModel: ViewModel;

  public constructor(
    configuration: FormEditorConfiguration,
    mediator: Mediator,
    viewModel: ViewModel
  ) {
    this.configuration = configuration || {} as FormEditorConfiguration;
    this.mediator = mediator;
    this.viewModel = viewModel;
  }

  public getPublisherSubscriber(): PublisherSubscriber {
    return Core.getPublisherSubscriber();
  }

  public undoApplicationState(): void {
    this.getApplicationStateStack().incrementCurrentStackPointer();
  }

  public redoApplicationState(): void {
    this.getApplicationStateStack().decrementCurrentStackPointer();
  }

  public getMaximalApplicationStates(): number {
    return this.getApplicationStateStack().getMaximalStackSize();
  }

  public getCurrentApplicationStates(): number {
    return this.getApplicationStateStack().getCurrentStackSize();
  }

  public getCurrentApplicationStatePosition(): number {
    return this.getApplicationStateStack().getCurrentStackPointer();
  }

  /**
   * @internal
   * @throws 1519855175
   */
  public setFormDefinition(formDefinition: FormElementDefinition): void {
    assert('object' === $.type(formDefinition), 'Invalid parameter "formDefinition"', 1519855175);
    this.getApplicationStateStack().setCurrentState('formDefinition', this.getFactory().createFormElement(formDefinition, undefined, undefined, true));
  }

  /**
   * @throws 1475378543
   */
  public getRunningAjaxRequest<T extends keyof AjaxRequests>(
    type: keyof AjaxRequests
  ): AjaxRequests[T] | null {
    assert(this.getUtility().isNonEmptyString(type), 'Invalid parameter "type"', 1475378543);
    return Core.getRunningAjaxRequest(type);
  }

  public getUtility(): Utility {
    return Core.getUtility();
  }

  public assert(test: boolean|(() => boolean), message: string, messageCode: number): void {
    this.getUtility().assert(test, message, messageCode);
  }

  public buildPropertyPath(
    propertyPath: string,
    collectionElementIdentifier?: string,
    collectionName?: keyof FormEditorDefinitions,
    _formElement?: string | FormElement,
    allowEmptyReturnValue?: boolean
  ): string {
    if (this.getUtility().isUndefinedOrNull(_formElement)) {
      _formElement = this.getCurrentlySelectedFormElement();
    }
    const formElement = this.getRepository().findFormElement(_formElement);
    return this.getUtility().buildPropertyPath(
      propertyPath,
      collectionElementIdentifier,
      collectionName,
      formElement,
      allowEmptyReturnValue
    );
  }

  public addPropertyValidationValidator(validatorIdentifier: string, func: Validator): void {
    this.getPropertyValidationService().addValidator(validatorIdentifier, func);
  }

  public validateCurrentlySelectedFormElementProperty(
    propertyPath: string
  ): ValidationResults {
    return this.validateFormElementProperty(
      this.getCurrentlySelectedFormElement(),
      propertyPath
    );
  }

  public validateFormElementProperty(
    _formElement: FormElement | string,
    propertyPath: string
  ): ValidationResults {
    const formElement = this.getRepository().findFormElement(_formElement);
    return this.getPropertyValidationService().validateFormElementProperty(formElement, propertyPath);
  }

  public validateFormElement(
    _formElement: FormElement | string
  ): ValidationResultsWithPath {
    const formElement = this.getRepository().findFormElement(_formElement);
    return this.getPropertyValidationService().validateFormElement(formElement);
  }

  public validationResultsHasErrors(
    validationResults: ValidationResultsRecursive
  ): boolean {
    return this.getPropertyValidationService().validationResultsHasErrors(validationResults);
  }

  public validateFormElementRecursive(
    _formElement: FormElement | string,
    returnAfterFirstMatch?: boolean
  ): ValidationResultsRecursive {
    const formElement = this.getRepository().findFormElement(_formElement);
    return this.getPropertyValidationService().validateFormElementRecursive(formElement, returnAfterFirstMatch);
  }

  /**
   * @throws 1475378544
   */
  public setUnsavedContent(unsavedContent: boolean): void {
    assert('boolean' === $.type(unsavedContent), 'Invalid parameter "unsavedContent"', 1475378544);
    this.unsavedContent = unsavedContent;
  }

  public getUnsavedContent(): boolean {
    return this.unsavedContent;
  }

  public getRootFormElement(): RootFormElement {
    return this.getRepository().getRootFormElement();
  }

  public getCurrentlySelectedFormElement(): FormElement {
    return this.getRepository().findFormElementByIdentifierPath(this.getApplicationStateStack().getCurrentState('currentlySelectedFormElementIdentifierPath'));
  }

  /**
   * @publish core/currentlySelectedFormElementChanged
   */
  public setCurrentlySelectedFormElement(
    _formElement: FormElement | string,
    doNotRefreshCurrentlySelectedPageIndex?: boolean
  ): void {
    doNotRefreshCurrentlySelectedPageIndex = !!doNotRefreshCurrentlySelectedPageIndex;

    const formElement = this.getRepository().findFormElement(_formElement);
    this.getApplicationStateStack().setCurrentState('currentlySelectedFormElementIdentifierPath', formElement.get('__identifierPath'));

    if (!doNotRefreshCurrentlySelectedPageIndex) {
      this.refreshCurrentlySelectedPageIndex();
    }
    this.getPublisherSubscriber().publish('core/currentlySelectedFormElementChanged', [formElement]);
  }

  /**
   * @throws 1475378545
   */
  public getFormElementByIdentifierPath(identifierPath: string): FormElement {
    assert(this.getUtility().isNonEmptyString(identifierPath), 'Invalid parameter "identifierPath"', 1475378545);
    return this.getRepository().findFormElementByIdentifierPath(identifierPath);
  }

  public isFormElementIdentifierUsed(formElementIdentifier: string): boolean {
    return this.getRepository().isFormElementIdentifierUsed(formElementIdentifier);
  }

  public createAndAddFormElement(
    formElementType: string,
    referenceFormElement?: FormElement | string,
    disablePublishersOnSet?: boolean
  ): FormElement {
    const formElement = this.addFormElement(
      this.createFormElement(formElementType, disablePublishersOnSet),
      referenceFormElement,
      disablePublishersOnSet
    );
    formElement.set('renderables', formElement.get('renderables'));
    return formElement;
  }

  /**
   * @throws 1475434337
   */
  public addFormElement(
    formElement: FormElement,
    _referenceFormElement?: FormElement | string,
    disablePublishersOnSet?: boolean
  ): FormElement {
    this.saveApplicationState();

    if (this.getUtility().isUndefinedOrNull(_referenceFormElement)) {
      _referenceFormElement = this.getCurrentlySelectedFormElement();
    }
    const referenceFormElement = this.getRepository().findFormElement(_referenceFormElement);
    assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475434337);
    return this.getRepository().addFormElement(formElement, referenceFormElement, true, disablePublishersOnSet);
  }

  /**
   * @throws 1475434336
   * @throws 1475435857
   */
  public createFormElement(
    formElementType: string,
    disablePublishersOnSet: boolean
  ): FormElement {
    assert(this.getUtility().isNonEmptyString(formElementType), 'Invalid parameter "formElementType"', 1475434336);

    const identifier = this.getRepository().getNextFreeFormElementIdentifier(formElementType);
    const formElementDefinition = this.getFormElementDefinitionByType(formElementType, undefined);
    return this.getFactory().createFormElement({
      type: formElementType,
      identifier: identifier,
      label: formElementDefinition.label || formElementType
    }, undefined, undefined, undefined, disablePublishersOnSet);
  }

  public removeFormElement(
    _formElementToRemove: FormElement | string,
    disablePublishersOnSet?: boolean
  ): FormElement {
    this.saveApplicationState();

    const formElementToRemove = this.getRepository().findFormElement(_formElementToRemove);
    const parentFormElement = formElementToRemove.get('__parentRenderable');
    this.getRepository().removeFormElement(formElementToRemove, true, disablePublishersOnSet);
    return parentFormElement;
  }

  /**
   * @throws 1475378551
   */
  public moveFormElement(
    _formElementToMove: FormElement | string,
    position: string,
    _referenceFormElement: FormElement | string,
    disablePublishersOnSet?: boolean
  ): FormElement {
    this.saveApplicationState();

    let formElementToMove = this.getRepository().findFormElement(_formElementToMove);
    const referenceFormElement = this.getRepository().findFormElement(_referenceFormElement);

    assert('after' === position || 'before' === position || 'inside' === position, 'Invalid position "' + position + '"', 1475378551);

    formElementToMove = this.getRepository().moveFormElement(formElementToMove, position, referenceFormElement, true);
    disablePublishersOnSet = !!disablePublishersOnSet;
    if (!disablePublishersOnSet) {
      formElementToMove.get('__parentRenderable').set('renderables', formElementToMove.get('__parentRenderable').get('renderables'));
    }
    return formElementToMove;
  }

  /**
   * @throws 1475378555
   * @throws 1475378556
   * @throws 1475446108
   */
  public getPropertyCollectionElementConfiguration(
    collectionElementIdentifier: string,
    collectionName: keyof FormEditorDefinitions,
    _formElement?: string | FormElement
  ): CollectionEntry {
    let collection, collectionElement;
    if (this.getUtility().isUndefinedOrNull(_formElement)) {
      _formElement = this.getCurrentlySelectedFormElement();
    }
    const formElement = this.getRepository().findFormElement(_formElement);

    assert(this.getUtility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475378555);
    assert(this.getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475378556);

    const formElementDefinition = this.getFormElementDefinitionByType(formElement.get('type'), undefined);
    if (!this.getUtility().isUndefinedOrNull(formElementDefinition.propertyCollections)) {
      collection = formElementDefinition.propertyCollections[collectionName];
      assert(!this.getUtility().isUndefinedOrNull(collection), 'Invalid collection name "' + collectionName + '"', 1475446108);
      collectionElement = this.getRepository().findCollectionElementByIdentifierPath(collectionElementIdentifier, collection);
      // Return a dereferenced object
      return $.extend(true, {}, collectionElement);
    } else {
      return {} as CollectionEntry;
    }
  }

  /**
   * @throws 1475378557
   * @throws 1475378558
   */
  public getIndexFromPropertyCollectionElement(
    collectionElementIdentifier: string,
    collectionName: keyof FormEditorDefinitions,
    _formElement?: string | FormElement
  ): number {
    if (this.getUtility().isUndefinedOrNull(_formElement)) {
      _formElement = this.getCurrentlySelectedFormElement();
    }
    const formElement = this.getRepository().findFormElement(_formElement);

    assert(this.getUtility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475378557);
    assert(this.getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475378558);

    const indexFromPropertyCollectionElement = this.getRepository().getIndexFromPropertyCollectionElementByIdentifier(
      collectionElementIdentifier,
      collectionName,
      formElement
    );

    return indexFromPropertyCollectionElement;
  }

  public createAndAddPropertyCollectionElement(
    collectionElementIdentifier: string,
    collectionName: keyof FormEditorDefinitions,
    formElement: FormElement,
    collectionElementConfiguration: CollectionElementConfiguration,
    referenceCollectionElementIdentifier: string
  ): FormElement {
    return this.addPropertyCollectionElement(
      this.createPropertyCollectionElement(
        collectionElementIdentifier,
        collectionName,
        collectionElementConfiguration
      ),
      collectionName,
      formElement,
      referenceCollectionElementIdentifier
    );
  }

  /**
   * @throws 1475443300
   * @throws 1475443301
   */
  public addPropertyCollectionElement(
    collectionElement: CollectionEntry,
    collectionName: keyof FormEditorDefinitions,
    _formElement?: FormElement | string,
    referenceCollectionElementIdentifier?: string
  ): FormElement {
    let collection;
    this.saveApplicationState();

    if (this.getUtility().isUndefinedOrNull(_formElement)) {
      _formElement = this.getCurrentlySelectedFormElement();
    }
    const formElement = this.getRepository().findFormElement(_formElement);

    assert('object' === $.type(collectionElement), 'Invalid parameter "collectionElement"', 1475443301);
    assert(this.getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475443300);

    if (this.getUtility().isUndefinedOrNull(referenceCollectionElementIdentifier)) {
      collection = formElement.get(collectionName);
      if ('array' === $.type(collection) && collection.length > 0) {
        referenceCollectionElementIdentifier = collection[collection.length - 1].identifier;
      }
    }

    return this.getRepository().addPropertyCollectionElement(
      collectionElement,
      collectionName,
      formElement,
      referenceCollectionElementIdentifier,
      false
    );
  }

  /**
   * @throws 1475378559
   * @throws 1475378560
   */
  public createPropertyCollectionElement(
    collectionElementIdentifier: string,
    collectionName: keyof FormEditorDefinitions,
    collectionElementConfiguration: CollectionElementConfiguration
  ): PropertyCollectionElement {
    assert(this.getUtility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475378559);
    assert(this.getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475378560);
    if ('object' !== $.type(collectionElementConfiguration)) {
      collectionElementConfiguration = {} as CollectionElementConfiguration;
    }

    return this.getFactory().createPropertyCollectionElement(
      collectionElementIdentifier,
      collectionElementConfiguration,
      collectionName
    );
  }

  /**
   * @throws 1475378561
   * @throws 1475378562
   */
  public removePropertyCollectionElement(
    collectionElementIdentifier: string,
    collectionName: keyof FormEditorDefinitions,
    _formElement?: FormElement | string,
    disablePublishersOnSet?: boolean
  ): void {
    this.saveApplicationState();

    if (this.getUtility().isUndefinedOrNull(_formElement)) {
      _formElement = this.getCurrentlySelectedFormElement();
    }
    const formElement = this.getRepository().findFormElement(_formElement);

    assert(this.getUtility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475378561);
    assert(this.getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475378562);

    this.getRepository().removePropertyCollectionElementByIdentifier(
      formElement,
      collectionElementIdentifier,
      collectionName,
      true
    );

    disablePublishersOnSet = !!disablePublishersOnSet;
    if (!disablePublishersOnSet) {
      this.getPublisherSubscriber().publish('core/formElement/somePropertyChanged', ['__fakeProperty']);
    }
  }

  /**
   * @throws 1477404352
   * @throws 1477404353
   * @throws 1477404354
   * @throws 1477404355
   */
  public movePropertyCollectionElement(
    collectionElementToMove: string,
    position: string,
    referenceCollectionElement: string,
    collectionName: keyof FormEditorDefinitions,
    formElement: FormElement,
    disablePublishersOnSet?: boolean
  ): void {
    this.saveApplicationState();

    formElement = this.getRepository().findFormElement(formElement);

    assert('string' === $.type(collectionElementToMove), 'Invalid parameter "collectionElementToMove"', 1477404352);
    assert('string' === $.type(referenceCollectionElement), 'Invalid parameter "referenceCollectionElement"', 1477404353);
    assert('after' === position || 'before' === position, 'Invalid position "' + position + '"', 1477404354);
    assert(this.getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1477404355);

    this.getRepository().movePropertyCollectionElement(collectionElementToMove, position, referenceCollectionElement, collectionName, formElement, disablePublishersOnSet);
  }

  /**
   * @throws 1475378563
   */
  public getFormElementDefinitionByType<T extends undefined | keyof FormElementDefinition, R = T extends keyof FormElementDefinition ? FormElementDefinition[T] : FormElementDefinition>(
    elementType: string,
    formElementDefinitionKey?: T
  ): R {
    assert(this.getUtility().isNonEmptyString(elementType), 'Invalid parameter "elementType"', 1475378563);

    const formElementDefinition = this.getRepository().getFormEditorDefinition('formElements', elementType);

    if (formElementDefinitionKey !== undefined/* && formElementDefinitionKey !== null*/) {
      const formElementDefinitionEntry = formElementDefinition[formElementDefinitionKey];
      if (formElementDefinitionEntry !== null && (typeof formElementDefinitionEntry === 'object')) {
        return $.extend(true, {}, formElementDefinitionEntry);
      } else {
        return formElementDefinitionEntry as R;
      }
    }

    if (formElementDefinition !== null && (typeof formElementDefinition === 'object')) {
      return $.extend(true, {}, formElementDefinition);
    } else {
      return formElementDefinition as R;
    }
  }

  public getFormElementDefinition<T extends keyof FormElementDefinition>(
    formElement: FormElement | string,
    formElementDefinitionKey?: T
  ): T extends keyof FormElementDefinition ? FormElementDefinition[T] : FormElementDefinition {
    formElement = this.getRepository().findFormElement(formElement);
    return this.getFormElementDefinitionByType(formElement.get('type'), formElementDefinitionKey);
  }

  public getFormEditorDefinition<D extends keyof FormEditorDefinitions, S extends keyof FormEditorDefinitions[D]>(
    definitionName: D,
    subject: S
  ): FormEditorDefinitions[D][S] {
    return this.getRepository().getFormEditorDefinition(definitionName, subject);
  }

  /**
   * @throws 1475672362
   */
  public getFormElementPropertyValidatorDefinition(validatorIdentifier: string): FormElementPropertyValidatorDefinition {
    assert(this.getUtility().isNonEmptyString(validatorIdentifier), 'Invalid parameter "validatorIdentifier"', 1475672362);

    const validatorDefinition = this.getRepository().getFormEditorDefinition('formElementPropertyValidators', validatorIdentifier);
    // Return a dereferenced object
    return $.extend(true, {}, validatorDefinition);
  }

  public getCurrentlySelectedPageIndex(): number {
    return this.getApplicationStateStack().getCurrentState('currentlySelectedPageIndex');
  }

  public refreshCurrentlySelectedPageIndex(): void {
    this.getApplicationStateStack().setCurrentState(
      'currentlySelectedPageIndex',
      this.getPageIndexFromFormElement(this.getCurrentlySelectedFormElement())
    );
  }

  /**
   * @throws 1477786068
   */
  public getCurrentlySelectedPage(): FormElement {
    const currentPage = this.getRepository().getRootFormElement().get('renderables')[this.getCurrentlySelectedPageIndex()];
    assert('object' === $.type(currentPage), 'No page found', 1477786068);
    return currentPage;
  }

  public getLastTopLevelElementOnCurrentPage(): FormElement {

    const renderables = this.getCurrentlySelectedPage().get('renderables');
    if (this.getUtility().isUndefinedOrNull(renderables)) {
      return undefined;
    }
    return renderables[renderables.length - 1];
  }

  public getLastFormElementWithinParentFormElement(formElement: FormElement): FormElement {
    formElement = this.getRepository().findFormElement(formElement);
    if (formElement.get('__identifierPath') === this.getRootFormElement().get('__identifierPath')) {
      return formElement;
    }
    return formElement.get('__parentRenderable').get('renderables')[formElement.get('__parentRenderable').get('renderables').length - 1];
  }

  public getPageIndexFromFormElement(formElement: FormElement | string): number {
    formElement = this.getRepository().findFormElement(formElement);

    return this.getRepository().getIndexForEnclosingCompositeFormElementWhichIsOnTopLevelForFormElement(
      formElement
    );
  }

  public renderCurrentFormPage(): void {
    this.renderFormPage(this.getCurrentlySelectedPageIndex());
  }

  /**
   * @throws 1475446442
   */
  public renderFormPage(pageIndex: number): void {
    assert('number' === $.type(pageIndex), 'Invalid parameter "pageIndex"', 1475446442);
    this.getDataBackend().renderFormDefinitionPage(pageIndex);
  }

  public findEnclosingCompositeFormElementWhichIsNotOnTopLevel(
    formElement: FormElement | string
  ): FormElement | null {
    return this.getRepository().findEnclosingCompositeFormElementWhichIsNotOnTopLevel(
      this.getRepository().findFormElement(formElement)
    );
  }

  /**
   * @todo deprecate, method is unused
   */
  public findEnclosingGridRowFormElement(
    formElement: FormElement | string
  ): FormElement | null {
    return this.getRepository().findEnclosingGridRowFormElement(
      this.getRepository().findFormElement(formElement)
    );
  }

  public getNonCompositeNonToplevelFormElements(): FormElement[] {
    return this.getRepository().getNonCompositeNonToplevelFormElements();
  }

  public isRootFormElementSelected(): boolean {
    return (this.getCurrentlySelectedFormElement().get('__identifierPath') === this.getRootFormElement().get('__identifierPath'));
  }

  public getViewModel(): ViewModel {
    return this.viewModel;
  }

  public saveFormDefinition(): void {
    this.getDataBackend().saveFormDefinition();
  }

  /**
   * @throws 1473200696
   */
  public run(): FormEditor {
    if (this.isRunning) {
      throw 'You can not run the app twice (1473200696)';
    }

    try {
      this.bootstrap();
      this.isRunning = true;
    } catch(error: unknown) {
      if (!(error instanceof Error)) {
        throw error;
      }
      Notification.error(
        TYPO3.lang['formEditor.error.headline'],
        TYPO3.lang['formEditor.error.message']
        + '\r\n'
        + '\r\n'
        + TYPO3.lang['formEditor.error.technicalReason']
        + '\r\n'
        + error.message);
    }
    return this;
  }

  private saveApplicationState(): void {
    this.getApplicationStateStack().addAndReset({
      formDefinition: this.getApplicationStateStack().getCurrentState('formDefinition').clone(),
      currentlySelectedPageIndex: this.getApplicationStateStack().getCurrentState('currentlySelectedPageIndex'),
      currentlySelectedFormElementIdentifierPath: this.getApplicationStateStack().getCurrentState('currentlySelectedFormElementIdentifierPath')
    });
  }

  private getDataBackend(): DataBackend {
    return Core.getDataBackend();
  }

  private getFactory(): Factory {
    return Core.getFactory();
  }

  private getRepository(): Repository {
    return Core.getRepository();
  }

  private getPropertyValidationService(): PropertyValidationService {
    return Core.getPropertyValidationService();
  }

  private getApplicationStateStack(): ApplicationStateStack {
    return Core.getApplicationStateStack();
  }

  /**
   * @publish ajax/beforeSend
   * @publish ajax/complete
   */
  private ajaxSetup(): void {
    $.ajaxSetup({
      beforeSend: () => {
        this.getPublisherSubscriber().publish('ajax/beforeSend');
      },
      complete: () => {
        this.getPublisherSubscriber().publish('ajax/complete');
      }
    });
  }

  /**
   * @throws 1475379748
   * @throws 1475379749
   * @throws 1475927876
   */
  private dataBackendSetup(endpoints: Endpoints, prototypeName: string, formPersistenceIdentifier: string): void {
    assert('object' === $.type(endpoints), 'Invalid parameter "endpoints"', 1475379748);
    assert(this.getUtility().isNonEmptyString(prototypeName), 'Invalid parameter "prototypeName"', 1475927876);
    assert(this.getUtility().isNonEmptyString(formPersistenceIdentifier), 'Invalid parameter "formPersistenceIdentifier"', 1475379749);

    Core.getDataBackend().setEndpoints(endpoints);
    Core.getDataBackend().setPrototypeName(prototypeName);
    Core.getDataBackend().setPersistenceIdentifier(formPersistenceIdentifier);
  }

  /**
   * @throws 1475379750
   */
  private repositorySetup(formEditorDefinitions: FormEditorDefinitions): void {
    assert('object' === $.type(formEditorDefinitions), 'Invalid parameter "formEditorDefinitions"', 1475379750);

    this.getRepository().setFormEditorDefinitions(formEditorDefinitions);
  }

  /**
   * @throws 1475492374
   */
  private viewSetup(additionalViewModelModules: AdditionalViewModelModules): void {
    assert('function' === $.type(this.viewModel.bootstrap), 'The view model does not implement the method "bootstrap"', 1475492374);

    if (this.getUtility().isUndefinedOrNull(additionalViewModelModules)) {
      additionalViewModelModules = [];
    }
    this.viewModel.bootstrap(formEditorInstance, additionalViewModelModules);
  }

  /**
   * @throws 1475492032
   */
  private mediatorSetup(): void {
    assert('function' === $.type(this.mediator.bootstrap), 'The mediator does not implement the method "bootstrap"', 1475492032);
    this.mediator.bootstrap(formEditorInstance, this.viewModel);
  }

  /**
   * @throws 1475379751
   */
  private applicationStateStackSetup(
    rootFormElement: FormElementDefinition,
    maximumUndoSteps: number
  ): void {
    assert('object' === $.type(rootFormElement), 'Invalid parameter "rootFormElement"', 1475379751);

    if ('number' !== $.type(maximumUndoSteps)) {
      maximumUndoSteps = 10;
    }
    this.getApplicationStateStack().setMaximalStackSize(maximumUndoSteps);

    this.getApplicationStateStack().addAndReset({
      currentlySelectedPageIndex: 0,
      currentlySelectedFormElementIdentifierPath: rootFormElement.identifier
    }, true);

    this.getApplicationStateStack().setCurrentState('formDefinition', this.getFactory().createFormElement(rootFormElement, undefined, undefined, true));
  }

  private bootstrap(): void {
    this.mediatorSetup();
    this.ajaxSetup();
    this.dataBackendSetup(this.configuration.endpoints, this.configuration.prototypeName, this.configuration.formPersistenceIdentifier);
    this.repositorySetup(this.configuration.formEditorDefinitions);
    this.applicationStateStackSetup(this.configuration.formDefinition, this.configuration.maximumUndoSteps);
    this.setCurrentlySelectedFormElement(this.getRepository().getRootFormElement());

    this.viewSetup(this.configuration.additionalViewModelModules);
  }
}

let formEditorInstance: FormEditor = null;

/**
 * @public
 * @static
 *
 * Implement the "Singleton Pattern".
 *
 * Return a singleton instance of a
 * "FormEditor" object.
 */
export function getInstance(
  configuration: FormEditorConfiguration,
  mediator: Mediator,
  viewModel: ViewModel
): FormEditor {
  if (formEditorInstance === null) {
    formEditorInstance = new FormEditor(configuration, mediator, viewModel);
  }
  return formEditorInstance;
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'core/currentlySelectedFormElementChanged': readonly [
      formElement: FormElement
    ];
    'ajax/beforeSend': undefined;
    'ajax/complete': undefined;
  }
}
