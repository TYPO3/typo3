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
 * Module: @typo3/form/backend/form-editor/stage-component
 */
import $ from 'jquery';
import * as Helper from '@typo3/form/backend/form-editor/helper';
import Icons from '@typo3/backend/icons';
import Sortable from 'sortablejs';
import type { FormElementStageItem, Validator, SelectOption } from '@typo3/form/backend/form-editor/component/form-element-stage-item';
import '@typo3/form/backend/form-editor/component/form-element-stage-item';
import type { PageStageItem } from '@typo3/form/backend/form-editor/component/page-stage-item';
import '@typo3/form/backend/form-editor/component/page-stage-item';

import type {
  FormEditor,
} from '@typo3/form/backend/form-editor';
import type {
  Utility,
  FormElement,
  FormElementDefinition,
  PublisherSubscriber,
} from '@typo3/form/backend/form-editor/core';
import type {
  Configuration as HelperConfiguration,
} from '@typo3/form/backend/form-editor/helper';
import type {
  InsertElementsModalConfiguration
} from '@typo3/form/backend/form-editor/modals-component';
type ViewModel = typeof import('./view-model');

interface Configuration extends Partial<HelperConfiguration> {
  isSortable: boolean,
}

const defaultConfiguration: Configuration = {
  domElementClassNames: {
    formElementIsComposit: 'formeditor-element-composit',
    formElementIsTopLevel: 'formeditor-element-toplevel',
    noNesting: 'no-nesting',
    selected: 'selected',
    sortable: 'sortable',
    previewViewPreviewElement: 'formeditor-element-preview'
  },
  domElementDataAttributeNames: {
    abstractType: 'data-element-abstract-type',
    noSorting: 'data-no-sorting'
  },
  domElementDataAttributeValues: {
    abstractViewToolbar: 'elementToolbar',
    abstractViewToolbarNewElement: 'stageElementToolbarNewElement',
    abstractViewToolbarNewElementSplitButton: 'stageElementToolbarNewElementSplitButton',
    abstractViewToolbarNewElementSplitButtonAfter: 'stageElementToolbarNewElementSplitButtonAfter',
    abstractViewToolbarNewElementSplitButtonInside: 'stageElementToolbarNewElementSplitButtonInside',
    abstractViewToolbarRemoveElement: 'stageElementToolbarRemoveElement',
    buttonHeaderRedo: 'redoButton',
    buttonHeaderUndo: 'undoButton',
    buttonPaginationPrevious: 'buttonPaginationPrevious',
    buttonPaginationNext: 'buttonPaginationNext',
    'FormElement-_ElementToolbar': 'FormElement-_ElementToolbar',
    'FormElement-_UnknownElement': 'FormElement-_UnknownElement',
    formElementIcon: 'elementIcon',
    iconValidator: 'form-validator',
    multiValueContainer: 'multiValueContainer',
    paginationTitle: 'paginationTitle',
    stageHeadline: 'formDefinitionLabel',
    stagePanel: 'stagePanel',
    validatorsContainer: 'validatorsContainer',
    validatorIcon: 'validatorIcon'
  },
  isSortable: true
};

let configuration: Configuration = null;

let formEditorApp: FormEditor = null;

let stageDomElement: JQuery = null;

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

function getViewModel(): ViewModel {
  return getFormEditorApp().getViewModel();
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
  formElement: FormElement,
  formElementDefinitionKey?: T
): T extends keyof FormElementDefinition ? FormElementDefinition[T] : FormElementDefinition {
  return getFormEditorApp().getFormElementDefinition(formElement, formElementDefinitionKey);
}

function setTemplateTextContent(domElement: HTMLElement, content: string): void {
  if (getUtility().isNonEmptyString(content)) {
    $(domElement).text(content);
  }
}

/**
 * @publish view/stage/abstract/render/template/perform
 */
function renderTemplateDispatcher(formElement: FormElement, template: JQuery): void {
  getPublisherSubscriber().publish('view/stage/abstract/render/template/perform', [formElement, template]);
}

/**
 * @throws 1478987818
 */
