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

use TYPO3\CMS\Core\Serializer\Exception\PolymorphicDeserializerException;

/**
 * @internal Only to be used by TYPO3 core
 */
final readonly class PolymorphicDeserializer
{
    /**
     * Validates the serialized payload by checking a static list of base classes or interfaces to be included in the
     * de-serialized output. If a non-allowed class is hit, the method throws an PolymorphicDeserializerException.
     * If the serialized payload is syntactically incorrect, PolymorphicDeserializerException is thrown as well.
     *
     * @param list<class-string> $allowedClasses
     * @throws PolymorphicDeserializerException
     */
    public function deserialize(string $payload, array $allowedClasses): mixed
    {
        // When allowing inheritance, extract all class names from payload and validate them
        $classNames = $this->parseClassNames($payload);

        foreach ($classNames as $className) {
            if (!$this->isInstanceOf($className, $allowedClasses)) {
                throw new PolymorphicDeserializerException('Invalid class name "' . $className . '" found in payload', 1767987405);
            }

            // Add the class if it's a valid subclass of any allowed class
            if (!in_array($className, $allowedClasses, true)) {
                $allowedClasses[] = $className;
            }
        }

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
            throw new PolymorphicDeserializerException($exceptionMessage, 1768212616);
        }

        return $result;
    }

    public function parseClassNames(string $payload): array
    {
        $classNames = [];
        if (preg_match_all('/[CO]:(?P<length>\d+):"(?P<className>[^"]+)"/', $payload, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches['className'] as $i => $classNameMatch) {
                $className = $classNameMatch[0];
                // Offset of the full O:... pattern
                $matchOffset = (int)$matches[0][$i][1];
                $declaredLength = (int)$matches['length'][$i][0];

                // Validate: 1) length matches, 2) not inside a string value
                if (strlen($className) === $declaredLength && !$this->isInsideString($payload, $matchOffset)) {
                    $classNames[] = $className;
                }
            }
        }
        return $classNames;
    }

    private function isInsideString(string $payload, int $offset): bool
    {
        if (preg_match_all('/s:(\d+):"/', $payload, $stringMatches, PREG_OFFSET_CAPTURE)) {
            foreach ($stringMatches[0] as $i => $match) {
                $stringDefOffset = $match[1];
                $stringLength = (int)$stringMatches[1][$i][0];
                // String content starts after s:LENGTH:"
                $contentStart = $stringDefOffset + strlen($match[0]);
                $contentEnd = $contentStart + $stringLength;

                if ($offset >= $contentStart && $offset < $contentEnd) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param list<class-string> $allowedClassNames
     */
    private function isInstanceOf(string $className, array $allowedClassNames): bool
    {
        foreach ($allowedClassNames as $allowedClassName) {
            if (is_a($className, $allowedClassName, true) || is_subclass_of($className, $allowedClassName)) {
                return true;
            }
        }
        return false;
    }
}
