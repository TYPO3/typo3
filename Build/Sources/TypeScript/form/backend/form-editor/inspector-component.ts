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

import $ from 'jquery';
import * as Helper from '@typo3/form/backend/form-editor/helper';
import Icons from '@typo3/backend/icons';
import Modal from '@typo3/backend/modal';
import { MessageUtility } from '@typo3/backend/utility/message-utility';
import Sortable from 'sortablejs';
import { selector } from '@typo3/core/literals';
import { PropertyGridEditorUpdateEvent, type PropertyGridEditorEntry } from '@typo3/form/backend/form-editor/component/property-grid-editor';

import type {
  FormEditor,
} from '@typo3/form/backend/form-editor';
import type {
  Utility,
  EditorConfiguration,
  FormEditorDefinitions,
  FormElement,
  FormElementDefinition,
  PublisherSubscriber,
} from '@typo3/form/backend/form-editor/core';
import type {
  Configuration as HelperConfiguration,
} from '@typo3/form/backend/form-editor/helper';
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
  editorHtml: HTMLElement | JQuery,
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
function openTypo3WinBrowser(mode: string, params: string): void {
  Modal.advanced({
    type: Modal.types.iframe,
    content: TYPO3.settings.FormEditor.typo3WinBrowserUrl + '&mode=' + mode + '&bparams=' + params,
    size: Modal.sizes.large
  });
}

/**
 * Listens on messages sent by ElementBrowser
 */
function listenOnElementBrowser(): void {
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
      $(getHelper().getDomElementDataAttribute('contentElementSelectorTarget', 'bracesWithKeyValue', [e.data.fieldName]))
        .val(result.pop())
        .trigger('paste');
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
  sortableDomElement: JQuery,
  collectionName: keyof FormEditorDefinitions,
): void {
  sortableDomElement.addClass(getHelper().getDomElementClassName('sortable'));
  new Sortable(sortableDomElement.get(0), {
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

      const movedCollectionElementIdentifier = $(e.item).attr(dataAttributeName);
      const previousCollectionElementIdentifier = $(e.item)
        .prevAll(getHelper().getDomElementClassName('collectionElement', true))
        .first()
        .attr(dataAttributeName);
      const nextCollectionElementIdentifier = $(e.item)
        .nextAll(getHelper().getDomElementClassName('collectionElement', true))
        .first()
        .attr(dataAttributeName);

      getPublisherSubscriber().publish('view/inspector/collectionElements/dnd/update', [
        movedCollectionElementIdentifier,
        previousCollectionElementIdentifier,
        nextCollectionElementIdentifier,
        collectionName
      ]);
    }
  });
}

function getEditorWrapperDomElement(editorDomElement: HTMLElement | JQuery): JQuery {
  return $(getHelper().getDomElementDataIdentifierSelector('editorWrapper'), $(editorDomElement));
}

function getEditorControlsWrapperDomElement(editorDomElement: HTMLElement | JQuery): JQuery {
  return $(getHelper().getDomElementDataIdentifierSelector('editorControlsWrapper'), $(editorDomElement));
}

function validateCollectionElement(propertyPath: string, editorHtml: HTMLElement | JQuery): void {
  let hasError, propertyPrefix, validationResults;

  validationResults = getFormEditorApp().validateCurrentlySelectedFormElementProperty(propertyPath);

  if (validationResults.length > 0) {
    getHelper()
      .getTemplatePropertyDomElement('validationErrors', editorHtml)
      .html('<span class="text-danger">' + validationResults[0] + '</span>');
    getViewModel().setElementValidationErrorClass(
      getEditorControlsWrapperDomElement(editorHtml),
      'hasError'
    );
  } else {
    getHelper().getTemplatePropertyDomElement('validationErrors', editorHtml).html('');
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
    getViewModel().setElementValidationErrorClass(
      getEditorControlsWrapperDomElement(editorHtml).closest(getHelper().getDomElementClassName('collectionElement', true))
    );
  } else {
    getViewModel().removeElementValidationErrorClass(
      getEditorControlsWrapperDomElement(editorHtml).closest(getHelper().getDomElementClassName('collectionElement', true))
    );
  }
}

/**
 * @throws 1489932939
 * @throws 1489932940
 */
