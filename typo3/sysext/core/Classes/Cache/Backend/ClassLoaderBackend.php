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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * A caching backend customized explicitly for the class loader.
 * This backend is not public API
 *
 * @internal
 */
class ClassLoaderBackend extends SimpleFileBackend {

	/**
	 * @param string $entryIdentifier
	 * @param string $filePath
	 * @internal This is not an API method
	 */
	public function setLinkToPhpFile($entryIdentifier, $filePath) {
		if (!file_exists($filePath)) {
			throw new \InvalidArgumentException('The specified file path must exist.', 1364205235);
		}
		if (strtolower(substr($filePath, -3)) !== 'php') {
			throw new \InvalidArgumentException('The specified file must be a php file.', 1364205377);
		}
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1364205166);
		}
		if ($entryIdentifier === '') {
			throw new \InvalidArgumentException('The specified entry identifier must not be empty.', 1364205170);
		}
		$cacheEntryPathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if ($filePath[0] === '/' && \TYPO3\CMS\Core\Utility\GeneralUtility::isAllowedAbsPath($filePath)) {
			// Make relative if absolute to prevent wrong entries if the whole installation is moved or copied
			$filePath = \TYPO3\CMS\Core\Utility\PathUtility::getRelativePath($this->cacheDirectory, dirname($filePath)) . basename($filePath);
		}
		if (@!symlink($filePath, $cacheEntryPathAndFilename)) {
			if ($filePath[0] === '/') {
				$this->set($entryIdentifier, '<?php require \'' . $filePath . '\';');
			} else {
				$this->set($entryIdentifier, '<?php require __DIR__ . \'/' . $filePath . '\';');
			}
		}
	}

	/**
	 * @param string $entryIdentifier
	 * @param string $otherEntryIdentifier
	 * @internal
	 */
	public function setLinkToOtherCacheEntry($entryIdentifier, $otherEntryIdentifier) {
		$otherCacheEntryPathAndFilename = $this->cacheDirectory . $otherEntryIdentifier . $this->cacheEntryFileExtension;
		$this->setLinkToPhpFile($entryIdentifier, $otherCacheEntryPathAndFilename);
	}

	/**
	 * Loads data from a cache file.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @throws \InvalidArgumentException If identifier is invalid
	 * @internal
	 */
	public function get($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756877);
		}
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if (!file_exists($pathAndFilename) && !is_link($pathAndFilename)) {
			return FALSE;
		}
		return file_get_contents($pathAndFilename);
	}

	/**
	 * Retrieves the target of the a linked cache entry
	 *
	 * @param string $entryIdentifier
	 * @return bool|string
	 * @internal
	 */
	public function getTargetOfLinkedCacheEntry($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if (is_link($pathAndFilename)) {
			return readlink($pathAndFilename);
		} elseif (is_file($pathAndFilename)) {
			// If not a link
			$fileContent = file_get_contents($pathAndFilename);
			$pattern = "!^\<\?php require ((__DIR__) \. )?'([\/\.\_a-z0-9]+)';!i";
			$matches = array();
			if (preg_match($pattern, $fileContent, $matches) !== FALSE) {
				if (!empty($matches[3])) {
					$targetPath = $matches[3];
					if (!empty($matches[2]) && $matches[2] == '__DIR__') {
						$targetPath = dirname($pathAndFilename) . $targetPath;
					}
					return \TYPO3\CMS\Core\Utility\PathUtility::getRelativePath($this->cacheDirectory, dirname($targetPath)) . basename($targetPath);
				}
			}
		}
		return FALSE;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @throws \InvalidArgumentException
	 * @internal
	 */
	public function has($entryIdentifier) {
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1334756878);
		}
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		return file_exists($pathAndFilename) || is_link($pathAndFilename);
	}

	/**
	 * Checks if the given cache entry files are still valid or if their
	 * lifetime has exceeded.
	 *
	 * @param string $cacheEntryPathAndFilename
	 * @return boolean
	 * @internal
	 */
	protected function isCacheFileExpired($cacheEntryPathAndFilename) {
		return file_exists($cacheEntryPathAndFilename) === FALSE && is_link($cacheEntryPathAndFilename) === FALSE;
	}

	/**
	 * Tries to find the cache entry for the specified identifier.
	 *
	 * @param string $entryIdentifier The cache entry identifier
	 * @return mixed The file names (including path) as an array if one or more entries could be found, otherwise FALSE
	 */
	protected function findCacheFilesByIdentifier($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		return (file_exists($pathAndFilename) || is_link($pathAndFilename)) ? array($pathAndFilename) : FALSE;
	}

	/**
	 * Loads PHP code from the cache and require_onces it right away.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed Potential return value from the include operation
	 * @throws \InvalidArgumentException
	 * @internal
	 */
	public function requireOnce($entryIdentifier) {
		$pathAndFilename = $this->cacheDirectory . $entryIdentifier . $this->cacheEntryFileExtension;
		if ($entryIdentifier !== basename($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified entry identifier must not contain a path segment.', 1282073036);
		}
		return (file_exists($pathAndFilename) || is_link($pathAndFilename)) ? require_once $pathAndFilename : FALSE;
	}

}


?>