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
 * Module: @typo3/form/backend/form-editor/inspector-component
 */

import type { Configuration as HelperConfiguration } from '@typo3/form/backend/form-editor/helper';
import * as Helper from '@typo3/form/backend/form-editor/helper';
import { merge } from 'lodash-es';
import Icons from '@typo3/backend/icons';
import Modal from '@typo3/backend/modal';
import { MessageUtility } from '@typo3/backend/utility/message-utility';
import Sortable from 'sortablejs';
import {
  type PropertyGridEditorEntry,
  PropertyGridEditorUpdateEvent
} from '@typo3/form/backend/form-editor/component/property-grid-editor';
const ckeditor = await import('@typo3/rte-ckeditor/ckeditor5').catch((): null => null);
import '@typo3/form/backend/form-editor/component/date-editor';
import {
  DateEditorChangeEvent
} from '@typo3/form/backend/form-editor/component/date-editor';

import type { FormEditor } from '@typo3/form/backend/form-editor';
import type {
  EditorConfiguration,
  FormEditorDefinitions,
  FormElement,
  FormElementDefinition,
  PublisherSubscriber,
  Utility,
} from '@typo3/form/backend/form-editor/core';
import {
  type FormElementSelectorEntry,
  FormElementSelectorSelectedEvent
} from '@typo3/form/backend/form-editor/component/form-element-selector';

type ViewModel = typeof import('./view-model');

interface Configuration extends Partial<HelperConfiguration> {
  isSortable: boolean,
}

type PropertyData = Array<{code: string, message: string}>;

const defaultConfiguration: Configuration = {
  domElementClassNames: {
    buttonFormElementRemove: 'formeditor-inspector-element-remove-button',
    collectionElement: 'formeditor-inspector-collection-element',
    finisherEditorPrefix: 't3-form-inspector-finishers-editor-',
    inspectorEditor: 'formeditor-inspector-element',
    inspectorInputGroup: 'input-group',
    sortable: 'sortable',
    validatorEditorPrefix: 'formeditor-inspector-validators-editor-'
  },
  domElementDataAttributeNames: {
    contentElementSelectorTarget: 'data-insert-target',
    finisher: 'data-finisher-identifier',
    validator: 'data-validator-identifier',
    randomId: 'data-random-id',
    randomIdTarget: 'data-random-id-attribute',
    randomIdIndex: 'data-random-id-number',
    maximumFileSize: 'data-maximumFileSize'
  },
  domElementDataAttributeValues: {
    collapse: 'actions-view-table-expand',
    editorControlsInputGroup: 'inspectorEditorControlsGroup',
    editorWrapper: 'editorWrapper',
    editorControlsWrapper: 'inspectorEditorControlsWrapper',
    formElementHeaderEditor: 'inspectorFormElementHeaderEditor',
    formElementSelectorControlsWrapper: 'inspectorEditorFormElementSelectorControlsWrapper',
    formElementSelectorSplitButtonContainer: 'inspectorEditorFormElementSelectorSplitButtonContainer',
    formElementSelectorSplitButtonListContainer: 'inspectorEditorFormElementSelectorSplitButtonListContainer',
    iconNotAvailable: 'actions-close',
    inspector: 'inspector',
    'Inspector-CheckboxEditor': 'Inspector-CheckboxEditor',
    'Inspector-CollectionElementHeaderEditor': 'Inspector-CollectionElementHeaderEditor',
    'Inspector-FinishersEditor': 'Inspector-FinishersEditor',
    'Inspector-FormElementHeaderEditor': 'Inspector-FormElementHeaderEditor',
    'Inspector-PropertyGridEditor': 'Inspector-PropertyGridEditor',
    'Inspector-RemoveElementEditor': 'Inspector-RemoveElementEditor',
    'Inspector-RequiredValidatorEditor': 'Inspector-RequiredValidatorEditor',
    'Inspector-SingleSelectEditor': 'Inspector-SingleSelectEditor',
    'Inspector-MultiSelectEditor': 'Inspector-MultiSelectEditor',
    'Inspector-GridColumnViewPortConfigurationEditor': 'Inspector-GridColumnViewPortConfigurationEditor',
    'Inspector-TextareaEditor': 'Inspector-TextareaEditor',
    'Inspector-TextEditor': 'Inspector-TextEditor',
    'Inspector-Typo3WinBrowserEditor': 'Inspector-Typo3WinBrowserEditor',
    'Inspector-ValidatorsEditor': 'Inspector-ValidatorsEditor',
    'Inspector-ValidationErrorMessageEditor': 'Inspector-ValidationErrorMessageEditor',
    'Inspector-DateEditor': 'Inspector-DateEditor',

    inspectorFinishers: 'inspectorFinishers',
    inspectorValidators: 'inspectorValidators',
    viewportButton: 'viewportButton'
  },
  domElementIdNames: {
    finisherPrefix: 't3-form-inspector-finishers-',
    validatorPrefix: 't3-form-inspector-validators-'
  },
  isSortable: true
};

let configuration: Configuration = null;

let formEditorApp: FormEditor = null;

function getFormEditorApp(): FormEditor {
  return formEditorApp;
}

function getViewModel(): ViewModel {
  return getFormEditorApp().getViewModel();
}

function getHelper(_configuration?: HelperConfiguration): typeof Helper {
  if (getUtility().isUndefinedOrNull(_configuration)) {
    return Helper.setConfiguration(configuration);
  }
  return Helper.setConfiguration(_configuration);
}

function getUtility(): Utility {
  return getFormEditorApp().getUtility();
}

function assert(test: boolean|(() => boolean), message: string, messageCode: number): void {
  return getFormEditorApp().assert(test, message, messageCode);
}

function getRootFormElement(): FormElement {
  return getFormEditorApp().getRootFormElement();
}

function getCurrentlySelectedFormElement(): FormElement {
  return getFormEditorApp().getCurrentlySelectedFormElement();
}

function getPublisherSubscriber(): PublisherSubscriber {
  return getFormEditorApp().getPublisherSubscriber();
}

function getFormElementDefinition<T extends keyof FormElementDefinition>(
  formElement: FormElement | string,
  formElementDefinitionKey?: T
): T extends keyof FormElementDefinition ? FormElementDefinition[T] : FormElementDefinition {
  return getFormEditorApp().getFormElementDefinition(formElement, formElementDefinitionKey);
}

/**
 * @publish view/inspector/editor/insert/perform
 */
function renderEditorDispatcher(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier?: string,
  collectionName?: keyof FormEditorDefinitions
): void {
  switch (editorConfiguration.templateName) {
    case 'Inspector-FormElementHeaderEditor':
      renderFormElementHeaderEditor(
        editorConfiguration,
        editorHtml
      );
      break;
    case 'Inspector-CollectionElementHeaderEditor':
      renderCollectionElementHeaderEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-MaximumFileSizeEditor':
      renderFileMaxSizeEditor(
        editorConfiguration,
        editorHtml
      );
      break;
    case 'Inspector-TextEditor':
      renderTextEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-FinishersEditor':
      renderCollectionElementSelectionEditor(
        'finishers',
        editorConfiguration,
        editorHtml
      );
      break;
    case 'Inspector-ValidatorsEditor':
      renderCollectionElementSelectionEditor(
        'validators',
        editorConfiguration,
        editorHtml
      );
      break;
    case 'Inspector-ValidationErrorMessageEditor':
      renderValidationErrorMessageEditor(
        editorConfiguration,
        editorHtml
      );
      break;
    case 'Inspector-RemoveElementEditor':
      renderRemoveElementEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-RequiredValidatorEditor':
      renderRequiredValidatorEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-CheckboxEditor':
      renderCheckboxEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-CountrySelectEditor':
      renderCountrySelectEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-SingleSelectEditor':
      renderSingleSelectEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-MultiSelectEditor':
      renderMultiSelectEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-GridColumnViewPortConfigurationEditor':
      renderGridColumnViewPortConfigurationEditor(
        editorConfiguration,
        editorHtml
      );
      break;
    case 'Inspector-PropertyGridEditor':
      renderPropertyGridEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-TextareaEditor':
      renderTextareaEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-Typo3WinBrowserEditor':
      renderTypo3WinBrowserEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    case 'Inspector-DateEditor':
      renderDateEditor(
        editorConfiguration,
        editorHtml,
        collectionElementIdentifier,
        collectionName
      );
      break;
    default:
      break;
  }
  getPublisherSubscriber().publish('view/inspector/editor/insert/perform', [
    editorConfiguration, editorHtml, collectionElementIdentifier, collectionName
  ]);
}

/**
 * opens a popup window with the element browser
 */
function openTypo3WinBrowser(mode: string, fieldReference: string, allowedTypes: string): void {
  const queryParams = new URLSearchParams({
    mode: mode,
    fieldReference: fieldReference,
    allowedTypes: allowedTypes,
  });
  Modal.advanced({
    type: Modal.types.iframe,
    content: TYPO3.settings.FormEditor.typo3WinBrowserUrl + '&' + queryParams.toString(),
    size: Modal.sizes.large
  });
}

