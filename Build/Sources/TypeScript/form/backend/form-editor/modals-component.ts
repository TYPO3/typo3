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
 * Module: @typo3/form/backend/form-editor/modals-component
 */
import $ from 'jquery';
import * as Helper from '@typo3/form/backend/form-editor/helper';
import Modal, { Button } from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import type {
  FormEditor,
} from '@typo3/form/backend/form-editor';
import type {
  Utility,
  FormEditorDefinitions,
  FormElement,
  FormElementDefinition,
  NoInfer,
  PublisherSubscriber,
  ValidationResultsRecursive
} from '@typo3/form/backend/form-editor/core';
import type {
  Configuration as HelperConfiguration,
} from '@typo3/form/backend/form-editor/helper';

export interface InsertElementsModalConfiguration {
  disableElementTypes: string[],
  onlyEnableElementTypes?: string[],
}

let configuration: HelperConfiguration = null;

const defaultConfiguration: Partial<HelperConfiguration> = {
  domElementClassNames: {
    buttonDefault: 'btn-default',
    buttonInfo: 'btn-info',
    buttonWarning: 'btn-warning'
  },
  domElementDataAttributeNames: {
    elementType: 'element-type',
    fullElementType: 'data-element-type'
  },
  domElementDataAttributeValues: {
    rowItem: 'rowItem',
    rowLink: 'rowLink',
    rowsContainer: 'rowsContainer',
    templateInsertElements: 'Modal-InsertElements',
    templateInsertPages: 'Modal-InsertPages',
    templateValidationErrors: 'Modal-ValidationErrors'
  }
};

let formEditorApp: FormEditor = null;

