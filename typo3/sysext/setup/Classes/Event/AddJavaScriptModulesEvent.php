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
    private $modules = [];

    public function addModule(string $moduleName): void
    {
        if (in_array($moduleName, $this->modules, true)) {
            return;
        }
        $this->modules[] = $moduleName;
    }

    /**
     * @return string[]
     */
    public function getModules(): array
    {
        return $this->modules;
    }
}
