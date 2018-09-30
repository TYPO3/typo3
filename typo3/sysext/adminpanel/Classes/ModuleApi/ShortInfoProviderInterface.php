<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\ModuleApi;

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
 * Adminpanel shortinfo provider interface can be used to add the module to the short info bar of the adminpanel
 *
 * Modules providing shortinfo will be displayed in the bottom bar of the adminpanel and may provide "at a glance" info
 * about the current state (for example the log module provides the number of warnings and errors directly).
 *
 * Be aware that modules with submodules at the moment can only render one short info (the one of the "parent" module).
 * This will likely change in TYPO3 v10.0.
 */
interface ShortInfoProviderInterface
{
    /**
     * Displayed directly in the bar
     *
     * @return string
     */
    public function getShortInfo(): string;

    /**
     * Icon identifier - needs to be registered in iconRegistry
     *
     * @return string
     */
    public function getIconIdentifier(): string;
}