function getFormEditorApp(): FormEditor {
  return formEditorApp;
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

function getPublisherSubscriber(): PublisherSubscriber {
  return getFormEditorApp().getPublisherSubscriber();
}

function getFormElementDefinition<T extends keyof FormElementDefinition>(
  formElement: FormElement,
  formElementDefinitionKey?: T
): T extends keyof FormElementDefinition ? FormElementDefinition[T] : FormElementDefinition {
  return getFormEditorApp().getFormElementDefinition(formElement, formElementDefinitionKey);
}

/**
 * @throws 1478889044
 * @throws 1478889049
 */
function showRemoveElementModal<T extends keyof PublisherSubscriberTopicArgumentsMap>(
  publisherTopicName: T,
  publisherTopicArguments: NoInfer<PublisherSubscriberTopicArgumentsMap[T]>
): void {
  const modalButtons: Button[] = [];
  assert(
    getUtility().isNonEmptyString(publisherTopicName),
    'Invalid parameter "publisherTopicName"',
    1478889049
  );
  assert(
    'array' === $.type(publisherTopicArguments),
    'Invalid parameter "formElement"',
    1478889044
  );

  modalButtons.push({
    text: getFormElementDefinition(getRootFormElement(), 'modalRemoveElementCancelButton'),
    active: true,
    btnClass: getHelper().getDomElementClassName('buttonDefault'),
    name: 'cancel',
    trigger: (e, modal) => {
      modal.hideModal();
    }
  });

  modalButtons.push({
    text: getFormElementDefinition(getRootFormElement(), 'modalRemoveElementConfirmButton'),
    active: true,
    btnClass: getHelper().getDomElementClassName('buttonWarning'),
    name: 'confirm',
    trigger: (e, modal) => {
      getPublisherSubscriber().publish(publisherTopicName, publisherTopicArguments);
      modal.hideModal();
    }
  });

  Modal.show(
    getFormElementDefinition(getRootFormElement(), 'modalRemoveElementDialogTitle'),
    getFormElementDefinition(getRootFormElement(), 'modalRemoveElementDialogMessage'),
    Severity.warning,
    modalButtons
  );
}

/**
 * @publish mixed
 * @throws 1478910954
 */
function insertElementsModalSetup(
  modalContent: JQuery,
  publisherTopicName: keyof PublisherSubscriberTopicArgumentsMap,
  configuration?: InsertElementsModalConfiguration
): void {
  assert(
    getUtility().isNonEmptyString(publisherTopicName),
    'Invalid parameter "publisherTopicName"',
    1478910954
  );

  if ('object' === $.type(configuration)) {
    for (const key of Object.keys(configuration)) {
      if (
        key === 'disableElementTypes'
        && 'array' === $.type(configuration[key])
      ) {
        for (let i = 0, len = configuration[key].length; i < len; ++i) {
          $(
            getHelper().getDomElementDataAttribute(
              'fullElementType',
              'bracesWithKeyValue', [configuration[key][i]]
            ),
            modalContent
          ).addClass(getHelper().getDomElementClassName('disabled'));
        }
      }

      if (
        key === 'onlyEnableElementTypes'
        && 'array' === $.type(configuration[key])
      ) {
        $(
          getHelper().getDomElementDataAttribute(
            'fullElementType',
            'bracesWithKey'
          ),
          modalContent
        ).each(function(this: HTMLElement) {
          for (let i = 0, len = configuration[key].length; i < len; ++i) {
            const that = $(this);
            if (that.data(getHelper().getDomElementDataAttribute('elementType')) !== configuration[key][i]) {
              that.addClass(getHelper().getDomElementClassName('disabled'));
            }
          }
        });
      }
    }
  }

  $('a', modalContent).on('click', function(this: HTMLElement) {
    getPublisherSubscriber().publish(publisherTopicName, [$(this).data(getHelper().getDomElementDataAttribute('elementType'))]);
    $('a', modalContent).off();
    Modal.currentModal.hideModal();
  });
}

/**
 * @publish view/modal/validationErrors/element/clicked
 * @throws 1479161268
 */
function _validationErrorsModalSetup(
  modalContent: JQuery,
  validationResults: ValidationResultsRecursive
): void {
  let formElement, newRowItem;

  assert(
    'array' === $.type(validationResults),
    'Invalid parameter "validationResults"',
    1479161268
  );

  const rowItemTemplate = $(
    getHelper().getDomElementDataIdentifierSelector('rowItem'),
    modalContent
  ).clone();

  $(getHelper().getDomElementDataIdentifierSelector('rowItem'), modalContent).remove();

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
      formElement = getFormEditorApp()
        .getFormElementByIdentifierPath(validationResults[i].formElementIdentifierPath);
      newRowItem = rowItemTemplate.clone();
      $(getHelper().getDomElementDataIdentifierSelector('rowLink'), newRowItem)
        .attr(
          getHelper().getDomElementDataAttribute('elementIdentifier'),
          validationResults[i].formElementIdentifierPath
        )
        .get(0).replaceChildren(_buildTitleByFormElement(formElement));
      $(getHelper().getDomElementDataIdentifierSelector('rowsContainer'), modalContent)
        .append(newRowItem);
    }
  }

  $('a', modalContent).on('click', function(this: HTMLElement) {
    getPublisherSubscriber().publish('view/modal/validationErrors/element/clicked', [
      $(this).attr(getHelper().getDomElementDataAttribute('elementIdentifier'))
    ]);
    $('a', modalContent).off();
    Modal.currentModal.hideModal();
  });
}

/**
 * @throws 1479162557
 */
function _buildTitleByFormElement(formElement: FormElement): HTMLElement {
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1479162557);

  const span = document.createElement('span');
  span.textContent = formElement.get('label') ? formElement.get('label') : formElement.get('identifier');
  return span;
}

/* *************************************************************
 * Public Methods
 * ************************************************************/

/**
 * @publish view/modal/removeFormElement/perform
 */
export function showRemoveFormElementModal(formElement: FormElement): void {
  showRemoveElementModal('view/modal/removeFormElement/perform', [formElement]);
}

/**
 * @publish view/modal/removeCollectionElement/perform
 * @throws 1478894420
 * @throws 1478894421
 */