let elementBrowserListenerRegistered = false;

/**
 * Listens on messages sent by ElementBrowser – registers only once
 */
function listenOnElementBrowser(): void {
  if (elementBrowserListenerRegistered) {
    return;
  }
  elementBrowserListenerRegistered = true;

  window.addEventListener('message', function (e) {
    if (!MessageUtility.verifyOrigin(e.origin)) {
      throw 'Denied message sent by ' + e.origin;
    }

    if (e.data.actionName === 'typo3:elementBrowser:elementAdded') {
      if (typeof e.data.fieldName === 'undefined') {
        throw 'fieldName not defined in message';
      }

      if (typeof e.data.value === 'undefined') {
        throw 'value not defined in message';
      }

      const result = e.data.value.split('_');
      const targetEl = document.querySelector<HTMLInputElement>(
        getHelper().getDomElementDataAttribute('contentElementSelectorTarget', 'bracesWithKeyValue', [e.data.fieldName])
      );
      if (targetEl) {
        targetEl.value = result.pop() ?? '';
        targetEl.dispatchEvent(new Event('paste'));
      }
    }
  });
}

function getCollectionElementClass(
  collectionName: keyof FormEditorDefinitions,
  collectionElementIdentifier: string
): string {
  if (collectionName === 'finishers') {
    return getHelper()
      .getDomElementClassName('finisherEditorPrefix') + collectionElementIdentifier;
  } else {
    return getHelper()
      .getDomElementClassName('validatorEditorPrefix') + collectionElementIdentifier;
  }
}

function getCollectionElementId(
  collectionName: keyof FormEditorDefinitions,
  collectionElementIdentifier: string,
  asSelector?: boolean
): string {
  if (collectionName === 'finishers') {
    return getHelper()
      .getDomElementIdName('finisherPrefix', asSelector) + collectionElementIdentifier;
  } else {
    return getHelper()
      .getDomElementIdName('validatorPrefix', asSelector) + collectionElementIdentifier;
  }
}

function addSortableCollectionElementsEvents(
  sortableDomElement: HTMLElement,
  collectionName: keyof FormEditorDefinitions,
): void {
  sortableDomElement.classList.add('sortable');
  new Sortable(sortableDomElement, {
    draggable: getHelper().getDomElementClassName('collectionElement', true),
    filter: 'input,textarea,select',
    preventOnFilter: false,
    animation: 200,
    fallbackTolerance: 200,
    swapThreshold: 0.6,
    dragClass: 'formeditor-sortable-drag',
    ghostClass: 'formeditor-sortable-ghost',
    onEnd: function (e) {
      let dataAttributeName;

      if (collectionName === 'finishers') {
        dataAttributeName = getHelper().getDomElementDataAttribute('finisher');
      } else {
        dataAttributeName = getHelper().getDomElementDataAttribute('validator');
      }

      const movedCollectionElementIdentifier = e.item.getAttribute(dataAttributeName);
      const previousCollectionElementIdentifier = e.item
        .previousElementSibling?.closest(getHelper().getDomElementClassName('collectionElement', true))
        ?.getAttribute(dataAttributeName);
      const nextCollectionElementIdentifier = e.item
        .nextElementSibling?.closest(getHelper().getDomElementClassName('collectionElement', true))
        ?.getAttribute(dataAttributeName);

      getPublisherSubscriber().publish('view/inspector/collectionElements/dnd/update', [
        movedCollectionElementIdentifier,
        previousCollectionElementIdentifier,
        nextCollectionElementIdentifier,
        collectionName
      ]);
    }
  });
}

function getEditorWrapperDomElement(editorDomElement: HTMLElement): HTMLElement | null {
  return (editorDomElement).querySelector(getHelper().getDomElementDataIdentifierSelector('editorWrapper'));
}

function getEditorControlsWrapperDomElement(editorDomElement: HTMLElement): HTMLElement | null {
  return (editorDomElement).querySelector(getHelper().getDomElementDataIdentifierSelector('editorControlsWrapper'));
}

function validateCollectionElement(propertyPath: string, editorHtml: HTMLElement): void {
  let hasError, propertyPrefix, validationResults;

  validationResults = getFormEditorApp().validateCurrentlySelectedFormElementProperty(propertyPath);

  const controlsWrapper = getEditorControlsWrapperDomElement(editorHtml);
  const collectionElement = controlsWrapper?.closest<HTMLElement>(getHelper().getDomElementClassName('collectionElement', true)) ?? null;

  const validationErrorsElement = getHelper().getTemplatePropertyElement('validationErrors', editorHtml);
  const inputElement = getEditorControlsWrapperDomElement(editorHtml)?.querySelector<HTMLElement>('input, textarea, select, button') ?? null;

  if (validationResults.length > 0) {
    // Generate a unique ID for the error message to link via aria-describedby
    let errorId = validationErrorsElement?.id ?? '';
    if (!errorId) {
      errorId = 'validation-error-' + Math.random().toString(36).substring(2, 9);
      if (validationErrorsElement) {
        validationErrorsElement.id = errorId;
      }
    }

    if (validationErrorsElement) {
      validationErrorsElement.innerHTML =
        '<span class="text-danger">' +
        '<typo3-backend-icon identifier="actions-exclamation-circle" size="small"></typo3-backend-icon> ' +
        validationResults[0] +
        '</span>';
      validationErrorsElement.setAttribute('role', 'alert');
    }

    if (inputElement) {
      inputElement.setAttribute('aria-invalid', 'true');
      inputElement.setAttribute('aria-describedby', errorId);
    }

    getViewModel().setElementValidationErrorClass(
      getEditorControlsWrapperDomElement(editorHtml),
      'hasError'
    );
  } else {
    if (validationErrorsElement) {
      validationErrorsElement.innerHTML = '';
      validationErrorsElement.removeAttribute('role');
    }

    // Remove aria attributes from input
    if (inputElement) {
      inputElement.removeAttribute('aria-invalid');
      inputElement.removeAttribute('aria-describedby');
    }

    getViewModel().removeElementValidationErrorClass(
      getEditorControlsWrapperDomElement(editorHtml),
      'hasError'
    );
  }

  validationResults = getFormEditorApp().validateFormElement(getCurrentlySelectedFormElement());
  propertyPrefix = propertyPath.split('.');
  propertyPrefix = propertyPrefix[0] + '.' + propertyPrefix[1];

  hasError = false;
  for (let i = 0, len = validationResults.length; i < len; ++i) {
    if (
      validationResults[i].propertyPath.indexOf(propertyPrefix, 0) === 0
      && validationResults[i].validationResults
      && validationResults[i].validationResults.length > 0
    ) {
      hasError = true;
      break;
    }
  }

  if (hasError) {
    getViewModel().setElementValidationErrorClass(collectionElement);
  } else {
    getViewModel().removeElementValidationErrorClass(collectionElement);
  }
}

/**
 * @throws 1489932939
 * @throws 1489932940
 */
function getFirstAvailableValidationErrorMessage(errorCodes: string[], propertyData: PropertyData): string | null {
  assert(
    Array.isArray(errorCodes),
    'Invalid configuration "errorCodes"',
    1489932939
  );
  assert(
    Array.isArray(propertyData),
    'Invalid configuration "propertyData"',
    1489932940
  );

  for (let i = 0, len1 = errorCodes.length; i < len1; ++i) {
    for (let j = 0, len2 = propertyData.length; j < len2; ++j) {
      if (parseInt(errorCodes[i], 10) === parseInt(propertyData[j].code, 10)) {
        if (getUtility().isNonEmptyString(propertyData[j].message)) {
          return propertyData[j].message;
        }
      }
    }
  }

  return null;
}

/**
 * @throws 1489932942
 */
function renewValidationErrorMessages(
  errorCodes: string[],
  propertyData: PropertyData,
  value: string
): PropertyData {
  assert(
    Array.isArray(propertyData),
    'Invalid configuration "propertyData"',
    1489932942
  );

  if (
    !getUtility().isUndefinedOrNull(errorCodes)
    && Array.isArray(errorCodes)
  ) {
    const errorCodeSubset: PropertyData = [];
    for (let i = 0, len1 = errorCodes.length; i < len1; ++i) {
      let errorCodeFound = false;

      for (let j = 0, len2 = propertyData.length; j < len2; ++j) {
        if (parseInt(errorCodes[i], 10) === parseInt(propertyData[j].code, 10)) {
          errorCodeFound = true;
          if (getUtility().isNonEmptyString(value)) {
            // error code exists and should be updated because message is not empty
            propertyData[j].message = value;
          } else {
            // error code exists but should be removed because message is empty
            propertyData.splice(j, 1);
            --len2;
          }
        }
      }

      if (!errorCodeFound) {
        // add new codes because message is not empty
        if (getUtility().isNonEmptyString(value)) {
          errorCodeSubset.push({
            code: errorCodes[i],
            message: value
          });
        }
      }
    }

    propertyData = propertyData.concat(errorCodeSubset);
  }

  return propertyData;
}

