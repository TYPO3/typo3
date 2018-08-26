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
 * Adminpanel page settings interface denotes that a module has settings regarding the page rendering.
 * The adminpanel knows two types of settings:
 * - ModuleSettings are relevant for the module itself and its representation (for example the log module provides settings
 *   where displayed log level and grouping of the module can be configured)
 * - PageSettings are relevant for rendering the page (for example the preview module provides settings showing or hiding
 *   hidden content elements or simulating a specific rendering time)
 * If a module provides settings changing the rendering of the main page request, use this interface.
 *
 * @see \TYPO3\CMS\Adminpanel\ModuleApi\ModuleSettingsProviderInterface
 */
interface PageSettingsProviderInterface
{
    /**
     * @return string
     */
    public function getPageSettings(): string;
}