function renderNestedSortableListItem(formElement: FormElement): JQuery {
  let childList, template;

  const listItem = $('<li></li>');
  if (!getFormElementDefinition(formElement, '_isCompositeFormElement')) {
    listItem.addClass(getHelper().getDomElementClassName('noNesting'));
  }

  if (getFormElementDefinition(formElement, '_isTopLevelFormElement')) {
    listItem.addClass(getHelper().getDomElementClassName('formElementIsTopLevel'));
  }
  if (getFormElementDefinition(formElement, '_isCompositeFormElement')) {
    listItem.addClass(getHelper().getDomElementClassName('formElementIsComposit'));
  }

  try {
    template = getHelper().getTemplate('FormElement-' + formElement.get('type')).clone();
  } catch {
    template = getHelper().getTemplate('FormElement-_UnknownElement').clone();
    assert(
      template.length > 0,
      'No template found for element "' + formElement.get('__identifierPath') + '"',
      1478987818
    );
  }

  template = $('<div></div>')
    .attr(getHelper().getDomElementDataAttribute('elementIdentifier'), formElement.get('__identifierPath'))
    .append($(template.html()));

  const isCompositeFormElement = getFormElementDefinition(formElement, '_isCompositeFormElement');
  if (isCompositeFormElement) {

    template.attr(getHelper().getDomElementDataAttribute('abstractType'), 'isCompositeFormElement');
  }
  const isTopLevelFormElement = getFormElementDefinition(formElement, '_isTopLevelFormElement');
  if (isTopLevelFormElement) {
    template.attr(getHelper().getDomElementDataAttribute('abstractType'), 'isTopLevelFormElement');
  } else {
    template.addClass('formeditor-element');
  }
  if (formElement.get('renderingOptions.enabled') === false) {
    template.addClass('formeditor-element-hidden');
  }
  listItem.append(template);

  const shouldRenderWebComponent = getHelper().getTemplate('FormElement-' + formElement.get('type')).length === 0;

  if (isTopLevelFormElement && shouldRenderWebComponent) {
    renderTopLevelStageItem(formElement, template);
  } else if (shouldRenderWebComponent) {
    renderFormElementStageItem(formElement, template);
  } else {
    renderTemplateDispatcher(formElement, template);
  }

  if (isTopLevelFormElement || isCompositeFormElement) {
    childList = $('<ol></ol>');
    childList.addClass(getHelper().getDomElementClassName('sortable'));
    childList.addClass('formeditor-list');
    const childFormElements = formElement.get('renderables');
    if ('array' === $.type(childFormElements)) {
      for (let i = 0, len = childFormElements.length; i < len; ++i) {
        childList.append(renderNestedSortableListItem(childFormElements[i]));
      }
    }
    listItem.append(childList);
  }

  return listItem;
}

/**
 * @publish view/stage/abstract/dnd/start
 * @publish view/stage/abstract/dnd/stop
 * @publish view/stage/abstract/dnd/change
 * @publish view/stage/abstract/dnd/update
 */
function addSortableEvents(): void {
  const sortableLists = stageDomElement.get(0).querySelectorAll('ol.' + getHelper().getDomElementClassName('sortable'));
  const draggableSelector = 'li:not(' + getHelper().getDomElementDataAttribute('noSorting', 'bracesWithKey') + ')';
  const handleSelector = 'div' + getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey');

  sortableLists.forEach(function (sortableList: HTMLElement) {
    sortableList.querySelectorAll(handleSelector).forEach(function (draggable) {
      draggable.classList.add('formeditor-sortable-handle');
    });

    new Sortable(sortableList, {
      group: 'stage-nodes',
      handle: handleSelector,
      draggable: draggableSelector,
      animation: 200,
      swapThreshold: 0.6,
      dragClass: 'formeditor-sortable-drag',
      ghostClass: 'formeditor-sortable-ghost',
      onStart: function (e) {
        getPublisherSubscriber().publish('view/stage/abstract/dnd/start', [$(e.item), $(e.item)]);
      },
      onChange: function (e) {
        let enclosingCompositeFormElement;
        const parentFormElementIdentifierPath = getAbstractViewParentFormElementIdentifierPathWithinDomElement($(e.item));

        if (parentFormElementIdentifierPath) {
          enclosingCompositeFormElement = getFormEditorApp()
            .findEnclosingCompositeFormElementWhichIsNotOnTopLevel(parentFormElementIdentifierPath);
        }
        getPublisherSubscriber().publish('view/stage/abstract/dnd/change', [
          $(e.item),
          parentFormElementIdentifierPath, enclosingCompositeFormElement
        ]);
      },
      onEnd: function (e) {
        const movedFormElementIdentifierPath = getAbstractViewFormElementIdentifierPathWithinDomElement($(e.item));
        const previousFormElementIdentifierPath = getAbstractViewSiblingFormElementIdentifierPathWithinDomElement($(e.item), 'prev');
        const nextFormElementIdentifierPath = getAbstractViewSiblingFormElementIdentifierPathWithinDomElement($(e.item), 'next');

        getPublisherSubscriber().publish('view/stage/abstract/dnd/update', [
          $(e.item),
          movedFormElementIdentifierPath,
          previousFormElementIdentifierPath,
          nextFormElementIdentifierPath
        ]);
        getPublisherSubscriber().publish('view/stage/abstract/dnd/stop', [
          getAbstractViewFormElementIdentifierPathWithinDomElement($(e.item))
        ]);
      },
    });
  });
}

