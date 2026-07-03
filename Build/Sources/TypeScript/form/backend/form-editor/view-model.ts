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
 * Module: @typo3/form/backend/form-editor/view-model
 */
import { cloneDeep } from 'lodash-es';
import * as TreeComponent from '@typo3/form/backend/form-editor/tree-component-adapter';
import * as ModalsComponent from '@typo3/form/backend/form-editor/modals-component';
import * as InspectorComponent from '@typo3/form/backend/form-editor/inspector-component';
import * as StageComponent from '@typo3/form/backend/form-editor/stage-component';
import * as Helper from '@typo3/form/backend/form-editor/helper';
import Icons from '@typo3/backend/icons';
import Notification from '@typo3/backend/notification';
import { loadModule } from '@typo3/core/java-script-item-processor';

import type {
  FormEditor,
} from '@typo3/form/backend/form-editor';
import type {
  Utility,
  CollectionElementConfiguration,
  FormEditorDefinitions,
  FormElement,
  FormElementDefinition,
  PublisherSubscriber,
  EditorConfiguration,
} from '@typo3/form/backend/form-editor/core';
import type {
  Configuration as HelperConfiguration,
} from '@typo3/form/backend/form-editor/helper';
import type { InsertElementsModalConfiguration } from '@typo3/form/backend/form-editor/modals-component';
import type { JavaScriptItemPayload } from '@typo3/core/java-script-item-processor';

type AdditionalViewModelModules = JavaScriptItemPayload[];

const configuration: Partial<HelperConfiguration> = {
  domElementClassNames: {
    formElementIsComposit: 'formeditor-element-composit',
    formElementIsTopLevel: 'formeditor-element-toplevel',
    hasError: 'has-error',
    selectedCompositFormElement: 'selected',
    selectedFormElement: 'selected',
    selectedRootFormElement: 'selected',
    selectedStagePanel: 't3-form-form-stage-selected',
    sortableHover: 'sortable-hover',
    viewModeAbstract: 'formeditor-module-viewmode-abstract',
    viewModePreview: 'formeditor-module-viewmode-preview'
  },
  domElementDataAttributeNames: {
    abstractType: 'data-element-abstract-type'
  },
  domElementDataAttributeValues: {
    buttonHeaderClose: 'closeButton',
    buttonHeaderPaginationNext: 'buttonPaginationNext',
    buttonHeaderPaginationPrevious: 'buttonPaginationPrevious',
    buttonHeaderRedo: 'redoButton',
    buttonHeaderSave: 'saveButton',
    buttonHeaderUndo: 'undoButton',
    buttonHeaderViewModeAbstract: 'buttonViewModeAbstract',
    buttonHeaderViewModePreview: 'buttonViewModePreview',
    buttonFormSettings: 'formSettings',
    buttonToggleStructure: 'formeditorStructureToggle',
    buttonExpandInspector: 'formeditorInspectorExpand',
    buttonCollapseInspector: 'formeditorInspectorCollapse',
    buttonNewPage: 'newPage',
    iconMailform: 'content-form',
    iconSave: 'actions-document-save',
    iconSaveSpinner: 'spinner-circle',
    inspectorSection: 'inspectorSection',
    moduleLoadingIndicator: 'moduleLoadingIndicator',
    moduleWrapper: 'moduleWrapper',
    stageArea: 'stageArea',
    stageContainer: 'stageContainer',
    stageContainerInner: 'stageContainerInner',
    stagePanelHeading: 'panelHeading',
    stageSection: 'stageSection',
    structure: 'structure-element',
    structureSection: 'structureSection',
    structureRootContainer: 'treeRootContainer',
    structureRootElement: 'treeRootElement'
  }
};

let previewMode: boolean = false;

let formEditorApp: FormEditor = null;

let structureComponent: typeof TreeComponent = null;

let modalsComponent: typeof ModalsComponent = null;

let inspectorsComponent: typeof InspectorComponent = null;

let stageComponent: typeof StageComponent = null;

function getRootFormElement(): FormElement {
  return getFormEditorApp().getRootFormElement();
}

function assert(test: boolean|(() => boolean), message: string, messageCode: number): void {
  return getFormEditorApp().assert(test, message, messageCode);
}

function getUtility(): Utility {
  return getFormEditorApp().getUtility();
}

function getCurrentlySelectedFormElement(): FormElement {
  return getFormEditorApp().getCurrentlySelectedFormElement();
}

function getPublisherSubscriber(): PublisherSubscriber {
  return getFormEditorApp().getPublisherSubscriber();
}

/**
 * RFC 3339 full-date format: YYYY-MM-DD
 */
const RFC3339_FULL_DATE_PATTERN = /^([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])$/i;

function isAbsoluteDate(value: string): boolean {
  return RFC3339_FULL_DATE_PATTERN.test(value);
}

/**
 * A relative date expression is any non-empty string that is NOT an absolute
 * date (YYYY-MM-DD). Actual validation is performed server-side by PHP's
 * DateTime parser, which supports the full strtotime() grammar (e.g.
 * "last sunday", "first day of next month", "+1 month +3 days").
 */
function isRelativeDateExpression(value: string): boolean {
  const trimmed = value.trim();
  return trimmed.length > 0 && !isAbsoluteDate(trimmed);
}

