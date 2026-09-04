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
import * as Helper from '@typo3/form/backend/form-editor/helper';
import { merge } from 'lodash-es';
import Modal, { type Button } from '@typo3/backend/modal';
import Severity from '@typo3/backend/severity';
import type {
  FormEditor,
} from '@typo3/form/backend/form-editor';
import type {
  Utility,
  FormEditorDefinitions,
  FormElement,
  FormElementDefinition,
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
    elementIdentifier: 'elementIdentifier',
    elementType: 'elementType',
    validationErrors: 'validationErrors',
    validationErrorGroup: 'validationErrorGroup',
    validationErrorGroupLabel: 'validationErrorGroupLabel',
    validationErrorGroupItems: 'validationErrorGroupItems',
    validationError: 'validationError',
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
    Array.isArray(publisherTopicArguments),
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
  modalContent: DocumentFragment,
  publisherTopicName: keyof PublisherSubscriberTopicArgumentsMap,
  configuration?: InsertElementsModalConfiguration
): void {
  assert(
    getUtility().isNonEmptyString(publisherTopicName),
    'Invalid parameter "publisherTopicName"',
    1478910954
  );

  if (typeof configuration === 'object' && configuration !== null && !Array.isArray(configuration)) {
    for (const key of Object.keys(configuration)) {
      if (
        key === 'disableElementTypes'
        && Array.isArray(configuration[key])
      ) {
        for (let i = 0, len = configuration[key].length; i < len; ++i) {
          modalContent.querySelectorAll(
            getHelper().getDomElementDataAttribute(
              'fullElementType',
              'bracesWithKeyValue', [configuration[key][i]]
            )
          ).forEach((el) => el.classList.add(getHelper().getDomElementClassName('disabled')));
        }
      }

      if (
        key === 'onlyEnableElementTypes'
        && Array.isArray(configuration[key])
      ) {
        modalContent.querySelectorAll(
          getHelper().getDomElementDataAttribute('fullElementType', 'bracesWithKey')
        ).forEach((el: Element) => {
          const elementType = el.getAttribute(getHelper().getDomElementDataAttribute('elementType'));
          const isEnabled = configuration[key].some((type) => type === elementType);
          if (!isEnabled) {
            el.classList.add(getHelper().getDomElementClassName('disabled'));
          }
        });
      }
    }
  }

  [...modalContent.children].forEach(el => el.addEventListener('typo3:form:insert-element-click', function(e: Event) {
    getPublisherSubscriber().publish(publisherTopicName, [(<CustomEvent> e).detail.item.identifier]);
  }));
}

/**
 * @publish view/modal/validationErrors/element/clicked
 * @throws 1479161268
 */
function _validationErrorsModalSetup(
  modalContent: DocumentFragment,
  validationResults: ValidationResultsRecursive
): void {
  let formElement: FormElement, newRowItem: HTMLElement | null;

  assert(
    Array.isArray(validationResults),
    'Invalid parameter "validationResults"',
    1479161268
  );

  const rowItemSelector = getHelper().getDomElementDataIdentifierSelector('rowItem');
  const rowItemTemplate = modalContent.querySelector(rowItemSelector)?.cloneNode(true) as HTMLElement | null;

  modalContent.querySelectorAll(rowItemSelector).forEach((el) => el.remove());

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
      newRowItem = rowItemTemplate?.cloneNode(true) as HTMLElement | null;
      if (newRowItem) {
        const rowLink = newRowItem;
        rowLink.setAttribute(
          getHelper().getDomElementDataAttribute('elementIdentifier'),
          validationResults[i].formElementIdentifierPath
        );
        const rowLinkTitle = rowLink.querySelector('.form-editor-validation-error-title');
        rowLinkTitle?.replaceChildren(_buildTitleByFormElement(formElement));
        rowLink.querySelector(
          getHelper().getDomElementDataIdentifierSelector('elementIdentifier')
        )?.replaceChildren(document.createTextNode(formElement.get('identifier')));
        rowLink.querySelector(
          getHelper().getDomElementDataIdentifierSelector('elementType')
        )?.replaceChildren(document.createTextNode(
          getFormElementDefinition(formElement, 'label') || formElement.get('type')
        ));
        const validationErrors = rowLink.querySelector(
          getHelper().getDomElementDataIdentifierSelector('validationErrors')
        );
        const validationErrorGroupTemplate = validationErrors?.querySelector(
          getHelper().getDomElementDataIdentifierSelector('validationErrorGroup')
        );
        const validationErrorTemplate = validationErrors?.querySelector(
          getHelper().getDomElementDataIdentifierSelector('validationError')
        );
        validationErrors?.replaceChildren();
        const validationErrorGroups = new Map<string, HTMLElement>();
        validationResults[i].validationResults.forEach((propertyValidationResult) => {
          const propertyLabel = _getPropertyLabelByPath(formElement, propertyValidationResult.propertyPath);
          const finisherIndex = _getFinisherIndexByPropertyPath(propertyValidationResult.propertyPath);
          const validatorIdentifier = _getValidatorIdentifierByPropertyPath(formElement, propertyValidationResult.propertyPath);
          const groupLabel = finisherIndex
            ? _getFinisherLabelByIdentifier(formElement, finisherIndex)
            : validatorIdentifier;
          const groupKey = finisherIndex
            ? `finisher-${finisherIndex}`
            : `validator-${validatorIdentifier}`;
          const validationMessage = propertyValidationResult.validationResults.join(' ');
          if (groupLabel && validationErrorGroupTemplate) {
            let validationErrorGroup = validationErrorGroups.get(groupKey);
            if (!validationErrorGroup) {
              validationErrorGroup = validationErrorGroupTemplate.cloneNode(true) as HTMLElement;
              validationErrorGroup.querySelector(
                getHelper().getDomElementDataIdentifierSelector('validationErrorGroupLabel')
              )?.replaceChildren(document.createTextNode(groupLabel));
              validationErrorGroup.querySelector(
                getHelper().getDomElementDataIdentifierSelector('validationErrorGroupItems')
              )?.replaceChildren();
              validationErrorGroups.set(groupKey, validationErrorGroup);
              validationErrors?.append(validationErrorGroup);
            }
            const validationError = validationErrorTemplate?.cloneNode() as HTMLElement | undefined;
            validationError?.replaceChildren(document.createTextNode(
              propertyLabel ? `${propertyLabel}: ${validationMessage}` : validationMessage
            ));
            validationErrorGroup.querySelector(
              getHelper().getDomElementDataIdentifierSelector('validationErrorGroupItems')
            )?.append(validationError);
          } else {
            const validationError = validationErrorTemplate?.cloneNode() as HTMLElement | undefined;
            validationError?.replaceChildren(document.createTextNode(propertyLabel ? `${propertyLabel}: ${validationMessage}` : validationMessage));
            validationErrors?.append(validationError);
          }
        });
      }
      const rowsContainer = modalContent.querySelector(getHelper().getDomElementDataIdentifierSelector('rowsContainer'));
      if (rowsContainer && newRowItem) {
        rowsContainer.append(newRowItem);
      }
    }
  }

  modalContent.querySelectorAll<HTMLAnchorElement>('a').forEach((a) => {
    a.addEventListener('click', function(event: MouseEvent) {
      event.preventDefault();
      getPublisherSubscriber().publish('view/modal/validationErrors/element/clicked', [
        a.getAttribute(getHelper().getDomElementDataAttribute('elementIdentifier'))
      ]);
      modalContent.querySelectorAll('a').forEach((link) => link.replaceWith(link.cloneNode(true)));
      Modal.currentModal.hideModal();
    });
  });
}

