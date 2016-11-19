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
 * Module: TYPO3/CMS/Form/Backend/FormEditor/InspectorComponent
 */

/**
 * Add legacy functions to be accessible in the global scope.
 * This is needed by TYPO3/CMS/Recordlist/ElementBrowser
 */
var setFormValueFromBrowseWin;

define(['jquery',
        'TYPO3/CMS/Form/Backend/FormEditor/Helper',
        'TYPO3/CMS/Backend/Icons',
        'TYPO3/CMS/Backend/Notification',
        'TYPO3/CMS/Form/Backend/Vendor/jquery.mjs.nestedSortable'
        ], function($, Helper, Icons, Notification) {
        'use strict';

    return (function($, Helper, Icons, Notification) {

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
                buttonFormElementRemove: 't3-form-remove-element-button',
                collectionElement: 't3-form-collection-element',
                finisherEditorPrefix: 't3-form-inspector-finishers-editor-',
                inspectorEditor: 'form-editor',
                validatorEditorPrefix: 't3-form-inspector-validators-editor-'
            },
            domElementDataAttributeNames: {
                contentElementSelectorTarget: 'data-insert-target',
                finisher: 'data-finisher-identifier',
                validator: 'data-validator-identifier'
            },
            domElementDataAttributeValues: {
                collapse: 'actions-view-table-expand',
                editorControlsWrapper: 'inspectorEditorControlsWrapper',
                formElementHeaderEditor: 'inspectorFormElementHeaderEditor',
                iconPage: 'apps-pagetree-page-default',
                iconTtContent: 'mimetypes-x-content-text',
                inspector: 'inspector',
                'Inspector-CheckboxEditor': 'Inspector-CheckboxEditor',
                'Inspector-CollectionElementHeaderEditor': 'Inspector-CollectionElementHeaderEditor',
                'Inspector-FinishersEditor': 'Inspector-FinishersEditor',
                'Inspector-FormElementHeaderEditor': 'Inspector-FormElementHeaderEditor',
                'Inspector-PropertyGridEditor': 'Inspector-PropertyGridEditor',
                'Inspector-RemoveElementEditor': 'Inspector-RemoveElementEditor',
                'Inspector-RequiredValidatorEditor': 'Inspector-RequiredValidatorEditor',
                'Inspector-SingleSelectEditor': 'Inspector-SingleSelectEditor',
                'Inspector-TextareaEditor': 'Inspector-TextareaEditor',
                'Inspector-TextEditor': 'Inspector-TextEditor',
                'Inspector-Typo3WinBrowserEditor': 'Inspector-Typo3WinBrowserEditor',
                'Inspector-ValidatorsEditor': 'Inspector-ValidatorsEditor',
                inspectorFinishers: 'inspectorFinishers',
                inspectorValidators: 'inspectorValidators',
                propertyGridEditorAddRow: 'addRow',
                propertyGridEditorAddRowItem: 'addRowItem',
                propertyGridEditorContainer: 'propertyGridContainer',
                propertyGridEditorDeleteRow: 'deleteRow',
                propertyGridEditorLabel: 'label',
                propertyGridEditorRowItem: 'rowItem',
                propertyGridEditorSelectValue: 'selectValue',
                propertyGridEditorSortRow: 'sortRow',
                propertyGridEditorValue: 'value'
            },
            domElementIdNames: {
                finisherPrefix: 't3-form-inspector-finishers-',
                validatorPrefix: 't3-form-inspector-validators-'
            },
            isSortable: true
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
         * @private
         *
         * @return object
         */
        function getViewModel() {
            return getFormEditorApp().getViewModel();
        };

        /**
         * @private
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
        function getCurrentlySelectedFormElement() {
            return getFormEditorApp().getCurrentlySelectedFormElement();
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
         * @private
         *
         * @param object
         * @param object
         * @param string
         * @param string
         * @return void
         * @publish view/inspector/editor/insert/perform
         */
        function _renderEditorDispatcher(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            switch (editorConfiguration['templateName']) {
                case 'Inspector-FormElementHeaderEditor':
                    renderFormElementHeaderEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-CollectionElementHeaderEditor':
                    renderCollectionElementHeaderEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-TextEditor':
                    renderTextEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-FinishersEditor':
                    renderCollectionElementSelectionEditor(
                        'finishers',
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-ValidatorsEditor':
                    renderCollectionElementSelectionEditor(
                        'validators',
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-RemoveElementEditor':
                    renderRemoveElementEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-RequiredValidatorEditor':
                    renderRequiredValidatorEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-CheckboxEditor':
                    renderCheckboxEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-SingleSelectEditor':
                    renderSingleSelectEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-PropertyGridEditor':
                    renderPropertyGridEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-TextareaEditor':
                    renderTextareaEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
                case 'Inspector-Typo3WinBrowserEditor':
                    renderTypo3WinBrowserEditor(
                        editorConfiguration,
                        editorHtml,
                        collectionElementIdentifier,
                        collectionName
                    );
                    break;
            }
            getPublisherSubscriber().publish('view/inspector/editor/insert/perform', [
                editorConfiguration, editorHtml, collectionElementIdentifier, collectionName
            ]);
        };

        /**
         * @private
         *
         * opens a popup window with the element browser
         *
         * @param string mode
         * @param string params
         * @param int width
         * @param int height
         */
        function _openTypo3WinBrowser(mode, params, width, height) {
            var openedPopupWindow, url;
            url = TYPO3.settings.FormEditor.typo3WinBrowserUrl
                + '&mode=' + mode + '&bparams=' + params;
            openedPopupWindow = window.open(
                url,
                'Typo3WinBrowser',
                'height=' + height + ',width=' + width + ',status=0,menubar=0,resizable=1,scrollbars=1'
            );
            openedPopupWindow.focus();
        };

        /**
         * @private
         *
         * @param string
         * @param string
         * @return object
         */
        function _getCollectionElementClass(collectionName, collectionElementIdentifier) {
            if (collectionName === 'finishers') {
                return getHelper()
                    .getDomElementClassName('finisherEditorPrefix') + collectionElementIdentifier;
            } else {
                return getHelper()
                    .getDomElementClassName('validatorEditorPrefix') + collectionElementIdentifier;
            }
        };

        /**
         * @private
         *
         * @param string
         * @param string
         * @param bool
         * @return object
         */
        function _getCollectionElementId(collectionName, collectionElementIdentifier, asSelector) {
            if (collectionName === 'finishers') {
                return getHelper()
                    .getDomElementIdName('finisherPrefix', asSelector) + collectionElementIdentifier;
            } else {
                return getHelper()
                    .getDomElementIdName('validatorPrefix', asSelector) + collectionElementIdentifier;
            }
        };

        /**
         * @private
         *
         * @param object
         * @param string
         * @return void
         */
        function _addSortableCollectionElementsEvents(sortableDomElement, collectionName) {
            sortableDomElement.addClass(getHelper().getDomElementClassName('sortable')).sortable({
                revert: 'true',
                items: getHelper().getDomElementClassName('collectionElement', true),
                cancel: getHelper().getDomElementClassName('jQueryUiStateDisabled', true) + ',input,select',
                delay: 200,
                update: function(e, o) {
                    var dataAttributeName, nextCollectionElementIdentifier, movedCollectionElementIdentifier, previousCollectionElementIdentifier;

                    if (collectionName === 'finishers') {
                        dataAttributeName = getHelper().getDomElementDataAttribute('finisher');
                    } else {
                        dataAttributeName = getHelper().getDomElementDataAttribute('validator');
                    }

                    movedCollectionElementIdentifier = $(o.item).attr(dataAttributeName);
                    previousCollectionElementIdentifier = $(o.item)
                        .prevAll(getHelper().getDomElementClassName('collectionElement', true))
                        .first()
                        .attr(dataAttributeName);
                    nextCollectionElementIdentifier = $(o.item)
                        .nextAll(getHelper().getDomElementClassName('collectionElement', true))
                        .first()
                        .attr(dataAttributeName);

                    getPublisherSubscriber().publish('view/inspector/collectionElements/dnd/update', [
                        movedCollectionElementIdentifier,
                        previousCollectionElementIdentifier,
                        nextCollectionElementIdentifier,
                        collectionName
                    ]);
                }
            });
        };

        /**
         * @private
         *
         * @param object editorHtml
         * @param bool multiSelection
         * @param string propertyPath
         * @param string propertyPathPrefix
         * @return void
         */
        function _setPropertyGridData(editorHtml, multiSelection, propertyPath, propertyPathPrefix) {
            var defaultValue, newPropertyData;

            if (multiSelection) {
                defaultValue = [];

                $(  getHelper().getDomElementDataIdentifierSelector('propertyGridEditorContainer') + ' ' +
                    getHelper().getDomElementDataIdentifierSelector('propertyGridEditorSelectValue') + ':checked',
                    $(editorHtml)
                ).each(function(i) {
                    defaultValue.push(
                        $(this)
                            .closest(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'))
                            .find(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorValue'))
                            .val()
                        );
                });
                getCurrentlySelectedFormElement().set(propertyPathPrefix + 'defaultValue', defaultValue);
            } else {
                getCurrentlySelectedFormElement().set(
                    propertyPathPrefix + 'defaultValue',
                    $(
                        getHelper().getDomElementDataIdentifierSelector('propertyGridEditorContainer') + ' ' +
                        getHelper().getDomElementDataIdentifierSelector('propertyGridEditorSelectValue') + ':checked',
                        $(editorHtml)
                    ).first()
                        .closest(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'))
                        .find(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorValue'))
                        .val(),
                    true
                );
            }

            newPropertyData = [];
            $(
                getHelper().getDomElementDataIdentifierSelector('propertyGridEditorContainer') + ' ' +
                getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'),
                $(editorHtml)
            ).each(function(i) {
                var value, label, tmpObject;

                value = $(this)
                    .find(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorValue'))
                    .val();
                label = $(this)
                    .find(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorLabel'))
                    .val();

                if ('' === value) {
                    value = label;
                }

                tmpObject = {};
                tmpObject[value] = label;
                newPropertyData.push({
                    _label: label,
                    _value: value
                });
            });

            getCurrentlySelectedFormElement().set(propertyPathPrefix + propertyPath, newPropertyData);
        };

        /**
         * @private
         *
         * @param object
         * @return object
         */
        function _getEditorControlsWrapperDomElement(editorDomElement) {
            return $(getHelper().getDomElementDataIdentifierSelector('editorControlsWrapper'), $(editorDomElement));
        };

        /**
         * @private
         *
         * @param string
         * @param object
         * @return void
         */
        function _validateCollectionElement(propertyPath, editorHtml) {
            var hasError, propertyPrefix, validationResults;

            validationResults = getFormEditorApp().validateCurrentlySelectedFormElementProperty(propertyPath);

            if (validationResults.length > 0) {
                getHelper()
                    .getTemplatePropertyDomElement('validationErrors', editorHtml)
                    .text(validationResults[0]);
                getViewModel().setElementValidationErrorClass(
                    getHelper().getTemplatePropertyDomElement('validationErrors', editorHtml)
                );
                getViewModel().setElementValidationErrorClass(
                    _getEditorControlsWrapperDomElement(editorHtml),
                    'hasError'
                );
            } else {
                getHelper().getTemplatePropertyDomElement('validationErrors', editorHtml).text('');
                getViewModel().removeElementValidationErrorClass(
                    getHelper().getTemplatePropertyDomElement('validationErrors', editorHtml)
                );
                getViewModel().removeElementValidationErrorClass(
                    _getEditorControlsWrapperDomElement(editorHtml),
                    'hasError'
                );
            }

            validationResults = getFormEditorApp().validateFormElement(getCurrentlySelectedFormElement());
            propertyPrefix = propertyPath.split('.');
            propertyPrefix = propertyPrefix[0] + '.' + propertyPrefix[1];

            hasError = false;
            for (var i = 0, len = validationResults.length; i < len; ++i) {
                if (
                    validationResults[i]['propertyPath'].indexOf(propertyPrefix, 0) === 0
                    && validationResults[i]['validationResults']
                    && validationResults[i]['validationResults'].length > 0
                ) {
                    hasError = true;
                    break;
                }
            }

            if (hasError) {
                getViewModel().setElementValidationErrorClass(
                    _getEditorControlsWrapperDomElement(editorHtml).closest(getHelper().getDomElementClassName('collectionElement', true))
                );
            } else {
                getViewModel().removeElementValidationErrorClass(
                    _getEditorControlsWrapperDomElement(editorHtml).closest(getHelper().getDomElementClassName('collectionElement', true))
                );
            }
        };

        /* *************************************************************
         * Public Methodes
         * ************************************************************/

        /**
         * @public
         *
         * callback from TYPO3/CMS/Recordlist/ElementBrowser
         *
         * @param string fieldReference
         * @param string elValue
         * @param string elName
         * @return void
         */
        setFormValueFromBrowseWin = function(fieldReference, elValue, elName) {
            var result;
            result = elValue.split('_');
            
            $(getHelper().getDomElementDataAttribute('contentElementSelectorTarget', 'bracesWithKeyValue', [fieldReference]))
                .val(result.pop())
                .trigger('paste');
        };

        /**
         * @public
         *
         * @return object
         */
        function getInspectorDomElement() {
            return $(getHelper().getDomElementDataIdentifierSelector('inspector'));
        };

        /**
         * @public
         *
         * @return object
         */
        function getFinishersContainerDomElement() {
            return $(getHelper().getDomElementDataIdentifierSelector('inspectorFinishers'), getInspectorDomElement());
        };

        /**
         * @public
         *
         * @return object
         */
        function getValidatorsContainerDomElement() {
            return $(getHelper().getDomElementDataIdentifierSelector('inspectorValidators'), getInspectorDomElement());
        };

        /**
         * @public
         *
         * @param string
         * @param string
         * @return object
         */
        function getCollectionElementDomElement(collectionName, collectionElementIdentifier) {
            if (collectionName === 'finishers') {
                return $(getHelper().getDomElementDataAttribute(
                            'finisher',
                            'bracesWithKeyValue',
                            [collectionElementIdentifier]
                        ), getFinishersContainerDomElement());
            } else {
                return $(getHelper().getDomElementDataAttribute(
                        'validator',
                        'bracesWithKeyValue',
                        [collectionElementIdentifier]
                    ), getValidatorsContainerDomElement());
            }
        };

        /**
         * @public
         *
         * @param object
         * @param function
         * @return void
         */
        function renderEditors(formElement, callback) {
            var formElementTypeDefinition;
            if (getUtility().isUndefinedOrNull(formElement)) {
                formElement = getCurrentlySelectedFormElement();
            }

            getInspectorDomElement().off().empty();

            formElementTypeDefinition = getFormElementDefinition(formElement);
            if ('array' !== $.type(formElementTypeDefinition['editors'])) {
                return;
            }

            for (var i = 0, len = formElementTypeDefinition['editors'].length; i < len; ++i) {
                var html, template;

                template = getHelper()
                    .getTemplate(formElementTypeDefinition['editors'][i]['templateName'])
                    .clone();
                if (!template.length) {
                    continue;
                }
                html = $(template.html());

                $(html)
                    .first()
                    .addClass(getHelper().getDomElementClassName('inspectorEditor'));
                getInspectorDomElement().append($(html));

                _renderEditorDispatcher(formElementTypeDefinition['editors'][i], html);
            }

            if ('function' === $.type(callback)) {
                callback();
            }
        };

        /**
         * @public
         *
         * @param string collectionName
         * @param string collectionElementIdentifier
         * @return void
         * @publish view/inspector/collectionElements/dnd/update
         * @throws 1478354853
         * @throws 1478354854
         */
        function renderCollectionElementEditors(collectionName, collectionElementIdentifier) {
            var collapseWrapper, collectionContainer, collectionContainerElementWrapper, collectionElementConfiguration, collectionElementEditorsLength;

            assert(
                getUtility().isNonEmptyString(collectionName),
                'Invalid parameter "collectionName"',
                1478354853
            );
            assert(
                getUtility().isNonEmptyString(collectionElementIdentifier),
                'Invalid parameter "collectionElementIdentifier"',
                1478354854
            );

            collectionElementConfiguration = getFormEditorApp().getPropertyCollectionElementConfiguration(
                collectionElementIdentifier,
                collectionName
            );
            if ('array' !== $.type(collectionElementConfiguration['editors'])) {
                return;
            }

            collectionContainerElementWrapper = $('<div></div>').
                addClass(getHelper().getDomElementClassName('collectionElement'));
            if (collectionName === 'finishers') {
                collectionContainer = getFinishersContainerDomElement();
                collectionContainerElementWrapper
                    .attr(getHelper().getDomElementDataAttribute('finisher'), collectionElementIdentifier);
            } else {
                collectionContainer = getValidatorsContainerDomElement();
                collectionContainerElementWrapper
                    .attr(getHelper().getDomElementDataAttribute('validator'), collectionElementIdentifier);
            }
            collectionContainer.append(collectionContainerElementWrapper);

            collectionElementEditorsLength = collectionElementConfiguration['editors'].length;
            if (
                collectionElementEditorsLength > 0
                && collectionElementConfiguration['editors'][0]['identifier'] === 'header'
            ) {
                collapseWrapper = $('<div role="tabpanel"></div>')
                    .addClass('panel-collapse collapse')
                    .prop('id', _getCollectionElementId(
                        collectionName,
                        collectionElementIdentifier
                    ));
            }

            for (var i = 0; i < collectionElementEditorsLength; ++i) {
                var html, template;

                template = getHelper()
                    .getTemplate(collectionElementConfiguration['editors'][i]['templateName'])
                    .clone();
                if (!template.length) {
                    continue;
                }
                html = $(template.html());

                $(html).first()
                    .addClass(_getCollectionElementClass(
                        collectionName,
                        collectionElementConfiguration['editors'][i]['identifier']
                    ))
                    .addClass(getHelper().getDomElementClassName('inspectorEditor'));

                if (i === 0 && collapseWrapper) {
                    getCollectionElementDomElement(collectionName, collectionElementIdentifier)
                        .append(html)
                        .append(collapseWrapper);
                } else if (
                    i === (collectionElementEditorsLength - 1)
                    && collapseWrapper
                    && collectionElementConfiguration['editors'][i]['identifier'] === 'removeButton'
                ) {
                    getCollectionElementDomElement(collectionName, collectionElementIdentifier).append(html);
                } else if (i > 0 && collapseWrapper) {
                    collapseWrapper.append(html);
                } else {
                    getCollectionElementDomElement(collectionName, collectionElementIdentifier).append(html);
                }

                _renderEditorDispatcher(
                    collectionElementConfiguration['editors'][i],
                    html,
                    collectionElementIdentifier,
                    collectionName
                );
            }

            if (
                collectionElementEditorsLength === 2
                && collectionElementConfiguration['editors'][0]['identifier'] === 'header'
                && collectionElementConfiguration['editors'][1]['identifier'] === 'removeButton'
            ) {
                $(getHelper().getDomElementDataIdentifierSelector('collapse'), collectionContainerElementWrapper).remove();
            }

            if (_configuration['isSortable']) {
                _addSortableCollectionElementsEvents(collectionContainer, collectionName);
            }
        };

       /**
         * @public
         *
         * @string collectionName
         * @param object editorConfiguration
         * @param object editorHtml
         * @return void
         * @publish view/inspector/collectionElement/existing/selected
         * @publish view/inspector/collectionElement/new/selected
         * @throws 1475423098
         * @throws 1475423099
         * @throws 1475423100
         * @throws 1475423101
         * @throws 1478362968
         */
        function renderCollectionElementSelectionEditor(collectionName, editorConfiguration, editorHtml) {
            var alreadySelectedCollectionElements, selectElement, collectionContainer;
            assert(
                getUtility().isNonEmptyString(collectionName),
                'Invalid configuration "collectionName"',
                1478362968
            );
            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1475423098
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1475423099
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1475423100
            );
            assert(
                'array' === $.type(editorConfiguration['selectOptions']),
                'Invalid configuration "selectOptions"',
                1475423101
            );

            if (collectionName === 'finishers') {
                collectionContainer = getFinishersContainerDomElement();
                alreadySelectedCollectionElements = getRootFormElement().get(collectionName);
            } else {
                collectionContainer = getValidatorsContainerDomElement();
                alreadySelectedCollectionElements = getCurrentlySelectedFormElement().get(collectionName);
            }

            collectionContainer.off().empty();

            getHelper().getTemplatePropertyDomElement('label', editorHtml).text(editorConfiguration['label']);
            selectElement = getHelper().getTemplatePropertyDomElement('selectOptions', editorHtml);

            if (!getUtility().isUndefinedOrNull(alreadySelectedCollectionElements)) {
                for (var i = 0, len = alreadySelectedCollectionElements.length; i < len; ++i) {
                    getPublisherSubscriber().publish('view/inspector/collectionElement/existing/selected', [
                        alreadySelectedCollectionElements[i]['identifier'],
                        collectionName
                    ]);
                }
            }

            for (var i = 0, len1 = editorConfiguration['selectOptions'].length; i < len1; ++i) {
                var appendOption = true;
                if (!getUtility().isUndefinedOrNull(alreadySelectedCollectionElements)) {
                    for (var j = 0, len2 = alreadySelectedCollectionElements.length; j < len2; ++j) {
                        if (alreadySelectedCollectionElements[j]['identifier'] === editorConfiguration['selectOptions'][i]['value']) {
                            appendOption = false;
                            break;
                        }
                    }
                }
                if (appendOption) {
                    selectElement.append(new Option(
                        editorConfiguration['selectOptions'][i]['label'],
                        editorConfiguration['selectOptions'][i]['value']
                    ));
                }
            }

            selectElement.on('change', function() {
                if ($(this).val() !== '') {
                    var value = $(this).val();
                    $('option[value="' + value + '"]', $(this)).remove();

                    getFormEditorApp().getPublisherSubscriber().publish(
                        'view/inspector/collectionElement/new/selected',
                        [value, collectionName]
                    );
                }
            });
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1475421525
         * @throws 1475421526
         * @throws 1475421527
         * @throws 1475421528
         */
        function renderFormElementHeaderEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            assert('object' === $.type(editorConfiguration), 'Invalid parameter "editorConfiguration"', 1475421525);
            assert('object' === $.type(editorHtml), 'Invalid parameter "editorHtml"', 1475421526);

            Icons.getIcon(
                getFormElementDefinition(getCurrentlySelectedFormElement(), 'iconIdentifier'),
                Icons.sizes.small,
                null,
                Icons.states.default
            ).done(function(icon) {
                getHelper().getTemplatePropertyDomElement('header-label', editorHtml)
                    .append($(icon).addClass(getHelper().getDomElementClassName('icon')))
                    .append(buildTitleByFormElement());
            });
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1475421257
         * @throws 1475421258
         * @throws 1475421259
         */
        function renderCollectionElementHeaderEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            var collectionElementConfiguration, setData;

            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1475421258
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1475421257
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1475421259
            );

            setData = function(icon) {
                getHelper()
                    .getTemplatePropertyDomElement('header-label', editorHtml)
                    .prepend($(icon));

                Icons.getIcon(
                    getHelper().getDomElementDataAttributeValue('collapse'),
                    Icons.sizes.small,
                    null,
                    Icons.states.default,
                    Icons.markupIdentifiers.inline
                ).done(function(icon) {
                    var iconWrap;
                    iconWrap = $('<a></a>')
                        .attr('href', _getCollectionElementId(collectionName, collectionElementIdentifier, true))
                        .attr('data-toggle', 'collapse')
                        .attr('aria-expanded', 'true')
                        .attr('aria-controls', _getCollectionElementId(collectionName, collectionElementIdentifier))
                        .addClass('collapsed')
                        .append($(icon));

                    getHelper()
                        .getTemplatePropertyDomElement('header-label', editorHtml)
                        .prepend(iconWrap);
                });
            };

            collectionElementConfiguration = getFormEditorApp().getFormEditorDefinition(collectionName, collectionElementIdentifier);
            if (collectionName === 'validators') {
                Icons.getIcon(
                    collectionElementConfiguration['iconIdentifier'],
                    Icons.sizes.small,
                    null,
                    Icons.states.default
                ).done(function(icon) {
                    setData(icon);
                });
            } else {
                Icons.getIcon(
                    collectionElementConfiguration['iconIdentifier'],
                    Icons.sizes.small,
                    null,
                    Icons.states.default
                ).done(function(icon) {
                    setData(icon);
                });
            }

            if (editorConfiguration['label']) {
                getHelper().getTemplatePropertyDomElement('label', editorHtml).append(editorConfiguration['label']);
            }
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1475421053
         * @throws 1475421054
         * @throws 1475421055
         * @throws 1475421056
         */
        function renderTextEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            var propertyPath, propertyData;
            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1475421053
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1475421054
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1475421055
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['propertyPath']),
                'Invalid configuration "propertyPath"',
                1475421056
            );

            getHelper()
                .getTemplatePropertyDomElement('label', editorHtml)
                .append(editorConfiguration['label']);
            if (getUtility().isNonEmptyString(editorConfiguration['fieldExplanationText'])) {
                getHelper()
                    .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
                    .text(editorConfiguration['fieldExplanationText']);
            } else {
                getHelper()
                    .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
                    .remove();
            }

            propertyPath = getFormEditorApp().buildPropertyPath(
                editorConfiguration['propertyPath'],
                collectionElementIdentifier,
                collectionName
            );
            propertyData = getCurrentlySelectedFormElement().get(propertyPath);

            _validateCollectionElement(propertyPath, editorHtml);

            getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).val(propertyData);

            getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).on('keyup paste', function() {
                getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
                _validateCollectionElement(propertyPath, editorHtml);
            });
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1475421048
         * @throws 1475421049
         * @throws 1475421050
         * @throws 1475421051
         * @throws 1475421052
         */
        function renderSingleSelectEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            var propertyData, propertyPath, selectElement;
            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1475421048
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1475421049
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1475421050
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['propertyPath']),
                'Invalid configuration "propertyPath"',
                1475421051
            );
            assert(
                'array' === $.type(editorConfiguration['selectOptions']),
                'Invalid configuration "selectOptions"',
                1475421052
            );

            propertyPath = getFormEditorApp().buildPropertyPath(
                editorConfiguration['propertyPath'],
                collectionElementIdentifier,
                collectionName
            );

            getHelper()
                .getTemplatePropertyDomElement('label', editorHtml)
                .append(editorConfiguration['label']);

            selectElement = getHelper()
                .getTemplatePropertyDomElement('selectOptions', editorHtml);

            propertyData = getCurrentlySelectedFormElement().get(propertyPath);

            for (var i = 0, len = editorConfiguration['selectOptions'].length; i < len; ++i) {
                var option;

                if (editorConfiguration['selectOptions'][i]['value'] === propertyData) {
                    option = new Option(editorConfiguration['selectOptions'][i]['label'], i, false, true);
                } else {
                    option = new Option(editorConfiguration['selectOptions'][i]['label'], i);
                }
                $(option).data({value: editorConfiguration['selectOptions'][i]['value']});
                selectElement.append(option);
            }

            selectElement.on('change', function() {
                getCurrentlySelectedFormElement().set(propertyPath, $('option:selected', $(this)).data('value'));
            });
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1475419226
         * @throws 1475419227
         * @throws 1475419228
         * @throws 1475419229
         * @throws 1475419230
         * @throws 1475419231
         * @throws 1475419232
         */
        function renderPropertyGridEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            var addRowTemplate, defaultValue, multiSelection, propertyData, propertyPathPrefix, rowItemTemplate, setData;
            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1475419226
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1475419227
            );
            assert(
                'boolean' === $.type(editorConfiguration['enableAddRow']),
                'Invalid configuration "enableAddRow"',
                1475419228
            );
            assert(
                'boolean' === $.type(editorConfiguration['enableDeleteRow']),
                'Invalid configuration "enableDeleteRow"',
                1475419230
            );
            assert(
                'boolean' === $.type(editorConfiguration['isSortable']),
                'Invalid configuration "isSortable"',
                1475419229
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['propertyPath']),
                'Invalid configuration "propertyPath"',
                1475419231
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1475419232
            );

            getHelper().getTemplatePropertyDomElement('label', editorHtml).append(editorConfiguration['label']);
            propertyPathPrefix = getFormEditorApp().buildPropertyPath(
                undefined,
                collectionElementIdentifier,
                collectionName,
                undefined,
                true
            );
            if (getUtility().isNonEmptyString(propertyPathPrefix)) {
                propertyPathPrefix = propertyPathPrefix + '.';
            }

            if (getUtility().isUndefinedOrNull(editorConfiguration['multiSelection'])) {
                multiSelection = false;
            } else {
                multiSelection = !!editorConfiguration['multiSelection'];
            }

            rowItemTemplate = $(
                getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'),
                $(editorHtml)
            ).clone();
            $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'), $(editorHtml)).remove();

            if (!!editorConfiguration['enableDeleteRow']) {
                $(  getHelper().getDomElementDataIdentifierSelector('propertyGridEditorDeleteRow'),
                    $(rowItemTemplate)
                ).on('click', function() {
                    if ($(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'), $(editorHtml)).length > 1) {
                        $(this)
                            .closest(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'))
                            .off()
                            .empty()
                            .remove();

                        _setPropertyGridData(
                            $(editorHtml),
                            multiSelection,
                            editorConfiguration['propertyPath'],
                            propertyPathPrefix
                        );
                    } else {
                        Notification.error(
                            editorConfiguration['removeLastAvailableRowFlashMessageTitle'],
                            editorConfiguration['removeLastAvailableRowFlashMessageMessage'],
                            2
                        );
                    }
                });
            } else {
                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorDeleteRow'), $(rowItemTemplate))
                    .parent()
                    .off()
                    .empty();
            }

            if (!!editorConfiguration['isSortable']) {
                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorContainer'), $(editorHtml))
                    .addClass(getHelper().getDomElementClassName('sortable'))
                    .sortable({
                        revert: 'true',
                        items: getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'),
                        update: function(e, o) {
                            _setPropertyGridData($(editorHtml), multiSelection, editorConfiguration['propertyPath'], propertyPathPrefix);
                        }
                    });
            } else {
                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorSortRow'), $(rowItemTemplate))
                    .parent()
                    .off()
                    .empty();
            }

            $(  getHelper().getDomElementDataIdentifierSelector('propertyGridEditorSelectValue'),
                $(rowItemTemplate)
            ).on('change', function() {
                if (!multiSelection) {
                    $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorSelectValue') + ':checked', $(editorHtml))
                        .not($(this))
                        .prop('checked', false);
                }
                _setPropertyGridData($(editorHtml), multiSelection, editorConfiguration['propertyPath'], propertyPathPrefix);
            });

            $(  getHelper().getDomElementDataIdentifierSelector('propertyGridEditorLabel') + ',' +
                getHelper().getDomElementDataIdentifierSelector('propertyGridEditorValue'),
                $(rowItemTemplate)
            ).on('keyup paste', function() {
                _setPropertyGridData($(editorHtml), multiSelection, editorConfiguration['propertyPath'], propertyPathPrefix);
            });

            $(  getHelper().getDomElementDataIdentifierSelector('propertyGridEditorLabel'),
                $(rowItemTemplate)
            ).on('focusout', function() {
                if ('' === $(this)
                                .closest(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'))
                                .find(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorValue'))
                                .val()
                ) {
                    $(this)
                        .closest(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorRowItem'))
                        .find(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorValue'))
                        .val($(this).val());
                }
            });

            if (!!editorConfiguration['enableAddRow']) {
                addRowTemplate = $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorAddRowItem'), $(editorHtml)).clone();
                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorAddRowItem'), $(editorHtml)).remove();

                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorAddRow'), $(addRowTemplate)).on('click', function() {
                    $(this)
                        .closest(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorAddRowItem'))
                        .before($(rowItemTemplate).clone(true, true));
                });
                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorContainer'), $(editorHtml))
                    .prepend($(addRowTemplate).clone(true, true));
            } else {
                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorAddRowItem'), $(editorHtml)).remove();
            }

            defaultValue = {};
            if (multiSelection) {
                if (!getUtility().isUndefinedOrNull(getCurrentlySelectedFormElement().get(propertyPathPrefix + 'defaultValue'))) {
                    defaultValue = getCurrentlySelectedFormElement().get(propertyPathPrefix + 'defaultValue');
                }
            } else {
                if (!getUtility().isUndefinedOrNull(getCurrentlySelectedFormElement().get(propertyPathPrefix + 'defaultValue'))) {
                    defaultValue = {0: getCurrentlySelectedFormElement().get(propertyPathPrefix + 'defaultValue')};
                }
            }
            propertyData = getCurrentlySelectedFormElement().get(propertyPathPrefix + editorConfiguration['propertyPath']) || {};

            setData = function(label, value) {
                var isPreselected, newRowTemplate;

                isPreselected = false;
                newRowTemplate = $(rowItemTemplate).clone(true, true);

                for (var defaultValueKey in defaultValue) {
                    if (!defaultValue.hasOwnProperty(defaultValueKey)) {
                        continue;
                    }
                    if (defaultValue[defaultValueKey] === value) {
                        isPreselected = true;
                        break;
                    }
                }

                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorLabel'), $(newRowTemplate)).val(label);
                $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorValue'), $(newRowTemplate)).val(value);
                if (isPreselected) {
                    $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorSelectValue'), $(newRowTemplate))
                        .prop('checked', true);
                }

                if (!!editorConfiguration['enableAddRow']) {
                    $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorAddRowItem'), $(editorHtml))
                        .before($(newRowTemplate));
                } else {
                    $(getHelper().getDomElementDataIdentifierSelector('propertyGridEditorContainer'), $(editorHtml))
                        .prepend($(newRowTemplate));
                }
            };

            if ('object' === $.type(propertyData)) {
                for (var propertyDataKey in propertyData) {
                    if (!propertyData.hasOwnProperty(propertyDataKey)) {
                        continue;
                    }
                    setData(propertyData[propertyDataKey], propertyDataKey);
                }
            } else {
                for (var i = 0, len = propertyData.length; i < len; ++i) {
                    setData(propertyData[i]['_label'], propertyData[i]['_value']);
                }
            }
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @publish view/inspector/collectionElement/new/selected
         * @publish view/inspector/removeCollectionElement/perform
         * @throws 1475417093
         * @throws 1475417094
         * @throws 1475417095
         * @throws 1475417096
         */
        function renderRequiredValidatorEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            var validatorIdentifier;
            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1475417093
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1475417094
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['validatorIdentifier']),
                'Invalid configuration "validatorIdentifier"',
                1475417095
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1475417096
            );

            validatorIdentifier = editorConfiguration['validatorIdentifier'];
            getHelper().getTemplatePropertyDomElement('label', editorHtml).append(editorConfiguration['label']);

            if (-1 !== getFormEditorApp().getIndexFromPropertyCollectionElement(validatorIdentifier, 'validators')) {
                $('input[type="checkbox"]', $(editorHtml)).prop('checked', true);
            }

            $('input[type="checkbox"]', $(editorHtml)).on('change', function() {
                if ($(this).is(":checked")) {
                    getPublisherSubscriber().publish(
                        'view/inspector/collectionElement/new/selected',
                        [validatorIdentifier, 'validators']
                    );
                } else {
                    getPublisherSubscriber().publish(
                        'view/inspector/removeCollectionElement/perform',
                        [validatorIdentifier, 'validators']
                    );                 
                }
            });
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1476218671
         * @throws 1476218672
         * @throws 1476218673
         * @throws 1476218674
         */
        function renderCheckboxEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            var propertyData, propertyPath;
            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1476218671
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1476218672
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1476218673
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['propertyPath']),
                'Invalid configuration "propertyPath"',
                1476218674
            );

            getHelper()
                .getTemplatePropertyDomElement('label', editorHtml)
                .append(editorConfiguration['label']);

            propertyPath = getFormEditorApp()
                .buildPropertyPath(editorConfiguration['propertyPath'], collectionElementIdentifier, collectionName);
            propertyData = getCurrentlySelectedFormElement().get(propertyPath);

            if (
                ('boolean' === $.type(propertyData) && propertyData)
                || propertyData === 'true'
                || propertyData === 1
                || propertyData === "1"
            ) {
                $('input[type="checkbox"]', $(editorHtml)).prop('checked', true);
            }

            $('input[type="checkbox"]', $(editorHtml)).on('change', function() {
                if ($(this).is(":checked")) {
                    getCurrentlySelectedFormElement().set(propertyPath, true);
                } else {
                    getCurrentlySelectedFormElement().set(propertyPath, false);
                }
            });
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1475412567
         * @throws 1475412568
         * @throws 1475416098
         * @throws 1475416099
         */
        function renderTextareaEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            var propertyPath, propertyData;
            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1475412567
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1475412568
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['propertyPath']),
                'Invalid configuration "propertyPath"',
                1475416098
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1475416099
            );

            propertyPath = getFormEditorApp()
                .buildPropertyPath(editorConfiguration['propertyPath'], collectionElementIdentifier, collectionName);

            getHelper()
                .getTemplatePropertyDomElement('label', editorHtml).append(editorConfiguration['label']);

            if (getUtility().isNonEmptyString(editorConfiguration['fieldExplanationText'])) {
                getHelper()
                    .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
                    .text(editorConfiguration['fieldExplanationText']);
            } else {
                getHelper()
                    .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
                    .remove();
            }

            propertyData = getCurrentlySelectedFormElement().get(propertyPath);
            $('textarea', $(editorHtml)).val(propertyData);

            $('textarea', $(editorHtml)).on('keyup paste', function() {
                getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
            });
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1477300587
         * @throws 1477300588
         * @throws 1477300589
         * @throws 1477300590
         * @throws 1477318981
         * @throws 1477319859
         */
        function renderTypo3WinBrowserEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            var iconType, propertyPath, propertyData;
            assert(
                'object' === $.type(editorConfiguration),
                'Invalid parameter "editorConfiguration"',
                1477300587
            );
            assert(
                'object' === $.type(editorHtml),
                'Invalid parameter "editorHtml"',
                1477300588
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['label']),
                'Invalid configuration "label"',
                1477300589
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['buttonLabel']),
                'Invalid configuration "buttonLabel"',
                1477318981
            );
            assert(
                getUtility().isNonEmptyString(editorConfiguration['propertyPath']),
                'Invalid configuration "propertyPath"',
                1477300590
            );
            assert(
                'tt_content' === editorConfiguration['browsableType'] || 'pages' === editorConfiguration['browsableType'],
                'Invalid configuration "browsableType"',
                1477319859
            );

            getHelper()
                .getTemplatePropertyDomElement('label', editorHtml)
                .append(editorConfiguration['label']);
            getHelper()
                .getTemplatePropertyDomElement('buttonLabel', editorHtml)
                .append(editorConfiguration['buttonLabel']);

            if (getUtility().isNonEmptyString(editorConfiguration['fieldExplanationText'])) {
                getHelper()
                    .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
                    .text(editorConfiguration['fieldExplanationText']);
            } else {
                getHelper()
                    .getTemplatePropertyDomElement('fieldExplanationText', editorHtml)
                    .remove();
            }

            $('form', $(editorHtml)).prop('name', editorConfiguration['propertyPath']);

            iconType = ('tt_content' === editorConfiguration['browsableType'])
                ? getHelper().getDomElementDataAttributeValue('iconTtContent')
                : getHelper().getDomElementDataAttributeValue('iconPage');
            Icons.getIcon(iconType, Icons.sizes.small).done(function(icon) {
                getHelper().getTemplatePropertyDomElement('image', editorHtml).append($(icon));
            });

            getHelper().getTemplatePropertyDomElement('onclick', editorHtml).on('click', function() {
                var insertTarget, randomIdentifier;

                randomIdentifier = Math.floor((Math.random() * 100000) + 1);
                insertTarget = $(this)
                    .closest(getHelper().getDomElementDataIdentifierSelector('editorControlsWrapper'))
                    .find(getHelper().getDomElementDataAttribute('contentElementSelectorTarget', 'bracesWithKey'));

                insertTarget.attr(getHelper().getDomElementDataAttribute('contentElementSelectorTarget'), randomIdentifier);
                _openTypo3WinBrowser('db', randomIdentifier + '|||' + editorConfiguration['browsableType']);
            });

            propertyPath = getFormEditorApp().buildPropertyPath(editorConfiguration['propertyPath'], collectionElementIdentifier, collectionName);
            propertyData = getCurrentlySelectedFormElement().get(propertyPath);

            _validateCollectionElement(propertyPath, editorHtml);
            getHelper()
                .getTemplatePropertyDomElement('propertyPath', editorHtml)
                .val(propertyData);

            getHelper().getTemplatePropertyDomElement('propertyPath', editorHtml).on('keyup paste', function() {
                getCurrentlySelectedFormElement().set(propertyPath, $(this).val());
                _validateCollectionElement(propertyPath, editorHtml);
            });
        };

        /**
         * @public
         *
         * @param object editorConfiguration
         * @param object editorHtml
         * @param string collectionElementIdentifier
         * @param string collectionName
         * @return void
         * @throws 1475412563
         * @throws 1475412564
         */
        function renderRemoveElementEditor(editorConfiguration, editorHtml, collectionElementIdentifier, collectionName) {
            assert('object' === $.type(editorConfiguration), 'Invalid parameter "editorConfiguration"', 1475412563);
            assert('object' === $.type(editorHtml), 'Invalid parameter "editorHtml"', 1475412564);

            if (getUtility().isUndefinedOrNull(collectionElementIdentifier)) {
                
                $('button', $(editorHtml))
                    .addClass(
                        getHelper().getDomElementClassName('buttonFormElementRemove') + ' ' +
                        getHelper().getDomElementClassName('buttonFormEditor')
                    );
            } else {
                $('button', $(editorHtml)).addClass(
                    getHelper().getDomElementClassName('buttonCollectionElementRemove')
                );
            }

            $('button', $(editorHtml)).on('click', function(e) {
                if (getUtility().isUndefinedOrNull(collectionElementIdentifier)) {
                    getViewModel().showRemoveFormElementModal();
                } else {
                    getViewModel().showRemoveCollectionElementModal(collectionElementIdentifier, collectionName);
                }
            });
        };

        /**
         * @public
         *
         * @param string content
         * @return void
         */
        function setFormElementHeaderEditorContent(content) {
            if (getFormEditorApp().getUtility().isUndefinedOrNull(content)) {
                content = buildTitleByFormElement();
            }

            $(getHelper()
                .getDomElementDataIdentifierSelector('formElementHeaderEditor'), getInspectorDomElement())
                .html(content);
        };

        /**
         * @public
         *
         * @param object
         * @return object
         * @throws 1478967319
         */
        function buildTitleByFormElement(formElement) {
            var label;
            if (getUtility().isUndefinedOrNull(formElement)) {
                formElement = getCurrentlySelectedFormElement();
            }
            assert('object' === $.type(formElement), 'Invalid parameter "formElement"', 1478967319);

            return $('<span></span>').text((formElement.get('label')
                ? formElement.get('label')
                : formElement.get('identifier')));          
        };

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
            buildTitleByFormElement: buildTitleByFormElement,
            getCollectionElementDomElement: getCollectionElementDomElement,
            getFinishersContainerDomElement: getFinishersContainerDomElement,
            getInspectorDomElement: getInspectorDomElement,
            getValidatorsContainerDomElement: getValidatorsContainerDomElement,
            renderCheckboxEditor: renderCheckboxEditor,
            renderCollectionElementEditors: renderCollectionElementEditors,
            renderCollectionElementHeaderEditor: renderCollectionElementHeaderEditor,
            renderCollectionElementSelectionEditor: renderCollectionElementSelectionEditor,
            renderEditors: renderEditors,
            renderFormElementHeaderEditor: renderFormElementHeaderEditor,
            renderPropertyGridEditor: renderPropertyGridEditor,
            renderRemoveElementEditor: renderRemoveElementEditor,
            renderRequiredValidatorEditor: renderRequiredValidatorEditor,
            renderSingleSelectEditor: renderSingleSelectEditor,
            renderTextareaEditor: renderTextareaEditor,
            renderTextEditor: renderTextEditor,
            renderTypo3WinBrowserEditor: renderTypo3WinBrowserEditor,
            setFormElementHeaderEditorContent: setFormElementHeaderEditorContent
        };
    })($, Helper, Icons, Notification);
});