/**
 * @throws 1523904699
 */
function setRandomIds(html: HTMLElement): void {
  assert(
    typeof html === 'object' && html !== null && !Array.isArray(html),
    'Invalid input "html"',
    1523904699
  );

  const idReplacements: Record<string, string> = {};

  html.querySelectorAll<HTMLElement>(getHelper().getDomElementDataAttribute('randomId', 'bracesWithKey')).forEach(function(element: HTMLElement) {
    const targetAttribute = element.getAttribute(getHelper().getDomElementDataAttribute('randomIdTarget'));
    const randomIdIndex = element.getAttribute(getHelper().getDomElementDataAttribute('randomIdIndex'));

    if (element.hasAttribute(targetAttribute)) {
      return;
    }

    if (!(randomIdIndex in idReplacements)) {
      idReplacements[randomIdIndex] = 'fe' + Math.floor(Math.random() * 42) + Date.now();
    }
    element.setAttribute(targetAttribute, idReplacements[randomIdIndex]);
  });
}

export function getInspectorDomElement(): HTMLElement | null {
  return document.querySelector(getHelper().getDomElementDataIdentifierSelector('inspector'));
}

export function getFinishersContainerDomElement(): HTMLElement | null {
  return getInspectorDomElement()?.querySelector(getHelper().getDomElementDataIdentifierSelector('inspectorFinishers')) ?? null;
}

export function getValidatorsContainerDomElement(): HTMLElement | null {
  return getInspectorDomElement()?.querySelector(getHelper().getDomElementDataIdentifierSelector('inspectorValidators')) ?? null;
}

export function getCollectionElementDomElement(
  collectionName: keyof FormEditorDefinitions,
  collectionElementIdentifier: string
): HTMLElement | null {
  if (collectionName === 'finishers') {
    return getFinishersContainerDomElement()?.querySelector(
      getHelper().getDomElementDataAttribute('finisher', 'bracesWithKeyValue', [collectionElementIdentifier])
    ) ?? null;
  } else {
    return getValidatorsContainerDomElement()?.querySelector(
      getHelper().getDomElementDataAttribute('validator', 'bracesWithKeyValue', [collectionElementIdentifier])
    ) ?? null;
  }
}

export function renderEditors(
  formElement?: FormElement | string,
  callback?: () => void
): void {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }

  const inspectorEl = getInspectorDomElement();
  if (inspectorEl) { inspectorEl.replaceChildren(); }

  const formElementTypeDefinition = getFormElementDefinition(formElement, undefined);
  if (!Array.isArray(formElementTypeDefinition.editors)) {
    return;
  }

  for (let i = 0, len = formElementTypeDefinition.editors.length; i < len; ++i) {
    const rawTemplate = getHelper().getTemplateElement(formElementTypeDefinition.editors[i].templateName);
    if (!rawTemplate) {
      continue;
    }
    const wrapper = document.createElement('div');
    wrapper.innerHTML = rawTemplate.innerHTML;

    const children = Array.from(wrapper.children) as HTMLElement[];
    for (const child of children) {
      child.classList.add(getHelper().getDomElementClassName('inspectorEditor'));
      inspectorEl?.append(child);
    }

    const html = children[0];
    if (!html) {
      continue;
    }

    for (const child of children) {
      setRandomIds(child);
    }
    renderEditorDispatcher(formElementTypeDefinition.editors[i], html);
  }

  if (typeof callback === 'function') {
    callback();
  }
}

export function renderCollectionElementEditors(
  collectionName: keyof FormEditorDefinitions,
  collectionElementIdentifier: string
): void {
  let collapseWrapper: HTMLElement, collapsePanel: HTMLElement, collectionContainer: HTMLElement | null;

  assert(
    getUtility().isNonEmptyString(collectionName),
    'Invalid parameter "collectionName"',
    1478354853
  );
  assert(
    getUtility().isNonEmptyString(collectionElementIdentifier),
    'Invalid parameter "collectionElementIdentifier"',
    1478354854
  );

  const collectionElementConfiguration = getFormEditorApp().getPropertyCollectionElementConfiguration(
    collectionElementIdentifier,
    collectionName
  );
  if (!collectionElementConfiguration || !Array.isArray(collectionElementConfiguration.editors)) {
    return;
  }

  const collectionContainerElementWrapper = document.createElement('div');
  collectionContainerElementWrapper.classList.add(
    getHelper().getDomElementClassName('collectionElement'),
    'panel',
    'panel-default'
  );
  if (collectionName === 'finishers') {
    collectionContainer = getFinishersContainerDomElement();
    collectionContainerElementWrapper.setAttribute(getHelper().getDomElementDataAttribute('finisher'), collectionElementIdentifier);
  } else {
    collectionContainer = getValidatorsContainerDomElement();
    collectionContainerElementWrapper.setAttribute(getHelper().getDomElementDataAttribute('validator'), collectionElementIdentifier);
  }
  collectionContainer?.append(collectionContainerElementWrapper);

  const collectionElementEditorsLength = collectionElementConfiguration.editors.length;
  if (
    collectionElementEditorsLength > 0
    && collectionElementConfiguration.editors[0].identifier === 'header'
  ) {
    collapsePanel = document.createElement('div');
    collapsePanel.classList.add('panel-body');
    collapseWrapper = document.createElement('div');
    collapseWrapper.classList.add('panel-collapse', 'collapse');
    collapseWrapper.id = getCollectionElementId(collectionName, collectionElementIdentifier);
    collapseWrapper.appendChild(collapsePanel);
  }

  for (let i = 0; i < collectionElementEditorsLength; ++i) {
    const rawTemplate = getHelper().getTemplateElement(collectionElementConfiguration.editors[i].templateName);
    if (!rawTemplate) {
      continue;
    }
    const wrapper = document.createElement('div');
    wrapper.innerHTML = rawTemplate.innerHTML;
    const html = wrapper.firstElementChild as HTMLElement ?? wrapper;
    html.classList.add(
      getCollectionElementClass(collectionName, collectionElementConfiguration.editors[i].identifier),
      getHelper().getDomElementClassName('inspectorEditor')
    );

    if (i === 0 && collapseWrapper) {
      collectionContainerElementWrapper.append(html);
      collectionContainerElementWrapper.append(collapseWrapper);
    } else if (
      i === (collectionElementEditorsLength - 1)
      && collapseWrapper
      && collectionElementConfiguration.editors[i].identifier === 'removeButton'
    ) {
      collapsePanel.append(html);
    } else if (i > 0 && collapseWrapper) {
      collapsePanel.append(html);
    } else {
      collectionContainerElementWrapper.append(html);
    }

    setRandomIds(html);
    renderEditorDispatcher(
      collectionElementConfiguration.editors[i],
      html,
      collectionElementIdentifier,
      collectionName
    );
  }

  if (
    (
      collectionElementEditorsLength === 2
      && collectionElementConfiguration.editors[0].identifier === 'header'
      && collectionElementConfiguration.editors[1].identifier === 'removeButton'
    ) || (
      collectionElementEditorsLength === 1
      && collectionElementConfiguration.editors[0].identifier === 'header'
    )
  ) {
    collectionContainerElementWrapper.querySelector(getHelper().getDomElementDataIdentifierSelector('collapse'))?.remove();
  }

  if (configuration.isSortable && collectionContainer) {
    addSortableCollectionElementsEvents(collectionContainer, collectionName);
  }
}

