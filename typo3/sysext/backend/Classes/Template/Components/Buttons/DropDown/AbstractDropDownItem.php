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

namespace TYPO3\CMS\Backend\Template\Components\Buttons\DropDown;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractDropDownItem implements \Stringable
{
    protected string $tag = 'a';
    protected ?Icon $icon = null;
    protected ?string $label = null;
    protected ?string $title = null;
    protected ?string $href = null;
    protected array $attributes = [];

    public function setTag(string $tag): self
    {
        $this->$tag = htmlspecialchars(trim($tag));
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

    public function setIcon(?Icon $icon): self
    {
        $icon?->setSize(Icon::SIZE_SMALL);
        $this->icon = $icon;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title ?? $this->label;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function setHref(?string $href): self
    {
        $this->href = $href;
        return $this;
    }

    /**
     * @param array<string, string> $attributes
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
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

    abstract public function render();

    public function __toString(): string
    {
        return $this->render();
    }
}
