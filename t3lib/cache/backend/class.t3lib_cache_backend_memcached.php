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
 * A caching backend which stores cache entries by using Memcached.
 *
 * This backend uses the following types of Memcache keys:
 * - tag_xxx
 *   xxx is tag name, value is array of associated identifiers identifier. This
 *   is "forward" tag index. It is mainly used for obtaining content by tag
 *   (get identifier by tag -> get content by identifier)
 * - ident_xxx
 *   xxx is identifier, value is array of associated tags. This is "reverse" tag
 *   index. It provides quick access for all tags associated with this identifier
 *   and used when removing the identifier
 * - tagIndex
 *   Value is a List of all tags (array)
 * Each key is prepended with a prefix. By default prefix consists from two parts
 * separated by underscore character and ends in yet another underscore character:
 * - "TYPO3"
 * - Current site path obtained from the PATH_site constant
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * This file is a backport from FLOW3 by Ingo Renner.
 *
 * @package TYPO3
 * @subpackage t3lib_cache
 * @version $Id$
 */
class t3lib_cache_backend_Memcached extends t3lib_cache_AbstractBackend {

	/**
	 * Instance of the PHP Memcache class
	 *
	 * @var Memcache
	 */
	protected $memcache;

	/**
	 * Array of Memcache server configurations
	 *
	 * @var array
	 */
	protected $servers = array();

	/**
	 * Indicates whether the memcache uses compression or not (requires zlib)
	 *
	 * @var boolean
	 */
	protected $useCompressed;

	/**
	 * A prefix to seperate stored data from other data possibly stored in the
	 * memcache. This prefix must be unique for each site in the tree. Default
	 * implementation uses MD5 of the current site path to make identifier prefix
	 * unique.
	 *
	 * @var	string
	 */
	protected $identifierPrefix;

	/**
	 * Indicates whther the server is connected
	 *
	 * @var	boolean
	 */
	protected $serverConnected = false;

	/**
	 * Constructs this backend
	 *
	 * @param mixed $options Configuration options - depends on the actual backend
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($options = array()) {
		if (!extension_loaded('memcache')) {
			throw new t3lib_cache_Exception(
				'The PHP extension "memcached" must be installed and loaded in ' .
				'order to use the Memcached backend.',
				1213987706
			);
		}

		parent::__construct($options);

		$this->memcache = new Memcache();
		$this->identifierPrefix = $this->getIdentifierPrefix();

		if (!count($this->servers)) {
			throw new t3lib_cache_Exception(
				'No servers were given to Memcache',
				1213115903
			);
		}

		foreach ($this->servers as $serverConf) {
			if (substr($serverConf, 0, 7) == 'unix://') {
				$host = $serverConf;
				$port = 0;
			} else {
				list($host, $port) = explode(':', $serverConf, 2);
			}

			if ($this->serverConnected) {
				$this->memcache->addserver($host, $port);
			} else {
					// pconnect throws PHP warnings when it cannot connect!
				$this->serverConnected = @$this->memcache->pconnect($host, $port);
			}
		}

		if (!$this->serverConnected) {
			t3lib_div::sysLog('Unable to connect to any Memcached server', 'core', 3);
		}
	}

	/**
	 * Sets servers property.
	 *
	 * @param	array	An array of servers to add (format: "host:port")
	 * @return	void
	 * @author	Christian Jul Jensen <julle@typo3.org>
	 */
	protected function setServers(array $servers) {
		$this->servers = $servers;
	}

	/**
	 * Sets for compression flag
	 *
	 * @param	boolean	$enableCompression	New value of compression flag
	 * @return	void
	 * @author	Christian Jul Jensen <julle@typo3.org>
	 */
	protected function setCompression($enableCompression) {
		$this->useCompressed = $enableCompression;
	}

