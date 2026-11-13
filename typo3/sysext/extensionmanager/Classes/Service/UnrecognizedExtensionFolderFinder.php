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

namespace TYPO3\CMS\Extensionmanager\Service;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extensionmanager\Enum\UnrecognizedExtensionFolderReason;

/**
 * Finds folders in the extensions path of a classic mode installation which
 * the package manager skips, because their composer.json is missing or does
 * not describe a TYPO3 extension.
 *
 * @internal
 */
readonly class UnrecognizedExtensionFolderFinder
{
    public function __construct(
        private PackageManager $packageManager,
    ) {}

    /**
     * @return array<string, UnrecognizedExtensionFolderReason> keyed by folder name
     */
    public function find(): array
    {
        $extensionsPath = Environment::getExtensionsPath();
        if (Environment::isComposerMode() || !is_dir($extensionsPath)) {
            return [];
        }
        $registeredPaths = [];
        foreach ($this->packageManager->getAvailablePackages() as $package) {
            $registeredPaths[] = realpath($package->getPackagePath());
        }
        $unrecognizedFolders = [];
        foreach (new \DirectoryIterator($extensionsPath) as $folder) {
            if (!$folder->isDir() || str_starts_with($folder->getFilename(), '.')) {
                continue;
            }
            if (in_array(realpath($folder->getPathname()), $registeredPaths, true)) {
                continue;
            }
            $reason = $this->determineReason($folder->getPathname() . '/composer.json');
            if ($reason !== null) {
                $unrecognizedFolders[$folder->getFilename()] = $reason;
            }
        }
        ksort($unrecognizedFolders);
        return $unrecognizedFolders;
    }

    private function determineReason(string $composerJsonPath): ?UnrecognizedExtensionFolderReason
    {
        if (!is_file($composerJsonPath)) {
            return UnrecognizedExtensionFolderReason::MissingComposerJson;
        }
        try {
            $manifest = json_decode((string)file_get_contents($composerJsonPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return UnrecognizedExtensionFolderReason::InvalidComposerJson;
        }
        if (!is_array($manifest)) {
            return UnrecognizedExtensionFolderReason::InvalidComposerJson;
        }
        if (!str_starts_with((string)($manifest['type'] ?? ''), 'typo3-cms-')) {
            return UnrecognizedExtensionFolderReason::NoTypo3PackageType;
        }
        if (empty($manifest['extra']['typo3/cms']['extension-key'])) {
            return UnrecognizedExtensionFolderReason::MissingExtensionKey;
        }
        return null;
    }
}
