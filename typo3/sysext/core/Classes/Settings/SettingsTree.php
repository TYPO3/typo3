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

namespace TYPO3\CMS\Core\Settings;

use TYPO3\CMS\Core\Utility\ArrayUtility;

/**
 * @internal
 */
final readonly class SettingsTree
{
    /**
     * @param string[] $changes
     * @param string[] $deletions
     */
    public function __construct(
        public array $settingsTree,
        public array $changes,
        public array $deletions,
    ) {}

    public function asArray(): array
    {
        return $this->settingsTree;
    }

    /**
     * Calculate a new settings tree for the given $targetSettings
     *
     * Settings that have the same value as their default value
     * are removed (tree is minified) if the list of default settings
     * via $defaultSettings.
     *
     * @param array $currentSettingsTree Current settings tree (recursive structure)
     *                                   In case of site settings: config/sites/…/settings.yaml
     * @param SettingsInterface $targetSettings Target settings
     *                                          (values as supplied via the settings editor)
     * @param SettingsInterface $defaultSettings Default settings, without local settings tree applied.
     *                                           In case of site settings: Combination of all settings
     *                                           defined in settings.definitions.yaml + setting.yaml
     *                                           from all selected sets combined
     */
    public static function diff(
        array $currentSettingsTree,
        SettingsInterface $targetSettings,
        ?SettingsInterface $defaultSettings = null,
    ): self {
        // Copy existing settings from current settings tree, to keep any settings
        // that have been present before (and are not defined in $defaultSettings)
        // Usecase for site settings:
        // Preserve "anonymous" v12-style site settings that have no definition in settings.definitions.yaml
        $settingsTree = $currentSettingsTree;

        // Merge target settings into current settingsTree
        $changes = [];
        $deletions = [];
        foreach ($targetSettings->getIdentifiers() as $key) {
            $value = $targetSettings->get($key);
            if ($defaultSettings !== null && $value === $defaultSettings->get($key)) {
                if (ArrayUtility::isValidPath($settingsTree, $key, '.')) {
                    $settingsTree = self::removeByPathWithAncestors($settingsTree, $key, '.');
                    $deletions[] = $key;
                }
                continue;
            }
            if (!ArrayUtility::isValidPath($settingsTree, $key, '.') ||
                $value !== ArrayUtility::getValueByPath($settingsTree, $key, '.')
            ) {
                $settingsTree = ArrayUtility::setValueByPath($settingsTree, $key, $value, '.');
                $changes[] = $key;
            }
        }

        return new self(
            $settingsTree,
            $changes,
            $deletions
        );
    }

    private static function removeByPathWithAncestors(array $array, string $path, string $delimiter): array
    {
        if ($path === '' || !ArrayUtility::isValidPath($array, $path, $delimiter)) {
            return $array;
        }

        $array = ArrayUtility::removeByPath($array, $path, $delimiter);
        $parts = explode($delimiter, $path);
        array_pop($parts);
        $parentPath = implode($delimiter, $parts);

        if ($parentPath !== '' && ArrayUtility::isValidPath($array, $parentPath, $delimiter)) {
            $parent = ArrayUtility::getValueByPath($array, $parentPath, $delimiter);
            if ($parent === []) {
                return self::removeByPathWithAncestors($array, $parentPath, $delimiter);
            }
        }
        return $array;
    }
}