function getFirstAvailableValidationErrorMessage(errorCodes: string[], propertyData: PropertyData): string | null {
  assert(
    'array' === $.type(errorCodes),
    'Invalid configuration "errorCodes"',
    1489932939
  );
  assert(
    'array' === $.type(propertyData),
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
    'array' === $.type(propertyData),
    'Invalid configuration "propertyData"',
    1489932942
  );

  if (
    !getUtility().isUndefinedOrNull(errorCodes)
    && 'array' === $.type(errorCodes)
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
function setRandomIds(html: JQuery): void {
  assert(
    'object' === $.type(html),
    'Invalid input "html"',
    1523904699
  );

  $(getHelper().getDomElementClassName('inspectorEditor', true)).each(function(this: HTMLElement) {
    const $parent = $(this);
    const idReplacements: Record<string, string> = {};

    $(getHelper().getDomElementDataAttribute('randomId', 'bracesWithKey'), $parent).each(function(this: HTMLElement) {
      const $element = $(this),
        targetAttribute = $element.attr(getHelper().getDomElementDataAttribute('randomIdTarget')),
        randomIdIndex = $element.attr(getHelper().getDomElementDataAttribute('randomIdIndex'));

      if ($element.is('[' + targetAttribute + ']')) {
        return;
      }

      if (!(randomIdIndex in idReplacements)) {
        idReplacements[randomIdIndex] = 'fe' + Math.floor(Math.random() * 42) + Date.now();
      }
      $element.attr(targetAttribute, idReplacements[randomIdIndex]);
    });
  });
}

export function getInspectorDomElement(): JQuery {
  return $(getHelper().getDomElementDataIdentifierSelector('inspector'));
}

export function getFinishersContainerDomElement(): JQuery {
  return $(getHelper().getDomElementDataIdentifierSelector('inspectorFinishers'), getInspectorDomElement());
}

export function getValidatorsContainerDomElement(): JQuery {
  return $(getHelper().getDomElementDataIdentifierSelector('inspectorValidators'), getInspectorDomElement());
}

export function getCollectionElementDomElement(
  collectionName: keyof FormEditorDefinitions,
  collectionElementIdentifier: string
): JQuery {
  if (collectionName === 'finishers') {
    return $(getHelper().getDomElementDataAttribute(
      'finisher',
      'bracesWithKeyValue',
      [collectionElementIdentifier]
    ), getFinishersContainerDomElement());
  } else {
    return $(getHelper().getDomElementDataAttribute(
      'validator',
      'bracesWithKeyValue',
      [collectionElementIdentifier]
    ), getValidatorsContainerDomElement());
  }
}

export function renderEditors(
  formElement?: FormElement | string,
  callback?: () => void
): void {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }

  getInspectorDomElement().off().empty();

  const formElementTypeDefinition = getFormElementDefinition(formElement, undefined);
  if ('array' !== $.type(formElementTypeDefinition.editors)) {
    return;
  }

  for (let i = 0, len = formElementTypeDefinition.editors.length; i < len; ++i) {
    const template = getHelper()
      .getTemplate(formElementTypeDefinition.editors[i].templateName)
      .clone();
    if (!template.length) {
      continue;
    }
    const html = $(template.html());

    $(html)
      .first()
      .addClass(getHelper().getDomElementClassName('inspectorEditor'));
    getInspectorDomElement().append($(html));

    setRandomIds(html);
    renderEditorDispatcher(formElementTypeDefinition.editors[i], html);
  }

  if ('function' === $.type(callback)) {
    callback();
  }
}

/**
 * @publish view/inspector/collectionElements/dnd/update
 * @throws 1478354853
 * @throws 1478354854
 */
export function renderCollectionElementEditors(
  collectionName: keyof FormEditorDefinitions,
  collectionElementIdentifier: string
): void {
  let collapseWrapper, collapsePanel, collectionContainer;

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
  if ('array' !== $.type(collectionElementConfiguration.editors)) {
    return;
  }

  const collectionContainerElementWrapper = $('<div></div>')
    .addClass(getHelper().getDomElementClassName('collectionElement'))
    .addClass('panel')
    .addClass('panel-default');
  if (collectionName === 'finishers') {
    collectionContainer = getFinishersContainerDomElement();
    collectionContainerElementWrapper
      .attr(getHelper().getDomElementDataAttribute('finisher'), collectionElementIdentifier);
  } else {
    collectionContainer = getValidatorsContainerDomElement();
    collectionContainerElementWrapper
      .attr(getHelper().getDomElementDataAttribute('validator'), collectionElementIdentifier);
  }
  collectionContainer.append(collectionContainerElementWrapper);

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
    const template = getHelper()
      .getTemplate(collectionElementConfiguration.editors[i].templateName)
      .clone();
    if (!template.length) {
      continue;
    }
    const html = $(template.html());

    $(html).first()
      .addClass(getCollectionElementClass(
        collectionName,
        collectionElementConfiguration.editors[i].identifier
      ))
      .addClass(getHelper().getDomElementClassName('inspectorEditor'));

    if (i === 0 && collapseWrapper) {
      getCollectionElementDomElement(collectionName, collectionElementIdentifier)
        .append(html)
        .append(collapseWrapper);
    } else if (
      i === (collectionElementEditorsLength - 1)
      && collapseWrapper
      && collectionElementConfiguration.editors[i].identifier === 'removeButton'
    ) {
      collapsePanel.append(html.get(0));
    } else if (i > 0 && collapseWrapper) {
      collapsePanel.append(html.get(0));
    } else {
      getCollectionElementDomElement(collectionName, collectionElementIdentifier).append(html);
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
    $(getHelper().getDomElementDataIdentifierSelector('collapse'), collectionContainerElementWrapper).remove();
  }

  if (configuration.isSortable) {
    addSortableCollectionElementsEvents(collectionContainer, collectionName);
  }
}

/**
 * @publish view/inspector/collectionElement/existing/selected
 * @publish view/inspector/collectionElement/new/selected
 * @throws 1475423098
 * @throws 1475423099
 * @throws 1475423100
 * @throws 1475423101
 * @throws 1478362968
 */
export function renderCollectionElementSelectionEditor(
  collectionName: keyof FormEditorDefinitions,
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
): void {
  let alreadySelectedCollectionElements, collectionContainer,
    removeSelectElement;
  assert(
    getUtility().isNonEmptyString(collectionName),
    'Invalid configuration "collectionName"',
    1478362968
  );
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475423098
  );
  assert(
    'object' === $.type(editorHtml),
    'Invalid parameter "editorHtml"',
    1475423099
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475423100
  );
  assert(
    'array' === $.type(editorConfiguration.selectOptions),
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

  collectionContainer.off().empty();

  getHelper().getTemplatePropertyDomElement('label', editorHtml).text(editorConfiguration.label);
  const selectElement = getHelper().getTemplatePropertyDomElement('selectOptions', editorHtml);
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
      selectElement.append(new Option(
        editorConfiguration.selectOptions[i].label,
        editorConfiguration.selectOptions[i].value
      ));
      if (editorConfiguration.selectOptions[i].value !== '') {
        removeSelectElement = false;
      }
    }
  }

  if (removeSelectElement) {
    getHelper()
      .getTemplatePropertyDomElement('select-group', editorHtml)
      .off()
      .empty()
      .remove();
    const labelNoSelect = getHelper()
      .getTemplatePropertyDomElement('label-no-select', editorHtml);
    if (hasAlreadySelectedCollectionElements) {
      labelNoSelect.text(editorConfiguration.label);
    } else {
      labelNoSelect.remove();
    }
    return;
  }

  getHelper().getTemplatePropertyDomElement('label-no-select', editorHtml).remove();

  selectElement.on('change', function(this: HTMLSelectElement) {
    if ($(this).val() !== '') {
      const value = $(this).val();
      $(selector`option[value="${value}"]`, $(this)).remove();

      getFormEditorApp().getPublisherSubscriber().publish(
        'view/inspector/collectionElement/new/selected',
        [value, collectionName]
      );
    }
  });
}

