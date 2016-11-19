<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Model\FormElements;

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

use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

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
     * @return void
     * @internal
     */
    public function initializeFormElement()
    {
    }

    /**
     * Returns a unique identifier of this element.
     * While element identifiers are only unique within one form,
     * this includes the identifier of the form itself, making it "globally" unique
     *
     * @return string the "globally" unique identifier of this element
     * @api
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
     * @api
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
     * @api
     */
    public function setDefaultValue($defaultValue)
    {
    }

    /**
     * Get all element-specific configuration properties
     *
     * @return array
     * @api
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
     * @return void
     * @api
     */
    public function setProperty(string $key, $value)
    {
        $this->properties[$key] = $value;
    }

    /**
     * Set the rendering option $key to $value.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     * @api
     */
    public function setRenderingOption(string $key, $value)
    {
        $this->renderingOptions[$key] = $value;
    }

    /**
     * Get all validators on the element
     *
     * @return \SplObjectStorage
     * @internal
     */
    public function getValidators(): \SplObjectStorage
    {
        $formDefinition = $this->getRootForm();
        return $formDefinition->getProcessingRule($this->getIdentifier())->getValidators();
    }

    /**
     * Add a validator to the element
     *
     * @param ValidatorInterface $validator
     * @return void
     * @api
     */
    public function addValidator(ValidatorInterface $validator)
    {
        $formDefinition = $this->getRootForm();
        $formDefinition->getProcessingRule($this->getIdentifier())->addValidator($validator);
    }

    /**
     * Whether or not this element is required
     *
     * @return bool
     * @api
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
