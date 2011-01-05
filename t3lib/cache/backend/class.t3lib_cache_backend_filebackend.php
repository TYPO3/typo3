<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
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
 * @version $Id$
 */
class t3lib_cache_backend_FileBackend extends t3lib_cache_backend_AbstractBackend implements t3lib_cache_backend_PhpCapableBackend {

	const SEPARATOR = '^';

	const EXPIRYTIME_FORMAT = 'YmdHis';
	const EXPIRYTIME_LENGTH = 14;

	const DATASIZE_DIGITS = 10;

	/**
	 * @var string Directory where the files are stored
	 */
	protected $cacheDirectory = '';

	/**
	 * @var string Absolute path to root, usually document root of website
	 */
	protected $root = '/';

	/**
	 * Maximum allowed file path length in the current environment.
	 *
	 * @var integer
	 */
	protected $maximumPathLength = null;

	/**
	 * Constructs this backend
	 *
	 * @param array $options Configuration options - depends on the actual backend
	 */
	public function __construct(array $options = array()) {
		parent::__construct($options);

		if (is_null($this->maximumPathLength)) {
			$this->maximumPathLength = t3lib_div::getMaximumPathLength();
		}
	}

	/**
	 * Sets a reference to the cache frontend which uses this backend and
	 * initializes the default cache directory
	 *
	 * @void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCache(t3lib_cache_frontend_Frontend $cache) {
		parent::setCache($cache);

		if (empty($this->cacheDirectory)) {
			$cacheDirectory = 'typo3temp/cache/';
			try {
				$this->setCacheDirectory($cacheDirectory);
			} catch (t3lib_cache_Exception $exception) {
			}
		}
	}

	/**
	 * Sets the directory where the cache files are stored. By default it is
	 * assumed that the directory is below the TYPO3_DOCUMENT_ROOT. However, an
	 * absolute path can be selected, too.
	 *
	 * @param string The directory. If a relative path is given, it's assumed it's in TYPO3_DOCUMENT_ROOT. If an absolute path is given it is taken as is.
	 * @return void
	 * @throws t3lib_cache_Exception if the directory does not exist, is not writable or could not be created.
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function setCacheDirectory($cacheDirectory) {
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
		$cacheDirectory .= $this->cacheIdentifier;
		if ($cacheDirectory[strlen($cacheDirectory) - 1] !== '/') {
			$cacheDirectory .= '/';
		}

		if (!is_writable($documentRoot . $cacheDirectory)) {
			t3lib_div::mkdir_deep(
				$documentRoot,
				$cacheDirectory
			);
		}
		if (!is_dir($documentRoot . $cacheDirectory)) {
			throw new t3lib_cache_Exception(
				'The directory "' . $documentRoot . $cacheDirectory . '" does not exist.',
				1203965199
			);
		}
		if (!is_writable($documentRoot . $cacheDirectory)) {
			throw new t3lib_cache_Exception(
				'The directory "' . $documentRoot . $cacheDirectory . '" is not writable.',
				1203965200
			);
		}

		$this->root = $documentRoot;
		$this->cacheDirectory = $cacheDirectory;
	}

	/**
	 * Returns the directory where the cache files are stored
	 *
	 * @return string Full path of the cache directory
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getCacheDirectory() {
		return $this->root . $this->cacheDirectory;
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
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'No cache frontend has been set yet via setCache().',
				1204111375
			);
		}

		if (!is_string($data)) {
			throw new t3lib_cache_Exception_InvalidData(
				'The specified data is of type "' . gettype($data) . '" but a string is expected.',
				1204481674
			);
		}

		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073032
			);
		}

		$this->remove($entryIdentifier);

		$temporaryCacheEntryPathAndFilename = $this->root . $this->cacheDirectory . uniqid() . '.temp';
		if (strlen($temporaryCacheEntryPathAndFilename) > $this->maximumPathLength) {
			throw new t3lib_cache_Exception(
				'The length of the temporary cache file path "' . $temporaryCacheEntryPathAndFilename .
				'" is ' . strlen($temporaryCacheEntryPathAndFilename) . ' characters long and exceeds the maximum path length of ' .
				$this->maximumPathLength . '. Please consider setting the temporaryDirectoryBase option to a shorter path. ',
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
		$cacheEntryPathAndFilename = $this->root . $this->cacheDirectory . $entryIdentifier;
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

		$pathAndFilename = $this->root . $this->cacheDirectory . $entryIdentifier;
		if ($this->isCacheFileExpired($pathAndFilename)) {
			return FALSE;
		}
		$dataSize = (integer) file_get_contents($pathAndFilename, NULL, NULL, filesize($pathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
		return file_get_contents($pathAndFilename, NULL, NULL, 0, $dataSize);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function has($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073034
			);
		}

		return !$this->isCacheFileExpired($this->root . $this->cacheDirectory . $entryIdentifier);
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
			throw new InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073035
			);
		}

		$pathAndFilename = $this->root . $this->cacheDirectory . $entryIdentifier;
		if (!file_exists($pathAndFilename)) {
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
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @api
	 */
	public function findIdentifiersByTag($searchedTag) {
		$entryIdentifiers = array();
		$now = $GLOBALS['EXEC_TIME'];
		for ($directoryIterator = t3lib_div::makeInstance('DirectoryIterator', $this->root . $this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
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
				$entryIdentifiers[] = $directoryIterator->getFilename();
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
		for ($directoryIterator = t3lib_div::makeInstance('DirectoryIterator', $this->root . $this->cacheDirectory); $directoryIterator->valid(); $directoryIterator->next()) {
			if ($directoryIterator->isDot()) {
				continue;
			}
			$cacheEntryPathAndFilename = $directoryIterator->getPathname();
			$index = (integer) file_get_contents($cacheEntryPathAndFilename, NULL, NULL, filesize($cacheEntryPathAndFilename) - self::DATASIZE_DIGITS, self::DATASIZE_DIGITS);
			$metaData = file_get_contents($cacheEntryPathAndFilename, NULL, NULL, $index);

			$expiryTime = (integer) substr($metaData, 0, self::EXPIRYTIME_LENGTH);
			if ($expiryTime !== 0 && $expiryTime < $GLOBALS['EXEC_TIME']) {
				continue;
			}
			if (in_array($searchedTags, explode(' ', substr($metaData, self::EXPIRYTIME_LENGTH, -self::DATASIZE_DIGITS)))) {
				$entryIdentifiers[] = $directoryIterator->getFilename();
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
		t3lib_div::rmdir($this->root . $this->cacheDirectory, TRUE);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
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
	 */
	protected function isCacheFileExpired($cacheEntryPathAndFilename) {
		if (!file_exists($cacheEntryPathAndFilename)) {
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
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1222686150
			);
		}

		$pattern = $this->root . $this->cacheDirectory . '*';
		$filesFound = glob($pattern);

		if (is_array($filesFound)) {
			foreach ($filesFound as $cacheFilename) {
				if ($this->isCacheFileExpired($cacheFilename)) {
					$this->remove(basename($cacheFilename));
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
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$pattern = $this->root . $this->cacheDirectory . $entryIdentifier;
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
			throw new InvalidArgumentException(
				'The specified entry identifier must not contain a path segment.',
				1282073036
			);
		}

		$pathAndFilename = $this->root . $this->cacheDirectory . $entryIdentifier;
		return ($this->isCacheFileExpired($pathAndFilename)) ? FALSE : require_once($pathAndFilename);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_filebackend.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_filebackend.php']);
}

?>