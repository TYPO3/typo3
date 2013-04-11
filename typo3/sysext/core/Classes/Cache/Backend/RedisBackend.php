<?php
namespace TYPO3\CMS\Core\Cache\Backend;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * A caching backend which stores cache entries by using Redis with phpredis
 * PHP module. Redis is a noSQL database with very good scaling characteristics
 * in proportion to the amount of entries and data size.
 *
 * @see http://code.google.com/p/redis/
 * @see http://github.com/owlient/phpredis
 * @author Christopher Hlubek <hlubek@networkteam.com>
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @api
 */
class RedisBackend extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend implements \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface {

	/**
	 * Faked unlimited lifetime = 31536000 (1 Year).
	 * In redis an entry does not have a lifetime by default (it's not "volatile").
	 * Entries can be made volatile either with EXPIRE after it has been SET,
	 * or with SETEX, which is a combined SET and EXPIRE command.
	 * But an entry can not be made "unvolatile" again. To set a volatile entry to
	 * not volatile again, it must be DELeted and SET without a following EXPIRE.
	 * To save these additional calls on every set(),
	 * we just make every entry volatile and treat a high number as "unlimited"
	 *
	 * @see http://code.google.com/p/redis/wiki/ExpireCommand
	 * @var integer Faked unlimited lifetime
	 */
	const FAKED_UNLIMITED_LIFETIME = 31536000;
	/**
	 * Key prefix for identifier->data entries
	 *
	 * @var string
	 */
	const IDENTIFIER_DATA_PREFIX = 'identData:';
	/**
	 * Key prefix for identifier->tags sets
	 *
	 * @var string
	 */
	const IDENTIFIER_TAGS_PREFIX = 'identTags:';
	/**
	 * Key prefix for tag->identifiers sets
	 *
	 * @var string
	 */
	const TAG_IDENTIFIERS_PREFIX = 'tagIdents:';
	/**
	 * Instance of the PHP redis class
	 *
	 * @var \Redis
	 */
	protected $redis;

	/**
	 * Indicates whether the server is connected
	 *
	 * @var boolean
	 */
	protected $connected = FALSE;

	/**
	 * Hostname / IP of the Redis server, defaults to 127.0.0.1.
	 *
	 * @var string
	 */
	protected $hostname = '127.0.0.1';

	/**
	 * Port of the Redis server, defaults to 6379
	 *
	 * @var integer
	 */
	protected $port = 6379;

	/**
	 * Number of selected database, defaults to 0
	 *
	 * @var integer
	 */
	protected $database = 0;

	/**
	 * Password for redis authentication
	 *
	 * @var string
	 */
	protected $password = '';

	/**
	 * Indicates whether data is compressed or not (requires php zlib)
	 *
	 * @var boolean
	 */
	protected $compression = FALSE;

	/**
	 * -1 to 9, indicates zlib compression level: -1 = default level 6, 0 = no compression, 9 maximum compression
	 *
	 * @var integer
	 */
	protected $compressionLevel = -1;

	/**
	 * Construct this backend
	 *
	 * @param string $context FLOW3's application context
	 * @param array $options Configuration options
	 * @throws \TYPO3\CMS\Core\Cache\Exception if php redis module is not loaded
	 */
	public function __construct($context, array $options = array()) {
		if (!extension_loaded('redis')) {
			throw new \TYPO3\CMS\Core\Cache\Exception('The PHP extension "redis" must be installed and loaded in order to use the redis backend.', 1279462933);
		}
		parent::__construct($context, $options);
	}