/**
 * @throws 1475421525
 * @throws 1475421526
 * @throws 1475421527
 * @throws 1475421528
 */
export function renderFormElementHeaderEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
): void {
  assert('object' === $.type(editorConfiguration), 'Invalid parameter "editorConfiguration"', 1475421525);
  assert('object' === $.type(editorHtml), 'Invalid parameter "editorHtml"', 1475421526);

  Icons.getIcon(
    getFormElementDefinition(getCurrentlySelectedFormElement(), 'iconIdentifier'),
    Icons.sizes.small,
    null,
    Icons.states.default
  ).then(function(icon) {
    getHelper().getTemplatePropertyDomElement('header-label', editorHtml)
      .append($(icon).addClass(getHelper().getDomElementClassName('icon')))
      .append(buildTitleByFormElement())
      .append('<code>' + getCurrentlySelectedFormElement().get('identifier') + '</code>');
  });
}

/**
 * @throws 1475421257
 * @throws 1475421258
 * @throws 1475421259
 */
export function renderCollectionElementHeaderEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421258
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475421257
  );
  assert(
    'object' === $.type(editorHtml),
    'Invalid parameter "editorHtml"',
    1475421259
  );

  const setData = function(icon?: string) {
    const iconPlaceholder = getHelper()
      .getTemplatePropertyDomElement('panel-icon', editorHtml);
    if (icon) {
      iconPlaceholder.replaceWith(icon);
    } else {
      iconPlaceholder.remove();
    }

    const editors = getFormEditorApp().getPropertyCollectionElementConfiguration(
      collectionElementIdentifier,
      collectionName
    ).editors;

    if (!(
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
      getHelper()
        .getTemplatePropertyDomElement('panel-heading-row', editorHtml)
        .find('.panel-title')
        .before(caret);
      getHelper()
        .getTemplatePropertyDomElement('panel-heading-row', editorHtml)
        .wrapInner(button);
    }

    // Move delete button
    const collectionElement = getCollectionElementDomElement(collectionName, collectionElementIdentifier).get(0);
    const removeButtonElement = collectionElement.querySelector('.formeditor-inspector-element-remove-button');
    if (removeButtonElement) {
      const removeButton = removeButtonElement.querySelector('button');
      removeButton.classList.add('btn-sm');
      removeButton.querySelector('.btn-label').classList.add('visually-hidden');
      const panelActions = document.createElement('div');
      panelActions.classList.add('panel-actions');
      panelActions.append(removeButton);
      getHelper()
        .getTemplatePropertyDomElement('panel-heading-row', editorHtml)
        .append(panelActions);
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
    getHelper()
      .getTemplatePropertyDomElement('panel-title', editorHtml)
      .removeAttr('data-template-property')
      .append(editorConfiguration.label);
  }
}

export function renderFileMaxSizeEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421258
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1475421257
  );
  assert(
    'object' === $.type(editorHtml),
    'Invalid parameter "editorHtml"',
    1475421259
  );

  if (editorConfiguration.label) {
    const element = getHelper().getTemplatePropertyDomElement('label', editorHtml);
    const maximumFileSize = element.attr(getHelper().getDomElementDataAttribute('maximumFileSize'));
    element.append(editorConfiguration.label.replace('{0}', maximumFileSize));
  }
}

/**
 * @throws 1475421053
 * @throws 1475421054
 * @throws 1475421055
 * @throws 1475421056
 */
