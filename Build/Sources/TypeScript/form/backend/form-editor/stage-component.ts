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
import * as Helper from '@typo3/form/backend/form-editor/helper';
import { merge } from 'lodash-es';
import Icons from '@typo3/backend/icons';
import Sortable from 'sortablejs';
import type { FormElementStageItem, Validator, SelectOption } from '@typo3/form/backend/form-editor/component/form-element-stage-item';
import '@typo3/form/backend/form-editor/component/form-element-stage-item';
import type { FormElementStageItemToolbar } from '@typo3/form/backend/form-editor/component/form-element-stage-item-toolbar';
import '@typo3/form/backend/form-editor/component/form-element-stage-item-toolbar';
import type { PageStageItem } from '@typo3/form/backend/form-editor/component/page-stage-item';
import '@typo3/form/backend/form-editor/component/page-stage-item';
import labels from '~labels/form.form_editor_javascript';

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

let stageDomElement: HTMLElement = null;

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
    domElement.textContent = content;
  }
}

/**
 * @publish view/stage/abstract/render/template/perform
 */
function renderTemplateDispatcher(formElement: FormElement, template: HTMLElement): void {
  getPublisherSubscriber().publish('view/stage/abstract/render/template/perform', [formElement, template]);
}

/**
 * Creates a "new element" placeholder <li> rendered at the first position
 * inside a Page or composite element.
 *
 * @param formElement - The parent form element (Page or composite)
 * @param position - always 'inside': inserts as first child of formElement
 */
function createNewElementPlaceholder(formElement: FormElement, position: 'inside' | 'after'): HTMLElement {
  const listItem = document.createElement('li');
  listItem.setAttribute('data-no-sorting', 'true');
  listItem.classList.add('formeditor-new-element-placeholder');

  const buttonLabel = labels.get('formEditor.stage.toolbar.new_element');

  const button = document.createElement('button');
  button.type = 'button';
  button.title = buttonLabel;
  button.classList.add('btn', 'btn-sm', 'btn-default');

  const icon = document.createElement('typo3-backend-icon');
  icon.setAttribute('identifier', 'actions-plus');
  icon.setAttribute('size', 'small');
  button.append(icon, document.createTextNode(' ' + buttonLabel));

  button.addEventListener('click', function(e) {
    e.stopPropagation();

    getFormEditorApp().setCurrentlySelectedFormElement(formElement);

    if (position === 'inside') {
      getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
        'view/insertElements/perform/inside',
        { disableElementTypes: [], onlyEnableElementTypes: [] }
      ]);
    } else {
      getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
        'view/insertElements/perform/after',
        { disableElementTypes: [], onlyEnableElementTypes: [] }
      ]);
    }
  });

  listItem.append(button);
  return listItem;
}

/**
 * @throws 1478987818
 */
