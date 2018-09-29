<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Enhancer;

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
 *     map:
 *       '.html': 1
 *       'menu.json': 13
 */
class PageTypeDecorator extends AbstractEnhancer implements DecoratingEnhancerInterface
{
    protected const PREFIXES = ['.', '-', '_'];

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var string
     */
    protected $default;

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
        $map = $configuration['map'] ?? null;

        if (!is_string($default)) {
            throw new \InvalidArgumentException('default must be string', 1538327508);
        }
        if (!is_array($map)) {
            throw new \InvalidArgumentException('map must be array', 1538327509);
        }

        $this->configuration = $configuration;
        $this->default = $default;
        $this->map = array_map('strval', $map);
    }

    /**
     * {@inheritdoc}
     */
    public function decorateForMatching(RouteCollection $collection, array &$parameters, string &$routePath): void
    {
        $pattern = $this->buildRegularExpressionPattern();
        if (!preg_match('#' . $pattern . '#', $routePath, $matches, PREG_UNMATCHED_AS_NULL)) {
            $parameters['type'] = 0;
            return;
        }

        $value = $matches['slashedItems'] ?? $matches['regularItems'] ?? null;
        if (!is_string($value)) {
            throw new \UnexpectedValueException(
                'Unexpected null value at end of URL',
                1538335671
            );
        }

        $parameters['type'] = $this->map[$value] ?? 0;
        $valuePattern = $this->quoteForRegularExpressionPattern($value) . '$';
        $routePath = preg_replace('#' . $valuePattern . '#', '', $routePath);
    }

    /**
     * {@inheritdoc}
     */
    public function decorateForGeneration(RouteCollection $collection, array &$parameters): void
    {
        $type = isset($parameters['type']) ? (string)$parameters['type'] : null;
        $value = $this->resolveValue($type);
        unset($parameters['type']);

        if ($value !== '' && !in_array($value{0}, static::PREFIXES)) {
            $value = '/' . $value;
        }

        /**
         * @var string $routeName
         * @var Route $existingRoute
         */
        foreach ($collection->all() as $routeName => $existingRoute) {
            $existingRoute->setPath(rtrim($existingRoute->getPath(), '/') . $value);
            $deflatedParameters = $existingRoute->getOption('deflatedParameters');
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
     * @return string
     */
    protected function buildRegularExpressionPattern(): string
    {
        $items = array_keys($this->map);
        $slashedItems = array_filter($items, [$this, 'needsSlashPrefix']);
        $regularItems = array_diff($items, $slashedItems);

        $slashedItems = array_map([$this, 'quoteForRegularExpressionPattern'], $slashedItems);
        $regularItems = array_map([$this, 'quoteForRegularExpressionPattern'], $regularItems);

        $patterns = [];
        if (!empty($slashedItems)) {
            $patterns[] = '/(?P<slashedItems>' . implode('|', $slashedItems) . ')';
        }
        if (!empty($regularItems)) {
            $patterns[] = '(?P<regularItems>' . implode('|', $regularItems) . ')';
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
            $value{0} ?? '',
            static::PREFIXES,
            true
        );
    }
}
