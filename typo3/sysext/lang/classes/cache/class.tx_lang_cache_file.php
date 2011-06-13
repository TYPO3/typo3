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
 * Provides a simple cache based on the filesystem.
 *
 * @package	TYPO3
 * @subpackage	tx_lang
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
class tx_lang_cache_File extends tx_lang_cache_Abstract {

	/**
	 * Get a cached value.
	 *
	 * @param  string $hash Cache hash
	 * @return bool|mixed
	 */
	public function get($hash) {
		$cacheFileName = $this->getCacheFilename($hash);
		if (@is_file($cacheFileName)) {
			return unserialize(t3lib_div::getUrl($cacheFileName));
		} else {
			return FALSE;
		}
	}

	/**
	 * Adds a value to the cache.
	 *
	 * @throws RuntimeException
	 * @param string $hash Cache hash
	 * @param mixed $data
	 * @return tx_lang_cache_File This instance to allow method chaining
	 */
	public function set($hash, $data) {
		$cacheFileName = $this->getCacheFilename($hash);
		$res = t3lib_div::writeFileToTypo3tempDir($cacheFileName, serialize($data));
		if ($res !== NULL) {
			throw new RuntimeException(
				'TYPO3 Fatal Error: ' . $res,
				1270853903
			);
		}

		return $this;
	}

	/**
	 * Gets the cache absolute file path.
	 *
	 * @param string $hashSource Cache hash
	 * @return string
	 */
	protected function getCacheFilename($hashSource) {
		return PATH_site . 'typo3temp/llxml/' . $hashSource . '.cache';
	}
}

?>