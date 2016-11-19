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

use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;
use TYPO3\CMS\Form\Domain\Renderer\UnknownFormElementRenderer;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * A Form Element that has no definition.
 *
 * Scope: frontend
 */
class UnknownFormElement extends AbstractRenderable implements FormElementInterface
{

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
            throw new IdentifierNotValidException('The given identifier was not a string or the string was empty.', 1382364370);
        }
        $this->identifier = $identifier;
        $this->type = $type;
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
        $uniqueIdentifier = sprintf('%s-%s', $formDefinition->getIdentifier(), $this->identifier);
        $uniqueIdentifier = preg_replace('/[^a-zA-Z0-9-_]/', '_', $uniqueIdentifier);
        return lcfirst($uniqueIdentifier);
    }

    /**
     * Unknown Form Elements are rendered with the UnknownFormElementRenderer
     *
     * @return string the renderer class name
     * @internal
     */
    public function getRendererClassName(): string
    {
        return UnknownFormElementRenderer::class;
    }

    /**
     * Not used in this implementation
     *
     * @return void
     * @internal
     */
    public function initializeFormElement()
    {
    }

    /**
     * @return mixed the default value for this Form Element
     * @internal
     */
    public function getDefaultValue()
    {
        return null;
    }

    /**
     * Not used in this implementation
     *
     * @param mixed $defaultValue the default value for this Form Element
     * @internal
     */
    public function setDefaultValue($defaultValue)
    {
    }

    /**
     * Not used in this implementation
     *
     * @param string $key
     * @param mixed $value
     * @return void
     * @internal
     */
    public function setProperty(string $key, $value)
    {
    }

    /**
     * @return array
     * @internal
     */
    public function getProperties(): array
    {
        return [];
    }

    /**
     * @return bool
     * @internal
     */
    public function isRequired(): bool
    {
        return false;
    }

    /**
     * Not used in this implementation
     *
     * @param FormRuntime $formRuntime
     * @param mixed $elementValue submitted value of the element *before post processing*
     * @param array $requestArguments submitted raw request values
     * @return void
     * @see FormRuntime::mapAndValidate()
     * @internal
     */
    public function onSubmit(FormRuntime $formRuntime, &$elementValue, array $requestArguments = [])
    {
    }
}
