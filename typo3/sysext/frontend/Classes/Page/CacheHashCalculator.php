<?php
namespace TYPO3\CMS\Frontend\Page;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Daniel Pötzinger (poetzinger@aoemedia.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Logic for cHash calculation
 *
 * @author Daniel Pötzinger <poetzinger@aoemedia.de>
 * @coauthor Tolleiv Nietsch <typo3@tolleiv.de>
 */
class CacheHashCalculator implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array Parameters that are relevant for cacheHash calculation. Optional.
	 */
	protected $cachedParametersWhiteList = array();

	/**
	 * @var array Parameters that are not relevant for cacheHash calculation.
	 */
	protected $excludedParameters = array();

	/**
	 * @var array Parameters that forces a presence of a valid cacheHash.
	 */
	protected $requireCacheHashPresenceParameters = array();

	/**
	 * @var array Parameters that need a value to be relevant for cacheHash calculation
	 */
	protected $excludedParametersIfEmpty = array();

	/**
	 * @var bool Whether to exclude all empty parameters for cacheHash calculation
	 */
	protected $excludeAllEmptyParameters = FALSE;

	/**
	 * Initialise class properties by using the relevant TYPO3 configuration
	 */
	public function __construct() {
		$this->setConfiguration($GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']);
	}

	/**
	 * Calculates the cHash based on the provided parameters
	 *
	 * @param array $params Array of cHash key-value pairs
	 * @return string Hash of all the values
	 */
	public function calculateCacheHash(array $params) {
		return !empty($params) ? md5(serialize($params)) : '';
	}

	/**
	 * Returns the cHash based on provided query parameters and added values from internal call
	 *
	 * @param string $queryString Query-parameters: "&xxx=yyy&zzz=uuu
	 * @return string Hash of all the values
	 */
	public function generateForParameters($queryString) {
		$cacheHashParams = $this->getRelevantParameters($queryString);
		return $this->calculateCacheHash($cacheHashParams);
	}

	/**
	 * Checks whether a parameter of the given $queryString requires cHash calculation
	 *
	 * @param string $queryString
	 * @return boolean
	 */
	public function doParametersRequireCacheHash($queryString) {
		if (empty($this->requireCacheHashPresenceParameters)) {
			return FALSE;
		}
		$hasRequiredParameter = FALSE;
		$parameterNames = array_keys($this->splitQueryStringToArray($queryString));
		foreach ($parameterNames as $parameterName) {
			if (in_array($parameterName, $this->requireCacheHashPresenceParameters)) {
				$hasRequiredParameter = TRUE;
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
	 * @see tslib_fe::makeCacheHash(), tslib_cObj::typoLink()
	 */
	public function getRelevantParameters($queryString) {
		$parameters = $this->splitQueryStringToArray($queryString);
		$relevantParameters = array();
		foreach ($parameters as $parameterName => $parameterValue) {
			if ($this->isAdminPanelParameter($parameterName) || $this->isExcludedParameter($parameterName) || $this->isCoreParameter($parameterName)) {
				continue;
			}
			if ($this->hasCachedParametersWhiteList() && !$this->isInCachedParametersWhiteList($parameterName)) {
				continue;
			}
			if ((is_null($parameterValue) || $parameterValue === '') && !$this->isAllowedWithEmptyValue($parameterName)) {
				continue;
			}
			$relevantParameters[$parameterName] = $parameterValue;
		}
		if (!empty($relevantParameters)) {
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
	 * @param $queryString
	 * @return array
	 */
	protected function splitQueryStringToArray($queryString) {
		$parameters = array_filter(explode('&', ltrim($queryString, '?')));
		$parameterArray = array();
		foreach ($parameters as $parameter) {
			list($parameterName, $parameterValue) = explode('=', $parameter);
			$parameterArray[rawurldecode($parameterName)] = rawurldecode($parameterValue);
		}
		return $parameterArray;
	}

	/**
	 * Checks whether the given parameter starts with TSFE_ADMIN_PANEL
	 * stristr check added to avoid bad performance
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isAdminPanelParameter($key) {
		return stristr($key, 'TSFE_ADMIN_PANEL') !== FALSE && preg_match('/TSFE_ADMIN_PANEL\\[.*?\\]/', $key);
	}

	/**
	 * Checks whether the given parameter is a core parameter
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isCoreParameter($key) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::inList('id,type,no_cache,cHash,MP,ftu', $key);
	}

	/**
	 * Checks whether the given parameter should be exluded from cHash calculation
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isExcludedParameter($key) {
		return in_array($key, $this->excludedParameters);
	}

	/**
	 * Checks whether the given parameter is an exclusive parameter for cHash calculation
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isInCachedParametersWhiteList($key) {
		return in_array($key, $this->cachedParametersWhiteList);
	}

	/**
	 * Checks whether cachedParametersWhiteList parameters are configured
	 *
	 * @return boolean
	 */
	protected function hasCachedParametersWhiteList() {
		return !empty($this->cachedParametersWhiteList);
	}

	/**
	 * Check whether the given parameter may be used even with an empty value
	 *
	 * @param $key
	 */
	protected function isAllowedWithEmptyValue($key) {
		return !($this->excludeAllEmptyParameters || in_array($key, $this->excludedParametersIfEmpty));
	}

	/**
	 * Loops through the configuration array and calls the accordant
	 * getters with the value.
	 *
	 * @param $configuration
	 */
	public function setConfiguration($configuration) {
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
	protected function setCachedParametersWhiteList(array $cachedParametersWhiteList) {
		$this->cachedParametersWhiteList = $cachedParametersWhiteList;
	}

	/**
	 * @param boolean $excludeAllEmptyParameters
	 */
	protected function setExcludeAllEmptyParameters($excludeAllEmptyParameters) {
		$this->excludeAllEmptyParameters = $excludeAllEmptyParameters;
	}

	/**
	 * @param array $excludedParameters
	 */
	protected function setExcludedParameters(array $excludedParameters) {
		$this->excludedParameters = $excludedParameters;
	}

	/**
	 * @param array $excludedParametersIfEmpty
	 */
	protected function setExcludedParametersIfEmpty(array $excludedParametersIfEmpty) {
		$this->excludedParametersIfEmpty = $excludedParametersIfEmpty;
	}

	/**
	 * @param array $requireCacheHashPresenceParameters
	 */
	protected function setRequireCacheHashPresenceParameters(array $requireCacheHashPresenceParameters) {
		$this->requireCacheHashPresenceParameters = $requireCacheHashPresenceParameters;
	}

}


?>