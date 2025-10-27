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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A flexible button type that allows rendering any HTML tag (button, anchor, or custom
 * web components) with full control over attributes. Unlike other button types that
 * extend AbstractButton, GenericButton implements ButtonInterface directly, providing
 * maximum flexibility for custom implementations.
 *
 * Use cases:
 * - Custom web components (e.g., <typo3-custom-element>)
 * - Standard buttons with custom attributes
 * - Links with button styling
 * - Any HTML element that should appear as a button in the document header
 *
 * Key features:
 * - Configurable HTML tag (button, a, custom elements)
 * - Full attribute control (href, data attributes, etc.)
 * - Icon and label support
 * - Automatic HTML escaping for security
 *
 * Example - Standard button:
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * public function myAction(): ResponseInterface
 * {
 *     $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
 *     $button = $this->componentFactory->createGenericButton()
 *         ->setTag('button')
 *         ->setLabel('Save')
 *         ->setTitle('Save changes')
 *         ->setIcon($iconFactory->getIcon('actions-save'))
 *         ->setShowLabelText(true)
 *         ->setAttributes(['type' => 'submit', 'form' => 'myForm']);
 *     $buttonBar->addButton($button);
 * }
 * ```
 *
 * Example - Web component:
 *
 * ```
 * $button = $this->componentFactory->createGenericButton()
 *     ->setTag('typo3-my-action-button')
 *     ->setLabel('Custom Action')
 *     ->setIcon($iconFactory->getIcon('actions-heart'))
 *     ->setAttributes([
 *         'url' => '/my-action',
 *         'data-value' => '123'
 *     ]);
 * $buttonBar->addButton($button);
 * ```
 *
 * Example - Link styled as button:
 *
 * ```
 * $button = $this->componentFactory->createGenericButton()
 *     ->setTag('a')
 *     ->setHref('/target-page')
 *     ->setLabel('View Page')
 *     ->setTitle('Open target page')
 *     ->setIcon($iconFactory->getIcon('actions-view'));
 * $buttonBar->addButton($button);
 * ```
 */
class GenericButton implements ButtonInterface
{
    protected string $tag = 'button';
    protected ?Icon $icon = null;
    protected string $label = '';
    protected ?string $title = null;
    protected ?string $href = null;
    protected string $classes = '';
    protected ButtonSize $size = ButtonSize::SMALL;
    protected array $attributes = [];
    protected bool $showLabelText = false;

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

    public function getClasses(): string
    {
        return $this->classes;
    }

    public function setClasses(string $classes): static
    {
        $this->classes = $classes;
        return $this;
    }

    public function getSize(): ButtonSize
    {
        return $this->size;
    }

    public function setSize(ButtonSize $size): static
    {
        $this->size = $size;
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

    public function setShowLabelText(bool $showLabelText): static
    {
        $this->showLabelText = $showLabelText;
        return $this;
    }

    public function isValid(): bool
    {
        return trim($this->getLabel()) !== ''
            && $this->getType() === static::class
            && $this->getIcon() !== null;
    }

    public function getType(): string
    {
        return static::class;
    }

    protected function getAttributesString(): string
    {
        $attributes = $this->getAttributes();
        $attributes['class'] = rtrim('btn ' . $this->getSize()->value . ' btn-default ' . $this->getClasses());
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
        return sprintf(
            '<%1$s %2$s>%3$s%4$s</%1$s>',
            $this->getTag(),
            $this->getAttributesString(),
            $this->getIcon()?->render() ?? '',
            htmlspecialchars($this->getShowLabelText() ? $this->getLabel() : '')
        );
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
