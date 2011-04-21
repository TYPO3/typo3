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
 * A caching backend which stores cache entries in files
 *
 * This file is a backport from FLOW3
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @api
 * @scope prototype
 */
class t3lib_cache_backend_FileBackend extends t3lib_cache_backend_AbstractBackend implements t3lib_cache_backend_PhpCapableBackend {

	const SEPARATOR = '^';

	const EXPIRYTIME_FORMAT = 'YmdHis';
	const EXPIRYTIME_LENGTH = 14;

	const DATASIZE_DIGITS = 10;

	/**
	 * Directory where the files are stored
	 *
	 * @var string
	 */
	protected $cacheDirectory = '';

	/**
	 * TYPO3 v4 note: This varialbe is only available in v5
	 * Temporary path to cache directory before setCache() was called. It is
	 * set by setCacheDirectory() and used in setCache() method which calls
	 * the directory creation if needed. The variable is not used afterwards,
	 * the final cache directory path is stored in $this->cacheDirectory then.
	 *
	 * @var string Temporary path to cache directory
	 */
	protected $temporaryCacheDirectory = '';

	/**
	 * A file extension to use for each cache entry.
	 *
	 * @var string
	 */
	protected $cacheEntryFileExtension = '';


	/**
	 * Sets a reference to the cache frontend which uses this backend and
	 * initializes the default cache directory.
	 *
	 * TYPO3 v4 note: This method is different between TYPO3 v4 and FLOW3
	 * because the Environment class to get the path to a temporary directory
	 * does not exist in v4.
	 *
	 * @param t3lib_cache_frontend_Frontend $cache The cache frontend
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCache(t3lib_cache_frontend_Frontend $cache) {
		parent::setCache($cache);

		if (empty($this->temporaryCacheDirectory)) {
				// If no cache directory was given with cacheDirectory
				// configuration option, set it to a path below typo3temp/
			$temporaryCacheDirectory = PATH_site . 'typo3temp/';
		} else {
			$temporaryCacheDirectory = $this->temporaryCacheDirectory;
		}

		$codeOrData = ($cache instanceof t3lib_cache_frontend_PhpFrontend) ? 'Code' : 'Data';
		$finalCacheDirectory = $temporaryCacheDirectory . 'Cache/' . $codeOrData . '/' . $this->cacheIdentifier . '/';

		if (!is_dir($finalCacheDirectory)) {
			$this->createFinalCacheDirectory($finalCacheDirectory);
		}
		unset($this->temporaryCacheDirectory);
		$this->cacheDirectory = $finalCacheDirectory;

		$this->cacheEntryFileExtension = ($cache instanceof t3lib_cache_frontend_PhpFrontend) ? '.php' : '';
	}

	/**
	 * Sets the directory where the cache files are stored. By default it is
	 * assumed that the directory is below the TYPO3_DOCUMENT_ROOT. However, an
	 * absolute path can be selected, too.
	 *
	 * This method does not exist in FLOW3 anymore, but it is needed in
	 * TYPO3 v4 to enable a cache path outside of document root. The final
	 * cache path is checked and created in createFinalCachDirectory(),
	 * called by setCache() method, which is done _after_ the cacheDirectory
	 * option was handled.
	 *
	 * @param string The cache base directory. If a relative path is given, it
	 * 		is assumed it is in TYPO3_DOCUMENT_ROOT. If an absolute path is
	 * 		given it is taken as is.
	 * @return void
	 * @throws t3lib_cache_Exception if the directory is not within allowed
	 * 		open_basedir path.
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 */
	public function setCacheDirectory($cacheDirectory) {
			// Skip handling if directory is a stream ressource
			// This is used by unit tests with vfs:// directoryies
		if (strpos($cacheDirectory, '://')) {
			$this->temporaryCacheDirectory = $cacheDirectory;
			return;
		}

		$documentRoot = PATH_site;

		if (($open_basedir = ini_get('open_basedir'))) {
			if (TYPO3_OS === 'WIN') {
				$delimiter = ';';
				$cacheDirectory = str_replace('\\', '/', $cacheDirectory);
				if (!(preg_match('/[A-Z]:/', substr($cacheDirectory, 0, 2)))) {
					$cacheDirectory = PATH_site . $cacheDirectory;
				}
			} else {
				$delimiter = ':';
				if ($cacheDirectory[0] != '/') {
						// relative path to cache directory.
					$cacheDirectory = PATH_site . $cacheDirectory;
				}
			}

			$basedirs = explode($delimiter, $open_basedir);
			$cacheDirectoryInBaseDir = FALSE;
			foreach ($basedirs as $basedir) {
				if (TYPO3_OS === 'WIN') {
					$basedir = str_replace('\\', '/', $basedir);
				}
				if ($basedir[strlen($basedir) - 1] !== '/') {
					$basedir .= '/';
				}
				if (t3lib_div::isFirstPartOfStr($cacheDirectory, $basedir)) {
					$documentRoot = $basedir;
					$cacheDirectory = str_replace($basedir, '', $cacheDirectory);
					$cacheDirectoryInBaseDir = TRUE;
					break;
				}
			}
			if (!$cacheDirectoryInBaseDir) {
				throw new t3lib_cache_Exception(
					'Open_basedir restriction in effect. The directory "' . $cacheDirectory . '" is not in an allowed path.'
				);
			}
		} else {
			if ($cacheDirectory[0] == '/') {
					// Absolute path to cache directory.
				$documentRoot = '/';
			}
			if (TYPO3_OS === 'WIN') {
				if (substr($cacheDirectory, 0,  strlen($documentRoot)) === $documentRoot) {
					$documentRoot = '';
				}
			}
		}

			// After this point all paths have '/' as directory seperator
		if ($cacheDirectory[strlen($cacheDirectory) - 1] !== '/') {
			$cacheDirectory .= '/';
		}

		$this->temporaryCacheDirectory = $documentRoot . $cacheDirectory . $this->cacheIdentifier . '/';
	}

