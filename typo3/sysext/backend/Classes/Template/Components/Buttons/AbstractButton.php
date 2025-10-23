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

use TYPO3\CMS\Backend\Template\Components\AbstractControl;
use TYPO3\CMS\Core\Imaging\Icon;

/**
 * Base class for all button types in the backend document header.
 * Provides common functionality for buttons including icon handling, label text display,
 * and disabled state management.
 *
 * This class extends AbstractControl (providing title, classes, data attributes) and
 * implements ButtonInterface (providing validation, type identification, and rendering).
 *
 * @see ButtonInterface
 * @see AbstractControl
 */
class AbstractButton extends AbstractControl implements ButtonInterface
{
    /**
     * Optional icon to display on the button
     */
    protected ?Icon $icon = null;

    /**
     * Whether to show the button's label text (from title property).
     * If false, only the icon is shown (label is used for title attribute).
     */
    protected bool $showLabelText = false;

    /**
     * Whether the button is in disabled state
     */
    protected bool $disabled = false;

    public function getShowLabelText(): bool
    {
        return $this->showLabelText;
    }

    public function setShowLabelText(bool $showLabelText): static
    {
        $this->showLabelText = $showLabelText;
        return $this;
    }

    public function getIcon(): ?Icon
    {
        return $this->icon;
    }

    public function getType(): string
    {
        return static::class;
    }

    public function setIcon(?Icon $icon): static
    {
        $this->icon = $icon;
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

    /**
     * Implementation from ButtonInterface
     * This object is an abstract, so no implementation is necessary
     */
    public function isValid(): bool
    {
        return false;
    }

    /**
     * Implementation from ButtonInterface
     * This object is an abstract, so no implementation is necessary
     */
    public function __toString(): string
    {
        return '';
    }

    /**
     * Implementation from ButtonInterface
     * This object is an abstract, so no implementation is necessary
     *
     * @return string
     */
    public function render(): string
    {
        return '';
    }
}
