<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Ingo Renner <ingo@typo3.org>
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
class t3lib_cache_backend_File extends t3lib_cache_AbstractBackend {

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
	 * Sets the directory where the cache files are stored.
	 *
	 * @param string The directory
	 * @return void
	 * @throws t3lib_cache_Exception if the directory does not exist, is not writable or could not be created.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setCacheDirectory($cacheDirectory) {

		if ($cacheDirectory{strlen($cacheDirectory) - 1} !== '/') {
			$cacheDirectory .= '/';
		}

		if (!is_writable($cacheDirectory)) {
			t3lib_div::mkdir_deep(
				t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/',
				$cacheDirectory
			);
		}

		if (!is_dir($cacheDirectory)) {
			throw new t3lib_cache_Exception(
				'The directory "' . $cacheDirectory . '" does not exist.',
				1203965199
			);
		}

		if (!is_writable($cacheDirectory)) {
			throw new t3lib_cache_Exception(
				'The directory "' . $cacheDirectory . '" is not writable.',
				1203965200
			);
		}

		$tagsDirectory = $cacheDirectory . 'tags/';

		if (!is_writable($tagsDirectory)) {
			t3lib_div::mkdir_deep(
				t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/',
				$tagsDirectory
			);
		}

		$this->cacheDirectory = $cacheDirectory;
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
	 * @param integer Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws t3lib_cache_Exception if the directory does not exist or is not writable, or if no cache frontend has been set.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function save($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!self::isValidEntryIdentifier($entryIdentifier)) {
			throw new InvalidArgumentException(
				'"' . $entryIdentifier . '" is not a valid cache entry identifier.',
				1207139693
			);
		}

		if (!is_object($this->cache)) {
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

		foreach ($tags as $tag) {
			if (!self::isValidTag($tag)) {
				throw new InvalidArgumentException(
					'"' . $tag . '" is not a valid tag for a cache entry.',
					1213105438
				);
			}
		}

		if (is_null($lifetime)) {
			$lifetime = $this->defaultLifetime;
		}

		$expiryTime          = new DateTime(
			'now +' . $lifetime . ' seconds',
			new DateTimeZone('UTC')
		);
		$entryIdentifierHash = sha1($entryIdentifier);
		$cacheEntryPath      = $this->cacheDirectory
			. 'data/' . $this->cache->getIdentifier()
			. '/' . $entryIdentifierHash{0} . '/' . $entryIdentifierHash {1} . '/';
		$filename            = $this->renderCacheFilename($entryIdentifier, $expiryTime);

		if (!is_writable($cacheEntryPath)) {
			try {
				t3lib_div::mkdir_deep(
					t3lib_div::getIndpEnv('TYPO3_DOCUMENT_ROOT') . '/',
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

		foreach ($tags as $tag) {
			$tagPath = $this->cacheDirectory . 'tags/' . $tag . '/';

			if (!is_writable($tagPath)) {
				mkdir($tagPath);
			}

			touch($tagPath . $this->cache->getIdentifier() . '_' . $entryIdentifier);
		}
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function load($entryIdentifier) {
		$pathsAndFilenames = $this->findCacheFilesByEntry($entryIdentifier);
		$cacheEntry        = FALSE;

		if ($pathsAndFilenames !== FALSE) {
			$cacheEntry = file_get_contents(array_pop($pathsAndFilenames));
		}

		return $cacheEntry;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param unknown_type
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function has($entryIdentifier) {
		return $this->findCacheFilesByEntry($entryIdentifier) !== FALSE;
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
		$pathsAndFilenames = $this->findCacheFilesByEntry($entryIdentifier);

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
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tag.
	 *
	 * @param string The tag to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function findEntriesByTag($tag) {
		if (!is_object($this->cache)) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$path       = $this->cacheDirectory . 'tags/';
		$pattern    = $path . $tag . '/*';
		$filesFound = glob($pattern);

		if ($filesFound === FALSE || count($filesFound) == 0) {
			return array();
		}

		$cacheEntries = array();
		foreach ($filesFound as $filename) {
			list(,$entryIdentifier) = explode('_', basename($filename));
			$cacheEntries[$entryIdentifier] = $entryIdentifier;
		}

		return array_values($cacheEntries);
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the specified tags.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * a tag.
	 *
	 * @param array Array of tags to search for, the "*" wildcard is supported
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function findEntriesByTags(array $tags) {
		$taggedEntries = array();
		$foundEntries  = array();

		foreach ($tags as $tag) {
			$taggedEntries[$tag] = $this->findEntriesByTag($tag);
		}

		$intersectedTaggedEntries = call_user_func_array('array_intersect', $taggedEntries);

		foreach ($intersectedTaggedEntries as $entryIdentifier) {
			$foundEntries[$entryIdentifier] = $entryIdentifier;
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
		if (!is_object($this->cache)) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$path       = $this->cacheDirectory . 'data/' . $this->cache->getIdentifier() . '/';
		$pattern    = $path . '*/*/*';
		$filesFound = glob($pattern);

		if ($filesFound === FALSE || count($filesFound) == 0) {
			return;
		}

		foreach($filesFound as $filename) {
			list(,$entryIdentifier) = explode('_', basename($filename));
			$this->remove($entryIdentifier);
		}
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string The tag the entries must have
	 * @return void
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	public function flushByTag($tag) {
		$path       = $this->cacheDirectory . 'tags/' . $tag . '/';
		$pattern    = $path . '*';
		$filesFound = glob($pattern);

		foreach ($filesFound as $file) {
			unlink($file);
		}
		rmdir($path);
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
		$filename = $expiryTime->format('Y-m-d\TH\;i\;sO') . '_' . $identifier;

		return $filename;
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
	protected function findCacheFilesByEntry($entryIdentifier) {
		if (!is_object($this->cache)) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$path            = $this->cacheDirectory . 'data/' . $this->cache->getIdentifier() . '/';
		$pattern         = $path . '*/*/????-??-?????;??;???????_' . $entryIdentifier;
		$filesFound      = glob($pattern);
		$validFilesFound = array();

		if ($filesFound === FALSE || count($filesFound) == 0) {
			return FALSE;
		}

		foreach ($filesFound as $pathAndFilename) {
			$expiryTimeAndIdentifier = explode('/', $pathAndFilename);
			$expiryTime = substr(array_pop($expiryTimeAndIdentifier), 0, -(strlen($entryIdentifier) + 1));

			$expiryTimeParsed = strtotime(str_replace(';', ':', $expiryTime));

			$now = new DateTime(
				'now',
				new DateTimeZone('UTC')
			);
			$now = (int) $now->format('U');

			if ($expiryTimeParsed > $now) {
				$validFilesFound[] = $pathAndFilename;
			} else {
				unlink($pathAndFilename);
			}
		}

		if (count($validFilesFound) == 0) {
			return FALSE;
		}

		return $validFilesFound;
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
		if (!is_object($this->cache)) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$path       = $this->cacheDirectory . 'tags/';
		$pattern    = $path . '*/' . $this->cache->getIdentifier() . '_' . $entryIdentifier;
		$filesFound = glob($pattern);

		if ($filesFound === FALSE || count($filesFound) == 0) {
			return FALSE;
		}

		return $filesFound;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_file.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_file.php']);
}

?>