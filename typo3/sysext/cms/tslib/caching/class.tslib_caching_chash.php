<?php
/***************************************************************
 *  Copyright notice
 *
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
 * Contains logic for cHash calculation
 *
 * $Id: $
 * @author Daniel Pötzinger
 */
class tslib_caching_chash implements t3lib_Singleton {
	
	/**
	 * @var array
	 */
	protected $TYPO3_CONF_VARS;
	
	/**
	 * @param array $TYPO3_CONF_VARS
	 */
	public function __construct($TYPO3_CONF_VARS = null) {
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
	 * @param	array		Array of key-value pairs
	 * @return	string		Hash of all the values
	 */
	public function calculateCHash(array $params) {
		$cHash = md5(serialize($params));
		return $cHash;
	}
	
	/**
	 * Returns the cHash based on provided query parameters and added values from internal call
	 *
	 * @param	string		Query-parameters: "&xxx=yyy&zzz=uuu"
	 * @return	string		Hash of all the values
	 * @see t3lib_div::cHashParams(), t3lib_div::calculateCHash()
	 */
	public function generateCHashForGetQuery($addQueryParams) {
		$cHashParams = $this->getRelevantHashParameters($addQueryParams);
		$cHash = $this->calculateCHash($cHashParams);
		return $cHash;
	}
	
	/**
	 * @param string $queryString
	 */
	public function hasParametersThatRequireCHash($queryString) {
		if ($this->TYPO3_CONF_VARS['FE']['cHashRequiringParameters'] == '') {
			return false;
		}
		$params = explode('&', substr($addQueryParams, 1)); // Splitting parameters up
		foreach ($params as $theP) {
			$pKV = explode('=', $theP); // Splitting single param by '=' sign
			if ($this->iscHashRequiringParameter($pKV[0])) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Splits the input query-parameters into an array with certain parameters filtered out.
	 * Used to create the cHash value
	 * 
	 * @param	string		Query-parameters: "&xxx=yyy&zzz=uuu"
	 * @return	array		Array with key/value pairs of query-parameters WITHOUT a certain list of variable names (like id, type, no_cache etc.) and WITH a variable, encryptionKey, specific for this server/installation
	 * @see tslib_fe::makeCacheHash(), tslib_cObj::typoLink(), t3lib_div::calculateCHash()
	 */
	public function getRelevantHashParameters($addQueryParams) {
		$params = explode('&', substr($addQueryParams, 1)); // Splitting parameters up
			// Make array:
		$pA = array();
		foreach ($params as $theP) {
			$pKV = explode('=', $theP); // Splitting single param by '=' sign
			
			if ($this->isAdminPanelParameter($pKV[0]) ||
				$this->isUncachedParameter($pKV[0]) ||
				$this->isCoreParameter($pKV[0]) ) {
				continue;
			}
			if ($this->hasCachedParametersWhiteList() && !$this->isInCachedParametersWhiteList($pKV[0])) {
				continue;
			}
			$pA[rawurldecode($pKV[0])] = (string) rawurldecode($pKV[1]);
			
		}
			// Finish and sort parameters array by keys:
		$pA['encryptionKey'] = $this->TYPO3_CONF_VARS['SYS']['encryptionKey'];
		ksort($pA);

		return $pA;
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	private function isAdminPanelParameter($key) {
		return preg_match('/TSFE_ADMIN_PANEL\[.*?\]/', $key);
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	private function isCoreParameter($key) {
		return t3lib_div::inList('id,type,no_cache,cHash,MP,ftu', $key);
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	private function iscHashRequiringParameter($key) {
		return t3lib_div::inList($this->TYPO3_CONF_VARS['FE']['cHashRequiringParameters'], $key);
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	private function isUncachedParameter($key) {
		return t3lib_div::inList($this->TYPO3_CONF_VARS['FE']['uncachedParameters'], $key);
	}
	
	/**
	 * @param string $key
	 * @return boolean
	 */
	private function isInCachedParametersWhiteList($key) {
		return t3lib_div::inList($this->TYPO3_CONF_VARS['FE']['cachedParametersWhiteList'], $key);
	}
	
	/**
	 * @return boolean
	 */
	private function hasCachedParametersWhiteList() {
		return $this->TYPO3_CONF_VARS['FE']['cachedParametersWhiteList'] != '';
	}

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_caching_chash.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/content/class.tslib_caching_chash.php']);
}

?>