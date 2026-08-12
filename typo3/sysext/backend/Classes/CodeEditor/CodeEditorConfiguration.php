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

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Backend\CodeEditor\Exception\InvalidModeException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Provides the modes and addons defined by all active packages in
 * "Configuration/Backend/T3editor/Modes.php" and "Configuration/Backend/T3editor/Addons.php".
 * The compiled definitions are stored in the assets cache.
 *
 * @internal
 */
class CodeEditorConfiguration
{
    private bool $initialized = false;

    /**
     * @var Mode[] Modes with their format code as key
     */
    private array $modes = [];

    private ?Mode $defaultMode = null;

    /**
     * @var Addon[]
     */
    private array $addons = [];

    public function __construct(
        private readonly PackageManager $packageManager,
        #[Autowire(service: 'cache.assets')]
        private readonly FrontendInterface $assetsCache,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").withPrefix("CodeEditorConfiguration").toString()')]
        private readonly string $cacheIdentifier,
    ) {}

    /**
     * @return Mode[] Modes with their format code as key
     */
    public function getModes(): array
    {
        $this->initialize();

        return $this->modes;
    }

    public function hasMode(string $formatCode): bool
    {
        $this->initialize();

        return isset($this->modes[$formatCode]);
    }

    /**
     * @throws InvalidModeException
     */
    public function getModeByFormatCode(string $formatCode): Mode
    {
        $this->initialize();

        return $this->modes[$formatCode] ?? throw new InvalidModeException(
            'Tried to get unregistered code editor mode by format code "' . $formatCode . '"',
            1499710203
        );
    }

    /**
     * @throws InvalidModeException
     */
    public function getModeByFileExtension(string $fileExtension): Mode
    {
        $this->initialize();

        foreach ($this->modes as $mode) {
            if (in_array($fileExtension, $mode->fileExtensions, true)) {
                return $mode;
            }
        }

        throw new InvalidModeException(
            'Cannot find a registered mode for requested file extension "' . $fileExtension . '"',
            1500306488
        );
    }

    /**
     * @throws InvalidModeException
     */
    public function getDefaultMode(): Mode
    {
        $this->initialize();

        return $this->defaultMode ?? throw new InvalidModeException(
            'No code editor mode has been marked as default.',
            1786492801
        );
    }

    /**
     * @return Addon[]
     */
    public function getAddons(): array
    {
        $this->initialize();

        return $this->addons;
    }

    /**
     * Options of all addons, merged in definition order
     */
    public function getAddonSettings(): array
    {
        $this->initialize();

        $settings = [];
        foreach ($this->addons as $addon) {
            $settings = array_merge($settings, $addon->options);
        }

        return $settings;
    }

    private function initialize(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $configuration = $this->loadConfiguration();

        foreach ($configuration['modes'] as $formatCode => $mode) {
            $modeInstance = new Mode(
                module: $mode['module'],
                formatCode: (string)$formatCode,
                fileExtensions: is_array($mode['extensions'] ?? null) ? $mode['extensions'] : [],
                isDefault: ($mode['default'] ?? false) === true,
            );

            if ($modeInstance->isDefault) {
                $this->defaultMode = $modeInstance;
            }

            $this->modes[$formatCode] = $modeInstance;
        }

        foreach ($configuration['addons'] as $identifier => $addon) {
            $this->addons[] = new Addon(
                identifier: (string)$identifier,
                module: $addon['module'] ?? null,
                keymap: $addon['keymap'] ?? null,
                options: is_array($addon['options'] ?? null) ? $addon['options'] : [],
                cssFiles: is_array($addon['cssFiles'] ?? null) ? $addon['cssFiles'] : [],
            );
        }
    }

    private function loadConfiguration(): array
    {
        $configuration = $this->assetsCache->get($this->cacheIdentifier);
        if ($configuration === false) {
            $configuration = $this->createConfiguration();
            $this->assetsCache->set($this->cacheIdentifier, $configuration);
        }

        return $configuration;
    }

    private function createConfiguration(): array
    {
        $configuration = [
            'modes' => [],
            'addons' => [],
        ];

        foreach ($this->packageManager->getActivePackages() as $package) {
            $configurationPath = $package->getPackagePath() . 'Configuration/Backend/T3editor';

            $modesFileNameForPackage = $configurationPath . '/Modes.php';
            if (is_file($modesFileNameForPackage)) {
                $definedModes = require $modesFileNameForPackage;
                if (is_array($definedModes)) {
                    $configuration['modes'] = array_merge($configuration['modes'], $definedModes);
                }
            }

            $addonsFileNameForPackage = $configurationPath . '/Addons.php';
            if (is_file($addonsFileNameForPackage)) {
                $definedAddons = require $addonsFileNameForPackage;
                if (is_array($definedAddons)) {
                    $configuration['addons'] = array_merge($configuration['addons'], $definedAddons);
                }
            }
        }

        return $configuration;
    }
}