export function showRemoveCollectionElementModal(
  collectionElementIdentifier: string,
  collectionName: keyof FormEditorDefinitions,
  formElement: FormElement
): void {
  assert(
    getUtility().isNonEmptyString(collectionElementIdentifier),
    'Invalid parameter "collectionElementIdentifier"',
    1478894420
  );
  assert(
    getUtility().isNonEmptyString(collectionName),
    'Invalid parameter "collectionName"',
    1478894421
  );

  showRemoveElementModal('view/modal/removeCollectionElement/perform', [collectionElementIdentifier, collectionName, formElement]);
}

/**
 * @publish view/modal/close/perform
 */
export function showCloseConfirmationModal(): void {
  const modalButtons: Button[] = [];

  modalButtons.push({
    text: getFormElementDefinition(getRootFormElement(), 'modalCloseCancelButton'),
    active: true,
    btnClass: getHelper().getDomElementClassName('buttonDefault'),
    name: 'cancel',
    trigger: (e, modal) => {
      modal.hideModal();
    }
  });

  modalButtons.push({
    text: getFormElementDefinition(getRootFormElement(), 'modalCloseConfirmButton'),
    active: true,
    btnClass: getHelper().getDomElementClassName('buttonWarning'),
    name: 'confirm',
    trigger: (e, modal) => {
      getPublisherSubscriber().publish('view/modal/close/perform', []);
      modal.hideModal();
    }
  });

  Modal.show(
    getFormElementDefinition(getRootFormElement(), 'modalCloseDialogTitle'),
    getFormElementDefinition(getRootFormElement(), 'modalCloseDialogMessage'),
    Severity.warning,
    modalButtons
  );
}

export function showInsertElementsModal(
  publisherTopicName: keyof PublisherSubscriberTopicArgumentsMap,
  configuration: InsertElementsModalConfiguration
): void {
  const template = getHelper().getTemplate('templateInsertElements');
  if (template.length > 0) {
    const html = $(template.html());
    insertElementsModalSetup(html, publisherTopicName, configuration);

    Modal.show(
      getFormElementDefinition(getRootFormElement(), 'modalInsertElementsDialogTitle'),
      $(html),
      Severity.info
    );
  }
}

export function showInsertPagesModal(
  publisherTopicName: keyof PublisherSubscriberTopicArgumentsMap,
): void {
  const template = getHelper().getTemplate('templateInsertPages');
  if (template.length > 0) {
    const html = $(template.html());
    insertElementsModalSetup(html, publisherTopicName);

    Modal.show(
      getFormElementDefinition(getRootFormElement(), 'modalInsertPagesDialogTitle'),
      $(html),
      Severity.info
    );
  }
}

export function showValidationErrorsModal(validationResults: ValidationResultsRecursive): void {
  const modalButtons: Button[] = [];

  modalButtons.push({
    text: getFormElementDefinition(getRootFormElement(), 'modalValidationErrorsConfirmButton'),
    active: true,
    btnClass: getHelper().getDomElementClassName('buttonDefault'),
    name: 'confirm',
    trigger: function(e, modal) {
      modal.hideModal();
    }
  });

  const template = getHelper().getTemplate('templateValidationErrors');
  if (template.length > 0) {
    const html = $(template.html()).clone();
    _validationErrorsModalSetup(html, validationResults);

    Modal.show(
      getFormElementDefinition(getRootFormElement(), 'modalValidationErrorsDialogTitle'),
      html,
      Severity.error,
      modalButtons
    );
  }
}

export function bootstrap(
  this: typeof import('./modals-component'),
  _formEditorApp: FormEditor,
  customConfiguration?: Partial<HelperConfiguration>
): typeof import('./modals-component') {
  formEditorApp = _formEditorApp;
  configuration = $.extend(true, defaultConfiguration, customConfiguration || {});
  Helper.bootstrap(formEditorApp);
  return this;
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'view/modal/removeFormElement/perform': readonly [
      formElement: FormElement
    ];
    'view/modal/removeCollectionElement/perform': readonly [
      collectionElementIdentifier: string,
      collectionName: keyof FormEditorDefinitions,
      formElement: FormElement
    ];
    'view/modal/close/perform': readonly [];
    'view/modal/validationErrors/element/clicked': readonly [
      elementIdentifier: string
    ];
  }
}