function renderNestedSortableListItem(formElement: FormElement): HTMLElement {
  let childList: HTMLElement;

  const listItem = document.createElement('li');
  if (!getFormElementDefinition(formElement, '_isCompositeFormElement')) {
    listItem.classList.add(getHelper().getDomElementClassName('noNesting'));
  }
  if (getFormElementDefinition(formElement, '_isTopLevelFormElement')) {
    listItem.classList.add(getHelper().getDomElementClassName('formElementIsTopLevel'));
  }
  if (getFormElementDefinition(formElement, '_isCompositeFormElement')) {
    listItem.classList.add(getHelper().getDomElementClassName('formElementIsComposit'));
  }

  let rawTemplate: HTMLTemplateElement | null;
  try {
    rawTemplate = getHelper().getTemplateElement('FormElement-' + formElement.get('type'));
  } catch {
    rawTemplate = null;
  }

  // When no custom template is registered, the web component renders the element directly.
  // The _UnknownElement template fallback is no longer required.
  const shouldRenderWebComponent = rawTemplate === null;

  const templateEl = document.createElement('div');
  templateEl.setAttribute(getHelper().getDomElementDataAttribute('elementIdentifier'), formElement.get('__identifierPath'));
  if (rawTemplate) {
    templateEl.append(document.importNode(rawTemplate.content, true));
  }

  const isCompositeFormElement = getFormElementDefinition(formElement, '_isCompositeFormElement');
  if (isCompositeFormElement) {
    templateEl.setAttribute(getHelper().getDomElementDataAttribute('abstractType'), 'isCompositeFormElement');
  }
  const isTopLevelFormElement = getFormElementDefinition(formElement, '_isTopLevelFormElement');
  if (isTopLevelFormElement) {
    templateEl.setAttribute(getHelper().getDomElementDataAttribute('abstractType'), 'isTopLevelFormElement');
  } else {
    templateEl.classList.add('formeditor-element');
    templateEl.setAttribute('tabindex', '0');
    templateEl.setAttribute('role', 'button');
    templateEl.setAttribute('aria-label', (formElement.get('label') || formElement.get('identifier')) + ' (' + getFormElementDefinition(formElement, 'label') + ')');
  }
  if (formElement.get('renderingOptions.enabled') === false) {
    templateEl.classList.add('formeditor-element-hidden');
  }

  // For non-top-level elements rendered via a custom Fluid template (legacy path),
  // automatically prepend the standalone toolbar web component.
  if (!isTopLevelFormElement && !shouldRenderWebComponent
    && !templateEl.querySelector('typo3-form-form-element-stage-item-toolbar')
  ) {
    const toolbarEl = document.createElement('typo3-form-form-element-stage-item-toolbar') as unknown as FormElementStageItemToolbar;
    toolbarEl.iconIdentifier = getFormElementDefinition(formElement, 'iconIdentifier') || '';
    toolbarEl.elementType = getFormElementDefinition(formElement, 'label') || '';
    toolbarEl.elementIdentifier = formElement.get('identifier') || '';
    toolbarEl.isHidden = formElement.get('renderingOptions.enabled') === false;
    toolbarEl.active = true;
    templateEl.prepend(toolbarEl as unknown as HTMLElement);
  }

  listItem.append(templateEl);

  if (isTopLevelFormElement && shouldRenderWebComponent) {
    renderTopLevelStageItem(formElement, templateEl);
  } else if (shouldRenderWebComponent) {
    renderFormElementStageItem(formElement, templateEl);
  } else {
    renderTemplateDispatcher(formElement, templateEl);
  }

  if (isTopLevelFormElement || isCompositeFormElement) {
    childList = document.createElement('ol');
    childList.classList.add(getHelper().getDomElementClassName('sortable'));
    childList.classList.add('formeditor-list');
    const childFormElements = formElement.get('renderables');
    const hasChildren = Array.isArray(childFormElements) && childFormElements.length > 0;

    // Show "Create new element" placeholder when the container (page or composite) is empty.
    if (!hasChildren) {
      childList.append(createNewElementPlaceholder(formElement, 'inside'));
    }

    if (hasChildren) {
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
  const sortableLists = stageDomElement.querySelectorAll<HTMLElement>('ol.' + getHelper().getDomElementClassName('sortable'));
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
        stageDomElement.classList.add('formeditor-is-dragging');
        getPublisherSubscriber().publish('view/stage/abstract/dnd/start', [e.item as HTMLElement, e.item as HTMLElement]);
      },
      onChange: function (e) {
        let enclosingCompositeFormElement;
        const parentFormElementIdentifierPath = getAbstractViewParentFormElementIdentifierPathWithinDomElement(e.item as HTMLElement);

        if (parentFormElementIdentifierPath) {
          enclosingCompositeFormElement = getFormEditorApp()
            .findEnclosingCompositeFormElementWhichIsNotOnTopLevel(parentFormElementIdentifierPath);
        }
        getPublisherSubscriber().publish('view/stage/abstract/dnd/change', [
          e.item as HTMLElement,
          parentFormElementIdentifierPath, enclosingCompositeFormElement
        ]);
      },
      onEnd: function (e) {
        const item = e.item as HTMLElement;
        const movedFormElementIdentifierPath = getAbstractViewFormElementIdentifierPathWithinDomElement(item);
        const previousFormElementIdentifierPath = getAbstractViewSiblingFormElementIdentifierPathWithinDomElement(item, 'prev');
        const nextFormElementIdentifierPath = getAbstractViewSiblingFormElementIdentifierPathWithinDomElement(item, 'next');

        getPublisherSubscriber().publish('view/stage/abstract/dnd/update', [
          item,
          movedFormElementIdentifierPath,
          previousFormElementIdentifierPath,
          nextFormElementIdentifierPath
        ]);
        getPublisherSubscriber().publish('view/stage/abstract/dnd/stop', [
          getAbstractViewFormElementIdentifierPathWithinDomElement(item)
        ]);
        stageDomElement.classList.remove('formeditor-is-dragging');
      },
    });
  });
}

export function getStageDomElement(): HTMLElement {
  return stageDomElement;
}

/**
 * @throws 1479037151
 */
export function buildTitleByFormElement(formElement?: FormElement): HTMLElement {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getRootFormElement();
  }
  assert(typeof formElement === 'object' && formElement !== null && !Array.isArray(formElement), 'Invalid parameter "formElement"', 1479037151);

  const span = document.createElement('span');
  span.textContent = formElement.get('label') ? formElement.get('label') : formElement.get('identifier');
  return span;
}

