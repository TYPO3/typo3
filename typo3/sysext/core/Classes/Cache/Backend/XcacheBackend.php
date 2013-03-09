<?php
namespace TYPO3\CMS\Core\Cache\Backend;
use TYPO3\CMS\Core\Cache\Exception;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Gampe <philipp.gampe@typo3.org>
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
 * A caching backend which stores cache entries by using xcache opcode cache.
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
 * - MD5 of script path and filename and SAPI name
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * @author Philipp Gampe <philipp.gampe@typo3.org>
 */
class XcacheBackend extends AbstractBackend implements TaggableBackendInterface {

	/**
	 * A prefix to separate stored data from other data possible stored in the xcache
	 *
	 * @var string
	 */
	protected $identifierPrefix;

	/**
	 * Constructs this backend
	 *
	 * @param string $context FLOW3's application context
	 * @param array $options Configuration options
	 * @throws \TYPO3\CMS\Core\Cache\Exception If xcache PHP extension is not loaded
	 */
	public function __construct($context, array $options = array()) {
		if (!extension_loaded('xcache')) {
			throw new Exception(
				'The PHP extension "xcache" must be installed and loaded in order to use the xcache backend.',
				1363116592
			);
		}
		parent::__construct($context, $options);
	}

	/**
	 * Saves data in the cache
	 *
	 * @param string $entryIdentifier An identifier for this specific cache entry
	 * @param string $data The data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if $data is not a string
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if ($this->runningFromCliOrWrongConfiguration()) {
			return;
		}
		if (!$this->cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
			throw new Exception(
				'No cache frontend has been set yet via setCache().',
				1363117491
			);
		}
		if (!is_string($data)) {
			throw new Exception\InvalidDataException(
				'The specified data is of type "' . gettype($data) . '" but a string is expected.',
				1363117435
			);
		}
		$tags[] = '%XCBE%' . $this->cache->getIdentifier();
		$expiration = $lifetime !== NULL ? $lifetime : $this->defaultLifetime;
		$success = xcache_set($this->identifierPrefix . $entryIdentifier, $data, $expiration);
		if ($success === TRUE) {
			$this->removeIdentifierFromAllTags($entryIdentifier);
			$this->addIdentifierToTags($entryIdentifier, $tags);
		} else {
			throw new Exception(
				'Could not set value.',
				1363117507
			);
		}
	}

	/**
	 * Loads data from the cache
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 */
	public function get($entryIdentifier) {
		if ($this->runningFromCliOrWrongConfiguration()) {
			return FALSE;
		}
		$value = xcache_get($this->identifierPrefix . $entryIdentifier);
		return $value ?: FALSE;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists
	 *
	 * @param string $entryIdentifier An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 */
	public function has($entryIdentifier) {
		if ($this->runningFromCliOrWrongConfiguration()) {
			return FALSE;
		}
		return xcache_isset($this->identifierPrefix . $entryIdentifier);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 */
	public function remove($entryIdentifier) {
		if ($this->runningFromCliOrWrongConfiguration()) {
			return FALSE;
		}
		$this->removeIdentifierFromAllTags($entryIdentifier);
		return xcache_unset($this->identifierPrefix . $entryIdentifier);
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * @param string $tag The tag to search for
	 * @return array An array with identifiers of all matching entries. An empty array if no entries matched
	 */
	public function findIdentifiersByTag($tag) {
		if ($this->runningFromCliOrWrongConfiguration()) {
			return array();
		}
		$identifiers = xcache_get($this->identifierPrefix . 'tag_' . $tag);
		return $identifiers ?: array();
	}

	/**
	 * Finds all tags for the given identifier. This function uses reverse tag
	 * index to search for tags.
	 *
	 * @param string $identifier Identifier to find tags by
	 * @return array Array with tags
	 */
	protected function findTagsByIdentifier($identifier) {
		if ($this->runningFromCliOrWrongConfiguration()) {
			return array();
		}
		$tags = xcache_get($this->identifierPrefix . 'ident_' . $identifier);
		return $tags ?: array();
	}

	/**
	 * Removes all cache entries of this cache
	 *
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception
	 */
	public function flush() {
		if ($this->runningFromCliOrWrongConfiguration()) {
			return;
		}
		if (!$this->cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
			throw new Exception(
				'Yet no cache frontend has been set via setCache().',
				1363117531
			);
		}
		$this->flushByTag('%XCBE%' . $this->cache->getIdentifier());
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified
	 * tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
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
		if ($this->runningFromCliOrWrongConfiguration()) {
			return;
		}
		foreach ($tags as $tag) {
			// Update tag-to-identifier index
			$identifiers = $this->findIdentifiersByTag($tag);
			if (array_search($entryIdentifier, $identifiers) === FALSE) {
				$identifiers[] = $entryIdentifier;
				xcache_set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
			}
			// Update identifier-to-tag index
			$existingTags = $this->findTagsByIdentifier($entryIdentifier);
			if (array_search($entryIdentifier, $existingTags) === FALSE) {
				xcache_set($this->identifierPrefix . 'ident_' . $entryIdentifier, array_merge($existingTags, $tags));
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
		if ($this->runningFromCliOrWrongConfiguration()) {
			return;
		}
		// Get tags for this identifier
		$tags = $this->findTagsByIdentifier($entryIdentifier);
		// Disassociate tags with this identifier
		foreach ($tags as $tag) {
			$identifiers = $this->findIdentifiersByTag($tag);
			// Formally array_search() below should never return false due to
			// the behavior of findTagsByIdentifier(). But if reverse index is
			// corrupted, we still can get 'false' from array_search(). This is
			// not a problem because we are removing this identifier from
			// anywhere.
			if (($key = array_search($entryIdentifier, $identifiers)) !== FALSE) {
				unset($identifiers[$key]);
				if (count($identifiers)) {
					xcache_set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
				} else {
					xcache_unset($this->identifierPrefix . 'tag_' . $tag);
				}
			}
		}
		// Clear reverse tag index for this identifier
		xcache_unset($this->identifierPrefix . 'ident_' . $entryIdentifier);
	}

	/**
	 * Does nothing, as xcache does GC itself
	 *
	 * @return void
	 */
	public function collectGarbage() {
	}

	/**
	 * Checks if backend is called from CLI context.
	 * In this case all methods fail silently as xcache user cache is not available in CLI context.
	 * xcache.var_size cat be zero or empty if in CLI mode, or if not correctly configured.
	 *
	 * @return bool TRUE if misconfigured or in CLI mode
	 */
	protected function runningFromCliOrWrongConfiguration() {
		$varSize = ini_get('xcache.var_size');
		return php_sapi_name() === 'cli' || empty($varSize);
	}
}

?>