export function getStageDomElement(): JQuery {
  return stageDomElement;
}

/**
 * @throws 1479037151
 */
export function buildTitleByFormElement(formElement?: FormElement): HTMLElement {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getRootFormElement();
  }
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1479037151);

  const span = document.createElement('span');
  span.textContent = formElement.get('label') ? formElement.get('label') : formElement.get('identifier');
  return span;
}

export function setStageHeadline(title: string): void {
  if (getUtility().isUndefinedOrNull(title)) {
    title = buildTitleByFormElement().textContent;
  }

  $(getHelper().getDomElementDataIdentifierSelector('stageHeadline')).text(title);
}

export function getStagePanelDomElement(): JQuery {
  return $(getHelper().getDomElementDataIdentifierSelector('stagePanel'));
}

export function renderPagination(): void {
  const pageCount = getRootFormElement().get('renderables').length;

  getViewModel().enableButton($(getHelper().getDomElementDataIdentifierSelector('buttonPaginationPrevious')));
  getViewModel().enableButton($(getHelper().getDomElementDataIdentifierSelector('buttonPaginationNext')));

  if (getFormEditorApp().getCurrentlySelectedPageIndex() === 0) {
    getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonPaginationPrevious')));
  }

  if (pageCount === 1 || getFormEditorApp().getCurrentlySelectedPageIndex() === (pageCount - 1)) {
    getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonPaginationNext')));
  }

  const currentPage = getFormEditorApp().getCurrentlySelectedPageIndex() + 1;
  $(getHelper().getDomElementDataIdentifierSelector('paginationTitle')).text(
    getFormElementDefinition(getRootFormElement(), 'paginationTitle')
      .replace('{0}', currentPage.toString())
      .replace('{1}', pageCount)
  );
}

