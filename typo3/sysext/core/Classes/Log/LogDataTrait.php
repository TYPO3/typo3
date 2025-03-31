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

namespace TYPO3\CMS\Core\Log;

/**
 * Helper for handling both serialize()/unserialize() and json_encode()/json_decode()
 * when migrating to json-encoded strings.
 */
trait LogDataTrait
{
    /**
     * Useful for handling old serialized data, which might have been migrated to JSON encoded
     * properties already.
     */
    protected function unserializeLogData(mixed $logData): ?array
    {
        // The @ symbol avoids an E_NOTICE when unserialize() fails
        $cleanedUpData = @unserialize((string)$logData, ['allowed_classes' => false]);
        if ($cleanedUpData === false) {
            $cleanedUpData = json_decode((string)$logData, true);
        }
        return is_array($cleanedUpData) ? $cleanedUpData : null;
    }

    /**
     * Replaces a string with placeholders (%s or {myPlaceholder}) with its substitutes.
     */
    protected function formatLogDetails(string $detailString, mixed $substitutes): string
    {
        if (!is_array($substitutes)) {
            $substitutes = $this->unserializeLogData($substitutes) ?? [];
        }
        return self::formatLogDetailsStatic($detailString, $substitutes);
    }

    /**
     * Static version for ViewHelpers etc.
     *
     * Replaces a string with placeholders (%s or {myPlaceholder}) with its substitutes.
     */
    protected static function formatLogDetailsStatic(string $detailString, array $substitutes): string
    {
        // Handles placeholders with "%" first
        try {
            $detailString = vsprintf($detailString, $substitutes);
        } catch (\ValueError|\ArgumentCountError) {
            // Ignore if $substitutes doesn't contain the number of "%" found in $detailString
        }

        // Handles placeholders with "{myPlaceholder}"
        $detailString = preg_replace_callback('/{([A-z]+)}/', static function (array $matches) use ($substitutes) {
            // $matches[0] contains the unsubstituted placeholder
            /** @var array{0: non-falsy-string, 1?: non-falsy-string} $matches added to mitigate false-positives PHPStan reportings */
            return $substitutes[$matches[1] ?? null] ?? $matches[0];
        }, $detailString);

        // Remove possible pending %s
        return str_replace('%s', '', (string)$detailString);
    }
}
