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

namespace TYPO3\CMS\Core\Package;

use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Detects extensions with composer deficits, e.g. missing
 * composer.json file or missing extension-key property.
 */
class ComposerDeficitDetector
{
    public const EXTENSION_COMPOSER_MANIFEST_VALID = 0;
    public const EXTENSION_COMPOSER_MANIFEST_MISSING = 1;
    public const EXTENSION_KEY_MISSING = 2;

    /**
     * Get all extensions with composer deficit
     */
    public function getExtensionsWithComposerDeficit(): array
    {
        $finder = Finder::create()->directories()->depth(0)->in(Environment::getExtensionsPath());
        $extensionsWithDeficit = [];

        if ($finder->hasResults()) {
            foreach ($finder as $extensionFolder) {
                $extensionKey = $extensionFolder->getFilename();
                try {
                    $extensionComposerDeficit = $this->checkExtensionComposerDeficit($extensionKey);
                } catch (\InvalidArgumentException $e) {
                    // Skip invalid extensions
                    continue;
                }
                if ($extensionComposerDeficit !== self::EXTENSION_COMPOSER_MANIFEST_VALID) {
                    $extensionsWithDeficit[$extensionKey] = $extensionComposerDeficit;
                }
            }
        }

        return $extensionsWithDeficit;
    }

    /**
     * Check an extension key for composer deficits like invalid or missing composer.json
     */
    public function checkExtensionComposerDeficit(string $extensionKey): int
    {
        if (!$this->isValidExtensionKey($extensionKey)) {
            throw new \InvalidArgumentException('Extension key ' . $extensionKey . ' is not valid.', 1619446378);
        }

        $composerManifestPath = Environment::getExtensionsPath() . '/' . $extensionKey . '/composer.json';

        if (!file_exists($composerManifestPath) || !($composerManifest = file_get_contents($composerManifestPath))) {
            return self::EXTENSION_COMPOSER_MANIFEST_MISSING;
        }

        $composerManifest = json_decode($composerManifest, true) ?? [];

        if (!is_array($composerManifest) || $composerManifest === []) {
            // Treat empty or invalid composer.json as missing
            return self::EXTENSION_COMPOSER_MANIFEST_MISSING;
        }

        return empty($composerManifest['extra']['typo3/cms']['extension-key'])
            ? self::EXTENSION_KEY_MISSING
            : self::EXTENSION_COMPOSER_MANIFEST_VALID;
    }

    protected function isValidExtensionKey(string $extensionKey): bool
    {
        return preg_match('/^[0-9a-z._\-]+$/i', $extensionKey)
            && GeneralUtility::isAllowedAbsPath(Environment::getExtensionsPath() . '/' . $extensionKey);
    }
}