export function setStageHeadline(title: string): void {
  if (getUtility().isUndefinedOrNull(title)) {
    title = buildTitleByFormElement().textContent;
  }
  const el = document.querySelector(getHelper().getDomElementDataIdentifierSelector('stageHeadline'));
  if (el) {
    el.textContent = title;
  }
}

export function getStagePanelDomElement(): HTMLElement | null {
  return document.querySelector(getHelper().getDomElementDataIdentifierSelector('stagePanel'));
}

export function renderPagination(): void {
  const pageCount = getRootFormElement().get('renderables').length;
  const qs = (id: string): HTMLElement | null => document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector(id));

  getViewModel().enableButton(qs('buttonPaginationPrevious'));
  getViewModel().enableButton(qs('buttonPaginationNext'));

  if (getFormEditorApp().getCurrentlySelectedPageIndex() === 0) {
    getViewModel().disableButton(qs('buttonPaginationPrevious'));
  }

  if (pageCount === 1 || getFormEditorApp().getCurrentlySelectedPageIndex() === (pageCount - 1)) {
    getViewModel().disableButton(qs('buttonPaginationNext'));
  }

  const currentPage = getFormEditorApp().getCurrentlySelectedPageIndex() + 1;
  const paginationEl = qs('paginationTitle');
  if (paginationEl) {
    paginationEl.textContent = getFormElementDefinition(getRootFormElement(), 'paginationTitle')
      .replace('{0}', currentPage.toString())
      .replace('{1}', pageCount);
  }
}

export function renderUndoRedo(): void {
  const qs = (id: string): HTMLElement | null => document.querySelector<HTMLElement>(getHelper().getDomElementDataIdentifierSelector(id));

  getViewModel().enableButton(qs('buttonHeaderUndo'));
  getViewModel().enableButton(qs('buttonHeaderRedo'));

  if (getFormEditorApp().getCurrentApplicationStatePosition() + 1 >= getFormEditorApp().getCurrentApplicationStates()) {
    getViewModel().disableButton(qs('buttonHeaderUndo'));
  }
  if (getFormEditorApp().getCurrentApplicationStatePosition() === 0) {
    getViewModel().disableButton(qs('buttonHeaderRedo'));
  }
}

