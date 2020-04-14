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

/**
 * Represents an addon for CodeMirror
 * @internal
 */
class Addon
{
    /**
     * @var string
     */
    protected $identifier = '';

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

    /**
     * @param string $identifier
     */
    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param array $modes
     * @return self
     */
    public function setModes(array $modes): Addon
    {
        $this->modes = $modes;

        return $this;
    }

    /**
     * @return array
     */
    public function getModes(): array
    {
        return $this->modes;
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
