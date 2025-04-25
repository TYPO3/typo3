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
 * Module: @typo3/form/backend/form-editor/tree-component
 */
import $ from 'jquery';
import * as Helper from '@typo3/form/backend/form-editor/helper';
import Icons from '@typo3/backend/icons';
import Sortable from 'sortablejs';

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

enum TreeNodeLinkType {
  hidden = 'hidden',
  connect = 'connect',
  line = 'line',
  last = 'last',
}

interface Configuration extends Partial<HelperConfiguration> {
  isSortable: boolean
}

const defaultConfiguration: Configuration = {
  domElementClassNames: {
    collapsed: 'collapsed',
    expanded: 'expanded',
    hasChildren: 'has-children',
    sortable: 'sortable',
    noNesting: 'no-nesting'
  },
  domElementDataAttributeNames: {
    abstractType: 'data-element-abstract-type'
  },
  domElementDataAttributeValues: {
    collapse: 'actions-chevron-right',
    expander: 'treeExpander',
    title: 'treeTitle'
  },
  isSortable: true,
};

let configuration: Configuration = null;

let formEditorApp: FormEditor = null;

let treeDomElement: JQuery = null;

const expanderStates: Record<string, boolean> = {};

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

function renderTreeNodeLink(type: TreeNodeLinkType): HTMLElement {
  const link = document.createElement('span');
  link.classList.add('formeditor-tree-line', 'formeditor-tree-line--' + type);

  return link;
}

/**
 * @publish view/tree/render/listItemAdded
 * @throws 1478715704
 */
function renderNestedSortableListItem(formElement: FormElement, current: number, max: number): JQuery {
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1478715704);

  const listItem = $('<li></li>');
  if (!getFormElementDefinition(formElement, '_isCompositeFormElement')) {
    listItem.addClass(getHelper().getDomElementClassName('noNesting'));
  }

  const listItemContent = $('<div></div>')
    .addClass('formeditor-tree-item')
    .attr(getHelper().getDomElementDataAttribute('elementIdentifier'), formElement.get('__identifierPath'))
    .append(
      $('<span></span>')
        .addClass('formeditor-tree-title')
        .attr(getHelper().getDomElementDataAttribute('identifier'), getHelper().getDomElementDataAttributeValue('title'))
        .append(buildTitleByFormElement(formElement))
    );

  if (getFormElementDefinition(formElement, '_isCompositeFormElement')) {
    listItemContent.attr(getHelper().getDomElementDataAttribute('abstractType'), 'isCompositeFormElement');
  }
  if (getFormElementDefinition(formElement, '_isTopLevelFormElement')) {
    listItemContent.attr(getHelper().getDomElementDataAttribute('abstractType'), 'isTopLevelFormElement');
  }

  const caret = document.createElement('span');
  caret.classList.add('caret');
  const expanderItem = $('<span></span>')
    .addClass('formeditor-tree-expander')
    .attr('data-identifier', getHelper().getDomElementDataAttributeValue('expander'))
    .append(caret);
  listItemContent.prepend(expanderItem);

  Icons.getIcon(getFormElementDefinition(formElement, 'iconIdentifier'), Icons.sizes.small, null, Icons.states.default).then(function(icon) {
    expanderItem.after(
      $('<span></span>')
        .addClass('formeditor-tree-icon')
        .addClass(getHelper().getDomElementClassName('icon'))
        .attr('title', 'id = ' + formElement.get('identifier'))
        .append(icon)
    );

    if (getFormElementDefinition(formElement, '_isCompositeFormElement')) {
      if (formElement.get('renderables') && formElement.get('renderables').length > 0) {
        expanderItem.before(renderTreeNodeLink(current != max ? TreeNodeLinkType.connect : TreeNodeLinkType.last));
        listItem.addClass(getHelper().getDomElementClassName('hasChildren'));
      } else {
        expanderItem.before(renderTreeNodeLink(TreeNodeLinkType.connect)).remove();
      }
    } else {
      listItemContent.prepend(renderTreeNodeLink(current != max ? TreeNodeLinkType.connect : TreeNodeLinkType.last));
      expanderItem.remove();
    }

    let searchElement = formElement.get('__parentRenderable');
    while (searchElement) {
      if (searchElement.get('__identifierPath') === getRootFormElement().get('__identifierPath')) {
        break;
      }

      if (searchElement.get('__identifierPath') === getFormEditorApp().getLastFormElementWithinParentFormElement(searchElement).get('__identifierPath')) {
        listItemContent.prepend(renderTreeNodeLink(TreeNodeLinkType.hidden));
      } else {
        listItemContent.prepend(renderTreeNodeLink(TreeNodeLinkType.line));
      }
      searchElement = searchElement.get('__parentRenderable');
    }
  });
  listItem.append(listItemContent);

  getPublisherSubscriber().publish('view/tree/render/listItemAdded', [listItem, formElement]);
  const childFormElements = formElement.get('renderables');
  let childList = null;
  if ('array' === $.type(childFormElements)) {
    childList = $('<ol></ol>');
    for (let i = 0, len = childFormElements.length; i < len; ++i) {
      childList.append(renderNestedSortableListItem(childFormElements[i], i + 1, len));
    }
  }

  if (childList) {
    listItem.append(childList);
  }
  return listItem;
}

