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

namespace TYPO3\CMS\Backend\Template\Components\Menu;

use TYPO3\CMS\Backend\Template\Components\AbstractControl;

/**
 * Represents a single item within a Menu in the backend module document header.
 * Each MenuItem has a title, URL (href), and can be marked as active to indicate
 * the current selection.
 *
 * MenuItems inherit from AbstractControl, providing access to common properties like
 * title, CSS classes, and data attributes.
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
 *     $menu = $this->componentFactory->createMenu();
 *     $menuItem = $this->componentFactory->createMenuItem()
 *         ->setTitle('List View')
 *         ->setHref('/my-module?view=list')
 *         ->setActive(true)  // Marks this item as currently selected
 *         ->setClasses('my-custom-class')
 *         ->setDataAttributes(['action' => 'switch-view']);
 *     $menu->addMenuItem($menuItem);
 * }
 * ```
 */
class MenuItem extends AbstractControl
{
    protected string $href = '';

    protected bool $active = false;

    public function setHref(string $href): static
    {
        $this->href = $href;
        return $this;
    }

    public function getHref(): string
    {
        return $this->href;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function isValid(): bool
    {
        return $this->getHref() !== '' && $this->getTitle() !== '';
    }
}