export function renderCollectionElementSelectionEditor(
  collectionName: keyof FormEditorDefinitions,
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
): void {
  let alreadySelectedCollectionElements, collectionContainer: HTMLElement | null, removeSelectElement: boolean;

  assert(
    getUtility().isNonEmptyString(collectionName),
    'Invalid configuration "collectionName"',
    1478362968
  );
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475423098
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1475423099
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475423100
  );
  assert(
    Array.isArray(editorConfiguration.selectOptions),
    'Invalid configuration "selectOptions"',
    1475423101
  );

  if (collectionName === 'finishers') {
    collectionContainer = getFinishersContainerDomElement();
    alreadySelectedCollectionElements = getRootFormElement().get(collectionName);
  } else {
    collectionContainer = getValidatorsContainerDomElement();
    alreadySelectedCollectionElements = getCurrentlySelectedFormElement().get(collectionName);
  }

  collectionContainer?.replaceChildren();

  const labelEl = getHelper().getTemplatePropertyElement('label', editorHtml);
  if (labelEl) { labelEl.append(document.createTextNode(editorConfiguration.label)); }
  const selectElement = getHelper().getTemplatePropertyElement('selectOptions', editorHtml) as HTMLSelectElement | null;
  const hasAlreadySelectedCollectionElements = (
    !getUtility().isUndefinedOrNull(alreadySelectedCollectionElements) &&
    alreadySelectedCollectionElements.length > 0
  );

  if (hasAlreadySelectedCollectionElements) {
    for (let i = 0, len = alreadySelectedCollectionElements.length; i < len; ++i) {
      getPublisherSubscriber().publish('view/inspector/collectionElement/existing/selected', [
        alreadySelectedCollectionElements[i].identifier,
        collectionName
      ]);
    }
  }

  removeSelectElement = true;
  for (let i = 0, len1 = editorConfiguration.selectOptions.length; i < len1; ++i) {
    let appendOption = true;
    if (!getUtility().isUndefinedOrNull(alreadySelectedCollectionElements)) {
      for (let j = 0, len2 = alreadySelectedCollectionElements.length; j < len2; ++j) {
        if (alreadySelectedCollectionElements[j].identifier === editorConfiguration.selectOptions[i].value) {
          appendOption = false;
          break;
        }
      }
    }
    if (appendOption) {
      selectElement?.append(new Option(
        editorConfiguration.selectOptions[i].label,
        editorConfiguration.selectOptions[i].value
      ));
      if (editorConfiguration.selectOptions[i].value !== '') {
        removeSelectElement = false;
      }
    }
  }

  if (removeSelectElement) {
    const selectGroup = getHelper().getTemplatePropertyElement('select-group', editorHtml);
    selectGroup?.replaceChildren();
    selectGroup?.remove();
    const labelNoSelect = getHelper().getTemplatePropertyElement('label-no-select', editorHtml);
    if (hasAlreadySelectedCollectionElements) {
      if (labelNoSelect) { labelNoSelect.textContent = editorConfiguration.label; }
    } else {
      labelNoSelect?.remove();
    }
    return;
  }

  getHelper().getTemplatePropertyElement('label-no-select', editorHtml)?.remove();

  selectElement?.addEventListener('change', function(this: HTMLSelectElement) {
    const value = this.value;
    if (value !== '') {
      this.querySelector(`option[value="${value}"]`)?.remove();

      getFormEditorApp().getPublisherSubscriber().publish(
        'view/inspector/collectionElement/new/selected',
        [value, collectionName]
      );
    }
  });
}

export function renderFormElementHeaderEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
): void {
  assert(typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration), 'Invalid parameter "editorConfiguration"', 1475421525);
  assert(typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml), 'Invalid parameter "editorHtml"', 1475421526);

  Icons.getIcon(
    getFormElementDefinition(getCurrentlySelectedFormElement(), 'iconIdentifier'),
    Icons.sizes.small,
    null,
    Icons.states.default
  ).then(function(icon) {
    const headerLabel = getHelper().getTemplatePropertyElement('header-label', editorHtml);
    if (headerLabel) {
      const tmp = document.createElement('div');
      tmp.innerHTML = icon;
      const iconEl = tmp.firstElementChild;
      if (iconEl) {
        iconEl.classList.add(getHelper().getDomElementClassName('icon'));
        headerLabel.append(iconEl);
      }
      headerLabel.append(buildTitleByFormElement());
      const code = document.createElement('code');
      code.textContent = getCurrentlySelectedFormElement().get('identifier');
      headerLabel.append(code);
    }
  });
}

export function renderCollectionElementHeaderEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421258
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475421257
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1475421259
  );

  const setData = function(icon?: string) {
    const iconPlaceholder = getHelper().getTemplatePropertyElement('panel-icon', editorHtml);
    if (icon) {
      const tmp = document.createElement('div');
      tmp.innerHTML = icon;
      iconPlaceholder?.replaceWith(tmp.firstElementChild ?? tmp);
    } else {
      iconPlaceholder?.remove();
    }

    const collectionConfig = getFormEditorApp().getPropertyCollectionElementConfiguration(
      collectionElementIdentifier,
      collectionName
    );
    const editors = collectionConfig?.editors;

    if (editors && !(
      (editors.length === 2 && editors[0].identifier === 'header' && editors[1].identifier === 'removeButton') ||
      (editors.length === 1 && editors[0].identifier === 'header')
    )) {
      const button = document.createElement('button');
      button.classList.add('panel-button', 'collapsed');
      button.setAttribute('type', 'button');
      button.setAttribute('data-bs-toggle', 'collapse');
      button.setAttribute('data-bs-target', getCollectionElementId(collectionName, collectionElementIdentifier, true));
      button.setAttribute('aria-expaned', 'false');
      button.setAttribute('aria-controls', getCollectionElementId(collectionName, collectionElementIdentifier));

      const caret = document.createElement('span');
      caret.classList.add('caret');
      const panelHeadingRow = getHelper().getTemplatePropertyElement('panel-heading-row', editorHtml);
      panelHeadingRow?.querySelector('.panel-title')?.before(caret);
      // wrapInner equivalent: wrap all children of panelHeadingRow in button
      if (panelHeadingRow) {
        while (panelHeadingRow.firstChild) {
          button.appendChild(panelHeadingRow.firstChild);
        }
        panelHeadingRow.appendChild(button);
      }
    }

    // Move delete button – search within editorHtml's parent (collectionContainerElementWrapper)
    const collectionContainerEl = editorHtml.closest(getHelper().getDomElementClassName('collectionElement', true)) as HTMLElement | null;
    const removeButtonElement = collectionContainerEl?.querySelector('.formeditor-inspector-element-remove-button');
    if (removeButtonElement) {
      const removeButton = removeButtonElement.querySelector('button');
      if (removeButton) {
        removeButton.classList.add('btn-sm');
        removeButton.querySelector('.btn-label')?.classList.add('visually-hidden');
        const panelActions = document.createElement('div');
        panelActions.classList.add('panel-actions');
        panelActions.append(removeButton);
        getHelper().getTemplatePropertyElement('panel-heading-row', editorHtml)?.append(panelActions);
      }
    }
    removeButtonElement?.remove();
  };

  const collectionElementConfiguration = getFormEditorApp().getFormEditorDefinition(collectionName, collectionElementIdentifier);
  if ('iconIdentifier' in collectionElementConfiguration) {
    Icons.getIcon(
      collectionElementConfiguration.iconIdentifier,
      Icons.sizes.small,
      null,
      Icons.states.default
    ).then(function(icon) {
      setData(icon);
    });
  } else {
    setData();
  }

  if (editorConfiguration.label) {
    const panelTitle = getHelper().getTemplatePropertyElement('panel-title', editorHtml);
    if (panelTitle) {
      panelTitle.removeAttribute('data-template-property');
      panelTitle.append(document.createTextNode(editorConfiguration.label));
    }
  }
}

export function renderFileMaxSizeEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421258
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475421257
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1475421259
  );

  if (editorConfiguration.label) {
    const element = getHelper().getTemplatePropertyElement('label', editorHtml);
    const maximumFileSize = element?.getAttribute(getHelper().getDomElementDataAttribute('maximumFileSize'));
    element?.append(document.createTextNode(editorConfiguration.label.replace('{0}', maximumFileSize ?? '')));
  }
}

export function renderTextEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421053
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1475421054
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475421055
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1475421056
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  renderDescription(editorConfiguration, editorHtml);

  if (getUtility().isNonEmptyString(editorConfiguration.placeholder)) {
    getHelper().getTemplatePropertyElement('propertyPath', editorHtml)
      ?.setAttribute('placeholder', editorConfiguration.placeholder);
  }

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath,
    collectionElementIdentifier,
    collectionName
  );
  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);

  validateCollectionElement(propertyPath, editorHtml);

  const inputEl = getHelper().getTemplatePropertyElement('propertyPath', editorHtml) as HTMLInputElement | null;
  if (inputEl) { inputEl.value = propertyData ?? ''; }

  if (
    !getUtility().isUndefinedOrNull(editorConfiguration.additionalElementPropertyPaths)
    && Array.isArray(editorConfiguration.additionalElementPropertyPaths)
  ) {
    for (let i = 0, len = editorConfiguration.additionalElementPropertyPaths.length; i < len; ++i) {
      getCurrentlySelectedFormElement().set(editorConfiguration.additionalElementPropertyPaths[i], propertyData);
    }
  }

  renderFormElementSelectorEditorAddition(editorConfiguration, editorHtml, propertyPath);

  inputEl?.addEventListener('keyup', handleTextInput);
  inputEl?.addEventListener('paste', handleTextInput);

  function handleTextInput(this: HTMLInputElement) {
    if (
      !!editorConfiguration.doNotSetIfPropertyValueIsEmpty
      && !getUtility().isNonEmptyString(this.value)
    ) {
      getCurrentlySelectedFormElement().unset(propertyPath);
    } else {
      getCurrentlySelectedFormElement().set(propertyPath, this.value);
    }
    validateCollectionElement(propertyPath, editorHtml);
    if (
      !getUtility().isUndefinedOrNull(editorConfiguration.additionalElementPropertyPaths)
      && Array.isArray(editorConfiguration.additionalElementPropertyPaths)
    ) {
      for (let i = 0, len = editorConfiguration.additionalElementPropertyPaths.length; i < len; ++i) {
        if (
          !!editorConfiguration.doNotSetIfPropertyValueIsEmpty
          && !getUtility().isNonEmptyString(this.value)
        ) {
          getCurrentlySelectedFormElement().unset(editorConfiguration.additionalElementPropertyPaths[i]);
        } else {
          getCurrentlySelectedFormElement().set(editorConfiguration.additionalElementPropertyPaths[i], this.value);
        }
      }
    }
  }
}

