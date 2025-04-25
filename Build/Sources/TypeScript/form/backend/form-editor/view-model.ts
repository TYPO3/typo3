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
import $ from 'jquery';
import * as TreeComponent from '@typo3/form/backend/form-editor/tree-component';
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
} from '@typo3/form/backend/form-editor/core';
import type {
  Configuration as HelperConfiguration,
} from '@typo3/form/backend/form-editor/helper';
import type { InsertElementsModalConfiguration } from '@typo3/form/backend/form-editor/modals-component';
import type { JavaScriptItemPayload } from '@typo3/core/java-script-item-processor';

export interface Configuration extends Partial<HelperConfiguration> {
  panels: {
    structure: {
      width: number
    },
    stage: {
      marginLeft: number,
      marginRight: number,
      marginLeftCollapsed: number,
      marginRightCollapsed: number,
      maxWidthPreview: number,
      maxWidthAbstract: number,
    },
    inspector: {
      width: number
    }
  }
}

type AdditionalViewModelModules = JavaScriptItemPayload[];

const configuration: Configuration = {
  domElementClassNames: {
    formElementIsComposit: 't3-form-element-composit',
    formElementIsTopLevel: 't3-form-element-toplevel',
    hasError: 'has-error',
    headerButtonBar: 'module-docheader-bar-column-left',
    selectedCompositFormElement: 't3-form-form-composit-element-selected',
    selectedFormElement: 't3-form-form-element-selected',
    selectedRootFormElement: 't3-form-root-element-selected',
    selectedStagePanel: 't3-form-form-stage-selected',
    sortableHover: 'sortable-hover',
    stageViewModeAbstract: 't3-form-stage-viewmode-abstract',
    stageViewModePreview: 't3-form-stage-viewmode-preview',
    validationErrors: 't3-form-validation-errors',
    validationChildHasErrors: 't3-form-validation-child-has-error'
  },
  domElementDataAttributeNames: {
    abstractType: 'data-element-abstract-type'
  },
  domElementDataAttributeValues: {
    buttonHeaderClose: 'closeButton',
    buttonHeaderNewPage: 'headerNewPage',
    buttonHeaderPaginationNext: 'buttonPaginationNext',
    buttonHeaderPaginationPrevious: 'buttonPaginationPrevious',
    buttonHeaderRedo: 'redoButton',
    buttonHeaderSave: 'saveButton',
    buttonHeaderSettings: 'formSettingsButton',
    buttonHeaderUndo: 'undoButton',
    buttonHeaderViewModeAbstract: 'buttonViewModeAbstract',
    buttonHeaderViewModePreview: 'buttonViewModePreview',
    buttonStageNewElementBottom: 'stageNewElementBottom',
    buttonStructureNewPage: 'treeNewPageBottom',
    iconMailform: 'content-form',
    iconSave: 'actions-document-save',
    iconSaveSpinner: 'spinner-circle',
    inspectorSection: 'inspectorSection',
    moduleLoadingIndicator: 'moduleLoadingIndicator',
    moduleWrapper: 'moduleWrapper',
    stageArea: 'stageArea',
    stageContainer: 'stageContainer',
    stageContainerInner: 'stageContainerInner',
    stageNewElementRow: 'stageNewElementRow',
    stagePanelHeading: 'panelHeading',
    stageSection: 'stageSection',
    structure: 'structure-element',
    structureSection: 'structureSection',
    structureRootContainer: 'treeRootContainer',
    structureRootElement: 'treeRootElement'
  },
  panels: {
    structure: {
      width: 300
    },
    stage: {
      marginLeft: 300,
      marginRight: 325,
      marginLeftCollapsed: 0,
      marginRightCollapsed: -25,
      maxWidthPreview: 1000,
      maxWidthAbstract: 800
    },
    inspector: {
      width: 350
    }
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

function addPropertyValidators(): void {
  getFormEditorApp().addPropertyValidationValidator('NotEmpty', function(formElement, propertyPath) {
    const value = formElement.get(propertyPath);
    if (value === '' || $.isArray(value) && !value.length) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('NotEmpty').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('Integer', function(formElement, propertyPath) {
    if (!$.isNumeric(formElement.get(propertyPath))) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('Integer').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('IntegerOrEmpty', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    if (formElement.get(propertyPath).length > 0 && !$.isNumeric(formElement.get(propertyPath))) {
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
    if (!formElement.get(propertyPath).match(/^([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])$/i)) {
      return getFormEditorApp().getFormElementPropertyValidatorDefinition('RFC3339FullDate').errorMessage || 'invalid value';
    }
    return undefined;
  });

  getFormEditorApp().addPropertyValidationValidator('RFC3339FullDateOrEmpty', function(formElement, propertyPath) {
    if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
      return undefined;
    }
    if (formElement.get(propertyPath).length > 0 && !formElement.get(propertyPath).match(/^([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])$/i)) {
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

  if ('array' !== $.type(additionalViewModelModules)) {
    getPublisherSubscriber().publish('view/ready');
    return;
  }
  const additionalViewModelModulesLength = additionalViewModelModules.length;

  if (additionalViewModelModulesLength > 0) {
    let loadedAdditionalViewModelModules = 0;
    for (let i = 0; i < additionalViewModelModulesLength; ++i) {
      loadModule(additionalViewModelModules[i]).then(function(additionalViewModelModule) {
        assert(
          'function' === $.type(additionalViewModelModule.bootstrap),
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
    'function' === $.type(TreeComponent.bootstrap),
    'The structure component does not implement the method "bootstrap"',
    1478268639
  );

  structureComponent = TreeComponent.bootstrap(
    getFormEditorApp(),
    $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
      getHelper().getDomElementDataAttributeValue('structure')
    ]))
  );

  $(getHelper().getDomElementDataIdentifierSelector('iconMailform'),
    $(getHelper().getDomElementDataIdentifierSelector('structureRootContainer'))
  ).attr('title', 'identifier: ' + getRootFormElement().get('identifier'));
}

/**
 * @throws 1478895106
 */
function modalsComponentSetup(): void {
  assert(
    'function' === $.type(ModalsComponent.bootstrap),
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
    'function' === $.type(InspectorComponent.bootstrap),
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
    'function' === $.type(InspectorComponent.bootstrap),
    'The stage component does not implement the method "bootstrap"',
    1478986610
  );
  stageComponent = StageComponent.bootstrap(
    getFormEditorApp(),
    $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
      getHelper().getDomElementDataAttributeValue('stageArea')
    ]))
  );

  getStage().getStagePanelDomElement().on('click', function(e) {
    if (
      $(e.target).attr(getHelper().getDomElementDataAttribute('identifier')) === getHelper().getDomElementDataAttributeValue('stagePanelHeading')
      || $(e.target).attr(getHelper().getDomElementDataAttribute('identifier')) === getHelper().getDomElementDataAttributeValue('stageSection')
      || $(e.target).attr(getHelper().getDomElementDataAttribute('identifier')) === getHelper().getDomElementDataAttributeValue('stageArea')
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
  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderSave')).on('click', function() {
    getPublisherSubscriber().publish('view/header/button/save/clicked', []);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderSettings')).on('click', function() {
    getPublisherSubscriber().publish('view/header/formSettings/clicked', []);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonStageNewElementBottom')).on('click', function() {
    getPublisherSubscriber().publish(
      'view/stage/abstract/button/newElement/clicked', [
        'view/insertElements/perform/bottom'
      ]
    );
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderNewPage')).on('click', function() {
    getPublisherSubscriber().publish('view/header/button/newPage/clicked', ['view/insertPages/perform']);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonStructureNewPage')).on('click', function() {
    getPublisherSubscriber().publish('view/structure/button/newPage/clicked', ['view/insertPages/perform']);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderClose')).on('click', function(e) {
    if (!getFormEditorApp().getUnsavedContent()) {
      return;
    }
    e.preventDefault();
    getPublisherSubscriber().publish('view/header/button/close/clicked', []);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')).on('click', function() {
    getPublisherSubscriber().publish('view/undoButton/clicked', []);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')).on('click', function() {
    getPublisherSubscriber().publish('view/redoButton/clicked', []);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')).on('click', function() {
    getPublisherSubscriber().publish('view/viewModeButton/abstract/clicked', []);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModePreview')).on('click', function() {
    getPublisherSubscriber().publish('view/viewModeButton/preview/clicked', []);
  });

  $(getHelper().getDomElementDataIdentifierSelector('structureRootContainer')).on('click', function() {
    getPublisherSubscriber().publish('view/structure/root/selected');
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderPaginationNext')).on('click', function() {
    getPublisherSubscriber().publish('view/paginationNext/clicked', []);
  });

  $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderPaginationPrevious')).on('click', function() {
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

export function getConfiguration(): Configuration {
  return $.extend(true, {}, configuration);
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

export function addStructureSelection(formElement?: FormElement): void {
  getStructure().getTreeNode(formElement).addClass(getHelper().getDomElementClassName('selectedFormElement'));
}

/**
 * @todo deprecate, method is unused
 */
export function removeStructureSelection(formElement?: FormElement): void {
  getStructure().getTreeNode(formElement).removeClass(getHelper().getDomElementClassName('selectedFormElement'));
}

export function removeAllStructureSelections(): void {
  $(getHelper().getDomElementClassName('selectedFormElement', true), getStructure().getTreeDomElement())
    .removeClass(getHelper().getDomElementClassName('selectedFormElement'));
}

export function getStructureRootElement(): JQuery {
  return $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
    getHelper().getDomElementDataAttributeValue('structureRootElement')
  ]));
}

export function removeStructureRootElementSelection(): void {
  $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
    getHelper().getDomElementDataAttributeValue('structureRootContainer')
  ])).removeClass(getHelper().getDomElementClassName('selectedRootFormElement'));
}

export function addStructureRootElementSelection(): void {
  $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
    getHelper().getDomElementDataAttributeValue('structureRootContainer')
  ])).addClass(getHelper().getDomElementClassName('selectedRootFormElement'));
}