	/**
	 * Create the final cache directory if it does not exist. This method
	 * exists in TYPO3 v4 only.
	 *
	 * @throws \t3lib_cache_Exception If directory is not writable after creation
	 * @param string Absolute path to final cache directory
	 * @return void
	 */
	protected function createFinalCacheDirectory($finalCacheDirectory) {
		try {
			t3lib_div::mkdir_deep($finalCacheDirectory);
		} catch (\RuntimeException $e) {
			throw new \t3lib_cache_Exception(
				'The directory "' . $finalCacheDirectory . '" can not be created.',
				1303669848,
				$e
			);
		}
		if (!is_writable($finalCacheDirectory)) {
			throw new \t3lib_cache_Exception(
				'The directory "' . $finalCacheDirectory . '" is not writable.',
				1203965200
			);
		}
	}

	/**
	 * Returns the directory where the cache files are stored
	 *
	 * @return string Full path of the cache directory
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCacheDirectory() {
		return $this->cacheDirectory;
	}

	/**
	 * Saves data in a cache file.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws t3lib_cache_Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
	 * @throws t3lib_cache_exception_InvalidData if the data to bes stored is not a string.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!is_string($data)) {
			throw new t3lib_cache_Exception_InvalidData(
				'The specified data is of type "' . gettype($data) . '" but a string is expected.',
				1204481674
			);
		}

		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073032
			);
		}

		if ($entryIdentifier === '') {
			throw new \InvalidArgumentException(
				'The specified entry identifier must not be empty.',
				1298114280
			);
		}

		$this->remove($entryIdentifier);

		$temporaryCacheEntryPathAndFilename = $this->cacheDirectory . uniqid() . '.temp';
		if (strlen($temporaryCacheEntryPathAndFilename) > t3lib_div::getMaximumPathLength()) {
			throw new t3lib_cache_Exception(
				'The length of the temporary cache file path "' . $temporaryCacheEntryPathAndFilename .
				'" is ' . strlen($temporaryCacheEntryPathAndFilename) . ' characters long and exceeds the maximum path length of ' .
				t3lib_div::getMaximumPathLength() . '. Please consider setting the temporaryDirectoryBase option to a shorter path. ',
				1248710426
			);
		}

		$expiryTime = ($lifetime === NULL) ? 0 : ($GLOBALS['EXEC_TIME'] + $lifetime);
		$metaData = str_pad($expiryTime, self::EXPIRYTIME_LENGTH) . implode(' ', $tags) . str_pad(strlen($data), self::DATASIZE_DIGITS);
		$result = file_put_contents($temporaryCacheEntryPathAndFilename, $data . $metaData);

		if ($result === FALSE) {
			throw new t3lib_cache_exception(
				'The temporary cache file "' . $temporaryCacheEntryPathAndFilename . '" could not be written.',
				1204026251
			);
		}

		$i = 0;
		$cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
			// @TODO: Figure out why the heck this is done and maybe find a smarter solution, report to FLOW3
		while (!rename($temporaryCacheEntryPathAndFilename, $cacheEntryPathAndFilename) && $i < 5) {
			$i++;
		}

			// @FIXME: At least the result of rename() should be handled here, report to FLOW3
		if ($result === FALSE) {
			throw new t3lib_cache_exception(
				'The cache file "' . $cacheEntryPathAndFilename . '" could not be written.',
				1222361632
			);
		}
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function get($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073033
			);
		}

		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if ($this->isCacheFileExpired($pathAndFilename)) {
			return FALSE;
		}
		$dataSize = (integer) file_get_contents($pathAndFilename, NULL, NULL, filesize($pathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
		return file_get_contents($pathAndFilename, NULL, NULL, 0, $dataSize);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function has($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073034
			);
		}

		return !$this->isCacheFileExpired($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function remove($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073035
			);
		}
		if ($entryIdentifier === '') {
			throw new \InvalidArgumentException(
				'The specified entry identifier must not be empty.',
				1298114279
			);
		}

		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if (file_exists($pathAndFilename) === FALSE) {
			return FALSE;
		}
		if (unlink($pathAndFilename) === FALSE) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $searchedTag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function findIdentifiersByTag($searchedTag) {
		$entryIdentifiers = array();
		$now = $GLOBALS['EXEC_TIME'];
		$cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
		for ($directoryIterator = t3lib_div::makeInstance('DirectoryIterator', $this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
			if ($directoryIterator->isDot()) {
				continue;
			}
			$cacheEntryPathAndFilename = $directoryIterator->getPathname();
			$index = (integer) file_get_contents($cacheEntryPathAndFilename, NULL, NULL, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
			$metaData = file_get_contents($cacheEntryPathAndFilename, NULL, NULL, $index);

			$expiryTime = (integer) substr($metaData, 0, self::EXPIRYTIME_LENGTH);
			if ($expiryTime !== 0 && $expiryTime < $now) {
				continue;
			}
			if (in_array($searchedTag, explode(' ', substr($metaData, self::EXPIRYTIME_LENGTH, -self::DATASIZE_DIGITS)))) {
				if ($cacheEntryFileExtensionLength > 0) {
					$entryIdentifiers[] = substr($directoryIterator->getFilename(), 0, - $cacheEntryFileExtensionLength);
				} else {
					$entryIdentifiers[] = $directoryIterator->getFilename();
				}
			}
		}
		return $entryIdentifiers;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tags.
	 *
	 * @param array $searchedTags Array of tags to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Ingo Renner <ingo@typo3.org>
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 * @api
	 */
	public function findIdentifiersByTags(array $searchedTags) {
		$entryIdentifiers = array();
		$now = $GLOBALS['EXEC_TIME'];
		$cacheEntryFileExtensionLength = strlen($this->cacheEntryFileExtension);
		for ($directoryIterator = t3lib_div::makeInstance('DirectoryIterator', $this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
			if ($directoryIterator->isDot()) {
				continue;
			}
			$cacheEntryPathAndFilename = $directoryIterator->getPathname();
			$index = (integer) file_get_contents($cacheEntryPathAndFilename, NULL, NULL, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
			$metaData = file_get_contents($cacheEntryPathAndFilename, NULL, NULL, $index);

			$expiryTime = (integer) substr($metaData, 0, self::EXPIRYTIME_LENGTH);
			if ($expiryTime !== 0 && $expiryTime < $now) {
				continue;
			}
			if (in_array($searchedTags, explode(' ', substr($metaData, self::EXPIRYTIME_LENGTH, -self::DATASIZE_DIGITS)))) {
				if ($cacheEntryFileExtensionLength > 0) {
					$entryIdentifiers[] = substr($directoryIterator->getFilename(), 0, - $cacheEntryFileExtensionLength);
				} else {
					$entryIdentifiers[] = $directoryIterator->getFilename();
				}
			}
		}
		return $entryIdentifiers;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Christian Kuhn <lolli@schwarzbu.ch>
	 * @api
	 */
	public function flush() {
		t3lib_div::rmdir($this->cacheDirectory, TRUE);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 * @api
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersByTag($tag);
		if (count($identifiers) === 0) {
			return;
		}

		foreach ($identifiers as $entryIdentifier) {
			$this->remove($entryIdentifier);
		}
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param array $tags The tags the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 * @api
	 */
	public function flushByTags(array $tags) {
		foreach ($tags as $tag) {
			$this->flushByTag($tag);
		}
	}

	/**
	 * Checks if the given cache entry files are still valid or if their
	 * lifetime has exceeded.
	 *
	 * @param string $cacheEntryPathAndFilename
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	protected function isCacheFileExpired($cacheEntryPathAndFilename) {
		if (file_exists($cacheEntryPathAndFilename) === FALSE) {
			return TRUE;
		}
		$index = (integer) file_get_contents($cacheEntryPathAndFilename, NULL, NULL, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
		$expiryTime = file_get_contents($cacheEntryPathAndFilename, NULL, NULL, $index, self::EXPIRYTIME_LENGTH);
		return ($expiryTime != 0 && $expiryTime < $GLOBALS['EXEC_TIME']);
	}

	/**
	 * Does garbage collection
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function collectGarbage() {
		for($directoryIterator = new \DirectoryIterator($this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
			if ($directoryIterator->isDot()) {
				continue;
			}

			if ($this->isCacheFileExpired($directoryIterator->getPathname())) {
				if (strlen($this->cacheEntryFileExtension) > 0) {
					$this->remove(substr($directoryIterator->getFilename(), 0, - $cacheEntryFileExtensionLength));
				} else {
					$this->remove($directoryIterator->getFilename());
				}
			}
		}
	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 * Usually only one cache entry should be found - if more than one exist, this
	 * is due to some error or crash.
	 *
	 * @param string $entryIdentifier The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws t3lib_cache_Exception if no frontend has been set
	 * @internal
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		$pattern = $this->cacheDirectory . $entryIdentifier;
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) === 0) {
			return FALSE;
		}

		return $filesFound;
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @api
	 */
	public function requireOnce($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073036
			);
		}

		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		return ($this->isCacheFileExpired($pathAndFilename)) ? FALSE : require_once($pathAndFilename);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_filebackend.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_filebackend.php']);
}

?>