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

namespace TYPO3\CMS\Frontend\Page;

/**
 * Model for configuration properties in $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'].
 *
 * URL parameter names are prefixed with the following indicators:
 * + = (equals): exact match, default behavior if not given
 * + + ^ (startsWith): matching the beginning of a parameter name
 * + ~ (contains): matching any inline occurrence in a parameter name
 *
 * Example:
 * $configuration = new CacheHashConfiguration([
 *     'excludedParameters' => ['utm_source', '^tx_my_plugin[aspects]', '~[temporary]'],
 *     ...
 * ]);
 */
class CacheHashConfiguration
{
    public const ASPECT_CACHED_PARAMETERS_WHITELIST = 'cachedParametersWhiteList';
    public const ASPECT_EXCLUDED_PARAMETERS = 'excludedParameters';
    public const ASPECT_EXCLUDED_PARAMETERS_IF_EMPTY = 'excludedParametersIfEmpty';
    public const ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS = 'requireCacheHashPresenceParameters';

    protected const PROPERTY_EXCLUDE_ALL_EMPTY_PARAMETERS = 'excludeAllEmptyParameters';
    protected const INDICATOR_STARTS_WITH = '^';
    protected const INDICATOR_CONTAINS = '~';
    protected const INDICATOR_EQUALS = '=';

    protected const ALLOWED_INDICATORS = [
        self::INDICATOR_STARTS_WITH,
        self::INDICATOR_CONTAINS,
        self::INDICATOR_EQUALS,
    ];

    protected const ALLOWED_PROPERTY_NAMES = [
        self::PROPERTY_EXCLUDE_ALL_EMPTY_PARAMETERS,
        self::ASPECT_CACHED_PARAMETERS_WHITELIST,
        self::ASPECT_EXCLUDED_PARAMETERS,
        self::ASPECT_EXCLUDED_PARAMETERS_IF_EMPTY,
        self::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
    ];

    /**
     * @var array
     */
    protected $configuration;

    /**
     * @var array
     */
    protected $data = [];

    public function __construct(array $configuration = null)
    {
        $configuration = $configuration ?? $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] ?? [];
        $this->configuration = array_filter($configuration, [$this, 'isAllowedProperty'], ARRAY_FILTER_USE_KEY);
        $this->processConfiguration();
    }

    /**
     * Merges other configuration property names with current configuration (extends current configuration).
     *
     * Example:
     * $configuration = (new CacheHashConfiguration(['cachedParametersWhiteList' => [...]])
     *                      ->with(new CacheHashConfiguration(['excludedParameters' => [...]]));
     * results in an instance having both aspects 'cachedParametersWhiteList' and 'excludedParameters' defined.
     *
     * @param CacheHashConfiguration $other
     * @return static
     */
    public function with(CacheHashConfiguration $other): self
    {
        $target = clone $this;
        $target->configuration = array_merge($this->configuration, $other->configuration);
        $target->processConfiguration();
        return $target;
    }

    public function shallExcludeAllEmptyParameters(): bool
    {
        return !empty($this->configuration[self::PROPERTY_EXCLUDE_ALL_EMPTY_PARAMETERS]);
    }

    public function applies(string $aspect, string $value): bool
    {
        return $this->equals($aspect, $value)
            || $this->contains($aspect, $value)
            || $this->startsWith($aspect, $value);
    }

    public function equals(string $aspect, string $value): bool
    {
        $data = $this->getData($aspect, self::INDICATOR_EQUALS);
        return !empty($data) && in_array($value, $data, true);
    }

    public function startsWith(string $aspect, string $value): bool
    {
        $data = $this->getData($aspect, self::INDICATOR_STARTS_WITH);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $item) {
            if (strpos($value, $item) === 0) {
                return true;
            }
        }
        return false;
    }

    public function contains(string $aspect, string $value): bool
    {
        $data = $this->getData($aspect, self::INDICATOR_CONTAINS);
        if (empty($data)) {
            return false;
        }
        foreach ($data as $item) {
            if (str_contains($value, $item)) {
                return true;
            }
        }
        return false;
    }

    public function hasData(string $aspect): bool
    {
        return !empty($this->data[$aspect]);
    }

    protected function getData(string $aspect, string $indicator): ?array
    {
        return $this->data[$aspect][$indicator] ?? null;
    }

    protected function defineData(string $aspect): void
    {
        if (empty($this->configuration[$aspect])) {
            return;
        }
        if (!is_array($this->configuration[$aspect])) {
            throw new \LogicException(
                sprintf('Expected array value, got %s', gettype($this->configuration[$aspect])),
                1580225311
            );
        }
        $data = [];
        foreach ($this->configuration[$aspect] as $value) {
            if (!is_scalar($value)) {
                throw new \LogicException(
                    sprintf('Expected scalar value, got %s', gettype($value)),
                    1580225312
                );
            }
            if ($value === '') {
                continue;
            }
            $indicator = $value[0] ?? null;
            // normalize value to be indicated
            if (!in_array($indicator, self::ALLOWED_INDICATORS, true)) {
                $indicator = self::INDICATOR_EQUALS;
                $value = self::INDICATOR_EQUALS . $value;
            }
            if (strlen((string)$value) === 1) {
                throw new \LogicException(
                    sprintf('Empty value after %s indicator', $indicator),
                    1580225313
                );
            }
            $data[$indicator][] = substr((string)$value, 1);
        }
        if (!empty($data)) {
            $this->data[$aspect] = $data;
        }
    }

    protected function processConfiguration(): void
    {
        $this->data = [];
        $this->defineData(self::ASPECT_CACHED_PARAMETERS_WHITELIST);
        $this->defineData(self::ASPECT_EXCLUDED_PARAMETERS);
        $this->defineData(self::ASPECT_EXCLUDED_PARAMETERS_IF_EMPTY);
        $this->defineData(self::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS);
    }

    protected function isAllowedProperty(string $propertyName): bool
    {
        return in_array($propertyName, self::ALLOWED_PROPERTY_NAMES, true);
    }
}
