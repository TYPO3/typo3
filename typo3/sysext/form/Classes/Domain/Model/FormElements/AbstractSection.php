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
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;
use TYPO3\CMS\Form\Domain\Model\Exception\FormDefinitionConsistencyException;
use TYPO3\CMS\Form\Domain\Model\Renderable\AbstractCompositeRenderable;

/**
 * A base class for "section-like" form parts like "Page" or "Section" (which
 * is rendered as "Fieldset")
 *
 * This class contains multiple FormElements ({@link FormElementInterface}).
 *
 * Please see {@link FormDefinition} for an in-depth explanation.
 *
 * **This class is NOT meant to be sub classed by developers.**
 * Scope: frontend
 */
abstract class AbstractSection extends AbstractCompositeRenderable
{

    /**
     * Constructor. Needs the identifier and type of this element
     *
     * @param string $identifier The Section identifier
     * @param string $type The Section type
     * @throws IdentifierNotValidException if the identifier was no non-empty string
     */
    public function __construct(string $identifier, string $type)
    {
        if (!is_string($identifier) || strlen($identifier) === 0) {
            throw new IdentifierNotValidException('The given identifier was not a string or the string was empty.', 1477082501);
        }

        $this->identifier = $identifier;
        $this->type = $type;
    }

    /**
     * Get the child Form Elements
     *
     * @return FormElementInterface[] The Page's elements
     */
    public function getElements(): array
    {
        return $this->renderables;
    }

    /**
     * Get the child Form Elements
     *
     * @return FormElementInterface[] The Page's elements
     */
    public function getElementsRecursively(): array
    {
        return $this->getRenderablesRecursively();
    }

    /**
     * Add a new form element at the end of the section
     *
     * @param FormElementInterface $formElement The form element to add
     * @throws FormDefinitionConsistencyException if FormElement is already added to a section
     */
    public function addElement(FormElementInterface $formElement)
    {
        $this->addRenderable($formElement);
    }

    /**
     * Create a form element with the given $identifier and attach it to this section/page.
     *
     * - Create Form Element object based on the given $typeName
     * - set defaults inside the Form Element (based on the parent form's field defaults)
     * - attach Form Element to this Section/Page
     * - return the newly created Form Element object
     *
     *
     * @param string $identifier Identifier of the new form element
     * @param string $typeName type of the new form element
     * @return FormElementInterface the newly created form element
     * @throws TypeDefinitionNotFoundException
     * @throws TypeDefinitionNotValidException
     */
    public function createElement(string $identifier, string $typeName): FormElementInterface
    {
        $formDefinition = $this->getRootForm();

        $typeDefinitions = $formDefinition->getTypeDefinitions();
        if (isset($typeDefinitions[$typeName])) {
            $typeDefinition = $typeDefinitions[$typeName];
        } else {
            $renderingOptions = $formDefinition->getRenderingOptions();
            $skipUnknownElements = isset($renderingOptions['skipUnknownElements']) && $renderingOptions['skipUnknownElements'] === true;
            if (!$skipUnknownElements) {
                throw new TypeDefinitionNotFoundException(sprintf('Type "%s" not found. Probably some configuration is missing.', $typeName), 1382364019);
            }

            $element = GeneralUtility::makeInstance(ObjectManager::class)
                ->get(UnknownFormElement::class, $identifier, $typeName);
            $this->addElement($element);
            return $element;
        }

        if (!isset($typeDefinition['implementationClassName'])) {
            throw new TypeDefinitionNotFoundException(sprintf('The "implementationClassName" was not set in type definition "%s".', $typeName), 1325689855);
        }

        $implementationClassName = $typeDefinition['implementationClassName'];
        $element = GeneralUtility::makeInstance(ObjectManager::class)
            ->get($implementationClassName, $identifier, $typeName);
        if (!$element instanceof FormElementInterface) {
            throw new TypeDefinitionNotValidException(sprintf('The "implementationClassName" for element "%s" ("%s") does not implement the FormElementInterface.', $identifier, $implementationClassName), 1327318156);
        }
        unset($typeDefinition['implementationClassName']);

        $this->addElement($element);
        $element->setOptions($typeDefinition);

        $element->initializeFormElement();
        return $element;
    }

    /**
     * Move FormElement $element before $referenceElement.
     *
     * Both $element and $referenceElement must be direct descendants of this Section/Page.
     *
     * @param FormElementInterface $elementToMove
     * @param FormElementInterface $referenceElement
     */
    public function moveElementBefore(FormElementInterface $elementToMove, FormElementInterface $referenceElement)
    {
        $this->moveRenderableBefore($elementToMove, $referenceElement);
    }

    /**
     * Move FormElement $element after $referenceElement
     *
     * Both $element and $referenceElement must be direct descendants of this Section/Page.
     *
     * @param FormElementInterface $elementToMove
     * @param FormElementInterface $referenceElement
     */
    public function moveElementAfter(FormElementInterface $elementToMove, FormElementInterface $referenceElement)
    {
        $this->moveRenderableAfter($elementToMove, $referenceElement);
    }

    /**
     * Remove $elementToRemove from this Section/Page
     *
     * @param FormElementInterface $elementToRemove
     */
    public function removeElement(FormElementInterface $elementToRemove)
    {
        $this->removeRenderable($elementToRemove);
    }
}