export function renderUndoRedo(): void {
  getViewModel().enableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')));
  getViewModel().enableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')));

  if (getFormEditorApp().getCurrentApplicationStatePosition() + 1 >= getFormEditorApp().getCurrentApplicationStates()) {
    getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')));
  }
  if (getFormEditorApp().getCurrentApplicationStatePosition() === 0) {
    getViewModel().disableButton($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')));
  }
}

export function getAllFormElementDomElements(): JQuery {
  return $(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'),
    stageDomElement
  );
}

/* *************************************************************
 * Abstract stage
 * ************************************************************/

/**
 * @throws 1478721208
 */
export function renderFormDefinitionPageAsSortableList(pageIndex: number): JQuery {
  assert(
    'number' === $.type(pageIndex),
    'Invalid parameter "pageIndex"',
    1478721208
  );

  return $('<ol></ol>')
    .addClass('formeditor-list')
    .append(renderNestedSortableListItem(getRootFormElement().get('renderables')[pageIndex]));
}

export function getAbstractViewParentFormElementWithinDomElement(element: HTMLElement | JQuery): JQuery {
  return $(element)
    .parent()
    .closest('li')
    .find(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
    .first();
}

export function getAbstractViewParentFormElementIdentifierPathWithinDomElement(element: HTMLElement | JQuery): string {
  return getAbstractViewParentFormElementWithinDomElement(element)
    .attr(getHelper().getDomElementDataAttribute('elementIdentifier'));
}

export function getAbstractViewFormElementWithinDomElement(element: HTMLElement | JQuery): JQuery {
  return $(element)
    .find(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
    .first();
}

export function getAbstractViewFormElementIdentifierPathWithinDomElement(element: HTMLElement | JQuery): string {
  return getAbstractViewFormElementWithinDomElement($(element))
    .attr(getHelper().getDomElementDataAttribute('elementIdentifier'));
}

export function getAbstractViewSiblingFormElementIdentifierPathWithinDomElement(element: HTMLElement | JQuery, position: string): string {
  if (getUtility().isUndefinedOrNull(position)) {
    position = 'prev';
  }
  const formElementIdentifierPath = getAbstractViewFormElementIdentifierPathWithinDomElement(element);
  element = (position === 'prev') ? $(element).prev('li') : $(element).next('li');
  return element.find(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
    .not(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKeyValue', [formElementIdentifierPath]))
    .first()
    .attr(getHelper().getDomElementDataAttribute('elementIdentifier'));
}

export function getAbstractViewFormElementDomElement(formElement?: FormElement | string): JQuery {
  let formElementIdentifierPath;

  if (typeof formElement === 'string') {
    formElementIdentifierPath = formElement;
  } else {
    if (getUtility().isUndefinedOrNull(formElement)) {
      formElementIdentifierPath = getCurrentlySelectedFormElement().get('__identifierPath');
    } else {
      formElementIdentifierPath = formElement.get('__identifierPath');
    }
  }
  return $(getHelper()
    .getDomElementDataAttribute('elementIdentifier', 'bracesWithKeyValue', [formElementIdentifierPath]), stageDomElement);
}

export function removeAllStageToolbars(): void {
  $(getHelper().getDomElementDataIdentifierSelector('abstractViewToolbar'), stageDomElement).off().empty().remove();
  hideFormElementStageItemToolbar();
}

/**
 * @publish view/insertElements/perform/after
 * @publish view/insertElements/perform/inside
 * @throws 1479035778
 */
export function createAbstractViewFormElementToolbar(formElement: FormElement): JQuery {
  let template: JQuery;
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1479035778);

  const formElementTypeDefinition = getFormElementDefinition(formElement, undefined);
  if (formElementTypeDefinition._isTopLevelFormElement) {
    return $();
  }

  template = getHelper().getTemplate('FormElement-_ElementToolbar').clone();
  if (!template.length) {
    return $();
  }

  template = $($(template.html()));

  getHelper().getTemplatePropertyDomElement('_type', template).text(getFormElementDefinition(formElement, 'label'));
  getHelper().getTemplatePropertyDomElement('_identifier', template).text(formElement.get('identifier'));

  if (formElementTypeDefinition._isCompositeFormElement) {
    getViewModel().hideComponent($(getHelper().getDomElementDataIdentifierSelector('abstractViewToolbarNewElement'), template));

    $(getHelper().getDomElementDataIdentifierSelector('abstractViewToolbarNewElementSplitButtonAfter'), template).on('click', function() {
      getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
        'view/insertElements/perform/after',
        {
          disableElementTypes: [],
          onlyEnableElementTypes: []
        }
      ]
      );
    });

    $(getHelper().getDomElementDataIdentifierSelector('abstractViewToolbarNewElementSplitButtonInside'), template).on('click', function() {
      getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
        'view/insertElements/perform/inside',
        {
          disableElementTypes: [],
          onlyEnableElementTypes: []
        }
      ]
      );
    });
  } else {
    getViewModel().hideComponent($(getHelper().getDomElementDataIdentifierSelector('abstractViewToolbarNewElementSplitButton'), template));

    $(getHelper().getDomElementDataIdentifierSelector('abstractViewToolbarNewElement'), template).on('click', function() {
      getPublisherSubscriber().publish(
        'view/stage/abstract/elementToolbar/button/newElement/clicked', [
          'view/insertElements/perform/after',
          {
            disableElementTypes: []
          }
        ]
      );
    });
  }

  $(getHelper().getDomElementDataIdentifierSelector('abstractViewToolbarRemoveElement'), template).on('click', function() {
    getViewModel().showRemoveFormElementModal();
  });

  return template;
}

export function createAndAddAbstractViewFormElementToolbar(
  selectedFormElementDomElement: JQuery,
  formElement: FormElement,
  useFadeEffect: boolean
): void {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }

  // Check if the element is a web component (FormElementStageItem)
  const webComponent = selectedFormElementDomElement.find('typo3-form-form-element-stage-item')[0] as FormElementStageItem;

  if (webComponent && webComponent.toolbarConfig) {
    webComponent.toolbarConfig = {
      ...webComponent.toolbarConfig,
      showToolbar: true
    };
    return;
  }

  // Fallback to old jQuery-based toolbar
  if (useFadeEffect) {
    createAbstractViewFormElementToolbar(formElement).fadeOut(0, function(this: HTMLElement) {
      selectedFormElementDomElement.prepend($(this));
      $(getHelper().getDomElementDataIdentifierSelector('abstractViewToolbar'), selectedFormElementDomElement).fadeIn('fast');
    });
  } else {
    selectedFormElementDomElement.prepend(createAbstractViewFormElementToolbar(formElement));
  }
}

export function hideFormElementStageItemToolbar(): void {
  const webComponents = document.querySelectorAll('typo3-form-form-element-stage-item');
  webComponents.forEach((component) => {
    const stageItem = component as FormElementStageItem;
    if (stageItem.toolbarConfig) {
      stageItem.toolbarConfig = {
        ...stageItem.toolbarConfig,
        showToolbar: false
      };
    }
  });
}

/**
 * @publish view/stage/dnd/stop
 * @publish view/stage/element/clicked
 * @throws 1478169511
 */