export function getAllFormElementDomElements(): NodeListOf<HTMLElement> {
  return stageDomElement
    ? stageDomElement.querySelectorAll<HTMLElement>(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
    : document.querySelectorAll<HTMLElement>('.formeditor-element-none');
}

/* *************************************************************
 * Abstract stage
 * ************************************************************/

/**
 * @throws 1478721208
 */
export function renderFormDefinitionPageAsSortableList(pageIndex: number): HTMLElement {
  assert(typeof pageIndex === 'number', 'Invalid parameter "pageIndex"', 1478721208);

  const ol = document.createElement('ol');
  ol.classList.add('formeditor-stage-list');
  ol.append(renderNestedSortableListItem(getRootFormElement().get('renderables')[pageIndex]));
  return ol;
}

export function getAbstractViewParentFormElementWithinDomElement(element: HTMLElement): HTMLElement | null {
  return element
    .parentElement
    ?.closest('li')
    ?.querySelector(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
    ?? null;
}

export function getAbstractViewParentFormElementIdentifierPathWithinDomElement(element: HTMLElement): string {
  return getAbstractViewParentFormElementWithinDomElement(element)
    ?.getAttribute(getHelper().getDomElementDataAttribute('elementIdentifier')) ?? '';
}

export function getAbstractViewFormElementWithinDomElement(element: HTMLElement): HTMLElement | null {
  return element.querySelector(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'));
}

export function getAbstractViewFormElementIdentifierPathWithinDomElement(element: HTMLElement): string {
  return getAbstractViewFormElementWithinDomElement(element)
    ?.getAttribute(getHelper().getDomElementDataAttribute('elementIdentifier')) ?? '';
}

export function getAbstractViewSiblingFormElementIdentifierPathWithinDomElement(element: HTMLElement, position: string): string {
  if (getUtility().isUndefinedOrNull(position)) {
    position = 'prev';
  }
  const formElementIdentifierPath = getAbstractViewFormElementIdentifierPathWithinDomElement(element);
  const sibling = position === 'prev'
    ? element.previousElementSibling as HTMLElement | null
    : element.nextElementSibling as HTMLElement | null;
  if (!sibling) { return ''; }
  const attr = getHelper().getDomElementDataAttribute('elementIdentifier');
  const found = sibling.querySelector<HTMLElement>(
    getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey') +
    ':not(' + getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKeyValue', [formElementIdentifierPath]) + ')'
  );
  return found?.getAttribute(attr) ?? '';
}

export function getAbstractViewFormElementDomElement(formElement?: FormElement | string): HTMLElement | null {
  let formElementIdentifierPath: string;

  if (typeof formElement === 'string') {
    formElementIdentifierPath = formElement;
  } else {
    if (getUtility().isUndefinedOrNull(formElement)) {
      formElementIdentifierPath = getCurrentlySelectedFormElement().get('__identifierPath');
    } else {
      formElementIdentifierPath = (formElement as FormElement).get('__identifierPath');
    }
  }
  return stageDomElement
    ? stageDomElement.querySelector<HTMLElement>(
      getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKeyValue', [formElementIdentifierPath])
    )
    : null;
}

/**
 * @deprecated since TYPO3 v14.2, will be removed in TYPO3 v15.
 *   Only used by the legacy template-based stage rendering approach.
 *   Web component-based elements handle their toolbar via the
 *   `toolbarConfig` property of `<typo3-form-form-element-stage-item>`.
 *   See Deprecation #109306.
 * @publish view/insertElements/perform/after
 * @publish view/insertElements/perform/inside
 * @throws 1479035778
 */
export function createAbstractViewFormElementToolbar(formElement: FormElement): HTMLElement | null {
  assert(typeof formElement === 'object' && formElement !== null && !Array.isArray(formElement), 'Invalid parameter "formElement"', 1479035778);

  const formElementTypeDefinition = getFormElementDefinition(formElement, undefined);
  if (formElementTypeDefinition._isTopLevelFormElement) {
    return null;
  }

  const rawTemplate = getHelper().getTemplateElement('FormElement-_ElementToolbar');
  if (!rawTemplate) {
    return null;
  }

  const template = document.importNode(rawTemplate.content, true).firstElementChild as HTMLElement ?? document.createElement('div');

  getHelper().getTemplatePropertyElement('_type', template)?.append(
    document.createTextNode(getFormElementDefinition(formElement, 'label'))
  );
  getHelper().getTemplatePropertyElement('_identifier', template)?.append(
    document.createTextNode(formElement.get('identifier'))
  );

  wireAbstractViewFormElementToolbarEventListeners(template, formElement);

  return template;
}

/**
 * Wires toolbar button event listeners onto an already-cloned toolbar HTMLElement.
 * Only used by the deprecated {@link createAbstractViewFormElementToolbar} function
 * (global _ElementToolbar template path).  New code uses
 * `<typo3-form-form-element-stage-item-toolbar>` which dispatches its own events.
 *
 * @deprecated since TYPO3 v14.2, will be removed in TYPO3 v15 together with
 *   {@link createAbstractViewFormElementToolbar}.
 */
function wireAbstractViewFormElementToolbarEventListeners(toolbar: HTMLElement, formElement: FormElement): void {
  const formElementTypeDefinition = getFormElementDefinition(formElement, undefined);

  const qs = (id: string): HTMLElement | null =>
    toolbar.querySelector(getHelper().getDomElementDataIdentifierSelector(id));

  if (formElementTypeDefinition._isCompositeFormElement) {
    getViewModel().hideComponent(qs('abstractViewToolbarNewElement'));

    qs('abstractViewToolbarNewElementSplitButtonAfter')?.addEventListener('click', function() {
      getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
        'view/insertElements/perform/after',
        { disableElementTypes: [], onlyEnableElementTypes: [] }
      ]);
    });

    qs('abstractViewToolbarNewElementSplitButtonInside')?.addEventListener('click', function() {
      getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
        'view/insertElements/perform/inside',
        { disableElementTypes: [], onlyEnableElementTypes: [] }
      ]);
    });
  } else {
    getViewModel().hideComponent(qs('abstractViewToolbarNewElementSplitButton'));

    qs('abstractViewToolbarNewElement')?.addEventListener('click', function() {
      getPublisherSubscriber().publish(
        'view/stage/abstract/elementToolbar/button/newElement/clicked', [
          'view/insertElements/perform/after',
          { disableElementTypes: [] }
        ]
      );
    });
  }

  qs('abstractViewToolbarRemoveElement')?.addEventListener('click', function() {
    getViewModel().showRemoveFormElementModal(formElement);
  });
}

