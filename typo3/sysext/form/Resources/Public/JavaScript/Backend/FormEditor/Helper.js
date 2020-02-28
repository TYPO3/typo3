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
 * Module: TYPO3/CMS/Form/Backend/FormEditor/Helper
 */
define(['jquery'], function($) {
  'use strict';

  return (function($) {

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
    var _configuration = {};

    /**
     * @private
     *
     * @var object
     */
    var _defaultConfiguration = {
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

    /* *************************************************************
     * Private Methods
     * ************************************************************/

    /**
     * @private
     *
     * @return object
     */
    function getFormEditorApp() {
      return _formEditorApp;
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
     * @param mixed test
     * @param string message
     * @param int messageCode
     * @return void
     */
    function assert(test, message, messageCode) {
      return getFormEditorApp().assert(test, message, messageCode);
    };

    /* *************************************************************
     * Public Methods
     * ************************************************************/

    /**
     * @public
     *
     * @param object
     * @return this
     * @throws 1478950623
     */
    function setConfiguration(configuration) {
      assert('object' === $.type(configuration), 'Invalid parameter "configuration"', 1478950623);
      _configuration = $.extend(true, _defaultConfiguration, configuration);
      return this;
    };

    /**
     * @public
     *
     * @param string
     * @param array
     * @return string
     * @throws 1478801251
     * @throws 1478801252
     */
    function buildDomElementSelectorHelper(patternIdentifier, replacements) {
      var newString;
      assert(
        !getUtility().isUndefinedOrNull(_configuration['domElementSelectorPattern'][patternIdentifier]),
        'Invalid parameter "patternIdentifier" (' + patternIdentifier + ')',
        1478801251
      );
      assert('array' === $.type(replacements), 'Invalid parameter "replacements"', 1478801252);

      newString = _configuration['domElementSelectorPattern'][patternIdentifier];
      for (var i = 0, len = replacements.length; i < len; ++i) {
        newString = newString.replace('{' + i + '}', replacements[i]);
      }
      return newString;
    };

    /**
     * @public
     *
     * @param string
     * @param array
     * @return string
     * @throws 1478372374
     */
    function getDomElementSelector(selectorIdentifier, args) {
      assert(
        !getUtility().isUndefinedOrNull(_configuration['domElementSelectorPattern'][selectorIdentifier]),
        'Invalid parameter "selectorIdentifier" (' + selectorIdentifier + ')',
        1478372374
      );
      return buildDomElementSelectorHelper(selectorIdentifier, args);
    };

    /**
     * @public
     *
     * @param string
     * @param bool
     * @return string
     * @throws 1478803906
     */
    function getDomElementClassName(classNameIdentifier, asSelector) {
      var className;
      assert(
        !getUtility().isUndefinedOrNull(_configuration['domElementClassNames'][classNameIdentifier]),
        'Invalid parameter "classNameIdentifier" (' + classNameIdentifier + ')',
        1478803906
      );

      className = _configuration['domElementClassNames'][classNameIdentifier];
      if (!!asSelector) {
        className = getDomElementSelector('class', [className]);
      }
      return className;
    };

    /**
     * @public
     *
     * @param string
     * @param bool
     * @return string
     * @throws 1479251518
     */
    function getDomElementIdName(idNameIdentifier, asSelector) {
      var idName;
      assert(
        !getUtility().isUndefinedOrNull(_configuration['domElementIdNames'][idNameIdentifier]),
        'Invalid parameter "domElementIdNames" (' + idNameIdentifier + ')',
        1479251518
      );

      idName = _configuration['domElementIdNames'][idNameIdentifier];
      if (!!asSelector) {
        idName = getDomElementSelector('id', [idName]);
      }
      return idName;
    };

    /**
     * @public
     *
     * @param string
     * @param bool
     * @return string
     * @throws 1478806884
     */
    function getDomElementDataAttributeValue(dataAttributeValueIdentifier) {
      assert(
        !getUtility().isUndefinedOrNull(_configuration['domElementDataAttributeValues'][dataAttributeValueIdentifier]),
        'Invalid parameter "dataAttributeValueIdentifier" (' + dataAttributeValueIdentifier + ')',
        1478806884
      );
      return _configuration['domElementDataAttributeValues'][dataAttributeValueIdentifier];
    };

    /**
     * @public
     *
     * @param string
     * @param string
     * @param array
     * @return string
     * @throws 1478808035
     */
    function getDomElementDataAttribute(dataAttributeIdentifier, selectorIdentifier, additionalSelectorArgs) {
      assert(
        !getUtility().isUndefinedOrNull(_configuration['domElementDataAttributeNames'][dataAttributeIdentifier]),
        'Invalid parameter "dataAttributeIdentifier" (' + dataAttributeIdentifier + ')',
        1478808035
      );

      if (getUtility().isUndefinedOrNull(selectorIdentifier)) {
        return _configuration['domElementDataAttributeNames'][dataAttributeIdentifier];
      }

      additionalSelectorArgs = additionalSelectorArgs || [];
      return getDomElementSelector(
        selectorIdentifier,
        [_configuration['domElementDataAttributeNames'][dataAttributeIdentifier]].concat(additionalSelectorArgs)
      );
    };

    /**
     * @public
     *
     * Return a string like [data-identifier="someValue"]
     *
     * @return string
     */
    function getDomElementDataIdentifierSelector(dataAttributeValueIdentifier) {
      return getDomElementDataAttribute('identifier', 'bracesWithKeyValue', [getDomElementDataAttributeValue(dataAttributeValueIdentifier)]);
    };

    /**
     * @public
     *
     * @param string
     * @return object
     */
    function getTemplate(templateName) {
      if (!getUtility().isUndefinedOrNull(_configuration['domElementDataAttributeValues'][templateName])) {
        templateName = getDomElementDataAttributeValue(templateName);
      }

      return $(getDomElementDataAttribute('template', 'bracesWithKeyValue', [templateName]));
    };

    /**
     * @public
     *
     * @param string
     * @param object
     * @return object
     */
    function getTemplatePropertyDomElement(templatePropertyName, templateDomElement) {
      return $(getDomElementDataAttribute('templateProperty', 'bracesWithKeyValue', [templatePropertyName]), $(templateDomElement));
    };

    /**
     * @public
     *
     * @param object formEditorApp
     * @return void
     */
    function bootstrap(formEditorApp) {
      _formEditorApp = formEditorApp;
    };

    /**
     * Publish the public methods.
     * Implements the "Revealing Module Pattern".
     */
    return {
      bootstrap: bootstrap,
      buildDomElementSelectorHelper: buildDomElementSelectorHelper,
      getDomElementClassName: getDomElementClassName,
      getDomElementIdName: getDomElementIdName,
      getDomElementDataAttribute: getDomElementDataAttribute,
      getDomElementDataAttributeValue: getDomElementDataAttributeValue,
      getDomElementDataIdentifierSelector: getDomElementDataIdentifierSelector,
      getDomElementSelector: getDomElementSelector,
      getTemplate: getTemplate,
      getTemplatePropertyDomElement: getTemplatePropertyDomElement,
      setConfiguration: setConfiguration
    };
  })($);
});