/**
 * @publish view/tree/dnd/stop
 * @publish view/tree/dnd/change
 * @publish view/tree/dnd/update
 */
function addSortableEvents(): void {
  const defaultConfiguration: Sortable.Options = {
    handle: 'div' + getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'),
    draggable: 'li',
    animation: 200,
    fallbackTolerance: 200,
    fallbackOnBody: true,
    swapThreshold: 0.6,
    dragClass: 'formeditor-sortable-drag',
    ghostClass: 'formeditor-sortable-ghost',
    onChange: function (e) {
      let enclosingCompositeFormElement;
      const parentFormElementIdentifierPath = getParentTreeNodeIdentifierPathWithinDomElement($(e.item));

      if (parentFormElementIdentifierPath) {
        enclosingCompositeFormElement = getFormEditorApp().findEnclosingCompositeFormElementWhichIsNotOnTopLevel(parentFormElementIdentifierPath);
      }
      getPublisherSubscriber().publish('view/tree/dnd/change', [$(e.item), parentFormElementIdentifierPath, enclosingCompositeFormElement]);
    },
    onEnd: function (e) {
      const movedFormElementIdentifierPath = getTreeNodeIdentifierPathWithinDomElement($(e.item));
      const previousFormElementIdentifierPath = getSiblingTreeNodeIdentifierPathWithinDomElement($(e.item), 'prev');
      const nextFormElementIdentifierPath = getSiblingTreeNodeIdentifierPathWithinDomElement($(e.item), 'next');

      getPublisherSubscriber().publish('view/tree/dnd/update', [$(e.item), movedFormElementIdentifierPath, previousFormElementIdentifierPath, nextFormElementIdentifierPath]);
      getPublisherSubscriber().publish('view/tree/dnd/stop', [getTreeNodeIdentifierPathWithinDomElement($(e.item))]);
    },
  };

  const sortableRoot: HTMLElement = treeDomElement.get(0).querySelector('ol.' + getHelper().getDomElementClassName('sortable'));
  new Sortable(sortableRoot, {
    ...defaultConfiguration,
    ...{
      group: 'tree-step-nodes',
      put: ['tree-step-nodes'],
    }
  });

  sortableRoot.querySelectorAll('ol').forEach(function (sortableList) {
    new Sortable(sortableList, {
      ...defaultConfiguration,
      ...{
        group: 'tree-leaves-nodes',
        pull: ['tree-leaves-nodes'],
      }
    });
  });
}

function saveExpanderStates(): void {
  const addStates = function(formElement: FormElement) {
    if (getFormElementDefinition(formElement, '_isCompositeFormElement')) {
      const treeNode = getTreeNode(formElement);
      if (treeNode.length) {
        if (treeNode.closest('li').hasClass(getHelper().getDomElementClassName('expanded'))) {
          expanderStates[formElement.get('__identifierPath')] = true;
        } else {
          expanderStates[formElement.get('__identifierPath')] = false;
        }
      }

      if (getUtility().isUndefinedOrNull(expanderStates[formElement.get('__identifierPath')])) {
        expanderStates[formElement.get('__identifierPath')] = true;
      }
    }

    const childFormElements = formElement.get('renderables');
    if ('array' === $.type(childFormElements)) {
      for (let i = 0, len = childFormElements.length; i < len; ++i) {
        addStates(childFormElements[i]);
      }
    }
  };
  addStates(getRootFormElement());

  for (const identifierPath of Object.keys(expanderStates)) {
    try {
      getFormEditorApp().getFormElementByIdentifierPath(identifierPath);
    } catch {
      delete expanderStates[identifierPath];
    }
  }
}

