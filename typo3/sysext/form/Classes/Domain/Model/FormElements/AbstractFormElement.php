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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Domain\Model\FormElements;

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;

/**
 * A base form element, which is the starting point for creating custom (PHP-based)
 * Form Elements.
 *
 * A *FormElement* is a part of a *Page*, which in turn is part of a FormDefinition.
 * See {@link FormDefinition} for an in-depth explanation.
 *
 * Subclassing this class is a good starting-point for implementing custom PHP-based
 * Form Elements.
 *
 * Most of the functionality and API is implemented in {@link \TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable}, so
 * make sure to check out this class as well.
 *
 * Still, it is quite rare that you need to subclass this class; often
 * you can just use the {@link \TYPO3\CMS\Form\Domain\Model\FormElements\GenericFormElement} and replace some templates.
 *
 * Scope: frontend
 * **This class is meant to be sub classed by developers.**
 */
abstract class AbstractFormElement extends AbstractRenderable implements FormElementInterface
{

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Constructor. Needs this FormElement's identifier and the FormElement type
     *
     * @param string $identifier The FormElement's identifier
     * @param string $type The Form Element Type
     * @throws IdentifierNotValidException
     */
    public function __construct(string $identifier, string $type)
    {
        if (!is_string($identifier) || strlen($identifier) === 0) {
            throw new IdentifierNotValidException('The given identifier was not a string or the string was empty.', 1477082502);
        }
        $this->identifier = $identifier;
        $this->type = $type;
    }

    /**
     * Override this method in your custom FormElements if needed
     */
    public function initializeFormElement()
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['initializeFormElement'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'initializeFormElement')) {
                $hookObj->initializeFormElement(
                    $this
                );
            }
        }
    }

    /**
     * Get the global unique identifier of the element
     *
     * @return string
     */
    public function getUniqueIdentifier(): string
    {
        $formDefinition = $this->getRootForm();
        $uniqueIdentifier = sprintf('%s-%s', $formDefinition->getIdentifier(), $this->identifier);
        $uniqueIdentifier = (string)preg_replace('/[^a-zA-Z0-9_-]/', '_', $uniqueIdentifier);
        return lcfirst($uniqueIdentifier);
    }

    /**
     * Get the default value of the element
     *
     * @return mixed
     */
    public function getDefaultValue()
    {
        $formDefinition = $this->getRootForm();
        return $formDefinition->getElementDefaultValueByIdentifier($this->identifier);
    }

    /**
     * Set the default value of the element
     *
     * @param mixed $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $formDefinition = $this->getRootForm();
        $currentDefaultValue = $formDefinition->getElementDefaultValueByIdentifier($this->identifier);
        if (is_array($currentDefaultValue) && is_array($defaultValue)) {
            ArrayUtility::mergeRecursiveWithOverrule($currentDefaultValue, $defaultValue);
            $defaultValue = ArrayUtility::removeNullValuesRecursive($currentDefaultValue);
        }
        $formDefinition->addElementDefaultValue($this->identifier, $defaultValue);
    }

    /**
     * Check if the element is required
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        foreach ($this->getValidators() as $validator) {
            if ($validator instanceof NotEmptyValidator) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set a property of the element
     *
     * @param string $key
     * @param mixed $value
     */
    public function setProperty(string $key, $value)
    {
        if (is_array($value) && isset($this->properties[$key]) && is_array($this->properties[$key])) {
            ArrayUtility::mergeRecursiveWithOverrule($this->properties[$key], $value);
            $this->properties[$key] = ArrayUtility::removeNullValuesRecursive($this->properties[$key]);
        } elseif ($value === null) {
            unset($this->properties[$key]);
        } else {
            $this->properties[$key] = $value;
        }
    }

    /**
     * Get all properties
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }
}
