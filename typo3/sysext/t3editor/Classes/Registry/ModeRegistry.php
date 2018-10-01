<?php
declare(strict_types = 1);
namespace TYPO3\CMS\T3editor\Registry;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\T3editor\Exception\InvalidModeException;
use TYPO3\CMS\T3editor\Mode;

/**
 * Registers and holds t3editor modes
 * @internal
 */
class ModeRegistry implements SingletonInterface
{
    /**
     * @var Mode[]
     */
    protected $registeredModes = [];

    /**
     * @var Mode
     */
    protected $defaultMode;

    /**
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function getInstance(): ModeRegistry
    {
        return GeneralUtility::makeInstance(static::class);
    }

    /**
     * Registers modes for t3editor
     *
     * @param Mode $mode
     * @return self
     */
    public function register(Mode $mode): ModeRegistry
    {
        $this->registeredModes[$mode->getIdentifier()] = $mode;
        if ($mode->isDefault()) {
            $this->defaultMode = $mode;
        }

        return $this;
    }

    /**
     * Removes registered modes
     *
     * @param string $identifier
     * @return self
     */
    public function unregister(string $identifier): ModeRegistry
    {
        if (isset($this->registeredModes[$identifier])) {
            unset($this->registeredModes[$identifier]);
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function isRegistered(string $identifier): bool
    {
        return isset($this->registeredModes[$identifier]);
    }

    /**
     * @param string $identifier
     * @return Mode
     * @throws InvalidModeException
     */
    public function getByIdentifier(string $identifier): Mode
    {
        if ($this->isRegistered($identifier)) {
            return $this->registeredModes[$identifier];
        }

        throw new InvalidModeException('Tried to get unregistered t3editor mode "' . $identifier . '"', 1499710202);
    }

    /**
     * @param string $formatCode
     * @return Mode
     * @throws InvalidModeException
     */
    public function getByFormatCode(string $formatCode): Mode
    {
        foreach ($this->registeredModes as $mode) {
            if ($mode->getFormatCode() === $formatCode) {
                return $mode;
            }
        }

        throw new InvalidModeException('Tried to get unregistered t3editor mode by format code "' . $formatCode . '"', 1499710203);
    }

    /**
     * @param string $fileExtension
     * @return Mode
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

    /**
     * @return Mode
     */
    public function getDefaultMode(): Mode
    {
        return $this->defaultMode;
    }
}