export function renderTextEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421053
  );
  assert(
    'object' === $.type(editorHtml),
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

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);
  if (getUtility().isNonEmptyString(editorConfiguration.fieldExplanationText)) {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .text(editorConfiguration.fieldExplanationText);
  } else {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .remove();
  }

  if (getUtility().isNonEmptyString(editorConfiguration.placeholder)) {
    getHelper()
      .getTemplatePropertyDomElement('propertyPath', editorHtml)
      .attr('placeholder', editorConfiguration.placeholder);
  }

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath,
    collectionElementIdentifier,
    collectionName
  );
  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);

  validateCollectionElement(propertyPath, editorHtml);

  getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).val(propertyData);

  if (
    !getUtility().isUndefinedOrNull(editorConfiguration.additionalElementPropertyPaths)
    && 'array' === $.type(editorConfiguration.additionalElementPropertyPaths)
  ) {
    for (let i = 0, len = editorConfiguration.additionalElementPropertyPaths.length; i < len; ++i) {
      getCurrentlySelectedFormElement().set(editorConfiguration.additionalElementPropertyPaths[i], propertyData);
    }
  }

  renderFormElementSelectorEditorAddition(editorConfiguration, editorHtml, propertyPath);

  getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).on('keyup paste', function(this: HTMLInputElement) {
    if (
      !!editorConfiguration.doNotSetIfPropertyValueIsEmpty
      && !getUtility().isNonEmptyString($(this).val())
    ) {
      getCurrentlySelectedFormElement().unset(propertyPath);
    } else {
      getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
    }
    validateCollectionElement(propertyPath, editorHtml);
    if (
      !getUtility().isUndefinedOrNull(editorConfiguration.additionalElementPropertyPaths)
      && 'array' === $.type(editorConfiguration.additionalElementPropertyPaths)
    ) {
      for (let i = 0, len = editorConfiguration.additionalElementPropertyPaths.length; i < len; ++i) {
        if (
          !!editorConfiguration.doNotSetIfPropertyValueIsEmpty
          && !getUtility().isNonEmptyString($(this).val())
        ) {
          getCurrentlySelectedFormElement().unset(editorConfiguration.additionalElementPropertyPaths[i]);
        } else {
          getCurrentlySelectedFormElement().set(editorConfiguration.additionalElementPropertyPaths[i], $(this).val());
        }
      }
    }
  });
}

/**
 * @throws 1489874120
 * @throws 1489874121
 * @throws 1489874122
 * @throws 1489874123
 */
export function renderValidationErrorMessageEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1489874121
  );
  assert(
    'object' === $.type(editorHtml),
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

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);
  if (getUtility().isNonEmptyString(editorConfiguration.fieldExplanationText)) {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .text(editorConfiguration.fieldExplanationText);
  } else {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .remove();
  }

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath
  );

  let propertyData: PropertyData = getCurrentlySelectedFormElement().get(propertyPath);

  if (
    !getUtility().isUndefinedOrNull(propertyData)
    && 'array' === $.type(propertyData)
  ) {
    const validationErrorMessage = getFirstAvailableValidationErrorMessage(editorConfiguration.errorCodes, propertyData);

    if (!getUtility().isUndefinedOrNull(validationErrorMessage)) {
      getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).val(validationErrorMessage);
    }
  }

  getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).on('keyup paste', function(this: HTMLInputElement) {
    propertyData = getCurrentlySelectedFormElement().get(propertyPath);
    if (getUtility().isUndefinedOrNull(propertyData)) {
      propertyData = [];
    }
    getCurrentlySelectedFormElement().set(propertyPath, renewValidationErrorMessages(
      editorConfiguration.errorCodes,
      propertyData,
      $(this).val()
    ));
  });
}

/**
 * @throws 1674826430
 * @throws 1674826431
 * @throws 1674826432
 */
export function renderCountrySelectEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1674826430
  );
  assert(
    'object' === $.type(editorHtml),
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

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);

  const selectElement = getHelper()
    .getTemplatePropertyDomElement('selectOptions', editorHtml);

  const propertyData: Record<string, string> = getCurrentlySelectedFormElement().get(propertyPath) || {};
  validateCollectionElement(propertyPath, editorHtml);

  const options = $('option', selectElement);
  selectElement.empty();

  for (let i = 0, len = options.length; i < len; ++i) {
    let selected = false;

    for (const propertyDataKey of Object.keys(propertyData)) {
      if ((options[i] as HTMLOptionElement).value === propertyData[propertyDataKey]) {
        selected = true;
        break;
      }
    }

    const option = new Option((options[i] as HTMLOptionElement).text, i.toString(), false, selected);
    $(option).data({ value: (options[i] as HTMLOptionElement).value });
    selectElement.append(option);
  }

  selectElement.on('change', function(this: HTMLSelectElement) {
    const selectValues: string[] = [];
    $('option:selected', $(this)).each(function(this: HTMLOptionElement) {
      selectValues.push($(this).data('value'));
    });

    getCurrentlySelectedFormElement().set(propertyPath, selectValues);
    validateCollectionElement(propertyPath, editorHtml);
  });
}

/**
 * @throws 1475421048
 * @throws 1475421049
 * @throws 1475421050
 * @throws 1475421051
 * @throws 1475421052
 */
export function renderSingleSelectEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475421048
  );
  assert(
    'object' === $.type(editorHtml),
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
    'array' === $.type(editorConfiguration.selectOptions),
    'Invalid configuration "selectOptions"',
    1475421052
  );

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath,
    collectionElementIdentifier,
    collectionName
  );

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);

  const selectElement = getHelper()
    .getTemplatePropertyDomElement('selectOptions', editorHtml);

  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);
  validateCollectionElement(propertyPath, editorHtml);

  for (let i = 0, len = editorConfiguration.selectOptions.length; i < len; ++i) {
    let option;

    if (editorConfiguration.selectOptions[i].value === propertyData) {
      option = new Option(editorConfiguration.selectOptions[i].label, i.toString(), false, true);
    } else {
      option = new Option(editorConfiguration.selectOptions[i].label, i.toString());
    }
    $(option).data({ value: editorConfiguration.selectOptions[i].value });
    selectElement.append(option);
  }

  selectElement.on('change', function(this: HTMLSelectElement) {
    getCurrentlySelectedFormElement().set(propertyPath, $('option:selected', $(this)).data('value'));
    validateCollectionElement(propertyPath, editorHtml);
  });
}

