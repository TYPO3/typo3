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
 * Module: TYPO3/CMS/Form/Backend/FormEditor/ModalsComponent
 */
define(['jquery',
  'TYPO3/CMS/Form/Backend/FormEditor/Helper',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Severity',
  'TYPO3/CMS/Backend/Icons'
], function($, Helper, Modal, Severity, Icons) {
  'use strict';

  return (function($, Helper, Modal, Severity, Icons) {

    /**
     * @private
     *
     * @var object
     */
    var _configuration = null;

    /**
     * @private
     *
     * @var object
     */
    var _defaultConfiguration = {
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
        templateInsertElements: 'Modal-InsertElements',
        templateInsertPages: 'Modal-InsertPages',
        templateValidationErrors: 'Modal-ValidationErrors'
      }
    };

    /**
     * @private
     *
     * @var object
     */
    var _formEditorApp = null;

    /* *************************************************************
     * Private Methodes
     * ************************************************************/

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
     * @return object
     */
    function getPublisherSubscriber() {
      return getFormEditorApp().getPublisherSubscriber();
    };

    /**
     * @private
     *
     * @param object
     * @param string
     * @return mixed
     */
    function getFormElementDefinition(formElement, formElementDefinitionKey) {
      return getFormEditorApp().getFormElementDefinition(formElement, formElementDefinitionKey);
    };

    /**
     * @public
     *
     * @param string publisherTopicName
     * @param object publisherTopicArguments
     * @return void
     * @throws 1478889044
     * @throws 1478889049
     */
    function _showRemoveElementModal(publisherTopicName, publisherTopicArguments) {
      var modalButtons = [];

      assert(
        getUtility().isNonEmptyString(publisherTopicName),
        'Invalid parameter "publisherTopicName"',
        1478889049
      );
      assert(
        'array' === $.type(publisherTopicArguments),
        'Invalid parameter "formElement"',
        1478889044
      );

      modalButtons.push({
        text: getFormElementDefinition(getRootFormElement(), 'modalRemoveElementCancleButton'),
        active: true,
        btnClass: getHelper().getDomElementClassName('buttonDefault'),
        name: 'cancel',
        trigger: function() {
          Modal.currentModal.trigger('modal-dismiss');
        }
      });

      modalButtons.push({
        text: getFormElementDefinition(getRootFormElement(), 'modalRemoveElementConfirmButton'),
        active: true,
        btnClass: getHelper().getDomElementClassName('buttonWarning'),
        name: 'confirm',
        trigger: function() {
          getPublisherSubscriber().publish(publisherTopicName, publisherTopicArguments);
          Modal.currentModal.trigger('modal-dismiss');
        }
      });

      Modal.show(
        getFormElementDefinition(getRootFormElement(), 'modalRemoveElementDialogTitle'),
        getFormElementDefinition(getRootFormElement(), 'modalRemoveElementDialogMessage'),
        Severity.warning,
        modalButtons
      );
    };

    /**
     * @private
     *
     * @param object modalContent
     * @param string publisherTopicName
     * @param object configuration
     * @return void
     * @publish mixed
     * @throws 1478910954
     */
    function _insertElementsModalSetup(modalContent, publisherTopicName, configuration) {
      var formElementItems;

      assert(
        getUtility().isNonEmptyString(publisherTopicName),
        'Invalid parameter "publisherTopicName"',
        1478910954
      );

      if ('object' === $.type(configuration)) {
        for (var key in configuration) {
          if (!configuration.hasOwnProperty(key)) {
            continue;
          }
          if (
            key === 'disableElementTypes'
            && 'array' === $.type(configuration[key])
          ) {
            for (var i = 0, len = configuration[key].length; i < len; ++i) {
              $(
                getHelper().getDomElementDataAttribute(
                  'fullElementType',
                  'bracesWithKeyValue', [configuration[key][i]]
                ),
                modalContent
              ).addClass(getHelper().getDomElementClassName('disabled'));
            }
          }

          if (
            key === 'onlyEnableElementTypes'
            && 'array' === $.type(configuration[key])
          ) {
            $(
              getHelper().getDomElementDataAttribute(
                'fullElementType',
                'bracesWithKey'
              ),
              modalContent
            ).each(function(i, element) {
              for (var i = 0, len = configuration[key].length; i < len; ++i) {
                var that = $(this);
                if (that.data(getHelper().getDomElementDataAttribute('elementType')) !== configuration[key][i]) {
                  that.addClass(getHelper().getDomElementClassName('disabled'));
                }
              }
            });
          }
        }
      }

      $('a', modalContent).on("click", function(e) {
        getPublisherSubscriber().publish(publisherTopicName, [$(this).data(getHelper().getDomElementDataAttribute('elementType'))]);
        $('a', modalContent).off();
        Modal.currentModal.trigger('modal-dismiss');
      });
    };

    /**
     * @private
     *
     * @param object modalContent
     * @param object validationResults
     * @return void
     * @publish view/modal/validationErrors/element/clicked
     * @throws 1479161268
     */
    function _validationErrorsModalSetup(modalContent, validationResults) {
      var formElement, newRowItem, rowItemTemplate;

      assert(
        'array' === $.type(validationResults),
        'Invalid parameter "validationResults"',
        1479161268
      );

      rowItemTemplate = $(
        getHelper().getDomElementDataIdentifierSelector('rowItem'),
        modalContent
      ).clone();

      $(getHelper().getDomElementDataIdentifierSelector('rowItem'), modalContent).remove();

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
          formElement = getFormEditorApp()
            .getFormElementByIdentifierPath(validationResults[i]['formElementIdentifierPath']);
          newRowItem = rowItemTemplate.clone();
          $(getHelper().getDomElementDataIdentifierSelector('rowLink'), newRowItem)
            .attr(
              getHelper().getDomElementDataAttribute('elementIdentifier'),
              validationResults[i]['formElementIdentifierPath']
            )
            .html(_buildTitleByFormElement(formElement));
          $(getHelper().getDomElementDataIdentifierSelector('rowsContainer'), modalContent)
            .append(newRowItem);
        }
      }

      $('a', modalContent).on("click", function(e) {
        getPublisherSubscriber().publish('view/modal/validationErrors/element/clicked', [
          $(this).attr(getHelper().getDomElementDataAttribute('elementIdentifier'))
        ]);
        $('a', modalContent).off();
        Modal.currentModal.trigger('modal-dismiss');
      });
    };

    /**
     * @private
     *
     * @param object
     * @return object
     * @throws 1479162557
     */
    function _buildTitleByFormElement(formElement) {
      var label;
      assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1479162557);

      return $('<span></span>').text((formElement.get('label')
        ? formElement.get('label')
        : formElement.get('identifier')));
    };

    /* *************************************************************
     * Public Methodes
     * ************************************************************/

    /**
     * @public
     *
     * @param object formElement
     * @return void
     * @publish view/modal/removeFormElement/perform
     */
    function showRemoveFormElementModal(formElement) {
      _showRemoveElementModal('view/modal/removeFormElement/perform', [formElement]);
    };

    /**
     * @public
     *
     * @param string collectionElementIdentifier
     * @param string collectionName
     * @param object formElement
     * @return void
     * @publish view/modal/removeCollectionElement/perform
     * @throws 1478894420
     * @throws 1478894421
     */
    function showRemoveCollectionElementModal(collectionElementIdentifier, collectionName, formElement) {
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

      _showRemoveElementModal('view/modal/removeCollectionElement/perform', [collectionElementIdentifier, collectionName, formElement]);
    };

    /**
     * @public
     *
     * @return void
     * @publish view/modal/close/perform
     */
    function showCloseConfirmationModal() {
      var modalButtons = [];

      modalButtons.push({
        text: getFormElementDefinition(getRootFormElement(), 'modalCloseCancleButton'),
        active: true,
        btnClass: getHelper().getDomElementClassName('buttonDefault'),
        name: 'cancel',
        trigger: function() {
          Modal.currentModal.trigger('modal-dismiss');
        }
      });

      modalButtons.push({
        text: getFormElementDefinition(getRootFormElement(), 'modalCloseConfirmButton'),
        active: true,
        btnClass: getHelper().getDomElementClassName('buttonWarning'),
        name: 'confirm',
        trigger: function() {
          getPublisherSubscriber().publish('view/modal/close/perform', []);
          Modal.currentModal.trigger('modal-dismiss');
        }
      });

      Modal.show(
        getFormElementDefinition(getRootFormElement(), 'modalCloseDialogTitle'),
        getFormElementDefinition(getRootFormElement(), 'modalCloseDialogMessage'),
        Severity.warning,
        modalButtons
      );
    };

    /**
     * @public
     *
     * @param string
     * @param object
     * @return void
     */
    function showInsertElementsModal(publisherTopicName, configuration) {
      var html, template;

      template = getHelper().getTemplate('templateInsertElements');
      if (template.length > 0) {
        html = $(template.html());
        _insertElementsModalSetup(html, publisherTopicName, configuration);

        Modal.show(
          getFormElementDefinition(getRootFormElement(), 'modalInsertElementsDialogTitle'),
          $(html),
          Severity.info
        );
      }
    };

    /**
     * @public
     *
     * @param string
     * @return void
     */
    function showInsertPagesModal(publisherTopicName) {
      var html, template;

      template = getHelper().getTemplate('templateInsertPages');
      if (template.length > 0) {
        html = $(template.html());
        _insertElementsModalSetup(html, publisherTopicName);

        Modal.show(
          getFormElementDefinition(getRootFormElement(), 'modalInsertPagesDialogTitle'),
          $(html),
          Severity.info
        );
      }
    };

    /**
     * @public
     *
     * @param object
     * @return void
     */
    function showValidationErrorsModal(validationResults) {
      var html, template, modalButtons = [];

      modalButtons.push({
        text: getFormElementDefinition(getRootFormElement(), 'modalValidationErrorsConfirmButton'),
        active: true,
        btnClass: getHelper().getDomElementClassName('buttonDefault'),
        name: 'confirm',
        trigger: function() {
          Modal.currentModal.trigger('modal-dismiss');
        }
      });

      template = getHelper().getTemplate('templateValidationErrors');
      if (template.length > 0) {
        html = $(template.html()).clone();
        _validationErrorsModalSetup(html, validationResults);

        Modal.show(
          getFormElementDefinition(getRootFormElement(), 'modalValidationErrorsDialogTitle'),
          html,
          Severity.error,
          modalButtons
        );
      }
    }

    /**
     * @public
     *
     * @param object
     * @param object
     * @return this
     */
    function bootstrap(formEditorApp, configuration) {
      _formEditorApp = formEditorApp;
      _configuration = $.extend(true, _defaultConfiguration, configuration || {});
      _helperSetup();
      return this;
    };

    /**
     * Publish the public methods.
     * Implements the "Revealing Module Pattern".
     */
    return {
      bootstrap: bootstrap,
      showCloseConfirmationModal: showCloseConfirmationModal,
      showInsertElementsModal: showInsertElementsModal,
      showInsertPagesModal: showInsertPagesModal,
      showRemoveCollectionElementModal: showRemoveCollectionElementModal,
      showRemoveFormElementModal: showRemoveFormElementModal,
      showValidationErrorsModal: showValidationErrorsModal
    };
  })($, Helper, Modal, Severity, Icons);
});
