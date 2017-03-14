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

use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotValidException;

/**
 * A grid row, being part of a grid container
 *
 * This class contains multiple FormElements ({@link FormElementInterface}).
 *
 * Please see {@link FormDefinition} for an in-depth explanation.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 */
class GridRow extends Section implements GridRowInterface
{

    /**
     * Register this element at the parent form, if there is a connection to the parent form.
     *
     * @return void
     * @throws TypeDefinitionNotValidException
     * @internal
     */
    public function registerInFormIfPossible()
    {
        if (!$this->getParentRenderable() instanceof GridContainerInterface) {
            throw new TypeDefinitionNotValidException(
                sprintf('Grid rows ("%s") only allowed within grid containers.', $this->getIdentifier()),
                1489413805
            );
        }
        parent::registerInFormIfPossible();
    }

    /**
     * Add a new form element at the end of the grid row
     *
     * @param FormElementInterface $formElement The form element to add
     * @return void
     * @throws TypeDefinitionNotValidException if FormElement is already added to a section
     * @api
     */
    public function addElement(FormElementInterface $formElement)
    {
        if ($formElement instanceof GridContainerInterface) {
            throw new TypeDefinitionNotValidException(
                sprintf('Grid containers ("%s") within grid rows ("%s") are not allowed.', $formElement->getIdentifier(), $this->getIdentifier()),
                1489413379
            );
        } elseif ($formElement instanceof GridRowInterface) {
            throw new TypeDefinitionNotValidException(
                sprintf('Grid rows ("%s") within grid rows ("%s") are not allowed.', $formElement->getIdentifier(), $this->getIdentifier()),
                1489413696
            );
        }

        $this->addRenderable($formElement);
    }

    /**
     * Create a form element with the given $identifier and attach it to this container.
     *
     * @param string $identifier Identifier of the new form element
     * @param string $typeName type of the new form element
     * @return GridRowInterface the newly created frid row
     * @throws TypeDefinitionNotValidException
     * @api
     */
    public function createElement(string $identifier, string $typeName): FormElementInterface
    {
        $element = parent::createElement($identifier, $typeName);

        if ($element instanceof GridContainerInterface) {
            throw new TypeDefinitionNotValidException(
                sprintf('Grid containers ("%s") within grid rows ("%s") are not allowed.', $element->getIdentifier(), $this->getIdentifier()),
                1489413538
            );
        } elseif ($element instanceof GridRowInterface) {
            throw new TypeDefinitionNotValidException(
                sprintf('Grid rows ("%s") within grid rows ("%s") are not allowed.', $element->getIdentifier(), $this->getIdentifier()),
                1489413697
            );
        }

        return $element;
    }
}
