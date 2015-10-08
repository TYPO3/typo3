<?php
namespace TYPO3\CMS\Frontend\View;

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
 * Interface for classes which hook into AdminPanelView
 */
interface AdminPanelViewHookInterface
{
    /**
     * Extend the adminPanel
     *
     * @param string $moduleContent Content of the admin panel
     * @param \TYPO3\CMS\Frontend\View\AdminPanelView $obj The adminPanel object
     * @return string Returns content of admin panel
     */
    public function extendAdminPanel($moduleContent, AdminPanelView $obj);
}
