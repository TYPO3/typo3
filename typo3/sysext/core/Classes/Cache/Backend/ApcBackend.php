<?php
namespace TYPO3\CMS\Core\Cache\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 * A caching backend which stores cache entries by using APC.
 *
 * This backend uses the following types of keys:
 * - tag_xxx
 * xxx is tag name, value is array of associated identifiers identifier. This
 * is "forward" tag index. It is mainly used for obtaining content by tag
 * (get identifier by tag -> get content by identifier)
 * - ident_xxx
 * xxx is identifier, value is array of associated tags. This is "reverse" tag
 * index. It provides quick access for all tags associated with this identifier
 * and used when removing the identifier
 *
 * Each key is prepended with a prefix. By default prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 * - "TYPO3"
 * - MD5 of path to TYPO3 and user running TYPO3
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * This file is a backport from FLOW3
 *
 * @author Robert Lemke <robert@typo3.org>
 * @author Karsten Dambekalns <karsten@typo3.org>
 * @author Christian Jul Jensen <julle@typo3.org>
 * @author Dmitry Dulepov <dmitry@typo3.org>
 * @api
 */
class ApcBackend extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend implements \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface {

	/**
	 * A prefix to seperate stored data from other data possible stored in the APC
	 *
	 * @var string
	 */
	protected $identifierPrefix;

	/**
	 * Set the cache identifier prefix.
	 *
	 * @param string $identifierPrefix
	 */
	protected function setIdentifierPrefix($identifierPrefix) {
		$this->identifierPrefix = $identifierPrefix;
	}

	/**
	 * Retrieves the cache identifier prefix.
	 *
	 * @return string
	 */
	protected function getIdentifierPrefix() {
		return $this->identifierPrefix;
	}

	/**
	 * Constructs this backend
	 *
	 * @param string $context FLOW3's application context
	 * @param array $options Configuration options - unused here
	 * @throws \TYPO3\CMS\Core\Cache\Exception
	 */
	public function __construct($context, array $options = array()) {
		if (!extension_loaded('apc')) {
			throw new \TYPO3\CMS\Core\Cache\Exception('The PHP extension "apc" or "apcu" must be installed and loaded in order to use the APC backend.', 1232985414);
		}
		parent::__construct($context, $options);
	}

