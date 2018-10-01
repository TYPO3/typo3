<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Model\FormElements;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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

use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;

/**
 * A Section, being part of a bigger Page
 *
 * This class contains multiple FormElements ({@link FormElementInterface}).
 *
 * Please see {@link FormDefinition} for an in-depth explanation.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 */
class Section extends AbstractSection implements FormElementInterface
{

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Will be called as soon as the element is (tried to be) added to a form
     * @see registerInFormIfPossible()
     *
     * @internal
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
     * Returns a unique identifier of this element.
     * While element identifiers are only unique within one form,
     * this includes the identifier of the form itself, making it "globally" unique
     *
     * @return string the "globally" unique identifier of this element
     */
    public function getUniqueIdentifier(): string
    {
        $formDefinition = $this->getRootForm();
        return sprintf('%s-%s', $formDefinition->getIdentifier(), $this->identifier);
    }

    /**
     * Get the default value with which the Form Element should be initialized
     * during display.
     * Note: This is currently not used for section elements
     *
     * @return mixed the default value for this Form Element
     */
    public function getDefaultValue()
    {
        return null;
    }

    /**
     * Set the default value with which the Form Element should be initialized
     * during display.
     * Note: This is currently ignored for section elements
     *
     * @param mixed $defaultValue the default value for this Form Element
     */
    public function setDefaultValue($defaultValue)
    {
    }

    /**
     * Get all element-specific configuration properties
     *
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Set an element-specific configuration property.
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
     * Whether or not this element is required
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
}
