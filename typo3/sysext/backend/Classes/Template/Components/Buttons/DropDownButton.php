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

use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DropDownButton
 *
 * This button type is a container for dropdown items.
 * It will render a dropdown containing all items attached
 * to it. There are different kinds available, each item
 * needs to implement the DropDownItemInterface. When this
 * type contains elements of type DropDownRadio it will use
 * the icon of the first active item of this type.
 *
 * $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 * $dropDownButton = $buttonBar->makeDropDownButton()
 *      ->setLabel('Dropdown')
 *      ->setTitle('Save')
 *      ->setIcon($this->iconFactory->getIcon('actions-heart'))
 *      ->getShowLabelText(true)
 *      ->addItem(
 *          GeneralUtility::makeInstance(DropDownItem::class)
 *              ->setLabel('Item')
 *              ->setHref('#')
 *      );
 * $buttonBar->addButton($dropDownButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
 */
class DropDownButton implements ButtonInterface
{
    protected ?Icon $icon = null;
    protected string $label = '';
    protected ?string $title = null;
    protected array $items = [];
    protected bool $showLabelText = false;

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(?Icon $icon): self
    {
        $icon?->setSize(Icon::SIZE_SMALL);
        $this->icon = $icon;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?? $this->label;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getShowLabelText(): bool
    {
        return $this->showLabelText;
    }

    public function setShowLabelText(bool $showLabelText): self
    {
        $this->showLabelText = $showLabelText;
        return $this;
    }

    public function addItem(DropDownItemInterface $item): self
    {
        if (!$item->isValid()) {
            throw new \InvalidArgumentException(
                'Only valid items may be assigned to a DropdownButton. "' .
                $item->getType() .
                '" did not pass validation',
                1667645426
            );
        }

        $this->items[] = clone $item;

        return $this;
    }

    /**
     * @return DropDownItemInterface[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return !empty($this->getLabel())
            && ($this->getShowLabelText() || $this->getIcon())
            && !empty($this->getItems());
    }

    /**
     * @return string
     */
    public function getType()
    {
        return static::class;
    }

    /**
     * @return string
     */
    public function render()
    {
        $items = $this->getItems();

        /**
         * @var DropDownRadio[] $activeItems
         */
        $activeItems = array_filter($items, function (DropDownItemInterface $item) {
            return $item instanceof DropDownRadio && $item->isActive();
        });
        if (!empty($activeItems)) {
            $activeItem = array_shift($activeItems);
            if ($activeItem->getIcon()) {
                $this->setIcon($activeItem->getIcon());
            }
        }

        $attributes = [
            'type' => 'button',
            'class' => 'btn btn-sm btn-default dropdown-toggle',
            'data-bs-toggle' => 'dropdown',
            'aria-expanded' => 'false',
        ];
        if ($this->getTitle()) {
            $attributes['title'] = $this->getTitle();
        }

        $labelText = '';
        if ($this->getShowLabelText()) {
            $labelText = ' ' . $this->getLabel();
        }

        $content = '<div class="btn-group">'
            . '<button ' . GeneralUtility::implodeAttributes($attributes, true) . '>'
            . ($this->getIcon() !== null ? $this->getIcon()->render() : '')
            . htmlspecialchars($labelText)
            . '</button>'
            . '<ul class="dropdown-menu">';

        /** @var DropDownItemInterface $item */
        foreach ($items as $item) {
            $content .= '<li>' . $item->render() . '</li>';
        }
        $content .= '
            </ul>
        </div>';
        return $content;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
