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
 * Adminpanel interface to denote that a module has content to be rendered
 *
 * In general there are two main types of admin panel modules: those providing settings influencing page rendering
 * (for example the preview module provides settings for displaying hidden pages or records) and those rendering
 * information regarding the current request (for example the log module).
 *
 * Modules implementing this interface denote that they render module content.
 */
interface ContentProviderInterface
{
    /**
     * Main method for content generation of an admin panel module.
     * Return content as HTML. For modules implementing the DataProviderInterface
     * the "ModuleData" object is automatically filled with the stored data - if
     * no data is given a "fresh" ModuleData object is injected.
     *
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData $data
     * @return string
     */
    public function getContent(ModuleData $data): string;
}
