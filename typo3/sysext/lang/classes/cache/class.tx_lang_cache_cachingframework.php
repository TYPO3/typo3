<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Dominique Feyer <dfeyer@reelpeek.net>
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
 * Provides a cache based on the caching framework.
 *
 * @package	TYPO3
 * @subpackage	tx_lang
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
class tx_lang_cache_CachingFramework extends tx_lang_cache_Abstract {

	/**
	 * @var t3lib_cache_frontend_StringFrontend
	 */
	protected $cacheInstance;

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct();
		$this->initializeCache();
	}

	/**
	 * Initialize cache instance to be ready to use
	 *
	 * @return void
	 */
	protected function initializeCache() {
			t3lib_cache::initializeCachingFramework();
			try {
					$this->cacheInstance = $GLOBALS['typo3CacheManager']->getCache('lang_l10n_cache');
			}
			catch (t3lib_cache_exception_NoSuchCache $e) {
					$this->cacheInstance = $GLOBALS['typo3CacheFactory']->create(
							'lang_l10n_cache',
							$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['frontend'],
							$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['backend'],
							$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['options']
					);
			}
	}

	/**
	 * Gets a cached value.
	 *
	 * @param  string $hash Cache hash
	 * @return bool|mixed
	 */
	public function get($hash) {
		$cacheData = $this->cacheInstance->get($hash);

		if ($cacheData) {
			$data = call_user_func($this->getUnserialize(), $cacheData);
		} else {
			return FALSE;
		}
		return $data;
	}

	/**
	 * Adds a value to the cache.
	 *
	 * @throws RuntimeException
	 * @param string $hash Cache hash
	 * @param mixed $data
	 * @return tx_lang_cache_CachingFramework This instance to allow method chaining
	 */
	public function set($hash, $data) {
		$data = call_user_func($this->getSerialize(), $data);

		$this->cacheInstance->set($hash, $data);

		return $this;
	}
}

?>