/**
 * @throws 1485712399
 * @throws 1485712400
 * @throws 1485712401
 * @throws 1485712402
 * @throws 1485712403
 */
export function renderMultiSelectEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1485712399
  );
  assert(
    'object' === $.type(editorHtml),
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
    'array' === $.type(editorConfiguration.selectOptions),
    'Invalid configuration "selectOptions"',
    1485712403
  );

  const propertyPath = getFormEditorApp().buildPropertyPath(
    editorConfiguration.propertyPath,
    collectionElementIdentifier,
    collectionName
  );

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);

  const selectElement = getHelper()
    .getTemplatePropertyDomElement('selectOptions', editorHtml);

  const propertyData: Record<string, string> = getCurrentlySelectedFormElement().get(propertyPath) || {};
  validateCollectionElement(propertyPath, editorHtml);

  for (let i = 0, len1 = editorConfiguration.selectOptions.length; i < len1; ++i) {
    let option = null;
    for (const propertyDataKey of Object.keys(propertyData)) {
      if (editorConfiguration.selectOptions[i].value === propertyData[propertyDataKey]) {
        option = new Option(editorConfiguration.selectOptions[i].label, i.toString(), false, true);
        break;
      }
    }

    if (!option) {
      option = new Option(editorConfiguration.selectOptions[i].label, i.toString());
    }

    $(option).data({ value: editorConfiguration.selectOptions[i].value });

    selectElement.append(option);
  }

  selectElement.on('change', function(this: HTMLSelectElement) {
    const selectValues: string[] = [];
    $('option:selected', $(this)).each(function(this: HTMLOptionElement) {
      selectValues.push($(this).data('value'));
    });

    getCurrentlySelectedFormElement().set(propertyPath, selectValues);
    validateCollectionElement(propertyPath, editorHtml);
  });
}

/**
 * @throws 1489528242
 * @throws 1489528243
 * @throws 1489528244
 * @throws 1489528245
 * @throws 1489528246
 * @throws 1489528247
 */
export function renderGridColumnViewPortConfigurationEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1489528242
  );
  assert(
    'object' === $.type(editorHtml),
    'Invalid parameter "editorHtml"',
    1489528243
  );
  assert(
    getUtility().isNonEmptyString(editorConfiguration.label),
    'Invalid configuration "label"',
    1489528244
  );
  assert(
    'array' === $.type(editorConfiguration.configurationOptions.viewPorts),
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

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);


  const viewportButtonTemplate = $(getHelper()
    .getDomElementDataIdentifierSelector('viewportButton'), $(editorHtml))
    .clone();

  $(getHelper()
    .getDomElementDataIdentifierSelector('viewportButton'), $(editorHtml))
    .remove();

  const numbersOfColumnsTemplate = getHelper()
    .getTemplatePropertyDomElement('numbersOfColumnsToUse', $(editorHtml))
    .clone();

  getHelper()
    .getTemplatePropertyDomElement('numbersOfColumnsToUse', $(editorHtml))
    .remove();

  const editorControlsWrapper = getEditorControlsWrapperDomElement(editorHtml);

  const initNumbersOfColumnsField = function(element: JQuery) {
    getHelper().getTemplatePropertyDomElement('numbersOfColumnsToUse', $(editorHtml))
      .off()
      .empty()
      .remove();

    const numbersOfColumnsTemplateClone = $(numbersOfColumnsTemplate).clone(true, true);
    getEditorWrapperDomElement(editorHtml).after(numbersOfColumnsTemplateClone);

    $('input', numbersOfColumnsTemplateClone).focus();

    getHelper()
      .getTemplatePropertyDomElement('numbersOfColumnsToUse-label', numbersOfColumnsTemplateClone)
      .append(
        editorConfiguration.configurationOptions.numbersOfColumnsToUse.label
          .replace('{@viewPortLabel}', element.data('viewPortLabel'))
      );

    getHelper()
      .getTemplatePropertyDomElement('numbersOfColumnsToUse-fieldExplanationText', numbersOfColumnsTemplateClone)
      .append(editorConfiguration.configurationOptions.numbersOfColumnsToUse.fieldExplanationText);

    const propertyPath = editorConfiguration.configurationOptions.numbersOfColumnsToUse.propertyPath
      .replace('{@viewPortIdentifier}', element.data('viewPortIdentifier'));

    getHelper()
      .getTemplatePropertyDomElement('numbersOfColumnsToUse-propertyPath', numbersOfColumnsTemplateClone)
      .val(getCurrentlySelectedFormElement().get(propertyPath));

    getHelper().getTemplatePropertyDomElement('numbersOfColumnsToUse-propertyPath', numbersOfColumnsTemplateClone).on('keyup paste change', function(this: HTMLInputElement) {
      const that = $(this);
      if (!$.isNumeric(that.val())) {
        that.val('');
      }
      getCurrentlySelectedFormElement().set(propertyPath, that.val());
    });
  };

  for (let i = 0, len = editorConfiguration.configurationOptions.viewPorts.length; i < len; ++i) {
    const viewPortIdentifier = editorConfiguration.configurationOptions.viewPorts[i].viewPortIdentifier;
    const viewPortLabel = editorConfiguration.configurationOptions.viewPorts[i].label;

    const viewportButtonTemplateClone = $(viewportButtonTemplate).clone(true, true);
    viewportButtonTemplateClone.text(viewPortIdentifier);
    viewportButtonTemplateClone.data('viewPortIdentifier', viewPortIdentifier);
    viewportButtonTemplateClone.data('viewPortLabel', viewPortLabel);
    viewportButtonTemplateClone.attr('title', viewPortLabel);
    editorControlsWrapper.append(viewportButtonTemplateClone);

    if (i === (len - 1)) {
      const numbersOfColumnsTemplateClone = $(numbersOfColumnsTemplate).clone(true, true);
      getEditorWrapperDomElement(editorHtml).after(numbersOfColumnsTemplateClone);
      initNumbersOfColumnsField(viewportButtonTemplateClone);
      viewportButtonTemplateClone.addClass(getHelper().getDomElementClassName('active'));
    }

    $('button', editorControlsWrapper).on('click', function(this: HTMLButtonElement) {
      const that = $(this);

      $('button', editorControlsWrapper).removeClass(getHelper().getDomElementClassName('active'));
      that.addClass(getHelper().getDomElementClassName('active'));

      initNumbersOfColumnsField(that);
    });
  }
}

