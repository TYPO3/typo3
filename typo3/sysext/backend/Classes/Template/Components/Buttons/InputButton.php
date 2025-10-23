<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This button type renders a HTML tag <button> and takes the HTML attributes
 * name and value as additional attributes to those defined in AbstractButton.
 *
 * Since we no longer want to have any <input type="submit" /> in the TYPO3 core
 * you should use this button type to send forms
 *
 * Example:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *     $saveButton = $this->componentFactory->createInputButton()
 *          ->setName('save')
 *          ->setValue('1')
 *          ->setIcon($this->iconFactory->getIcon('actions-document-save', IconSize::SMALL))
 *          ->setTitle('Save');
 *     $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 * }
 * ```
 */
class InputButton extends AbstractButton
{
    protected string $name = '';
    protected string $value = '';
    protected string $form = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getForm(): string
    {
        return $this->form;
    }

    public function setForm(string $form): static
    {
        $this->form = $form;
        return $this;
    }

    public function isValid(): bool
    {
        return trim($this->getName()) !== ''
            && trim($this->getValue()) !== ''
            && trim($this->getTitle()) !== ''
            && $this->getType() === static::class
            && $this->getIcon() !== null;
    }

    public function render(): string
    {
        $attributes = [
            'name' => $this->getName(),
            'class' => 'btn btn-sm btn-default ' . $this->getClasses(),
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

        return sprintf(
            '<button %s>%s%s</button>',
            GeneralUtility::implodeAttributes($attributes, true),
            $this->getIcon()?->render() ?? '',
            htmlspecialchars($labelText)
        );
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