function addPropertyValidators(): void {
  getFormEditorApp().addPropertyValidationValidator('NotEmpty', function(formElement, propertyPath) {
    const value = formElement.get(propertyPath);
    if (!value || value === '' || Array.isArray(value) && !value.length) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('NotEmpty').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('Integer', function(formElement, propertyPath) {
    const value = formElement.get(propertyPath);
    if (value === '' || value === null || isNaN(Number(value))) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('Integer').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('IntegerOrEmpty', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    if (formElement.get(propertyPath).length > 0 && isNaN(Number(formElement.get(propertyPath)))) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('Integer').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('NaiveEmail', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    if (!formElement.get(propertyPath).match(/\S+@\S+\.\S+/)) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('NaiveEmail').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('NaiveEmailOrEmpty', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    if (formElement.get(propertyPath).length > 0 && !formElement.get(propertyPath).match(/\S+@\S+\.\S+/)) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('NaiveEmailOrEmpty').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('FormElementIdentifierWithinCurlyBracesInclusive', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }

    const regex = /\{([a-z0-9-_]+)?\}/gi;
    const match = regex.exec(formElement.get(propertyPath));
    if (match && ((match[1] && match[1] !== '__currentTimestamp' && !getFormEditorApp().isFormElementIdentifierUsed(match[1])) || !match[1])) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('FormElementIdentifierWithinCurlyBracesInclusive').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('FormElementIdentifierWithinCurlyBracesExclusive', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }

    const regex = /^\{([a-z0-9-_]+)?\}$/i;
    const match = regex.exec(formElement.get(propertyPath));
    if (!match || ((match[1] && match[1] !== '__currentTimestamp' && !getFormEditorApp().isFormElementIdentifierUsed(match[1])) || !match[1])) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('FormElementIdentifierWithinCurlyBracesInclusive').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('FileSize', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    if (!formElement.get(propertyPath).match(/^(\d*\.?\d+)(B|K|M|G)$/i)) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('FileSize').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('RFC3339FullDate', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    const value = formElement.get(propertyPath);
    if (!isAbsoluteDate(value) && !isRelativeDateExpression(value)) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('RFC3339FullDate').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('RFC3339FullDateOrEmpty', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    const value = formElement.get(propertyPath);
    if (value.length > 0 && !isAbsoluteDate(value) && !isRelativeDateExpression(value)) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('RFC3339FullDate').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('RegularExpressionPattern', function(formElement, propertyPath) {
    const value = formElement.get(propertyPath);
    let isValid = true;

    if (!getUtility().isNonEmptyString(value)) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('RegularExpressionPattern').errorMessage || 'invalid value';
    }

    try {
      const matches = value.match(/^\/(.*)\/[gmixsuUAJD]*$/);

      if (null !== matches) {
        new RegExp(matches[1]);
      } else {
        isValid = false;
      }
    } catch {
      isValid = false;
    }

    if (!isValid) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('RegularExpressionPattern').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('ItemCount', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    const value = formElement.get(propertyPath);
    const items: Array<string> = value.split(',').filter((str: string) => str !== '');

    const { minItems, maxItems } = resolveItemCountConstraints(formElement, propertyPath);

    if ((minItems > 0 && items.length < minItems) || (maxItems > 1 && items.length > maxItems)) {
      return getItemCountValidationError(minItems, maxItems);
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('IntegerList', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    const value = formElement.get(propertyPath);
    const items: Array<string> = value.split(',').filter((str: string) => str !== '');

    for (const item of items) {
      if (isNaN(Number(item))) {
        return getFormEditorApp().getFormElementPropertyValidatorDefinition('IntegerList').errorMessage || 'invalid value';
      }
    }
    return undefined;
  });
}

/**
 * Resolves the `minItems` / `maxItems` constraints of the inspector editor
 * that is bound to the given property path. Falls back to the framework
 * defaults (min 0, max 1) when the editor or the options are not defined.
 */
function resolveItemCountConstraints(
  formElement: FormElement,
  propertyPath: string
): { minItems: number, maxItems: number } {
  const editors: Array<EditorConfiguration> = getFormEditorApp().getFormElementDefinition(formElement, 'editors');
  const editorConfiguration = editors.find((configuration) => configuration.propertyPath === propertyPath);

  return {
    minItems: editorConfiguration?.minItems ?? 0,
    maxItems: editorConfiguration?.maxItems ?? 1,
  };
}

/**
 * Builds the localized "ItemCount" error message with the `{0}` / `{1}`
 * placeholders replaced by the resolved constraints.
 */
function getItemCountValidationError(minItems: number, maxItems: number): string {
  const errorMessage = getFormEditorApp().getFormElementPropertyValidatorDefinition('ItemCount').errorMessage;
  if (!errorMessage) {
    return 'invalid value';
  }
  return errorMessage.replace('{0}', String(minItems)).replace('{1}', String(maxItems));
}

/**
 * @publish view/ready
 * @throws 1475425785
 */
function loadAdditionalModules(_additionalViewModelModules: AdditionalViewModelModules | Record<string, JavaScriptItemPayload>): void {
  let additionalViewModelModules: AdditionalViewModelModules = [];
  if (typeof _additionalViewModelModules === 'object' && !Array.isArray(_additionalViewModelModules)) {
    for (const key of Object.keys(_additionalViewModelModules)) {
      additionalViewModelModules.push(_additionalViewModelModules[key]);
    }
  } else {
    additionalViewModelModules = _additionalViewModelModules as AdditionalViewModelModules;
  }

  if (!Array.isArray(additionalViewModelModules)) {
    getPublisherSubscriber().publish('view/ready');
    return;
  }
  const additionalViewModelModulesLength = additionalViewModelModules.length;

  if (additionalViewModelModulesLength > 0) {
    let loadedAdditionalViewModelModules = 0;
    for (let i = 0; i < additionalViewModelModulesLength; ++i) {
      loadModule(additionalViewModelModules[i]).then(function(additionalViewModelModule) {
        assert(
          typeof additionalViewModelModule.bootstrap === 'function',
          'The module "' + additionalViewModelModules[i].name + '" does not implement the method "bootstrap"',
          1475425785
        );
        additionalViewModelModule.bootstrap(getFormEditorApp());

        loadedAdditionalViewModelModules++;
        if (additionalViewModelModulesLength === loadedAdditionalViewModelModules) {
          getPublisherSubscriber().publish('view/ready');
        }
      });
    }
  } else {
    getPublisherSubscriber().publish('view/ready');
  }
}

/**
 * @throws 1478268639
 */
function structureComponentSetup(): void {
  assert(
    typeof TreeComponent.bootstrap === 'function',
    'The structure component does not implement the method "bootstrap"',
    1478268639
  );

  structureComponent = TreeComponent.bootstrap(
    getFormEditorApp(),
    document.querySelector<HTMLElement>(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
      getHelper().getDomElementDataAttributeValue('structure')
    ]))
  );

  const iconMailformEl = document.querySelector(
    getHelper().getDomElementDataIdentifierSelector('structureRootContainer')
  )?.querySelector(getHelper().getDomElementDataIdentifierSelector('iconMailform'));
  if (iconMailformEl) {
    iconMailformEl.setAttribute('title', 'identifier: ' + getRootFormElement().get('identifier'));
  }
}

/**
 * @throws 1478895106
 */
function modalsComponentSetup(): void {
  assert(
    typeof ModalsComponent.bootstrap === 'function',
    'The modals component does not implement the method "bootstrap"',
    1478895106
  );
  modalsComponent = ModalsComponent.bootstrap(getFormEditorApp());
}

/**
 * @throws 1478895106
 */
function inspectorsComponentSetup(): void {
  assert(
    typeof InspectorComponent.bootstrap === 'function',
    'The inspector component does not implement the method "bootstrap"',
    1478895106
  );
  inspectorsComponent = InspectorComponent.bootstrap(getFormEditorApp());
}

/**
 * @throws 1478986610
 */
function stageComponentSetup(): void {
  assert(
    typeof InspectorComponent.bootstrap === 'function',
    'The stage component does not implement the method "bootstrap"',
    1478986610
  );
  stageComponent = StageComponent.bootstrap(
    getFormEditorApp(),
    document.querySelector<HTMLElement>(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
      getHelper().getDomElementDataAttributeValue('stageArea')
    ]))
  );

  const stagePanelEl = getStage().getStagePanelDomElement();
  stagePanelEl?.addEventListener('click', function(e: MouseEvent) {
    const identifierAttr = getHelper().getDomElementDataAttribute('identifier');
    const target = e.target as Element;
    if (
      target.getAttribute(identifierAttr) === getHelper().getDomElementDataAttributeValue('stagePanelHeading')
      || target.getAttribute(identifierAttr) === getHelper().getDomElementDataAttributeValue('stageSection')
      || target.getAttribute(identifierAttr) === getHelper().getDomElementDataAttributeValue('stageArea')
    ) {
      selectPageBatch(getFormEditorApp().getCurrentlySelectedPageIndex());
    }
    getPublisherSubscriber().publish('view/stage/panel/clicked', []);
  });
}

/**
 * @publish view/header/button/save/clicked
 * @publish view/stage/abstract/button/newElement/clicked
 * @publish view/header/button/newPage/clicked
 * @publish view/structure/button/newPage/clicked
 * @publish view/header/button/close/clicked
 */
function buttonsSetup(): void {
  const qs = (id: string): HTMLElement | null => document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector(id));

  qs('buttonHeaderSave')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/header/button/save/clicked', []);
  });

  qs('buttonToggleStructure')?.addEventListener('click', function() {
    qs('structureSection')?.classList.toggle('formeditor-inspector-expanded');
  });

  qs('buttonExpandInspector')?.addEventListener('click', function() {
    qs('inspectorSection')?.classList.add('formeditor-inspector-expanded');
  });

  qs('buttonCollapseInspector')?.addEventListener('click', function() {
    qs('inspectorSection')?.classList.remove('formeditor-inspector-expanded');
  });

  qs('buttonFormSettings')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/header/formSettings/clicked', []);
  });

  qs('buttonNewPage')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/structure/button/newPage/clicked', ['view/insertPages/perform']);
  });

  qs('buttonHeaderClose')?.addEventListener('click', function(e) {
    if (!getFormEditorApp().getUnsavedContent()) {
      return;
    }
    e.preventDefault();
    getPublisherSubscriber().publish('view/header/button/close/clicked', []);
  });

  qs('buttonHeaderUndo')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/undoButton/clicked', []);
  });

  qs('buttonHeaderRedo')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/redoButton/clicked', []);
  });

  qs('buttonHeaderViewModeAbstract')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/viewModeButton/abstract/clicked', []);
  });

  qs('buttonHeaderViewModePreview')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/viewModeButton/preview/clicked', []);
  });

  qs('structureRootContainer')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/structure/root/selected');
  });

  qs('buttonHeaderPaginationNext')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/paginationNext/clicked', []);
  });

  qs('buttonHeaderPaginationPrevious')?.addEventListener('click', function() {
    getPublisherSubscriber().publish('view/paginationPrevious/clicked', []);
  });
}

