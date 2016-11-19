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
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

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
     * @api
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
     *
     * @return void
     * @api
     */
    public function initializeFormElement()
    {
    }

    /**
     * Get the global unique identifier of the element
     *
     * @return string
     * @api
     */
    public function getUniqueIdentifier(): string
    {
        $formDefinition = $this->getRootForm();
        $uniqueIdentifier = sprintf('%s-%s', $formDefinition->getIdentifier(), $this->identifier);
        $uniqueIdentifier = preg_replace('/[^a-zA-Z0-9-_]/', '_', $uniqueIdentifier);
        return lcfirst($uniqueIdentifier);
    }

    /**
     * Get the default value of the element
     *
     * @return mixed
     * @api
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
     * @return void
     * @api
     */
    public function setDefaultValue($defaultValue)
    {
        $formDefinition = $this->getRootForm();
        $formDefinition->addElementDefaultValue($this->identifier, $defaultValue);
    }

    /**
     * Check if the element is required
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

    /**
     * Set a property of the element
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
     * Get all properties
     *
     * @return array
     * @api
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Override this method in your custom FormElements if needed
     *
     * @param FormRuntime $formRuntime
     * @param mixed $elementValue
     * @param array $requestArguments submitted raw request values
     * @return void
     * @api
     */
    public function onSubmit(FormRuntime $formRuntime, &$elementValue, array $requestArguments = [])
    {
    }
}
