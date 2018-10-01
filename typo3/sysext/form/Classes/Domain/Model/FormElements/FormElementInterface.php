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

use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;
use TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface;

/**
 * A base form element interface, which can be the starting point for creating
 * custom (PHP-based) Form Elements.
 *
 * A *FormElement* is a part of a *Page*, which in turn is part of a FormDefinition.
 * See {@link FormDefinition} for an in-depth explanation.
 *
 * **Often, you should rather subclass {@link AbstractFormElement} instead of
 * implementing this interface.**
 *
 * Scope: frontend
 */
interface FormElementInterface extends RenderableInterface
{

    /**
     * Will be called as soon as the element is (tried to be) added to a form
     * @see registerInFormIfPossible()
     *
     * @internal
     */
    public function initializeFormElement();

    /**
     * Returns a unique identifier of this element.
     * While element identifiers are only unique within one form,
     * this includes the identifier of the form itself, making it "globally" unique
     *
     * @return string the "globally" unique identifier of this element
     */
    public function getUniqueIdentifier(): string;

    /**
     * Get the default value with which the Form Element should be initialized
     * during display.
     *
     * @return mixed the default value for this Form Element
     */
    public function getDefaultValue();

    /**
     * Set the default value with which the Form Element should be initialized
     * during display.
     *
     * @param mixed $defaultValue the default value for this Form Element
     */
    public function setDefaultValue($defaultValue);

    /**
     * Set an element-specific configuration property.
     *
     * @param string $key
     * @param mixed $value
     */
    public function setProperty(string $key, $value);

    /**
     * Get all element-specific configuration properties
     *
     * @return array
     */
    public function getProperties(): array;

    /**
     * Set a rendering option
     *
     * @param string $key
     * @param mixed $value
     */
    public function setRenderingOption(string $key, $value);

    /**
     * Returns the child validators of the ConjunctionValidator that is registered for this element
     *
     * @return \SplObjectStorage<ValidatorInterface>
     * @internal
     */
    public function getValidators(): \SplObjectStorage;

    /**
     * Registers a validator for this element
     *
     * @param ValidatorInterface $validator
     */
    public function addValidator(ValidatorInterface $validator);

    /**
     * Set the target data type for this element
     *
     * @param string $dataType the target data type
     */
    public function setDataType(string $dataType);

    /**
     * Whether or not this element is required
     *
     * @return bool
     */
    public function isRequired(): bool;
}