function loadExpanderStates(): void {
  for (const identifierPath of Object.keys(expanderStates)) {
    const treeNode = getTreeNode(identifierPath);
    if (treeNode.length) {
      if (expanderStates[identifierPath]) {
        treeNode.closest('li')
          .removeClass(getHelper().getDomElementClassName('collapsed'))
          .addClass(getHelper().getDomElementClassName('expanded'));
      } else {
        treeNode.closest('li')
          .addClass(getHelper().getDomElementClassName('collapsed'))
          .removeClass(getHelper().getDomElementClassName('expanded'));
      }
    }
  }
}

/**
 * @throws 1478721208
 */
export function renderCompositeFormElementChildsAsSortableList(formElement: FormElement): JQuery {
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1478721208);

  const elementList = $('<ol></ol>').addClass(getHelper().getDomElementClassName('sortable'));
  if ('array' === $.type(formElement.get('renderables'))) {
    for (let i = 0, len = formElement.get('renderables').length; i < len; ++i) {
      elementList.append(renderNestedSortableListItem(formElement.get('renderables')[i], i + 1, len));
    }
  }
  return elementList;
}

/**
 * @publish view/tree/node/clicked
 */
export function renew(formElement?: FormElement): void {
  if (getFormEditorApp().getUtility().isUndefinedOrNull(formElement)) {
    formElement = getRootFormElement();
  }
  saveExpanderStates();
  treeDomElement.off().empty().append(renderCompositeFormElementChildsAsSortableList(formElement));

  // We make use of the same strategy for db click detection as the current core pagetree implementation.
  // @see https://github.com/typo3/typo3/blob/260226e93c651356545e91a7c55ee63e186766d5/typo3/sysext/backend/Resources/Public/JavaScript/PageTree/PageTree.js#L350
  let clicks = 0;
  treeDomElement.on('click', function(e) {
    const formElementIdentifierPath = $(e.target)
      .closest(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
      .attr(getHelper().getDomElementDataAttribute('elementIdentifier'));
    if (getUtility().isUndefinedOrNull(formElementIdentifierPath) || !getUtility().isNonEmptyString(formElementIdentifierPath)) {
      return;
    }

    clicks++;

    if (clicks === 1) {
      setTimeout(function() {
        if (clicks === 1) {
          getPublisherSubscriber().publish('view/tree/node/clicked', [formElementIdentifierPath]);
        } else {
          editTreeNodeLabel(formElementIdentifierPath);
        }
        clicks = 0;
      }, 300);
    }
  });

  $(getHelper().getDomElementDataIdentifierSelector('expander'), treeDomElement).on('click', function(this: HTMLElement) {
    $(this).closest('li').toggleClass(getHelper().getDomElementClassName('collapsed')).toggleClass(getHelper().getDomElementClassName('expanded'));
  });

  if (configuration.isSortable) {
    addSortableEvents();
  }
  loadExpanderStates();
}

export function getAllTreeNodes(): JQuery {
  return $(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'), treeDomElement);
}

export function getTreeNodeWithinDomElement(element: HTMLElement | JQuery): JQuery {
  return $(element).find(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey')).first();
}

export function getTreeNodeIdentifierPathWithinDomElement(element: HTMLElement | JQuery): string {
  return getTreeNodeWithinDomElement($(element)).attr(getHelper().getDomElementDataAttribute('elementIdentifier'));
}

export function getParentTreeNodeWithinDomElement(element: HTMLElement | JQuery): JQuery {
  return $(element).parent().closest('li').find(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey')).first();
}

export function getParentTreeNodeIdentifierPathWithinDomElement(
  element: HTMLElement | JQuery
): string {
  return getParentTreeNodeWithinDomElement(element).attr(getHelper().getDomElementDataAttribute('elementIdentifier'));
}

export function getSiblingTreeNodeIdentifierPathWithinDomElement(
  element: HTMLElement | JQuery,
  position: string
): string {
  if (getUtility().isUndefinedOrNull(position)) {
    position = 'prev';
  }
  const formElementIdentifierPath = getTreeNodeIdentifierPathWithinDomElement(element);
  element = (position === 'prev') ? $(element).prev('li') : $(element).next('li');
  return element.find(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKey'))
    .not(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKeyValue', [formElementIdentifierPath]))
    .first()
    .attr(getHelper().getDomElementDataAttribute('elementIdentifier'));
}

export function setTreeNodeTitle(title?: string, formElement?: FormElement): void {
  let titleContent: HTMLElement;
  if (getUtility().isUndefinedOrNull(title)) {
    titleContent = buildTitleByFormElement(formElement);
  } else {
    titleContent = document.createElement('span');
    titleContent.textContent = title;
  }

  $(getHelper().getDomElementDataIdentifierSelector('title'), getTreeNode(formElement)).get(0).replaceChildren(titleContent);
}

export function getTreeNode(formElement?: FormElement | string): JQuery {
  let formElementIdentifierPath: string;

  if (typeof formElement === 'string') {
    formElementIdentifierPath = formElement;
  } else {
    if (getUtility().isUndefinedOrNull(formElement)) {
      formElementIdentifierPath = getCurrentlySelectedFormElement().get('__identifierPath');
    } else {
      formElementIdentifierPath = formElement.get('__identifierPath');
    }
  }
  return $(getHelper().getDomElementDataAttribute('elementIdentifier', 'bracesWithKeyValue', [formElementIdentifierPath]), treeDomElement);
}

/**
 * @throws 1478719287
 */
export function buildTitleByFormElement(formElement: FormElement): HTMLElement {
  if (getUtility().isUndefinedOrNull(formElement)) {
    formElement = getCurrentlySelectedFormElement();
  }
  assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1478719287);

  const span = document.createElement('span');
  span.textContent = formElement.get('label') ? formElement.get('label') : formElement.get('identifier');
  const small = document.createElement('small');
  small.textContent = '(' + getFormElementDefinition(formElement, 'label') + ')';
  span.appendChild(small);
  return span;
}

export function getTreeDomElement(): JQuery {
  return treeDomElement;
}

function editTreeNodeLabel(formElementIdentifierPath: string): void {
  const treeNode = getTreeNode(formElementIdentifierPath);
  const titleNode = $(getHelper().getDomElementDataIdentifierSelector('title'), treeNode);
  const currentTitle = titleNode.children()[0].childNodes[0].nodeValue.trim();
  let nodeIsEdit = true;

  const input = $('<input>')
    .attr('class', 'formeditor-tree-edit')
    .attr('type', 'text')
    .attr('value', currentTitle)
    .on('click', (e: Event) => {
      e.stopPropagation();
    })
    .on('keyup', function(this: HTMLInputElement, e) {
      if (e.keyCode === 13 || e.keyCode === 9) { //enter || tab
        const newTitle = this.value.trim();

        if (getUtility().isNonEmptyString(newTitle) && (newTitle !== currentTitle)) {
          nodeIsEdit = false;
          input.remove();
          getPublisherSubscriber().publish('view/tree/node/changed', [formElementIdentifierPath, newTitle]);
        } else {
          nodeIsEdit = false;
          input.remove();
        }
      } else if (e.keyCode === 27) { //esc
        nodeIsEdit = false;
        input.remove();
      }
    })
    .on('blur', function(this: HTMLInputElement) {
      if(nodeIsEdit) {
        const newTitle = this.value.trim();
        input.remove();
        if(getUtility().isNonEmptyString(newTitle) && newTitle !== currentTitle) {
          getPublisherSubscriber().publish('view/tree/node/changed', [formElementIdentifierPath, newTitle]);
        }
      }
    });

  treeNode.append(input);
  input.focus();
}

/**
 * @throws 1478714814
 */
export function bootstrap(
  this: typeof import('./tree-component'),
  _formEditorApp: FormEditor,
  appendToDomElement: JQuery,
  customConfiguration?: typeof defaultConfiguration
): typeof import('./tree-component') {
  formEditorApp = _formEditorApp;
  assert('object' === $.type(appendToDomElement), 'Invalid parameter "appendToDomElement"', 1478714814);
  treeDomElement = $(appendToDomElement);
  configuration = $.extend(true, defaultConfiguration, customConfiguration || {});
  Helper.bootstrap(formEditorApp);
  return this;
}

declare global {
  interface PublisherSubscriberTopicArgumentsMap {
    'view/tree/node/changed': readonly [
      formElementIdentifierPath: string,
      newLabel: string,
    ];
    'view/tree/node/clicked': readonly [
      formElementIdentifierPath: string
    ];
    'view/tree/render/listItemAdded': readonly [
      listItem: JQuery,
      formElement: FormElement
    ];
    'view/tree/dnd/update': readonly [
      dndItem: JQuery,
      movedFormElementIdentifierPath: string,
      previousFormElementIdentifierPath: string,
      nextFormElementIdentifierPath: string,
    ];
    'view/tree/dnd/change': readonly [
      dndItem: JQuery,
      parentFormElementIdentifierPath: string,
      enclosingCompositeFormElement: FormElement,
    ];
    'view/tree/dnd/stop': readonly [
      treeNodeIdentifierPathWithinDomElement: string,
    ];
  }
}