/* *************************************************************
 * Public Methods
 * ************************************************************/

export function getFormEditorApp(): FormEditor {
  return formEditorApp;
}

export function getHelper(_configuration?: HelperConfiguration): typeof Helper {
  if (getUtility().isUndefinedOrNull(_configuration)) {
    return Helper.setConfiguration(configuration);
  }
  return Helper.setConfiguration(_configuration);
}

export function getFormElementDefinition<T extends keyof FormElementDefinition>(
  formElement: FormElement,
  formElementDefinitionKey?: T
): T extends keyof FormElementDefinition ? FormElementDefinition[T] : FormElementDefinition {
  return getFormEditorApp().getFormElementDefinition(formElement, formElementDefinitionKey);
}

export function getConfiguration(): Partial<HelperConfiguration> {
  return cloneDeep(configuration);
}

export function getPreviewMode(): boolean {
  return previewMode;
}

export function setPreviewMode(newPreviewMode: boolean): void {
  previewMode = !!newPreviewMode;
}

/* *************************************************************
 * Structure
 * ************************************************************/

export function getStructure(): typeof TreeComponent {
  return structureComponent;
}

/**
 * @publish view/structure/renew/postProcess
 */
export function renewStructure(): void {
  getStructure().renew();
  getPublisherSubscriber().publish('view/structure/renew/postProcess');
}