/**
 * @throws 1475419226
 * @throws 1475419227
 * @throws 1475419228
 * @throws 1475419229
 * @throws 1475419230
 * @throws 1475419231
 * @throws 1475419232
 */
export function renderPropertyGridEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475419226
  );
  assert(
    'object' === $.type(editorHtml),
    'Invalid parameter "editorHtml"',
    1475419227
  );
  assert(
    'boolean' === $.type(editorConfiguration.enableAddRow),
    'Invalid configuration "enableAddRow"',
    1475419228
  );
  assert(
    'boolean' === $.type(editorConfiguration.enableDeleteRow),
    'Invalid configuration "enableDeleteRow"',
    1475419230
  );
  assert(
    'boolean' === $.type(editorConfiguration.isSortable),
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

  getHelper().getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);
  if (getUtility().isNonEmptyString(editorConfiguration.fieldExplanationText)) {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .text(editorConfiguration.fieldExplanationText);
  } else {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .remove();
  }

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

  const propertyGridEditor = editorHtml instanceof HTMLElement ?
    editorHtml.querySelector('typo3-form-property-grid-editor') :
    editorHtml.get(0).querySelector('typo3-form-property-grid-editor');
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
      }
      if (gridColumnConfig.name === 'value') {
        propertyGridEditor.labelValue = gridColumnConfig.title;
      }
      if (gridColumnConfig.name === 'selected') {
        propertyGridEditor.labelSelected = gridColumnConfig.title;
      }
    });
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
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475417093
  );
  assert(
    'object' === $.type(editorHtml),
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
  getHelper().getTemplatePropertyDomElement('label', editorHtml).append(editorConfiguration.label);

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

  const validationErrorMessageTemplate = getHelper()
    .getTemplatePropertyDomElement('validationErrorMessage', $(editorHtml))
    .clone();

  getHelper()
    .getTemplatePropertyDomElement('validationErrorMessage', $(editorHtml))
    .remove();

  const showValidationErrorMessage = function() {
    const validationErrorMessageTemplateClone = $(validationErrorMessageTemplate).clone(true, true);
    getEditorWrapperDomElement(editorHtml).after(validationErrorMessageTemplateClone);

    getHelper()
      .getTemplatePropertyDomElement('validationErrorMessage-label', validationErrorMessageTemplateClone)
      .append(editorConfiguration.configurationOptions.validationErrorMessage.label);

    getHelper()
      .getTemplatePropertyDomElement('validationErrorMessage-fieldExplanationText', validationErrorMessageTemplateClone)
      .append(editorConfiguration.configurationOptions.validationErrorMessage.fieldExplanationText);

    propertyData = getCurrentlySelectedFormElement().get(validationErrorMessagePropertyPath);
    if (getUtility().isUndefinedOrNull(propertyData)) {
      propertyData = [];
    }

    const validationErrorMessage = getFirstAvailableValidationErrorMessage(
      editorConfiguration.configurationOptions.validationErrorMessage.errorCodes,
      propertyData
    );
    if (!getUtility().isUndefinedOrNull(validationErrorMessage)) {
      getHelper()
        .getTemplatePropertyDomElement('validationErrorMessage-propertyPath', validationErrorMessageTemplateClone)
        .val(validationErrorMessage);
    }

    getHelper().getTemplatePropertyDomElement('validationErrorMessage-propertyPath', validationErrorMessageTemplateClone).on('keyup paste', function(this: HTMLInputElement) {
      let propertyData = getCurrentlySelectedFormElement().get(validationErrorMessagePropertyPath);
      if (getUtility().isUndefinedOrNull(propertyData)) {
        propertyData = [];
      }

      getCurrentlySelectedFormElement().set(validationErrorMessagePropertyPath, renewValidationErrorMessages(
        editorConfiguration.configurationOptions.validationErrorMessage.errorCodes,
        propertyData,
        $(this).val()
      ));
    });
  };

  if (-1 !== getFormEditorApp().getIndexFromPropertyCollectionElement(validatorIdentifier, 'validators')) {
    $('input[type="checkbox"]', $(editorHtml)).prop('checked', true);
    showValidationErrorMessage();
  }

  $('input[type="checkbox"]', $(editorHtml)).on('change', function(this: HTMLInputElement) {
    getHelper().getTemplatePropertyDomElement('validationErrorMessage', $(editorHtml))
      .off()
      .empty()
      .remove();

    if ($(this).is(':checked')) {
      showValidationErrorMessage();
      getPublisherSubscriber().publish(
        'view/inspector/collectionElement/new/selected',
        [validatorIdentifier, 'validators']
      );

      if (getUtility().isNonEmptyString(propertyPath)) {
        getCurrentlySelectedFormElement().set(propertyPath, propertyValue);
      }
    } else {
      getPublisherSubscriber().publish(
        'view/inspector/removeCollectionElement/perform',
        [validatorIdentifier, 'validators']
      );
      if (getUtility().isNonEmptyString(propertyPath)) {
        getCurrentlySelectedFormElement().unset(propertyPath);
      }

      propertyData = getCurrentlySelectedFormElement().get(validationErrorMessagePropertyPath);
      if (getUtility().isUndefinedOrNull(propertyData)) {
        propertyData = [];
      }

      getCurrentlySelectedFormElement().set(validationErrorMessagePropertyPath, renewValidationErrorMessages(
        editorConfiguration.configurationOptions.validationErrorMessage.errorCodes,
        propertyData,
        ''
      ));
    }
  });
}

