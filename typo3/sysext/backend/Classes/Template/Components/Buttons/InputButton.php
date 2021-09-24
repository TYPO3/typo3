<?php

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

namespace TYPO3\CMS\Backend\Template\Components\Buttons;

/**
 * InputButton
 *
 * This button type renders a HTML tag <button> and takes the HTML attributes
 * name and value as additional attributes to those defined in AbstractButton.
 *
 * Since we no longer want to have any <input type="submit" /> in the TYPO3 core
 * you should use this button type to send forms
 *
 * EXAMPLE USAGE TO ADD A BUTTON TO THE FIRST BUTTON GROUP IN THE LEFT BAR:
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 * $saveButton = $buttonBar->makeInputButton()
 *      ->setName('save')
 *      ->setValue('1')
 *      ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
 *      ->setTitle('Save');
 * $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 */
class InputButton extends AbstractButton
{
    /**
     * Name Attribute of the button
     *
     * @var string
     */
    protected $name = '';

    /**
     * Value attribute of the button
     *
     * @var string
     */
    protected $value = '';

    /**
     * ID of the referenced <form> tag
     *
     * @var string
     */
    protected $form = '';

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name Name attribute
     *
     * @return InputButton
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set value
     *
     * @param string $value Value attribute
     *
     * @return InputButton
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param string $form
     *
     * @return InputButton
     */
    public function setForm($form)
    {
        $this->form = $form;
        return $this;
    }

    /**
     * Validates the current button
     *
     * @return bool
     */
    public function isValid()
    {
        if (
            trim($this->getName()) !== ''
            && trim($this->getValue()) !== ''
            && trim($this->getTitle()) !== ''
            && $this->getType() === self::class
            && $this->getIcon() !== null
        ) {
            return true;
        }
        return false;
    }

    /**
     * Renders the markup of the button
     *
     * @return string
     */
    public function render()
    {
        $attributes = [
            'name' => $this->getName(),
            'class' => 'btn btn-default btn-sm ' . $this->getClasses(),
            'value' => $this->getValue(),
            'title' => $this->getTitle(),
            'form' => trim($this->getForm()),
        ];
        if ($this->isDisabled()) {
            $attributes['disabled'] = 'disabled';
        }
        $labelText = '';
        if ($this->showLabelText) {
            $labelText = ' ' . $this->title;
        }
        foreach ($this->dataAttributes as $attributeName => $attributeValue) {
            $attributes['data-' . $attributeName] = $attributeValue;
        }
        $attributesString = '';
        foreach ($attributes as $key => $value) {
            if ($value !== '') {
                $attributesString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            }
        }
        return '<button' . $attributesString . '>'
            . $this->getIcon()->render() . htmlspecialchars($labelText)
        . '</button>';
    }

    /**
     * Magic method so Fluid can access a button via {button}
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