export function selectStructureNode(formElement?: FormElement): void {
  getStructure().selectTreeNode(formElement);
}

export function addStructureSelection(formElement?: FormElement): void {
  getStructure().getTreeNode(formElement)?.classList.add(getHelper().getDomElementClassName('selectedFormElement'));
}

/**
 * @todo deprecate, method is unused
 */
export function removeStructureSelection(formElement?: FormElement): void {
  getStructure().getTreeNode(formElement)?.classList.remove(getHelper().getDomElementClassName('selectedFormElement'));
}

export function removeAllStructureSelections(): void {
  const treeDom = getStructure().getTreeDomElement();
  if (treeDom) {
    treeDom.querySelectorAll(getHelper().getDomElementClassName('selectedFormElement', true))
      .forEach((el) => el.classList.remove(getHelper().getDomElementClassName('selectedFormElement')));
  }
}

export function getStructureRootContainer(): HTMLElement | null {
  return document.querySelector<HTMLElement>(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
    getHelper().getDomElementDataAttributeValue('structureRootContainer')
  ]));
}

export function getStructureRootElement(): HTMLElement | null {
  return document.querySelector<HTMLElement>(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
    getHelper().getDomElementDataAttributeValue('structureRootElement')
  ]));
}

export function removeStructureRootElementSelection(): void {
  getStructureRootContainer()?.classList.remove(getHelper().getDomElementClassName('selectedRootFormElement'));
}

export function addStructureRootElementSelection(): void {
  getStructureRootContainer()?.classList.add(getHelper().getDomElementClassName('selectedRootFormElement'));
}

export function setStructureRootElementTitle(title?: string): void {
  if (getUtility().isUndefinedOrNull(title)) {
    const span = document.createElement('span');
    span.textContent = getRootFormElement().get('label') ? getRootFormElement().get('label') : getRootFormElement().get('identifier');
    title = span.textContent;
  }
  const el = getStructureRootElement();
  if (el) {
    el.textContent = title;
  }
}

export function addStructureValidationResults(): void {
  getStructure().clearAllValidationErrors();

  const validationResults = getFormEditorApp().validateFormElementRecursive(getRootFormElement());
  for (let i = 0, len = validationResults.length; i < len; ++i) {
    let hasError = false;
    for (let j = 0, len2 = validationResults[i].validationResults.length; j < len2; ++j) {
      if (
        validationResults[i].validationResults[j].validationResults
        && validationResults[i].validationResults[j].validationResults.length > 0
      ) {
        hasError = true;
        break;
      }
    }

    if (hasError) {
      const identifierPath = validationResults[i].formElementIdentifierPath;

      // Set validation error on the tree node (adds CSS class + overlay icon)
      getStructure().setNodeValidationError(identifierPath, true);

      // Mark all parent nodes as having a child with error
      const pathParts = identifierPath.split('/');
      while (pathParts.pop()) {
        const parentPath = pathParts.join('/');
        if (parentPath) {
          getStructure().setNodeChildHasError(parentPath, true);
        }
      }
    }
  }
}

/* *************************************************************
 * Modals
 * ************************************************************/

export function getModals(): typeof ModalsComponent {
  return modalsComponent;
}

export function showRemoveFormElementModal(formElement?: FormElement): void {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }
  getModals().showRemoveFormElementModal(formElement);
}

export function showRemoveCollectionElementModal(
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions,
  formElement?: FormElement
): void {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }
  getModals().showRemoveCollectionElementModal(collectionElementIdentifier, collectionName, formElement);
}

export function showCloseConfirmationModal(): void {
  getModals().showCloseConfirmationModal();
}

export function showInsertElementsModal(
  targetEvent: keyof PublisherSubscriberTopicArgumentsMap,
  configuration: InsertElementsModalConfiguration
): void {
  getModals().showInsertElementsModal(targetEvent, configuration);
}

export function showInsertPagesModal(
  targetEvent: keyof PublisherSubscriberTopicArgumentsMap
): void {
  getModals().showInsertPagesModal(targetEvent);
}

export function showValidationErrorsModal(): void {
  const validationResults = getFormEditorApp().validateFormElementRecursive(getRootFormElement());

  getModals().showValidationErrorsModal(validationResults);
}

/* *************************************************************
 * Inspector
 * ************************************************************/

export function getInspector(): typeof InspectorComponent {
  return inspectorsComponent;
}

export function renderInspectorEditors(formElement?: FormElement | string): void {
  getInspector().renderEditors(formElement);
}

export function focusFirstInspectorInput(): void {
  const inspectorSection = document.querySelector(getHelper().getDomElementDataIdentifierSelector('inspectorSection'));
  if (inspectorSection) {
    const firstInput = inspectorSection.querySelector<HTMLElement>('input, select, textarea');
    firstInput?.focus();
  }
}

export function showInspectorSidebar(): void {
  document.querySelector(getHelper().getDomElementDataIdentifierSelector('inspectorSection'))?.classList.add('formeditor-inspector-expanded');
}

export function renderInspectorCollectionElementEditors(
  collectionName: keyof FormEditorDefinitions,
  collectionElementIdentifier: string
): void {
  getInspector().renderCollectionElementEditors(collectionName, collectionElementIdentifier);
}

/* *************************************************************
 * Stage
 * ************************************************************/

export function getStage(): typeof StageComponent {
  return stageComponent;
}

export function setStageHeadline(title?: string): void {
  getStage().setStageHeadline(title);
}

export function addStagePanelSelection(): void {
  getStage().getStagePanelDomElement()?.classList.add(getHelper().getDomElementClassName('selectedStagePanel'));
}

export function removeStagePanelSelection(): void {
  getStage().getStagePanelDomElement()?.classList.remove(getHelper().getDomElementClassName('selectedStagePanel'));
}

export function renderPagination(): void {
  getStage().renderPagination();
}

