<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
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
 * @version $Id$
 */
class t3lib_cache_backend_FileBackend extends t3lib_cache_backend_AbstractBackend {

	const SEPARATOR = '-';

	const FILENAME_EXPIRYTIME_FORMAT    = 'YmdHis';
	const FILENAME_EXPIRYTIME_GLOB      = '??????????????';
	const FILENAME_EXPIRYTIME_UNLIMITED = '99991231235959';

	/**
	 * @var string Directory where the files are stored
	 */
	protected $cacheDirectory = '';

	/**
	 * Constructs this backend
	 *
	 * @param mixed Configuration options - depends on the actual backend
	 */
	public function __construct(array $options = array()) {
		parent::__construct($options);

		if (empty($this->cacheDirectory)) {
			$cacheDirectory = 'typo3temp/cache/';
			try {
				$this->setCacheDirectory($cacheDirectory);
			} catch(t3lib_cache_Exception $exception) {

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
		$documentRoot = t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/';

			// resetting if an absolute path is given
		if ($cacheDirectory{0} == '/') {
			$documentRoot = '/';
		}

		if ($cacheDirectory{strlen($cacheDirectory) - 1} !== '/') {
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
				'The directory "' . $cacheDirectory . '" does not exist.',
				1203965199
			);
		}

		if (!is_writable($documentRoot . $cacheDirectory)) {
			throw new t3lib_cache_Exception(
				'The directory "' . $cacheDirectory . '" is not writable.',
				1203965200
			);
		}

		$tagsDirectory = $cacheDirectory . 'tags/';

		if (!is_writable($tagsDirectory)) {
			t3lib_div::mkdir_deep(
				$documentRoot,
				$tagsDirectory
			);
		}

		$this->cacheDirectory = $documentRoot . $cacheDirectory;
	}

	/**
	 * Returns the directory where the cache files are stored
	 *
	 * @return string Full path of the cache directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCacheDirectory() {
		return $this->cacheDirectory;
	}

	/**
	 * Saves data in a cache file.
	 *
	 * @param string An identifier for this specific cache entry
	 * @param string The data to be stored
	 * @param array Tags to associate with this cache entry
	 * @param integer Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws t3lib_cache_Exception if the directory does not exist or is not writable, or if no cache frontend has been set.
	 * @throws t3lib_cache_exception_InvalidData if the data to bes stored is not a string.
	 * @author Robert Lemke <robert@typo3.org>
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

		if ($lifetime === 0 || ($lifetime === NULL && $this->defaultLifetime === 0)) {
			$expiryTime = new DateTime('9999-12-31T23:59:59+0000', new DateTimeZone('UTC'));
		} else {
			if ($lifetime === NULL) {
				$lifetime = $this->defaultLifetime;
			}
			$expiryTime = new DateTime('now +' . $lifetime . ' seconds', new DateTimeZone('UTC'));
		}

		$cacheEntryPath = $this->renderCacheEntryPath($entryIdentifier);
		$filename       = $this->renderCacheFilename($entryIdentifier, $expiryTime);

		if (!is_writable($cacheEntryPath)) {
			try {
				t3lib_div::mkdir_deep(
					'',
					$cacheEntryPath
				);
			} catch(Exception $exception) {

			}
			if (!is_writable($cacheEntryPath)) {
				throw new t3lib_cache_Exception(
					'The cache directory "' . $cacheEntryPath . '" could not be created.',
					1204026250
				);
			}
		}

		$this->remove($entryIdentifier);

		$temporaryFilename = $filename . '.' . uniqid() . '.temp';
		$result = file_put_contents($cacheEntryPath . $temporaryFilename, $data);
		if ($result === FALSE) {
			throw new t3lib_cache_Exception(
				'The temporary cache file "' . $temporaryFilename . '" could not be written.',
				1204026251
			);
		}

		for ($i = 0; $i < 5; $i++) {
			$result = rename(
				$cacheEntryPath . $temporaryFilename,
				$cacheEntryPath . $filename
			);

			if ($result === TRUE) {
				break;
			}
		}

		if ($result === FALSE) {
			throw new t3lib_cache_Exception(
				'The cache file "' . $filename . '" could not be written.',
				1222361632
			);
		}

		foreach ($tags as $tag) {
			$tagPath = $this->cacheDirectory . 'tags/' . $tag . '/';

			if (!is_writable($tagPath)) {
				t3lib_div::mkdir_deep(
					'',
					$tagPath
				);
			}

			touch($tagPath
				. $this->cache->getIdentifier()
				. self::SEPARATOR
				. $entryIdentifier
			);
		}
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function get($entryIdentifier) {
		$pathsAndFilenames = $this->findCacheFilesByIdentifier($entryIdentifier);

		if ($pathsAndFilenames === FALSE) {
			return FALSE;
		}
		$pathAndFilename = array_pop($pathsAndFilenames);

		return ($this->isCacheFileExpired($pathAndFilename)) ? FALSE : file_get_contents($pathAndFilename);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param	string $entryIdentifier
	 * @return	boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function has($entryIdentifier) {
		$pathsAndFilenames = $this->findCacheFilesByIdentifier($entryIdentifier);

		if ($pathsAndFilenames === FALSE) return FALSE;

		return !$this->isCacheFileExpired(array_pop($pathsAndFilenames));
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function remove($entryIdentifier) {
		$pathsAndFilenames = $this->findCacheFilesByIdentifier($entryIdentifier);

		if ($pathsAndFilenames === FALSE) {
			return FALSE;
		}

		foreach ($pathsAndFilenames as $pathAndFilename) {
			$result = unlink($pathAndFilename);
			if ($result === FALSE) {
				return FALSE;
			}
		}

		$pathsAndFilenames = $this->findTagFilesByEntry($entryIdentifier);
		if ($pathsAndFilenames === FALSE) {
			return FALSE;
		}

		foreach ($pathsAndFilenames as $pathAndFilename) {
			$result = unlink($pathAndFilename);
			if ($result === FALSE) {
				return FALSE;
			}
		}

		return TRUE;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersByTag($tag) {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$path    = $this->cacheDirectory . 'tags/';
		$pattern = $path . $tag . '/'
			. $this->cache->getIdentifier() . self::SEPARATOR . '*';
		$filesFound = glob($pattern);

		if ($filesFound === FALSE || count($filesFound) === 0) {
			return array();
		}

		$cacheEntries = array();
		foreach ($filesFound as $filename) {
			list(,$entryIdentifier) = explode(self::SEPARATOR, basename($filename));
			if ($this->has($entryIdentifier)) {
				$cacheEntries[$entryIdentifier] = $entryIdentifier;
			}
		}

		return array_values($cacheEntries);
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tags.
	 *
	 * @param array Array of tags to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findIdentifiersByTags(array $tags) {
		$taggedEntries = array();
		$foundEntries  = array();

		foreach ($tags as $tag) {
			$taggedEntries[$tag] = $this->findIdentifiersByTag($tag);
		}

		$intersectedTaggedEntries = call_user_func_array('array_intersect', $taggedEntries);

		foreach ($intersectedTaggedEntries as $entryIdentifier) {
			if ($this->has($entryIdentifier)) {
				$foundEntries[$entryIdentifier] = $entryIdentifier;
			}
		}

		return $foundEntries;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function flush() {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$dataPath = $this->cacheDirectory . 'data/' . $this->cache->getIdentifier() . '/';
		$tagsPath = $this->cacheDirectory . 'tags/' . $this->cache->getIdentifier() . '/';

		t3lib_div::rmdir($dataPath, true);
		t3lib_div::rmdir($tagsPath, true);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string The tag the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
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
	 * @param array	The tags the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
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
	 * @param	string	$cacheFilename
	 * @return	boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function isCacheFileExpired($cacheFilename) {
		list($timestamp) = explode(self::SEPARATOR, basename($cacheFilename), 2);
		return $timestamp < gmdate('YmdHis');
	}

	/**
	 * Does garbage collection for the given entry or all entries.
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function collectGarbage() {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1222686150
			);
		}

		$pattern = $this->cacheDirectory . 'data/' . $this->cache->getIdentifier() . '/*/*/*';
		$filesFound = glob($pattern);

		foreach ($filesFound as $cacheFile) {
			$splitFilename = explode(self::SEPARATOR, basename($cacheFile), 2);
			if ($splitFilename[0] < gmdate('YmdHis')) {
				$this->remove($splitFilename[1]);
			}
		}
	}

	/**
	 * Renders a file name for the specified cache entry
	 *
	 * @param string Identifier for the cache entry
	 * @param DateTime Date and time specifying the expiration of the entry. Must be a UTC time.
	 * @return string Filename of the cache data file
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function renderCacheFilename($identifier, DateTime $expiryTime) {
		$filename = $expiryTime->format(self::FILENAME_EXPIRYTIME_FORMAT) . self::SEPARATOR . $identifier;

		return $filename;
	}

	/**
	 * Renders the full path (excluding file name) leading to the given cache entry.
	 * Doesn't check if such a cache entry really exists.
	 *
	 * @param string $identifier Identifier for the cache entry
	 * @return string Absolute path leading to the directory containing the cache entry
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function renderCacheEntryPath($identifier) {
		$identifierHash = sha1($identifier);
		return $this->cacheDirectory . 'data/' . $this->cache->getIdentifier() . '/' . $identifierHash[0] . '/' . $identifierHash[1] . '/';
	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 * Usually only one cache entry should be found - if more than one exist, this
	 * is due to some error or crash.
	 *
	 * @param string The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws t3lib_cache_Exception if no frontend has been set
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$pattern    = $this->renderCacheEntryPath($entryIdentifier) . self::FILENAME_EXPIRYTIME_GLOB . self::SEPARATOR . $entryIdentifier;
		$filesFound = glob($pattern);

		return $filesFound;
	}


	/**
	 * Tries to find the tag entries for the specified cache entry.
	 *
	 * @param string The cache entry identifier to find tag files for
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws t3lib_cache_Exception if no frontend has been set
	 */
	protected function findTagFilesByEntry($entryIdentifier) {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$path       = $this->cacheDirectory . 'tags/';
		$pattern    = $path . '*/' . $this->cache->getIdentifier() . self::SEPARATOR . $entryIdentifier;
		$filesFound = glob($pattern);

		if ($filesFound === FALSE || count($filesFound) === 0) {
			return FALSE;
		}

		return $filesFound;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_filebackend.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_filebackend.php']);
}

?>