export function renderValidationErrorMessageEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1489874121
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1489874122
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1489874123
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1489874124
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  renderDescription(editorConfiguration, editorHtml);

  const propertyPath = getFormEditorApp().buildPropertyPath(editorConfiguration.propertyPath);
  let propertyData: PropertyData = getCurrentlySelectedFormElement().get(propertyPath);

  if (!getUtility().isUndefinedOrNull(propertyData) && Array.isArray(propertyData)) {
    const validationErrorMessage = getFirstAvailableValidationErrorMessage(editorConfiguration.errorCodes, propertyData);
    const inputEl = getHelper().getTemplatePropertyElement('propertyPath', editorHtml) as HTMLInputElement | null;
    if (!getUtility().isUndefinedOrNull(validationErrorMessage) && inputEl) {
      inputEl.value = validationErrorMessage;
    }
  }

  const inputEl = getHelper().getTemplatePropertyElement('propertyPath', editorHtml) as HTMLInputElement | null;
  inputEl?.addEventListener('keyup', handleInput);
  inputEl?.addEventListener('paste', handleInput);

  function handleInput(this: HTMLInputElement) {
    propertyData = getCurrentlySelectedFormElement().get(propertyPath);
    if (getUtility().isUndefinedOrNull(propertyData)) {
      propertyData = [];
    }
    getCurrentlySelectedFormElement().set(propertyPath, renewValidationErrorMessages(
      editorConfiguration.errorCodes,
      propertyData,
      this.value
    ));
  }
}

export function renderCountrySelectEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1674826430
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1674826431
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1674826432
  );

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath,
    collectionElementIdentifier,
    collectionName
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  renderDescription(editorConfiguration, editorHtml);

  const selectElement = getHelper().getTemplatePropertyElement('selectOptions', editorHtml) as HTMLSelectElement | null;
  const propertyData: Record<string, string> = getCurrentlySelectedFormElement().get(propertyPath) || {};
  validateCollectionElement(propertyPath, editorHtml);

  const options = Array.from(selectElement?.querySelectorAll('option') ?? []);
  selectElement?.replaceChildren();

  for (let i = 0, len = options.length; i < len; ++i) {
    let selected = false;
    for (const propertyDataKey of Object.keys(propertyData)) {
      if (options[i].value === propertyData[propertyDataKey]) {
        selected = true;
        break;
      }
    }
    const option = new Option(options[i].text, i.toString(), false, selected);
    (option as any)._dataValue = options[i].value;
    selectElement?.append(option);
  }

  selectElement?.addEventListener('change', function(this: HTMLSelectElement) {
    const selectValues: string[] = [];
    this.querySelectorAll<HTMLOptionElement>('option:checked').forEach(function(opt: HTMLOptionElement) {
      selectValues.push((opt as any)._dataValue);
    });
    getCurrentlySelectedFormElement().set(propertyPath, selectValues);
    validateCollectionElement(propertyPath, editorHtml);
  });
}

export function renderSingleSelectEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421048
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1475421049
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475421050
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1475421051
  );
  assert(
    Array.isArray(editorConfiguration.selectOptions),
    'Invalid configuration "selectOptions"',
    1475421052
  );

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath,
    collectionElementIdentifier,
    collectionName
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  renderDescription(editorConfiguration, editorHtml);

  const selectElement = getHelper().getTemplatePropertyElement('selectOptions', editorHtml) as HTMLSelectElement | null;
  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);
  validateCollectionElement(propertyPath, editorHtml);

  for (let i = 0, len = editorConfiguration.selectOptions.length; i < len; ++i) {
    const selected = editorConfiguration.selectOptions[i].value === propertyData;
    const option = new Option(editorConfiguration.selectOptions[i].label, i.toString(), false, selected);
    (option as any)._dataValue = editorConfiguration.selectOptions[i].value;
    selectElement?.append(option);
  }

  selectElement?.addEventListener('change', function(this: HTMLSelectElement) {
    const selectedOpt = this.querySelector<HTMLOptionElement>('option:checked');
    getCurrentlySelectedFormElement().set(propertyPath, (selectedOpt as any)?._dataValue);
    validateCollectionElement(propertyPath, editorHtml);
  });
}

export function renderMultiSelectEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1485712399
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1485712400
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1485712401
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1485712402
  );
  assert(
    Array.isArray(editorConfiguration.selectOptions),
    'Invalid configuration "selectOptions"',
    1485712403
  );

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath,
    collectionElementIdentifier,
    collectionName
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  renderDescription(editorConfiguration, editorHtml);

  const selectElement = getHelper().getTemplatePropertyElement('selectOptions', editorHtml) as HTMLSelectElement | null;
  const propertyData: Record<string, string> = getCurrentlySelectedFormElement().get(propertyPath) || {};
  validateCollectionElement(propertyPath, editorHtml);

  for (let i = 0, len1 = editorConfiguration.selectOptions.length; i < len1; ++i) {
    let selected = false;
    for (const propertyDataKey of Object.keys(propertyData)) {
      if (editorConfiguration.selectOptions[i].value === propertyData[propertyDataKey]) {
        selected = true;
        break;
      }
    }
    const option = new Option(editorConfiguration.selectOptions[i].label, i.toString(), false, selected);
    (option as any)._dataValue = editorConfiguration.selectOptions[i].value;
    selectElement?.append(option);
  }

  selectElement?.addEventListener('change', function(this: HTMLSelectElement) {
    const selectValues: string[] = [];
    this.querySelectorAll<HTMLOptionElement>('option:checked').forEach(function(opt: HTMLOptionElement) {
      selectValues.push((opt as any)._dataValue);
    });
    getCurrentlySelectedFormElement().set(propertyPath, selectValues);
    validateCollectionElement(propertyPath, editorHtml);
  });
}

export function renderGridColumnViewPortConfigurationEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1489528242
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1489528243
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1489528244
  );
  assert(
    Array.isArray(editorConfiguration.configurationOptions.viewPorts),
    'Invalid configurationOptions "viewPorts"',
    1489528245
  );
  assert(
    !getUtility().isUndefinedOrNull(editorConfiguration.configurationOptions.numbersOfColumnsToUse.label),
    'Invalid configurationOptions "numbersOfColumnsToUse"',
    1489528246
  );
  assert(
    !getUtility().isUndefinedOrNull(editorConfiguration.configurationOptions.numbersOfColumnsToUse.propertyPath),
    'Invalid configuration "selectOptions"',
    1489528247
  );

  if (!getFormElementDefinition(getCurrentlySelectedFormElement().get('__parentRenderable'), '_isGridRowFormElement')) {
    editorHtml.remove();
    return;
  }

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));

  const viewportButtonSel = getHelper().getDomElementDataIdentifierSelector('viewportButton');
  const viewportButtonTemplate = editorHtml.querySelector(viewportButtonSel)?.cloneNode(true) as HTMLElement | null;
  editorHtml.querySelectorAll(viewportButtonSel).forEach(el => el.remove());

  const numbersOfColumnsTemplate = getHelper().getTemplatePropertyElement('numbersOfColumnsToUse', editorHtml)?.cloneNode(true) as HTMLElement | null;
  getHelper().getTemplatePropertyElement('numbersOfColumnsToUse', editorHtml)?.remove();

  const editorControlsWrapper = getEditorControlsWrapperDomElement(editorHtml);

  const initNumbersOfColumnsField = function(element: HTMLElement) {
    getHelper().getTemplatePropertyElement('numbersOfColumnsToUse', editorHtml)?.replaceChildren();
    getHelper().getTemplatePropertyElement('numbersOfColumnsToUse', editorHtml)?.remove();

    const numbersOfColumnsTemplateClone = numbersOfColumnsTemplate?.cloneNode(true) as HTMLElement | null;
    getEditorWrapperDomElement(editorHtml)?.after(numbersOfColumnsTemplateClone);

    numbersOfColumnsTemplateClone?.querySelector<HTMLInputElement>('input')?.focus();

    const labelEl = getHelper().getTemplatePropertyElement('numbersOfColumnsToUse-label', numbersOfColumnsTemplateClone);
    if (labelEl) {
      labelEl.append(document.createTextNode(
        editorConfiguration.configurationOptions.numbersOfColumnsToUse.label
          .replace('{@viewPortLabel}', element.dataset.viewPortLabel ?? '')
      ));
    }

    const descEl = getHelper().getTemplatePropertyElement('numbersOfColumnsToUse-description', numbersOfColumnsTemplateClone);
    if (descEl) {
      descEl.append(document.createTextNode(editorConfiguration.configurationOptions.numbersOfColumnsToUse.description));
    }

    const propertyPath = editorConfiguration.configurationOptions.numbersOfColumnsToUse.propertyPath
      .replace('{@viewPortIdentifier}', element.dataset.viewPortIdentifier ?? '');

    const inputEl = getHelper().getTemplatePropertyElement('numbersOfColumnsToUse-propertyPath', numbersOfColumnsTemplateClone) as HTMLInputElement | null;
    if (inputEl) {
      inputEl.value = getCurrentlySelectedFormElement().get(propertyPath) ?? '';
      inputEl.addEventListener('keyup', handleInput);
      inputEl.addEventListener('paste', handleInput);
      inputEl.addEventListener('change', handleInput);

      function handleInput(this: HTMLInputElement) {
        if (this.value === '' || isNaN(Number(this.value))) {
          this.value = '';
        }
        getCurrentlySelectedFormElement().set(propertyPath, this.value);
      }
    }
  };

  for (let i = 0, len = editorConfiguration.configurationOptions.viewPorts.length; i < len; ++i) {
    const viewPortIdentifier = editorConfiguration.configurationOptions.viewPorts[i].viewPortIdentifier;
    const viewPortLabel = editorConfiguration.configurationOptions.viewPorts[i].label;

    const viewportButtonTemplateClone = viewportButtonTemplate?.cloneNode(true) as HTMLElement | null;
    if (!viewportButtonTemplateClone) { continue; }
    viewportButtonTemplateClone.textContent = viewPortIdentifier;
    viewportButtonTemplateClone.dataset.viewPortIdentifier = viewPortIdentifier;
    viewportButtonTemplateClone.dataset.viewPortLabel = viewPortLabel;
    viewportButtonTemplateClone.setAttribute('title', viewPortLabel);
    editorControlsWrapper?.append(viewportButtonTemplateClone);

    if (i === (len - 1)) {
      initNumbersOfColumnsField(viewportButtonTemplateClone);
      viewportButtonTemplateClone.classList.add(getHelper().getDomElementClassName('active'));
    }
  }

  editorControlsWrapper?.querySelectorAll<HTMLButtonElement>('button').forEach(btn => {
    btn.addEventListener('click', function(this: HTMLButtonElement) {
      editorControlsWrapper.querySelectorAll('button').forEach(b => b.classList.remove(getHelper().getDomElementClassName('active')));
      this.classList.add(getHelper().getDomElementClassName('active'));
      initNumbersOfColumnsField(this);
    });
  });
}

