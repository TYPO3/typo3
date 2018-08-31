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
 * Module: TYPO3/CMS/Form/Backend/FormEditor/ViewModel
 */
define(['jquery',
  'TYPO3/CMS/Form/Backend/FormEditor/TreeComponent',
  'TYPO3/CMS/Form/Backend/FormEditor/ModalsComponent',
  'TYPO3/CMS/Form/Backend/FormEditor/InspectorComponent',
  'TYPO3/CMS/Form/Backend/FormEditor/StageComponent',
  'TYPO3/CMS/Form/Backend/FormEditor/Helper',
  'TYPO3/CMS/Backend/Icons',
  'TYPO3/CMS/Backend/Notification'
], function($, TreeComponent, ModalsComponent, InspectorComponent, StageComponent, Helper, Icons, Notification) {
  'use strict';

  return (function($, TreeComponent, ModalsComponent, InspectorComponent, StageComponent, Helper, Icons, Notification) {

    /**
     * @private
     *
     * @var object
     */
    var _configuration = {
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
        iconSaveSpinner: 'spinner-circle-dark',
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

    /**
     * @private
     *
     * @var bool
     */
    var _previewMode = false;

    /**
     * @private
     *
     * @var object
     */
    var _formEditorApp = null;

    /**
     * @private
     *
     * @var object
     */
    var _structureComponent = null;

    /**
     * @private
     *
     * @var object
     */
    var _modalsComponent = null;

    /**
     * @private
     *
     * @var object
     */
    var _inspectorsComponent = null;

    /**
     * @private
     *
     * @var object
     */
    var _stageComponent = null;

    /* *************************************************************
     * Private Methodes
     * ************************************************************/

    /**
     * @private
     *
     * @return object
     */
    function getRootFormElement() {
      return getFormEditorApp().getRootFormElement();
    };

    /**
     * @private
     *
     * @param mixed test
     * @param string message
     * @param int messageCode
     * @return void
     */
    function assert(test, message, messageCode) {
      return getFormEditorApp().assert(test, message, messageCode);
    };

    /**
     * @private
     *
     * @return object
     */
    function getUtility() {
      return getFormEditorApp().getUtility();
    };

    /**
     * @private
     *
     * @return object
     */
    function getCurrentlySelectedFormElement() {
      return getFormEditorApp().getCurrentlySelectedFormElement();
    };


    /**
     * @private
     *
     * @return object
     */
    function getPublisherSubscriber() {
      return getFormEditorApp().getPublisherSubscriber();
    };

    /**
     * @private
     *
     * @return void
     */
    function _addPropertyValidators() {
      getFormEditorApp().addPropertyValidationValidator('NotEmpty', function(formElement, propertyPath) {
        if (formElement.get(propertyPath) === '') {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('NotEmpty')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('Integer', function(formElement, propertyPath) {
        if (!$.isNumeric(formElement.get(propertyPath))) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('Integer')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('IntegerOrEmpty', function(formElement, propertyPath) {
        if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
          return;
        }
        if (formElement.get(propertyPath).length > 0 && !$.isNumeric(formElement.get(propertyPath))) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('Integer')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('NaiveEmail', function(formElement, propertyPath) {
        if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
          return;
        }
        if (!formElement.get(propertyPath).match(/\S+@\S+\.\S+/)) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('NaiveEmail')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('NaiveEmailOrEmpty', function(formElement, propertyPath) {
        if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
          return;
        }
        if (formElement.get(propertyPath).length > 0 && !formElement.get(propertyPath).match(/\S+@\S+\.\S+/)) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('NaiveEmailOrEmpty')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('FormElementIdentifierWithinCurlyBracesInclusive', function(formElement, propertyPath) {
        var match, regex;

        if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
          return;
        }

        regex = /\{([a-z0-9-_]+)?\}/gi;
        match = regex.exec(formElement.get(propertyPath));
        if (match && ((match[1] && !getFormEditorApp().isFormElementIdentifierUsed(match[1])) || !match[1])) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('FormElementIdentifierWithinCurlyBracesInclusive')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('FormElementIdentifierWithinCurlyBracesExclusive', function(formElement, propertyPath) {
        var match, regex;

        if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
          return;
        }

        regex = /^\{([a-z0-9-_]+)?\}$/i;
        match = regex.exec(formElement.get(propertyPath));
        if (!match || ((match[1] && !getFormEditorApp().isFormElementIdentifierUsed(match[1])) || !match[1])) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('FormElementIdentifierWithinCurlyBracesInclusive')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('FileSize', function(formElement, propertyPath) {
        if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
          return;
        }
        if (!formElement.get(propertyPath).match(/^(\d*\.?\d+)(B|K|M|G)$/i)) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('FileSize')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('RFC3339FullDate', function(formElement, propertyPath) {
        if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
          return;
        }
        if (!formElement.get(propertyPath).match(/^([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])$/i)) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('RFC3339FullDate')['errorMessage'] || 'invalid value';
        }
      });

      getFormEditorApp().addPropertyValidationValidator('RFC3339FullDateOrEmpty', function(formElement, propertyPath) {
        if (getUtility().isUndefinedOrNull(formElement.get(propertyPath))) {
          return;
        }
        if (formElement.get(propertyPath).length > 0 && !formElement.get(propertyPath).match(/^([0-9]{4})-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])$/i)) {
          return getFormEditorApp().getFormElementPropertyValidatorDefinition('RFC3339FullDate')['errorMessage'] || 'invalid value';
        }
      });
    };

    /**
     * @private
     *
     * @param object additionalViewModelModules
     * @return void
     * @publish view/ready
     * @throws 1475425785
     */
    function _loadAdditionalModules(additionalViewModelModules) {
      var additionalViewModelModulesLength, converted, isLastElement, loadedAdditionalViewModelModules;

      if ('object' === $.type(additionalViewModelModules)) {
        converted = [];
        for (var key in additionalViewModelModules) {
          if (!additionalViewModelModules.hasOwnProperty(key)) {
            continue;
          }
          converted.push(additionalViewModelModules[key]);
        }
        additionalViewModelModules = converted;
      }

      if ('array' !== $.type(additionalViewModelModules)) {
        getPublisherSubscriber().publish('view/ready');
        return;
      }
      additionalViewModelModulesLength = additionalViewModelModules.length;

      if (additionalViewModelModulesLength > 0) {
        loadedAdditionalViewModelModules = 0;
        for (var i = 0; i < additionalViewModelModulesLength; ++i) {
          require([additionalViewModelModules[i]], function(additionalViewModelModule) {
            assert(
              'function' === $.type(additionalViewModelModule.bootstrap),
              'The module "' + additionalViewModelModules[i] + '" does not implement the method "bootstrap"',
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
    };

    /**
     * @private
     *
     * @return void
     * @throws 1478268638
     */
    function _helperSetup() {
      assert('function' === $.type(Helper.bootstrap),
        'The view model helper does not implement the method "bootstrap"',
        1478268638
      );

      Helper.bootstrap(getFormEditorApp());
    };

    /**
     * @private
     *
     * @return void
     * @throws 1478268639
     */
    function _structureComponentSetup() {
      assert(
        'function' === $.type(TreeComponent.bootstrap),
        'The structure component does not implement the method "bootstrap"',
        1478268639
      );

      _structureComponent = TreeComponent.bootstrap(
        getFormEditorApp(),
        $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
          getHelper().getDomElementDataAttributeValue('structure')
        ]))
      );

      $(getHelper().getDomElementDataIdentifierSelector('iconMailform'),
        $(getHelper().getDomElementDataIdentifierSelector('structureRootContainer'))
      ).tooltip({
        title: 'identifier: ' + getRootFormElement().get('identifier'),
        placement: 'right'
      });
    };

    /**
     * @private
     *
     * @return void
     * @throws 1478895106
     */
    function _modalsComponentSetup() {
      assert(
        'function' === $.type(ModalsComponent.bootstrap),
        'The modals component does not implement the method "bootstrap"',
        1478895106
      );
      _modalsComponent = ModalsComponent.bootstrap(getFormEditorApp());
    };

    /**
     * @private
     *
     * @return void
     * @throws 1478895106
     */
    function _inspectorsComponentSetup() {
      assert(
        'function' === $.type(InspectorComponent.bootstrap),
        'The inspector component does not implement the method "bootstrap"',
        1478895106
      );
      _inspectorsComponent = InspectorComponent.bootstrap(getFormEditorApp());
    };

    /**
     * @private
     *
     * @return void
     * @throws 1478986610
     */
    function _stageComponentSetup() {
      assert(
        'function' === $.type(InspectorComponent.bootstrap),
        'The stage component does not implement the method "bootstrap"',
        1478986610
      );
      _stageComponent = StageComponent.bootstrap(
        getFormEditorApp(),
        $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
          getHelper().getDomElementDataAttributeValue('stageArea')
        ]))
      );

      getStage().getStagePanelDomElement().on("click", function(e) {
        if (
          $(e.target).attr(getHelper().getDomElementDataAttribute('identifier')) === getHelper().getDomElementDataAttributeValue('stagePanelHeading')
          || $(e.target).attr(getHelper().getDomElementDataAttribute('identifier')) === getHelper().getDomElementDataAttributeValue('stageSection')
          || $(e.target).attr(getHelper().getDomElementDataAttribute('identifier')) === getHelper().getDomElementDataAttributeValue('stageArea')
        ) {
          selectPageBatch(getFormEditorApp().getCurrentlySelectedPageIndex());
        }
        getPublisherSubscriber().publish('view/stage/panel/clicked', []);
      });
    };

    /**
     * @private
     *
     * @return void
     * @publish view/header/button/save/clicked
     * @publish view/stage/abstract/button/newElement/clicked
     * @publish view/header/button/newPage/clicked
     * @publish view/structure/button/newPage/clicked
     * @publish view/header/button/close/clicked
     */
    function _buttonsSetup() {
      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderSave')).on("click", function(e) {
        getPublisherSubscriber().publish('view/header/button/save/clicked', []);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderSettings')).on('click', function(e) {
        getPublisherSubscriber().publish('view/header/formSettings/clicked', []);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonStageNewElementBottom')).on('click', function(e) {
        getPublisherSubscriber().publish(
          'view/stage/abstract/button/newElement/clicked', [
            'view/insertElements/perform/bottom'
          ]
        );
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderNewPage')).on('click', function(e) {
        getPublisherSubscriber().publish('view/header/button/newPage/clicked', ['view/insertPages/perform']);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonStructureNewPage')).on('click', function(e) {
        getPublisherSubscriber().publish('view/structure/button/newPage/clicked', ['view/insertPages/perform']);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderClose')).on('click', function(e) {
        if (!getFormEditorApp().getUnsavedContent()) {
          return;
        }
        e.preventDefault();
        getPublisherSubscriber().publish('view/header/button/close/clicked', []);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderUndo')).on('click', function(e) {
        getPublisherSubscriber().publish('view/undoButton/clicked', []);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderRedo')).on('click', function(e) {
        getPublisherSubscriber().publish('view/redoButton/clicked', []);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')).on('click', function(e) {
        getPublisherSubscriber().publish('view/viewModeButton/abstract/clicked', []);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModePreview')).on('click', function(e) {
        getPublisherSubscriber().publish('view/viewModeButton/preview/clicked', []);
      });

      $(getHelper().getDomElementDataIdentifierSelector('structureRootContainer')).on("click", function(e) {
        getPublisherSubscriber().publish('view/structure/root/selected');
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderPaginationNext')).on('click', function(e) {
        getPublisherSubscriber().publish('view/paginationNext/clicked', []);
      });

      $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderPaginationPrevious')).on('click', function(e) {
        getPublisherSubscriber().publish('view/paginationPrevious/clicked', []);
      });
    };

    /* *************************************************************
     * Public Methodes
     * ************************************************************/

    /**
     * @public
     *
     * @return object
     */
    function getFormEditorApp() {
      return _formEditorApp;
    };

    /**
     * @public
     *
     * @param object
     * @return object
     */
    function getHelper(configuration) {
      if (getUtility().isUndefinedOrNull(configuration)) {
        return Helper.setConfiguration(_configuration);
      }
      return Helper.setConfiguration(configuration);
    };

    /**
     * @public
     *
     * @param object formElement
     * @param string formElementDefinitionKey
     * @return mixed
     */
    function getFormElementDefinition(formElement, formElementDefinitionKey) {
      return getFormEditorApp().getFormElementDefinition(formElement, formElementDefinitionKey);
    };

    /**
     * @public
     *
     * @return object (derefernced)
     */
    function getConfiguration() {
      return $.extend(true, {}, _configuration);
    };

    /**
     * @public
     *
     * @return int
     */
    function getPreviewMode() {
      return _previewMode;
    };

    /**
     * @public
     *
     * @param bool
     * @return void
     */
    function setPreviewMode(previewMode) {
      _previewMode = !!previewMode;
    };

    /* *************************************************************
     * Structure
     * ************************************************************/

    /**
     * @public
     *
     * @return object
     */
    function getStructure() {
      return _structureComponent;
    };

    /**
     * @public
     *
     * @return void
     * @publish view/structure/renew/postProcess
     */
    function renewStructure() {
      getStructure().renew();
      getPublisherSubscriber().publish('view/structure/renew/postProcess');
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function addStructureSelection(formElement) {
      getStructure().getTreeNode(formElement).addClass(getHelper().getDomElementClassName('selectedFormElement'));
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function removeStructureSelection(formElement) {
      getStructure().getTreeNode(formElement).removeClass(getHelper().getDomElementClassName('selectedFormElement'));
    };

    /**
     * @public
     *
     * @return void
     */
    function removeAllStructureSelections() {
      $(getHelper().getDomElementClassName('selectedFormElement', true), getStructure().getTreeDomElement())
        .removeClass(getHelper().getDomElementClassName('selectedFormElement'));
    };

    /**
     * @public
     *
     * @return object
     */
    function getStructureRootElement() {
      return $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
        getHelper().getDomElementDataAttributeValue('structureRootElement')
      ]));
    };

    /**
     * @public
     *
     * @return void
     */
    function removeStructureRootElementSelection() {
      $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
        getHelper().getDomElementDataAttributeValue('structureRootContainer')
      ])).removeClass(getHelper().getDomElementClassName('selectedRootFormElement'));
    };

    /**
     * @public
     *
     * @return void
     */
    function addStructureRootElementSelection() {
      $(getHelper().getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [
        getHelper().getDomElementDataAttributeValue('structureRootContainer')
      ])).addClass(getHelper().getDomElementClassName('selectedRootFormElement'));
    };

    /**
     * @public
     *
     * @param string title
     * @return void
     */
    function setStructureRootElementTitle(title) {
      if (getUtility().isUndefinedOrNull(title)) {
        title = $('<span></span>')
          .text((getRootFormElement().get('label') ? getRootFormElement().get('label') : getRootFormElement().get('identifier')))
          .text();
      }
      getStructureRootElement().text(title);
    };

    /**
     * @public
     *
     * @return void
     */
    function addStructureValidationResults() {
      var validationResults;

      getStructure().getAllTreeNodes()
        .removeClass(getHelper().getDomElementClassName('validationErrors'))
        .removeClass(getHelper().getDomElementClassName('validationChildHasErrors'));

      removeElementValidationErrorClass(getStructureRootElement());

      validationResults = getFormEditorApp().validateFormElementRecursive(getRootFormElement());
      for (var i = 0, len = validationResults.length; i < len; ++i) {
        var hasError = false, pathParts, validationElement;
        for (var j = 0, len2 = validationResults[i]['validationResults'].length; j < len2; ++j) {
          if (
            validationResults[i]['validationResults'][j]['validationResults']
            && validationResults[i]['validationResults'][j]['validationResults'].length > 0
          ) {
            hasError = true;
            break;
          }
        }

        if (hasError) {
          if (i === 0) {
            setElementValidationErrorClass(getStructureRootElement());
          } else {
            validationElement = getStructure().getTreeNode(validationResults[i]['formElementIdentifierPath']);
            setElementValidationErrorClass(validationElement);

            pathParts = validationResults[i]['formElementIdentifierPath'].split('/');
            while (pathParts.pop()) {
              validationElement = getStructure().getTreeNode(pathParts.join('/'));
              if ('object' === $.type(validationElement)) {
                setElementValidationErrorClass(validationElement, 'validationChildHasErrors');
              }
            }
          }
        }
      }
    };

    /* *************************************************************
     * Modals
     * ************************************************************/

    /**
     * @public
     *
     * @return object
     */
    function getModals() {
      return _modalsComponent
    };

    /**
     * @public
     *
     * @param object formElement
     * @return void
     */
    function showRemoveFormElementModal(formElement) {
      if (getUtility().isUndefinedOrNull(formElement)) {
        formElement = getCurrentlySelectedFormElement();
      }
      getModals().showRemoveFormElementModal(formElement);
    };

    /**
     * @public
     *
     * @param string collectionElementIdentifier
     * @param string collectionName
     * @param object formElement
     * @return void
     */
    function showRemoveCollectionElementModal(collectionElementIdentifier, collectionName, formElement) {
      if (getUtility().isUndefinedOrNull(formElement)) {
        formElement = getCurrentlySelectedFormElement();
      }
      getModals().showRemoveCollectionElementModal(collectionElementIdentifier, collectionName, formElement);
    };

    /**
     * @public
     *
     * @return void
     */
    function showCloseConfirmationModal() {
      getModals().showCloseConfirmationModal();
    };

    /**
     * @public
     *
     * @param string targetEvent
     * @param object configuration
     * @return void
     */
    function showInsertElementsModal(targetEvent, configuration) {
      getModals().showInsertElementsModal(targetEvent, configuration);
    };

    /**
     * @public
     *
     * @param string targetEvent
     * @return void
     */
    function showInsertPagesModal(targetEvent) {
      getModals().showInsertPagesModal(targetEvent);
    };

    /**
     * @public
     *
     * @param bool
     * @return void
     */
    function showValidationErrorsModal() {
      var validationResults;
      validationResults = getFormEditorApp().validateFormElementRecursive(getRootFormElement());

      getModals().showValidationErrorsModal(validationResults);
    };

    /* *************************************************************
     * Inspector
     * ************************************************************/

    /**
     * @public
     *
     * @return object
     */
    function getInspector() {
      return _inspectorsComponent
    };

    /**
     * @public
     *
     * @param object
     * @param bool
     * @return void
     */
    function renderInspectorEditors(formElement, useFadeEffect) {
      var render;
      if (getUtility().isUndefinedOrNull(useFadeEffect)) {
        useFadeEffect = true;
      }

      /**
       * @private
       *
       * @param function
       * @return void
       */
      render = function(callback) {
        getInspector().renderEditors(formElement, callback);
      };

      if (!!useFadeEffect) {
        getInspector().getInspectorDomElement().fadeOut('fast', function() {
          render(function() {
            getInspector().getInspectorDomElement().fadeIn('fast');
          });
        });
      } else {
        render();
      }
    };

    /**
     * @public
     *
     * @param string
     * @param string
     * @return void
     */
    function renderInspectorCollectionElementEditors(collectionName, collectionElementIdentifier) {
      getInspector().renderCollectionElementEditors(collectionName, collectionElementIdentifier);
    };

    /**
     * @public
     *
     * @param string content
     * @return void
     */
    function setInspectorFormElementHeaderEditorContent(content) {
      getInspector().setFormElementHeaderEditorContent(content);
    };

    /* *************************************************************
     * Stage
     * ************************************************************/

    /**
     * @public
     *
     * @return object
     */
    function getStage() {
      return _stageComponent;
    };

    /**
     * @public
     *
     * @param string title
     * @return void
     */
    function setStageHeadline(title) {
      getStage().setStageHeadline(title);
    };

    /**
     * @public
     *
     * @return void
     */
    function addStagePanelSelection() {
      getStage().getStagePanelDomElement().addClass(getHelper().getDomElementClassName('selectedStagePanel'));
    };

    /**
     * @public
     *
     * @return void
     */
    function removeStagePanelSelection() {
      getStage().getStagePanelDomElement().removeClass(getHelper().getDomElementClassName('selectedStagePanel'));
    };

    /**
     * @public
     *
     * @return void
     */
    function renderPagination() {
      getStage().renderPagination();
    };

    /**
     * @public
     *
     * @return void
     */
    function renderUndoRedo() {
      getStage().renderUndoRedo();
    };

    /**
     * @public
     *
     * @param bool
     * @param bool
     * @return void
     * @publish view/stage/abstract/render/postProcess
     * @publish view/stage/abstract/render/preProcess
     */
    function renderAbstractStageArea(useFadeEffect, toolbarUseFadeEffect) {
      var render, renderPostProcess;

      $(getHelper().getDomElementDataIdentifierSelector('structureSection'))
        .animate({
          'left': '0px'
        }, 'slow');
      $(getHelper().getDomElementDataIdentifierSelector('inspectorSection'))
        .animate({
          'right': '0px'
        }, 'slow');
      $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
        .animate({
          'margin-left': _configuration['panels']['stage']['marginLeft'] + 'px',
          'margin-right': _configuration['panels']['stage']['marginRight'] + 'px'
        }, 'slow');
      $(getHelper().getDomElementDataIdentifierSelector('stageContainerInner'))
        .animate({
          'max-width': _configuration['panels']['stage']['maxWidthAbstract'] + 'px'
        }, 'slow');
      $(getHelper().getDomElementClassName('headerButtonBar', true))
        .animate({
          'margin-left': _configuration['panels']['structure']['width'] + 'px'
        }, 'slow');

      if (getUtility().isUndefinedOrNull(useFadeEffect)) {
        useFadeEffect = true;
      }

      if (getUtility().isUndefinedOrNull(toolbarUseFadeEffect)) {
        toolbarUseFadeEffect = true;
      }

      setButtonActive($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModeAbstract')));
      removeButtonActive($(getHelper().getDomElementDataIdentifierSelector('buttonHeaderViewModePreview')));

      /**
       * @private
       *
       * @param function
       * @return void
       */
      render = function(callback) {
        $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
          .addClass(getHelper().getDomElementClassName('stageViewModeAbstract'))
          .removeClass(getHelper().getDomElementClassName('stageViewModePreview'));

        getStage().renderAbstractStageArea(undefined, callback);
      };

      /**
       * @private
       *
       * @return void
       */
      renderPostProcess = function() {
        var formElementTypeDefinition;

        formElementTypeDefinition = getFormElementDefinition(getCurrentlySelectedFormElement());
        getStage().getAllFormElementDomElements().hover(function() {
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
          formElementTypeDefinition['_isTopLevelFormElement']
          && !formElementTypeDefinition['_isCompositeFormElement']
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
    };

    /**
     * @public
     *
     * @param string html
     * @return void
     * @publish view/stage/preview/render/postProcess
     */
    function renderPreviewStageArea(html) {
      $(getHelper().getDomElementDataIdentifierSelector('structureSection'))
        .animate({
          'left': '-=' + _configuration['panels']['structure']['width'] + 'px'
        }, 'slow');
      $(getHelper().getDomElementDataIdentifierSelector('inspectorSection'))
        .animate({
          'right': '-=' + _configuration['panels']['inspector']['width'] + 'px'
        }, 'slow');
      $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
        .animate({
          'margin-left': _configuration['panels']['stage']['marginLeftCollapsed'] + 'px',
          'margin-right': _configuration['panels']['stage']['marginRightCollapsed'] + 'px'
        }, 'slow');
      $(getHelper().getDomElementDataIdentifierSelector('stageContainerInner'))
        .animate({
          'max-width': _configuration['panels']['stage']['maxWidthPreview'] + 'px'
        }, 'slow');
      $(getHelper().getDomElementClassName('headerButtonBar', true))
        .animate({
          'margin-left': _configuration['panels']['stage']['marginLeftCollapsed'] + 'px'
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
    };

    /**
     * @public
     *
     * @return void
     */
    function addAbstractViewValidationResults() {
      var validationResults;

      validationResults = getFormEditorApp().validateFormElementRecursive(getRootFormElement());
      for (var i = 0, len = validationResults.length; i < len; ++i) {
        var hasError = false, validationElement;
        for (var j = 0, len2 = validationResults[i]['validationResults'].length; j < len2; ++j) {
          if (
            validationResults[i]['validationResults'][j]['validationResults']
            && validationResults[i]['validationResults'][j]['validationResults'].length > 0
          ) {
            hasError = true;
            break;
          }
        }

        if (hasError) {
          if (i > 0) {
            validationElement = getStage().getAbstractViewFormElementDomElement(validationResults[i]['formElementIdentifierPath']);
            setElementValidationErrorClass(validationElement);
          }
        }
      }
    };

    /* *************************************************************
     * Form element methods
     * ************************************************************/

    /**
     * @public
     *
     * @param string formElementType
     * @param string|object referenceFormElement
     * @param bool
     * @return object
     * @publish view/formElement/inserted
     */
    function createAndAddFormElement(formElementType, referenceFormElement, disablePublishersOnSet) {
      var newFormElement;

      newFormElement = getFormEditorApp().createAndAddFormElement(formElementType, referenceFormElement);
      if (!!!disablePublishersOnSet) {
        getPublisherSubscriber().publish('view/formElement/inserted', [newFormElement]);
      }
      return newFormElement;
    };

    /**
     * @public
     *
     * @param string|object formElementToMove
     * @param string position
     * @param string|object referenceFormElement
     * @param bool
     * @return object
     * @publish view/formElement/moved
     */
    function moveFormElement(formElementToMove, position, referenceFormElement, disablePublishersOnSet) {
      var movedFormElement;

      movedFormElement = getFormEditorApp().moveFormElement(formElementToMove, position, referenceFormElement, false);
      if (!!!disablePublishersOnSet) {
        getPublisherSubscriber().publish('view/formElement/moved', [movedFormElement]);
      }
      return movedFormElement;
    };

    /**
     * @public
     *
     * @param object formElement
     * @param bool
     * @return object
     * @publish view/formElement/removed
     */
    function removeFormElement(formElement, disablePublishersOnSet) {
      var parentFormElement;

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
        if (!!!disablePublishersOnSet) {
          getPublisherSubscriber().publish('view/formElement/removed', [parentFormElement]);
        }
      }
      return parentFormElement;
    };

    /**
     * @public
     *
     * @param string collectionElementIdentifier
     * @param string collectionName
     * @param object formElement
     * @param object collectionElementConfiguration
     * @param string referenceCollectionElementIdentifier
     * @param bool
     * @return void
     * @publish view/collectionElement/new/added
     */
    function createAndAddPropertyCollectionElement(collectionElementIdentifier, collectionName, formElement, collectionElementConfiguration, referenceCollectionElementIdentifier, disablePublishersOnSet) {
      getFormEditorApp().createAndAddPropertyCollectionElement(
        collectionElementIdentifier,
        collectionName,
        formElement,
        collectionElementConfiguration,
        referenceCollectionElementIdentifier
      );
      if (!!!disablePublishersOnSet) {
        getPublisherSubscriber().publish('view/collectionElement/new/added', [
          collectionElementIdentifier,
          collectionName,
          formElement,
          collectionElementConfiguration,
          referenceCollectionElementIdentifier
        ]);
      }
    };

    /**
     * @public
     *
     * @param string collectionElementToMove
     * @param string position
     * @param string referenceCollectionElement
     * @param string collectionName
     * @param object formElement
     * @param bool
     * @return void
     */
    function movePropertyCollectionElement(collectionElementToMove, position, referenceCollectionElement, collectionName, formElement, disablePublishersOnSet) {
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
      if (!!!disablePublishersOnSet) {
        getPublisherSubscriber().publish('view/collectionElement/moved', [
          collectionElementToMove,
          position,
          referenceCollectionElement,
          collectionName,
          formElement]
        );
      }
    };

    /**
     * @public
     *
     * @param string collectionElementIdentifier
     * @param string collectionName
     * @param object formElement
     * @param bool
     * @return void
     * @publish view/collectionElement/removed
     */
    function removePropertyCollectionElement(collectionElementIdentifier, collectionName, formElement, disablePublishersOnSet) {
      var collectionElementConfiguration, propertyData, propertyPath;

      getFormEditorApp().removePropertyCollectionElement(collectionElementIdentifier, collectionName, formElement);

      collectionElementConfiguration = getFormEditorApp().getPropertyCollectionElementConfiguration(
        collectionElementIdentifier,
        collectionName
      );
      if ('array' === $.type(collectionElementConfiguration['editors'])) {
        for (var i = 0, len1 = collectionElementConfiguration['editors'].length; i < len1; ++i) {
          if ('array' === $.type(collectionElementConfiguration['editors'][i]['additionalElementPropertyPaths'])) {
            for (var j = 0, len2 = collectionElementConfiguration['editors'][i]['additionalElementPropertyPaths'].length; j < len2; ++j) {
              getCurrentlySelectedFormElement().unset(collectionElementConfiguration['editors'][i]['additionalElementPropertyPaths'][j], true);
            }
          } else if (collectionElementConfiguration['editors'][i]['identifier'] === 'validationErrorMessage') {
            propertyPath = getFormEditorApp().buildPropertyPath(
              collectionElementConfiguration['editors'][i]['propertyPath']
            );
            propertyData = getCurrentlySelectedFormElement().get(propertyPath);
            if (!getUtility().isUndefinedOrNull(propertyData)) {
              for (var j = 0, len2 = collectionElementConfiguration['editors'][i]['errorCodes'].length; j < len2; ++j) {
                for (var k = 0, len3 = propertyData.length; k < len3; ++k) {
                  if (parseInt(collectionElementConfiguration['editors'][i]['errorCodes'][j]) === parseInt(propertyData[k]['code'])) {
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

      if (!!!disablePublishersOnSet) {
        getPublisherSubscriber().publish('view/collectionElement/removed', [
          collectionElementIdentifier,
          collectionName,
          formElement]
        );
      }
    };

    /* *************************************************************
     * Batch methodes
     * ************************************************************/

    /**
     * @public
     *
     * @param bool
     * @return void
     */
    function refreshSelectedElementItemsBatch(toolbarUseFadeEffect) {
      var formElementTypeDefinition, selectedElement;

      if (getUtility().isUndefinedOrNull(toolbarUseFadeEffect)) {
        toolbarUseFadeEffect = true;
      }

      formElementTypeDefinition = getFormElementDefinition(getCurrentlySelectedFormElement());

      getStage().removeAllStageToolbars();
      removeAllStageElementSelectionsBatch();
      removeAllStructureSelections();

      if (!getFormEditorApp().isRootFormElementSelected()) {
        removeStructureRootElementSelection();
        addStructureSelection();

        selectedElement = getStage().getAbstractViewFormElementDomElement();

        if (formElementTypeDefinition['_isTopLevelFormElement']) {
          addStagePanelSelection();
        } else {
          selectedElement.addClass(getHelper().getDomElementClassName('selectedFormElement'));
          getStage().createAndAddAbstractViewFormElementToolbar(selectedElement, undefined, toolbarUseFadeEffect);
        }

        getStage().getAllFormElementDomElements().parent().removeClass(getHelper().getDomElementClassName('selectedCompositFormElement'));
        if (!formElementTypeDefinition['_isTopLevelFormElement'] && formElementTypeDefinition['_isCompositeFormElement']) {
          selectedElement.parent().addClass(getHelper().getDomElementClassName('selectedCompositFormElement'));
        }
      }
    };

    /**
     * @public
     *
     * @param int
     * @return void
     * @throws 1478651732
     * @throws 1478651733
     * @throws 1478651734
     */
    function selectPageBatch(pageIndex) {
      assert('number' === $.type(pageIndex), 'Invalid parameter "pageIndex"', 1478651732);
      assert(pageIndex >= 0, 'Invalid parameter "pageIndex"', 1478651733);
      assert(pageIndex < getRootFormElement().get('renderables').length, 'Invalid parameter "pageIndex"', 1478651734);

      getFormEditorApp().setCurrentlySelectedFormElement(getRootFormElement().get('renderables')[pageIndex]);
      renewStructure();
      renderPagination()
      refreshSelectedElementItemsBatch();
      renderInspectorEditors();
    };

    /**
     * @public
     *
     * @return void
     */
    function removeAllStageElementSelectionsBatch() {
      getStage().getAllFormElementDomElements().removeClass(getHelper().getDomElementClassName('selectedFormElement'));
      removeStagePanelSelection();
      getStage().getAllFormElementDomElements().parent().removeClass(getHelper().getDomElementClassName('sortableHover'));
    };

    /**
     * @public
     *
     * @return void
     */
    function onViewReadyBatch() {
      $(getHelper().getDomElementDataIdentifierSelector('structureSection'))
        .css({
          width: _configuration['panels']['structure']['width'] + 'px',
          left: '-=' + _configuration['panels']['structure']['width'] + 'px'
        });
      $(getHelper().getDomElementDataIdentifierSelector('inspectorSection'))
        .css({
          width: _configuration['panels']['inspector']['width'] + 'px',
          right: '-=' + _configuration['panels']['inspector']['width'] + 'px'
        });

      $(getHelper().getDomElementClassName('headerButtonBar', true))
        .css({
          'margin-left': _configuration['panels']['structure']['width'] + 'px'
        });

      $(getHelper().getDomElementDataIdentifierSelector('stageContainer'))
        .css({
          'margin-left': _configuration['panels']['stage']['marginLeft'] + 'px',
          'margin-right': _configuration['panels']['stage']['marginRight'] + 'px'
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
    };

    /**
     * @public
     *
     * @param object
     * @param object
     * @return void
     */
    function onAbstractViewDndStartBatch(draggedFormElementDomElement, draggedFormPlaceholderDomElement) {
      draggedFormPlaceholderDomElement.removeClass(getHelper().getDomElementClassName('sortableHover'));
    };

    /**
     * @public
     *
     * @param object
     * @param string
     * @param object
     * @return void
     */
    function onAbstractViewDndChangeBatch(placeholderDomElement, parentFormElementIdentifierPath, enclosingCompositeFormElement) {
      getStage().getAllFormElementDomElements().parent().removeClass(getHelper().getDomElementClassName('sortableHover'));
      if (enclosingCompositeFormElement) {
        getStage().getAbstractViewParentFormElementWithinDomElement(placeholderDomElement).parent().addClass(getHelper().getDomElementClassName('sortableHover'));
      }
    };

    /**
     * @public
     *
     * @param object
     * @param string
     * @param string
     * @param string
     * @return void
     * @throws 1472502237
     */
    function onAbstractViewDndUpdateBatch(movedDomElement, movedFormElementIdentifierPath, previousFormElementIdentifierPath, nextFormElementIdentifierPath) {
      var movedFormElement, parentFormElementIdentifierPath;
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
    };

    /**
     * @public
     *
     * @param object
     * @param string
     * @param object
     * @return void
     */
    function onStructureDndChangeBatch(placeholderDomElement, parentFormElementIdentifierPath, enclosingCompositeFormElement) {
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
    };

    /**
     * @public
     *
     * @param object
     * @param string
     * @param string
     * @param string
     * @return void
     * @throws 1479048646
     */
    function onStructureDndUpdateBatch(movedDomElement, movedFormElementIdentifierPath, previousFormElementIdentifierPath, nextFormElementIdentifierPath) {
      var movedFormElement, parentFormElementIdentifierPath;
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
    };

    /* *************************************************************
     * Misc
     * ************************************************************/

    /**
     * @public
     *
     * @return void
     */
    function closeEditor() {
      document.location.href = $(getHelper().getDomElementDataIdentifierSelector('buttonHeaderClose')).prop('href');
    };

    /**
     * @public
     *
     * @param object
     * @param string
     * @return void
     */
    function setElementValidationErrorClass(element, classIdentifier) {
      if (getFormEditorApp().getUtility().isUndefinedOrNull(classIdentifier)) {
        element.addClass(getHelper().getDomElementClassName('validationErrors'));
      } else {
        element.addClass(getHelper().getDomElementClassName(classIdentifier));
      }
    };

    /**
     * @public
     *
     * @param object
     * @param string
     * @return void
     */
    function removeElementValidationErrorClass(element, classIdentifier) {
      if (getFormEditorApp().getUtility().isUndefinedOrNull(classIdentifier)) {
        element.removeClass(getHelper().getDomElementClassName('validationErrors'));
      } else {
        element.removeClass(getHelper().getDomElementClassName(classIdentifier));
      }
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function showComponent(element) {
      element.removeClass(getHelper().getDomElementClassName('hidden')).show();
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function hideComponent(element) {
      element.addClass(getHelper().getDomElementClassName('hidden')).hide();
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function enableButton(buttonElement) {
      buttonElement.prop('disabled', false).removeClass(getHelper().getDomElementClassName('disabled'));
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function disableButton(buttonElement) {
      buttonElement.prop('disabled', 'disabled').addClass(getHelper().getDomElementClassName('disabled'));
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function setButtonActive(buttonElement) {
      buttonElement.addClass(getHelper().getDomElementClassName('active'));
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function removeButtonActive(buttonElement) {
      buttonElement.removeClass(getHelper().getDomElementClassName('active'));
    };

    /**
     * @public
     *
     * @return void
     */
    function showSaveButtonSpinnerIcon() {
      Icons.getIcon(getHelper().getDomElementDataAttributeValue('iconSaveSpinner'), Icons.sizes.small).done(function(markup) {
        $(getHelper().getDomElementDataIdentifierSelector('iconSave')).replaceWith($(markup));
      });
    };

    /**
     * @public
     *
     * @return void
     */
    function showSaveButtonSaveIcon() {
      Icons.getIcon(getHelper().getDomElementDataAttributeValue('iconSave'), Icons.sizes.small).done(function(markup) {
        $(getHelper().getDomElementDataIdentifierSelector('iconSaveSpinner')).replaceWith($(markup));
      });
    };

    /**
     * @public
     *
     * @return void
     */
    function showSaveSuccessMessage() {
      Notification.success(
        getFormElementDefinition(getRootFormElement(), 'saveSuccessFlashMessageTitle'),
        getFormElementDefinition(getRootFormElement(), 'saveSuccessFlashMessageMessage'),
        2
      );
    };

    /**
     * @public
     *
     * @return void
     */
    function showSaveErrorMessage(response) {
      Notification.error(
        getFormElementDefinition(getRootFormElement(), 'saveErrorFlashMessageTitle'),
        getFormElementDefinition(getRootFormElement(), 'saveErrorFlashMessageMessage') +
        " " +
        response.message
      );
    };

    /**
     * @public
     *
     * @param string
     * @param string
     * @return void
     */
    function showErrorFlashMessage(title, message) {
      Notification.error(title, message, 2);
    };

    /**
     * @public
     *
     * @param object formEditorApp
     * @param object additionalViewModelModules
     * @return void
     */
    function bootstrap(formEditorApp, additionalViewModelModules) {
      _formEditorApp = formEditorApp;

      _helperSetup();
      _structureComponentSetup();
      _modalsComponentSetup();
      _inspectorsComponentSetup();
      _stageComponentSetup();
      _buttonsSetup();
      _addPropertyValidators();
      _loadAdditionalModules(additionalViewModelModules);
    };

    /**
     * Publish the public methods.
     * Implements the "Revealing Module Pattern".
     */
    return {
      addAbstractViewValidationResults: addAbstractViewValidationResults,
      addStagePanelSelection: addStagePanelSelection,
      addStructureRootElementSelection: addStructureRootElementSelection,
      addStructureSelection: addStructureSelection,
      addStructureValidationResults: addStructureValidationResults,
      bootstrap: bootstrap,
      closeEditor: closeEditor,
      createAndAddFormElement: createAndAddFormElement,
      createAndAddPropertyCollectionElement: createAndAddPropertyCollectionElement,
      disableButton: disableButton,
      enableButton: enableButton,
      getConfiguration: getConfiguration,
      getFormEditorApp: getFormEditorApp,
      getFormElementDefinition: getFormElementDefinition,
      getHelper: getHelper,
      getInspector: getInspector,
      getModals: getModals,
      getPreviewMode: getPreviewMode,
      getStage: getStage,
      getStructure: getStructure,
      getStructureRootElement: getStructureRootElement,
      hideComponent: hideComponent,
      moveFormElement: moveFormElement,
      movePropertyCollectionElement: movePropertyCollectionElement,
      onAbstractViewDndChangeBatch: onAbstractViewDndChangeBatch,
      onAbstractViewDndStartBatch: onAbstractViewDndStartBatch,
      onAbstractViewDndUpdateBatch: onAbstractViewDndUpdateBatch,
      onStructureDndChangeBatch: onStructureDndChangeBatch,
      onStructureDndUpdateBatch: onStructureDndUpdateBatch,
      onViewReadyBatch: onViewReadyBatch,
      refreshSelectedElementItemsBatch: refreshSelectedElementItemsBatch,
      removeAllStageElementSelectionsBatch: removeAllStageElementSelectionsBatch,
      removeAllStructureSelections: removeAllStructureSelections,
      removeButtonActive: removeButtonActive,
      removeElementValidationErrorClass: removeElementValidationErrorClass,
      removeFormElement: removeFormElement,
      removePropertyCollectionElement: removePropertyCollectionElement,
      removeStagePanelSelection: removeStagePanelSelection,
      removeStructureRootElementSelection: removeStructureRootElementSelection,
      removeStructureSelection: removeStructureSelection,
      renderAbstractStageArea: renderAbstractStageArea,
      renderInspectorEditors: renderInspectorEditors,
      renderInspectorCollectionElementEditors: renderInspectorCollectionElementEditors,
      renderPagination: renderPagination,
      renderPreviewStageArea: renderPreviewStageArea,
      renewStructure: renewStructure,
      renderUndoRedo: renderUndoRedo,
      selectPageBatch: selectPageBatch,
      setButtonActive: setButtonActive,
      setElementValidationErrorClass: setElementValidationErrorClass,
      setInspectorFormElementHeaderEditorContent: setInspectorFormElementHeaderEditorContent,
      setPreviewMode: setPreviewMode,
      setStageHeadline: setStageHeadline,
      setStructureRootElementTitle: setStructureRootElementTitle,
      showCloseConfirmationModal: showCloseConfirmationModal,
      showComponent: showComponent,
      showErrorFlashMessage: showErrorFlashMessage,
      showInsertElementsModal: showInsertElementsModal,
      showInsertPagesModal: showInsertPagesModal,
      showRemoveFormElementModal: showRemoveFormElementModal,
      showRemoveCollectionElementModal: showRemoveCollectionElementModal,
      showSaveButtonSaveIcon: showSaveButtonSaveIcon,
      showSaveButtonSpinnerIcon: showSaveButtonSpinnerIcon,
      showSaveSuccessMessage: showSaveSuccessMessage,
      showSaveErrorMessage: showSaveErrorMessage,
      showValidationErrorsModal: showValidationErrorsModal
    };
  })($, TreeComponent, ModalsComponent, InspectorComponent, StageComponent, Helper, Icons, Notification);
});