export function renderAbstractStageArea(pageIndex: number, callback: () => void) {
  if (getUtility().isUndefinedOrNull(pageIndex)) {
    pageIndex = getFormEditorApp().getCurrentlySelectedPageIndex();
  }
  stageDomElement.off().empty().append(renderFormDefinitionPageAsSortableList(pageIndex));

  stageDomElement.on('click', function(e) {
    const formElementIdentifierPath = $(e.target)
      .closest(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
      .attr(getHelper().getDomElementDataAttribute('elementIdentifier'));
    if (
      getUtility().isUndefinedOrNull(formElementIdentifierPath)
      || !getUtility().isNonEmptyString(formElementIdentifierPath)
    ) {
      return;
    }

    getPublisherSubscriber().publish('view/stage/element/clicked', [formElementIdentifierPath]);
  });

  if (configuration.isSortable) {
    addSortableEvents();
  }

  if ('function' === $.type(callback)) {
    callback();
  }
}


/* *************************************************************
 * Preview stage
 * ************************************************************/

/**
 * @throws 1475424409
 */
export function renderPreviewStageArea(html: string): void {
  assert(getUtility().isNonEmptyString(html), 'Invalid parameter "html"', 1475424409);

  stageDomElement.off().empty().html(html);

  $(':input', stageDomElement).prop('disabled', 'disabled').on('click dblclick select focus keydown keypress keyup mousedown mouseup', function(e) {
    return e.preventDefault();
  });

  $('form', stageDomElement).submit(function(e) {
    return e.preventDefault();
  });

  getAllFormElementDomElements().each(function(this: HTMLElement) {
    const formElement = getFormEditorApp()
      .getFormElementByIdentifierPath($(this).data('elementIdentifierPath'));

    if (
      !getFormElementDefinition(formElement, '_isTopLevelFormElement')
    ) {
      $(this).attr('title', 'identifier: ' + formElement.get('identifier') + ' (type: ' + formElement.get('type') + ')');
    }

    if (getFormElementDefinition(formElement, '_isTopLevelFormElement')) {
      $(this).addClass(getHelper().getDomElementClassName('formElementIsTopLevel'));
    }
    if (getFormElementDefinition(formElement, '_isCompositeFormElement')) {
      $(this).addClass(getHelper().getDomElementClassName('formElementIsComposit'));
    }
  });

}

/* *************************************************************
 * Template rendering
 * ************************************************************/

/**
 * Renders a top-level form element (page) using the PageStageItem web component
 *
 * @throws 1768924251
 */
export function renderTopLevelStageItem(formElement: FormElement, template: JQuery): void {
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1768924251);

  const stageItem = document.createElement('typo3-form-page-stage-item') as PageStageItem;

  stageItem.pageTitle = formElement.get('label') || '';

  template.empty().append(stageItem);
}

/**
 * @throws 1768924252
 */