export function renderPropertyGridEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475419226
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1475419227
  );
  assert(
    typeof editorConfiguration.enableAddRow === 'boolean',
    'Invalid configuration "enableAddRow"',
    1475419228
  );
  assert(
    typeof editorConfiguration.enableDeleteRow === 'boolean',
    'Invalid configuration "enableDeleteRow"',
    1475419230
  );
  assert(
    typeof editorConfiguration.isSortable === 'boolean',
    'Invalid configuration "isSortable"',
    1475419229
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1475419231
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475419232
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  renderDescription(editorConfiguration, editorHtml);

  const propertyPathPrefix = (() => {
    const path = getFormEditorApp().buildPropertyPath(undefined, collectionElementIdentifier, collectionName, undefined, true);
    return getUtility().isNonEmptyString(path) ? path + '.' : path;
  })();

  const multiSelection: boolean = getUtility().isUndefinedOrNull(editorConfiguration.multiSelection)
    ? false
    : !!editorConfiguration.multiSelection;

  const enableSelection = getUtility().isNonEmptyArray(editorConfiguration.gridColumns)
    ? editorConfiguration.gridColumns.some(item => item.name === 'selected')
    : true;

  const defaultValue: Record<string, string> = (() => {
    const val = getCurrentlySelectedFormElement().get(propertyPathPrefix + 'defaultValue');
    return !getUtility().isUndefinedOrNull(val)
      ? multiSelection ? val : { '0': val }
      : {};
  })();

  const propertyData = (() : PropertyGridEditorEntry[] => {
    const formElement = getCurrentlySelectedFormElement();
    const fullPropertyPath = propertyPathPrefix + editorConfiguration.propertyPath;
    const rawData = formElement.get(fullPropertyPath) || {};
    let propertyEntries: PropertyGridEditorEntry[];

    if (Array.isArray(rawData)) {
      // Handle array of objects: [{_label, _value}] or raw values
      propertyEntries = rawData.map((item, index): PropertyGridEditorEntry => ({
        id: 'fe' + Math.floor(Math.random() * 42) + Date.now(),
        label: getUtility().isUndefinedOrNull(item._label) ? item : item._label,
        value: getUtility().isUndefinedOrNull(item._label) ? index : item._value,
        selected: false,
      }));
    } else if (typeof rawData === 'object') {
      // Handle object case: { value: label }
      propertyEntries = Object.entries(rawData).map(([value, label]: [string, string]): PropertyGridEditorEntry => ({
        id: 'fe' + Math.floor(Math.random() * 42) + Date.now(),
        label,
        value,
        selected: false,
      }));
    }

    return propertyEntries.map(entry => {
      for (const defaultValueKey of Object.keys(defaultValue)) {
        if (defaultValue[defaultValueKey] === entry.value) {
          entry.selected = true;
          break;
        }
      }
      return entry;
    });
  })();

  const useLabelAsFallbackValue = getUtility().isUndefinedOrNull(editorConfiguration.useLabelAsFallbackValue)
    ? true
    : editorConfiguration.useLabelAsFallbackValue;

  const propertyGridEditor = editorHtml.querySelector('typo3-form-property-grid-editor') as any;
  propertyGridEditor.enableAddRow = editorConfiguration.enableAddRow;
  propertyGridEditor.enableSelection = enableSelection;
  propertyGridEditor.enableMultiSelection = multiSelection;
  propertyGridEditor.enableSorting = editorConfiguration.isSortable ?? false;
  propertyGridEditor.enableDeleteRow = editorConfiguration.enableDeleteRow ?? false;
  propertyGridEditor.enableLabelAsFallbackValue = useLabelAsFallbackValue;
  propertyGridEditor.entries = propertyData;

  if (getUtility().isNonEmptyArray(editorConfiguration.gridColumns)) {
    editorConfiguration.gridColumns.forEach(gridColumnConfig => {
      if (gridColumnConfig.name === 'label') {
        propertyGridEditor.labelLabel = gridColumnConfig.title;
        propertyGridEditor.enableLabelFormElementSelectionButton = gridColumnConfig.enableFormelementSelectionButton;
      }
      if (gridColumnConfig.name === 'value') {
        propertyGridEditor.labelValue = gridColumnConfig.title;
        propertyGridEditor.enableValueFormElementSelectionButton = gridColumnConfig.enableFormelementSelectionButton;
      }
      if (gridColumnConfig.name === 'selected') {
        propertyGridEditor.labelSelected = gridColumnConfig.title;
      }
    });
  }
  if (propertyGridEditor.enableLabelFormElementSelectionButton || propertyGridEditor.enableValueFormElementSelectionButton) {
    propertyGridEditor.formElements = getFormElementSelectorEntries();
  }

  propertyGridEditor.addEventListener(PropertyGridEditorUpdateEvent.eventName, (event: PropertyGridEditorUpdateEvent) => {
    const entries = event.data;
    const defaultValues: (string | number)[] = [];
    const newData: Array<{_label: string, _value: string | number}> = [];

    for (const entry of entries) {
      const entryLabel = entry.label;
      const entryValue = entry.value === ''
        ? entry.label
        : getUtility().canBeInterpretedAsInteger(entry.value)
          ? parseInt(entry.value, 10)
          : entry.value;
      if (entry.selected) {
        defaultValues.push(entryValue);
      }
      newData.push({
        _label: entryLabel,
        _value: entryValue
      });
    }

    if (multiSelection) {
      getCurrentlySelectedFormElement().set(propertyPathPrefix + 'defaultValue', defaultValues);
    } else {
      getCurrentlySelectedFormElement().set(propertyPathPrefix + 'defaultValue', defaultValues[0] ?? '', true);
    }

    getCurrentlySelectedFormElement().set(propertyPathPrefix + editorConfiguration.propertyPath, newData);
    validateCollectionElement(propertyPathPrefix + editorConfiguration.propertyPath, editorHtml);
  });

  validateCollectionElement(propertyPathPrefix + editorConfiguration.propertyPath, editorHtml);
}

/**
 * @publish view/inspector/collectionElement/new/selected
 * @publish view/inspector/removeCollectionElement/perform
 * @throws 1475417093
 * @throws 1475417094
 * @throws 1475417095
 * @throws 1475417096
 */
