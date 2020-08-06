<?php

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Logic for cHash calculation
 */
class CacheHashCalculator implements SingletonInterface
{
    /**
     * @var CacheHashConfiguration
     */
    protected $configuration;

    /**
     * Initialise class properties by using the relevant TYPO3 configuration
     *
     * @param CacheHashConfiguration|null $configuration
     */
    public function __construct(CacheHashConfiguration $configuration = null)
    {
        $this->configuration = $configuration ?? GeneralUtility::makeInstance(CacheHashConfiguration::class);
    }

    /**
     * Calculates the cHash based on the provided parameters
     *
     * @param array $params Array of cHash key-value pairs
     * @return string Hash of all the values
     */
    public function calculateCacheHash(array $params)
    {
        return !empty($params) ? md5(serialize($params)) : '';
    }

    /**
     * Returns the cHash based on provided query parameters and added values from internal call
     *
     * @param string $queryString Query-parameters: "&xxx=yyy&zzz=uuu
     * @return string Hash of all the values
     * @throws \RuntimeException
     */
    public function generateForParameters($queryString)
    {
        $cacheHashParams = $this->getRelevantParameters($queryString);
        return $this->calculateCacheHash($cacheHashParams);
    }

    /**
     * Checks whether a parameter of the given $queryString requires cHash calculation
     *
     * @param string $queryString
     * @return bool
     */
    public function doParametersRequireCacheHash($queryString)
    {
        if (!$this->configuration->hasData(CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS)) {
            return false;
        }
        $parameterNames = array_keys($this->splitQueryStringToArray($queryString));
        foreach ($parameterNames as $parameterName) {
            $hasRequiredParameter = $this->configuration->applies(
                CacheHashConfiguration::ASPECT_REQUIRED_CACHE_HASH_PRESENCE_PARAMETERS,
                $parameterName
            );
            if ($hasRequiredParameter) {
                return  true;
            }
        }
        return false;
    }

    /**
     * Splits the input query-parameters into an array with certain parameters filtered out.
     * Used to create the cHash value
     *
     * @param string $queryString Query-parameters: "&xxx=yyy&zzz=uuu
     * @return array Array with key/value pairs of query-parameters WITHOUT a certain list of
     * @throws \RuntimeException
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typoLink()
     */
    public function getRelevantParameters($queryString)
    {
        $parameters = $this->splitQueryStringToArray($queryString);
        $relevantParameters = [];
        foreach ($parameters as $parameterName => $parameterValue) {
            if ($this->isAdminPanelParameter($parameterName) || $this->isExcludedParameter($parameterName) || $this->isCoreParameter($parameterName)) {
                continue;
            }
            if ($this->hasCachedParametersWhiteList() && !$this->isInCachedParametersWhiteList($parameterName)) {
                continue;
            }
            if (($parameterValue === null || $parameterValue === '') && $this->isAllowedWithEmptyValue($parameterName)) {
                continue;
            }
            $relevantParameters[$parameterName] = $parameterValue;
        }
        if (!empty($relevantParameters)) {
            if (empty($parameters['id'])) {
                throw new \RuntimeException('ID parameter needs to be passed for the cHash calculation!', 1467983513);
            }
            $relevantParameters['id'] = $parameters['id'];
            // Finish and sort parameters array by keys:
            $relevantParameters['encryptionKey'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
            ksort($relevantParameters);
        }
        return $relevantParameters;
    }

    /**
     * Parses the query string and converts it to an array.
     * Unlike parse_str it only creates an array with one level.
     *
     * e.g. foo[bar]=baz will be array('foo[bar]' => 'baz')
     *
     * @param string $queryString
     * @return array
     */
    protected function splitQueryStringToArray($queryString)
    {
        $parameters = array_filter(explode('&', ltrim($queryString, '?')));
        $parameterArray = [];
        foreach ($parameters as $parameter) {
            // should not remove empty values with trimExplode, otherwise cases like &=value, value is used as parameterName.
            $parts = GeneralUtility::trimExplode('=', $parameter, false);
            $parameterName = $parts[0];
            $parameterValue = $parts[1] ?? '';
            if (trim($parameterName) === '') {
                // This parameter cannot appear in $_GET in PHP even if its value is not empty, so it should be ignored!
                continue;
            }
            $parameterArray[rawurldecode($parameterName)] = rawurldecode($parameterValue);
        }
        return $parameterArray;
    }

    /**
     * Checks whether the given parameter is out of a known data-set starting
     * with ADMCMD.
     *
     * @param string $key
     * @return bool
     */
    protected function isAdminPanelParameter($key)
    {
        return $key === 'ADMCMD_simUser' || $key === 'ADMCMD_simTime' || $key === 'ADMCMD_prev';
    }

    /**
     * Checks whether the given parameter is a core parameter
     *
     * @param string $key
     * @return bool
     */
    protected function isCoreParameter($key)
    {
        return $key === 'id' || $key === 'type' || $key === 'no_cache' || $key === 'cHash' || $key === 'MP';
    }

    /**
     * Checks whether the given parameter should be excluded from cHash calculation
     *
     * @param string $key
     * @return bool
     */
    protected function isExcludedParameter($key)
    {
        return $this->configuration->applies(
            CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS,
            $key
        );
    }

    /**
     * Checks whether the given parameter is an exclusive parameter for cHash calculation
     *
     * @param string $key
     * @return bool
     */
    protected function isInCachedParametersWhiteList($key)
    {
        return $this->configuration->applies(
            CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST,
            $key
        );
    }

    /**
     * Checks whether cachedParametersWhiteList parameters are configured
     *
     * @return bool
     */
    protected function hasCachedParametersWhiteList()
    {
        return $this->configuration->hasData(
            CacheHashConfiguration::ASPECT_CACHED_PARAMETERS_WHITELIST
        );
    }

    /**
     * Check whether the given parameter may be used even with an empty value
     *
     * @param string $key
     * @return bool
     */
    protected function isAllowedWithEmptyValue($key)
    {
        return $this->configuration->shallExcludeAllEmptyParameters()
            || $this->configuration->applies(
                CacheHashConfiguration::ASPECT_EXCLUDED_PARAMETERS_IF_EMPTY,
                $key
            );
    }

    /**
     * Extends (or overrides) property names of current configuration.
     *
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $newConfiguration = GeneralUtility::makeInstance(
            CacheHashConfiguration::class,
            $configuration ?? $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] ?? []
        );
        $this->configuration = $this->configuration->with($newConfiguration);
    }
}
