<?php
namespace TYPO3\CMS\Backend\Template\Components\Menu;

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

use TYPO3\CMS\Backend\Template\Components\AbstractControl;

/**
 * MenuItem
 */
class MenuItem extends AbstractControl
{
    /**
     * Sets the href of the menuItem
     *
     * @var string
     */
    protected $href = '';

    /**
     * Sets the active state of the menuItem
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Set href
     *
     * @param string $href Href of the MenuItem
     *
     * @return MenuItem
     */
    public function setHref($href)
    {
        $this->href = $href;
        return $this;
    }

    /**
     * Set active
     *
     * @param bool $active Defines whether a menuItem is active
     *
     * @return MenuItem
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * Get href
     *
     * @return string
     */
    public function getHref()
    {
        return $this->href;
    }

    /**
     * Check if is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * Validation
     *
     * @param MenuItem $menuItem The menuItem to validate
     *
     * @return bool
     */
    public function isValid(MenuItem $menuItem)
    {
        if (
            $menuItem->getHref() !== ''
            && $menuItem->getTitle() !== ''
        ) {
            return true;
        }
        return false;
    }
}
