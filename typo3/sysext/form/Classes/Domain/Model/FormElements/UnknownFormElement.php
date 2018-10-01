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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractRenderable;

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
     * Sets up the form element
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
        $uniqueIdentifier = sprintf('%s-%s', $formDefinition->getIdentifier(), $this->identifier);
        $uniqueIdentifier = preg_replace('/[^a-zA-Z0-9-_]/', '_', $uniqueIdentifier);
        return lcfirst($uniqueIdentifier);
    }

    /**
     * Get the template name of the renderable
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'UnknownElement';
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
}
