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
     *
     * @param mixed $logData
     * @return array|null
     */
    protected function unserializeLogData($logData): ?array
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
     *
     * @param string $detailString
     * @param mixed $substitutes
     * @return string
     */
    protected function formatLogDetails(string $detailString, $substitutes): string
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
        // Handle legacy "%s" placeholders
        if (str_contains($detailString, '%')) {
            $detailString = vsprintf($detailString, $substitutes);
        } elseif ($substitutes !== []) {
            // Handles placeholders with "{myPlaceholder}"
            $detailString = preg_replace_callback('/{([A-z]+)}/', static function ($matches) use ($substitutes) {
                // $matches[0] contains the unsubstituted placeholder
                return $substitutes[$matches[1]] ?? $matches[0];
            }, $detailString);
        }
        // Remove possible pending other %s
        return str_replace('%s', '', (string)$detailString);
    }
}
