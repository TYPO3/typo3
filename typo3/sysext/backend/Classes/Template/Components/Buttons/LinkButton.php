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
 * This button type renders a regular anchor tag with TYPO3s way to render a
 * button control.
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
 *     $saveButton = $this->componentFactory->createLinkButton()
 *          ->setHref('#')
 *          ->setDataAttributes([
 *              'foo' => 'bar'
 *          ])
 *          ->setIcon($this->iconFactory->getIcon('actions-document-save', IconSize::SMALL))
 *          ->setTitle('Save');
 *     $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
 * }
 * ```
 */
class LinkButton extends AbstractButton
{
    protected string $href = '';

    protected string $role = 'button';

    public function getHref(): string
    {
        return $this->href;
    }

    public function setHref(string $href): static
    {
        $this->href = $href;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function isValid(): bool
    {
        return trim($this->getHref()) !== ''
            && trim($this->getTitle()) !== ''
            && $this->getType() === static::class
            && $this->getIcon() !== null;
    }

    public function render(): string
    {
        $attributes = [
            'role' => $this->getRole(),
            'href' => $this->getHref(),
            // @see SplitButton - hard-coded replacement for this hard-coded class-list
            'class' => 'btn btn-sm btn-default ' . $this->getClasses(),
            'title' => $this->getTitle(),
        ];
        $labelText = '';
        if ($this->showLabelText) {
            $labelText = ' ' . $this->title;
        }
        foreach ($this->dataAttributes as $attributeName => $attributeValue) {
            $attributes['data-' . $attributeName] = $attributeValue;
        }
        if ($this->isDisabled()) {
            $attributes['aria-disabled'] = 'true';
            $attributes['class'] .= ' disabled';
        }
        return sprintf(
            '<a %s>%s%s</a>',
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
