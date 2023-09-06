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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * GenericButton
 *
 * $button = GeneralUtility::makeInstance(GenericButton::class)
 *     ->setTag('a')
 *     ->setHref('#')
 *     ->setLabel('Label')
 *     ->setTitle('Title')
 *     ->setIcon($this->iconFactory->getIcon('actions-heart'))
 *     ->setAttributes(['data-value' => '123']);
 */
class GenericButton implements ButtonInterface
{
    protected string $tag = 'button';
    protected ?Icon $icon = null;
    protected string $label = '';
    protected ?string $title = null;
    protected ?string $href = null;
    protected string $classes = '';
    protected array $attributes = [];
    protected bool $showLabelText = false;

    public function setTag(string $tag): self
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

    public function setIcon(?Icon $icon): self
    {
        if ($icon instanceof Icon) {
            $icon->setSize(Icon::SIZE_SMALL);
        }
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

    public function getClasses(): string
    {
        return $this->classes;
    }

    public function setClasses(string $classes): self
    {
        $this->classes = $classes;
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

    public function getShowLabelText(): bool
    {
        return $this->showLabelText;
    }

    public function setShowLabelText(bool $showLabelText): self
    {
        $this->showLabelText = $showLabelText;
        return $this;
    }

    public function isValid(): bool
    {
        return trim($this->getLabel()) !== ''
            && $this->getType() === self::class
            && $this->getIcon() !== null;
    }

    public function getType(): string
    {
        return static::class;
    }

    protected function getAttributesString(): string
    {
        $attributes = $this->getAttributes();
        $attributes['class'] = rtrim('btn btn-sm btn-default ' . $this->getClasses());
        if ($this->getHref()) {
            $attributes['href'] = $this->getHref();
        }
        if ($this->getTitle()) {
            $attributes['title'] = $this->getTitle();
        }

        return GeneralUtility::implodeAttributes($attributes, true);
    }

    public function render(): string
    {
        $labelText = '';
        if ($this->getShowLabelText()) {
            $labelText = $this->getLabel();
        }

        return '<' . $this->getTag() . ' ' . $this->getAttributesString() . '>'
            . ($this->getIcon() ? $this->getIcon()->render() : '')
            . htmlspecialchars($labelText)
            . '</' . $this->getTag() . '>';
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
