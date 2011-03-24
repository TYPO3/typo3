<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * An abstract caching backend
 *
 * This file is a backport from FLOW3
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib_cache
 * @api
 */
abstract class t3lib_cache_backend_AbstractBackend implements t3lib_cache_backend_Backend {

	const DATETIME_EXPIRYTIME_UNLIMITED = '9999-12-31T23:59:59+0000';
	const UNLIMITED_LIFETIME = 0;

	/**
	 * Reference to the cache which uses this backend
	 *
	 * @var t3lib_cache_frontend_Frontend
	 */
	protected $cache;

	/**
	 * @var string
	 */
	protected $cacheIdentifier;

	/**
	 * Default lifetime of a cache entry in seconds
	 *
	 * @var integer
	 */
	protected $defaultLifetime = 3600;

	/**
	 * Constructs this backend
	 *
	 * @param array $options Configuration options - depends on the actual backend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(array $options = array()) {
		if (is_array($options) || $options instanceof ArrayAccess) {
			foreach ($options as $optionKey => $optionValue) {
				$methodName = 'set' . ucfirst($optionKey);
				if (method_exists($this, $methodName)) {
					$this->$methodName($optionValue);
				} else {
					throw new InvalidArgumentException('Invalid cache backend option "' . $optionKey . '" for backend of type "' . get_class($this) . '"', 1235837747);
				}
			}
		}
	}

	/**
	 * Sets a reference to the cache frontend which uses this backend
	 *
	 * @param t3lib_cache_frontend_Frontend The frontend for this backend
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCache(t3lib_cache_frontend_Frontend $cache) {
		$this->cache = $cache;
		$this->cacheIdentifier = $this->cache->getIdentifier();
	}

	/**
	 * Sets the default lifetime for this cache backend
	 *
	 * @param integer $defaultLifetime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setDefaultLifetime($defaultLifetime) {
		if (!is_int($defaultLifetime) || $defaultLifetime < 0) {
			throw new InvalidArgumentException(
				'The default lifetime must be given as a positive integer.',
				1233072774
			);
		}

		$this->defaultLifetime = $defaultLifetime;
	}

	/**
	 * Calculates the expiry time by the given lifetime. If no lifetime is
	 * specified, the default lifetime is used.
	 *
	 * @param integer $lifetime The lifetime in seconds
	 * @return \DateTime The expiry time
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
	 */
	protected function calculateExpiryTime($lifetime = NULL) {
		if ($lifetime === self::UNLIMITED_LIFETIME || ($lifetime === NULL && $this->defaultLifetime === self::UNLIMITED_LIFETIME)) {
			$expiryTime = new DateTime(self::DATETIME_EXPIRYTIME_UNLIMITED, new DateTimeZone('UTC'));
		} else {
			if ($lifetime === NULL) {
				$lifetime = $this->defaultLifetime;
			}
			$expiryTime = new DateTime('now +' . $lifetime . ' seconds', new DateTimeZone('UTC'));
		}

		return $expiryTime;
	}


}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_abstractbackend.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_abstractbackend.php']);
}

?>