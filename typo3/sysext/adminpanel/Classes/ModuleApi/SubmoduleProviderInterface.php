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
 * Adminpanel interface providing hierarchical functionality for modules
 *
 * A module implementing this interface may have submodules. Be aware that the current implementation of the adminpanel
 * renders a maximum level of 2 for modules. If you need to render more levels, write your own module and implement
 * multi-hierarchical rendering in the getContent method
 *
 * @see \TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface::getContent()
 */
interface SubmoduleProviderInterface
{
    /**
     * Sets array of module instances (instances of `ModuleInterface`) as submodules
     *
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface[] $subModules
     */
    public function setSubModules(array $subModules): void;

    /**
     * Returns an array of module instances
     *
     * @return \TYPO3\CMS\Adminpanel\ModuleApi\ModuleInterface[]
     */
    public function getSubModules(): array;

    /**
     * Return true if any of the submodules has settings to be rendered
     * (can be used to render settings in a central place)
     *
     * @return bool
     */
    public function hasSubmoduleSettings(): bool;
}
