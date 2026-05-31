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
 * @internal Only to be used by TYPO3 core
 */
final readonly class PolymorphicDeserializer
{
    public function __construct(
        private DeserializationService $deserializationService = new DeserializationService(),
    ) {}

    /**
     * Validates the serialized payload by checking a static list of base classes or interfaces to be included in the
     * de-serialized output. If a non-allowed class is hit, the method throws an PolymorphicDeserializerException.
     * If the serialized payload is syntactically incorrect, PolymorphicDeserializerException is thrown as well.
     *
     * @param list<class-string> $allowedClasses
     * @throws DeserializerException
     */
    public function deserialize(string $payload, array $allowedClasses): mixed
    {
        // When allowing inheritance, extract all class names from payload and validate them
        $classNames = $this->deserializationService->parseClassNames($payload);

        foreach ($classNames as $className) {
            if (!$this->isInstanceOf($className, $allowedClasses)) {
                throw new DeserializerException('Invalid class name "' . $className . '" found in payload', 1767987405);
            }

            // Add the class if it's a valid subclass of any allowed class
            if (!in_array($className, $allowedClasses, true)) {
                $allowedClasses[] = $className;
            }
        }

        return $this->deserializationService->deserialize($payload, $allowedClasses);
    }

    /**
     * @return list<class-string>
     * @deprecated use DeserializationService::parseClassNames instead; will be removed in v15
     */
    public function parseClassNames(string $payload): array
    {
        return $this->deserializationService->parseClassNames($payload);
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
