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

use TYPO3\CMS\Backend\Template\Components\AbstractControl;
use TYPO3\CMS\Core\Imaging\Icon;

/**
 * AbstractButton
 */
class AbstractButton extends AbstractControl implements ButtonInterface
{
    /**
     * Icon object
     *
     * @var Icon
     */
    protected $icon;

    /**
     * Defines whether to show the title as a label within the button
     *
     * @var bool
     */
    protected $showLabelText = false;

    /**
     * Disabled state of the button
     *
     * @var bool
     */
    protected $disabled = false;

    /**
     * Show Label text
     *
     * @return bool
     */
    public function getShowLabelText()
    {
        return $this->showLabelText;
    }

    /**
     * Show Label text
     *
     * @param bool $showLabelText
     *
     * @return $this
     */
    public function setShowLabelText($showLabelText)
    {
        $this->showLabelText = $showLabelText;
        return $this;
    }

    /**
     * Get icon
     *
     * @return Icon
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return static::class;
    }

    /**
     * Set icon
     *
     * @param Icon $icon Icon object for the button
     *
     * @return $this
     */
    public function setIcon(Icon $icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Check if button is disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Set if button needs to be disabled
     *
     * @param bool $disabled
     * @return AbstractButton
     */
    public function setDisabled(bool $disabled): AbstractButton
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * Implementation from ButtonInterface
     * This object is an abstract, so no implementation is necessary
     *
     * @return bool
     */
    public function isValid()
    {
        return false;
    }

    /**
     * Implementation from ButtonInterface
     * This object is an abstract, so no implementation is necessary
     *
     * @return string
     */
    public function __toString()
    {
        return '';
    }

    /**
     * Implementation from ButtonInterface
     * This object is an abstract, so no implementation is necessary
     *
     * @return string
     */
    public function render()
    {
        return '';
    }
}
