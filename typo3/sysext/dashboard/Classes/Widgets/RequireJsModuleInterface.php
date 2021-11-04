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

namespace TYPO3\CMS\Dashboard\Widgets;

/**
 * In case a widget should provide additional requireJS modules, the widget must implement this interface.
 * @todo deprecate in TYPO3 v12.0
 * @internal migrate to JavaScriptInstructionInterface in TYPO3 v12.0
 */
interface RequireJsModuleInterface
{
    /**
     * This method returns an array with requireJs modules.
     * e.g. [
     *   'TYPO3/CMS/Backend/Modal',
     *   'TYPO3/CMS/MyExt/FooBar' => 'function(FooBar) { ... }'
     * ]
     *
     * @return list<string>|array<string, string>
     */
    public function getRequireJsModules(): array;
}
