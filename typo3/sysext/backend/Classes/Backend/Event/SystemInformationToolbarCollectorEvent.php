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

namespace TYPO3\CMS\Backend\Backend\Event;

use TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem;

/**
 * An event to enrich the system information toolbar in the TYPO3 Backend top toolbar
 * with various information
 */
final class SystemInformationToolbarCollectorEvent
{
    /**
     * @var SystemInformationToolbarItem
     */
    private $toolbarItem;

    public function __construct(SystemInformationToolbarItem $toolbarItem)
    {
        $this->toolbarItem = $toolbarItem;
    }

    public function getToolbarItem(): SystemInformationToolbarItem
    {
        return $this->toolbarItem;
    }
}