	/**
	 * Initializes the redis backend
	 *
	 * @return void
	 * @throws \TYPO3\CMS\Core\Cache\Exception if access to redis with password is denied or if database selection fails
	 */
	public function initializeObject() {
		$this->redis = new \Redis();
		try {
			$this->connected = $this->redis->connect($this->hostname, $this->port);
		} catch (\Exception $e) {
			\TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Could not connect to redis server.', 'core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_ERROR);
		}
		if ($this->connected) {
			if (strlen($this->password)) {
				$success = $this->redis->auth($this->password);
				if (!$success) {
					throw new \TYPO3\CMS\Core\Cache\Exception('The given password was not accepted by the redis server.', 1279765134);
				}
			}
			if ($this->database > 0) {
				$success = $this->redis->select($this->database);
				if (!$success) {
					throw new \TYPO3\CMS\Core\Cache\Exception('The given database "' . $this->database . '" could not be selected.', 1279765144);
				}
			}
		}
	}

	/**
	 * Setter for server hostname
	 *
	 * @param string $hostname Hostname
	 * @return void
	 * @api
	 */
	public function setHostname($hostname) {
		$this->hostname = $hostname;
	}

	/**
	 * Setter for server port
	 *
	 * @param integer $port Port
	 * @return void
	 * @api
	 */
	public function setPort($port) {
		$this->port = $port;
	}

	/**
	 * Setter for database number
	 *
	 * @param integer $database Database
	 * @return void
	 * @throws \InvalidArgumentException if database number is not valid
	 * @api
	 */
	public function setDatabase($database) {
		if (!is_integer($database)) {
			throw new \InvalidArgumentException('The specified database number is of type "' . gettype($database) . '" but an integer is expected.', 1279763057);
		}
		if ($database < 0) {
			throw new \InvalidArgumentException('The specified database "' . $database . '" must be greater or equal than zero.', 1279763534);
		}
		$this->database = $database;
	}

	/**
	 * Setter for authentication password
	 *
	 * @param string $password Password
	 * @return void
	 * @api
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Enable data compression
	 *
	 * @param boolean $compression TRUE to enable compression
	 * @return void
	 * @throws \InvalidArgumentException if compression parameter is not of type boolean
	 * @api
	 */
	public function setCompression($compression) {
		if (!is_bool($compression)) {
			throw new \InvalidArgumentException('The specified compression of type "' . gettype($compression) . '" but a boolean is expected.', 1289679153);
		}
		$this->compression = $compression;
	}

	/**
	 * Set data compression level.
	 * If compression is enabled and this is not set,
	 * gzcompress default level will be used.
	 *
	 * @param integer $compressionLevel -1 to 9: Compression level
	 * @return void
	 * @throws \InvalidArgumentException if compressionLevel parameter is not within allowed bounds
	 * @api
	 */
	public function setCompressionLevel($compressionLevel) {
		if (!is_integer($compressionLevel)) {
			throw new \InvalidArgumentException('The specified compression of type "' . gettype($compressionLevel) . '" but an integer is expected.', 1289679154);
		}
		if ($compressionLevel >= -1 && $compressionLevel <= 9) {
			$this->compressionLevel = $compressionLevel;
		} else {
			throw new \InvalidArgumentException('The specified compression level must be an integer between -1 and 9.', 1289679155);
		}
	}

	/**
	 * Save data in the cache
	 *
	 * Scales O(1) with number of cache entries
	 * Scales O(n) with number of tags
	 *
	 * @param string $entryIdentifier Identifier for this specific cache entry
	 * @param string $data Data to be stored
	 * @param array $tags Tags to associate with this cache entry
	 * @param integer $lifetime Lifetime of this cache entry in seconds. If NULL is specified, default lifetime is used. "0" means unlimited lifetime.
	 * @return void
	 * @throws \InvalidArgumentException if identifier is not valid
	 * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if data is not a string
	 * @api
	 */
	public function set($entryIdentifier, $data, array $tags = array(), $lifetime = NULL) {
		if (!is_string($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified identifier is of type "' . gettype($entryIdentifier) . '" but a string is expected.', 1279470252);
		}
		if (!is_string($data)) {
			throw new \TYPO3\CMS\Core\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1279469941);
		}
		$lifetime = $lifetime === NULL ? $this->defaultLifetime : $lifetime;
		if (!is_integer($lifetime)) {
			throw new \InvalidArgumentException('The specified lifetime is of type "' . gettype($lifetime) . '" but an integer or NULL is expected.', 1279488008);
		}
		if ($lifetime < 0) {
			throw new \InvalidArgumentException('The specified lifetime "' . $lifetime . '" must be greater or equal than zero.', 1279487573);
		}
		if ($this->connected) {
			$expiration = $lifetime === 0 ? self::FAKED_UNLIMITED_LIFETIME : $lifetime;
			if ($this->compression) {
				$data = gzcompress($data, $this->compressionLevel);
			}
			$this->redis->setex(self::IDENTIFIER_DATA_PREFIX . $entryIdentifier, $expiration, $data);
			$addTags = $tags;
			$removeTags = array();
			$existingTags = $this->redis->sMembers(self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier);
			if (!empty($existingTags)) {
				$addTags = array_diff($tags, $existingTags);
				$removeTags = array_diff($existingTags, $tags);
			}
			if (count($removeTags) > 0 || count($addTags) > 0) {
				$queue = $this->redis->multi(\Redis::PIPELINE);
				foreach ($removeTags as $tag) {
					$queue->sRemove(self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier, $tag);
					$queue->sRemove(self::TAG_IDENTIFIERS_PREFIX . $tag, $entryIdentifier);
				}
				foreach ($addTags as $tag) {
					$queue->sAdd(self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier, $tag);
					$queue->sAdd(self::TAG_IDENTIFIERS_PREFIX . $tag, $entryIdentifier);
				}
				$queue->exec();
			}
		}
	}

	/**
	 * Loads data from the cache.
	 *
	 * Scales O(1) with number of cache entries
	 *
	 * @param string $entryIdentifier An identifier which describes the cache entry to load
	 * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
	 * @throws \InvalidArgumentException if identifier is not a string
	 * @api
	 */
	public function get($entryIdentifier) {
		if (!is_string($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified identifier is of type "' . gettype($entryIdentifier) . '" but a string is expected.', 1279470253);
		}
		$storedEntry = FALSE;
		if ($this->connected) {
			$storedEntry = $this->redis->get(self::IDENTIFIER_DATA_PREFIX . $entryIdentifier);
		}
		if ($this->compression && strlen($storedEntry) > 0) {
			$storedEntry = gzuncompress($storedEntry);
		}
		return $storedEntry;
	}

	/**
	 * Checks if a cache entry with the specified identifier exists.
	 *
	 * Scales O(1) with number of cache entries
	 *
	 * @param string $entryIdentifier Identifier specifying the cache entry
	 * @return boolean TRUE if such an entry exists, FALSE if not
	 * @throws \InvalidArgumentException if identifier is not a string
	 * @api
	 */
	public function has($entryIdentifier) {
		if (!is_string($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified identifier is of type "' . gettype($entryIdentifier) . '" but a string is expected.', 1279470254);
		}
		return $this->connected && $this->redis->exists(self::IDENTIFIER_DATA_PREFIX . $entryIdentifier);
	}

	/**
	 * Removes all cache entries matching the specified identifier.
	 *
	 * Scales O(1) with number of cache entries
	 * Scales O(n) with number of tags
	 *
	 * @param string $entryIdentifier Specifies the cache entry to remove
	 * @return boolean TRUE if (at least) an entry could be removed or FALSE if no entry was found
	 * @throws \InvalidArgumentException if identifier is not a string
	 * @api
	 */
	public function remove($entryIdentifier) {
		if (!is_string($entryIdentifier)) {
			throw new \InvalidArgumentException('The specified identifier is of type "' . gettype($entryIdentifier) . '" but a string is expected.', 1279470255);
		}
		$elementsDeleted = FALSE;
		if ($this->connected) {
			if ($this->redis->exists(self::IDENTIFIER_DATA_PREFIX . $entryIdentifier)) {
				$assignedTags = $this->redis->sMembers(self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier);
				$queue = $this->redis->multi(\Redis::PIPELINE);
				foreach ($assignedTags as $tag) {
					$queue->sRemove(self::TAG_IDENTIFIERS_PREFIX . $tag, $entryIdentifier);
				}
				$queue->delete(self::IDENTIFIER_DATA_PREFIX . $entryIdentifier, self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier);
				$queue->exec();
				$elementsDeleted = TRUE;
			}
		}
		return $elementsDeleted;
	}

	/**
	 * Finds and returns all cache entry identifiers which are tagged by the
	 * specified tag.
	 *
	 * Scales O(1) with number of cache entries
	 * Scales O(n) with number of tag entries
	 *
	 * @param string $tag The tag to search for
	 * @return array An array of entries with all matching entries. An empty array if no entries matched
	 * @throws \InvalidArgumentException if tag is not a string
	 * @api
	 */
	public function findIdentifiersByTag($tag) {
		if (!is_string($tag)) {
			throw new \InvalidArgumentException('The specified tag is of type "' . gettype($tag) . '" but a string is expected.', 1279569759);
		}
		$foundIdentifiers = array();
		if ($this->connected) {
			$foundIdentifiers = $this->redis->sMembers(self::TAG_IDENTIFIERS_PREFIX . $tag);
		}
		return $foundIdentifiers;
	}

	/**
	 * Removes all cache entries of this cache.
	 *
	 * Scales O(1) with number of cache entries
	 *
	 * @return void
	 * @api
	 */
	public function flush() {
		if ($this->connected) {
			$this->redis->flushdb();
		}
	}

	/**
	 * Removes all cache entries of this cache which are tagged with the specified tag.
	 *
	 * Scales O(1) with number of cache entries
	 * Scales O(n^2) with number of tag entries
	 *
	 * @param string $tag Tag the entries must have
	 * @return void
	 * @throws \InvalidArgumentException if identifier is not a string
	 * @api
	 */
	public function flushByTag($tag) {
		if (!is_string($tag)) {
			throw new \InvalidArgumentException('The specified tag is of type "' . gettype($tag) . '" but a string is expected.', 1279578078);
		}
		if ($this->connected) {
			$identifiers = $this->redis->sMembers(self::TAG_IDENTIFIERS_PREFIX . $tag);
			if (count($identifiers) > 0) {
				$this->removeIdentifierEntriesAndRelations($identifiers, array($tag));
			}
		}
	}

	/**
	 * With the current internal structure, only the identifier to data entries
	 * have a redis internal lifetime. If an entry expires, attached
	 * identifier to tags and tag to identifiers entries will be left over.
	 * This methods finds those entries and cleans them up.
	 *
	 * Scales O(n*m) with number of cache entries (n) and number of tags (m)
	 *
	 * @return void
	 * @api
	 */
	public function collectGarbage() {
		$identifierToTagsKeys = $this->redis->getKeys(self::IDENTIFIER_TAGS_PREFIX . '*');
		foreach ($identifierToTagsKeys as $identifierToTagsKey) {
			list(, $identifier) = explode(':', $identifierToTagsKey);
			// Check if the data entry still exists
			if (!$this->redis->exists((self::IDENTIFIER_DATA_PREFIX . $identifier))) {
				$tagsToRemoveIdentifierFrom = $this->redis->sMembers($identifierToTagsKey);
				$queue = $this->redis->multi(\Redis::PIPELINE);
				$queue->delete($identifierToTagsKey);
				foreach ($tagsToRemoveIdentifierFrom as $tag) {
					$queue->sRemove(self::TAG_IDENTIFIERS_PREFIX . $tag, $identifier);
				}
				$queue->exec();
			}
		}
	}

	/**
	 * Helper method for flushByTag()
	 * Gets list of identifiers and tags and removes all relations of those tags
	 *
	 * Scales O(1) with number of cache entries
	 * Scales O(n^2) with number of tags
	 *
	 * @param array $identifiers List of identifiers to remove
	 * @param array $tags List of tags to be handled
	 * @return void
	 */
	protected function removeIdentifierEntriesAndRelations(array $identifiers, array $tags) {
		// Set a temporary entry which holds all identifiers that need to be removed from
		// the tag to identifiers sets
		$uniqueTempKey = 'temp:' . uniqId();
		$prefixedKeysToDelete = array($uniqueTempKey);
		$prefixedIdentifierToTagsKeysToDelete = array();
		foreach ($identifiers as $identifier) {
			$prefixedKeysToDelete[] = self::IDENTIFIER_DATA_PREFIX . $identifier;
			$prefixedIdentifierToTagsKeysToDelete[] = self::IDENTIFIER_TAGS_PREFIX . $identifier;
		}
		foreach ($tags as $tag) {
			$prefixedKeysToDelete[] = self::TAG_IDENTIFIERS_PREFIX . $tag;
		}
		$tagToIdentifiersSetsToRemoveIdentifiersFrom = $this->redis->sUnion($prefixedIdentifierToTagsKeysToDelete);
		// Remove the tag to identifier set of the given tags, they will be removed anyway
		$tagToIdentifiersSetsToRemoveIdentifiersFrom = array_diff($tagToIdentifiersSetsToRemoveIdentifiersFrom, $tags);
		// Diff all identifiers that must be removed from tag to identifiers sets off from a
		// tag to identifiers set and store result in same tag to identifiers set again
		$queue = $this->redis->multi(\Redis::PIPELINE);
		foreach ($identifiers as $identifier) {
			$queue->sAdd($uniqueTempKey, $identifier);
		}
		foreach ($tagToIdentifiersSetsToRemoveIdentifiersFrom as $tagToIdentifiersSet) {
			$queue->sDiffStore(self::TAG_IDENTIFIERS_PREFIX . $tagToIdentifiersSet, self::TAG_IDENTIFIERS_PREFIX . $tagToIdentifiersSet, $uniqueTempKey);
		}
		$queue->delete(array_merge($prefixedKeysToDelete, $prefixedIdentifierToTagsKeysToDelete));
		$queue->exec();
	}

}


?>