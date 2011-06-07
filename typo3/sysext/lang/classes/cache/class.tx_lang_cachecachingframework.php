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
class tx_lang_CacheCachingFramework extends tx_lang_CacheAbstract {

	/**
	 * Gets a cached value.
	 *
	 * @param  string $hash Cache hash
	 * @return bool|mixed
	 */
	public function get($hash) {
		$cacheIdentifier = 'language-' . $hash;
		$cacheHash = md5($cacheIdentifier);
		$cache = t3lib_pageSelect::getHash($cacheHash);
		$unserialize = $this->getUnserialize();

		if ($cache) {
			$data = $unserialize($cache);
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
	 * @return tx_lang_CacheCachingFramework This instance to allow method chaining
	 */
	public function set($hash, $data) {
		$cacheIdentifier = 'language-' . $hash;
		$cacheHash = md5($cacheIdentifier);
		$serialize = $this->getSerialize();

		t3lib_pageSelect::storeHash(
			$cacheHash,
			$serialize($data),
			'language'
		);

		return $this;
	}
}

?>