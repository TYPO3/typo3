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

namespace TYPO3\CMS\Core\Routing\Enhancer;

use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Routing\RouteCollection;

/**
 * Resolves a static list (like page.typeNum) against a file pattern. Usually added on the very last part
 * of the URL.
 * It is important that the PageType Enhancer is executed at the very end in your configuration, as it modifies
 * EXISTING route variants.
 *
 * routeEnhancers:
 *   PageTypeSuffix:
 *     type: PageType
 *     default: ''
 *     index: 'index'
 *     map:
 *       '.html': 1
 *       'menu.json': 13
 */
class PageTypeDecorator extends AbstractEnhancer implements DecoratingEnhancerInterface
{
    protected const ROUTE_PATH_DELIMITERS = ['.', '-', '_', '/'];

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $default;

    /**
     * @var string
     */
    protected $index;

    /**
     * @var array
     */
    protected $map;

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $default = $configuration['default'] ?? '';
        $index = $configuration['index'] ?? 'index';
        $map = $configuration['map'] ?? null;

        if (!is_string($default)) {
            throw new \InvalidArgumentException('default must be string', 1538327508);
        }
        if (!is_string($index)) {
            throw new \InvalidArgumentException('index must be string', 1538327509);
        }
        if (!is_array($map)) {
            throw new \InvalidArgumentException('map must be array', 1538327510);
        }

        $this->configuration = $configuration;
        $this->default = $default;
        $this->index = $index;
        $this->map = array_map('strval', $map);
    }

    /**
     * @return string
     */
    public function getRoutePathRedecorationPattern(): string
    {
        return $this->buildRegularExpressionPattern(false);
    }

    /**
     * {@inheritdoc}
     */
    public function decorateForMatching(RouteCollection $collection, string $routePath): void
    {
        $decoratedRoutePath = null;
        $decoratedParameters = null;

        $pattern = $this->buildRegularExpressionPattern();
        if (preg_match('#(?P<decoration>(?:' . $pattern . '))#', $routePath, $matches, PREG_UNMATCHED_AS_NULL)) {
            if (!isset($matches['decoration'])) {
                throw new \UnexpectedValueException(
                    'Unexpected null value at end of URL',
                    1538335671
                );
            }

            $routePathValue = $matches['decoration'];
            $parameterValue = $matches['indexItems'] ?? $matches['slashedItems'] ?? $matches['regularItems'];
            $routePathValuePattern = $this->quoteForRegularExpressionPattern($routePathValue) . '$';
            $decoratedRoutePath = preg_replace('#' . $routePathValuePattern . '#', '', $routePath);

            $mappedType = $this->map[$parameterValue] ?? null;
            if ($mappedType !== null) {
                $decoratedParameters = ['type' => $mappedType];
            } elseif ($this->default === $routePathValue) {
                $decoratedParameters = ['type' => 0];
            }
        }

        foreach ($collection->all() as $route) {
            if ($decoratedRoutePath !== null) {
                $route->setOption(
                    '_decoratedRoutePath',
                    '/' . trim($decoratedRoutePath, '/')
                );
            }
            if ($decoratedParameters !== null) {
                $route->setOption(
                    '_decoratedParameters',
                    $decoratedParameters
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function decorateForGeneration(RouteCollection $collection, array $parameters): void
    {
        $type = isset($parameters['type']) ? (string)$parameters['type'] : null;
        $value = $this->resolveValue($type);
        // If the type is > 0 but the value could not be resolved,
        // the type is appended as GET argument, which can be resolved already anyway.
        // This happens when the PageTypeDecorator is used, but hasn't been configured for all available types.
        if (!empty($type) && ($value === '' || $value === $this->default)) {
            return;
        }

        $considerIndex = $value !== '' && in_array($value[0], static::ROUTE_PATH_DELIMITERS);
        if ($value !== '' && !in_array($value[0], static::ROUTE_PATH_DELIMITERS)) {
            $value = '/' . $value;
        }

        /**
         * @var string $routeName
         * @var Route $existingRoute
         */
        foreach ($collection->all() as $routeName => $existingRoute) {
            $existingRoutePath = rtrim($existingRoute->getPath(), '/');
            if ($considerIndex && $existingRoutePath === '') {
                $existingRoutePath = $this->index;
            }
            $existingRoute->setPath($existingRoutePath . $value);
            $deflatedParameters = $existingRoute->getOption('deflatedParameters') ?? $parameters;
            if (isset($deflatedParameters['type'])) {
                unset($deflatedParameters['type']);
                $existingRoute->setOption(
                    'deflatedParameters',
                    $deflatedParameters
                );
            }
        }
    }

    /**
     * Checks if the value exists inside the map.
     *
     * @param string|null $type
     * @return string
     */
    protected function resolveValue(?string $type): string
    {
        $index = array_search($type, $this->map, true);
        if ($index !== false) {
            return $index;
        }
        return $this->default;
    }

    /**
     * Builds a regexp out of the map.
     *
     * @param bool $useNames
     * @return string
     */
    protected function buildRegularExpressionPattern(bool $useNames = true): string
    {
        $items = array_keys($this->map);
        if ($this->default !== '' && !in_array($this->default, $items, true)) {
            $items[] = $this->default;
        }
        $slashedItems = array_filter($items, [$this, 'needsSlashPrefix']);
        $regularItems = array_diff($items, $slashedItems);

        $slashedItems = array_map([$this, 'quoteForRegularExpressionPattern'], $slashedItems);
        $regularItems = array_map([$this, 'quoteForRegularExpressionPattern'], $regularItems);

        $patterns = [];
        if (!empty($slashedItems)) {
            $name = $useNames ? '?P<slashedItems>' : '';
            $patterns[] = '(?:^|/)(' . $name . implode('|', $slashedItems) . ')';
        }
        if (!empty($regularItems) && !empty($this->index)) {
            $name = $useNames ? '?P<indexItems>' : '';
            $indexPattern = $this->quoteForRegularExpressionPattern($this->index);
            $patterns[] = '^' . $indexPattern . '(' . $name . '(?:' . implode('|', $regularItems) . '))';
        }
        if (!empty($regularItems)) {
            $name = $useNames ? '?P<regularItems>' : '';
            $patterns[] = '(' . $name . implode('|', $regularItems) . ')';
        }
        return '(?:' . implode('|', $patterns) . ')$';
    }

    /**
     * Helper method for regexps.
     *
     * @param string $value
     * @return string
     */
    protected function quoteForRegularExpressionPattern(string $value): string
    {
        return preg_quote($value, '#');
    }

    /**
     * Checks if a slash should be prefixed.
     *
     * @param string $value
     * @return bool
     */
    protected function needsSlashPrefix(string $value): bool
    {
        return !in_array(
            $value[0] ?? '',
            static::ROUTE_PATH_DELIMITERS,
            true
        );
    }
}
