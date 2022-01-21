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

namespace TYPO3\CMS\T3editor;

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;

/**
 * Represents an addon for CodeMirror
 * @internal
 */
class Addon
{
    protected string $identifier;

    protected ?JavaScriptModuleInstruction $module = null;

    protected ?JavaScriptModuleInstruction $keymap = null;

    /**
     * @var array
     */
    protected $modes = [];

    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $cssFiles = [];

    public function __construct(
        string $identifier,
        ?JavaScriptModuleInstruction $module = null,
        ?JavaScriptModuleInstruction $keymap = null
    ) {
        $this->identifier = $identifier;
        $this->module = $module;
        $this->keymap = $keymap;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getModule(): ?JavaScriptModuleInstruction
    {
        return $this->module;
    }

    public function getKeymap(): ?JavaScriptModuleInstruction
    {
        return $this->keymap;
    }

    /**
     * @param array $options
     * @return self
     */
    public function setOptions(array $options): Addon
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $cssFiles
     * @return self
     */
    public function setCssFiles(array $cssFiles): Addon
    {
        $this->cssFiles = $cssFiles;

        return $this;
    }

    /**
     * @return array
     */
    public function getCssFiles(): array
    {
        return $this->cssFiles;
    }
}
