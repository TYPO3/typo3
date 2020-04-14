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

namespace TYPO3\CMS\Core\Routing\Aspect;

use TYPO3\CMS\Core\Routing\Route;

/**
 * Helper class for resolving all aspects that are mappable.
 */
class MappableProcessor
{
    /**
     * @param Route $route
     * @param array $attributes
     * @return bool
     */
    public function resolve(Route $route, array &$attributes): bool
    {
        $mappers = $this->fetchMappers($route, $attributes);
        if (empty($mappers)) {
            return true;
        }

        $values = [];
        foreach ($mappers as $variableName => $mapper) {
            $value = $mapper->resolve(
                (string)($attributes[$variableName] ?? '')
            );
            if ($value === null) {
                return false;
            }
            $values[$variableName] = $value;
        }

        $attributes = array_merge($attributes, $values);
        return true;
    }

    /**
     * @param Route $route
     * @param array $attributes
     * @return bool
     */
    public function generate(Route $route, array &$attributes): bool
    {
        $mappers = $this->fetchMappers($route, $attributes);
        if (empty($mappers)) {
            return true;
        }

        $values = [];
        foreach ($mappers as $variableName => $mapper) {
            $value = $mapper->generate(
                (string)($attributes[$variableName] ?? '')
            );
            if ($value === null) {
                return false;
            }
            $values[$variableName] = $value;
        }

        $attributes = array_merge($attributes, $values);
        return true;
    }

    /**
     * @param Route $route
     * @param array $attributes
     * @param string $type
     * @return MappableAspectInterface[]
     */
    protected function fetchMappers(Route $route, array $attributes, string $type = MappableAspectInterface::class): array
    {
        if (empty($attributes)) {
            return [];
        }
        return $route->filterAspects([$type], array_keys($attributes));
    }
}
