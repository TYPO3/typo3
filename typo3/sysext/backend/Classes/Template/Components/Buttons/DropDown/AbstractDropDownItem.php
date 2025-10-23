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

namespace TYPO3\CMS\Backend\Template\Components\Buttons\DropDown;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Base class for all dropdown menu items that can be added to DropDownButton or SplitButton.
 * Provides common functionality for dropdown items including icon handling, labels, links,
 * custom HTML tags, and active state management.
 *
 * Implements DropDownItemInterface, providing validation, type identification, and rendering
 * capabilities for all dropdown items.
 */
abstract class AbstractDropDownItem implements \Stringable
{
    /**
     * HTML tag name for the dropdown item element (e.g., 'a', 'button', custom elements)
     */
    protected string $tag = 'a';

    /**
     * Optional icon displayed before the label
     */
    protected ?Icon $icon = null;

    /**
     * Text content/label of the dropdown item
     */
    protected ?string $label = null;

    /**
     * Tooltip/title attribute (defaults to label if not explicitly set)
     */
    protected ?string $title = null;

    /**
     * URL/href for link items
     */
    protected ?string $href = null;

    /**
     * Custom HTML attributes for the element
     *
     * @var array<string, string>
     */
    protected array $attributes = [];

    /**
     * Whether this item is currently active/selected
     */
    protected bool $active = false;

    public function setTag(string $tag): static
    {
        $this->tag = htmlspecialchars(trim($tag));
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title ?? $this->label;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function setHref(?string $href): static
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = $attributes;
        return $this;
    }

    public function setAttribute(string $name, string $value): static
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function isValid(): bool
    {
        return $this->getLabel() !== null && trim($this->getLabel()) !== '';
    }

    public function getType(): string
    {
        return static::class;
    }

    protected function getAttributesString(): string
    {
        $attributes = $this->getAttributes();
        $attributes['class'] = 'dropdown-item dropdown-item-spaced';
        if ($this->isActive()) {
            $attributes['aria-selected'] = 'true';
        }
        if ($this->getHref()) {
            $attributes['href'] = $this->getHref();
        }
        if ($this->getTitle()) {
            $attributes['title'] = $this->getTitle();
        }

        return GeneralUtility::implodeAttributes($attributes, true);
    }

    protected function getRenderedIcon(): string
    {
        return $this->getIcon()?->render() ?? '';
    }

    abstract public function render(): string;

    public function __toString(): string
    {
        return $this->render();
    }
}