export function renderUndoRedo(): void {
  getStage().renderUndoRedo();
}

/**
 * @publish view/stage/abstract/render/postProcess
 * @publish view/stage/abstract/render/preProcess
 */
export function renderAbstractStageArea(): void {
  setButtonActive(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')));
  removeButtonActive(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModePreview')));

  document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('moduleWrapper'))
    ?.classList.add(getHelper().getDomElementClassName('viewModeAbstract'));
  document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('moduleWrapper'))
    ?.classList.remove(getHelper().getDomElementClassName('viewModePreview'));

  const render = (callback: () => void): void => {
    getStage().renderAbstractStageArea(undefined, callback);
  };

  const renderPostProcess = (): void => {
    const formElementTypeDefinition = getFormElementDefinition(getCurrentlySelectedFormElement(), undefined);
    getStage().getAllFormElementDomElements().forEach(function(el: HTMLElement) {
      el.addEventListener('mouseenter', function() {
        getStage().getAllFormElementDomElements().forEach((other: HTMLElement) => {
          other.parentElement?.classList.remove(getHelper().getDomElementClassName('sortableHover'));
        });
        if (
          el.parentElement?.classList.contains(getHelper().getDomElementClassName('formElementIsComposit'))
          && !el.parentElement?.classList.contains(getHelper().getDomElementClassName('formElementIsTopLevel'))
        ) {
          el.parentElement?.classList.add(getHelper().getDomElementClassName('sortableHover'));
        }
      });
    });

    if (
      formElementTypeDefinition._isTopLevelFormElement
      && !formElementTypeDefinition._isCompositeFormElement
      && !getFormEditorApp().isRootFormElementSelected()
    ) {
      // Non-composite top-level elements don't allow adding children
    }

    refreshSelectedElementItemsBatch();
    getPublisherSubscriber().publish('view/stage/abstract/render/postProcess');
  };

  render(function() {
    getPublisherSubscriber().publish('view/stage/abstract/render/preProcess');
    renderPostProcess();
    getPublisherSubscriber().publish('view/stage/abstract/render/postProcess');
  });
}

/**
 * @publish view/stage/preview/render/postProcess
 */
export function renderPreviewStageArea(html: string): void {
  setButtonActive(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModePreview')));
  removeButtonActive(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')));

  document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('moduleWrapper'))
    ?.classList.add(getHelper().getDomElementClassName('viewModePreview'));
  document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('moduleWrapper'))
    ?.classList.remove(getHelper().getDomElementClassName('viewModeAbstract'));

  getStage().renderPreviewStageArea(html);
  getPublisherSubscriber().publish('view/stage/preview/render/postProcess');
}

export function addAbstractViewValidationResults(): void {
  const validationResults = getFormEditorApp().validateFormElementRecursive(getRootFormElement());
  for (let i = 0, len = validationResults.length; i < len; ++i) {
    let hasError = false;
    for (let j = 0, len2 = validationResults[i].validationResults.length; j < len2; ++j) {
      if (
        validationResults[i].validationResults[j].validationResults
        && validationResults[i].validationResults[j].validationResults.length > 0
      ) {
        hasError = true;
        break;
      }
    }

    if (hasError) {
      if (i > 0) {
        const validationElement = getStage().getAbstractViewFormElementDomElement(validationResults[i].formElementIdentifierPath);

        // Set invalid property on Lit web component (FormElementStageItem)
        const stageItem = validationElement.querySelector('typo3-form-form-element-stage-item') as HTMLElement | null;
        if (stageItem && 'invalid' in stageItem) {
          (stageItem as any).invalid = true;
        }

        // Also set legacy CSS class for backward compatibility (legacy templates)
        setElementValidationErrorClass(validationElement);
      }
    }
  }
}

/* *************************************************************
 * Form element methods
 * ************************************************************/

/**
 * @publish view/formElement/inserted
 */
export function createAndAddFormElement(
  formElementType: string,
  referenceFormElement?: FormElement | string,
  disablePublishersOnSet?: boolean
): FormElement {
  const newFormElement = getFormEditorApp().createAndAddFormElement(formElementType, referenceFormElement);
  if (!disablePublishersOnSet) {
    getPublisherSubscriber().publish('view/formElement/inserted', [newFormElement]);
  }
  return newFormElement;
}

/**
 * @publish view/formElement/moved
 */
export function moveFormElement(
  formElementToMove: FormElement | string,
  position: string,
  referenceFormElement: FormElement | string,
  disablePublishersOnSet?: boolean
): FormElement {
  const movedFormElement = getFormEditorApp().moveFormElement(formElementToMove, position, referenceFormElement, false);
  if (!disablePublishersOnSet) {
    getPublisherSubscriber().publish('view/formElement/moved', [movedFormElement]);
  }
  return movedFormElement;
}

/**
 * @publish view/formElement/removed
 */
export function removeFormElement(formElement: FormElement, disablePublishersOnSet?: boolean): FormElement {
  let parentFormElement;

  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }

  if (
    getFormElementDefinition(formElement, '_isTopLevelFormElement')
    && getFormElementDefinition(formElement, '_isCompositeFormElement')
    && getRootFormElement().get('renderables').length === 1
  ) {
    Notification.error(
      getFormElementDefinition(getRootFormElement(), 'modalRemoveElementLastAvailablePageFlashMessageTitle'),
      getFormElementDefinition(getRootFormElement(), 'modalRemoveElementLastAvailablePageFlashMessageMessage'),
      2
    );
  } else {
    parentFormElement = getFormEditorApp().removeFormElement(formElement, false);
    if (!disablePublishersOnSet) {
      getPublisherSubscriber().publish('view/formElement/removed', [parentFormElement]);
    }
  }
  return parentFormElement;
}

/**
 * @publish view/collectionElement/new/added
 */
