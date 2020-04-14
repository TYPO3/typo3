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
 * Represents a mode for CodeMirror
 * @internal
 */
class Mode
{
    /**
     * @var string
     */
    protected $identifier = '';

    /**
     * @var string
     */
    protected $formatCode = '';

    /**
     * @var array
     */
    protected $fileExtensions = [];

    /**
     * @var bool
     */
    protected $isDefault = false;

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
     * @return string
     */
    public function getFormatCode(): string
    {
        return $this->formatCode;
    }

    /**
     * @param string $formatCode
     * @return Mode
     */
    public function setFormatCode(string $formatCode): Mode
    {
        $this->formatCode = $formatCode;

        return $this;
    }

    /**
     * @param array $fileExtensions
     * @return self
     */
    public function bindToFileExtensions(array $fileExtensions): Mode
    {
        $this->fileExtensions = $fileExtensions;

        return $this;
    }

    /**
     * @return array
     */
    public function getBoundFileExtensions(): array
    {
        return $this->fileExtensions;
    }

    /**
     * @return Mode
     */
    public function setAsDefault(): Mode
    {
        $this->isDefault = true;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }
}
