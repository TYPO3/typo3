<?php
namespace TYPO3\CMS\Frontend\Page;

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

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Logic for cHash calculation
 */
class CacheHashCalculator implements SingletonInterface
{
    /**
     * @var array Parameters that are relevant for cacheHash calculation. Optional.
     */
    protected $cachedParametersWhiteList = [];

    /**
     * @var array Parameters that are not relevant for cacheHash calculation.
     */
    protected $excludedParameters = [];

    /**
     * @var array Parameters that forces a presence of a valid cacheHash.
     */
    protected $requireCacheHashPresenceParameters = [];

    /**
     * @var array Parameters that need a value to be relevant for cacheHash calculation
     */
    protected $excludedParametersIfEmpty = [];

    /**
     * @var bool Whether to exclude all empty parameters for cacheHash calculation
     */
    protected $excludeAllEmptyParameters = false;

    /**
     * Initialise class properties by using the relevant TYPO3 configuration
     *
     * @param array $configuration
     */
    public function __construct(array $configuration = null)
    {
        $configuration = $configuration ?? $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] ?? [];
        $this->setConfiguration($configuration);
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
        if (empty($this->requireCacheHashPresenceParameters)) {
            return false;
        }
        $hasRequiredParameter = false;
        $parameterNames = array_keys($this->splitQueryStringToArray($queryString));
        foreach ($parameterNames as $parameterName) {
            if (in_array($parameterName, $this->requireCacheHashPresenceParameters, true)) {
                $hasRequiredParameter = true;
                break;
            }
        }
        return $hasRequiredParameter;
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
            if (($parameterValue === null || $parameterValue === '') && !$this->isAllowedWithEmptyValue($parameterName)) {
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
     * with ADMCMD or starts with TSFE_ADMIN_PANEL.
     *
     * @param string $key
     * @return bool
     */
    protected function isAdminPanelParameter($key)
    {
        return $key === 'ADMCMD_noBeUser' || $key === 'ADMCMD_view' || $key === 'ADMCMD_editIcons'
            || $key === 'ADMCMD_simUser' || $key === 'ADMCMD_simTime' || $key === 'ADMCMD_prev'
            || stripos($key, 'TSFE_ADMIN_PANEL') !== false && preg_match('/TSFE_ADMIN_PANEL\\[.*?\\]/', $key);
    }

    /**
     * Checks whether the given parameter is a core parameter
     *
     * @param string $key
     * @return bool
     */
    protected function isCoreParameter($key)
    {
        return $key === 'id' || $key === 'type' || $key === 'no_cache' || $key === 'cHash' || $key === 'MP' || $key === 'ftu';
    }

    /**
     * Checks whether the given parameter should be excluded from cHash calculation
     *
     * @param string $key
     * @return bool
     */
    protected function isExcludedParameter($key)
    {
        return in_array($key, $this->excludedParameters, true);
    }

    /**
     * Checks whether the given parameter is an exclusive parameter for cHash calculation
     *
     * @param string $key
     * @return bool
     */
    protected function isInCachedParametersWhiteList($key)
    {
        return in_array($key, $this->cachedParametersWhiteList, true);
    }

    /**
     * Checks whether cachedParametersWhiteList parameters are configured
     *
     * @return bool
     */
    protected function hasCachedParametersWhiteList()
    {
        return !empty($this->cachedParametersWhiteList);
    }

    /**
     * Check whether the given parameter may be used even with an empty value
     *
     * @param $key
     * @return bool
     */
    protected function isAllowedWithEmptyValue($key)
    {
        return !($this->excludeAllEmptyParameters || in_array($key, $this->excludedParametersIfEmpty, true));
    }

    /**
     * Loops through the configuration array and calls the accordant
     * getters with the value.
     *
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        foreach ($configuration as $name => $value) {
            $setterName = 'set' . ucfirst($name);
            if (method_exists($this, $setterName)) {
                $this->{$setterName}($value);
            }
        }
    }

    /**
     * @param array $cachedParametersWhiteList
     */
    protected function setCachedParametersWhiteList(array $cachedParametersWhiteList)
    {
        $this->cachedParametersWhiteList = $cachedParametersWhiteList;
    }

    /**
     * @param bool $excludeAllEmptyParameters
     */
    protected function setExcludeAllEmptyParameters($excludeAllEmptyParameters)
    {
        $this->excludeAllEmptyParameters = $excludeAllEmptyParameters;
    }

    /**
     * @param array $excludedParameters
     */
    protected function setExcludedParameters(array $excludedParameters)
    {
        $this->excludedParameters = $excludedParameters;
    }

    /**
     * @param array $excludedParametersIfEmpty
     */
    protected function setExcludedParametersIfEmpty(array $excludedParametersIfEmpty)
    {
        $this->excludedParametersIfEmpty = $excludedParametersIfEmpty;
    }

    /**
     * @param array $requireCacheHashPresenceParameters
     */
    protected function setRequireCacheHashPresenceParameters(array $requireCacheHashPresenceParameters)
    {
        $this->requireCacheHashPresenceParameters = $requireCacheHashPresenceParameters;
    }
}
