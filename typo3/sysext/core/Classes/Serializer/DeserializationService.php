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

namespace TYPO3\CMS\Core\Serializer;

use TYPO3\CMS\Core\Serializer\Exception\DeserializerException;

/**
 * Low-level utilities for PHP serialization format inspection.
 *
 * @internal Only to be used by TYPO3 core
 */
final readonly class DeserializationService
{
    /**
     * Extracts all class names from a PHP-serialized payload, ignoring any
     * class-name tokens that appear inside serialized string values.
     *
     * Returns an empty array for payloads that contain no objects, and skips
     * any token whose declared byte-length does not match the actual class-name
     * length (malformed entries).
     *
     * @return list<class-string>
     */
    public function parseClassNames(string $payload): array
    {
        // Build string ranges once upfront to avoid re-scanning the payload per class-name token
        $stringRanges = [];
        if (preg_match_all('/s:(\d+):"/', $payload, $stringMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($stringMatches[0] as $i => $match) {
                $contentStart = $match[1] + strlen($match[0]);
                $stringRanges[] = [$contentStart, $contentStart + (int)$stringMatches[1][$i][0]];
            }
        }

        $classNames = [];
        if (preg_match_all('/[CO]:(?P<length>\d+):"(?P<className>[^"]+)"/', $payload, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches['className'] as $i => $classNameMatch) {
                $className = $classNameMatch[0];
                $matchOffset = (int)$matches[0][$i][1];
                $declaredLength = (int)$matches['length'][$i][0];

                if (strlen($className) !== $declaredLength) {
                    continue;
                }
                if (in_array($className, $classNames, true)) {
                    continue;
                }
                $insideString = false;
                foreach ($stringRanges as [$start, $end]) {
                    if ($matchOffset >= $start && $matchOffset < $end) {
                        $insideString = true;
                        break;
                    }
                }
                if (!$insideString) {
                    $classNames[] = $className;
                }
            }
        }
        return $classNames;
    }

    /**
     * @param string $payload
     * @param bool|list<class-string> $allowedClasses
     */
    public function deserialize(string $payload, bool|array $allowedClasses = false): mixed
    {
        $result = @unserialize($payload, ['allowed_classes' => $allowedClasses]);
        if ($result === false) {
            if ($payload === serialize(false)) {
                // Do not throw an exception in case the serialized string is *actually* false
                // See https://www.php.net/manual/en/function.unserialize.php#refsect1-function.unserialize-notes
                return false;
            }
            $exceptionMessage = 'Syntax error in payload, unable to de-serialize';
            $lastError = error_get_last();
            if ($lastError !== null) {
                $exceptionMessage .= ': ' . $lastError['message'];
            }
            throw new DeserializerException($exceptionMessage, 1768212616);
        }
        return $result;
    }
}
