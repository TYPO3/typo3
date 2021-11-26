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

namespace TYPO3\CMS\Backend\Toolbar;

/**
 * Interface for classes which extend the backend by adding items to the top toolbar
 */
interface ToolbarItemInterface
{
    /**
     * Checks whether the user has access to this toolbar item
     * @TODO: Split into two methods a permission method and a "hasContent" or similar
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess();

    /**
     * Render "item" part of this toolbar
     *
     * @return string Toolbar item HTML
     */
    public function getItem();

    /**
     * TRUE if this toolbar item has a collapsible drop down
     *
     * @return bool
     */
    public function hasDropDown();

    /**
     * Render "drop down" part of this toolbar
     *
     * @return string Drop down HTML
     */
    public function getDropDown();

    /**
     * Returns an array with additional attributes added to containing <li> tag of the item.
     *
     * Typical usages are additional css classes and data-* attributes, classes may be merged
     * with other classes needed by the framework. Do NOT set an id attribute here.
     *
     * array(
     *     'class' => 'my-class',
     *     'data-foo' => '42',
     * )
     *
     * @return array List item HTML attributes
     */
    public function getAdditionalAttributes();

    /**
     * Returns an integer between 0 and 100 to determine
     * the position of this item relative to others
     *
     * By default, extensions should return 50 to be sorted between main core
     * items and other items that should be on the very right.
     *
     * @return int 0 .. 100
     */
    public function getIndex();
}