	/**
	 * Initializes the identifier prefix when setting the cache.
	 *
	 * @param \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache
	 * @return void
	 */
	public function setCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache) {
		parent::setCache($cache);
		$processUser = $this->getCurrentUserData();
		$pathHash = \TYPO3\CMS\Core\Utility\GeneralUtility::shortMD5($this->getPathSite() . $processUser['name'] . $this->context . $cache->getIdentifier(), 12);
		$this->setIdentifierPrefix('TYPO3_' . $pathHash);
	}

	/**
	 * Returns the current user data with posix_getpwuid or a default structure when
	 * posix_getpwuid is not available.
	 *
	 * @return array
	 */
	protected function getCurrentUserData() {
		return extension_loaded('posix') ? posix_getpwuid(posix_geteuid()) : array('name' => 'default');
	}

	/**
	 * Returns the PATH_site constant.
	 *
	 * @return string
	 */
	protected function getPathSite() {
		return PATH_site;
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if $data is not a string
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!$this->cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
			throw new \TYPO3\CMS\Core\Cache\Exception('No cache frontend has been set yet via setCache().', 1232986818);
		}
		if (!is_string($data)) {
			throw new \TYPO3\CMS\Core\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1232986825);
		}
		$tags[] = '%APCBE%' . $this->cacheIdentifier;
		$expiration = $lifetime !== NULL ? $lifetime : $this->defaultLifetime;
		$success = apc_store($this->getIdentifierPrefix() . $entryIdentifier, $data, $expiration);
		if ($success === TRUE) {
			$this->removeIdentifierFromAllTags($entryIdentifier);
			$this->addIdentifierToTags($entryIdentifier, $tags);
		} else {
			throw new \TYPO3\CMS\Core\Cache\Exception('Could not set value.', 1232986877);
		}
	}

	/**
	 * Loads data from the cache.
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @api
	 */
	public function get($entryIdentifier) {
		$success = FALSE;
		$value = apc_fetch($this->getIdentifierPrefix() . $entryIdentifier, $success);
		return $success ? $value : $success;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @api
	 */
	public function has($entryIdentifier) {
		$success = FALSE;
		apc_fetch($this->getIdentifierPrefix() . $entryIdentifier, $success);
		return $success;
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @api
	 */
	public function remove($entryIdentifier) {
		$this->removeIdentifierFromAllTags($entryIdentifier);
		return apc_delete($this->getIdentifierPrefix() . $entryIdentifier);
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 * @api
	 */
	public function findIdentifiersByTag($tag) {
		$success = FALSE;
		$identifiers = apc_fetch($this->getIdentifierPrefix() . 'tag_' . $tag, $success);
		if ($success === FALSE) {
			return array();
		} else {
			return (array) $identifiers;
		}
	}

	/**
	 * Finds all tags for the given identifier. This function uses reverse tag
	 * index to search for tags.
	 *
	 * @param string $identifier Identifier to find tags by
	 * @return array Array with tags
	 */
	protected function findTagsByIdentifier($identifier) {
		$success = FALSE;
		$tags = apc_fetch($this->getIdentifierPrefix() . 'ident_' . $identifier, $success);
		return $success ? (array) $tags : array();
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception
	 * @api
	 */
	public function flush() {
		if (!$this->cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
			throw new \TYPO3\CMS\Core\Cache\Exception('Yet no cache frontend has been set via setCache().', 1232986971);
		}
		$this->flushByTag('%APCBE%' . $this->cacheIdentifier);
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @api
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersByTag($tag);
		foreach ($identifiers as $identifier) {
			$this->remove($identifier);
		}
	}

	/**
	 * Associates the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @param array $tags
	 * @return void
	 */
	protected function addIdentifierToTags($entryIdentifier, array $tags) {
		foreach ($tags as $tag) {
			// Update tag-to-identifier index
			$identifiers = $this->findIdentifiersByTag($tag);
			if (array_search($entryIdentifier, $identifiers) === FALSE) {
				$identifiers[] = $entryIdentifier;
				apc_store($this->getIdentifierPrefix() . 'tag_' . $tag, $identifiers);
			}
			// Update identifier-to-tag index
			$existingTags = $this->findTagsByIdentifier($entryIdentifier);
			if (array_search($entryIdentifier, $existingTags) === FALSE) {
				apc_store($this->getIdentifierPrefix() . 'ident_' . $entryIdentifier, array_merge($existingTags, $tags));
			}
		}
	}

	/**
	 * Removes association of the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @return void
	 */
	protected function removeIdentifierFromAllTags($entryIdentifier) {
		// Get tags for this identifier
		$tags = $this->findTagsByIdentifier($entryIdentifier);
		// Deassociate tags with this identifier
		foreach ($tags as $tag) {
			$identifiers = $this->findIdentifiersByTag($tag);
			// Formally array_search() below should never return FALSE due to
			// the behavior of findTagsByIdentifier(). But if reverse index is
			// corrupted, we still can get 'FALSE' from array_search(). This is
			// not a problem because we are removing this identifier from
			// anywhere.
			if (($key = array_search($entryIdentifier, $identifiers)) !== FALSE) {
				unset($identifiers[$key]);
				if (count($identifiers)) {
					apc_store($this->getIdentifierPrefix() . 'tag_' . $tag, $identifiers);
				} else {
					apc_delete($this->getIdentifierPrefix() . 'tag_' . $tag);
				}
			}
		}
		// Clear reverse tag index for this identifier
		apc_delete($this->getIdentifierPrefix() . 'ident_' . $entryIdentifier);
	}

	/**
	 * Does nothing, as APC does GC itself
	 *
	 * @return void
	 */
	public function collectGarbage() {

	}

}


?>