export function renderFormElementStageItem(formElement: FormElement, template: JQuery): void {
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1768924252);

  const stageItem = document.createElement('typo3-form-form-element-stage-item') as FormElementStageItem;

  stageItem.elementType = getFormElementDefinition(formElement, 'label');
  stageItem.elementIdentifier = formElement.get('identifier');
  stageItem.elementLabel = formElement.get('label') || formElement.get('identifier');
  stageItem.elementIconIdentifier = getFormElementDefinition(formElement, 'iconIdentifier');
  stageItem.isHidden = formElement.get('renderingOptions.enabled') === false;

  const validators = formElement.get('validators');
  const validatorList: Validator[] = [];
  let hasNotEmptyValidator = false;

  if ('array' === $.type(validators) && validators.length > 0) {
    for (let i = 0, len = validators.length; i < len; ++i) {
      if ('NotEmpty' === validators[i].identifier) {
        hasNotEmptyValidator = true;
        continue;
      }

      const collectionElementConfiguration = getFormEditorApp()
        .getFormEditorDefinition('validators', validators[i].identifier);

      validatorList.push({
        identifier: validators[i].identifier,
        label: collectionElementConfiguration.label
      });
    }
  }

  stageItem.validators = validatorList;
  stageItem.isRequired = hasNotEmptyValidator;

  const textValue = formElement.get('properties.text');
  if (textValue && getUtility().isNonEmptyString(textValue)) {
    stageItem.content = textValue;
  }

  const contentElementUid = formElement.get('properties.contentElementUid');
  if (contentElementUid && getUtility().isNonEmptyString(contentElementUid)) {
    stageItem.content = contentElementUid;
  }

  // Process options (for select elements like SingleSelect, MultiSelect, RadioButton, etc.)
  const propertyPath = 'properties.options';
  const propertyValue = formElement.get(propertyPath);
  const optionsList: SelectOption[] = [];

  if (propertyValue) {
    let defaultValue = formElement.get('defaultValue');

    if (getFormEditorApp().getUtility().isUndefinedOrNull(defaultValue)) {
      defaultValue = {};
    } else if ('string' === $.type(defaultValue)) {
      defaultValue = { 0: defaultValue };
    }

    if ('object' === $.type(propertyValue)) {
      for (const propertyValueKey of Object.keys(propertyValue)) {
        let isSelected = false;
        for (const defaultValueKey of Object.keys(defaultValue)) {
          if (defaultValue[defaultValueKey] === propertyValueKey) {
            isSelected = true;
            break;
          }
        }
        optionsList.push({
          label: propertyValue[propertyValueKey],
          value: propertyValueKey,
          selected: isSelected
        });
      }
    } else if ('array' === $.type(propertyValue)) {
      for (const propertyValueKey of Object.keys(propertyValue)) {
        let label: string;
        let value: string;

        if (getUtility().isUndefinedOrNull(propertyValue[propertyValueKey]._label)) {
          label = propertyValue[propertyValueKey];
          value = propertyValueKey;
        } else {
          label = propertyValue[propertyValueKey]._label;
          value = propertyValue[propertyValueKey]._value;
        }

        let isSelected = false;
        for (const defaultValueKey of Object.keys(defaultValue)) {
          if (defaultValue[defaultValueKey] === value) {
            isSelected = true;
            break;
          }
        }

        optionsList.push({
          label: label,
          value: value,
          selected: isSelected
        });
      }
    }
  }

  stageItem.options = optionsList;

  // Process allowed mime types (for FileUpload and ImageUpload elements)
  const allowedMimeTypesPath = 'properties.allowedMimeTypes';
  const allowedMimeTypesValue = formElement.get(allowedMimeTypesPath);
  const mimeTypesList: string[] = [];

  if (allowedMimeTypesValue) {
    if ('object' === $.type(allowedMimeTypesValue)) {
      for (const key of Object.keys(allowedMimeTypesValue)) {
        if (!isNaN(Number(key))) {
          mimeTypesList.push(allowedMimeTypesValue[key]);
        }
      }
    } else if ('array' === $.type(allowedMimeTypesValue)) {
      for (let i = 0, len = allowedMimeTypesValue.length; i < len; ++i) {
        mimeTypesList.push(allowedMimeTypesValue[i]);
      }
    }
  }

  if (mimeTypesList.length > 0) {
    stageItem.allowedMimeTypes = mimeTypesList;
  }

  if (stageItem.isHidden) {
    stageItem.classList.add('formeditor-element-hidden');
  }

  // Configure toolbar (will be shown when element is selected)
  const formElementTypeDefinition = getFormElementDefinition(formElement, undefined);
  stageItem.toolbarConfig = {
    showToolbar: false, // Initially hidden, will be toggled when selected
    isCompositeElement: formElementTypeDefinition._isCompositeFormElement || false,
    elementTypeLabel: getFormElementDefinition(formElement, 'label'),
    elementIdentifier: formElement.get('identifier')
  };

  // Register event listeners for toolbar actions
  stageItem.addEventListener('toolbar-new-element-after', () => {
    getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
      'view/insertElements/perform/after',
      {
        disableElementTypes: []
      }
    ]);
  });

  stageItem.addEventListener('toolbar-new-element-inside', () => {
    getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
      'view/insertElements/perform/inside',
      {
        disableElementTypes: [],
        onlyEnableElementTypes: []
      }
    ]);
  });

  stageItem.addEventListener('toolbar-remove-element', () => {
    getViewModel().showRemoveFormElementModal();
  });

  template.empty().append(stageItem);
}

export function eachTemplateProperty(
  formElement: FormElement,
  template: JQuery,
  callback?: (propertyPath: string, propertyValue: unknown, element: HTMLElement) => void
) {
  $(getHelper().getDomElementDataAttribute('templateProperty', 'bracesWithKey'), template).each(function(i, element) {
    const propertyPath = $(element).attr(getHelper().getDomElementDataAttribute('templateProperty'));
    const propertyValue = formElement.get(propertyPath);

    if ('function' === $.type(callback)) {
      callback(propertyPath, propertyValue, element as HTMLElement);
    }
  });
}

export function renderCheckboxTemplate(formElement: FormElement, template: JQuery) {
  renderSimpleTemplateWithValidators(formElement, template);

  eachTemplateProperty(formElement, template, function(propertyPath, propertyValue, domElement) {
    if (
      ('boolean' === $.type(propertyValue) && propertyValue)
      || propertyValue === 'true'
      || propertyValue === 1
      || propertyValue === '1'
    ) {
      $(domElement).addClass(getHelper().getDomElementClassName('noNesting'));
    }
  });
}

