<?php
declare(strict_types = 1);
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
 * A GridContainer, being part of a bigger Page
 *
 * This class contains multiple GridRow elements.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 */
class GridContainer extends Section implements GridContainerInterface
{
    /**
     * Initializes the Form Element
     *
     * @internal
     */
    public function initializeFormElement()
    {
        trigger_error(
            '"GridContainer" form elements will be removed in TYPO3 v10.0. Use "GridRow" form elements instead.',
            E_USER_DEPRECATED
        );
        parent::initializeFormElement();
    }

    /**
     * Register this element at the parent form, if there is a connection to the parent form.
     *
     * @throws TypeDefinitionNotValidException
     * @internal
     */
    public function registerInFormIfPossible()
    {
        foreach ($this->getElementsRecursively() as $renderable) {
            if ($renderable instanceof GridContainerInterface) {
                throw new TypeDefinitionNotValidException(
                    sprintf('Grid containers ("%s") within grid containers ("%s") are not allowed.', $renderable->getIdentifier(), $this->getIdentifier()),
                    1489412790
                );
            }
        }
        parent::registerInFormIfPossible();
    }

    /**
     * Add a new row element at the end of the grid container
     *
     * @param FormElementInterface $formElement The form element to add
     */
    public function addElement(FormElementInterface $formElement)
    {
        if (!$formElement instanceof GridRowInterface) {
            throw new TypeDefinitionNotValidException(
                sprintf('The "implementationClassName" for element "%s" (type "%s") does not implement the GridRowInterface.', $formElement->getIdentifier(), $formElement->getType()),
                1489486301
            );
        }
        $this->addRenderable($formElement);
    }

    /**
     * Create a form element with the given $identifier and attach it to this container.
     *
     * @param string $identifier Identifier of the new form element
     * @param string $typeName type of the new form element
     * @return FormElementInterface the newly created grid row
     * @throws TypeDefinitionNotValidException
     */
    public function createElement(string $identifier, string $typeName): FormElementInterface
    {
        $element = parent::createElement($identifier, $typeName);

        if (!$element instanceof GridRowInterface) {
            throw new TypeDefinitionNotValidException(
                sprintf('The "implementationClassName" for element "%s" (type "%s") does not implement the GridRowInterface.', $identifier, $typeName),
                1489486302
            );
        }
        return $element;
    }
}
