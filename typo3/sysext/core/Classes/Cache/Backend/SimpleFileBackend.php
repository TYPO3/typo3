<?php
namespace TYPO3\CMS\Core\Cache\Backend;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
/**
 * A caching backend which stores cache entries in files, but does not support or
 * care about expiry times and tags.
 *
 * @api
 */
class SimpleFileBackend extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend implements \TYPO3\CMS\Core\Cache\Backend\PhpCapableBackendInterface {

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
	 * TYPO3 v4 note: This variable is only available in v5
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
	 * @var array
	 */
	protected $cacheEntryIdentifiers = array();

	/**
	 * @var boolean
	 */
	protected $frozen = FALSE;

	/**
	 * If the extension "igbinary" is installed, use it for increased performance.
	 * Caching the result of extension_loaded() here is faster than calling extension_loaded() multiple times.
	 *
	 * @var boolean
	 */
	protected $useIgBinary = FALSE;

	/**
	 * Initializes this cache frontend
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->useIgBinary = extension_loaded('igbinary');
	}

	/**
	 * Sets a reference to the cache frontend which uses this backend and
	 * initializes the default cache directory.
	 *
	 * TYPO3 v4 note: This method is different between TYPO3 v4 and FLOW3
	 * because the Environment class to get the path to a temporary directory
	 * does not exist in v4.
	 *
	 * @param \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache The cache frontend
	 * @throws \TYPO3\CMS\Core\Cache\Exception
	 * @return void
	 */
	public function setCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache) {
		parent::setCache($cache);
		if (empty($this->temporaryCacheDirectory)) {
			// If no cache directory was given with cacheDirectory
			// configuration option, set it to a path below typo3temp/
			$temporaryCacheDirectory = PATH_site . 'typo3temp/';
		} else {
			$temporaryCacheDirectory = $this->temporaryCacheDirectory;
		}
		$codeOrData = $cache instanceof \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend ? 'Code' : 'Data';
		$finalCacheDirectory = $temporaryCacheDirectory . 'Cache/' . $codeOrData . '/' . $this->cacheIdentifier . '/';
		if (!is_dir($finalCacheDirectory)) {
			$this->createFinalCacheDirectory($finalCacheDirectory);
		}
		unset($this->temporaryCacheDirectory);
		$this->cacheDirectory = $finalCacheDirectory;
		$this->cacheEntryFileExtension = $cache instanceof \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend ? '.php' : '';
		if (strlen($this->cacheDirectory) + 23 > \TYPO3\CMS\Core\Utility\GeneralUtility::getMaximumPathLength()) {
			throw new \TYPO3\CMS\Core\Cache\Exception('The length of the temporary cache file path "' . $this->cacheDirectory . '" exceeds the ' . 'maximum path length of ' . (\TYPO3\CMS\Core\Utility\GeneralUtility::getMaximumPathLength() - 23) . '. Please consider ' . 'setting the temporaryDirectoryBase option to a shorter path.', 1248710426);
		}
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
	 * @param string $cacheDirectory The cache base directory. If a relative path
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception if the directory is not within allowed
	 */
	public function setCacheDirectory($cacheDirectory) {
		// Skip handling if directory is a stream ressource
		// This is used by unit tests with vfs:// directoryies
		if (strpos($cacheDirectory, '://')) {
			$this->temporaryCacheDirectory = $cacheDirectory;
			return;
		}
		$documentRoot = PATH_site;
		if ($open_basedir = ini_get('open_basedir')) {
			if (TYPO3_OS === 'WIN') {
				$delimiter = ';';
				$cacheDirectory = str_replace('\\', '/', $cacheDirectory);
				if (!preg_match('/[A-Z]:/', substr($cacheDirectory, 0, 2))) {
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
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($cacheDirectory, $basedir)) {
					$documentRoot = $basedir;
					$cacheDirectory = str_replace($basedir, '', $cacheDirectory);
					$cacheDirectoryInBaseDir = TRUE;
					break;
				}
			}
			if (!$cacheDirectoryInBaseDir) {
				throw new \TYPO3\CMS\Core\Cache\Exception('Open_basedir restriction in effect. The directory "' . $cacheDirectory . '" is not in an allowed path.');
			}
		} else {
			if ($cacheDirectory[0] == '/') {
				// Absolute path to cache directory.
				$documentRoot = '/';
			}
			if (TYPO3_OS === 'WIN') {
				if (substr($cacheDirectory, 0, strlen($documentRoot)) === $documentRoot) {
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
	 * @param string $finalCacheDirectory Absolute path to final cache directory
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception If directory is not writable after creation
	 */
	protected function createFinalCacheDirectory($finalCacheDirectory) {
		try {
			\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir_deep($finalCacheDirectory);
		} catch (\RuntimeException $e) {
			throw new \TYPO3\CMS\Core\Cache\Exception('The directory "' . $finalCacheDirectory . '" can not be created.', 1303669848, $e);
		}
		if (!is_writable($finalCacheDirectory)) {
			throw new \TYPO3\CMS\Core\Cache\Exception('The directory "' . $finalCacheDirectory . '" is not writable.', 1203965200);
		}
	}

	/**
	 * Returns the directory where the cache files are stored
	 *
	 * @return string Full path of the cache directory
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
	 * @throws \TYPO3\CMS\Core\Cache\Exception if the directory does not exist or is not writable or exceeds the maximum allowed path length, or if no cache frontend has been set.
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if the data to bes stored is not a string.
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!is_string($data)) {
			throw new \TYPO3\CMS\Core\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1334756734);
		}
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756735);
		}
		if ($entryIdentifier === '') {
			throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756736);
		}
		$temporaryCacheEntryPathAndFilename = $this->cacheDirectory . uniqid() . '.temp';
		$result = file_put_contents($temporaryCacheEntryPathAndFilename, $data);
		\TYPO3\CMS\Core\Utility\GeneralUtility::fixPermissions($temporaryCacheEntryPathAndFilename);
		if ($result === FALSE) {
			throw new \TYPO3\CMS\Core\Cache\Exception('The temporary cache file "' . $temporaryCacheEntryPathAndFilename . '" could not be written.', 1334756737);
		}
		$cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		rename($temporaryCacheEntryPathAndFilename, $cacheEntryPathAndFilename);
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @throws \InvalidArgumentException If identifier is invalid
	 * @api
	 */
	public function get($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756877);
		}
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if (!file_exists($pathAndFilename)) {
			return FALSE;
		}
		return file_get_contents($pathAndFilename);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function has($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756878);
		}
		return file_exists($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function remove($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756960);
		}
		if ($entryIdentifier === '') {
			throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1334756961);
		}
		try {
			unlink($this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension);
		} catch (\Exception $e) {
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::flushDirectory($this->cacheDirectory);
		$this->createFinalCacheDirectory($this->cacheDirectory);
	}

	/**
	 * Checks if the given cache entry files are still valid or if their
	 * lifetime has exceeded.
	 *
	 * @param string $cacheEntryPathAndFilename
	 * @return boolean
	 * @api
	 */
	protected function isCacheFileExpired($cacheEntryPathAndFilename) {
		return file_exists($cacheEntryPathAndFilename) === FALSE;
	}

	/**
	 * Not necessary
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {

	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 *
	 * @param string $entryIdentifier The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		return file_exists($pathAndFilename) ? array($pathAndFilename) : FALSE;
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @throws \InvalidArgumentException
	 * @api
	 */
	public function requireOnce($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073036);
		}
		return file_exists($pathAndFilename) ? require_once $pathAndFilename : FALSE;
	}

}


?>