export function createAndAddPropertyCollectionElement(
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions,
  formElement?: FormElement,
  collectionElementConfiguration?: CollectionElementConfiguration,
  referenceCollectionElementIdentifier?: string,
  disablePublishersOnSet?: boolean
): void {
  getFormEditorApp().createAndAddPropertyCollectionElement(
    collectionElementIdentifier,
    collectionName,
    formElement,
    collectionElementConfiguration,
    referenceCollectionElementIdentifier
  );
  if (!disablePublishersOnSet) {
    getPublisherSubscriber().publish('view/collectionElement/new/added', [
      collectionElementIdentifier,
      collectionName,
      formElement,
      collectionElementConfiguration,
      referenceCollectionElementIdentifier
    ]);
  }
}

export function movePropertyCollectionElement(
  collectionElementToMove: string,
  position: string,
  referenceCollectionElement: string,
  collectionName: keyof FormEditorDefinitions,
  formElement?: FormElement,
  disablePublishersOnSet?: boolean
): void {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }
  getFormEditorApp().movePropertyCollectionElement(
    collectionElementToMove,
    position,
    referenceCollectionElement,
    collectionName,
    formElement,
    false
  );
  if (!disablePublishersOnSet) {
    getPublisherSubscriber().publish('view/collectionElement/moved', [
      collectionElementToMove,
      position,
      referenceCollectionElement,
      collectionName,
      formElement
    ]);
  }
}

/**
 * @publish view/collectionElement/removed
 */
export function removePropertyCollectionElement(
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions,
  formElement?: FormElement,
  disablePublishersOnSet?: boolean
): void {
  let propertyData, propertyPath;

  getFormEditorApp().removePropertyCollectionElement(collectionElementIdentifier, collectionName, formElement);

  const collectionElementConfiguration = getFormEditorApp().getPropertyCollectionElementConfiguration(
    collectionElementIdentifier,
    collectionName
  );
  if (Array.isArray(collectionElementConfiguration.editors)) {
    for (let i = 0, len1 = collectionElementConfiguration.editors.length; i < len1; ++i) {
      if (Array.isArray(collectionElementConfiguration.editors[i].additionalElementPropertyPaths)) {
        for (let j = 0, len2 = collectionElementConfiguration.editors[i].additionalElementPropertyPaths.length; j < len2; ++j) {
          getCurrentlySelectedFormElement().unset(collectionElementConfiguration.editors[i].additionalElementPropertyPaths[j], true);
        }
      } else if (collectionElementConfiguration.editors[i].identifier === 'validationErrorMessage') {
        propertyPath = getFormEditorApp().buildPropertyPath(
          collectionElementConfiguration.editors[i].propertyPath
        );
        propertyData = getCurrentlySelectedFormElement().get(propertyPath);
        if (!getUtility().isUndefinedOrNull(propertyData)) {
          for (let j = 0, len2 = collectionElementConfiguration.editors[i].errorCodes.length; j < len2; ++j) {
            for (let k = 0, len3 = propertyData.length; k < len3; ++k) {
              if (parseInt(collectionElementConfiguration.editors[i].errorCodes[j], 10) === parseInt(propertyData[k].code, 10)) {
                propertyData.splice(k, 1);
                --len3;
              }
            }
          }
          getCurrentlySelectedFormElement().set(propertyPath, propertyData);
        }
      }
    }
  }

  if (!disablePublishersOnSet) {
    getPublisherSubscriber().publish('view/collectionElement/removed', [
      collectionElementIdentifier,
      collectionName,
      formElement
    ]);
  }
}

/* *************************************************************
 * Batch methods
 * ************************************************************/

export function refreshSelectedElementItemsBatch(): void {
  const formElementTypeDefinition = getFormElementDefinition(getCurrentlySelectedFormElement(), undefined);

  removeAllStageElementSelectionsBatch();
  removeAllStructureSelections();

  if (!getFormEditorApp().isRootFormElementSelected()) {
    removeStructureRootElementSelection();
    addStructureSelection();

    const selectedElement = getStage().getAbstractViewFormElementDomElement();

    if (formElementTypeDefinition._isTopLevelFormElement) {
      addStagePanelSelection();
    } else {
      selectedElement?.classList.add(getHelper().getDomElementClassName('selectedFormElement'));
      getStage().createAndAddAbstractViewFormElementToolbar(selectedElement, undefined);
    }

    getStage().getAllFormElementDomElements().forEach((el: HTMLElement) => {
      el.parentElement?.classList.remove(getHelper().getDomElementClassName('selectedCompositFormElement'));
    });
    if (!formElementTypeDefinition._isTopLevelFormElement && formElementTypeDefinition._isCompositeFormElement) {
      selectedElement?.parentElement?.classList.add(getHelper().getDomElementClassName('selectedCompositFormElement'));
    }
  }
}

/**
 * @throws 1478651732
 * @throws 1478651733
 * @throws 1478651734
 */
export function selectPageBatch(pageIndex: number): void {
  assert(typeof pageIndex === 'number', 'Invalid parameter "pageIndex"', 1478651732);
  assert(pageIndex >= 0, 'Invalid parameter "pageIndex"', 1478651733);
  assert(pageIndex < getRootFormElement().get('renderables').length, 'Invalid parameter "pageIndex"', 1478651734);

  getFormEditorApp().setCurrentlySelectedFormElement(getRootFormElement().get('renderables')[pageIndex]);
  renewStructure();
  renderPagination();
  refreshSelectedElementItemsBatch();
  renderInspectorEditors();
}

export function removeAllStageElementSelectionsBatch(): void {
  getStage().getAllFormElementDomElements().forEach((el: HTMLElement) => el.classList.remove(getHelper().getDomElementClassName('selectedFormElement')));
  removeStagePanelSelection();
  getStage().getAllFormElementDomElements().forEach((el: HTMLElement) => el.parentElement?.classList.remove(getHelper().getDomElementClassName('sortableHover')));
}

