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

namespace TYPO3\CMS\Form\Domain\DTO\FormConfiguration;

/**
 * Shared helpers to normalize raw values of the form YAML configuration into
 * predictable, typed structures.
 *
 * @internal
 */
trait ConfigurationValueNormalizationTrait
{
    /**
     * Normalize a configuration value into a numerically indexed list of strings.
     *
     * The YAML configuration may use associative keys (e.g. `10:`, `100:`) to
     * define ordering, so values are cast to strings and re-indexed.
     *
     * @param list<string> $default
     * @return list<string>
     */
    private static function normalizeStringList(mixed $value, array $default = []): array
    {
        if (!is_array($value)) {
            return $default;
        }

        return array_values(array_map(strval(...), $value));
    }

    /**
     * Normalize a configuration value into a map of strings, preserving keys.
     *
     * @return array<string, string>
     */
    private static function normalizeStringMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_map(strval(...), $value);
    }

    /**
     * Normalize translation files, keeping the integer priority keys intact
     * because the TranslationService relies on them for ordering.
     *
     * @return array<int, string>
     */
    private static function normalizeTranslationFiles(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $translationFiles = [];
        foreach ($value as $key => $translationFile) {
            $translationFiles[(int)$key] = (string)$translationFile;
        }
        return $translationFiles;
    }
}
