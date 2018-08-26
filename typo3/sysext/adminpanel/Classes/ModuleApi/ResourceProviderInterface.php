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
 * Adminpanel interface to denote that a module has own resource files.
 *
 * An adminpanel module implementing this interface may deliver custom JavaScript and Css files to provide additional
 * styling and JavaScript functionality
 */
interface ResourceProviderInterface
{
    /**
     * Returns a string array with javascript files that will be rendered after the module
     *
     * Example: return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Edit.js'];
     *
     * @return array
     */
    public function getJavaScriptFiles(): array;

    /**
     * Returns a string array with css files that will be rendered after the module
     *
     * Example: return ['EXT:adminpanel/Resources/Public/JavaScript/Modules/Edit.css'];
     *
     * @return array
     */
    public function getCssFiles(): array;
}
