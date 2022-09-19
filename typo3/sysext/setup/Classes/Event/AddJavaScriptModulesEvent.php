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

namespace TYPO3\CMS\Setup\Event;

/**
 * Collects additional JavaScript modules to be loaded in SetupModuleController.
 */
final class AddJavaScriptModulesEvent
{
    /**
     * @var string[]
     */
    private array $javaScriptModules = [];

    /**
     * @var string[]
     */
    private array $modules = [];

    /**
     * @param string $specifier Bare module identifier like @my/package/filename.js
     */
    public function addJavaScriptModule(string $specifier): void
    {
        if (in_array($specifier, $this->javaScriptModules, true)) {
            return;
        }
        $this->javaScriptModules[] = $specifier;
    }

    /**
     * @deprecated will be removed in TYPO3 v13.0. Use addJavaScriptModule() instead, available since TYPO3 v12.0.
     */
    public function addModule(string $moduleName): void
    {
        trigger_error('AddJavaScriptModulesEvent->addModule is deprecated in favor of native ES6 modules and will be removed in TYPO3 v13.0. Use an ES6 module via addJavaScriptModule instead.', E_USER_DEPRECATED);
        if (in_array($moduleName, $this->modules, true)) {
            return;
        }
        $this->modules[] = $moduleName;
    }

    /**
     * @return string[]
     */
    public function getJavaScriptModules(): array
    {
        return $this->javaScriptModules;
    }

    /**
     * @return string[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }
}
