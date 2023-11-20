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

namespace TYPO3\CMS\Backend\CodeEditor\Registry;

use TYPO3\CMS\Backend\CodeEditor\Exception\InvalidModeException;
use TYPO3\CMS\Backend\CodeEditor\Mode;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Registers and holds code editor modes
 * @internal
 */
class ModeRegistry implements SingletonInterface
{
    /**
     * @var Mode[]
     */
    protected array $registeredModes = [];

    protected Mode $defaultMode;

    /**
     * Registers modes for code editor
     */
    public function register(Mode $mode): ModeRegistry
    {
        $this->registeredModes[$mode->getFormatCode()] = $mode;
        if ($mode->isDefault()) {
            $this->defaultMode = $mode;
        }

        return $this;
    }

    /**
     * Removes registered modes
     */
    public function unregister(string $formatCode): ModeRegistry
    {
        if (isset($this->registeredModes[$formatCode])) {
            unset($this->registeredModes[$formatCode]);
        }

        return $this;
    }

    public function isRegistered(string $formatCode): bool
    {
        return isset($this->registeredModes[$formatCode]);
    }

    /**
     * @throws InvalidModeException
     */
    public function getByFormatCode(string $formatCode): Mode
    {
        foreach ($this->registeredModes as $mode) {
            if ($mode->getFormatCode() === $formatCode) {
                return $mode;
            }
        }

        throw new InvalidModeException('Tried to get unregistered code editor mode by format code "' . $formatCode . '"', 1499710203);
    }

    /**
     * @throws InvalidModeException
     */
    public function getByFileExtension(string $fileExtension): Mode
    {
        foreach ($this->registeredModes as $mode) {
            if (in_array($fileExtension, $mode->getBoundFileExtensions(), true)) {
                return $mode;
            }
        }

        throw new InvalidModeException('Cannot find a registered mode for requested file extension "' . $fileExtension . '"', 1500306488);
    }

    public function getDefaultMode(): Mode
    {
        return $this->defaultMode;
    }
}