export function renderRequiredValidatorEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475417093
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1475417094
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.validatorIdentifier),
    'Invalid configuration "validatorIdentifier"',
    1475417095
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475417096
  );

  const validatorIdentifier = editorConfiguration.validatorIdentifier;
  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));

  let propertyValue: string;
  let propertyPath: string;
  let propertyData: PropertyData;
  if (getUtility().isNonEmptyString(editorConfiguration.propertyPath)) {
    propertyPath = getFormEditorApp()
      .buildPropertyPath(editorConfiguration.propertyPath, collectionElementIdentifier, collectionName);
  }
  if (getUtility().isNonEmptyString(editorConfiguration.propertyValue)) {
    propertyValue = editorConfiguration.propertyValue;
  } else {
    propertyValue = '';
  }

  const validationErrorMessagePropertyPath = getFormEditorApp()
    .buildPropertyPath(editorConfiguration.configurationOptions.validationErrorMessage.propertyPath);

  const rawValidationErrorMessageTemplate = getHelper().getTemplatePropertyElement('validationErrorMessage', editorHtml);
  const validationErrorMessageTemplate = rawValidationErrorMessageTemplate?.cloneNode(true) as HTMLElement | null;
  rawValidationErrorMessageTemplate?.remove();

  const showValidationErrorMessage = function() {
    const validationErrorMessageTemplateClone = validationErrorMessageTemplate?.cloneNode(true) as HTMLElement | null;
    getEditorWrapperDomElement(editorHtml)?.after(validationErrorMessageTemplateClone);

    getHelper().getTemplatePropertyElement('validationErrorMessage-label', validationErrorMessageTemplateClone)
      ?.append(document.createTextNode(editorConfiguration.configurationOptions.validationErrorMessage.label));

    getHelper().getTemplatePropertyElement('validationErrorMessage-description', validationErrorMessageTemplateClone)
      ?.append(document.createTextNode(editorConfiguration.configurationOptions.validationErrorMessage.description));

    propertyData = getCurrentlySelectedFormElement().get(validationErrorMessagePropertyPath);
    if (getUtility().isUndefinedOrNull(propertyData)) {
      propertyData = [];
    }

    const validationErrorMessage = getFirstAvailableValidationErrorMessage(
      editorConfiguration.configurationOptions.validationErrorMessage.errorCodes,
      propertyData
    );
    const valInputEl = getHelper().getTemplatePropertyElement('validationErrorMessage-propertyPath', validationErrorMessageTemplateClone) as HTMLInputElement | null;
    if (!getUtility().isUndefinedOrNull(validationErrorMessage) && valInputEl) {
      valInputEl.value = validationErrorMessage;
    }

    valInputEl?.addEventListener('keyup', handleValInput);
    valInputEl?.addEventListener('paste', handleValInput);

    function handleValInput(this: HTMLInputElement) {
      let propertyData = getCurrentlySelectedFormElement().get(validationErrorMessagePropertyPath);
      if (getUtility().isUndefinedOrNull(propertyData)) {
        propertyData = [];
      }

      getCurrentlySelectedFormElement().set(validationErrorMessagePropertyPath, renewValidationErrorMessages(
        editorConfiguration.configurationOptions.validationErrorMessage.errorCodes,
        propertyData,
        this.value
      ));
    }
  };

  const checkboxEl = editorHtml.querySelector<HTMLInputElement>('input[type="checkbox"]');
  if (-1 !== getFormEditorApp().getIndexFromPropertyCollectionElement(validatorIdentifier, 'validators')) {
    if (checkboxEl) { checkboxEl.checked = true; }
    showValidationErrorMessage();
  }

  checkboxEl?.addEventListener('change', function(this: HTMLInputElement) {
    getHelper().getTemplatePropertyElement('validationErrorMessage', editorHtml)?.replaceChildren();
    getHelper().getTemplatePropertyElement('validationErrorMessage', editorHtml)?.remove();

    if (this.checked) {
      showValidationErrorMessage();
      getPublisherSubscriber().publish('view/inspector/collectionElement/new/selected', [validatorIdentifier, 'validators']);
      if (getUtility().isNonEmptyString(propertyPath)) {
        getCurrentlySelectedFormElement().set(propertyPath, propertyValue);
      }
    } else {
      if (getUtility().isNonEmptyString(propertyPath)) {
        getCurrentlySelectedFormElement().unset(propertyPath);
      }
      getPublisherSubscriber().publish('view/inspector/removeCollectionElement/perform', [validatorIdentifier, 'validators']);
      propertyData = getCurrentlySelectedFormElement().get(validationErrorMessagePropertyPath);
      if (getUtility().isUndefinedOrNull(propertyData)) { propertyData = []; }
      getCurrentlySelectedFormElement().set(validationErrorMessagePropertyPath, renewValidationErrorMessages(
        editorConfiguration.configurationOptions.validationErrorMessage.errorCodes,
        propertyData,
        ''
      ));
    }
  });
}

export function renderCheckboxEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1476218671
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1476218672
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1476218673
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1476218674
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  renderDescription(editorConfiguration, editorHtml);

  const propertyPath = getFormEditorApp()
    .buildPropertyPath(editorConfiguration.propertyPath, collectionElementIdentifier, collectionName);
  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);

  // For renderingOptions.enabled, undefined means "use default" which is true
  const useDefaultEnabled = editorConfiguration.propertyPath === 'renderingOptions.enabled'
    && getUtility().isUndefinedOrNull(propertyData);

  const checkboxEl = editorHtml.querySelector<HTMLInputElement>('input[type="checkbox"]');
  if (
    useDefaultEnabled
    || (typeof propertyData === 'boolean' && propertyData)
    || propertyData === 'true'
    || propertyData === 1
    || propertyData === '1'
  ) {
    if (checkboxEl) { checkboxEl.checked = true; }
  }

  checkboxEl?.addEventListener('change', function(this: HTMLInputElement) {
    getCurrentlySelectedFormElement().set(propertyPath, this.checked);
  });
}

/**
 * @throws 1475412567
 * @throws 1475412568
 * @throws 1475416098
 * @throws 1475416099
 */
export function renderTextareaEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475412567
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1475412568
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1475416098
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475416099
  );

  const propertyPath = getFormEditorApp()
    .buildPropertyPath(editorConfiguration.propertyPath, collectionElementIdentifier, collectionName);

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  renderDescription(editorConfiguration, editorHtml);

  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);

  const textarea = editorHtml.querySelector('textarea') as HTMLTextAreaElement;

  if (!textarea) {
    throw new Error('Textarea element not found in editor HTML');
  }

  textarea.value = propertyData as string;

  const rteOptions = editorConfiguration.rteOptions || {};

  if (editorConfiguration.enableRichtext === true && rteOptions && typeof rteOptions === 'object' && Object.keys(rteOptions).length !== 0) {
    const wrapper = textarea.parentElement;
    if (!wrapper) {
      throw new Error('Textarea wrapper element not found');
    }
    if (ckeditor) {
      const textareaId = textarea.id;
      const rteId = textareaId ? textareaId + 'ckeditor5' : '';

      const rteElement = document.createElement('typo3-rte-ckeditor-ckeditor5');
      if (rteId) {
        rteElement.id = rteId;
      }

      const optionsJson = JSON.stringify(rteOptions);
      rteElement.setAttribute('options', optionsJson);

      textarea.setAttribute('slot', 'textarea');
      rteElement.appendChild(textarea);

      wrapper.innerHTML = '';
      wrapper.appendChild(rteElement);

      (rteElement as any).options = rteOptions;
    }
  }

  validateCollectionElement(propertyPath, editorHtml);

  const eventNames = editorConfiguration.enableRichtext === true ? ['change'] : ['keyup', 'paste'];
  const handleTextareaChange = (event: Event) => {
    const target = event.target as HTMLTextAreaElement;
    getCurrentlySelectedFormElement().set(propertyPath, target.value);
    validateCollectionElement(propertyPath, editorHtml);
  };
  eventNames.forEach(eventName => {
    textarea.addEventListener(eventName, handleTextareaChange);
  });
}

/**
 * @throws 1477300587
 * @throws 1477300588
 * @throws 1477300589
 * @throws 1477300590
 * @throws 1477318981
 * @throws 1477319859
 */
export function renderTypo3WinBrowserEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1477300587
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1477300588
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1477300589
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.buttonLabel),
    'Invalid configuration "buttonLabel"',
    1477318981
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1477300590
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label));
  getHelper().getTemplatePropertyElement('buttonLabel', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.buttonLabel));
  renderDescription(editorConfiguration, editorHtml);

  const formEl = editorHtml.querySelector('form');
  if (formEl) { formEl.name = editorConfiguration.propertyPath; }

  Icons.getIcon(editorConfiguration.iconIdentifier, Icons.sizes.small).then(function(icon) {
    const imageEl = getHelper().getTemplatePropertyElement('image', editorHtml);
    if (imageEl) {
      const tmp = document.createElement('div');
      tmp.innerHTML = icon;
      imageEl.append(tmp.firstElementChild ?? tmp);
    }
  });

  getHelper().getTemplatePropertyElement('onclick', editorHtml)?.addEventListener('click', function(this: HTMLElement) {
    const randomIdentifier = Math.floor((Math.random() * 100000) + 1);
    const insertTarget = this
      .closest(getHelper().getDomElementDataIdentifierSelector('editorControlsWrapper'))
      ?.querySelector<HTMLElement>(getHelper().getDomElementDataAttribute('contentElementSelectorTarget', 'bracesWithKey'));

    if (insertTarget) {
      insertTarget.setAttribute(getHelper().getDomElementDataAttribute('contentElementSelectorTarget'), String(randomIdentifier));
    }
    openTypo3WinBrowser('db', String(randomIdentifier), editorConfiguration.browsableType);
  });

  listenOnElementBrowser();

  const propertyPath = getFormEditorApp().buildPropertyPath(editorConfiguration.propertyPath, collectionElementIdentifier, collectionName);
  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);
  validateCollectionElement(propertyPath, editorHtml);

  const inputEl = getHelper().getTemplatePropertyElement('propertyPath', editorHtml) as HTMLInputElement | null;
  if (inputEl) { inputEl.value = propertyData ?? ''; }

  inputEl?.addEventListener('keyup', handleInput);
  inputEl?.addEventListener('paste', handleInput);

  function handleInput(this: HTMLInputElement) {
    getCurrentlySelectedFormElement().set(propertyPath, this.value);
    validateCollectionElement(propertyPath, editorHtml);
  }
}

