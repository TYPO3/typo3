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
 * Module: TYPO3/CMS/Form/Backend/FormEditor/Core
 */
define(['jquery'], function($) {
  'use strict';

  return (function($) {

    /**
     * @private
     *
     * @var object
     */
    var _dataBackendEndpoints = {};

    /**
     * @private
     *
     * @var string
     */
    var _dataBackendPrototypeName = null;

    /**
     * @private
     *
     * @var string
     */
    var _dataBackendPersistenceIdentifier = null;

    /**
     * @private
     *
     * @var object
     */
    var _publisherSubscriberTopics = {};

    /**
     * @private
     *
     * @var int
     */
    var _publisherSubscriberUid = -1;

    /**
     * @private
     *
     * @var object
     */
    var _repositoryFormEditorDefinitions = {};

    /**
     * @private
     *
     * @var object
     */
    var _runningAjaxRequests = [];

    /**
     * @private
     *
     * @var object
     */
    var _propertyValidationServiceValidators = {};

    /**
     * @private
     *
     * @var int
     */
    var _applicationStateStackSize = 10;

    /**
     * @private
     *
     * @var int
     */
    var _applicationStateStackPointer = 0;

    /**
     * @private
     *
     * @var object
     */
    var _applicationStateStack = [];

    /**
     * @public
     *
     * @return object
     */
    function utility() {

      /**
       * @public
       *
       * @param mixed test
       * @param string message
       * @param int messageCode
       * @return void
       */
      function assert(test, message, messageCode) {
        if ('function' === $.type(test)) {
          test = (test() !== false);
        }
        if (!test) {
          message = message || "Assertion failed";
          if (messageCode) {
            message = message + ' (' + messageCode + ')';
          }
          if ('undefined' !== typeof Error) {
            throw new Error(message);
          }
          throw message;
        }
      };

      /**
       * @public
       *
       * @param mixed value
       * @return bool
       */
      function isUndefinedOrNull(value) {
        return ('undefined' === $.type(value) || 'null' === $.type(value));
      };

      /**
       * @public
       *
       * @param mixed value
       * @return bool
       */
      function isNonEmptyString(value) {
        return ('string' === $.type(value) && value.length > 0);
      };

      /**
       * @public
       *
       * @param mixed value
       * @return bool
       */
      function canBeInterpretedAsInteger(value) {
        if (value === '' || 'object' === $.type(value) || 'array' === $.type(value) || isUndefinedOrNull(value)) {
            return false;
        }

        return (value * 1).toString() === value.toString() && value.toString().indexOf('.') === -1;
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
       * @throws 1475412569
       * @throws 1475412570
       * @throws 1475415988
       * @throws 1475663210
       */
      function buildPropertyPath(propertyPath, collectionElementIdentifier, collectionName, formElement, allowEmptyReturnValue) {
        var newPropertyPath = '';

        allowEmptyReturnValue = !!allowEmptyReturnValue;
        if (isNonEmptyString(collectionElementIdentifier) || isNonEmptyString(collectionName)) {
          assert(isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475412569);
          assert(isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475412570);
          newPropertyPath = collectionName + '.' + repository().getIndexFromPropertyCollectionElementByIdentifier(collectionElementIdentifier, collectionName, formElement);
        } else {
          newPropertyPath = '';
        }

        if (!isUndefinedOrNull(propertyPath)) {
          assert(isNonEmptyString(propertyPath), 'Invalid parameter "propertyPath"', 1475415988);
          if (isNonEmptyString(newPropertyPath)) {
            newPropertyPath = newPropertyPath + '.' + propertyPath;
          } else {
            newPropertyPath = propertyPath;
          }
        }

        if (!allowEmptyReturnValue) {
          assert(isNonEmptyString(newPropertyPath), 'The property path could not be resolved', 1475663210);
        }
        return newPropertyPath;
      };

      /**
       * @public
       *
       * @param object formElement
       * @return object
       * @throws 1475377782
       */
      function convertToSimpleObject(formElement) {
        var childFormElements, simpleObject, objectData;
        assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475377782);

        simpleObject = {};
        objectData = ('function' === $.type(formElement.getObjectData)) ? formElement.getObjectData() : formElement;
        childFormElements = objectData['renderables'];
        delete objectData['renderables'];

        for (var key in objectData) {
          if (!objectData.hasOwnProperty(key)) {
            continue;
          }
          var value = objectData[key];
          if (key.match(/^__/)) {
            continue;
          }

          if ('object' === $.type(value)) {
            simpleObject[key] = convertToSimpleObject(value);
          } else if ('function' !== $.type(value) && 'undefined' !== $.type(value)) {
            simpleObject[key] = value;
          }
        }

        if ('array' === $.type(childFormElements)) {
          simpleObject['renderables'] = [];
          for (var i = 0, len = childFormElements.length; i < len; ++i) {
            simpleObject['renderables'].push(convertToSimpleObject(childFormElements[i]));
          }
        }

        return simpleObject;
      };

      /**
       * Publish the public methods.
       */
      return {
        assert: assert,
        convertToSimpleObject: convertToSimpleObject,
        isNonEmptyString: isNonEmptyString,
        isUndefinedOrNull: isUndefinedOrNull,
        buildPropertyPath: buildPropertyPath,
        canBeInterpretedAsInteger: canBeInterpretedAsInteger
      };
    };

    /**
     * @public
     *
     * @return object
     */
    function propertyValidationService() {

      /**
       * @public
       *
       * @param object formElement
       * @param object validators
       * @param string propertyPath
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param object configuration
       * @return void
       * @throws 1475661025
       * @throws 1475661026
       * @throws 1479238074
       */
      function addValidatorIdentifiersToFormElementProperty(formElement, validators, propertyPath, collectionElementIdentifier, collectionName, configuration) {
        var formElementIdentifierPath, propertyPath, propertyValidationServiceRegisteredValidators;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475661025);
        utility().assert('array' === $.type(validators), 'Invalid parameter "validators"', 1475661026);
        utility().assert('array' === $.type(validators), 'Invalid parameter "validators"', 1479238074);

        formElementIdentifierPath = formElement.get('__identifierPath');
        propertyPath = utility().buildPropertyPath(propertyPath, collectionElementIdentifier, collectionName, formElement);

        propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
        if (utility().isUndefinedOrNull(propertyValidationServiceRegisteredValidators[formElementIdentifierPath])) {
          propertyValidationServiceRegisteredValidators[formElementIdentifierPath] = {};
        }
        if (utility().isUndefinedOrNull(propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath])) {
          propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath] = {
            validators: [],
            configuration: configuration
          };
        }
        for (var i = 0, len = validators.length; i < len; ++i) {
          if (propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath]['validators'].indexOf(validators[i]) === -1) {
            propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath]['validators'].push(validators[i]);
          }
        }
        getApplicationStateStack().setCurrentState('propertyValidationServiceRegisteredValidators', propertyValidationServiceRegisteredValidators);
      };

      /**
       * @public
       *
       * @param object formElement
       * @param string propertyPath
       * @return void
       * @throws 1475700618
       * @throws 1475706896
       */
      function removeValidatorIdentifiersFromFormElementProperty(formElement, propertyPath) {
        var formElementIdentifierPath, propertyValidationServiceRegisteredValidators,
          registeredValidators;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475700618);
        utility().assert(utility().isNonEmptyString(propertyPath), 'Invalid parameter "propertyPath"', 1475706896);

        formElementIdentifierPath = formElement.get('__identifierPath');

        registeredValidators = {};
        propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
        for (var registeredPropertyPath in propertyValidationServiceRegisteredValidators[formElementIdentifierPath]) {
          if (
            !propertyValidationServiceRegisteredValidators[formElementIdentifierPath].hasOwnProperty(registeredPropertyPath)
            || registeredPropertyPath.indexOf(propertyPath) > -1
          ) {
            continue;
          }
          registeredValidators[registeredPropertyPath] = propertyValidationServiceRegisteredValidators[formElementIdentifierPath][registeredPropertyPath];
        }
        propertyValidationServiceRegisteredValidators[formElementIdentifierPath] = registeredValidators;
        getApplicationStateStack().setCurrentState('propertyValidationServiceRegisteredValidators', propertyValidationServiceRegisteredValidators);
      };

      /**
       * @public
       *
       * @param string|object formElement
       * @return void
       * @throws 1475668189
       */
      function removeAllValidatorIdentifiersFromFormElement(formElement) {
        var propertyValidationServiceRegisteredValidators, registeredValidators;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475668189);

        registeredValidators = {};
        propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
        for (var formElementIdentifierPath in propertyValidationServiceRegisteredValidators) {
          if (
            !propertyValidationServiceRegisteredValidators.hasOwnProperty(formElementIdentifierPath)
            || formElementIdentifierPath === formElement.get('__identifierPath')
            || formElementIdentifierPath.indexOf(formElement.get('__identifierPath') + '/') > -1
          ) {
            continue;
          }
          registeredValidators[formElementIdentifierPath] = propertyValidationServiceRegisteredValidators[formElementIdentifierPath];
        }
        getApplicationStateStack().setCurrentState('propertyValidationServiceRegisteredValidators', registeredValidators);
      };

      /**
       * @public
       *
       * @param string validatorIdentifier
       * @param function func
       * @return void
       * @throws 1475669143
       * @throws 1475669144
       * @throws 1475669145
       */
      function addValidator(validatorIdentifier, func) {
        utility().assert(utility().isNonEmptyString(validatorIdentifier), 'Invalid parameter "validatorIdentifier"', 1475669143);
        utility().assert('function' === $.type(func), 'Invalid parameter "func"', 1475669144);
        utility().assert('function' !== $.type(_propertyValidationServiceValidators[validatorIdentifier]), 'The validator "' + validatorIdentifier + '" is already registered', 1475669145);

        _propertyValidationServiceValidators[validatorIdentifier] = func;
      };

      /**
       * @public
       *
       * @param object formElement
       * @param string propertyPath
       * @param string errorMessage
       * @return object
       * @throws 1475676517
       * @throws 1475676518
       */
      function validateFormElementProperty(formElement, propertyPath) {
        var configuration, formElementIdentifierPath, propertyValidationServiceRegisteredValidators,
          validationResults;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475676517);
        utility().assert(utility().isNonEmptyString(propertyPath), 'Invalid parameter "propertyPath"', 1475676518);

        formElementIdentifierPath = formElement.get('__identifierPath');

        validationResults = [];
        propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
        configuration = {
          propertyValidatorsMode: 'AND'
        };

        if (
          !utility().isUndefinedOrNull(propertyValidationServiceRegisteredValidators[formElementIdentifierPath])
          && 'object' === $.type(propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath])
          && 'array' === $.type(propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath]['validators'])
        ) {
          configuration = propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath]['configuration'];
          for (var i = 0, len = propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath]['validators'].length; i < len; ++i) {
            var validatorIdentifier, validationResult;

            validatorIdentifier = propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath]['validators'][i];
            if ('function' !== $.type(_propertyValidationServiceValidators[validatorIdentifier])) {
              continue;
            }
            validationResult = _propertyValidationServiceValidators[validatorIdentifier](formElement, propertyPath);

            if (utility().isNonEmptyString(validationResult)) {
              validationResults.push(validationResult);
            }
          }
        }

        if (
          validationResults.length > 0
          && configuration['propertyValidatorsMode'] === 'OR'
          && validationResults.length !== propertyValidationServiceRegisteredValidators[formElementIdentifierPath][propertyPath]['validators'].length
        ) {
          return [];
        }

        return validationResults;
      };

      /**
       * @public
       *
       * @param object formElement
       * @return object
       * @throws 1475749668
       */
      function validateFormElement(formElement) {
        var formElementIdentifierPath, propertyValidationServiceRegisteredValidators,
          validationResults;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475749668);

        formElementIdentifierPath = formElement.get('__identifierPath');

        validationResults = [];
        propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
        if (!utility().isUndefinedOrNull(propertyValidationServiceRegisteredValidators[formElementIdentifierPath])) {
          for (var registeredPropertyPath in propertyValidationServiceRegisteredValidators[formElementIdentifierPath]) {
            var validationResult;
            if (!propertyValidationServiceRegisteredValidators[formElementIdentifierPath].hasOwnProperty(registeredPropertyPath)) {
              continue;
            }
            validationResult = {
              propertyPath: registeredPropertyPath,
              validationResults: validateFormElementProperty(formElement, registeredPropertyPath)
            };
            validationResults.push(validationResult);
          }
        }
        return validationResults;
      };

      /**
       * @public
       *
       * @param array validationResults
       * @return bool
       * @throws 1478613477
       */
      function validationResultsHasErrors(validationResults) {
        utility().assert('array' === $.type(validationResults), 'Invalid parameter "validationResults"', 1478613477);

        for (var i = 0, len = validationResults.length; i < len; ++i) {
          for (var j = 0, len2 = validationResults[i]['validationResults'].length; j < len2; ++j) {
            if (
              validationResults[i]['validationResults'][j]['validationResults']
              && validationResults[i]['validationResults'][j]['validationResults'].length > 0
            ) {
              return true;
            }
          }
        }
        return false;
      };

      /**
       * @public
       *
       * @param object formElement
       * @param boolean returnAfterFirstMatch
       * @param object validationResults
       * @return object
       * @throws 1475749668
       */
      function validateFormElementRecursive(formElement, returnAfterFirstMatch, validationResults) {
        var formElements, validationResult, validationResults;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475756764);
        returnAfterFirstMatch = !!returnAfterFirstMatch;

        validationResults = validationResults || [];
        validationResult = {
          formElementIdentifierPath: formElement.get('__identifierPath'),
          validationResults: validateFormElement(formElement)
        };
        validationResults.push(validationResult);

        if (returnAfterFirstMatch && validationResultsHasErrors(validationResults)) {
          return validationResults;
        }

        formElements = formElement.get('renderables');
        if ('array' === $.type(formElements)) {
          for (var i = 0, len = formElements.length; i < len; ++i) {
            validateFormElementRecursive(formElements[i], returnAfterFirstMatch, validationResults);
            if (returnAfterFirstMatch && validationResultsHasErrors(validationResults)) {
              return validationResults;
            }
          }
        }

        return validationResults;
      }

      /**
       * @public
       *
       * @param object formElement
       * @return void
       * @throws 1475707334
       */
      function addValidatorIdentifiersFromFormElementPropertyCollections(formElement) {
        var formElementTypeDefinition;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475707334);

        formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));

        if (!utility().isUndefinedOrNull(formElementTypeDefinition['propertyCollections'])) {
          for (var collectionName in formElementTypeDefinition['propertyCollections']) {
            if (
              !formElementTypeDefinition['propertyCollections'].hasOwnProperty(collectionName)
              || 'array' !== $.type(formElementTypeDefinition['propertyCollections'][collectionName])
            ) {
              continue;
            }
            for (var i = 0, len1 = formElementTypeDefinition['propertyCollections'][collectionName].length; i < len1; ++i) {
              if (
                'array' !== $.type(formElementTypeDefinition['propertyCollections'][collectionName][i]['editors'])
                || repository().getIndexFromPropertyCollectionElementByIdentifier(formElementTypeDefinition['propertyCollections'][collectionName][i]['identifier'], collectionName, formElement) === -1
              ) {
                continue;
              }
              for (var j = 0, len2 = formElementTypeDefinition['propertyCollections'][collectionName][i]['editors'].length; j < len2; ++j) {
                var configuration = {};

                if ('array' !== $.type(formElementTypeDefinition['propertyCollections'][collectionName][i]['editors'][j]['propertyValidators'])) {
                  continue;
                }

                if (
                  !utility().isUndefinedOrNull(formElementTypeDefinition['propertyCollections'][collectionName][i]['editors'][j]['propertyValidatorsMode'])
                  && formElementTypeDefinition['propertyCollections'][collectionName][i]['editors'][j]['propertyValidatorsMode'] === 'OR'
                ) {
                  configuration['propertyValidatorsMode'] = 'OR';
                } else {
                  configuration['propertyValidatorsMode'] = 'AND';
                }
                addValidatorIdentifiersToFormElementProperty(
                  formElement,
                  formElementTypeDefinition['propertyCollections'][collectionName][i]['editors'][j]['propertyValidators'],
                  formElementTypeDefinition['propertyCollections'][collectionName][i]['editors'][j]['propertyPath'],
                  formElementTypeDefinition['propertyCollections'][collectionName][i]['identifier'],
                  collectionName,
                  configuration
                );
              }
            }
          }
        }
      };

      /**
       * Publish the public methods.
       */
      return {
        addValidatorIdentifiersToFormElementProperty: addValidatorIdentifiersToFormElementProperty,
        removeValidatorIdentifiersFromFormElementProperty: removeValidatorIdentifiersFromFormElementProperty,
        removeAllValidatorIdentifiersFromFormElement: removeAllValidatorIdentifiersFromFormElement,
        validateFormElementProperty: validateFormElementProperty,
        validateFormElement: validateFormElement,
        validateFormElementRecursive: validateFormElementRecursive,
        validationResultsHasErrors: validationResultsHasErrors,
        addValidator: addValidator,
        addValidatorIdentifiersFromFormElementPropertyCollections: addValidatorIdentifiersFromFormElementPropertyCollections
      };
    };

    /**
     * @public
     *
     * @param string ajaxRequestIdentifier
     * @return object|null
     * @throws 1475358064
     */
    function getRunningAjaxRequest(ajaxRequestIdentifier) {
      utility().assert(utility().isNonEmptyString(ajaxRequestIdentifier), 'Invalid parameter "ajaxRequestIdentifier"', 1475358064);
      return _runningAjaxRequests[ajaxRequestIdentifier] || null;
    };

    /**
     * @public
     *
     * Implements the "Publish/Subscribe Pattern"
     *
     * @return object
     * @credits Addy Osmani https://addyosmani.com/resources/essentialjsdesignpatterns/book/#highlighter_634280
     */
    function publisherSubscriber() {

      /**
       * @public
       *
       * @param string topic
       * @param mixed args
       * @return void
       * @throws 1475358066
       */
      function publish(topic, args) {
        utility().assert(utility().isNonEmptyString(topic), 'Invalid parameter "topic"', 1475358066);
        if (utility().isUndefinedOrNull(_publisherSubscriberTopics[topic])) {
          return;
        }

        for (var i = 0, len = _publisherSubscriberTopics[topic].length; i < len; ++i) {
          _publisherSubscriberTopics[topic][i].func(topic, args);
        }
      };

      /**
       * @public
       *
       * @param string topic
       * @param function func
       * @return string
       * @throws 1475358067
       */
      function subscribe(topic, func) {
        utility().assert(utility().isNonEmptyString(topic), 'Invalid parameter "topic"', 1475358067);
        utility().assert('function' === $.type(func), 'Invalid parameter "func"', 1475411986);

        if (utility().isUndefinedOrNull(_publisherSubscriberTopics[topic])) {
          _publisherSubscriberTopics[topic] = [];
        }

        var token = (++_publisherSubscriberUid).toString();
        _publisherSubscriberTopics[topic].push({
          token: token,
          func: func
        });
        return token;
      };

      /**
       * @public
       *
       * @param string token
       * @return null|string
       * @throws 1475358068
       */
      function unsubscribe(token) {
        utility().assert(utility().isNonEmptyString(token), 'Invalid parameter "token"', 1475358068);

        for (var key in _publisherSubscriberTopics) {
          if (!_publisherSubscriberTopics.hasOwnProperty(key)) {
            continue;
          }
          for (var i = 0, len = _publisherSubscriberTopics[key].length; i < len; ++i) {
            if (_publisherSubscriberTopics[key][i].token === token) {
              _publisherSubscriberTopics[key].splice(i, 1);
              return token;
            }
          }
        }
        return null;
      };

      /**
       * Publish the public methods.
       */
      return {
        publish: publish,
        subscribe: subscribe,
        unsubscribe: unsubscribe
      };
    };

    /**
     * @private
     *
     * @param object modelToExtend
     * @param object modelExtension
     * @param string pathPrefix
     * @return void
     * @throws 1474640022
     * @throws 1475358069
     * @throws 1475358070
     * @publish core/formElement/somePropertyChanged
     */
    function extendModel(modelToExtend, modelExtension, pathPrefix, disablePublishersOnSet) {
      utility().assert('object' === $.type(modelToExtend), 'Invalid parameter "modelToExtend"', 1475358069);
      utility().assert('object' === $.type(modelExtension) || 'array' === $.type(modelExtension), 'Invalid parameter "modelExtension"', 1475358070);

      disablePublishersOnSet = !!disablePublishersOnSet;
      pathPrefix = pathPrefix || '';

      if ($.isEmptyObject(modelExtension)) {
        utility().assert('' !== pathPrefix, 'Empty path is not allowed', 1474640022);
        modelToExtend.on(pathPrefix, 'core/formElement/somePropertyChanged');
        modelToExtend.set(pathPrefix, modelExtension, disablePublishersOnSet);
      } else {
        for (var key in modelExtension) {
          if (!modelExtension.hasOwnProperty(key)) {
            continue;
          }
          var path = (pathPrefix === '') ? key : pathPrefix + '.' + key;

          modelToExtend.on(path, 'core/formElement/somePropertyChanged');

          if ('object' === $.type(modelExtension[key]) || 'array' === $.type(modelExtension[key])) {
            extendModel(modelToExtend, modelExtension[key], path, disablePublishersOnSet);
          } else if (pathPrefix === 'properties.options') {
            modelToExtend.set(pathPrefix, modelExtension, disablePublishersOnSet);
          } else {
            modelToExtend.set(path, modelExtension[key], disablePublishersOnSet);
          }
        }
      }
    };

    /**
     * @private
     *
     * @param object modelExtension
     * @return object
     */
    function createModel(modelExtension) {
      var newModel;

      modelExtension = modelExtension || {};

      function M() {

        /**
         * @private
         */
        var _objectData = {};

        /**
         * @private
         */
        var _publisherTopics = {};

        /**
         * @public
         *
         * @param string key
         * @return mixed|undefined
         * @throws 1475361755
         */
        function get(key) {
          var firstPartOfPath, obj;
          utility().assert(utility().isNonEmptyString(key), 'Invalid parameter "key"', 1475361755);

          obj = _objectData;
          while (key.indexOf('.') > 0) {
            firstPartOfPath = key.slice(0, key.indexOf('.'));
            key = key.slice(firstPartOfPath.length + 1);
            if (!obj.hasOwnProperty(firstPartOfPath)) {
              return undefined;
            }
            obj = obj[firstPartOfPath];
          }

          return obj[key];
        };

        /**
         * @public
         *
         * @param string key
         * @param mixed value
         * @param bool disablePublishersOnSet
         * @return void
         * @throws 1475361756
         * @publish mixed
         */
        function set(key, value, disablePublishersOnSet) {
          var obj, oldValue, path, firstPartOfPath, nextPartOfPath, index;
          utility().assert(utility().isNonEmptyString(key), 'Invalid parameter "key"', 1475361756);
          disablePublishersOnSet = !!disablePublishersOnSet;

          oldValue = get(key);
          obj = _objectData;
          path = key;

          while (path.indexOf('.') > 0) {
            firstPartOfPath = path.slice(0, path.indexOf('.'));
            path = path.slice(firstPartOfPath.length + 1);

            if ($.isNumeric(firstPartOfPath)) {
              firstPartOfPath = parseInt(firstPartOfPath);
            }

            index = path.indexOf('.');
            nextPartOfPath = index === -1 ? path : path.slice(0, index);

            // initialize objects case they are undefined by looking up the type
            // of the next path segment, the target type is guessed(!), thus e.g.
            // "key" results in having an object, "123" results in having an array
            if ('undefined' === $.type(obj[firstPartOfPath])) {
              if ($.isNumeric(nextPartOfPath)) {
                obj[firstPartOfPath] = [];
              } else {
                obj[firstPartOfPath] = {};
              }
            // in case the previous guess was wrong, the initialized array
            // is converted to an object when a non-numeric path segment is found
            } else if (false === $.isNumeric(nextPartOfPath) && 'array' === $.type(obj[firstPartOfPath])) {
              obj[firstPartOfPath] = obj[firstPartOfPath].reduce(
                function(converted, item, itemIndex) {
                  converted[itemIndex] = item;
                  return converted;
                },
                {}
              );
            }
            obj = obj[firstPartOfPath];
          }
          obj[path] = value;

          if (!utility().isUndefinedOrNull(_publisherTopics[key]) && !disablePublishersOnSet) {
            for (var i = 0, len = _publisherTopics[key].length; i < len; ++i) {
              publisherSubscriber().publish(_publisherTopics[key][i], [key, value, oldValue, _objectData['__identifierPath']]);
            }
          }
        };

        /**
         * @public
         *
         * @param string key
         * @param bool disablePublishersOnSet
         * @return void
         * @throws 1489321637
         * @throws 1489319753
         * @publish mixed
         */
        function unset(key, disablePublishersOnSet) {
          var obj, oldValue, parentPropertyData, parentPropertyPath, propertyToRemove;
          utility().assert(utility().isNonEmptyString(key), 'Invalid parameter "key"', 1489321637);
          disablePublishersOnSet = !!disablePublishersOnSet;

          oldValue = get(key);

          if (key.indexOf('.') > 0) {
            parentPropertyPath = key.split('.');
            propertyToRemove = parentPropertyPath.pop();
            parentPropertyPath = parentPropertyPath.join('.');
            parentPropertyData = get(parentPropertyPath);
            delete parentPropertyData[propertyToRemove];
          } else {
            assert(false, 'remove toplevel properties is not supported', 1489319753);
          }

          if (!utility().isUndefinedOrNull(_publisherTopics[key]) && !disablePublishersOnSet) {
            for (var i = 0, len = _publisherTopics[key].length; i < len; ++i) {
              publisherSubscriber().publish(_publisherTopics[key][i], [key, undefined, oldValue, _objectData['__identifierPath']]);
            }
          }
        };

        /**
         * @public
         *
         * @param string key
         * @param string topicName
         * @return void
         * @throws 1475361757
         * @throws 1475361758
         */
        function on(key, topicName) {
          utility().assert(utility().isNonEmptyString(key), 'Invalid parameter "key"', 1475361757);
          utility().assert(utility().isNonEmptyString(topicName), 'Invalid parameter "topicName"', 1475361758);

          if ('array' !== $.type(_publisherTopics[key])) {
            _publisherTopics[key] = [];
          }
          if (_publisherTopics[key].indexOf(topicName) === -1) {
            _publisherTopics[key].push(topicName);
          }
        };

        /**
         * @public
         *
         * @param string key
         * @param string topicName
         * @return void
         * @throws 1475361759
         * @throws 1475361760
         */
        function off(key, topicName) {
          utility().assert(utility().isNonEmptyString(key), 'Invalid parameter "key"', 1475361759);
          utility().assert(utility().isNonEmptyString(topicName), 'Invalid parameter "topicName"', 1475361760);

          if ('array' === $.type(_publisherTopics[key])) {
            _publisherTopics[key] = _publisherTopics[key].filter(function(currentTopicName) {
              return topicName !== currentTopicName;
            });
          }
        };

        /**
         * @public
         *
         * @return object (dereferenced)
         */
        function getObjectData() {
          return $.extend(true, {}, _objectData);
        };

        /**
         * @public
         *
         * @return string
         */
        function toString() {
          var childFormElements, objectData;

          objectData = getObjectData();
          childFormElements = objectData['renderables'] || null;
          delete objectData['renderables'];

          if (!utility().isUndefinedOrNull(objectData['__parentRenderable'])) {
            objectData['__parentRenderable'] = objectData['__parentRenderable'].getObjectData()['__identifierPath'] + ' (filtered)';
          }

          if (null !== childFormElements) {
            objectData['renderables'] = [];
            for (var i = 0, len = childFormElements.length; i < len; ++i) {
              var childFormElement = childFormElements[i];
              objectData['renderables'].push(JSON.parse(childFormElement.toString()));
            }
          }

          return JSON.stringify(objectData, null, 2);
        };

        /**
         * @public
         *
         * @return object
         */
        function clone() {
          var childFormElements, newModel, newRenderables, objectData;

          objectData = getObjectData();
          childFormElements = objectData['renderables'] || null;
          delete objectData['renderables'];
          delete objectData['__parentRenderable'];
          objectData['renderables'] = (childFormElements) ? true : null,

            newModel = new M();
          extendModel(newModel, objectData, '', true);

          if (null !== childFormElements) {
            newRenderables = [];
            for (var i = 0, len = childFormElements.length; i < len; ++i) {
              var childFormElement = childFormElements[i];

              childFormElement = childFormElement.clone();
              childFormElement.set('__parentRenderable', newModel, true);
              newRenderables.push(childFormElement);
            }
            newModel.set('renderables', newRenderables, true);
          }

          return newModel;
        };

        /**
         * Publish the public methods.
         */
        return {
          get: get,
          set: set,
          unset: unset,

          on: on,
          off: off,

          getObjectData: getObjectData,
          toString: toString,
          clone: clone
        };
      };

      newModel = new M();
      extendModel(newModel, modelExtension, '', true);

      return newModel;
    };

    /**
     * @public
     *
     * @return object
     */
    function repository() {

      /**
       * @public
       *
       * @param object typeDefinitions
       * @return void
       * @throws 1475364394
       */
      function setFormEditorDefinitions(formEditorDefinitions) {
        utility().assert('object' === $.type(formEditorDefinitions), 'Invalid parameter "formEditorDefinitions"', 1475364394);

        for (var key1 in formEditorDefinitions) {
          if (!formEditorDefinitions.hasOwnProperty(key1) || 'object' !== $.type(formEditorDefinitions[key1])) {
            continue;
          }
          for (var key2 in formEditorDefinitions[key1]) {
            if (!formEditorDefinitions[key1].hasOwnProperty(key2)) {
              continue;
            }
            if ('object' !== $.type(formEditorDefinitions[key1][key2])) {
              formEditorDefinitions[key1][key2] = {};
            }
          }
        }
        _repositoryFormEditorDefinitions = formEditorDefinitions;
      };

      /**
       * @public
       *
       * @param string typeName
       * @param string subject
       * @return object (dereferenced)
       * @throws 1475364952
       * @throws 1475364953
       */
      function getFormEditorDefinition(definitionName, subject) {
        utility().assert(utility().isNonEmptyString(definitionName), 'Invalid parameter "definitionName"', 1475364952);
        utility().assert(utility().isNonEmptyString(subject), 'Invalid parameter "subject"', 1475364953);
        return $.extend(true, {}, _repositoryFormEditorDefinitions[definitionName][subject]);
      };

      /**
       * @public
       *
       * @return object
       */
      function getRootFormElement() {
        return getApplicationStateStack().getCurrentState('formDefinition');
      };

      /**
       * @public
       *
       * @param object formElement
       * @param object referenceFormElement
       * @param boolean registerPropertyValidators
       * @param boolean disablePublishersOnSet
       * @return object
       * @throws 1475436224
       * @throws 1475364956
       */
      function addFormElement(formElement, referenceFormElement, registerPropertyValidators, disablePublishersOnSet) {
        var enclosingCompositeFormElement, identifier, formElementTypeDefinition,
          parentFormElementsArray, parentFormElementTypeDefinition, referenceFormElementElements,
          referenceFormElementTypeDefinition;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475436224);
        utility().assert('object' === $.type(referenceFormElement), 'Invalid parameter "referenceFormElement"', 1475364956);

        if (utility().isUndefinedOrNull(disablePublishersOnSet)) {
          disablePublishersOnSet = true;
        }
        disablePublishersOnSet = !!disablePublishersOnSet;

        registerPropertyValidators = !!registerPropertyValidators;
        formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        referenceFormElementTypeDefinition = repository().getFormEditorDefinition('formElements', referenceFormElement.get('type'));

        // formElement != Page / SummaryPage && referenceFormElement == Page / Fieldset / GridContainer / GridRow
        if (!formElementTypeDefinition['_isTopLevelFormElement'] && referenceFormElementTypeDefinition['_isCompositeFormElement']) {
          if ('array' !== $.type(referenceFormElement.get('renderables'))) {
            referenceFormElement.set('renderables', [], disablePublishersOnSet);
          }

          formElement.set('__parentRenderable', referenceFormElement, disablePublishersOnSet);
          formElement.set('__identifierPath', referenceFormElement.get('__identifierPath') + '/' + formElement.get('identifier'), disablePublishersOnSet);
          referenceFormElement.get('renderables').push(formElement);
        } else {
          // referenceFormElement == root form element
          if (referenceFormElement.get('__identifierPath') === getApplicationStateStack().getCurrentState('formDefinition').get('__identifierPath')) {
            referenceFormElementElements = referenceFormElement.get('renderables');
            // referenceFormElement = last page
            referenceFormElement = referenceFormElementElements[referenceFormElementElements.length - 1];
            // if formElement == Page / SummaryPage && referenceFormElement != Page / SummaryPage
          } else if (formElementTypeDefinition['_isTopLevelFormElement'] && !referenceFormElementTypeDefinition['_isTopLevelFormElement']) {
            // referenceFormElement = parent Page
            referenceFormElement = findEnclosingCompositeFormElementWhichIsOnTopLevel(referenceFormElement);
            // formElement == Page / SummaryPage / Fieldset / GridContainer / GridRow
          } else if (formElementTypeDefinition['_isCompositeFormElement']) {
            enclosingCompositeFormElement = findEnclosingCompositeFormElementWhichIsNotOnTopLevel(referenceFormElement);
            if (enclosingCompositeFormElement) {
              // referenceFormElement = parent Fieldset / GridContainer / GridRow
              referenceFormElement = enclosingCompositeFormElement;
            }
          }

          formElement.set('__parentRenderable', referenceFormElement.get('__parentRenderable'), disablePublishersOnSet);
          formElement.set('__identifierPath', referenceFormElement.get('__parentRenderable').get('__identifierPath') + '/' + formElement.get('identifier'), disablePublishersOnSet);
          parentFormElementsArray = referenceFormElement.get('__parentRenderable').get('renderables');
          parentFormElementsArray.splice(parentFormElementsArray.indexOf(referenceFormElement) + 1, 0, formElement);
        }

        if (registerPropertyValidators) {
          if ('array' === $.type(formElementTypeDefinition['editors'])) {
            for (var i = 0, len1 = formElementTypeDefinition['editors'].length; i < len1; ++i) {
              var configuration = {};

              if ('array' !== $.type(formElementTypeDefinition['editors'][i]['propertyValidators'])) {
                continue;
              }

              if (
                !utility().isUndefinedOrNull(formElementTypeDefinition['editors'][i]['propertyValidatorsMode'])
                && formElementTypeDefinition['editors'][i]['propertyValidatorsMode'] === 'OR'
              ) {
                configuration['propertyValidatorsMode'] = 'OR';
              } else {
                configuration['propertyValidatorsMode'] = 'AND';
              }

              propertyValidationService().addValidatorIdentifiersToFormElementProperty(
                formElement,
                formElementTypeDefinition['editors'][i]['propertyValidators'],
                formElementTypeDefinition['editors'][i]['propertyPath'],
                undefined,
                undefined,
                configuration
              );
            }
          }
        }

        return formElement;
      };

      /**
       * @param object formElement
       * @param boolean removeRegisteredPropertyValidators
       * @param boolean disablePublishersOnSet
       * @return void
       * @throws 1472553024
       * @throws 1475364957
       */
      function removeFormElement(formElement, removeRegisteredPropertyValidators, disablePublishersOnSet) {
        var parentFormElementElements;

        if (utility().isUndefinedOrNull(disablePublishersOnSet)) {
          disablePublishersOnSet = true;
        }
        disablePublishersOnSet = !!disablePublishersOnSet;
        removeRegisteredPropertyValidators = !!removeRegisteredPropertyValidators;

        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364957);
        utility().assert('object' === $.type(formElement.get('__parentRenderable')), 'Removing the root element is not allowed', 1472553024);

        parentFormElementElements = formElement.get('__parentRenderable').get('renderables');
        parentFormElementElements.splice(parentFormElementElements.indexOf(formElement), 1);
        formElement.get('__parentRenderable').set('renderables', parentFormElementElements, disablePublishersOnSet);

        if (removeRegisteredPropertyValidators) {
          propertyValidationService().removeAllValidatorIdentifiersFromFormElement(formElement);
        }
      };

      /**
       * @param object formElementToMove
       * @param string position
       * @param object referenceFormElement
       * @param boolean disablePublishersOnSet
       * @return object
       * @throws 1475364958
       * @throws 1475364959
       * @throws 1475364960
       * @throws 1475364961
       * @throws 1475364962
       * @throws 1476993731
       * @throws 1476993732
       */
      function moveFormElement(formElementToMove, position, referenceFormElement, disablePublishersOnSet) {
        var formElementToMoveTypeDefinition, referenceFormElementParentElements,
          referenceFormElementElements, referenceFormElementIndex,
          referenceFormElementTypeDefinition, reSetIdentifierPath;
        utility().assert('object' === $.type(formElementToMove), 'Invalid parameter "formElementToMove"', 1475364958);
        utility().assert('after' === position || 'before' === position || 'inside' === position, 'Invalid position "' + position + '"', 1475364959);
        utility().assert('object' === $.type(referenceFormElement), 'Invalid parameter "referenceFormElement"', 1475364960);

        if (utility().isUndefinedOrNull(disablePublishersOnSet)) {
          disablePublishersOnSet = true;
        }
        disablePublishersOnSet = !!disablePublishersOnSet;

        formElementToMoveTypeDefinition = repository().getFormEditorDefinition('formElements', formElementToMove.get('type'));
        referenceFormElementTypeDefinition = repository().getFormEditorDefinition('formElements', referenceFormElement.get('type'));

        removeFormElement(formElementToMove, false);
        reSetIdentifierPath = function(formElement, pathPrefix) {
          var formElements, newIdentifierPath, oldIdentifierPath,
            propertyValidationServiceRegisteredValidators;
          utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364961);
          utility().assert(utility().isNonEmptyString(pathPrefix), 'Invalid parameter "pathPrefix"', 1475364962);

          oldIdentifierPath = formElement.get('__identifierPath');
          newIdentifierPath = pathPrefix + '/' + formElement.get('identifier');

          propertyValidationServiceRegisteredValidators = getApplicationStateStack().getCurrentState('propertyValidationServiceRegisteredValidators');
          if (!utility().isUndefinedOrNull(propertyValidationServiceRegisteredValidators[oldIdentifierPath])) {
            propertyValidationServiceRegisteredValidators[newIdentifierPath] = propertyValidationServiceRegisteredValidators[oldIdentifierPath];
            delete propertyValidationServiceRegisteredValidators[oldIdentifierPath];
          }
          getApplicationStateStack().setCurrentState('propertyValidationServiceRegisteredValidators', propertyValidationServiceRegisteredValidators);

          formElement.set('__identifierPath', newIdentifierPath, disablePublishersOnSet);
          formElements = formElement.get('renderables');
          if ('array' === $.type(formElements)) {
            for (var i = 0, len = formElements.length; i < len; ++i) {
              reSetIdentifierPath(formElements[i], formElement.get('__identifierPath'));
            }
          }
        };

        /**
         * This is true on:
         * * Drag a Element on a Page Element (tree)
         * * Drag a Element on a Section Element (tree)
         */
        if (position === 'inside') {
          // formElementToMove == Page / SummaryPage
          utility().assert(!formElementToMoveTypeDefinition['_isTopLevelFormElement'], 'This move is not allowed', 1476993731);
          // referenceFormElement != Page / Fieldset / GridContainer / GridRow
          utility().assert(referenceFormElementTypeDefinition['_isCompositeFormElement'], 'This move is not allowed', 1476993732);

          formElementToMove.set('__parentRenderable', referenceFormElement, disablePublishersOnSet);
          reSetIdentifierPath(formElementToMove, referenceFormElement.get('__identifierPath'));

          referenceFormElementElements = referenceFormElement.get('renderables');
          if (utility().isUndefinedOrNull(referenceFormElementElements)) {
            referenceFormElementElements = [];
          }
          referenceFormElementElements.splice(0, 0, formElementToMove);
          referenceFormElement.set('renderables', referenceFormElementElements, disablePublishersOnSet);
        } else {
          /**
           * This is true on:
           * * Drag a Page before another Page (tree)
           * * Drag a Page after another Page (tree)
           */
          if (formElementToMoveTypeDefinition['_isTopLevelFormElement'] && referenceFormElementTypeDefinition['_isTopLevelFormElement']) {
            referenceFormElementParentElements = referenceFormElement.get('__parentRenderable').get('renderables');
            referenceFormElementIndex = referenceFormElementParentElements.indexOf(referenceFormElement);

            if (position === 'after') {
              referenceFormElementParentElements.splice(referenceFormElementIndex + 1, 0, formElementToMove);
            } else {
              referenceFormElementParentElements.splice(referenceFormElementIndex, 0, formElementToMove);
            }

            referenceFormElement.get('__parentRenderable').set('renderables', referenceFormElementParentElements, disablePublishersOnSet);
          } else {
            /**
             * This is true on:
             * * Drag a Element before another Element within the same level (tree)
             * * Drag a Element after another Element within the same level (tree)
             * * Drag a Element before another Element (stage)
             * * Drag a Element after another Element (stage)
             */
            if (formElementToMove.get('__parentRenderable').get('identifier') === referenceFormElement.get('__parentRenderable').get('identifier')) {
              referenceFormElementParentElements = referenceFormElement.get('__parentRenderable').get('renderables');
              referenceFormElementIndex = referenceFormElementParentElements.indexOf(referenceFormElement);
            } else {
              /**
               * This is true on:
               * * Drag a Element before an Element on another page (tree / stage)
               * * Drag a Element after an Element on another page (tree / stage)
               */
              formElementToMove.set('__parentRenderable', referenceFormElement.get('__parentRenderable'), disablePublishersOnSet);
              reSetIdentifierPath(formElementToMove, referenceFormElement.get('__parentRenderable').get('__identifierPath'));

              referenceFormElementParentElements = referenceFormElement.get('__parentRenderable').get('renderables');
              referenceFormElementIndex = referenceFormElementParentElements.indexOf(referenceFormElement);
            }

            if (position === 'after') {
              referenceFormElementParentElements.splice(referenceFormElementIndex + 1, 0, formElementToMove);
            } else {
              referenceFormElementParentElements.splice(referenceFormElementIndex, 0, formElementToMove);
            }

            referenceFormElement.get('__parentRenderable').set('renderables', referenceFormElementParentElements, disablePublishersOnSet);
          }
        }

        return formElementToMove;
      };

      /**
       * @param object formElement
       * @return int
       * @throws 1475364963
       */
      function getIndexForEnclosingCompositeFormElementWhichIsOnTopLevelForFormElement(formElement) {
        var enclosingCompositeFormElementWhichIsOnTopLevel, formElementTypeDefinition;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364963);

        formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));

        if (formElementTypeDefinition['_isTopLevelFormElement'] && formElementTypeDefinition['_isCompositeFormElement']) {
          enclosingCompositeFormElementWhichIsOnTopLevel = formElement;
        } else if (formElement.get('__identifierPath') === getApplicationStateStack().getCurrentState('formDefinition').get('__identifierPath')) {
          enclosingCompositeFormElementWhichIsOnTopLevel = getApplicationStateStack().getCurrentState('formDefinition').get('renderables')[0];
        } else {
          enclosingCompositeFormElementWhichIsOnTopLevel = findEnclosingCompositeFormElementWhichIsOnTopLevel(formElement);
        }
        return enclosingCompositeFormElementWhichIsOnTopLevel.get('__parentRenderable').get('renderables').indexOf(enclosingCompositeFormElementWhichIsOnTopLevel);
      };

      /**
       * @param object formElement
       * @return object
       * @throws 1472556223
       * @throws 1475364964
       */
      function findEnclosingCompositeFormElementWhichIsOnTopLevel(formElement) {
        var formElementTypeDefinition;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364964);
        utility().assert('object' === $.type(formElement.get('__parentRenderable')), 'The root element is never encloused by anything', 1472556223);

        formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        while (!formElementTypeDefinition['_isTopLevelFormElement']) {
          formElement = formElement.get('__parentRenderable');
          formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        }

        return formElement;
      };

      /**
       * @param object formElement
       * @return object|null
       * @throws 1489447996
       */
      function findEnclosingGridContainerFormElement(formElement) {
        var formElementTypeDefinition;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1489447996);

        formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        while (!formElementTypeDefinition['_isGridContainerFormElement']) {
          if (formElementTypeDefinition['_isTopLevelFormElement']) {
            return null;
          }
          formElement = formElement.get('__parentRenderable');
          formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        }
        if (formElementTypeDefinition['_isTopLevelFormElement']) {
          return null;
        }
        return formElement;
      };

      /**
       * @param object formElement
       * @return object|null
       * @throws 1490520271
       */
      function findEnclosingGridRowFormElement(formElement) {
        var formElementTypeDefinition;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1490520271);

        formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        while (!formElementTypeDefinition['_isGridRowFormElement']) {
          if (formElementTypeDefinition['_isTopLevelFormElement']) {
            return null;
          }
          formElement = formElement.get('__parentRenderable');
          formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        }
        if (formElementTypeDefinition['_isTopLevelFormElement']) {
          return null;
        }
        return formElement;
      };

      /**
       * @param object formElement
       * @return object|null
       * @throws 1475364965
       */
      function findEnclosingCompositeFormElementWhichIsNotOnTopLevel(formElement) {
        var formElementTypeDefinition;
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364965);

        formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        while (!formElementTypeDefinition['_isCompositeFormElement']) {
          if (formElementTypeDefinition['_isTopLevelFormElement']) {
            return null;
          }
          formElement = formElement.get('__parentRenderable');
          formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));
        }
        if (formElementTypeDefinition['_isTopLevelFormElement']) {
          return null;
        }
        return formElement;
      };

      /**
       * @return object
       */
      function getNonCompositeNonToplevelFormElements() {
        var collect, nonCompositeNonToplevelFormElements;

        collect = function(formElement) {
          var formElements, formElementTypeDefinition;
          utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475364961);

          formElementTypeDefinition = repository().getFormEditorDefinition('formElements', formElement.get('type'));

          if (!formElementTypeDefinition['_isTopLevelFormElement'] && !formElementTypeDefinition['_isCompositeFormElement']) {
            nonCompositeNonToplevelFormElements.push(formElement);
          }

          formElements = formElement.get('renderables');
          if ('array' === $.type(formElements)) {
            for (var i = 0, len = formElements.length; i < len; ++i) {
              collect(formElements[i]);
            }
          }
        };

        nonCompositeNonToplevelFormElements = [];
        collect(getRootFormElement());
        return nonCompositeNonToplevelFormElements;
      };

      /**
       * @param string identifier
       * @returl bool
       * @throws 1475364966
       */
      function isFormElementIdentifierUsed(identifier) {
        var checkIdentifier, identifierFound;
        utility().assert(utility().isNonEmptyString(identifier), 'Invalid parameter "identifier"', 1475364966);

        checkIdentifier = function(formElement) {
          var formElements;

          if (formElement.get('identifier') === identifier) {
            identifierFound = true;
          }

          if (!identifierFound) {
            formElements = formElement.get('renderables');
            if ('array' === $.type(formElements)) {
              for (var i = 0, len = formElements.length; i < len; ++i) {
                checkIdentifier(formElements[i]);
                if (identifierFound) {
                  break;
                }
              }
            }
          }
        }

        checkIdentifier(getApplicationStateStack().getCurrentState('formDefinition'));
        return identifierFound;
      };

      /**
       * @param string formElementType
       * @return string
       * @throws 1475373676
       */
      function getNextFreeFormElementIdentifier(formElementType) {
        var i, prefix;
        utility().assert(utility().isNonEmptyString(formElementType), 'Invalid parameter "formElementType"', 1475373676);

        prefix = formElementType.toLowerCase().replace(/[^a-z0-9]/g, '-') + '-';
        i = 1;
        while (isFormElementIdentifierUsed(prefix + i)) {
          i++;
        }
        return prefix + i;
      };

      /**
       * @param string identifierPath
       * @return object
       * @throws 1472424333
       * @throws 1472424334
       * @throws 1472424330
       * @throws 1475373677
       */
      function findFormElementByIdentifierPath(identifierPath) {
        var obj, pathParts, pathPartsLength, formElement, formElements;

        utility().assert(utility().isNonEmptyString(identifierPath), 'Invalid parameter "identifierPath"', 1475373677);

        formElement = getApplicationStateStack().getCurrentState('formDefinition');
        pathParts = identifierPath.split('/');
        pathPartsLength = pathParts.length;

        for (var i = 0; i < pathPartsLength; ++i) {
          var key = pathParts[i];
          if (i === 0 || i === pathPartsLength) {
            utility().assert(key === formElement.get('identifier'), '"' + key + '" does not exist in path "' + identifierPath + '"', 1472424333);
            continue;
          }

          formElements = formElement.get('renderables');
          if ('array' === $.type(formElements)) {
            obj = null;
            for (var j = 0, len = formElements.length; j < len; ++j) {
              if (key === formElements[j].get('identifier')) {
                obj = formElements[j];
                break;
              }
            }

            utility().assert('null' !== $.type(obj), 'Could not find form element "' + key + '" in path "' + identifierPath + '"', 1472424334);
            formElement = obj;
          } else {
            utility().assert(false, 'No form elements found', 1472424330);
          }
        }
        return formElement;
      };

      /**
       * @param string|object formElement
       * @return object
       */
      function findFormElement(formElement) {
        if ('object' === $.type(formElement)) {
          formElement = formElement.get('__identifierPath');
        }
        return findFormElementByIdentifierPath(formElement);
      };

      /**
       * @public
       *
       * @param string collectionElementIdentifier
       * @param object collection
       * @return undefined|object
       * @throws 1475375281
       * @throws 1475375282
       */
      function findCollectionElementByIdentifierPath(collectionElementIdentifier, collection) {
        utility().assert(utility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475375281);
        utility().assert('array' === $.type(collection), 'Invalid parameter "collection"', 1475375282);

        for (var i = 0, len = collection.length; i < len; ++i) {
          if (collection[i]['identifier'] === collectionElementIdentifier) {
            return collection[i];
          }
        }

        return undefined;
      };

      /**
       * @public
       *
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param object formElement
       * @return int
       * @throws 1475375283
       * @throws 1475375284
       * @throws 1475375285
       */
      function getIndexFromPropertyCollectionElementByIdentifier(collectionElementIdentifier, collectionName, formElement) {
        var collection;
        utility().assert(utility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475375283);
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475375284);
        utility().assert(utility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475375285);

        collection = formElement.get(collectionName);
        if ('array' === $.type(collection)) {
          for (var i = 0, len = collection.length; i < len; ++i) {
            if (collection[i]['identifier'] === collectionElementIdentifier) {
              return i;
            }
          }
        }
        return -1;
      };

      /**
       * @public
       *
       * @param object collectionElementToAdd
       * @param string collectionName
       * @param object formElement
       * @param string referenceCollectionElementIdentifier
       * @param boolean disablePublishersOnSet
       * @return object
       * @throws 1475375686
       * @throws 1475375687
       * @throws 1475375688
       * @throws 1477413154
       */
      function addPropertyCollectionElement(collectionElementToAdd, collectionName, formElement, referenceCollectionElementIdentifier, disablePublishersOnSet) {
        var collection, formElementTypeDefinition, newCollection, newCollectionElementIndex;
        utility().assert('object' === $.type(collectionElementToAdd), 'Invalid parameter "collectionElementToAdd"', 1475375686);
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475375687);
        utility().assert(utility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475375688);

        if (utility().isUndefinedOrNull(disablePublishersOnSet)) {
          disablePublishersOnSet = true;
        }
        disablePublishersOnSet = !!disablePublishersOnSet;

        collection = formElement.get(collectionName);
        if ('array' !== $.type(collection)) {
          extendModel(formElement, [], collectionName, true);
          collection = formElement.get(collectionName);
        }

        if (utility().isUndefinedOrNull(referenceCollectionElementIdentifier)) {
          newCollectionElementIndex = 0;
        } else {
          newCollectionElementIndex = getIndexFromPropertyCollectionElementByIdentifier(referenceCollectionElementIdentifier, collectionName, formElement) + 1;
          utility().assert(-1 < newCollectionElementIndex, 'Could not find collection element ' + referenceCollectionElementIdentifier + ' within collection ' + collectionName, 1477413154);
        }

        collection.splice(newCollectionElementIndex, 0, collectionElementToAdd);
        formElement.set(collectionName, collection, true);

        propertyValidationService().removeValidatorIdentifiersFromFormElementProperty(formElement, collectionName);

        for (var i = 0, len = collection.length; i < len; ++i) {
          extendModel(formElement, collection[i], collectionName + '.' + i, true);
        }

        formElement.set(collectionName, collection, true);
        propertyValidationService().addValidatorIdentifiersFromFormElementPropertyCollections(formElement);
        formElement.set(collectionName, collection, disablePublishersOnSet);

        return formElement;
      };

      /**
       * @public
       *
       * @param object formElement
       * @param string collectionElementIdentifier
       * @param string collectionName
       * @param boolean disablePublishersOnSet
       * @return void
       * @throws 1475375689
       * @throws 1475375690
       * @throws 1475375691
       * @throws 1475375692
       */
      function removePropertyCollectionElementByIdentifier(formElement, collectionElementIdentifier, collectionName, disablePublishersOnSet) {
        var collection, collectionElementIndex;
        utility().assert(utility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475375689);
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1475375690);
        utility().assert(utility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475375691);

        collection = formElement.get(collectionName);
        utility().assert('array' === $.type(collection), 'The collection "' + collectionName + '" does not exist', 1475375692);

        if (utility().isUndefinedOrNull(disablePublishersOnSet)) {
          disablePublishersOnSet = true;
        }
        disablePublishersOnSet = !!disablePublishersOnSet;

        propertyValidationService().removeValidatorIdentifiersFromFormElementProperty(formElement, collectionName);
        collectionElementIndex = getIndexFromPropertyCollectionElementByIdentifier(collectionElementIdentifier, collectionName, formElement);
        collection.splice(collectionElementIndex, 1);
        formElement.set(collectionName, collection, disablePublishersOnSet);
        propertyValidationService().addValidatorIdentifiersFromFormElementPropertyCollections(formElement);
      };

      /**
       * @param string collectionElementToMoveIdentifier
       * @param string position
       * @param string referenceCollectionElementIdentifier
       * @param string position
       * @param object formElement
       * @param boolean disablePublishersOnSet
       * @return void
       * @throws 1477404484
       * @throws 1477404485
       * @throws 1477404486
       * @throws 1477404488
       * @throws 1477404489
       * @throws 1477404490
       */
      function movePropertyCollectionElement(collectionElementToMoveIdentifier, position, referenceCollectionElementIdentifier, collectionName, formElement, disablePublishersOnSet) {
        var collection, collectionElementToMove, referenceCollectionElement,
          referenceCollectionElementIndex;

        utility().assert('after' === position || 'before' === position, 'Invalid position "' + position + '"', 1477404485);
        utility().assert('string' === $.type(referenceCollectionElementIdentifier), 'Invalid parameter "referenceCollectionElementIdentifier"', 1477404486);
        utility().assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1477404488);

        collection = formElement.get(collectionName);
        utility().assert('array' === $.type(collection), 'The collection "' + collectionName + '" does not exist', 1477404490);

        collectionElementToMove = findCollectionElementByIdentifierPath(collectionElementToMoveIdentifier, collection);
        utility().assert('object' === $.type(collectionElementToMove), 'Invalid parameter "collectionElementToMove"', 1477404484);

        removePropertyCollectionElementByIdentifier(formElement, collectionElementToMoveIdentifier, collectionName);

        referenceCollectionElementIndex = getIndexFromPropertyCollectionElementByIdentifier(referenceCollectionElementIdentifier, collectionName, formElement);
        utility().assert(-1 < referenceCollectionElementIndex, 'Could not find collection element ' + referenceCollectionElementIdentifier + ' within collection ' + collectionName, 1477404489);

        if ('before' === position) {
          referenceCollectionElement = collection[referenceCollectionElementIndex - 1];
          if (utility().isUndefinedOrNull(referenceCollectionElement)) {
            referenceCollectionElementIdentifier = undefined;
          } else {
            referenceCollectionElementIdentifier = referenceCollectionElement['identifier'];
          }
        }

        addPropertyCollectionElement(collectionElementToMove, collectionName, formElement, referenceCollectionElementIdentifier, disablePublishersOnSet)
      };

      /**
       * Publish the public methods.
       */
      return {
        getRootFormElement: getRootFormElement,

        getFormEditorDefinition: getFormEditorDefinition,
        setFormEditorDefinitions: setFormEditorDefinitions,

        findFormElement: findFormElement,
        findFormElementByIdentifierPath: findFormElementByIdentifierPath,
        findEnclosingCompositeFormElementWhichIsNotOnTopLevel: findEnclosingCompositeFormElementWhichIsNotOnTopLevel,
        findEnclosingCompositeFormElementWhichIsOnTopLevel: findEnclosingCompositeFormElementWhichIsOnTopLevel,
        findEnclosingGridContainerFormElement: findEnclosingGridContainerFormElement,
        findEnclosingGridRowFormElement: findEnclosingGridRowFormElement,
        getIndexForEnclosingCompositeFormElementWhichIsOnTopLevelForFormElement: getIndexForEnclosingCompositeFormElementWhichIsOnTopLevelForFormElement,
        getNonCompositeNonToplevelFormElements: getNonCompositeNonToplevelFormElements,

        getNextFreeFormElementIdentifier: getNextFreeFormElementIdentifier,
        isFormElementIdentifierUsed: isFormElementIdentifierUsed,

        addFormElement: addFormElement,
        moveFormElement: moveFormElement,
        removeFormElement: removeFormElement,

        findCollectionElementByIdentifierPath: findCollectionElementByIdentifierPath,
        getIndexFromPropertyCollectionElementByIdentifier: getIndexFromPropertyCollectionElementByIdentifier,
        addPropertyCollectionElement: addPropertyCollectionElement,
        removePropertyCollectionElementByIdentifier: removePropertyCollectionElementByIdentifier,
        movePropertyCollectionElement: movePropertyCollectionElement
      };
    };

    /**
     * @public
     *
     * @return object
     */
    function factory() {

      /**
       * @public
       *
       * @param object configuration
       * @param string identifierPathPrefix
       * @param object parentFormElement
       * @param boolean registerPropertyValidators
       * @param boolean disablePublishersOnSet
       * @return object
       * @throws 1475375693
       * @throws 1475436040
       * @throws 1475604050
       */
      function createFormElement(configuration, identifierPathPrefix, parentFormElement, registerPropertyValidators, disablePublishersOnSet) {
        var currentChildFormElements, collections, formElementTypeDefinition, identifierPath,
          rawChildFormElements, formElement, predefinedDefaults;
        utility().assert('object' === $.type(configuration), 'Invalid parameter "configuration"', 1475375693);
        utility().assert(utility().isNonEmptyString(configuration['identifier']), '"identifier" must not be empty', 1475436040);
        utility().assert(utility().isNonEmptyString(configuration['type']), '"type" must not be empty', 1475604050);

        registerPropertyValidators = !!registerPropertyValidators;
        if (utility().isUndefinedOrNull(disablePublishersOnSet)) {
          disablePublishersOnSet = true;
        }
        disablePublishersOnSet = !!disablePublishersOnSet;

        formElementTypeDefinition = repository().getFormEditorDefinition('formElements', configuration['type']);
        rawChildFormElements = configuration['renderables'];
        delete configuration['renderables'];

        collections = {};
        predefinedDefaults = formElementTypeDefinition['predefinedDefaults'] || {};
        for (var collectionName in configuration) {
          if (!configuration.hasOwnProperty(collectionName)) {
            continue;
          }
          if (utility().isUndefinedOrNull(_repositoryFormEditorDefinitions[collectionName])) {
            continue;
          }

          predefinedDefaults[collectionName] = predefinedDefaults[collectionName] || {};
          collections[collectionName] = $.extend(
            predefinedDefaults[collectionName] || {},
            configuration[collectionName]
          );

          delete predefinedDefaults[collectionName];
          delete configuration[collectionName];
        }

        identifierPathPrefix = identifierPathPrefix || '';
        identifierPath = (identifierPathPrefix === '') ? configuration['identifier'] : identifierPathPrefix + '/' + configuration['identifier'];

        configuration = $.extend(
          predefinedDefaults,
          configuration,
          {
            renderables: (rawChildFormElements) ? true : null,
            __parentRenderable: null,
            __identifierPath: identifierPath
          }
        );

        formElement = createModel(configuration);
        formElement.set('__parentRenderable', parentFormElement || null, disablePublishersOnSet);

        for (var collectionName in collections) {
          if (!collections.hasOwnProperty(collectionName)) {
            continue;
          }

          for (var i in collections[collectionName]) {
            var previousCreatePropertyCollectionElementIdentifier, propertyCollectionElement;
            if (!collections[collectionName].hasOwnProperty(i)) {
              continue;
            }
            propertyCollectionElement = createPropertyCollectionElement(
              collections[collectionName][i]['identifier'],
              collections[collectionName][i],
              collectionName
            );
            if (i > 0) {
              previousCreatePropertyCollectionElementIdentifier = collections[collectionName][i - 1]['identifier']
            }
            repository().addPropertyCollectionElement(propertyCollectionElement, collectionName, formElement, previousCreatePropertyCollectionElementIdentifier, true);
          }
        }

        if (registerPropertyValidators) {
          if ('array' === $.type(formElementTypeDefinition['editors'])) {
            for (var i = 0, len1 = formElementTypeDefinition['editors'].length; i < len1; ++i) {
              var configuration = {};

              if ('array' !== $.type(formElementTypeDefinition['editors'][i]['propertyValidators'])) {
                continue;
              }

              if (
                !utility().isUndefinedOrNull(formElementTypeDefinition['editors'][i]['propertyValidatorsMode'])
                && formElementTypeDefinition['editors'][i]['propertyValidatorsMode'] === 'OR'
              ) {
                configuration['propertyValidatorsMode'] = 'OR';
              } else {
                configuration['propertyValidatorsMode'] = 'AND';
              }

              propertyValidationService().addValidatorIdentifiersToFormElementProperty(
                formElement,
                formElementTypeDefinition['editors'][i]['propertyValidators'],
                formElementTypeDefinition['editors'][i]['propertyPath'],
                undefined,
                undefined,
                configuration
              );
            }
          }
        }

        if ('array' === $.type(rawChildFormElements)) {
          currentChildFormElements = [];
          for (var i = 0, len = rawChildFormElements.length; i < len; ++i) {
            currentChildFormElements.push(createFormElement(rawChildFormElements[i], identifierPath, formElement, registerPropertyValidators, disablePublishersOnSet));
          }
          formElement.set('renderables', currentChildFormElements, disablePublishersOnSet);
        }
        return formElement;
      };

      /**
       * @public
       *
       * @param string collectionElementIdentifier
       * @param object collectionElementConfiguration
       * @param string collectionName
       * @return object
       * @throws 1475377160
       * @throws 1475377161
       * @throws 1475377162
       */
      function createPropertyCollectionElement(collectionElementIdentifier, collectionElementConfiguration, collectionName) {
        var collectionDefinition, collectionElementPresets;
        utility().assert(utility().isNonEmptyString(collectionElementIdentifier), 'Invalid parameter "collectionElementIdentifier"', 1475377160);
        utility().assert('object' === $.type(collectionElementConfiguration), 'Invalid parameter "collectionElementConfiguration"', 1475377161);
        utility().assert(utility().isNonEmptyString(collectionName), 'Invalid parameter "collectionName"', 1475377162);

        collectionElementConfiguration['identifier'] = collectionElementIdentifier;
        collectionDefinition = repository().getFormEditorDefinition(collectionName, collectionElementIdentifier);
        if (collectionDefinition['predefinedDefaults']) {
          collectionElementPresets = collectionDefinition['predefinedDefaults'];
        } else {
          collectionElementPresets = {};
        }

        return $.extend(collectionElementPresets, collectionElementConfiguration);
      };

      /**
       * Publish the public methods.
       */
      return {
        createFormElement: createFormElement,
        createPropertyCollectionElement: createPropertyCollectionElement
      };
    };

    /**
     * @public
     *
     * @return object
     */
    function dataBackend() {

      /**
       * @public
       *
       * @param object endpoints
       * @return void
       * @throws 1475377488
       */
      function setEndpoints(endpoints) {
        utility().assert('object' === $.type(endpoints), 'Invalid parameter "endpoints"', 1475377488);
        _dataBackendEndpoints = endpoints;
      };

      /**
       * @public
       *
       * @param string prototypeName
       * @return void
       * @throws 1475377489
       */
      function setPrototypeName(prototypeName) {
        utility().assert(utility().isNonEmptyString(prototypeName), 'Invalid parameter "prototypeName"', 1475928095);
        _dataBackendPrototypeName = prototypeName;
      };

      /**
       * @public
       *
       * @param string persistenceIdentifier
       * @return void
       * @throws 1475377489
       */
      function setPersistenceIdentifier(persistenceIdentifier) {
        utility().assert(utility().isNonEmptyString(persistenceIdentifier), 'Invalid parameter "persistenceIdentifier"', 1475377489);
        _dataBackendPersistenceIdentifier = persistenceIdentifier;
      };

      /**
       * @public
       *
       * @return void
       * @publish core/ajax/saveFormDefinition/success
       * @publish core/ajax/error
       * @throws 1475520918
       */
      function saveFormDefinition() {
        utility().assert(utility().isNonEmptyString(_dataBackendEndpoints['saveForm']), 'The endpoint "saveForm" is not configured', 1475520918);

        if (_runningAjaxRequests['saveForm']) {
          _runningAjaxRequests['saveForm'].abort();
        }

        _runningAjaxRequests['saveForm'] = $.post(_dataBackendEndpoints['saveForm'], {
          tx_form_web_formformbuilder: {
            formPersistenceIdentifier: _dataBackendPersistenceIdentifier,
            formDefinition: JSON.stringify(utility().convertToSimpleObject(getApplicationStateStack().getCurrentState('formDefinition')))
          }
        }, function(data, textStatus, jqXHR) {
          if (_runningAjaxRequests['saveForm'] !== jqXHR) {
            return;
          }
          _runningAjaxRequests['saveForm'] = null;
          if (data['status'] === 'success') {
            publisherSubscriber().publish('core/ajax/saveFormDefinition/success', [data]);
          } else {
            publisherSubscriber().publish('core/ajax/saveFormDefinition/error', [data]);
          }
        }).fail(function(jqXHR, textStatus, errorThrown) {
          publisherSubscriber().publish('core/ajax/error', [jqXHR, textStatus, errorThrown]);
        });
      };

      /**
       * @public
       *
       * @param int pageIndex
       * @return void
       * @publish core/ajax/renderFormDefinitionPage/success
       * @publish core/ajax/error
       * @throws 1473447677
       * @throws 1475377781
       * @throws 1475377782
       */
      function renderFormDefinitionPage(pageIndex) {
        utility().assert($.isNumeric(pageIndex), 'Invalid parameter "pageIndex"', 1475377781);
        utility().assert(utility().isNonEmptyString(_dataBackendEndpoints['formPageRenderer']), 'The endpoint "formPageRenderer" is not configured', 1473447677);

        if (_runningAjaxRequests['renderFormDefinitionPage']) {
          _runningAjaxRequests['renderFormDefinitionPage'].abort();
        }

        _runningAjaxRequests['renderFormDefinitionPage'] = $.post(_dataBackendEndpoints['formPageRenderer'], {
          tx_form_web_formformbuilder: {
            formDefinition: JSON.stringify(utility().convertToSimpleObject(getApplicationStateStack().getCurrentState('formDefinition'))),
            pageIndex: pageIndex,
            prototypeName: _dataBackendPrototypeName
          }
        }, function(data, textStatus, jqXHR) {
          if (_runningAjaxRequests['renderFormDefinitionPage'] !== jqXHR) {
            return;
          }
          _runningAjaxRequests['renderFormDefinitionPage'] = null;
          publisherSubscriber().publish('core/ajax/renderFormDefinitionPage/success', [data, pageIndex]);
        }).fail(function(jqXHR, textStatus, errorThrown) {
          publisherSubscriber().publish('core/ajax/error', [jqXHR, textStatus, errorThrown]);
        });
      };

      /**
       * Publish the public methods.
       */
      return {
        renderFormDefinitionPage: renderFormDefinitionPage,
        saveFormDefinition: saveFormDefinition,
        setEndpoints: setEndpoints,
        setPersistenceIdentifier: setPersistenceIdentifier,
        setPrototypeName: setPrototypeName
      };
    };

    /**
     * @public
     *
     * @return object
     */
    function getApplicationStateStack() {

      /**
       * @public
       *
       * @param object applicationState
       * @param bool disablePublishersOnSet
       * @return void
       * @publish core/applicationState/add
       * @throws 1477847415
       */
      function add(applicationState, disablePublishersOnSet) {
        utility().assert('object' === $.type(applicationState), 'Invalid parameter "applicationState"', 1477847415);
        disablePublishersOnSet = !!disablePublishersOnSet;

        $.extend(applicationState, {
          propertyValidationServiceRegisteredValidators: $.extend(true, {}, getCurrentState('propertyValidationServiceRegisteredValidators'))
        });

        _applicationStateStack.splice(0, 0, applicationState);
        if (_applicationStateStack.length > _applicationStateStackSize) {
          _applicationStateStack.splice(_applicationStateStackSize - 1, (_applicationStateStack.length - _applicationStateStackSize));
        }

        if (!disablePublishersOnSet) {
          publisherSubscriber().publish('core/applicationState/add', [applicationState, getCurrentStackPointer(), getCurrentStackSize()]);
        }
      };

      /**
       * @public
       *
       * @param applicationState
       * @param bool disablePublishersOnSet
       * @return void
       * @publish core/applicationState/add
       * @throws 1477872641
       */
      function addAndReset(applicationState, disablePublishersOnSet) {
        utility().assert('object' === $.type(applicationState), 'Invalid parameter "applicationState"', 1477872641);

        if (_applicationStateStackPointer > 0) {
          _applicationStateStack.splice(0, _applicationStateStackPointer);
        }

        _applicationStateStackPointer = 0;
        add(applicationState, true);

        if (!disablePublishersOnSet) {
          publisherSubscriber().publish('core/applicationState/add', [getCurrentState(), getCurrentStackPointer(), getCurrentStackSize()]);
        }
      };

      /**
       * @public
       *
       * @param string
       * @return object
       * @throws 1477932754
       */
      function getCurrentState(type) {
        if (!utility().isUndefinedOrNull(type)) {
          utility().assert(
            'formDefinition' === type
            || 'currentlySelectedPageIndex' === type
            || 'currentlySelectedFormElementIdentifierPath' === type
            || 'propertyValidationServiceRegisteredValidators' === type,

            'Invalid parameter "type"', 1477932754
          );

          if ('undefined' === $.type(_applicationStateStack[_applicationStateStackPointer])) {
            return undefined;
          }
          return _applicationStateStack[_applicationStateStackPointer][type];
        }
        return _applicationStateStack[_applicationStateStackPointer];
      };

      /**
       * @public
       *
       * @param string
       * @param mixed
       * @return void
       * @throws 1477934111
       */
      function setCurrentState(type, value) {
        utility().assert(
          'formDefinition' === type
          || 'currentlySelectedPageIndex' === type
          || 'currentlySelectedFormElementIdentifierPath' === type
          || 'propertyValidationServiceRegisteredValidators' === type,

          'Invalid parameter "type"', 1477934111
        );
        _applicationStateStack[_applicationStateStackPointer][type] = value;
      };

      /**
       * @public
       *
       * @param int
       * @return void
       * @throws 1477846933
       */
      function setMaximalStackSize(stackSize) {
        utility().assert('number' === $.type(stackSize), 'Invalid parameter "size"', 1477846933);
        _applicationStateStackSize = stackSize;
      };

      /**
       * @public
       *
       * @return int
       */
      function getMaximalStackSize() {
        return _applicationStateStackSize;
      };

      /**
       * @public
       *
       * @return int
       */
      function getCurrentStackSize() {
        return _applicationStateStack.length;
      };

      /**
       * @public
       *
       * @return object
       */
      function getCurrentStackPointer() {
        return _applicationStateStackPointer;
      };

      /**
       * @public
       *
       * @param int
       * @return void
       * @throws 1477852138
       */
      function setCurrentStackPointer(stackPointer) {
        utility().assert('number' === $.type(stackPointer), 'Invalid parameter "size"', 1477852138);
        if (stackPointer < 0) {
          _applicationStateStackPointer = 0;
        } else if (stackPointer > _applicationStateStack.length - 1) {
          _applicationStateStackPointer = _applicationStateStack.length - 1;
        } else {
          _applicationStateStackPointer = stackPointer;
        }
      };


      /**
       * @public
       *
       * @return void
       */
      function decrementCurrentStackPointer() {
        setCurrentStackPointer(--_applicationStateStackPointer);
      };

      /**
       * @public
       *
       * @return void
       */
      function incrementCurrentStackPointer() {
        setCurrentStackPointer(++_applicationStateStackPointer);
      };

      /**
       * Publish the public methods.
       */
      return {
        add: add,
        addAndReset: addAndReset,
        getCurrentState: getCurrentState,
        setCurrentState: setCurrentState,
        getCurrentStackPointer: getCurrentStackPointer,
        setCurrentStackPointer: setCurrentStackPointer,
        decrementCurrentStackPointer: decrementCurrentStackPointer,
        incrementCurrentStackPointer: incrementCurrentStackPointer,
        setMaximalStackSize: setMaximalStackSize,
        getMaximalStackSize: getMaximalStackSize,
        getCurrentStackSize: getCurrentStackSize
      };
    };

    /**
     * Publish the public methods.
     * Implements the "Revealing Module Pattern".
     */
    return {
      getDataBackend: dataBackend,
      getFactory: factory,
      getPublisherSubscriber: publisherSubscriber,
      getRepository: repository,
      getUtility: utility,
      getPropertyValidationService: propertyValidationService,
      getRunningAjaxRequest: getRunningAjaxRequest,
      getApplicationStateStack: getApplicationStateStack
    };
  })($);
});
