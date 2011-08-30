<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Daniel Pötzinger (poetzinger@aoemedia.de)
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
 */
class tslib_caching_CacheHash implements t3lib_Singleton {

	/**
	 * @param array $TYPO3_CONF_VARS
	 */
	public function __construct($TYPO3_CONF_VARS = NULL) {
		if (is_null($TYPO3_CONF_VARS)) {
			$this->TYPO3_CONF_VARS = $GLOBALS['TYPO3_CONF_VARS'];
		}
		else {
			$this->TYPO3_CONF_VARS = $TYPO3_CONF_VARS;
		}
	}

	/**
	 * Calculates the cHash based on the provided parameters
	 *
	 * @param array $params Array of cHash key-value pairs
	 * @return string Hash of all the values
	 */
	public function calculateCacheHash(array $params) {
		$cHash = md5(serialize($params));
		return $cHash;
	}

	/**
	 * Returns the cHash based on provided query parameters and added values from internal call
	 *
	 * @param string $addQueryParams Query-parameters: "&xxx=yyy&zzz=uuu"
	 * @return string Hash of all the values
	 * @see t3lib_div::cHashParams(), t3lib_div::calculateCHash()
	 */
	public function generateCacheHashForGetQuery($addQueryParams) {
		$cHashParams = $this->getRelevantHashParameters($addQueryParams);
		$cHash = $this->calculateCacheHash($cHashParams);
		return $cHash;
	}

	/**
	 * Checks whether a parameter of the given $queryString requires cHash calculation
	 * @param string $queryString
	 * @return boolean
	 */
	public function hasParameterRequiringCacheHash($queryString) {
		if (empty($this->TYPO3_CONF_VARS['FE']['cHashRequiringParameters'])) {
			return FALSE;
		}
			// Split parameters up, remove leading ?
		$parameters = explode('&', ltrim($queryString, '?'));
		foreach ($parameters as $parameterString) {
			$parameterKeyAndValue = explode('=', $parameterString); // Splitting single param by '=' sign
			if ($this->isCacheHashRequiringParameter($parameterKeyAndValue[0])) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Splits the input query-parameters into an array with certain parameters filtered out.
	 * Used to create the cHash value
	 *
	 * @param string $addQueryParameters Query-parameters: "&xxx=yyy&zzz=uuu"
	 * @return array Array with key/value pairs of query-parameters WITHOUT a certain list of
	 * 		variable names (like id, type, no_cache etc.) and WITH a variable, encryptionKey, specific
	 * 		for this server/installation
	 * @see tslib_fe::makeCacheHash(), tslib_cObj::typoLink(), t3lib_div::calculateCHash()
	 */
	public function getRelevantHashParameters($addQueryParameters) {
			// Split parameters up, remove leading ?
		$parameters = explode('&', ltrim($addQueryParameters, '?'));
			// Make array:
		$relevantParameters = array();
		foreach ($parameters as $parameterString) {
			 // Split single param by '=' sign
			$parameterKeyAndValue = explode('=', $parameterString);
			if ($this->isAdminPanelParameter($parameterKeyAndValue[0])
				|| $this->isUncachedParameter($parameterKeyAndValue[0])
				|| $this->isCoreParameter($parameterKeyAndValue[0])) {
				continue;
			}
			if ($this->hasCachedParametersWhiteList() && !$this->isInCachedParametersWhiteList($parameterKeyAndValue[0])) {
				continue;
			}
			$relevantParameters[rawurldecode($parameterKeyAndValue[0])] = (string) rawurldecode($parameterKeyAndValue[1]);
		}

			// Finish and sort parameters array by keys:
		$relevantParameters['encryptionKey'] = $this->TYPO3_CONF_VARS['SYS']['encryptionKey'];
		ksort($relevantParameters);

		return $relevantParameters;
	}

	/**
	 * Checks whether the given parameter starts with TSFE_ADMIN_PANEL
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isAdminPanelParameter($key) {
		return preg_match('/TSFE_ADMIN_PANEL\[.*?\]/', $key);
	}

	/**
	 * Checks whether the given parameter is a core parameter
	 * @param string $key
	 * @return boolean
	 */
	protected function isCoreParameter($key) {
		return t3lib_div::inList('id,type,no_cache,cHash,MP,ftu', $key);
	}

	/**
	 * Checks whether the given paramter requires cHash calculation
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isCacheHashRequiringParameter($key) {
		return t3lib_div::inList($this->TYPO3_CONF_VARS['FE']['cHashRequiringParameters'], $key);
	}

	/**
	 * Checks whether the given parameter should be exluded from cHash calculation
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isUncachedParameter($key) {
		return t3lib_div::inList($this->TYPO3_CONF_VARS['FE']['uncachedParameters'], $key);
	}

	/**
	 * Checks whether the given parameter is an exclusive parameter for cHash calculation
	 *
	 * @param string $key
	 * @return boolean
	 */
	protected function isInCachedParametersWhiteList($key) {
		return t3lib_div::inList($this->TYPO3_CONF_VARS['FE']['cachedParametersWhiteList'], $key);
	}

	/**
	 * Checks whether cachedParametersWhiteList parameters are configured
	 *
	 * @return boolean
	 */
	protected function hasCachedParametersWhiteList() {
		return !empty($this->TYPO3_CONF_VARS['FE']['cachedParametersWhiteList']);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_caching_cachehash.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_caching_cachehash.php']);
}

?>