export function onViewReadyBatch(): void {

  setStageHeadline();
  setStructureRootElementTitle();
  renderAbstractStageArea();
  renewStructure();
  addStructureRootElementSelection();
  renderInspectorEditors();
  renderPagination();

  hideComponent(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('moduleLoadingIndicator')));
  showComponent(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('moduleWrapper')));
  showComponent(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('inspectorSection')));
  showComponent(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderSave')));
  showComponent(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderClose')));
  showComponent(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')));
  showComponent(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')));
  setButtonActive(document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')));
}

export function onAbstractViewDndStartBatch(
  draggedFormElementDomElement: HTMLElement,
  draggedFormPlaceholderDomElement: HTMLElement
): void {
  draggedFormPlaceholderDomElement?.classList.remove(getHelper().getDomElementClassName('sortableHover'));
}

export function onAbstractViewDndChangeBatch(
  placeholderDomElement: HTMLElement,
  parentFormElementIdentifierPath: string,
  enclosingCompositeFormElement?: FormElement | string
): void {
  getStage().getAllFormElementDomElements().forEach((el: HTMLElement) => {
    el.parentElement?.classList.remove(getHelper().getDomElementClassName('sortableHover'));
  });
  if (enclosingCompositeFormElement) {
    getStage()
      .getAbstractViewParentFormElementWithinDomElement(placeholderDomElement)
      ?.parentElement?.classList.add(getHelper().getDomElementClassName('sortableHover'));
  }
}

/**
 * @throws 1472502237
 */
export function onAbstractViewDndUpdateBatch(
  movedDomElement: HTMLElement,
  movedFormElementIdentifierPath: string,
  previousFormElementIdentifierPath: string,
  nextFormElementIdentifierPath: string
): void {
  let movedFormElement, parentFormElementIdentifierPath;
  if (nextFormElementIdentifierPath) {
    movedFormElement = moveFormElement(movedFormElementIdentifierPath, 'before', nextFormElementIdentifierPath);
  } else if (previousFormElementIdentifierPath) {
    movedFormElement = moveFormElement(movedFormElementIdentifierPath, 'after', previousFormElementIdentifierPath);
  } else {
    parentFormElementIdentifierPath = getStage().getAbstractViewParentFormElementIdentifierPathWithinDomElement(movedDomElement);
    if (parentFormElementIdentifierPath) {
      movedFormElement = moveFormElement(movedFormElementIdentifierPath, 'inside', parentFormElementIdentifierPath);
    } else {
      assert(false, 'Next element, previous or parent element need to be set.', 1472502237);
    }
  }

  getStage()
    .getAbstractViewFormElementWithinDomElement(movedDomElement)
    ?.setAttribute(
      getHelper().getDomElementDataAttribute('elementIdentifier'),
      movedFormElement.get('__identifierPath')
    );
}

export function onStructureDndChangeBatch(
  placeholderDomElement: HTMLElement | null,
  parentFormElementIdentifierPath: string,
  enclosingCompositeFormElement?: FormElement | string
): void {
  getStructure()
    .getAllTreeNodes()
    .forEach((node) => node.parentElement?.classList.remove(getHelper().getDomElementClassName('sortableHover')));

  getStage()
    .getAllFormElementDomElements()
    .forEach((el: HTMLElement) => el.parentElement?.classList.remove(getHelper().getDomElementClassName('sortableHover')));

  if (enclosingCompositeFormElement) {
    getStructure()
      .getParentTreeNodeWithinDomElement(placeholderDomElement)
      ?.parentElement?.classList.add(getHelper().getDomElementClassName('sortableHover'));

    getStage()
      .getAbstractViewFormElementDomElement(enclosingCompositeFormElement)
      ?.parentElement?.classList.add(getHelper().getDomElementClassName('sortableHover'));
  }
}

/**
 * @throws 1479048646
 */
export function onStructureDndUpdateBatch(
  movedDomElement: HTMLElement | null,
  movedFormElementIdentifierPath: string,
  previousFormElementIdentifierPath: string,
  nextFormElementIdentifierPath: string
): void {
  let movedFormElement, parentFormElementIdentifierPath;
  if (nextFormElementIdentifierPath) {
    movedFormElement = moveFormElement(movedFormElementIdentifierPath, 'before', nextFormElementIdentifierPath);
  } else if (previousFormElementIdentifierPath) {
    movedFormElement = moveFormElement(movedFormElementIdentifierPath, 'after', previousFormElementIdentifierPath);
  } else {
    parentFormElementIdentifierPath = getStructure().getParentTreeNodeIdentifierPathWithinDomElement(movedDomElement);
    if (parentFormElementIdentifierPath) {
      movedFormElement = moveFormElement(movedFormElementIdentifierPath, 'inside', parentFormElementIdentifierPath);
    } else {
      getFormEditorApp().assert(false, 'Next element, previous or parent element need to be set.', 1479048646);
    }
  }

  getStructure()
    .getTreeNodeWithinDomElement(movedDomElement)
    ?.setAttribute(
      getHelper().getDomElementDataAttribute('elementIdentifier'),
      movedFormElement.get('__identifierPath')
    );
}

/* *************************************************************
 * Misc
 * ************************************************************/

export function closeEditor(): void {
  const el = document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('buttonHeaderClose'));
  document.location.href = (el as HTMLAnchorElement)?.href ?? '';
}

export function setElementValidationErrorClass(element: HTMLElement | null, classIdentifier?: string): void {
  if (getFormEditorApp().getUtility().isUndefinedOrNull(classIdentifier)) {
    element?.classList.replace('panel-default', 'panel-danger');
  } else {
    element?.classList.add(getHelper().getDomElementClassName(classIdentifier));
  }
}

export function removeElementValidationErrorClass(element: HTMLElement | null, classIdentifier?: string): void {
  if (getFormEditorApp().getUtility().isUndefinedOrNull(classIdentifier)) {
    element?.classList.replace('panel-danger', 'panel-default');
  } else {
    element?.classList.remove(getHelper().getDomElementClassName(classIdentifier));
  }
}

export function showComponent(element: HTMLElement | null): void {
  element?.classList.remove(getHelper().getDomElementClassName('hidden'));
  if (element) { element.style.display = ''; }
}

export function hideComponent(element: HTMLElement | null): void {
  element?.classList.add(getHelper().getDomElementClassName('hidden'));
  if (element) { element.style.display = 'none'; }
}

export function enableButton(buttonElement: HTMLElement | null): void {
  if (buttonElement) { (buttonElement as HTMLButtonElement).disabled = false; }
  buttonElement?.classList.remove(getHelper().getDomElementClassName('disabled'));
}

export function disableButton(buttonElement: HTMLElement | null): void {
  if (buttonElement) { (buttonElement as HTMLButtonElement).disabled = true; }
  buttonElement?.classList.add(getHelper().getDomElementClassName('disabled'));
}

export function setButtonActive(buttonElement: HTMLElement | null): void {
  buttonElement?.classList.add(getHelper().getDomElementClassName('active'));
}

export function removeButtonActive(buttonElement: HTMLElement | null): void {
  buttonElement?.classList.remove(getHelper().getDomElementClassName('active'));
}

export function showSaveButtonSpinnerIcon(): void {
  Icons.getIcon(getHelper().getDomElementDataAttributeValue('iconSaveSpinner'), Icons.sizes.small).then(function(markup) {
    const target = document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('iconSave'));
    if (target) {
      const tmp = document.createElement('div');
      tmp.innerHTML = markup;
      target.replaceWith(tmp.firstElementChild ?? tmp);
    }
  });
}

export function showSaveButtonSaveIcon(): void {
  Icons.getIcon(getHelper().getDomElementDataAttributeValue('iconSave'), Icons.sizes.small).then(function(markup) {
    const target = document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector('iconSaveSpinner'));
    if (target) {
      const tmp = document.createElement('div');
      tmp.innerHTML = markup;
      target.replaceWith(tmp.firstElementChild ?? tmp);
    }
  });
}

export function showSaveSuccessMessage(): void {
  Notification.success(
    getFormElementDefinition(getRootFormElement(), 'saveSuccessFlashMessageTitle'),
    getFormElementDefinition(getRootFormElement(), 'saveSuccessFlashMessageMessage'),
    2
  );
}

export function showSaveErrorMessage(response: { message: string }): void {
  Notification.error(
    getFormElementDefinition(getRootFormElement(), 'saveErrorFlashMessageTitle'),
    getFormElementDefinition(getRootFormElement(), 'saveErrorFlashMessageMessage') +
    ' ' +
    response.message
  );
}

export function showErrorFlashMessage(title: string, message: string): void {
  Notification.error(title, message, 2);
}

export function bootstrap(_formEditorApp: FormEditor, additionalViewModelModules: AdditionalViewModelModules): void {
  formEditorApp = _formEditorApp;

  Helper.bootstrap(formEditorApp);
  structureComponentSetup();
  modalsComponentSetup();
  inspectorsComponentSetup();
  stageComponentSetup();
  buttonsSetup();
  addPropertyValidators();
  loadAdditionalModules(additionalViewModelModules);
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'view/collectionElement/new/added': readonly [
      collectionElementIdentifier: string,
      collectionName: keyof FormEditorDefinitions,
      formElement: FormElement,
      collectionElementConfiguration: CollectionElementConfiguration,
      referenceCollectionElementIdentifier: string
    ];
    'view/collectionElement/moved': readonly [
      collectionElementToMove: string,
      position: string,
      referenceCollectionElement: string,
      collectionName: keyof FormEditorDefinitions,
      formElement: FormElement
    ];
    'view/collectionElement/removed': readonly [
      collectionElementIdentifier: string,
      collectionName: keyof FormEditorDefinitions,
      formElement: FormElement
    ];
    'view/formElement/inserted': [
      newFormElement: FormElement
    ];
    'view/formElement/moved': readonly [
      movedFormElement: FormElement
    ];
    'view/formElement/removed': readonly [
      parentFormElement: FormElement
    ];
    'view/header/button/save/clicked': readonly [];
    'view/header/button/close/clicked': readonly [];
    'view/header/button/newPage/clicked': readonly [
      targetEvent: 'view/insertPages/perform'
    ];
    'view/header/formSettings/clicked': readonly [];
    // triggered by 'view/stage/abstract/button/newElement/clicked'
    // ModalComponent.insertElementsModalSetup()
    'view/insertElements/perform/bottom': readonly [
      formElementType: string,
    ];
    // triggered by 'view/header/button/newPage/clicked' via
    // ModalComponent.insertElementsModalSetup()
    'view/insertPages/perform': readonly [
      formElementType: string,
    ];
    'view/paginationNext/clicked': readonly [];
    'view/paginationPrevious/clicked': readonly [];
    'view/ready': undefined;
    'view/redoButton/clicked': readonly [];
    'view/stage/abstract/button/newElement/clicked': readonly [
      targetEvent: 'view/insertElements/perform/bottom',
      // @todo modalConfiguration is never published, but used by
      // mediator in subscribe('view/stage/abstract/button/newElement/clicked', …)
      // Can this be removed or is it possibly used by extensions?
      modalConfiguration?: InsertElementsModalConfiguration
    ];
    'view/stage/abstract/render/postProcess': undefined,
    'view/stage/abstract/render/preProcess': undefined,
    'view/stage/panel/clicked': readonly [];
    'view/stage/preview/render/postProcess': undefined;
    'view/structure/button/newPage/clicked': readonly [
      targetEvent: 'view/insertPages/perform'
    ];
    'view/structure/renew/postProcess': undefined;
    'view/structure/root/selected': undefined;
    'view/undoButton/clicked': readonly [];
    'view/viewModeButton/abstract/clicked': readonly [];
    'view/viewModeButton/preview/clicked': readonly [];
  }
}
