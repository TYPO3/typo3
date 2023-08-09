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
 * Module: @typo3/form/backend/form-editor/helper
 */
import $ from 'jquery';
import type {
  FormEditor,
} from '@typo3/form/backend/form-editor';
import type {
  Utility,
} from '@typo3/form/backend/form-editor/core';

let formEditorApp: FormEditor = null;

let configuration: Configuration = null;

export interface Configuration {
  domElementClassNames: Record<string, string>,
  domElementDataAttributeNames: Record<string, string>,
  domElementSelectorPattern: Record<string, string>,
  domElementDataAttributeValues: Record<string, string>,
  domElementIdNames: Record<string, string>,
}

const defaultConfiguration: Partial<Configuration> = {
  domElementClassNames: {
    active: 'active',
    buttonCollectionElementRemove: 't3-form-collection-element-remove-button',
    buttonFormEditor: 't3-form-button',
    disabled: 'disabled',
    hidden: 'hidden',
    icon: 't3-form-icon',
    jQueryUiStateDisabled: 'ui-state-disabled',
    sortableHover: 'sortable-hover'
  },
  domElementDataAttributeNames: {
    elementIdentifier: 'data-element-identifier-path',
    identifier: 'data-identifier',
    template: 'data-template-name',
    templateProperty: 'data-template-property'
  },
  domElementSelectorPattern: {
    bracesWithKey: '[{0}]',
    bracesWithKeyValue: '[{0}="{1}"]',
    class: '.{0}',
    id: '#{0}',
    keyValue: '{0}="{1}"'
  }
};

function getFormEditorApp(): FormEditor {
  return formEditorApp;
}

function getUtility(): Utility {
  return getFormEditorApp().getUtility();
}

function assert(test: boolean|(() => boolean), message: string, messageCode: number): void {
  return getFormEditorApp().assert(test, message, messageCode);
}

/**
 * @throws 1478950623
 */
export function setConfiguration(
  this: typeof import('./helper'),
  customConfiguration: Partial<Configuration>
): typeof import('./helper') {
  assert('object' === $.type(customConfiguration), 'Invalid parameter "partialConfiguration"', 1478950623);
  configuration = $.extend(true, defaultConfiguration, customConfiguration);
  return this;
}

/**
 * @throws 1478801251
 * @throws 1478801252
 */
export function buildDomElementSelectorHelper(
  patternIdentifier: keyof Configuration['domElementSelectorPattern'],
  replacements: string[]
): string {
  let newString;
  assert(
    !getUtility().isUndefinedOrNull(configuration.domElementSelectorPattern[patternIdentifier]),
    'Invalid parameter "patternIdentifier" (' + patternIdentifier + ')',
    1478801251
  );
  assert('array' === $.type(replacements), 'Invalid parameter "replacements"', 1478801252);

  newString = configuration.domElementSelectorPattern[patternIdentifier];
  for (let i = 0, len = replacements.length; i < len; ++i) {
    newString = newString.replace('{' + i + '}', replacements[i]);
  }
  return newString;
}

/**
 * @throws 1478372374
 */
export function getDomElementSelector(
  selectorIdentifier: keyof Configuration['domElementSelectorPattern'],
  args: string[]
): string {
  assert(
    !getUtility().isUndefinedOrNull(configuration.domElementSelectorPattern[selectorIdentifier]),
    'Invalid parameter "selectorIdentifier" (' + selectorIdentifier + ')',
    1478372374
  );
  return buildDomElementSelectorHelper(selectorIdentifier, args);
}

/**
 * @throws 1478803906
 */
export function getDomElementClassName(
  classNameIdentifier: keyof Configuration['domElementClassNames'],
  asSelector?: boolean
): string {
  let className;
  assert(
    !getUtility().isUndefinedOrNull(configuration.domElementClassNames[classNameIdentifier]),
    'Invalid parameter "classNameIdentifier" (' + classNameIdentifier + ')',
    1478803906
  );

  className = configuration.domElementClassNames[classNameIdentifier];
  if (asSelector) {
    className = getDomElementSelector('class', [className]);
  }
  return className;
}

/**
 * @throws 1479251518
 */
export function getDomElementIdName(idNameIdentifier: string, asSelector: boolean): string {
  let idName;
  assert(
    !getUtility().isUndefinedOrNull(configuration.domElementIdNames[idNameIdentifier]),
    'Invalid parameter "domElementIdNames" (' + idNameIdentifier + ')',
    1479251518
  );

  idName = configuration.domElementIdNames[idNameIdentifier];
  if (asSelector) {
    idName = getDomElementSelector('id', [idName]);
  }
  return idName;
}

/**
 * @throws 1478806884
 */
export function getDomElementDataAttributeValue(dataAttributeValueIdentifier: string): string {
  assert(
    !getUtility().isUndefinedOrNull(configuration.domElementDataAttributeValues[dataAttributeValueIdentifier]),
    'Invalid parameter "dataAttributeValueIdentifier" (' + dataAttributeValueIdentifier + ')',
    1478806884
  );
  return configuration.domElementDataAttributeValues[dataAttributeValueIdentifier];
}

/**
 * @throws 1478808035
 */
export function getDomElementDataAttribute(
  dataAttributeIdentifier: keyof Configuration['domElementDataAttributeNames'],
  selectorIdentifier?: keyof Configuration['domElementSelectorPattern'],
  additionalSelectorArgs?: string[]
): string {
  assert(
    !getUtility().isUndefinedOrNull(configuration.domElementDataAttributeNames[dataAttributeIdentifier]),
    'Invalid parameter "dataAttributeIdentifier" (' + dataAttributeIdentifier + ')',
    1478808035
  );

  if (getUtility().isUndefinedOrNull(selectorIdentifier)) {
    return configuration.domElementDataAttributeNames[dataAttributeIdentifier];
  }

  additionalSelectorArgs = additionalSelectorArgs || [];
  return getDomElementSelector(
    selectorIdentifier,
    [configuration.domElementDataAttributeNames[dataAttributeIdentifier]].concat(additionalSelectorArgs)
  );
}

/**
 * Return a string like [data-identifier="someValue"]
 */
export function getDomElementDataIdentifierSelector(
  dataAttributeValueIdentifier: string
): string {
  return getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [getDomElementDataAttributeValue(dataAttributeValueIdentifier)]);
}

export function getTemplate(templateName: string): JQuery {
  if (!getUtility().isUndefinedOrNull(configuration.domElementDataAttributeValues[templateName])) {
    templateName = getDomElementDataAttributeValue(templateName);
  }

  return $(getDomElementDataAttribute('template', 'bracesWithKeyValue', [templateName]));
}

export function getTemplatePropertyDomElement(
  templatePropertyName: string,
  templateDomElement: HTMLElement | JQuery
): JQuery {
  return $(getDomElementDataAttribute('templateProperty', 'bracesWithKeyValue', [templatePropertyName]), $(templateDomElement));
}

export function bootstrap(_formEditorApp: FormEditor): void {
  formEditorApp = _formEditorApp;
}