export function renderRemoveElementEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration), 'Invalid parameter "editorConfiguration"', 1475412563);
  assert(typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml), 'Invalid parameter "editorHtml"', 1475412564);

  const button = editorHtml.querySelector('button');
  if (getUtility().isUndefinedOrNull(collectionElementIdentifier)) {
    button?.classList.add(
      getHelper().getDomElementClassName('buttonFormElementRemove'),
      getHelper().getDomElementClassName('buttonFormEditor')
    );
  } else {
    button?.classList.add(getHelper().getDomElementClassName('buttonCollectionElementRemove'));
  }

  button?.addEventListener('click', function() {
    if (getUtility().isUndefinedOrNull(collectionElementIdentifier)) {
      getViewModel().showRemoveFormElementModal();
    } else {
      getViewModel().showRemoveCollectionElementModal(collectionElementIdentifier, collectionName);
    }
  });
}

export function renderFormElementSelectorEditorAddition(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  propertyPath: string
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null && !Array.isArray(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1484574704
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null && !Array.isArray(editorHtml),
    'Invalid parameter "editorHtml"',
    1484574705
  );
  assert(
    getUtility().isNonEmptyString(propertyPath),
    'Invalid parameter "propertyPath"',
    1484574706
  );

  const formElementSelector = editorHtml.querySelector('typo3-form-element-selector');

  if (!formElementSelector) {
    return;
  }

  if (editorConfiguration.enableFormelementSelectionButton === true) {
    (formElementSelector as any).elements = getFormElementSelectorEntries();
    formElementSelector.addEventListener(FormElementSelectorSelectedEvent.eventName, (event: FormElementSelectorSelectedEvent) => {
      let propertyData;
      propertyData = getCurrentlySelectedFormElement().get(propertyPath) || '';
      if (propertyData.length === 0) {
        propertyData = `{${event.value}}`;
      } else {
        propertyData = `${propertyData} {${event.value}}`;
      }
      getCurrentlySelectedFormElement().set(propertyPath, propertyData);
      const inputEl = getHelper().getTemplatePropertyElement('propertyPath', editorHtml) as HTMLInputElement | null;
      if (inputEl) { inputEl.value = propertyData; }
      validateCollectionElement(propertyPath, editorHtml);
    });
  } else {
    formElementSelector.remove();
    const controlsGroup = editorHtml.querySelector('[data-identifier="inspectorEditorControlsGroup"]');
    if (controlsGroup) {
      controlsGroup.classList.remove('input-group');
    }
  }
}

function getFormElementSelectorEntries(): FormElementSelectorEntry[] {
  return ((): FormElementSelectorEntry[] => {
    const nonCompositeNonToplevelFormElements = getFormEditorApp().getNonCompositeNonToplevelFormElements();

    return nonCompositeNonToplevelFormElements.map((nonCompositeNonToplevelFormElement: FormElement): FormElementSelectorEntry => ({
      icon: getFormElementDefinition(nonCompositeNonToplevelFormElement, 'iconIdentifier'),
      label: nonCompositeNonToplevelFormElement.get('label'),
      value: nonCompositeNonToplevelFormElement.get('identifier'),
    }));
  })();
}

/**
 * @throws 1478967319
 */
export function buildTitleByFormElement(formElement?: FormElement): HTMLElement {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }
  assert(typeof formElement === 'object' && formElement !== null && !Array.isArray(formElement), 'Invalid parameter "formElement"', 1478967319);

  let label;
  if (formElement.get('type') === 'Form') {
    label = formElement.get('type');
  } else {
    label = getFormElementDefinition(formElement, 'label')
      ? getFormElementDefinition(formElement, 'label')
      : formElement.get('identifier');
  }

  const span = document.createElement('span');
  span.textContent = label;
  return span;
}

/**
 * Inspector editor for date constraints using the <typo3-form-date-editor> web component.
 *
 * Delegates UI rendering and state management to the web component.
 * Handles syncing the composed value to the form element model and additionalElementPropertyPaths.
 * The regex patterns from DateRangeValidatorPatterns (PHP) are passed via TYPO3.settings
 * (injected by FormEditorController) to the web component as attributes, so JS and PHP
 * always share the same validation logic.
 */
export function renderDateEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    typeof editorConfiguration === 'object' && editorConfiguration !== null,
    'Invalid parameter "editorConfiguration"',
    1740000001
  );
  assert(
    typeof editorHtml === 'object' && editorHtml !== null,
    'Invalid parameter "editorHtml"',
    1740000002
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.propertyPath),
    'Invalid configuration "propertyPath"',
    1740000003
  );

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath,
    collectionElementIdentifier,
    collectionName
  );

  getHelper().getTemplatePropertyElement('label', editorHtml)
    ?.append(document.createTextNode(editorConfiguration.label || ''));
  renderDescription(editorConfiguration, editorHtml);

  const editorElement = editorHtml.querySelector('typo3-form-date-editor');

  const dateEditorSettings = TYPO3.settings.FormEditor.dateEditor;
  assert(
    getUtility().isNonEmptyString(dateEditorSettings.absolutePattern),
    'Missing required TYPO3.settings.FormEditor.dateEditor.absolutePattern',
    1740000004
  );
  editorElement.setAttribute('absolute-pattern', dateEditorSettings.absolutePattern);
  editorElement.value = getCurrentlySelectedFormElement().get(propertyPath) || '';

  validateCollectionElement(propertyPath, editorHtml);

  editorElement.addEventListener(DateEditorChangeEvent.eventName, (event: DateEditorChangeEvent) => {
    const value = event.value;
    getCurrentlySelectedFormElement().set(propertyPath, value);

    if (
      !getUtility().isUndefinedOrNull(editorConfiguration.additionalElementPropertyPaths)
      && Array.isArray(editorConfiguration.additionalElementPropertyPaths)
    ) {
      for (let i = 0, len = editorConfiguration.additionalElementPropertyPaths.length; i < len; ++i) {
        if (value === '') {
          getCurrentlySelectedFormElement().unset(editorConfiguration.additionalElementPropertyPaths[i]);
        } else {
          getCurrentlySelectedFormElement().set(editorConfiguration.additionalElementPropertyPaths[i], value);
        }
      }
    }

    validateCollectionElement(propertyPath, editorHtml);
  });
}

export function renderDescription(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement
): void {
  const descEl = getHelper().getTemplatePropertyElement('description', editorHtml);
  if (getUtility().isNonEmptyString(editorConfiguration.description)) {
    if (descEl) { descEl.textContent = editorConfiguration.description; }
  } else {
    descEl?.remove();
  }
}

export function bootstrap(
  this: typeof import('./inspector-component'),
  _formEditorApp: FormEditor,
  customConfiguration?: Configuration
): typeof import('./inspector-component') {
  formEditorApp = _formEditorApp;
  configuration = merge({}, defaultConfiguration, customConfiguration ?? {}) as Configuration;
  Helper.bootstrap(formEditorApp);
  return this;
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'view/inspector/removeCollectionElement/perform': readonly [
      validatorIdentifier: string,
      info: 'validators',
      formElement?: FormElement,
    ];
    'view/inspector/collectionElement/new/selected': readonly [
      value: string,
      collectionName: keyof FormEditorDefinitions
    ];
    'view/inspector/collectionElement/existing/selected': readonly [
      alreadySelectedCollectionElementIdentifier: string,
      collectionName: keyof FormEditorDefinitions
    ];
    'view/inspector/collectionElements/dnd/update': readonly [
      movedCollectionElementIdentifier: string,
      previousCollectionElementIdentifier: string,
      nextCollectionElementIdentifier: string,
      collectionName: keyof FormEditorDefinitions
    ];
    'view/inspector/editor/insert/perform': readonly [
      editorConfiguration: EditorConfiguration,
      editorHtml: HTMLElement,
      collectionElementIdentifier: string,
      collectionName: keyof FormEditorDefinitions
    ];
  }
}