/**
 * @throws 1476218671
 * @throws 1476218672
 * @throws 1476218673
 * @throws 1476218674
 */
export function renderCheckboxEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1476218671
  );
  assert(
    'object' === $.type(editorHtml),
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

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);
  if (getUtility().isNonEmptyString(editorConfiguration.fieldExplanationText)) {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .text(editorConfiguration.fieldExplanationText);
  } else {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .remove();
  }

  const propertyPath = getFormEditorApp()
    .buildPropertyPath(editorConfiguration.propertyPath, collectionElementIdentifier, collectionName);
  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);

  if (
    ('boolean' === $.type(propertyData) && propertyData)
    || propertyData === 'true'
    || propertyData === 1
    || propertyData === '1'
  ) {
    $('input[type="checkbox"]', $(editorHtml)).prop('checked', true);
  }

  $('input[type="checkbox"]', $(editorHtml)).on('change', function(this: HTMLInputElement) {
    if ($(this).is(':checked')) {
      getCurrentlySelectedFormElement().set(propertyPath, true);
    } else {
      getCurrentlySelectedFormElement().set(propertyPath, false);
    }
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
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1475412567
  );
  assert(
    'object' === $.type(editorHtml),
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

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml).append(editorConfiguration.label);

  if (getUtility().isNonEmptyString(editorConfiguration.fieldExplanationText)) {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .text(editorConfiguration.fieldExplanationText);
  } else {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .remove();
  }

  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);
  $('textarea', $(editorHtml)).val(propertyData);
  validateCollectionElement(propertyPath, editorHtml);

  $('textarea', $(editorHtml)).on('keyup paste', function(this: HTMLTextAreaElement) {
    getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
    validateCollectionElement(propertyPath, editorHtml);
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
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1477300587
  );
  assert(
    'object' === $.type(editorHtml),
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

  getHelper()
    .getTemplatePropertyDomElement('label', editorHtml)
    .append(editorConfiguration.label);
  getHelper()
    .getTemplatePropertyDomElement('buttonLabel', editorHtml)
    .append(editorConfiguration.buttonLabel);

  if (getUtility().isNonEmptyString(editorConfiguration.fieldExplanationText)) {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .text(editorConfiguration.fieldExplanationText);
  } else {
    getHelper()
      .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
      .remove();
  }

  $('form', $(editorHtml)).prop('name', editorConfiguration.propertyPath);

  Icons.getIcon(editorConfiguration.iconIdentifier, Icons.sizes.small).then(function(icon) {
    getHelper().getTemplatePropertyDomElement('image', editorHtml).append($(icon));
  });

  getHelper().getTemplatePropertyDomElement('onclick', editorHtml).on('click', function(this: HTMLElement) {
    const randomIdentifier = Math.floor((Math.random() * 100000) + 1);
    const insertTarget = $(this)
      .closest(getHelper().getDomElementDataIdentifierSelector('editorControlsWrapper'))
      .find(getHelper().getDomElementDataAttribute('contentElementSelectorTarget', 'bracesWithKey'));

    insertTarget.attr(getHelper().getDomElementDataAttribute('contentElementSelectorTarget'), randomIdentifier);
    openTypo3WinBrowser('db', randomIdentifier + '|||' + editorConfiguration.browsableType);
  });

  listenOnElementBrowser();

  const propertyPath = getFormEditorApp().buildPropertyPath(editorConfiguration.propertyPath, collectionElementIdentifier, collectionName);
  const propertyData = getCurrentlySelectedFormElement().get(propertyPath);

  validateCollectionElement(propertyPath, editorHtml);
  getHelper()
    .getTemplatePropertyDomElement('propertyPath', editorHtml)
    .val(propertyData);

  getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).on('keyup paste', function(this: HTMLInputElement) {
    getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
    validateCollectionElement(propertyPath, editorHtml);
  });
}

/**
 * @throws 1475412563
 * @throws 1475412564
 */
