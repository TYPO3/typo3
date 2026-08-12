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

namespace TYPO3\CMS\Backend\CodeEditor;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Represents an addon for CodeMirror
 * @internal
 */
final readonly class Addon
{
    /**
     * @param string[] $cssFiles
     */
    public function __construct(
        public string $identifier,
        public ?JavaScriptModuleInstruction $module = null,
        public ?JavaScriptModuleInstruction $keymap = null,
        public array $options = [],
        public array $cssFiles = [],
    ) {}
}
