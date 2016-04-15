<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Support\Helper;

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

/**
 * Helper to interact with the Topbar
 */
class Topbar
{
    /**
     * Selector for the topbar container
     *
     * @var string
     */
    public static $containerSelector = '#typo3-top-container';

    /**
     * Selector for the dropdown container
     *
     * @var string
     */
    public static $dropdownContainerSelector = '.dropdown-menu';

    /**
     * Selector for the dropdown container
     *
     * @var string
     */
    public static $dropdownListSelector = '.dropdown-menu .dropdown-list';

    /**
     * Selector for the dropdown toggle
     *
     * @var string
     */
    public static $dropdownToggleSelector = '.dropdown-toggle';
}