export function createAndAddAbstractViewFormElementToolbar(
  selectedFormElementDomElement: HTMLElement,
  formElement: FormElement
): void {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }

  const stageItem = selectedFormElementDomElement.querySelector('typo3-form-form-element-stage-item') as FormElementStageItem;
  if (stageItem) {
    return;
  }

  const formElementTypeDefinition = getFormElementDefinition(formElement, undefined);
  if (formElementTypeDefinition._isTopLevelFormElement) {
    return;
  }

  let toolbar = selectedFormElementDomElement.querySelector('typo3-form-form-element-stage-item-toolbar') as unknown as FormElementStageItemToolbar | null;
  if (!toolbar) {
    const toolbarEl = document.createElement('typo3-form-form-element-stage-item-toolbar');
    selectedFormElementDomElement.prepend(toolbarEl);
    toolbar = toolbarEl as unknown as FormElementStageItemToolbar;
  }

  // Wire events exactly once per toolbar instance.
  if (!toolbar.dataset.eventsWired) {
    toolbar.dataset.eventsWired = 'true';

    toolbar.addEventListener('toolbar-new-element-before', () => {
      getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
        'view/insertElements/perform/before',
        { disableElementTypes: [] }
      ]);
    });

    toolbar.addEventListener('toolbar-new-element-after', () => {
      getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
        'view/insertElements/perform/after',
        { disableElementTypes: [] }
      ]);
    });

    toolbar.addEventListener('toolbar-remove-element', () => {
      getViewModel().showRemoveFormElementModal(formElement);
    });
  }

  toolbar.active = true;
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
  stageDomElement.replaceChildren(renderFormDefinitionPageAsSortableList(pageIndex));

  stageDomElement.addEventListener('click', function(e) {
    const formElementIdentifierPath = (e.target as Element)
      .closest(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
      ?.getAttribute(getHelper().getDomElementDataAttribute('elementIdentifier'));
    if (
      getUtility().isUndefinedOrNull(formElementIdentifierPath)
      || !getUtility().isNonEmptyString(formElementIdentifierPath)
    ) {
      return;
    }
    getPublisherSubscriber().publish('view/stage/element/clicked', [formElementIdentifierPath]);
  });

  stageDomElement.addEventListener('keydown', function(e: KeyboardEvent) {
    const target = (e.target as Element).closest<HTMLElement>('.formeditor-element[tabindex]');
    if (!target) {
      return;
    }
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      target.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
    }
  });

  if (configuration.isSortable) {
    addSortableEvents();
  }

  if (typeof callback === 'function') {
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
  stageDomElement.replaceChildren();
  stageDomElement.innerHTML = html;

  stageDomElement.querySelectorAll<HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement | HTMLButtonElement>(
    'input, select, textarea, button'
  ).forEach((el) => {
    el.disabled = true;
    ['click', 'dblclick', 'select', 'focus', 'keydown', 'keypress', 'keyup', 'mousedown', 'mouseup'].forEach((evt) => {
      el.addEventListener(evt, (e) => e.preventDefault());
    });
  });

  stageDomElement.querySelector('form')?.addEventListener('submit', (e) => e.preventDefault());

  getAllFormElementDomElements().forEach(function(el: HTMLElement) {
    const formElement = getFormEditorApp()
      .getFormElementByIdentifierPath(el.dataset.elementIdentifierPath);

    if (!getFormElementDefinition(formElement, '_isTopLevelFormElement')) {
      el.setAttribute('title', 'identifier: ' + formElement.get('identifier') + ' (type: ' + formElement.get('type') + ')');
    }
    if (getFormElementDefinition(formElement, '_isTopLevelFormElement')) {
      el.classList.add(getHelper().getDomElementClassName('formElementIsTopLevel'));
    }
    if (getFormElementDefinition(formElement, '_isCompositeFormElement')) {
      el.classList.add(getHelper().getDomElementClassName('formElementIsComposit'));
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
export function renderTopLevelStageItem(formElement: FormElement, template: HTMLElement): void {
  assert(typeof formElement === 'object' && formElement !== null && !Array.isArray(formElement), 'Invalid parameter "formElement"', 1768924251);

  const stageItem = document.createElement('typo3-form-page-stage-item') as PageStageItem;

  stageItem.pageTitle = formElement.get('label') || '';

  template.replaceChildren(stageItem);
}

/**
 * @throws 1768924252
 */
export function renderFormElementStageItem(formElement: FormElement, template: HTMLElement): void {
  assert(typeof formElement === 'object' && formElement !== null && !Array.isArray(formElement), 'Invalid parameter "formElement"', 1768924252);

  const stageItem = document.createElement('typo3-form-form-element-stage-item') as FormElementStageItem;

  stageItem.elementType = getFormElementDefinition(formElement, 'label');
  stageItem.elementIdentifier = formElement.get('identifier');
  stageItem.elementLabel = formElement.get('label') || formElement.get('identifier');
  stageItem.elementIconIdentifier = getFormElementDefinition(formElement, 'iconIdentifier');
  stageItem.isHidden = formElement.get('renderingOptions.enabled') === false;

  const validators = formElement.get('validators');
  const validatorList: Validator[] = [];
  let hasNotEmptyValidator = false;

  if (Array.isArray(validators) && validators.length > 0) {
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
    } else if (typeof defaultValue === 'string') {
      defaultValue = { 0: defaultValue };
    }

    if (typeof propertyValue === 'object' && propertyValue !== null && !Array.isArray(propertyValue)) {
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
    } else if (Array.isArray(propertyValue)) {
      const entries = propertyValue as Record<string, any>;
      for (const propertyValueKey of Object.keys(entries)) {
        let label: string;
        let value: string;

        if (getUtility().isUndefinedOrNull(entries[propertyValueKey]._label)) {
          label = entries[propertyValueKey];
          value = propertyValueKey;
        } else {
          label = entries[propertyValueKey]._label;
          value = entries[propertyValueKey]._value;
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
    if (typeof allowedMimeTypesValue === 'object' && allowedMimeTypesValue !== null && !Array.isArray(allowedMimeTypesValue)) {
      for (const key of Object.keys(allowedMimeTypesValue)) {
        if (!isNaN(Number(key))) {
          mimeTypesList.push(allowedMimeTypesValue[key]);
        }
      }
    } else if (Array.isArray(allowedMimeTypesValue)) {
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

  // Check if form element has validation errors
  const validationResults = getFormEditorApp().validateFormElement(formElement);
  let hasValidationError = false;
  for (let i = 0, len = validationResults.length; i < len; ++i) {
    if (
      validationResults[i].validationResults
      && validationResults[i].validationResults.length > 0
    ) {
      hasValidationError = true;
      break;
    }
  }
  stageItem.invalid = hasValidationError;

  stageItem.addEventListener('toolbar-new-element-before', () => {
    getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
      'view/insertElements/perform/before',
      { disableElementTypes: [] }
    ]);
  });

  stageItem.addEventListener('toolbar-new-element-after', () => {
    getPublisherSubscriber().publish('view/stage/abstract/elementToolbar/button/newElement/clicked', [
      'view/insertElements/perform/after',
      { disableElementTypes: [] }
    ]);
  });

  stageItem.addEventListener('toolbar-remove-element', () => {
    getViewModel().showRemoveFormElementModal(formElement);
  });

  template.replaceChildren(stageItem);
}

/**
 * @deprecated since TYPO3 v14.2, will be removed in TYPO3 v15.
 *   See also Feature #107058.
 */
export function eachTemplateProperty(
  formElement: FormElement,
  template: HTMLElement,
  callback?: (propertyPath: string, propertyValue: unknown, element: HTMLElement) => void
) {
  template.querySelectorAll<HTMLElement>(
    getHelper().getDomElementDataAttribute('templateProperty', 'bracesWithKey')
  ).forEach(function(element: HTMLElement) {
    const propertyPath = element.getAttribute(getHelper().getDomElementDataAttribute('templateProperty'));
    const propertyValue = formElement.get(propertyPath);
    if (typeof callback === 'function') {
      callback(propertyPath, propertyValue, element);
    }
  });
}

/**
 * @deprecated since TYPO3 v14.2, will be removed in TYPO3 v15.
 *   Implement a custom rendering instead.
 *   See also Feature #107058.
 */
export function renderCheckboxTemplate(formElement: FormElement, template: HTMLElement) {
  renderSimpleTemplateWithValidators(formElement, template);

  eachTemplateProperty(formElement, template, function(propertyPath, propertyValue, domElement) {
    if (
      (typeof propertyValue === 'boolean' && propertyValue)
      || propertyValue === 'true'
      || propertyValue === 1
      || propertyValue === '1'
    ) {
      domElement.classList.add(getHelper().getDomElementClassName('noNesting'));
    }
  });
}

/**
 * @deprecated since TYPO3 v14.2, will be removed in TYPO3 v15.
 *   Implement a custom rendering instead.
 *   See also Feature #107058.
 *
 * @throws 1479035696
 */
export function renderSimpleTemplate(formElement: FormElement, template: HTMLElement): void {
  assert(typeof formElement === 'object' && formElement !== null && !Array.isArray(formElement), 'Invalid parameter "formElement"', 1479035696);

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
    const iconContainer = template.querySelector(getHelper().getDomElementDataIdentifierSelector('formElementIcon'));
    if (iconContainer) {
      const tmp = document.createElement('div');
      tmp.innerHTML = icon;
      const iconEl = tmp.firstElementChild;
      if (iconEl) {
        iconEl.classList.add(getHelper().getDomElementClassName('icon'));
        iconContainer.append(iconEl);
      }
    }
  });

  getHelper().getTemplatePropertyElement('_type', template)
    ?.append(document.createTextNode(getFormElementDefinition(formElement, 'label')));
  getHelper().getTemplatePropertyElement('_identifier', template)
    ?.append(document.createTextNode(formElement.get('identifier')));
}

/**
 * @deprecated since TYPO3 v14.2, will be removed in TYPO3 v15.
 *   Implement a custom rendering instead.
 *   See also Feature #107058.
 *
 * @throws 1479035674
 */
export function renderSimpleTemplateWithValidators(formElement: FormElement, template: HTMLElement): void {
  assert(typeof formElement === 'object' && formElement !== null && !Array.isArray(formElement), 'Invalid parameter "formElement"', 1479035674);

  renderSimpleTemplate(formElement, template);

  const validatorsContainerSel = getHelper().getDomElementDataIdentifierSelector('validatorsContainer');
  const validatorsContainerEl = template.querySelector(validatorsContainerSel);
  const validatorsTemplateContent = validatorsContainerEl?.cloneNode(true) as HTMLElement | null;
  validatorsContainerEl?.replaceChildren();

  const validators = formElement.get('validators');

  if (Array.isArray(validators)) {
    let validatorsCountWithoutRequired = 0;
    if (validators.length > 0) {
      for (let i = 0, len = validators.length; i < len; ++i) {
        if ('NotEmpty' === validators[i].identifier) {
          getHelper().getTemplatePropertyElement('_required', template)?.append(
            document.createTextNode('*')
          );
          continue;
        }
        validatorsCountWithoutRequired++;

        const collectionElementConfiguration = getFormEditorApp()
          .getFormEditorDefinition('validators', validators[i].identifier);
        const rowTemplate = validatorsTemplateContent?.cloneNode(true) as HTMLElement | null;
        if (!rowTemplate) { continue; }

        getHelper().getTemplatePropertyElement('_label', rowTemplate)
          ?.append(document.createTextNode(collectionElementConfiguration.label));

        const refreshedContainer = template.querySelector(validatorsContainerSel);
        refreshedContainer?.insertAdjacentHTML('beforeend', rowTemplate.outerHTML);
      }

      if (validatorsCountWithoutRequired > 0) {
        Icons.getIcon(
          getHelper().getDomElementDataAttributeValue('iconValidator'),
          Icons.sizes.small,
          null,
          Icons.states.default,
          Icons.markupIdentifiers.inline
        ).then(function(icon) {
          const iconContainer = template.querySelector(getHelper().getDomElementDataIdentifierSelector('validatorIcon'));
          if (iconContainer) {
            const tmp = document.createElement('div');
            tmp.innerHTML = icon;
            const iconEl = tmp.firstElementChild;
            if (iconEl) {
              iconEl.classList.add(getHelper().getDomElementClassName('icon'));
              iconContainer.append(iconEl);
            }
          }
        });
      }
    }
  }
}

/**
 * @deprecated since TYPO3 v14.2, will be removed in TYPO3 v15.
 *   Implement a custom rendering instead.
 *   See also Feature #107058.
 */
export function renderSelectTemplates(formElement: FormElement, template: HTMLElement): void {
  const multiValueContainerSel = getHelper().getDomElementDataIdentifierSelector('multiValueContainer');
  const multiValueContainerEl = template.querySelector(multiValueContainerSel);
  const multiValueTemplateContent = multiValueContainerEl?.cloneNode(true) as HTMLElement | null;
  multiValueContainerEl?.replaceChildren();

  renderSimpleTemplateWithValidators(formElement, template);

  const propertyPath = template.querySelector(multiValueContainerSel)
    ?.getAttribute(getHelper().getDomElementDataAttribute('templateProperty'));
  const propertyValue = formElement.get(propertyPath);

  const appendMultiValue = (label: string, value: string, defaultValue: Record<string, string>) => {
    let isPreselected = false;
    const rowTemplate = multiValueTemplateContent?.cloneNode(true) as HTMLElement | null;
    if (!rowTemplate) { return; }

    for (const defaultValueKey of Object.keys(defaultValue)) {
      if (defaultValue[defaultValueKey] === value) { isPreselected = true; break; }
    }

    getHelper().getTemplatePropertyElement('_label', rowTemplate)
      ?.append(document.createTextNode(label));

    if (isPreselected) {
      getHelper().getTemplatePropertyElement('_label', rowTemplate)
        ?.classList.add(getHelper().getDomElementClassName('selected'));
    }

    template.querySelector(multiValueContainerSel)
      ?.insertAdjacentHTML('beforeend', rowTemplate.outerHTML);
  };

  let defaultValue = formElement.get('defaultValue');
  if (getFormEditorApp().getUtility().isUndefinedOrNull(defaultValue)) {
    defaultValue = {};
  } else if (typeof defaultValue === 'string') {
    defaultValue = { 0: defaultValue };
  }

  if (typeof propertyValue === 'object' && propertyValue !== null && !Array.isArray(propertyValue)) {
    for (const propertyValueKey of Object.keys(propertyValue)) {
      appendMultiValue(propertyValue[propertyValueKey], propertyValueKey, defaultValue);
    }
  } else if (Array.isArray(propertyValue)) {
    const entries = propertyValue as Record<string, any>;
    for (const propertyValueKey of Object.keys(entries)) {
      if (getUtility().isUndefinedOrNull(entries[propertyValueKey]._label)) {
        appendMultiValue(entries[propertyValueKey], propertyValueKey, defaultValue);
      } else {
        appendMultiValue(entries[propertyValueKey]._label, entries[propertyValueKey]._value, defaultValue);
      }
    }
  }
}

/**
 * @deprecated since TYPO3 v14.2, will be removed in TYPO3 v15.
 *   Implement a custom rendering instead.
 *   See also Feature #107058.
 */
export function renderFileUploadTemplates(formElement: FormElement, template: HTMLElement): void {
  const multiValueContainerSel = getHelper().getDomElementDataIdentifierSelector('multiValueContainer');
  const multiValueContainerEl = template.querySelector(multiValueContainerSel);
  const multiValueTemplateContent = multiValueContainerEl?.cloneNode(true) as HTMLElement | null;
  multiValueContainerEl?.replaceChildren();

  renderSimpleTemplateWithValidators(formElement, template);

  const propertyPath = template.querySelector(multiValueContainerSel)
    ?.getAttribute(getHelper().getDomElementDataAttribute('templateProperty'));
  const propertyValue = formElement.get(propertyPath);

  const appendMultiValue = function(value: string) {
    const rowTemplate = multiValueTemplateContent?.cloneNode(true) as HTMLElement | null;
    if (!rowTemplate) { return; }
    getHelper().getTemplatePropertyElement('_value', rowTemplate)?.append(document.createTextNode(value));
    template.querySelector(multiValueContainerSel)
      ?.insertAdjacentHTML('beforeend', rowTemplate.outerHTML);
  };

  if (typeof propertyValue === 'object' && propertyValue !== null && !Array.isArray(propertyValue)) {
    for (const propertyValueKey of Object.keys(propertyValue)) {
      appendMultiValue(propertyValue[propertyValueKey]);
    }
  } else if (Array.isArray(propertyValue)) {
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
  appendToDomElement: HTMLElement,
  customConfiguration?: Configuration
): typeof import('./stage-component') {
  formEditorApp = _formEditorApp;
  assert(typeof appendToDomElement === 'object' && appendToDomElement !== null && !Array.isArray(appendToDomElement), 'Invalid parameter "appendToDomElement"', 1478992119);
  stageDomElement = appendToDomElement;
  configuration = merge({}, defaultConfiguration, customConfiguration ?? {}) as Configuration;
  Helper.bootstrap(formEditorApp);
  return this;
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'view/stage/abstract/render/template/perform': readonly [
      formElement: FormElement,
      template: HTMLElement
    ];
    'view/stage/abstract/dnd/start': readonly [
      draggedFormElementDomElement: HTMLElement,
      draggedFormPlaceholderDomElement: HTMLElement,
    ];
    'view/stage/abstract/dnd/change': readonly [
      placeholderDomElement: HTMLElement,
      parentFormElementIdentifierPath: string,
      enclosingCompositeFormElement: FormElement
    ];
    'view/stage/abstract/dnd/update': readonly [
      movedDomElement: HTMLElement,
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
      targetEvent: 'view/insertElements/perform/before' | 'view/insertElements/perform/after' | 'view/insertElements/perform/inside',
      modalConfiguration: InsertElementsModalConfiguration
    ];
    'view/insertElements/perform/before': readonly [
      formElementType: string
    ];
    'view/insertElements/perform/after': readonly [
      formElementType: string
    ];
    'view/insertElements/perform/inside': readonly [
      formElementType: string
    ];
  }
}