/**
 * @throws 1479162557
 */
function _buildTitleByFormElement(formElement: FormElement): HTMLElement {
  assert(typeof formElement === 'object' && formElement !== null && !Array.isArray(formElement), 'Invalid parameter "formElement"', 1479162557);

  const span = document.createElement('span');
  span.textContent = formElement.get('label') ? formElement.get('label') : formElement.get('identifier');
  return span;
}

function _getPropertyLabelByPath(formElement: FormElement, propertyPath: string): string {
  return _getEditorConfigurationByPropertyPath(formElement, propertyPath)?.label || '';
}

function _getValidatorIdentifierByPropertyPath(formElement: FormElement, propertyPath: string): string {
  const editorConfiguration = _getEditorConfigurationByPropertyPath(formElement, propertyPath);
  const validatorIdentifier = editorConfiguration?.propertyValidators?.find((identifier) => identifier !== 'NotEmpty');
  return validatorIdentifier || '';
}

function _getEditorConfigurationByPropertyPath(
  formElement: FormElement,
  propertyPath: string
): FormElementDefinition['editors'][number] | undefined {
  const formElementDefinition = getFormElementDefinition(formElement, undefined) as FormElementDefinition;
  const editors = [
    ...(formElementDefinition.editors || []),
    ...Object.values(formElementDefinition.propertyCollections || {}).flatMap((collection) =>
      collection.flatMap((collectionElement) => collectionElement.editors || [])
    )
  ];
  return editors.find((editorConfiguration) =>
    editorConfiguration.propertyPath === propertyPath
    || propertyPath.endsWith(`.${editorConfiguration.propertyPath}`)
  );
}

function _getFinisherIndexByPropertyPath(propertyPath: string): string {
  const match = propertyPath.match(/^finishers\.(\d+)\./);
  if (!match) {
    return '';
  }
  return match[1];
}

function _getFinisherLabelByIdentifier(formElement: FormElement, finisherIndex: string): string {
  const finishers = formElement.get('finishers') as Array<{ identifier: string }> | undefined;
  const finisherIdentifier = finishers?.[Number(finisherIndex)]?.identifier;
  if (!finisherIdentifier) {
    return '';
  }
  return getFormEditorApp().getFormEditorDefinition('finishers', finisherIdentifier)?.label || '';
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
  const template = getHelper().getTemplateElement('templateInsertElements');
  if (template) {
    const content = document.importNode(template.content, true);
    insertElementsModalSetup(content, publisherTopicName, configuration);

    Modal.advanced({
      title: getFormElementDefinition(getRootFormElement(), 'modalInsertElementsDialogTitle'),
      size: Modal.sizes.large,
      content,
    });
  }
}

export function showInsertPagesModal(
  publisherTopicName: keyof PublisherSubscriberTopicArgumentsMap,
): void {
  const template = getHelper().getTemplateElement('templateInsertPages');
  if (template) {
    const content = document.importNode(template.content, true);
    insertElementsModalSetup(content, publisherTopicName);

    Modal.advanced({
      title: getFormElementDefinition(getRootFormElement(), 'modalInsertPagesDialogTitle'),
      size: Modal.sizes.small,
      content,
    });
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

  const template = getHelper().getTemplateElement('templateValidationErrors');
  if (template) {
    const content = document.importNode(template.content, true);
    _validationErrorsModalSetup(content, validationResults);

    Modal.show(
      getFormElementDefinition(getRootFormElement(), 'modalValidationErrorsDialogTitle'),
      content,
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
  configuration = merge({}, defaultConfiguration, customConfiguration ?? {}) as HelperConfiguration;
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
