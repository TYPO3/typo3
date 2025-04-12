<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\CMS\Extbase\Mvc\ExtbaseRequestParameters;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper;

/**
 * Abstract Form ViewHelper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class will
 * automatically set the name and value of a form element.
 *
 * Note this set of ViewHelpers is tailored to be used only in extbase context.
 */
abstract class AbstractFormFieldViewHelper extends AbstractFormViewHelper
{
    protected ConfigurationManagerInterface $configurationManager;
    protected bool $respectSubmittedDataValue = false;

    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('name', 'string', 'Name of input tag');
        $this->registerArgument('value', 'mixed', 'Value of input tag');
        $this->registerArgument('property', 'string', 'Name of Object Property. If used in conjunction with <f:form object="...">, the "name" property will be ignored, while "value" can be used to specify a default field value instead of the object property value.');
    }

    /**
     * Getting the current configuration for respectSubmittedDataValue.
     */
    public function getRespectSubmittedDataValue(): bool
    {
        return $this->respectSubmittedDataValue;
    }

    /**
     * Define respectSubmittedDataValue to enable or disable the usage of the submitted values in the viewhelper.
     */
    public function setRespectSubmittedDataValue(bool $respectSubmittedDataValue): void
    {
        $this->respectSubmittedDataValue = $respectSubmittedDataValue;
    }

    /**
     * Get the name of this form element.
     * Either returns arguments['name'], or the correct name for Object Access.
     * In case property is something like bla.blubb (hierarchical), then [bla][blubb] is generated.
     */
    protected function getName(): string
    {
        $name = $this->getNameWithoutPrefix();
        return $this->prefixFieldName($name);
    }

    /**
     * Shortcut for retrieving the request from the controller context
     *
     * @return RequestInterface The extbase (!) request. All these VH's are extbase-only.
     */
    protected function getRequest(): RequestInterface
    {
        if (!$this->renderingContext->hasAttribute(ServerRequestInterface::class)
            || !$this->renderingContext->getAttribute(ServerRequestInterface::class) instanceof RequestInterface
        ) {
            throw new \RuntimeException(
                'Form ViewHelpers are Extbase specific and need an Extbase Request to work',
                1663617170
            );
        }
        return $this->renderingContext->getAttribute(ServerRequestInterface::class);
    }

    /**
     * Get the name of this form element, without prefix.
     */
    protected function getNameWithoutPrefix(): string
    {
        if ($this->isObjectAccessorMode()) {
            $formObjectName = $this->renderingContext->getViewHelperVariableContainer()->get(
                FormViewHelper::class,
                'formObjectName'
            );
            if (!empty($formObjectName)) {
                $propertySegments = explode('.', (string)($this->arguments['property'] ?? ''));
                $propertyPath = '';
                foreach ($propertySegments as $segment) {
                    $propertyPath .= '[' . $segment . ']';
                }
                $name = $formObjectName . $propertyPath;
            } else {
                $name = $this->arguments['property'] ?? '';
            }
        } else {
            $name = $this->arguments['name'] ?? '';
        }
        if ($this->hasArgument('value') &&
            is_object($this->arguments['value']) &&
            !$this->persistenceManager->isNewObject($this->arguments['value'])
        ) {
            $name .= '[__identity]';
        }
        return (string)$name;
    }

    /**
     * Returns the current value of this Form ViewHelper and converts it to an identifier string in case it's an object
     * The value is determined as follows:
     * * If property mapping errors occurred and the form is re-displayed, the *last submitted* value is returned
     * * If a "value" attribute was specified, this value is used (preferring an "override" from integrators)
     * * Else the bound property value is returned (only in objectAccessor-mode)
     *
     * Note: This method should *not* be used for form elements that must not change the value attribute, e.g. (radio) buttons and checkboxes.
     *
     * @return mixed Value
     */
    protected function getValueAttribute()
    {
        $value = null;

        if ($this->respectSubmittedDataValue) {
            $value = $this->getValueFromSubmittedFormData($value);
        } elseif ($this->hasArgument('value')) {
            $value = $this->arguments['value'];
        } elseif ($this->isObjectAccessorMode()) {
            $value = $this->getPropertyValue();
        }

        $value = $this->convertToPlainValue($value);
        return $value;
    }

    /**
     * If property mapping errors occurred and the form is re-displayed, the *last submitted* value is returned by this
     * method.
     *
     * Note:
     * This method should *not* be used for form elements that must not change the value attribute, e.g. (radio)
     * buttons and checkboxes. The default behaviour is not to use this method. You need to set
     * respectSubmittedDataValue to TRUE to enable the form data handling for the viewhelper.
     *
     * @param mixed $value
     * @return mixed Value
     */
    protected function getValueFromSubmittedFormData($value)
    {
        $submittedFormData = null;
        if ($this->hasMappingErrorOccurred()) {
            $submittedFormData = $this->getLastSubmittedFormData();
        }
        if ($submittedFormData !== null) {
            $value = $submittedFormData;
        } elseif ($this->hasArgument('value')) {
            $value = $this->arguments['value'];
        } elseif ($this->isObjectAccessorMode()) {
            $value = $this->getPropertyValue();
        }

        return $value;
    }

    /**
     * Converts an arbitrary value to a plain value
     *
     * @param mixed $value The value to convert
     * @return mixed
     */
    protected function convertToPlainValue($value)
    {
        if (is_object($value)) {
            if ($value instanceof DomainObjectInterface && $value->getUid() !== null) {
                // We prefer to use the `getUid()` method because this returns the properly overlaid identifier (defaultLanguageRecordUid).
                // Otherwise, an identifier would contain '[defaultLanguageRecordUid]_[localizedRecordUid]'. This in turn
                // will not properly trigger the select option "is selected" comparison.
                // @see SelectViewHelper->getOptionValueScalar()
                return $value->getUid();
            }
            $identifier = $this->persistenceManager->getIdentifierByObject($value);
            if ($identifier !== null) {
                return $identifier;
            }
        }
        return $value;
    }

    /**
     * Checks if a property mapping error has occurred in the last request.
     */
    protected function hasMappingErrorOccurred(): bool
    {
        /** @var ExtbaseRequestParameters $extbaseRequestParameters */
        $extbaseRequestParameters = $this->getRequest()->getAttribute('extbase');
        return $extbaseRequestParameters->getOriginalRequest() !== null;
    }

    /**
     * Get the form data which has last been submitted; only returns valid data in case
     * a property mapping error has occurred. Check with hasMappingErrorOccurred() before!
     *
     * @return mixed
     */
    protected function getLastSubmittedFormData()
    {
        $propertyPath = rtrim(preg_replace('/(\\]\\[|\\[|\\])/', '.', $this->getNameWithoutPrefix()) ?? '', '.');
        /** @var ExtbaseRequestParameters $extbaseRequestParameters */
        $extbaseRequestParameters = $this->getRequest()->getAttribute('extbase');
        $value = ObjectAccess::getPropertyPath(
            $extbaseRequestParameters->getOriginalRequest()->getArguments(),
            $propertyPath
        );
        return $value;
    }

    /**
     * Add additional identity properties in case the current property is hierarchical (of the form "bla.blubb").
     * Then, [bla][__identity] has to be generated as well.
     */
    protected function addAdditionalIdentityPropertiesIfNeeded(): void
    {
        if (!$this->isObjectAccessorMode()) {
            return;
        }

        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if (!$viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'formObject'
        )
        ) {
            return;
        }
        $propertySegments = explode('.', (string)($this->arguments['property'] ?? ''));
        // hierarchical property. If there is no "." inside (thus $propertySegments == 1), we do not need to do anything
        if (count($propertySegments) < 2) {
            return;
        }
        $formObject = $viewHelperVariableContainer->get(
            FormViewHelper::class,
            'formObject'
        );
        $objectName = $viewHelperVariableContainer->get(
            FormViewHelper::class,
            'formObjectName'
        );
        // If count == 2 -> we need to go through the for-loop exactly once
        $propertySegmentsCount = count($propertySegments);
        for ($i = 1; $i < $propertySegmentsCount; $i++) {
            $object = ObjectAccess::getPropertyPath($formObject, implode('.', array_slice($propertySegments, 0, $i)));
            if (!is_object($object)) {
                $object = null;
            }
            $objectName .= '[' . $propertySegments[$i - 1] . ']';
            $hiddenIdentityField = $this->renderHiddenIdentityField($object, $objectName);
            // Add the hidden identity field to the ViewHelperVariableContainer
            $additionalIdentityProperties = $viewHelperVariableContainer->get(
                FormViewHelper::class,
                'additionalIdentityProperties'
            );
            $additionalIdentityProperties[$objectName] = $hiddenIdentityField;
            $viewHelperVariableContainer->addOrUpdate(
                FormViewHelper::class,
                'additionalIdentityProperties',
                $additionalIdentityProperties
            );
        }
    }

    /**
     * Get the current property of the object bound to this form.
     *
     * @return mixed Value
     */
    protected function getPropertyValue()
    {
        if (!isset($this->arguments['property'])) {
            return null;
        }
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if (!$viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'formObject'
        )
        ) {
            return null;
        }
        $formObject = $viewHelperVariableContainer->get(
            FormViewHelper::class,
            'formObject'
        );
        return ObjectAccess::getPropertyPath($formObject, (string)$this->arguments['property']);
    }

    /**
     * Internal method which checks if we should evaluate a domain object or just output arguments['name']
     * and arguments['value']. Returns true if domoin object should be evaluated.
     */
    protected function isObjectAccessorMode(): bool
    {
        return $this->hasArgument('property') && $this->renderingContext->getViewHelperVariableContainer()->exists(
            FormViewHelper::class,
            'formObjectName'
        );
    }

    /**
     * Add a CSS class if this ViewHelper has errors
     */
    protected function setErrorClassAttribute(): void
    {
        if ($this->hasArgument('class')) {
            // @deprecated: Fallback layer for VH's that register 'class' as argument
            //              via registerUniversalTagAttributes(). Remove in v14. Make
            //              elseif() below if().
            $cssClass = $this->arguments['class'] . ' ';
        } elseif (isset($this->additionalArguments['class'])) {
            $cssClass = $this->additionalArguments['class'] . ' ';
        } else {
            $cssClass = '';
        }

        $mappingResultsForProperty = $this->getMappingResultsForProperty();
        if ($mappingResultsForProperty->hasErrors()) {
            if ($this->hasArgument('errorClass')) {
                $cssClass .= $this->arguments['errorClass'];
            } else {
                $cssClass .= 'error';
            }
            $this->tag->addAttribute('class', $cssClass);
        }
    }

    /**
     * Get errors for the property and form name of this ViewHelper
     */
    protected function getMappingResultsForProperty(): Result
    {
        if (!$this->isObjectAccessorMode()) {
            return new Result();
        }
        /** @var ExtbaseRequestParameters $extbaseRequestParameters */
        $extbaseRequestParameters = $this->getRequest()->getAttribute('extbase');
        $originalRequestMappingResults = $extbaseRequestParameters->getOriginalRequestMappingResults();
        $formObjectName = $this->renderingContext->getViewHelperVariableContainer()->get(
            FormViewHelper::class,
            'formObjectName'
        );
        return $originalRequestMappingResults->forProperty($formObjectName)->forProperty((string)$this->arguments['property']);
    }

    /**
     * Renders a hidden field with the same name as the element, to make sure the empty value is submitted
     * in case nothing is selected. This is needed for checkbox and multiple select fields
     */
    protected function renderHiddenFieldForEmptyValue(): string
    {
        $hiddenFieldNames = [];
        $viewHelperVariableContainer = $this->renderingContext->getViewHelperVariableContainer();
        if ($viewHelperVariableContainer->exists(
            FormViewHelper::class,
            'renderedHiddenFields'
        )
        ) {
            $hiddenFieldNames = $viewHelperVariableContainer->get(
                FormViewHelper::class,
                'renderedHiddenFields'
            );
        }
        $fieldName = $this->getName();
        if (substr($fieldName, -2) === '[]') {
            $fieldName = substr($fieldName, 0, -2);
        }
        if (!in_array($fieldName, $hiddenFieldNames, true)) {
            $hiddenFieldNames[] = $fieldName;
            $viewHelperVariableContainer->addOrUpdate(
                FormViewHelper::class,
                'renderedHiddenFields',
                $hiddenFieldNames
            );
            return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="" />';
        }
        return '';
    }
}
