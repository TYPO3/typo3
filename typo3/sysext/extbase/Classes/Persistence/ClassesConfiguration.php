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

namespace TYPO3\CMS\Extbase\Persistence;

class ClassesConfiguration
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $className
     * @return bool
     */
    public function hasClass(string $className): bool
    {
        return array_key_exists($className, $this->configuration);
    }

    /**
     * @param string $className
     * @return array|null
     */
    public function getConfigurationFor(string $className): ?array
    {
        return $this->configuration[$className] ?? null;
    }

    /**
     * Resolves all subclasses for the given set of (sub-)classes.
     * The whole classes configuration is used to determine all subclasses recursively.
     *
     * @param string $className
     * @return array A numeric array that contains all available subclasses-strings as values.
     */
    public function getSubClasses(string $className): array
    {
        return $this->resolveSubClassesRecursive($className);
    }

    /**
     * @param string $className
     * @param array $subClasses
     * @return array
     */
    private function resolveSubClassesRecursive(string $className, array $subClasses = []): array
    {
        foreach ($this->configuration[$className]['subclasses'] ?? [] as $subclass) {
            if (in_array($subclass, $subClasses, true)) {
                continue;
            }

            $subClasses[] = $subclass;
            $subClasses = $this->resolveSubClassesRecursive($subclass, $subClasses);
        }

        return $subClasses;
    }
}
