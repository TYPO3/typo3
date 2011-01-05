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

	const SEPARATOR = '^';

	const EXPIRYTIME_FORMAT = 'YmdHis';
	const EXPIRYTIME_LENGTH = 14;

	/**
	 * @var string Directory where the files are stored
	 */
	protected $cacheDirectory = '';

	protected $root = '/';

	/**
	 * Maximum allowed file path length in the current environment.
	 * Will be set in initializeObject()
	 *
	 * @var integer
	 */
	protected $maximumPathLength = null;

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

		if (is_null($this->maximumPathLength)) {
			$this->maximumPathLength = t3lib_div::getMaximumPathLength();
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
				if (!(preg_match('/[A-Z]:/', substr($cacheDirectory,0,2)))) {
					$cacheDirectory = PATH_site . $cacheDirectory;
				}
			} else {
				$delimiter = ':';
				if ($cacheDirectory{0} != '/') {
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
				if ($basedir{strlen($basedir) - 1} !== '/') {
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
			if ($cacheDirectory{0} == '/') {
					// absolute path to cache directory.
				$documentRoot = '/';
			}

			if (TYPO3_OS === 'WIN') {
				if (substr($cacheDirectory, 0,  strlen($documentRoot)) === $documentRoot) {
					$documentRoot = '';
				}
			}
		}

		// after this point all paths have '/' as directory seperator

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

		$tagsDirectory = $cacheDirectory . 'tags/';



		if (!is_writable($documentRoot . $tagsDirectory)) {
			t3lib_div::mkdir_deep(
				$documentRoot,
				$tagsDirectory
			);
		}
		$this->root = $documentRoot;
		$this->cacheDirectory =  $cacheDirectory;
	}

	/**
	 * Returns the directory where the cache files are stored
	 *
	 * @return string Full path of the cache directory
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getCacheDirectory() {
		return $this->root . $this->cacheDirectory;
	}

	/**
	 * Saves data in a cache file.
	 *
	 * @param string An identifier for this specific cache entry
	 * @param string The data to be stored
	 * @param array Tags to associate with this cache entry
	 * @param integer Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws t3lib_cache_Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
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

		$expirytime = $this->calculateExpiryTime($lifetime);
		$cacheEntryPath = $this->renderCacheEntryPath($entryIdentifier);
		$absCacheEntryPath = $this->root . $cacheEntryPath;


		if (!is_writable($absCacheEntryPath)) {
			try {
				t3lib_div::mkdir_deep(
					$this->root,
					$cacheEntryPath
				);
			} catch(Exception $exception) {

			}

			if (!is_writable($absCacheEntryPath)) {
				throw new t3lib_cache_Exception(
					'The cache directory "' . $absCacheEntryPath . '" could not be created.',
					1204026250
				);
			}
		}

		$this->remove($entryIdentifier);

		$data = $expirytime->format(self::EXPIRYTIME_FORMAT) . $data;
		$cacheEntryPathAndFilename = $absCacheEntryPath . uniqid() . '.temp';
		if (strlen($cacheEntryPathAndFilename) > $this->maximumPathLength) {
			throw new t3lib_cache_Exception(
				'The length of the temporary cache file path "' . $cacheEntryPathAndFilename .
					'" is ' . strlen($cacheEntryPathAndFilename) . ' characters long and exceeds the maximum path length of ' .
					$this->maximumPathLength . '. Please consider setting the temporaryDirectoryBase option to a shorter path. ',
				1248710426
			);
		}
		$result = file_put_contents($cacheEntryPathAndFilename, $data);
		if ($result === FALSE) {
			throw new t3lib_cache_Exception(
				'The temporary cache file "' . $cacheEntryPathAndFilename . '" could not be written.',
				1204026251
			);
		}

		for ($i = 0; $i < 5; $i++) {
			$result = rename($cacheEntryPathAndFilename, $absCacheEntryPath . $entryIdentifier);
			if ($result === TRUE) {
				break;
			}
		}

		if ($result === FALSE) {
			throw new t3lib_cache_Exception(
				'The cache file "' . $entryIdentifier . '" could not be written.',
				1222361632
			);
		}

		foreach ($tags as $tag) {
			$this->setTag($entryIdentifier, $tag);
		}
	}

	/**
	 * Creates a tag that is associated with the given cache identifier
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string Tag to associate with this cache entry
	 * @return void
	 * @throws t3lib_cache_Exception if the tag path is not writable or exceeds the maximum allowed path length
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Ingo Renner <ingo@typo3.org>
	 */
	protected function setTag($entryIdentifier, $tag) {
		$tagPath = $this->cacheDirectory . 'tags/' . $tag . '/';
		$absTagPath = $this->root . $tagPath;
		if (!is_writable($absTagPath)) {
			t3lib_div::mkdir_deep($this->root, $tagPath);
			if (!is_writable($absTagPath)) {
				throw new t3lib_cache_Exception(
					'The tag directory "' . $absTagPath . '" could not be created.',
					1238242144
				);
			}
		}

		$tagPathAndFilename = $absTagPath . $this->cache->getIdentifier()
			. self::SEPARATOR . $entryIdentifier;
		if (strlen($tagPathAndFilename) > $this->maximumPathLength) {
			throw new t3lib_cache_Exception(
				'The length of the tag path "' . $tagPathAndFilename . '" is ' . strlen($tagPathAndFilename) .
					' characters long and exceeds the maximum path length of ' . $this->maximumPathLength .
					'. Please consider setting the temporaryDirectoryBase option to a shorter path. ',
				1248710426
			);
		}
		touch($tagPathAndFilename);
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
		$pathAndFilename = $this->root . $this->renderCacheEntryPath($entryIdentifier) . $entryIdentifier;
		return ($this->isCacheFileExpired($pathAndFilename)) ? FALSE : file_get_contents($pathAndFilename, NULL, NULL, self::EXPIRYTIME_LENGTH);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param	string $entryIdentifier
	 * @return	boolean TRUE if such an entry exists, FALSE if not
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function has($entryIdentifier) {
		return !$this->isCacheFileExpired($this->root . $this->renderCacheEntryPath($entryIdentifier) . $entryIdentifier);
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
		$pathAndFilename = $this->root . $this->renderCacheEntryPath($entryIdentifier) . $entryIdentifier;

		if (!file_exists($pathAndFilename)) {
			return FALSE;
		}

		if (unlink ($pathAndFilename) === FALSE) {
			return FALSE;
		}

		foreach($this->findTagFilesByEntry($entryIdentifier) as $pathAndFilename) {
			if (!file_exists($pathAndFilename)) {
				return FALSE;
			}

			if (unlink ($pathAndFilename) === FALSE) {
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

		$path = $this->root . $this->cacheDirectory . 'tags/';
		$pattern = $path . $tag . '/' . $this->cache->getIdentifier() . self::SEPARATOR . '*';
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

		$dataPath = $this->root . $this->cacheDirectory . 'data/' . $this->cache->getIdentifier() . '/';
		$tagsPath = $this->root . $this->cacheDirectory . 'tags/';

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
		$timestamp = (file_exists($cacheFilename)) ? file_get_contents($cacheFilename, NULL, NULL, 0, self::EXPIRYTIME_LENGTH) : 1;
		return $timestamp < gmdate(self::EXPIRYTIME_FORMAT);
	}

	/**
	 * Does garbage collection
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

		$pattern = $this->root . $this->cacheDirectory . 'data/' . $this->cache->getIdentifier() . '/*/*/*';
		$filesFound = glob($pattern);

		foreach ($filesFound as $cacheFilename) {
			if ($this->isCacheFileExpired($cacheFilename)) {
				$this->remove(basename($cacheFilename));
 			}
 		}
	}

	/**
	 * Renders the full path (excluding file name) leading to the given cache entry.
	 * Doesn't check if such a cache entry really exists.
	 *
	 * @param string $identifier Identifier for the cache entry
	 * @return string Absolute path leading to the directory containing the cache entry
	 * @author Robert Lemke <robert@typo3.org>
	 * @internal
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
	 * @internal
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$pattern = $this->root . $this->renderCacheEntryPath($entryIdentifier) . $entryIdentifier;
		$filesFound = glob($pattern);
		if ($filesFound === FALSE || count($filesFound) === 0) {
			return FALSE;
		}

		return $filesFound;
	}


	/**
	 * Tries to find the tag entries for the specified cache entry.
	 *
	 * @param string The cache entry identifier to find tag files for
	 * @return array The file names (including path)
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws t3lib_cache_Exception if no frontend has been set
	 * @internal
	 */
	protected function findTagFilesByEntry($entryIdentifier) {
		if (!$this->cache instanceof t3lib_cache_frontend_Frontend) {
			throw new t3lib_cache_Exception(
				'Yet no cache frontend has been set via setCache().',
				1204111376
			);
		}

		$path = $this->root . $this->cacheDirectory . 'tags/';
		$pattern = $path . '*/' . $this->cache->getIdentifier() . self::SEPARATOR . $entryIdentifier;
		return glob($pattern);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_filebackend.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_filebackend.php']);
}

?>