/**
 * @throws 1479035696
 */
export function renderSimpleTemplate(formElement: FormElement, template: JQuery): void {
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1479035696);

  eachTemplateProperty(formElement, template, (propertyPath, propertyValue: string, domElement) => {
    setTemplateTextContent(domElement, propertyValue);
  });

  const overlayIdentifier = formElement.get('renderingOptions.enabled') === false ? 'overlay-hidden' : null;

  Icons.getIcon(
    getFormElementDefinition(formElement, 'iconIdentifier'),
    Icons.sizes.small,
    overlayIdentifier,
    Icons.states.default,
    Icons.markupIdentifiers.inline
  ).then(function(icon) {
    $(getHelper().getDomElementDataIdentifierSelector('formElementIcon'), template)
      .append($(icon).addClass(getHelper().getDomElementClassName('icon')));
  });

  getHelper()
    .getTemplatePropertyDomElement('_type', template)
    .append(document.createTextNode(getFormElementDefinition(formElement, 'label')));
  getHelper()
    .getTemplatePropertyDomElement('_identifier', template)
    .append(document.createTextNode(formElement.get('identifier')));
}

/**
 * @throws 1479035674
 */
export function renderSimpleTemplateWithValidators(formElement: FormElement, template: JQuery): void {
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1479035674);

  renderSimpleTemplate(formElement, template);

  const validatorsTemplateContent = $(
    getHelper().getDomElementDataIdentifierSelector('validatorsContainer'),
    $(template)
  ).clone();

  $(getHelper().getDomElementDataIdentifierSelector('validatorsContainer'), $(template)).empty();
  const validators = formElement.get('validators');

  if ('array' === $.type(validators)) {
    let validatorsCountWithoutRequired = 0;
    if (validators.length > 0) {
      for (let i = 0, len = validators.length; i < len; ++i) {
        if ('NotEmpty' === validators[i].identifier) {
          getHelper()
            .getTemplatePropertyDomElement('_required', template)
            .text('*');
          continue;
        }
        validatorsCountWithoutRequired++;

        const collectionElementConfiguration = getFormEditorApp()
          .getFormEditorDefinition('validators', validators[i].identifier);
        const rowTemplate = $($(validatorsTemplateContent).clone());

        getHelper()
          .getTemplatePropertyDomElement('_label', rowTemplate)
          .append(document.createTextNode(collectionElementConfiguration.label));
        $(getHelper().getDomElementDataIdentifierSelector('validatorsContainer'), $(template))
          .append(rowTemplate.html());
      }

      if (validatorsCountWithoutRequired > 0) {
        Icons.getIcon(
          getHelper().getDomElementDataAttributeValue('iconValidator'),
          Icons.sizes.small,
          null,
          Icons.states.default,
          Icons.markupIdentifiers.inline
        ).then(function(icon) {
          $(getHelper().getDomElementDataIdentifierSelector('validatorIcon'), $(template))
            .append($(icon).addClass(getHelper().getDomElementClassName('icon')));
        });
      }
    }
  }
}

export function renderSelectTemplates(formElement: FormElement, template: JQuery): void {
  const multiValueTemplateContent = $(
    getHelper().getDomElementDataIdentifierSelector('multiValueContainer'),
    $(template)
  ).clone();
  $(getHelper().getDomElementDataIdentifierSelector('multiValueContainer'), $(template)).empty();

  renderSimpleTemplateWithValidators(formElement, template);

  const propertyPath = $(getHelper().getDomElementDataIdentifierSelector('multiValueContainer'), $(template))
    .attr(getHelper().getDomElementDataAttribute('templateProperty'));

  const propertyValue = formElement.get(propertyPath);

  const appendMultiValue = (label: string, value: string, defaultValue: Record<string, string>) => {
    let isPreselected = false;
    const rowTemplate = $($(multiValueTemplateContent).clone());

    for (const defaultValueKey of Object.keys(defaultValue)) {
      if (defaultValue[defaultValueKey] === value) {
        isPreselected = true;
        break;
      }
    }

    getHelper().getTemplatePropertyDomElement('_label', rowTemplate).append(document.createTextNode(label));

    if (isPreselected) {
      getHelper().getTemplatePropertyDomElement('_label', rowTemplate).addClass(
        getHelper().getDomElementClassName('selected')
      );
    }

    $(getHelper().getDomElementDataIdentifierSelector('multiValueContainer'), $(template))
      .append(rowTemplate.html());
  };

  let defaultValue = formElement.get('defaultValue');

  if (getFormEditorApp().getUtility().isUndefinedOrNull(defaultValue)) {
    defaultValue = {};
  } else if ('string' === $.type(defaultValue)) {
    defaultValue = { 0: defaultValue };
  }

  if ('object' === $.type(propertyValue)) {
    for (const propertyValueKey of Object.keys(propertyValue)) {
      appendMultiValue(propertyValue[propertyValueKey], propertyValueKey, defaultValue);
    }
  } else if ('array' === $.type(propertyValue)) {
    for (const propertyValueKey of Object.keys(propertyValue)) {
      if (getUtility().isUndefinedOrNull(propertyValue[propertyValueKey]._label)) {
        appendMultiValue(propertyValue[propertyValueKey], propertyValueKey, defaultValue);
      } else {
        appendMultiValue(propertyValue[propertyValueKey]._label, propertyValue[propertyValueKey]._value, defaultValue);
      }
    }
  }
}

