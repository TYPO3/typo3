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
 * Module: TYPO3/CMS/Form/Backend/FormEditor
 */
define(['jquery',
  'TYPO3/CMS/Form/Backend/FormEditor/Core',
  'TYPO3/CMS/Backend/Notification'
], function($, core, Notification) {
  'use strict';

  /**
   * Return a static method named "getInstance".
   * Use this method to create the formeditor app.
   */
  return (function(_core, Notification) {

    /**
     * @private
     *
     * Hold the instance (Singleton Pattern)
     */
    var _formEditorInstance = null;

    /**
     * @public
     *
     * @param object _configuration
     * @param object _mediator
     * @param object _viewModel
     * @return object
     */
    function FormEditor(_configuration, _mediator, _viewModel) {

      /**
       * @private
       *
       * @var bool
       */
      var _isRunning = false;

      /**
       * @private
       *
       * @var bool
       */
      var _unsavedContent = false;

      /**
       * @private
       *
       * @var bool
       */
      var _previewMode = false;

      /**
       * @public
       *
       * @return object
       */
      function getPublisherSubscriber() {
        return _core.getPublisherSubscriber();
      };

      /**
       * @public
       *
       * @return void
       */
      function _saveApplicationState() {

        _getApplicationStateStack().addAndReset({
          formDefinition: _getApplicationStateStack().getCurrentState('formDefinition').clone(),
          currentlySelectedPageIndex: _getApplicationStateStack().getCurrentState('currentlySelectedPageIndex'),
          currentlySelectedFormElementIdentifierPath: _getApplicationStateStack().getCurrentState('currentlySelectedFormElementIdentifierPath')
        });
      };

      /**
       * @public
       *
       * @return void
       */
      function undoApplicationState() {
        _getApplicationStateStack().incrementCurrentStackPointer();
      };

      /**
       * @public
       *
       * @return void
       */
      function redoApplicationState() {
        _getApplicationStateStack().decrementCurrentStackPointer();
      };

      /**
       * @public
       *
       * @return int
       */
      function getMaximalApplicationStates() {
        return _getApplicationStateStack().getMaximalStackSize();
      };

      /**
       * @public
       *
       * @return int
       */
      function getCurrentApplicationStates() {
        return _getApplicationStateStack().getCurrentStackSize();
      };

      /**
       * @public
       *
       * @return int
       */
      function getCurrentApplicationStatePosition() {
        return _getApplicationStateStack().getCurrentStackPointer();
      };

      /**
       * @internal
       *
       * @return void
       * @throws 1519855175
       */
      function setFormDefinition(formDefinition) {
        assert('object' === $.type(formDefinition), 'Invalid parameter "formDefinition"', 1519855175);
        _getApplicationStateStack().setCurrentState('formDefinition', _getFactory().createFormElement(formDefinition, undefined, undefined, true));
      };

      /**
       * @public
       *
       * @param string type
       * @return object
       * @throws 1475378543
       */
      function getRunningAjaxRequest(type) {
        assert(getUtility().isNonEmptyString(type), 'Invalid parameter "type"', 1475378543);
        return _core.getRunningAjaxRequest(type);
      };

      /**
       * @public
       *
       * @return object
       */
      function getUtility() {
        return _core.getUtility();
      };

      /**
       * @public
       *
       * @param mixed test
       * @param string message
       * @param int messageCode
       * @return void
       */
      function assert(test, message, messageCode) {
        getUtility().assert(test, message, messageCode);
      };

      /**
       * @public
       *
       * @param string propertyPath
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param object formElement
       * @param boolean allowEmptyReturnValue
       * @return string
       */
      function buildPropertyPath(propertyPath, collectionElementIdentifier, collectionName, formElement, allowEmptyReturnValue) {
        if (getUtility().isUndefinedOrNull(formElement)) {
          formElement = getCurrentlySelectedFormElement();
        }
        formElement = _getRepository().findFormElement(formElement);
        return getUtility().buildPropertyPath(propertyPath, collectionElementIdentifier, collectionName, formElement, allowEmptyReturnValue);
      };

      /**
       * @public
       *
       * @param string validatorIdentifier
       * @param function func
       * @return void
       */
      function addPropertyValidationValidator(validatorIdentifier, func) {
        _getPropertyValidationService().addValidator(validatorIdentifier, func);
      };

      /**
       * @public
       *
       * @param string propertyPath
       * @return object
       */
      function validateCurrentlySelectedFormElementProperty(propertyPath) {
        return validateFormElementProperty(
          getCurrentlySelectedFormElement(),
          propertyPath
        );
      };

      /**
       * @public
       *
       * @param object formElement
       * @param string propertyPath
       * @return object
       */
      function validateFormElementProperty(formElement, propertyPath) {
        formElement = _getRepository().findFormElement(formElement);
        return _getPropertyValidationService().validateFormElementProperty(formElement, propertyPath);
      };

      /**
       * @public
       *
       * @param object formElement
       * @return object
       */
      function validateFormElement(formElement) {
        formElement = _getRepository().findFormElement(formElement);
        return _getPropertyValidationService().validateFormElement(formElement);
      };

      /**
       * @public
       *
       * @param object validationResults
       * @return boolean
       */
      function validationResultsHasErrors(validationResults) {
        return _getPropertyValidationService().validationResultsHasErrors(validationResults);
      };

      /**
       * @public
       *
       * @param object formElement
       * @param boolean returnAfterFirstMatch
       * @return object
       */
      function validateFormElementRecursive(formElement, returnAfterFirstMatch) {
        formElement = _getRepository().findFormElement(formElement);
        return _getPropertyValidationService().validateFormElementRecursive(formElement, returnAfterFirstMatch);
      };

      /**
       * @public
       *
       * @param bool unsavedContent
       * @return void
       * @throws 1475378544
       */
      function setUnsavedContent(unsavedContent) {
        assert('boolean' === $.type(unsavedContent), 'Invalid parameter "unsavedContent"', 1475378544);
        _unsavedContent = unsavedContent;
      };

      /**
       * @public
       *
       * @return boolean
       */
      function getUnsavedContent() {
        return _unsavedContent;
      };

      /**
       * @public
       *
       * @return object
       */
      function getRootFormElement() {
        return _getRepository().getRootFormElement();
      };

      /**
       * @public
       *
       * @return string
       */
      function getCurrentlySelectedFormElement() {
        return _getRepository().findFormElementByIdentifierPath(_getApplicationStateStack().getCurrentState('currentlySelectedFormElementIdentifierPath'));
      };

      /**
       * @public
       *
       * @param string|object formElement
       * @param boolean doNotRefreshCurrentlySelectedPageIndex
       * @return void
       * @publish core/currentlySelectedFormElementChanged
       */
      function setCurrentlySelectedFormElement(formElement, doNotRefreshCurrentlySelectedPageIndex) {
        doNotRefreshCurrentlySelectedPageIndex = !!doNotRefreshCurrentlySelectedPageIndex;

        formElement = _getRepository().findFormElement(formElement);
        _getApplicationStateStack().setCurrentState('currentlySelectedFormElementIdentifierPath', formElement.get('__identifierPath'));

        if (!doNotRefreshCurrentlySelectedPageIndex) {
          refreshCurrentlySelectedPageIndex();
        }
        getPublisherSubscriber().publish('core/currentlySelectedFormElementChanged', [formElement]);
      };

      /**
       * @public
       *
       * @param string identifierPath
       * @return object
       * @throws 1475378545
       */
      function getFormElementByIdentifierPath(identifierPath) {
        assert(getUtility().isNonEmptyString(identifierPath), 'Invalid parameter "identifierPath"', 1475378545);
        return _getRepository().findFormElementByIdentifierPath(identifierPath);
      };

      /**
       * @public
       *
       * @param string identifierPath
       * @return bool
       */
      function isFormElementIdentifierUsed(formElementIdentifier) {
        return _getRepository().isFormElementIdentifierUsed(formElementIdentifier);
      }

      /**
       * @public
       *
       * @param string formElementType
       * @param string|object referenceFormElement
       * @param boolean disablePublishersOnSet
       * @return object
       */
      function createAndAddFormElement(formElementType, referenceFormElement, disablePublishersOnSet) {
        var formElement;
        formElement = addFormElement(createFormElement(formElementType, disablePublishersOnSet), referenceFormElement, disablePublishersOnSet);
        formElement.set('renderables', formElement.get('renderables'));
        return formElement;
      };

      /**
       * @public
       *
       * @param object formElement
       * @param string|object referenceFormElement
       * @param boolean disablePublishersOnSet
       * @return object
       * @throws 1475434337
       */
      function addFormElement(formElement, referenceFormElement, disablePublishersOnSet) {
        _saveApplicationState();

        if (getUtility().isUndefinedOrNull(referenceFormElement)) {
          referenceFormElement = getCurrentlySelectedFormElement();
        }
        referenceFormElement = _getRepository().findFormElement(referenceFormElement);
        assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475434337);
        return _getRepository().addFormElement(formElement, referenceFormElement, true, disablePublishersOnSet);
      };

      /**
       * @public
       *
       * @param string formElementType
       * @param boolean disablePublishersOnSet
       * @return object
       * @throws 1475434336
       * @throws 1475435857
       */
      function createFormElement(formElementType, disablePublishersOnSet) {
        var formElementDefinition, identifier;
        assert(getUtility().isNonEmptyString(formElementType), 'Invalid parameter "formElementType"', 1475434336);

        identifier = _getRepository().getNextFreeFormElementIdentifier(formElementType);
        formElementDefinition = getFormElementDefinitionByType(formElementType);
        return _getFactory().createFormElement({
          type: formElementType,
          identifier: identifier,
          label: formElementDefinition['label'] || formElementType
        }, undefined, undefined, undefined, disablePublishersOnSet);
      };

      /**
       * @public
       *
       * @param string|object formElementToRemove
       * @param boolean disablePublishersOnSet
       * @return object
       */
      function removeFormElement(formElementToRemove, disablePublishersOnSet) {
        var parentFormElement;
        _saveApplicationState();

        formElementToRemove = _getRepository().findFormElement(formElementToRemove);
        parentFormElement = formElementToRemove.get('__parentRenderable');
        _getRepository().removeFormElement(formElementToRemove, true, disablePublishersOnSet);
        return parentFormElement;
      };

      /**
       * @public
       *
       * @param string|object formElementToMove
       * @param string position
       * @param string|object referenceFormElement
       * @param boolean disablePublishersOnSet
       * @return string
       * @throws 1475378551
       */
      function moveFormElement(formElementToMove, position, referenceFormElement, disablePublishersOnSet) {
        _saveApplicationState();

        formElementToMove = _getRepository().findFormElement(formElementToMove);
        referenceFormElement = _getRepository().findFormElement(referenceFormElement);

        assert('after' === position || 'before' === position || 'inside' === position, 'Invalid position "' + position + '"', 1475378551);

        formElementToMove = _getRepository().moveFormElement(formElementToMove, position, referenceFormElement, true);
        disablePublishersOnSet = !!disablePublishersOnSet;
        if (!disablePublishersOnSet) {
          formElementToMove.get('__parentRenderable').set('renderables', formElementToMove.get('__parentRenderable').get('renderables'));
        }
        return formElementToMove;
      };

      /**
       * @public
       *
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param string formElement
       * @return object (dereferenced)
       * @throws 1475378555
       * @throws 1475378556
       * @throws 1475446108
       */
      function getPropertyCollectionElementConfiguration(collectionElementIdentifier, collectionName, formElement) {
        var collection, collectionElement, formElementDefinition;
        if (getUtility().isUndefinedOrNull(formElement)) {
          formElement = getCurrentlySelectedFormElement();
        }
        formElement = _getRepository().findFormElement(formElement);

        assert(getUtility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475378555);
        assert(getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475378556);

        formElementDefinition = getFormElementDefinitionByType(formElement.get('type'));
        if (!getUtility().isUndefinedOrNull(formElementDefinition['propertyCollections'])) {
          collection = formElementDefinition['propertyCollections'][collectionName];
          assert(!getUtility().isUndefinedOrNull(collection), 'Invalid collection name "' + collectionName + '"', 1475446108);
          collectionElement = _getRepository().findCollectionElementByIdentifierPath(collectionElementIdentifier, collection);
          return $.extend(true, {}, collectionElement);
        } else {
          return {};
        }
      };

      /**
       * @public
       *
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param string formElement
       * @return int
       * @throws 1475378557
       * @throws 1475378558
       */
      function getIndexFromPropertyCollectionElement(collectionElementIdentifier, collectionName, formElement) {
        var indexFromPropertyCollectionElement;
        if (getUtility().isUndefinedOrNull(formElement)) {
          formElement = getCurrentlySelectedFormElement();
        }
        formElement = _getRepository().findFormElement(formElement);

        assert(getUtility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475378557);
        assert(getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475378558);

        indexFromPropertyCollectionElement = _getRepository().getIndexFromPropertyCollectionElementByIdentifier(
          collectionElementIdentifier,
          collectionName,
          formElement
        );

        return indexFromPropertyCollectionElement;
      };

      /**
       * @public
       *
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param object formElement
       * @param object collectionElementConfiguration
       * @param string referenceCollectionElementIdentifier
       * @return object
       */
      function createAndAddPropertyCollectionElement(collectionElementIdentifier, collectionName, formElement, collectionElementConfiguration, referenceCollectionElementIdentifier) {
        return addPropertyCollectionElement(createPropertyCollectionElement(collectionElementIdentifier, collectionName, collectionElementConfiguration), collectionName, formElement, referenceCollectionElementIdentifier);
      };

      /**
       * @public
       *
       * @param object collectionElement
       * @param string collectionName
       * @param string|object formElement
       * @param string referenceCollectionElementIdentifier
       * @return object
       * @throws 1475443300
       * @throws 1475443301
       */
      function addPropertyCollectionElement(collectionElement, collectionName, formElement, referenceCollectionElementIdentifier) {
        var collection;
        _saveApplicationState();

        if (getUtility().isUndefinedOrNull(formElement)) {
          formElement = getCurrentlySelectedFormElement();
        }
        formElement = _getRepository().findFormElement(formElement);

        assert('object' === $.type(collectionElement), 'Invalid parameter "collectionElement"', 1475443301);
        assert(getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475443300);

        if (getUtility().isUndefinedOrNull(referenceCollectionElementIdentifier)) {
          collection = formElement.get(collectionName);
          if ('array' === $.type(collection) && collection.length > 0) {
            referenceCollectionElementIdentifier = collection[collection.length - 1]['identifier'];
          }
        }

        return _getRepository().addPropertyCollectionElement(
          collectionElement,
          collectionName,
          formElement,
          referenceCollectionElementIdentifier,
          false
        );
      };

      /**
       * @public
       *
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param object collectionElementConfiguration
       * @return void
       * @throws 1475378559
       * @throws 1475378560
       */
      function createPropertyCollectionElement(collectionElementIdentifier, collectionName, collectionElementConfiguration) {
        assert(getUtility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475378559);
        assert(getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475378560);
        if ('object' !== $.type(collectionElementConfiguration)) {
          collectionElementConfiguration = {};
        }

        return _getFactory().createPropertyCollectionElement(collectionElementIdentifier, collectionElementConfiguration, collectionName);
      };

      /**
       * @public
       *
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param string formElement
       * @param bool disablePublishersOnSet
       * @return void
       * @throws 1475378561
       * @throws 1475378562
       */
      function removePropertyCollectionElement(collectionElementIdentifier, collectionName, formElement, disablePublishersOnSet) {
        _saveApplicationState();

        if (getUtility().isUndefinedOrNull(formElement)) {
          formElement = getCurrentlySelectedFormElement();
        }
        formElement = _getRepository().findFormElement(formElement);

        assert(getUtility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475378561);
        assert(getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475378562);

        _getRepository().removePropertyCollectionElementByIdentifier(
          formElement,
          collectionElementIdentifier,
          collectionName,
          true
        );

        disablePublishersOnSet = !!disablePublishersOnSet;
        if (!disablePublishersOnSet) {
          getPublisherSubscriber().publish('core/formElement/somePropertyChanged', ['__fakeProperty']);
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
       * @param boolean disablePublishersOnSet
       * @return string
       * @throws 1477404352
       * @throws 1477404353
       * @throws 1477404354
       * @throws 1477404355
       */
      function movePropertyCollectionElement(collectionElementToMove, position, referenceCollectionElement, collectionName, formElement, disablePublishersOnSet) {
        _saveApplicationState();

        formElement = _getRepository().findFormElement(formElement);

        assert('string' === $.type(collectionElementToMove), 'Invalid parameter "collectionElementToMove"', 1477404352);
        assert('string' === $.type(referenceCollectionElement), 'Invalid parameter "referenceCollectionElement"', 1477404353);
        assert('after' === position || 'before' === position, 'Invalid position "' + position + '"', 1477404354);
        assert(getUtility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1477404355);

        return _getRepository().movePropertyCollectionElement(collectionElementToMove, position, referenceCollectionElement, collectionName, formElement, disablePublishersOnSet);
      };

      /**
       * @public
       *
       * @param string elementType
       * @param string formElementDefinitionKey
       * @returnmixed
       * @throws 1475378563
       */
      function getFormElementDefinitionByType(elementType, formElementDefinitionKey) {
        var formElementDefinition;
        assert(getUtility().isNonEmptyString(elementType), 'Invalid parameter "elementType"', 1475378563);

        formElementDefinition = _getRepository().getFormEditorDefinition('formElements', elementType);

        if (!getUtility().isUndefinedOrNull(formElementDefinitionKey)) {
          formElementDefinition = formElementDefinition[formElementDefinitionKey];
        }

        if ('object' === $.type(formElementDefinition) || 'array' === $.type(formElementDefinition)) {
          return $.extend(true, {}, formElementDefinition);
        } else {
          return formElementDefinition;
        }
      };

      /**
       * @public
       *
       * @param object formElement
       * @param string formElementDefinitionKey
       * @return mixed
       */
      function getFormElementDefinition(formElement, formElementDefinitionKey) {
        formElement = _getRepository().findFormElement(formElement);
        return getFormElementDefinitionByType(formElement.get('type'), formElementDefinitionKey);
      };

      /**
       * @public
       *
       * @param string collectionName
       * @param string collectionElementIdentifier
       * @return mixed
       */
      function getFormEditorDefinition(definitionName, subject) {
        return _getRepository().getFormEditorDefinition(definitionName, subject);
      };

      /**
       * @public
       *
       * @param string validatorIdentifier
       * @return object (dereferenced)
       * @throws 1475672362
       */
      function getFormElementPropertyValidatorDefinition(validatorIdentifier) {
        var validatorDefinition;
        assert(getUtility().isNonEmptyString(validatorIdentifier), 'Invalid parameter "validatorIdentifier"', 1475672362);

        validatorDefinition = _getRepository().getFormEditorDefinition('formElementPropertyValidators', validatorIdentifier);
        return $.extend(true, {}, validatorDefinition);
      };

      /**
       * @public
       *
       * @return int
       */
      function getCurrentlySelectedPageIndex() {
        return _getApplicationStateStack().getCurrentState('currentlySelectedPageIndex');
      };

      /**
       * @public
       *
       * @return void
       */
      function refreshCurrentlySelectedPageIndex() {
        _getApplicationStateStack().setCurrentState('currentlySelectedPageIndex', getPageIndexFromFormElement(getCurrentlySelectedFormElement()));
      };

      /**
       * @public
       *
       * @return object
       * @throws 1477786068
       */
      function getCurrentlySelectedPage() {
        var currentPage;

        currentPage = _getRepository().getRootFormElement().get('renderables')[getCurrentlySelectedPageIndex()];
        assert('object' === $.type(currentPage), 'No page found', 1477786068);
        return currentPage;
      };

      /**
       * @public
       *
       * @return object
       */
      function getLastTopLevelElementOnCurrentPage() {
        var lastRenderable, renderables;

        renderables = getCurrentlySelectedPage().get('renderables');
        if (getUtility().isUndefinedOrNull(renderables)) {
          return undefined;
        }
        lastRenderable = renderables[renderables.length - 1];
        return lastRenderable;
      };

      /**
       * @public
       *
       * @param object
       * @return object
       */
      function getLastFormElementWithinParentFormElement(formElement) {
        var lastElement;

        formElement = _getRepository().findFormElement(formElement);
        if (formElement.get('__identifierPath') === getRootFormElement().get('__identifierPath')) {
          return formElement;
        }
        return formElement.get('__parentRenderable').get('renderables')[formElement.get('__parentRenderable').get('renderables').length - 1];
      };

      /**
       * @public
       *
       * @param object
       * @return int
       */
      function getPageIndexFromFormElement(formElement) {
        formElement = _getRepository().findFormElement(formElement);

        return _getRepository().getIndexForEnclosingCompositeFormElementWhichIsOnTopLevelForFormElement(
          formElement
        );
      };

      /**
       * @public
       *
       * @return void
       */
      function renderCurrentFormPage() {
        renderFormPage(getCurrentlySelectedPageIndex());
      };

      /**
       * @public
       *
       * @param int pageIndex
       * @return void
       * @throws 1475446442
       */
      function renderFormPage(pageIndex) {
        assert('number' === $.type(pageIndex), 'Invalid parameter "pageIndex"', 1475446442);
        _getDataBackend().renderFormDefinitionPage(pageIndex);
      };

      /**
       * @public
       *
       * @param object formElement
       * @return object|null
       */
      function findEnclosingCompositeFormElementWhichIsNotOnTopLevel(formElement) {
        return _getRepository().findEnclosingCompositeFormElementWhichIsNotOnTopLevel(
          _getRepository().findFormElement(formElement)
        );
      };

      /**
       * @public
       *
       * @param object formElement
       * @return object|null
       */
      function findEnclosingGridContainerFormElement(formElement) {
        return _getRepository().findEnclosingGridContainerFormElement(
          _getRepository().findFormElement(formElement)
        );
      };

      /**
       * @public
       *
       * @param object formElement
       * @return object|null
       */
      function findEnclosingGridRowFormElement(formElement) {
        return _getRepository().findEnclosingGridRowFormElement(
          _getRepository().findFormElement(formElement)
        );
      };

      /**
       * @public
       *
       * @return object
       */
      function getNonCompositeNonToplevelFormElements() {
        return _getRepository().getNonCompositeNonToplevelFormElements();
      };

      /**
       * @public
       *
       * @return boolean
       */
      function isRootFormElementSelected() {
        return (getCurrentlySelectedFormElement().get('__identifierPath') === getRootFormElement().get('__identifierPath'));
      };

      /**
       * @public
       *
       * @return object
       */
      function getViewModel() {
        return _viewModel;
      };

      /**
       * @public
       *
       * @return void
       */
      function saveFormDefinition() {
        _getDataBackend().saveFormDefinition();
      };

      /**
       * @private
       *
       * @return object
       */
      function _getDataBackend() {
        return _core.getDataBackend();
      };

      /**
       * @private
       *
       * @return object
       */
      function _getFactory() {
        return _core.getFactory();
      };

      /**
       * @private
       *
       * @return object
       */
      function _getRepository() {
        return _core.getRepository();
      };

      /**
       * @private
       *
       * @return object
       */
      function _getPropertyValidationService() {
        return _core.getPropertyValidationService();
      };

      /**
       * @public
       *
       * @return object
       */
      function _getApplicationStateStack() {
        return _core.getApplicationStateStack();
      };

      /**
       * @private
       *
       * @return void
       * @publish ajax/beforeSend
       * @publish ajax/complete
       */
      function _ajaxSetup() {
        $.ajaxSetup({
          beforeSend: function() {
            getPublisherSubscriber().publish('ajax/beforeSend');
          },
          complete: function() {
            getPublisherSubscriber().publish('ajax/complete');
          }
        });
      };

      /**
       * @private
       *
       * @param object endpoints
       * @param string prototypeName
       * @param string formPersistenceIdentifier
       * @return void
       * @throws 1475379748
       * @throws 1475379749
       * @throws 1475927876
       */
      function _dataBackendSetup(endpoints, prototypeName, formPersistenceIdentifier) {
        assert('object' === $.type(endpoints), 'Invalid parameter "endpoints"', 1475379748);
        assert(getUtility().isNonEmptyString(prototypeName), 'Invalid parameter "prototypeName"', 1475927876);
        assert(getUtility().isNonEmptyString(formPersistenceIdentifier), 'Invalid parameter "formPersistenceIdentifier"', 1475379749);

        _core.getDataBackend().setEndpoints(endpoints);
        _core.getDataBackend().setPrototypeName(prototypeName);
        _core.getDataBackend().setPersistenceIdentifier(formPersistenceIdentifier);
      };

      /**
       * @private
       *
       * @param object formEditorDefinitions
       * @return void
       * @throws 1475379750
       */
      function _repositorySetup(formEditorDefinitions) {
        assert('object' === $.type(formEditorDefinitions), 'Invalid parameter "formEditorDefinitions"', 1475379750);

        _getRepository().setFormEditorDefinitions(formEditorDefinitions);
      }

      /**
       * @private
       *
       * @param object additionalViewModelModules
       * @return void
       * @throws 1475492374
       */
      function _viewSetup(additionalViewModelModules) {
        assert('function' === $.type(_viewModel.bootstrap), 'The view model does not implement the method "bootstrap"', 1475492374);

        if (getUtility().isUndefinedOrNull(additionalViewModelModules)) {
          additionalViewModelModules = [];
        }
        _viewModel.bootstrap(_formEditorInstance, additionalViewModelModules);
      };

      /**
       * @private
       *
       * @return void
       * @throws 1475492032
       */
      function _mediatorSetup() {
        assert('function' === $.type(_mediator.bootstrap), 'The mediator does not implement the method "bootstrap"', 1475492032);
        _mediator.bootstrap(_formEditorInstance, _viewModel);
      };

      /**
       * @private
       *
       * @param object rootFormElement
       * @param int maximumUndoSteps
       * @return void
       * @throws 1475379751
       */
      function _applicationStateStackSetup(rootFormElement, maximumUndoSteps) {
        assert('object' === $.type(rootFormElement), 'Invalid parameter "rootFormElement"', 1475379751);

        if ('number' !== $.type(maximumUndoSteps)) {
          maximumUndoSteps = 10;
        }
        _getApplicationStateStack().setMaximalStackSize(maximumUndoSteps);

        _getApplicationStateStack().addAndReset({
          currentlySelectedPageIndex: 0,
          currentlySelectedFormElementIdentifierPath: rootFormElement['identifier']
        }, true);

        _getApplicationStateStack().setCurrentState('formDefinition', _getFactory().createFormElement(rootFormElement, undefined, undefined, true));
      };

      /**
       * @private
       *
       * @return void
       */
      function _bootstrap() {
        _configuration = _configuration || {};

        _mediatorSetup();
        _ajaxSetup();
        _dataBackendSetup(_configuration['endpoints'], _configuration['prototypeName'], _configuration['formPersistenceIdentifier']);
        _repositorySetup(_configuration['formEditorDefinitions']);
        _applicationStateStackSetup(_configuration['formDefinition'], _configuration['maximumUndoSteps']);
        setCurrentlySelectedFormElement(_getRepository().getRootFormElement());

        _viewSetup(_configuration['additionalViewModelModules']);
      };

      /**
       * @public
       *
       * @return TYPO3/CMS/Form/Backend/FormEditor
       * @throws 1473200696
       */
      function run() {
        if (_isRunning) {
          throw 'You can not run the app twice (1473200696)';
        }

        try {
          _bootstrap();
          _isRunning = true;
        } catch(error) {
          Notification.error(
            TYPO3.lang['formEditor.error.headline'],
            TYPO3.lang['formEditor.error.message']
            + "\r\n"
            + "\r\n"
            + TYPO3.lang['formEditor.error.technicalReason']
            + "\r\n"
            + error.message);
        }
        return this;
      };

      /**
       * Publish the public methods.
       * Implements the "Revealing Module Pattern".
       */
      return {
        getRootFormElement: getRootFormElement,

        createAndAddFormElement: createAndAddFormElement,
        createFormElement: createFormElement,
        addFormElement: addFormElement,
        moveFormElement: moveFormElement,
        removeFormElement: removeFormElement,

        getCurrentlySelectedFormElement: getCurrentlySelectedFormElement,
        setCurrentlySelectedFormElement: setCurrentlySelectedFormElement,

        getFormElementByIdentifierPath: getFormElementByIdentifierPath,
        isFormElementIdentifierUsed: isFormElementIdentifierUsed,

        createAndAddPropertyCollectionElement: createAndAddPropertyCollectionElement,
        createPropertyCollectionElement: createPropertyCollectionElement,
        addPropertyCollectionElement: addPropertyCollectionElement,
        removePropertyCollectionElement: removePropertyCollectionElement,
        movePropertyCollectionElement: movePropertyCollectionElement,
        getIndexFromPropertyCollectionElement: getIndexFromPropertyCollectionElement,
        getPropertyCollectionElementConfiguration: getPropertyCollectionElementConfiguration,

        saveFormDefinition: saveFormDefinition,
        renderCurrentFormPage: renderCurrentFormPage,
        renderFormPage: renderFormPage,

        getCurrentlySelectedPageIndex: getCurrentlySelectedPageIndex,
        refreshCurrentlySelectedPageIndex: refreshCurrentlySelectedPageIndex,
        getPageIndexFromFormElement: getPageIndexFromFormElement,
        getCurrentlySelectedPage: getCurrentlySelectedPage,
        getLastTopLevelElementOnCurrentPage: getLastTopLevelElementOnCurrentPage,
        findEnclosingCompositeFormElementWhichIsNotOnTopLevel: findEnclosingCompositeFormElementWhichIsNotOnTopLevel,
        findEnclosingGridContainerFormElement: findEnclosingGridContainerFormElement,
        findEnclosingGridRowFormElement: findEnclosingGridRowFormElement,
        isRootFormElementSelected: isRootFormElementSelected,
        getLastFormElementWithinParentFormElement: getLastFormElementWithinParentFormElement,
        getNonCompositeNonToplevelFormElements: getNonCompositeNonToplevelFormElements,

        getFormElementDefinitionByType: getFormElementDefinitionByType,
        getFormElementDefinition: getFormElementDefinition,
        getFormElementPropertyValidatorDefinition: getFormElementPropertyValidatorDefinition,
        getFormEditorDefinition: getFormEditorDefinition,

        getPublisherSubscriber: getPublisherSubscriber,
        getRunningAjaxRequest: getRunningAjaxRequest,

        setUnsavedContent: setUnsavedContent,
        getUnsavedContent: getUnsavedContent,

        addPropertyValidationValidator: addPropertyValidationValidator,
        validateFormElementProperty: validateFormElementProperty,
        validateCurrentlySelectedFormElementProperty: validateCurrentlySelectedFormElementProperty,
        validateFormElement: validateFormElement,
        validateFormElementRecursive: validateFormElementRecursive,
        validationResultsHasErrors: validationResultsHasErrors,

        getUtility: getUtility,
        assert: assert,
        buildPropertyPath: buildPropertyPath,

        getViewModel: getViewModel,
        undoApplicationState: undoApplicationState,
        redoApplicationState: redoApplicationState,
        getMaximalApplicationStates: getMaximalApplicationStates,
        getCurrentApplicationStates: getCurrentApplicationStates,
        getCurrentApplicationStatePosition: getCurrentApplicationStatePosition,
        setFormDefinition: setFormDefinition,

        run: run
      };
    };

    /**
     * Emulation of static methods
     */
    return {
      /**
       * @public
       * @static
       *
       * Implement the "Singleton Pattern".
       *
       * Return a singleton instance of a
       * "FormEditor" object.
       *
       * @param object configuration
       * @param object mediator
       * @param object viewModel
       * @return object
       */
      getInstance: function(configuration, mediator, viewModel) {
        if (_formEditorInstance === null) {
          _formEditorInstance = new FormEditor(configuration, mediator, viewModel);
        }
        return _formEditorInstance;
      }
    };
  })(core, Notification);
});
