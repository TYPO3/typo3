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
 * Adminpanel module settings interface denotes that a module has own settings.
 *
 * The adminpanel knows two types of settings:
 * - ModuleSettings are relevant for the module itself and its representation (for example the log module provides settings
 *   where displayed log level and grouping of the module can be configured)
 * - PageSettings are relevant for rendering the page (for example the preview module provides settings showing or hiding
 *   hidden content elements or simulating a specific rendering time)
 *
 * If a module provides settings relevant to its own content, use this interface.
 *
 * @see \TYPO3\CMS\Adminpanel\ModuleApi\PageSettingsProviderInterface
 */
interface ModuleSettingsProviderInterface
{
    /**
     * @return string
     */
    public function getSettings(): string;
}