	/**
	 * Saves data in the cache.
	 *
	 * @param string An identifier for this specific cache entry
	 * @param string The data to be stored
	 * @param array Tags to associate with this cache entry
	 * @param integer Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
	 * @return void
	 * @throws t3lib_cache_Exception if no cache frontend has been set.
	 * @throws InvalidArgumentException if the identifier is not valid
	 * @throws t3lib_cache_exception_InvalidData if $data is not a string
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 **/
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!$this->serverConnected) {
			return;
		}

		if (!self::isValidEntryIdentifier($entryIdentifier)) {
			throw new InvalidArgumentException(
				'"' . $entryIdentifier . '" is not a valid cache entry identifier.',
				1207149191
			);
		}

		if (!$this->cache instanceof t3lib_cache_AbstractCache) {
			throw new t3lib_cache_Exception(
				'No cache frontend has been set yet via setCache().',
				1207149215
			);
		}

		if (!is_string($data)) {
			throw new t3lib_cache_Exception_InvalidData(
				'The specified data is of type "' . gettype($data) .
				'" but a string is expected.',
				1207149231
			);
		}

		foreach($tags as $tag) {
			if (!self::isValidTag($tag)) {
				throw new InvalidArgumentException(
					'"' . $tag . '" is not a valid tag.',
					1213120275
				);
			}
		}

		$expiration = $lifetime ? $lifetime : $this->defaultLifetime;
		try {
			$this->remove($entryIdentifier);
			$success = $this->memcache->set(
				$this->identifierPrefix . $entryIdentifier,
				$data,
				$this->useCompressed,
				$expiration
			);

			if (!$success) {
				throw new t3lib_cache_Exception(
					'Memcache was unable to connect to any server.',
					1207165277
				);
			}

			$this->addTagsToTagIndex($tags);
			$this->addIdentifierToTags($entryIdentifier, $tags);
		} catch(Exception $exception) {
			throw new t3lib_cache_Exception(
				'Memcache was unable to connect to any server. ' .
				$exception->getMessage(),
				1207208100
			);
		}
	}

	/**
	 * Loads data from the cache.
	 *
	 * @param string An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function get($entryIdentifier) {
		return !$this->serverConnected ? false : $this->memcache->get($this->identifierPrefix . $entryIdentifier);
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * @param string An identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function has($entryIdentifier) {
		return $this->serverConnected && $this->memcache->get($this->identifierPrefix . $entryIdentifier) !== false;
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 * Usually this only affects one entry but if - for what reason ever -
	 * old entries for the identifier still exist, they are removed as well.
	 *
	 * @param string Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @author Christian Jul Jensen <julle@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function remove($entryIdentifier) {
		$result = false;

		if ($this->serverConnected) {
			$this->removeIdentifierFromAllTags($entryIdentifier);
			$this->memcache->delete($this->identifierPrefix . 'ident_' . $entryIdentifier);
			$result = $this->memcache->delete($this->identifierPrefix . $entryIdentifier);
		}

		return $result;
	}

	/**
	 * Finds and returns all cache entries which are tagged by the specified tag.
	 * The asterisk ("*") is allowed as a wildcard at the beginning and the end of
	 * the tag.
	 *
	 * @param string The tag to search for, the "*" wildcard is supported
	 * @return array An array of entries with all matching entries. An empty array if no entries matched
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findEntriesByTag($tag) {
		if (!self::isValidTag($tag)) {
			throw new InvalidArgumentException(
				'"' . $tag . '" is not a valid tag.',
				1213120307
			);
		}

		$entries = array();
		$identifiers = $this->findIdentifiersTaggedWith($tag);
		foreach($identifiers as $identifier) {
			$entries[] = $this->get($identifier);
		}

		return $entries;
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
	 * Beware that this flushes the complete memcached, not only the cache
	 * entries we stored there. We do this because:
	 *  it is expensive to keep track of all identifiers we put there
	 *  memcache is a cache, you should never rely on things being there
	 *
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flush() {
		if ($this->serverConnected) {
			$this->memcache->flush();
		}
	}

	/**
	 * Removes all cache entries of this cache which are tagged by the specified tag.
	 *
	 * @param string $tag The tag the entries must have
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function flushByTag($tag) {
		$identifiers = $this->findIdentifiersTaggedWith($tag);
		foreach($identifiers as $identifier) {
			$this->remove($identifier);
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
	 * Returns an array with all known tags
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function getTagIndex() {
		$tagIndex = $this->memcache->get($this->identifierPrefix . 'tagIndex');
		return ($tagIndex == false ? array() : (array)$tagIndex);
	}

	/**
	 * Saves the tags known to the backend
	 *
	 * @param array Array of tags
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function setTagIndex(array $tags) {
		$this->memcache->set($this->identifierPrefix . 'tagIndex', array_unique($tags), 0, 0);
	}

	/**
	 * Adds the given tags to the tag index
	 *
	 * @param array Array of tags
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function addTagsToTagIndex(array $tags) {
		if(count($tags)) {
			$this->setTagIndex(array_merge($tags, $this->getTagIndex()));
		}
	}

	/**
	 * Removes the given tags from the tag index
	 *
	 * @param array $tags
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	protected function removeTagsFromTagIndex(array $tags) {
		if(count($tags)) {
			$this->setTagIndex(array_diff($this->getTagIndex(), $tags));
		}
	}

	/**
	 * Associates the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @param array Array of tags
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author	Dmitry Dulepov
	 */
	protected function addIdentifierToTags($entryIdentifier, array $tags) {
		if ($this->serverConnected) {
			foreach($tags as $tag) {
					// Update tag-to-identifier index
				$identifiers = $this->findIdentifiersTaggedWith($tag);
				if (array_search($entryIdentifier, $identifiers) === false) {
					$identifiers[] = $entryIdentifier;
					$this->memcache->set($this->identifierPrefix . 'tag_' . $tag,
						$identifiers);
				}

					// Update identifier-to-tag index
				$existingTags = $this->findTagsForIdentifier($entryIdentifier);
				if (array_search($entryIdentifier, $existingTags) === false) {
					$this->memcache->set($this->identifierPrefix . 'ident_' . $entryIdentifier,
						array_merge($existingTags, $tags));
				}
			}
		}
	}

	/**
	 * Removes association of the identifier with the given tags
	 *
	 * @param string $entryIdentifier
	 * @param array Array of tags
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @author	Dmitry Dulepov
	 */
	protected function removeIdentifierFromAllTags($entryIdentifier) {
		if ($this->serverConnected) {
				// Get tags for this identifier
			$tags = $this->findTagsForIdentifier($entryIdentifier);
				// Deassociate tags with this identifier
			foreach ($tags as $tag) {
				$identifiers = $this->findIdentifiersTaggedWith($tag);
					// Formally array_search() below should never return false
					// due to the behavior of findTagsForIdentifier(). But if
					// reverse index is corrupted, we still can get 'false' from
					// array_search(). This is not a problem because we are
					// removing this identifier from anywhere.
				if (($key = array_search($entryIdentifier, $identifiers)) !== false) {
					unset($identifiers[$key]);

					if(count($identifiers)) {
						$this->memcache->set(
							$this->identifierPrefix . 'tag_' . $tag,
							$identifiers
						);
					} else {
						$this->removeTagsFromTagIndex(array($tag));
						$this->memcache->delete($this->identifierPrefix . 'tag_' . $tag);
					}
				}
			}

				// Clear reverse tag index for this identifier
			$this->memcache->delete($this->identifierPrefix . 'ident_' . $entryIdentifier);
		}
	}

	/**
	 * Returns all identifiers associated with $tag
	 *
	 * @param string $tag
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function findIdentifiersTaggedWith($tag) {
		$identifiers = !$this->serverConnected ? false : $this->memcache->get($this->identifierPrefix . 'tag_' . $tag);
		return ($identifiers === false ? array() : $identifiers);
	}

	/**
	 * Finds all tags for the given identifier. This function uses reverse tag
	 * index to search for tags.
	 *
	 * @param	string	$identifier	Identifier to search tags for
	 * @return	array	Array with tags
	 */
	protected function findTagsForIdentifier($identifier) {
		$tags = !$this->serverConnected ? false : $this->memcache->get($this->identifierPrefix . 'ident_' . $identifier);
		return ($tags == false ? array() : (array)$tags);
	}

	/**
	 * Returns idenfier prefix. Extensions can override this function to provide
	 * another identifier prefix if it is necessary for special purposes.
	 * Default identifier prefix is based on PATH_site only. In most cases
	 * it is enough because different installations use different paths and page
	 * IDs in the same installation never repeat.
	 *
	 * @return	string	Identifier prefix, ending with underscore
	 * @author	Dmitry Dulepov
	 */
	protected function getIdentifierPrefix() {
		return 'TYPO3_' . md5(PATH_site) . '_';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_memcached.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/cache/backend/class.t3lib_cache_backend_memcached.php']);
}

?>