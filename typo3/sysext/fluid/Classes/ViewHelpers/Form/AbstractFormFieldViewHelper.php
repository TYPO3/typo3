<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Form;

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

use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * Abstract Form View Helper. Bundles functionality related to direct property access of objects in other Form ViewHelpers.
 *
 * If you set the "property" attribute to the name of the property to resolve from the object, this class will
 * automatically set the name and value of a form element.
 */
abstract class AbstractFormFieldViewHelper extends AbstractFormViewHelper
{
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var bool
     */
    protected $respectSubmittedDataValue = false;

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('name', 'string', 'Name of input tag');
        $this->registerArgument('value', 'mixed', 'Value of input tag');
        $this->registerArgument(
            'property',
            'string',
            'Name of Object Property. If used in conjunction with <f:form object="...">, "name" and "value" properties will be ignored.'
        );
    }

    /**
     * Getting the current configuration for respectSubmittedDataValue.
     *
     * @return bool
     */
    public function getRespectSubmittedDataValue()
    {
        return $this->respectSubmittedDataValue;
    }

    /**
     * Define respectSubmittedDataValue to enable or disable the usage of the submitted values in the viewhelper.
     *
     * @param bool $respectSubmittedDataValue
     */
    public function setRespectSubmittedDataValue($respectSubmittedDataValue)
    {
        $this->respectSubmittedDataValue = $respectSubmittedDataValue;
    }

    /**
     * Get the name of this form element.
     * Either returns arguments['name'], or the correct name for Object Access.
     *
     * In case property is something like bla.blubb (hierarchical), then [bla][blubb] is generated.
     *
     * @return string Name
     */
    protected function getName()
    {
        $name = $this->getNameWithoutPrefix();
        return $this->prefixFieldName($name);
    }

    /**
     * Shortcut for retrieving the request from the controller context
     *
     * @return \TYPO3\CMS\Extbase\Mvc\Request
     */
    protected function getRequest()
    {
        return $this->renderingContext->getControllerContext()->getRequest();
    }

    /**
     * Get the name of this form element, without prefix.
     *
     * @return string name
     */
    protected function getNameWithoutPrefix()
    {
        if ($this->isObjectAccessorMode()) {
            $formObjectName = $this->viewHelperVariableContainer->get(
                \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
                'formObjectName'
            );
            if (!empty($formObjectName)) {
                $propertySegments = explode('.', $this->arguments['property'] ?? '');
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
        if ($this->hasArgument('value') && is_object($this->arguments['value'])) {
            // @todo Use  $this->persistenceManager->isNewObject() once it is implemented
            if (null !== $this->persistenceManager->getIdentifierByObject($this->arguments['value'])) {
                $name .= '[__identity]';
            }
        }
        return $name;
    }

    /**
     * Returns the current value of this Form ViewHelper and converts it to an identifier string in case it's an object
     * The value is determined as follows:
     * * If property mapping errors occurred and the form is re-displayed, the *last submitted* value is returned
     * * Else the bound property is returned (only in objectAccessor-mode)
     * * As fallback the "value" argument of this ViewHelper is used
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
            $identifier = $this->persistenceManager->getIdentifierByObject($value);
            if ($identifier !== null) {
                $value = $identifier;
            }
        }
        return $value;
    }

    /**
     * Checks if a property mapping error has occurred in the last request.
     *
     * @return bool TRUE if a mapping error occurred, FALSE otherwise
     */
    protected function hasMappingErrorOccurred()
    {
        return $this->renderingContext->getControllerContext()->getRequest()->getOriginalRequest() !== null;
    }

    /**
     * Get the form data which has last been submitted; only returns valid data in case
     * a property mapping error has occurred. Check with hasMappingErrorOccurred() before!
     *
     * @return mixed
     */
    protected function getLastSubmittedFormData()
    {
        $propertyPath = rtrim(preg_replace('/(\\]\\[|\\[|\\])/', '.', $this->getNameWithoutPrefix()), '.');
        $value = ObjectAccess::getPropertyPath(
            $this->renderingContext->getControllerContext()->getRequest()->getOriginalRequest()->getArguments(),
            $propertyPath
        );
        return $value;
    }

    /**
     * Add additional identity properties in case the current property is hierarchical (of the form "bla.blubb").
     * Then, [bla][__identity] has to be generated as well.
     */
    protected function addAdditionalIdentityPropertiesIfNeeded()
    {
        if (!$this->isObjectAccessorMode()) {
            return;
        }

        if (!$this->viewHelperVariableContainer->exists(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
            'formObject'
        )
        ) {
            return;
        }
        $propertySegments = explode('.', $this->arguments['property']);
        // hierarchical property. If there is no "." inside (thus $propertySegments == 1), we do not need to do anything
        if (count($propertySegments) < 2) {
            return;
        }
        $formObject = $this->viewHelperVariableContainer->get(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
            'formObject'
        );
        $objectName = $this->viewHelperVariableContainer->get(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
            'formObjectName'
        );
        // If count == 2 -> we need to go through the for-loop exactly once
        $propertySegmentsCount = count($propertySegments);
        for ($i = 1; $i < $propertySegmentsCount; $i++) {
            $object = ObjectAccess::getPropertyPath($formObject, implode('.', array_slice($propertySegments, 0, $i)));
            $objectName .= '[' . $propertySegments[$i - 1] . ']';
            $hiddenIdentityField = $this->renderHiddenIdentityField($object, $objectName);
            // Add the hidden identity field to the ViewHelperVariableContainer
            $additionalIdentityProperties = $this->viewHelperVariableContainer->get(
                \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
                'additionalIdentityProperties'
            );
            $additionalIdentityProperties[$objectName] = $hiddenIdentityField;
            $this->viewHelperVariableContainer->addOrUpdate(
                \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
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
        if (!$this->viewHelperVariableContainer->exists(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
            'formObject'
        )
        ) {
            return null;
        }
        $formObject = $this->viewHelperVariableContainer->get(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
            'formObject'
        );
        return ObjectAccess::getPropertyPath($formObject, $this->arguments['property']);
    }

    /**
     * Internal method which checks if we should evaluate a domain object or just output arguments['name'] and arguments['value']
     *
     * @return bool TRUE if we should evaluate the domain object, FALSE otherwise.
     */
    protected function isObjectAccessorMode()
    {
        return $this->hasArgument('property') && $this->viewHelperVariableContainer->exists(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
            'formObjectName'
        );
    }

    /**
     * Add an CSS class if this view helper has errors
     */
    protected function setErrorClassAttribute()
    {
        if ($this->hasArgument('class')) {
            $cssClass = $this->arguments['class'] . ' ';
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
     * Get errors for the property and form name of this view helper
     *
     * @return \TYPO3\CMS\Extbase\Error\Result Array of errors
     */
    protected function getMappingResultsForProperty()
    {
        if (!$this->isObjectAccessorMode()) {
            return new \TYPO3\CMS\Extbase\Error\Result();
        }
        $originalRequestMappingResults = $this->getRequest()->getOriginalRequestMappingResults();
        $formObjectName = $this->viewHelperVariableContainer->get(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
            'formObjectName'
        );
        return $originalRequestMappingResults->forProperty($formObjectName)->forProperty($this->arguments['property']);
    }

    /**
     * Renders a hidden field with the same name as the element, to make sure the empty value is submitted
     * in case nothing is selected. This is needed for checkbox and multiple select fields
     *
     * @return string the hidden field.
     */
    protected function renderHiddenFieldForEmptyValue()
    {
        $hiddenFieldNames = [];
        if ($this->viewHelperVariableContainer->exists(
            \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
            'renderedHiddenFields'
        )
        ) {
            $hiddenFieldNames = $this->viewHelperVariableContainer->get(
                \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
                'renderedHiddenFields'
            );
        }
        $fieldName = $this->getName();
        if (substr($fieldName, -2) === '[]') {
            $fieldName = substr($fieldName, 0, -2);
        }
        if (!in_array($fieldName, $hiddenFieldNames)) {
            $hiddenFieldNames[] = $fieldName;
            $this->viewHelperVariableContainer->addOrUpdate(
                \TYPO3\CMS\Fluid\ViewHelpers\FormViewHelper::class,
                'renderedHiddenFields',
                $hiddenFieldNames
            );
            return '<input type="hidden" name="' . htmlspecialchars($fieldName) . '" value="" />';
        }
        return '';
    }
}