export function renderRemoveElementEditor(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions
): void {
  assert('object' === $.type(editorConfiguration), 'Invalid parameter "editorConfiguration"', 1475412563);
  assert('object' === $.type(editorHtml), 'Invalid parameter "editorHtml"', 1475412564);

  if (getUtility().isUndefinedOrNull(collectionElementIdentifier)) {

    $('button', $(editorHtml))
      .addClass(
        getHelper().getDomElementClassName('buttonFormElementRemove') + ' ' +
        getHelper().getDomElementClassName('buttonFormEditor')
      );
  } else {
    $('button', $(editorHtml)).addClass(
      getHelper().getDomElementClassName('buttonCollectionElementRemove')
    );
  }

  $('button', $(editorHtml)).on('click', function() {
    if (getUtility().isUndefinedOrNull(collectionElementIdentifier)) {
      getViewModel().showRemoveFormElementModal();
    } else {
      getViewModel().showRemoveCollectionElementModal(collectionElementIdentifier, collectionName);
    }
  });
}

/**
 * @throws 1484574704
 * @throws 1484574705
 * @throws 1484574706
 */
export function renderFormElementSelectorEditorAddition(
  editorConfiguration: EditorConfiguration,
  editorHtml: HTMLElement | JQuery,
  propertyPath: string
): void {
  assert(
    'object' === $.type(editorConfiguration),
    'Invalid parameter "editorConfiguration"',
    1484574704
  );
  assert(
    'object' === $.type(editorHtml),
    'Invalid parameter "editorHtml"',
    1484574705
  );
  assert(
    getUtility().isNonEmptyString(propertyPath),
    'Invalid parameter "propertyPath"',
    1484574706
  );

  const formElementSelectorControlsWrapper = $(
    getHelper().getDomElementDataIdentifierSelector('formElementSelectorControlsWrapper'), editorHtml
  );

  if (editorConfiguration.enableFormelementSelectionButton === true) {
    if (formElementSelectorControlsWrapper.length === 0) {
      return;
    }

    const formElementSelectorSplitButtonListContainer = $(
      getHelper().getDomElementDataIdentifierSelector('formElementSelectorSplitButtonListContainer'), editorHtml
    );

    formElementSelectorSplitButtonListContainer.off().empty();
    const nonCompositeNonToplevelFormElements = getFormEditorApp().getNonCompositeNonToplevelFormElements();

    if (nonCompositeNonToplevelFormElements.length === 0) {
      Icons.getIcon(
        getHelper().getDomElementDataAttributeValue('iconNotAvailable'),
        Icons.sizes.small,
        null,
        Icons.states.default
      ).then(function(icon) {
        const itemTemplate = $('<li data-no-sorting>'
          + '<span class="dropdown-item"></span>'
          + '</li>');

        itemTemplate.find('span')
          .append($(icon))
          .append(' ' + getFormElementDefinition(getRootFormElement(), 'inspectorEditorFormElementSelectorNoElements'));
        formElementSelectorSplitButtonListContainer.append(itemTemplate);
      });
    } else {
      $.each(nonCompositeNonToplevelFormElements, function(i, nonCompositeNonToplevelFormElement) {
        Icons.getIcon(
          getFormElementDefinition(nonCompositeNonToplevelFormElement, 'iconIdentifier'),
          Icons.sizes.small,
          null,
          Icons.states.default
        ).then(function(icon) {
          const itemTemplate = $('<li data-no-sorting>'
            + '<a href="#" class="dropdown-item" data-formelement-identifier="' + nonCompositeNonToplevelFormElement.get('identifier') + '">'
            + '</a>'
            + '</li>');

          $(selector`[data-formelement-identifier="${nonCompositeNonToplevelFormElement.get('identifier')}"]`, itemTemplate)
            .append($(icon))
            .append(' ' + nonCompositeNonToplevelFormElement.get('label'));

          $('a', itemTemplate).on('click', function(this: HTMLElement) {
            let propertyData;

            propertyData = getCurrentlySelectedFormElement().get(propertyPath) || '';

            if (propertyData.length === 0) {
              propertyData = '{' + $(this).attr('data-formelement-identifier') + '}';
            } else {
              propertyData = propertyData + ' ' + '{' + $(this).attr('data-formelement-identifier') + '}';
            }

            getCurrentlySelectedFormElement().set(propertyPath, propertyData);
            getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).val(propertyData);
            validateCollectionElement(propertyPath, editorHtml);
          });

          formElementSelectorSplitButtonListContainer.append(itemTemplate);
        });
      });
    }
  } else {
    $(getHelper().getDomElementDataIdentifierSelector('editorControlsInputGroup'), editorHtml)
      .removeClass(getHelper().getDomElementClassName('inspectorInputGroup'));
    formElementSelectorControlsWrapper.off().empty().remove();
  }
}

/**
 * @throws 1478967319
 */
export function buildTitleByFormElement(formElement?: FormElement): HTMLElement {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1478967319);

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

export function bootstrap(
  this: typeof import('./inspector-component'),
  _formEditorApp: FormEditor,
  customConfiguration?: Configuration
): typeof import('./inspector-component') {
  formEditorApp = _formEditorApp;
  configuration = $.extend(true, defaultConfiguration, customConfiguration || {});
  Helper.bootstrap(formEditorApp);
  return this;
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'view/inspector/removeCollectionElement/perform': readonly [
      validatorIdentifier: string,
      info: 'validators',
      // @todo formElement is never published, but used by
      // media subscribe('view/inspector/removeCollectionElement/perform', .)
      // Can this be removed or is it possibly used by extensions?
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
      editorHtml: HTMLElement | JQuery,
      collectionElementIdentifier: string,
      collectionName: keyof FormEditorDefinitions
    ];
  }
}
