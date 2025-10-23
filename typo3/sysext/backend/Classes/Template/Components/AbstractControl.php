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

namespace TYPO3\CMS\Backend\Template\Components;

/**
 * Base class providing common properties for UI controls in the backend.
 * Provides standard HTML attributes like title, CSS classes, and data attributes
 * that are shared across various component types (buttons, menu items, etc.).
 *
 * This class is extended by components like:
 * - AbstractButton
 * - MenuItem
 *
 * Example (inherited in MenuItem):
 *
 * ```
 * public function __construct(
 *     protected readonly ComponentFactory $componentFactory,
 * ) {}
 *
 * $menuItem = $this->componentFactory->createMenuItem()
 *     ->setTitle('My Item')              // From AbstractControl
 *     ->setClasses('custom-class')       // From AbstractControl
 *     ->setDataAttributes([              // From AbstractControl
 *         'action' => 'do-something'
 *     ])
 *     ->setHref('/target');              // MenuItem-specific
 * ```
 */
class AbstractControl
{
    /**
     * CSS classes to apply to the rendered element
     */
    protected string $classes = '';

    /**
     * Title/label text for the control
     */
    protected string $title = '';

    /**
     * HTML data-* attributes for the control
     *
     * @var array<string, string> Key-value pairs (e.g., ['action' => 'save'])
     */
    protected array $dataAttributes = [];

    public function getClasses(): string
    {
        return $this->classes;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getDataAttributes(): array
    {
        return $this->dataAttributes;
    }

    public function setClasses(string $classes): static
    {
        $this->classes = $classes;
        return $this;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function setDataAttributes(array $dataAttributes): static
    {
        $this->dataAttributes = $dataAttributes;
        return $this;
    }
}