export function renderFileUploadTemplates(formElement: FormElement, template: JQuery): void {
  const multiValueTemplateContent = $(
    getHelper().getDomElementDataIdentifierSelector('multiValueContainer'),
    $(template)
  ).clone();
  $(getHelper().getDomElementDataIdentifierSelector('multiValueContainer'), $(template)).empty();

  renderSimpleTemplateWithValidators(formElement, template);

  const propertyPath = $(getHelper().getDomElementDataIdentifierSelector('multiValueContainer'), $(template))
    .attr(getHelper().getDomElementDataAttribute('templateProperty'));
  const propertyValue = formElement.get(propertyPath);

  const appendMultiValue = function(value: string) {
    const rowTemplate = $($(multiValueTemplateContent).clone());

    getHelper().getTemplatePropertyDomElement('_value', rowTemplate).append(value);
    $(getHelper().getDomElementDataIdentifierSelector('multiValueContainer'), $(template))
      .append(rowTemplate.html());
  };

  if ('object' === $.type(propertyValue)) {
    for (const propertyValueKey of Object.keys(propertyValue)) {
      appendMultiValue(propertyValue[propertyValueKey]);
    }
  } else if ('array' === $.type(propertyValue)) {
    for (let i = 0, len = propertyValue.length; i < len; ++i) {
      appendMultiValue(propertyValue[i]);
    }
  }
}

/**
 * @throws 1478992119
 */
export function bootstrap(
  this: typeof import('./stage-component'),
  _formEditorApp: FormEditor,
  appendToDomElement: JQuery,
  customConfiguration?: Configuration
): typeof import('./stage-component') {
  formEditorApp = _formEditorApp;
  assert('object' === $.type(appendToDomElement), 'Invalid parameter "appendToDomElement"', 1478992119);
  stageDomElement = $(appendToDomElement);
  configuration = $.extend(true, defaultConfiguration, customConfiguration || {});
  Helper.bootstrap(formEditorApp);
  return this;
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'view/stage/abstract/render/template/perform': readonly [
      formElement: FormElement,
      template: JQuery
    ];
    'view/stage/abstract/dnd/start': readonly [
      draggedFormElementDomElement: HTMLElement | JQuery,
      draggedFormPlaceholderDomElement: HTMLElement | JQuery,
    ];
    'view/stage/abstract/dnd/change': readonly [
      placeholderDomElement: JQuery,
      parentFormElementIdentifierPath: string,
      enclosingCompositeFormElement: FormElement
    ];
    'view/stage/abstract/dnd/update': readonly [
      movedDomElement: JQuery,
      movedFormElementIdentifierPath: string,
      previousFormElementIdentifierPath: string,
      nextFormElementIdentifierPath: string,
    ];
    'view/stage/abstract/dnd/stop': readonly [
      draggedFormElementIdentifierPath: string
    ];
    'view/stage/element/clicked': readonly [
      formElementIdentifierPath: string
    ];
    'view/stage/abstract/elementToolbar/button/newElement/clicked': readonly [
      targetEvent: 'view/insertElements/perform/after' | 'view/insertElements/perform/inside',
      modalConfiguration: InsertElementsModalConfiguration
    ];
    // triggered by 'view/stage/abstract/elementToolbar/button/newElement/clicked' via
    // ModalComponent.insertElementsModalSetup()
    'view/insertElements/perform/after': readonly [
      formElementType: string
    ];
    // triggered by 'view/stage/abstract/elementToolbar/button/newElement/clicked' via
    // ModalComponent.insertElementsModalSetup()
    'view/insertElements/perform/inside': readonly [
      formElementType: string
    ];
  }
}
