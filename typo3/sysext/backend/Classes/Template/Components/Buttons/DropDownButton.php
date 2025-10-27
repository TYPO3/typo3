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

use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItemInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This button type is a container for dropdown items.
 * It will render a dropdown containing all items attached
 * to it. There are different kinds available, each item
 * needs to implement the DropDownItemInterface. When this
 * type contains elements of type DropDownRadio it will use
 * the icon of the first active item of this type.
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
 *     $dropDownButton = $this->componentFactory->createDropDownButton()
 *          ->setLabel('Dropdown')
 *          ->setTitle('Save')
 *          ->setIcon($this->iconFactory->getIcon('actions-heart'))
 *          ->getShowLabelText(true)
 *          ->addItem(
 *              GeneralUtility::makeInstance(DropDownItem::class)
 *                  ->setLabel('Item')
 *                  ->setHref('#')
 *          );
 *     $buttonBar->addButton($dropDownButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);
 * }
 * ```
 */
class DropDownButton implements ButtonInterface
{
    protected ?Icon $icon = null;
    protected string $label = '';
    protected ?string $title = null;
    protected array $items = [];
    protected bool $showLabelText = false;
    protected bool $disabled = false;
    protected ButtonSize $size = ButtonSize::SMALL;

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function setIcon(?Icon $icon): static
    {
        $icon?->setSize(IconSize::SMALL);
        $this->icon = $icon;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title ?? $this->label;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getShowLabelText(): bool
    {
        return $this->showLabelText;
    }

    public function setShowLabelText(bool $showLabelText): static
    {
        $this->showLabelText = $showLabelText;
        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function addItem(DropDownItemInterface $item): static
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

    public function getSize(): ButtonSize
    {
        return $this->size;
    }

    public function setSize(ButtonSize $size): DropDownButton
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @return DropDownItemInterface[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function isValid(): bool
    {
        return !empty($this->getLabel())
            && ($this->getShowLabelText() || $this->getIcon())
            && !empty($this->getItems());
    }

    public function getType(): string
    {
        return static::class;
    }

    public function render(): string
    {
        $items = $this->getItems();

        /**
         * @var DropDownRadio[] $activeItems
         */
        $activeItems = array_filter($items, static function (DropDownItemInterface $item): bool {
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
            'class' => 'btn ' . $this->getSize()->value . ' btn-default dropdown-toggle',
            'data-bs-toggle' => 'dropdown',
            'aria-expanded' => 'false',
        ];
        if ($this->getTitle()) {
            $attributes['title'] = $this->getTitle();
        }
        if ($this->isDisabled()) {
            $attributes['disabled'] = 'disabled';
        }

        $labelText = '';
        if ($this->getShowLabelText()) {
            $labelText = ' ' . $this->getLabel();
        }

        return sprintf(
            '<div class="btn-group"><button %s>%s%s</button><ul class="dropdown-menu">%s</ul></div>',
            GeneralUtility::implodeAttributes($attributes, true),
            $this->getIcon()?->render() ?? '',
            htmlspecialchars($labelText),
            implode('', array_map(static fn(DropDownItemInterface $item) => '<li>' . $item->render() . '</li>', $items))
        );
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