export function setStructureRootElementTitle(title?: string): void {
  if (getUtility().isUndefinedOrNull(title)) {
    title = $('<span></span>')
      .text((getRootFormElement().get('label') ? getRootFormElement().get('label') : getRootFormElement().get('identifier')))
      .text();
  }
  getStructureRootElement().text(title);
}

export function addStructureValidationResults(): void {
  getStructure().getAllTreeNodes()
    .removeClass(getHelper().getDomElementClassName('validationErrors'))
    .removeClass(getHelper().getDomElementClassName('validationChildHasErrors'));

  removeElementValidationErrorClass(getStructureRootElement());

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
      if (i === 0) {
        setElementValidationErrorClass(getStructureRootElement());
      } else {
        let validationElement = getStructure().getTreeNode(validationResults[i].formElementIdentifierPath);
        setElementValidationErrorClass(validationElement);

        const pathParts = validationResults[i].formElementIdentifierPath.split('/');
        while (pathParts.pop()) {
          validationElement = getStructure().getTreeNode(pathParts.join('/'));
          if ('object' === $.type(validationElement)) {
            setElementValidationErrorClass(validationElement, 'validationChildHasErrors');
          }
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

export function renderInspectorEditors(formElement?: FormElement | string, useFadeEffect?: boolean): void {
  if (getUtility().isUndefinedOrNull(useFadeEffect)) {
    useFadeEffect = true;
  }

  const render = (callback?: () => void): void => {
    getInspector().renderEditors(formElement, callback);
  };

  if (useFadeEffect) {
    getInspector().getInspectorDomElement().fadeOut('fast', function() {
      render(function() {
        getInspector().getInspectorDomElement().fadeIn('fast');
      });
    });
  } else {
    render();
  }
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
  getStage().getStagePanelDomElement().addClass(getHelper().getDomElementClassName('selectedStagePanel'));
}

export function removeStagePanelSelection(): void {
  getStage().getStagePanelDomElement().removeClass(getHelper().getDomElementClassName('selectedStagePanel'));
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
export function renderAbstractStageArea(useFadeEffect?: boolean, toolbarUseFadeEffect?: boolean): void {
  $(getHelper().getDomElementDataIdentifierSelector('structureSection'))
    .animate({
      'inset-inline-start': '0px'
    }, 'slow');
  $(getHelper().getDomElementDataIdentifierSelector('inspectorSection'))
    .animate({
      'inset-inline-end': '0px'
    }, 'slow');
  $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
    .animate({
      'margin-inline-start': configuration.panels.stage.marginLeft + 'px',
      'margin-inline-end': configuration.panels.stage.marginRight + 'px'
    }, 'slow');
  $(getHelper().getDomElementDataIdentifierSelector('stageContainerInner'))
    .animate({
      'max-width': configuration.panels.stage.maxWidthAbstract + 'px'
    }, 'slow');
  $(getHelper().getDomElementClassName('headerButtonBar', true))
    .animate({
      'margin-inline-start': configuration.panels.structure.width + 'px'
    }, 'slow');

  if (getUtility().isUndefinedOrNull(useFadeEffect)) {
    useFadeEffect = true;
  }

  if (getUtility().isUndefinedOrNull(toolbarUseFadeEffect)) {
    toolbarUseFadeEffect = true;
  }

  setButtonActive($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')));
  removeButtonActive($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModePreview')));

  const render = (callback: () => void): void => {
    $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
      .addClass(getHelper().getDomElementClassName('stageViewModeAbstract'))
      .removeClass(getHelper().getDomElementClassName('stageViewModePreview'));

    getStage().renderAbstractStageArea(undefined, callback);
  };

  const renderPostProcess = (): void => {
    const formElementTypeDefinition = getFormElementDefinition(getCurrentlySelectedFormElement(), undefined);
    getStage().getAllFormElementDomElements().hover(function(this: HTMLElement) {
      getStage().getAllFormElementDomElements().parent().removeClass(getHelper().getDomElementClassName('sortableHover'));
      if (
        $(this).parent().hasClass(getHelper().getDomElementClassName('formElementIsComposit'))
        && !$(this).parent().hasClass(getHelper().getDomElementClassName('formElementIsTopLevel'))
      ) {
        $(this).parent().addClass(getHelper().getDomElementClassName('sortableHover'));
      }
    });

    showComponent($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderNewPage')));
    if (
      formElementTypeDefinition._isTopLevelFormElement
      && !formElementTypeDefinition._isCompositeFormElement
      && !getFormEditorApp().isRootFormElementSelected()
    ) {
      hideComponent($(getHelper().getDomElementDataIdentifierSelector('buttonStageNewElementBottom')));
      hideComponent($(getHelper().getDomElementDataIdentifierSelector('stageNewElementRow')));
    } else {
      showComponent($(getHelper().getDomElementDataIdentifierSelector('buttonStageNewElementBottom')));
      showComponent($(getHelper().getDomElementDataIdentifierSelector('stageNewElementRow')));
    }

    refreshSelectedElementItemsBatch(toolbarUseFadeEffect);
    getPublisherSubscriber().publish('view/stage/abstract/render/postProcess');
  };

  if (useFadeEffect) {
    $(getHelper().getDomElementDataIdentifierSelector('stageSection')).fadeOut(400, function() {
      render(function() {
        getPublisherSubscriber().publish('view/stage/abstract/render/preProcess');
        $(getHelper().getDomElementDataIdentifierSelector('stageSection')).fadeIn(400);
        renderPostProcess();
        getPublisherSubscriber().publish('view/stage/abstract/render/postProcess');
      });
    });
  } else {
    render(function() {
      getPublisherSubscriber().publish('view/stage/abstract/render/preProcess');
      renderPostProcess();
      getPublisherSubscriber().publish('view/stage/abstract/render/postProcess');
    });
  }
}

/**
 * @publish view/stage/preview/render/postProcess
 */
export function renderPreviewStageArea(html: string): void {
  $(getHelper().getDomElementDataIdentifierSelector('structureSection'))
    .animate({
      'inset-inline-start': '-' + configuration.panels.structure.width + 'px'
    }, 'slow');
  $(getHelper().getDomElementDataIdentifierSelector('inspectorSection'))
    .animate({
      'inset-inline-end': '-' + configuration.panels.inspector.width + 'px'
    }, 'slow');
  $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
    .animate({
      'margin-inline-start': configuration.panels.stage.marginLeftCollapsed + 'px',
      'margin-inline-end': configuration.panels.stage.marginRightCollapsed + 'px'
    }, 'slow');
  $(getHelper().getDomElementDataIdentifierSelector('stageContainerInner'))
    .animate({
      'max-width': configuration.panels.stage.maxWidthPreview + 'px'
    }, 'slow');
  $(getHelper().getDomElementClassName('headerButtonBar', true))
    .animate({
      'margin-inline-start': configuration.panels.stage.marginLeftCollapsed + 'px'
    }, 'slow');

  setButtonActive($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModePreview')));
  removeButtonActive($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')));

  $(getHelper().getDomElementDataIdentifierSelector('stageSection')).fadeOut(400, function() {
    $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
      .addClass(getHelper().getDomElementClassName('stageViewModePreview'))
      .removeClass(getHelper().getDomElementClassName('stageViewModeAbstract'));

    hideComponent($(getHelper().getDomElementDataIdentifierSelector('buttonStageNewElementBottom')));
    hideComponent($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderNewPage')));

    getStage().renderPreviewStageArea(html);
    $(getHelper().getDomElementDataIdentifierSelector('stageSection')).fadeIn(400);
    getPublisherSubscriber().publish('view/stage/preview/render/postProcess');
  });
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
  if ('array' === $.type(collectionElementConfiguration.editors)) {
    for (let i = 0, len1 = collectionElementConfiguration.editors.length; i < len1; ++i) {
      if ('array' === $.type(collectionElementConfiguration.editors[i].additionalElementPropertyPaths)) {
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

export function refreshSelectedElementItemsBatch(toolbarUseFadeEffect?: boolean): void {
  if (getUtility().isUndefinedOrNull(toolbarUseFadeEffect)) {
    toolbarUseFadeEffect = true;
  }

  const formElementTypeDefinition = getFormElementDefinition(getCurrentlySelectedFormElement(), undefined);

  getStage().removeAllStageToolbars();
  removeAllStageElementSelectionsBatch();
  removeAllStructureSelections();

  if (!getFormEditorApp().isRootFormElementSelected()) {
    removeStructureRootElementSelection();
    addStructureSelection();

    const selectedElement = getStage().getAbstractViewFormElementDomElement();

    if (formElementTypeDefinition._isTopLevelFormElement) {
      addStagePanelSelection();
    } else {
      selectedElement.addClass(getHelper().getDomElementClassName('selectedFormElement'));
      getStage().createAndAddAbstractViewFormElementToolbar(selectedElement, undefined, toolbarUseFadeEffect);
    }

    getStage().getAllFormElementDomElements().parent().removeClass(getHelper().getDomElementClassName('selectedCompositFormElement'));
    if (!formElementTypeDefinition._isTopLevelFormElement && formElementTypeDefinition._isCompositeFormElement) {
      selectedElement.parent().addClass(getHelper().getDomElementClassName('selectedCompositFormElement'));
    }
  }
}

/**
 * @throws 1478651732
 * @throws 1478651733
 * @throws 1478651734
 */
export function selectPageBatch(pageIndex: number): void {
  assert('number' === $.type(pageIndex), 'Invalid parameter "pageIndex"', 1478651732);
  assert(pageIndex >= 0, 'Invalid parameter "pageIndex"', 1478651733);
  assert(pageIndex < getRootFormElement().get('renderables').length, 'Invalid parameter "pageIndex"', 1478651734);

  getFormEditorApp().setCurrentlySelectedFormElement(getRootFormElement().get('renderables')[pageIndex]);
  renewStructure();
  renderPagination();
  refreshSelectedElementItemsBatch();
  renderInspectorEditors();
}

export function removeAllStageElementSelectionsBatch(): void {
  getStage().getAllFormElementDomElements().removeClass(getHelper().getDomElementClassName('selectedFormElement'));
  removeStagePanelSelection();
  getStage().getAllFormElementDomElements().parent().removeClass(getHelper().getDomElementClassName('sortableHover'));
}

export function onViewReadyBatch(): void {
  $(getHelper().getDomElementDataIdentifierSelector('structureSection'))
    .css({
      width: configuration.panels.structure.width + 'px',
      insetStart: '-=' + configuration.panels.structure.width + 'px'
    });
  $(getHelper().getDomElementDataIdentifierSelector('inspectorSection'))
    .css({
      width: configuration.panels.inspector.width + 'px',
      insetEnd: '-=' + configuration.panels.inspector.width + 'px'
    });

  $(getHelper().getDomElementClassName('headerButtonBar', true))
    .css({
      'margin-inline-start': configuration.panels.structure.width + 'px'
    });

  $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
    .css({
      'margin-inline-start': configuration.panels.stage.marginLeft + 'px',
      'margin-inline-end': configuration.panels.stage.marginRight + 'px'
    });

  hideComponent($(getHelper().getDomElementDataIdentifierSelector('buttonStageNewElementBottom')));
  hideComponent($(getHelper().getDomElementDataIdentifierSelector('stageNewElementRow')));

  setStageHeadline();
  setStructureRootElementTitle();
  renderAbstractStageArea(false);
  renewStructure();
  addStructureRootElementSelection();
  renderInspectorEditors();
  renderPagination();

  hideComponent($(getHelper().getDomElementDataIdentifierSelector('moduleLoadingIndicator')));
  showComponent($(getHelper().getDomElementDataIdentifierSelector('moduleWrapper')));
  showComponent($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderSave')));
  showComponent($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderSettings')));
  showComponent($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderClose')));
  showComponent($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderNewPage')));
  showComponent($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')));
  showComponent($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')));
  setButtonActive($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')));
}

export function onAbstractViewDndStartBatch(
  draggedFormElementDomElement: HTMLElement | JQuery,
  draggedFormPlaceholderDomElement: HTMLElement | JQuery
): void {
  $(draggedFormPlaceholderDomElement).removeClass(getHelper().getDomElementClassName('sortableHover'));
}

export function onAbstractViewDndChangeBatch(
  placeholderDomElement: HTMLElement | JQuery,
  parentFormElementIdentifierPath: string,
  enclosingCompositeFormElement?: FormElement | string
): void {
  getStage().getAllFormElementDomElements().parent().removeClass(getHelper().getDomElementClassName('sortableHover'));
  if (enclosingCompositeFormElement) {
    getStage().getAbstractViewParentFormElementWithinDomElement(placeholderDomElement).parent().addClass(getHelper().getDomElementClassName('sortableHover'));
  }
}

/**
 * @throws 1472502237
 */
export function onAbstractViewDndUpdateBatch(
  movedDomElement: HTMLElement | JQuery,
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
    .attr(
      getHelper().getDomElementDataAttribute('elementIdentifier'),
      movedFormElement.get('__identifierPath')
    );
}

export function onStructureDndChangeBatch(
  placeholderDomElement: HTMLElement | JQuery,
  parentFormElementIdentifierPath: string,
  enclosingCompositeFormElement?: FormElement | string
): void {
  getStructure()
    .getAllTreeNodes()
    .parent()
    .removeClass(getHelper().getDomElementClassName('sortableHover'));

  getStage()
    .getAllFormElementDomElements()
    .parent()
    .removeClass(getHelper().getDomElementClassName('sortableHover'));

  if (enclosingCompositeFormElement) {
    getStructure()
      .getParentTreeNodeWithinDomElement(placeholderDomElement)
      .parent()
      .addClass(getHelper().getDomElementClassName('sortableHover'));

    getStage()
      .getAbstractViewFormElementDomElement(enclosingCompositeFormElement)
      .parent()
      .addClass(getHelper().getDomElementClassName('sortableHover'));
  }
}

/**
 * @throws 1479048646
 */
export function onStructureDndUpdateBatch(
  movedDomElement: HTMLElement | JQuery,
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
    .attr(
      getHelper().getDomElementDataAttribute('elementIdentifier'),
      movedFormElement.get('__identifierPath')
    );
}

/* *************************************************************
 * Misc
 * ************************************************************/

export function closeEditor(): void {
  document.location.href = $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderClose')).prop('href');
}

export function setElementValidationErrorClass(element: JQuery, classIdentifier?: string): void {
  if (getFormEditorApp().getUtility().isUndefinedOrNull(classIdentifier)) {
    element.addClass(getHelper().getDomElementClassName('validationErrors'));
  } else {
    element.addClass(getHelper().getDomElementClassName(classIdentifier));
  }
}

export function removeElementValidationErrorClass(element: JQuery, classIdentifier?: string): void {
  if (getFormEditorApp().getUtility().isUndefinedOrNull(classIdentifier)) {
    element.removeClass(getHelper().getDomElementClassName('validationErrors'));
  } else {
    element.removeClass(getHelper().getDomElementClassName(classIdentifier));
  }
}

export function showComponent(element: JQuery): void {
  element.removeClass(getHelper().getDomElementClassName('hidden')).show();
}

export function hideComponent(element: JQuery): void {
  element.addClass(getHelper().getDomElementClassName('hidden')).hide();
}

export function enableButton(buttonElement: JQuery): void {
  buttonElement.prop('disabled', false).removeClass(getHelper().getDomElementClassName('disabled'));
}

export function disableButton(buttonElement: JQuery): void {
  buttonElement.prop('disabled', 'disabled').addClass(getHelper().getDomElementClassName('disabled'));
}

export function setButtonActive(buttonElement: JQuery): void {
  buttonElement.addClass(getHelper().getDomElementClassName('active'));
}

export function removeButtonActive(buttonElement: JQuery): void {
  buttonElement.removeClass(getHelper().getDomElementClassName('active'));
}

export function showSaveButtonSpinnerIcon(): void {
  Icons.getIcon(getHelper().getDomElementDataAttributeValue('iconSaveSpinner'), Icons.sizes.small).then(function(markup) {
    $(getHelper().getDomElementDataIdentifierSelector('iconSave')).replaceWith($(markup));
  });
}

export function showSaveButtonSaveIcon(): void {
  Icons.getIcon(getHelper().getDomElementDataAttributeValue('iconSave'), Icons.sizes.small).then(function(markup) {
    $(getHelper().getDomElementDataIdentifierSelector('iconSaveSpinner')).replaceWith($(markup));
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
