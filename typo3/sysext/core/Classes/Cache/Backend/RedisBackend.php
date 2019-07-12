<?php
namespace TYPO3\CMS\Core\Cache\Backend;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * A caching backend which stores cache entries by using Redis with phpredis
 * PHP module. Redis is a noSQL database with very good scaling characteristics
 * in proportion to the amount of entries and data size.
 *
 * @see https://redis.io/
 * @see https://github.com/phpredis/phpredis
 */
class RedisBackend extends AbstractBackend implements TaggableBackendInterface
{
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
     * @see https://redis.io/commands/expire
     * @var int Faked unlimited lifetime
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
     * @var bool
     */
    protected $connected = false;

    /**
     * Persistent connection
     *
     * @var bool
     */
    protected $persistentConnection = false;

    /**
     * Hostname / IP of the Redis server, defaults to 127.0.0.1.
     *
     * @var string
     */
    protected $hostname = '127.0.0.1';

    /**
     * Port of the Redis server, defaults to 6379
     *
     * @var int
     */
    protected $port = 6379;

    /**
     * Number of selected database, defaults to 0
     *
     * @var int
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
     * @var bool
     */
    protected $compression = false;

    /**
     * -1 to 9, indicates zlib compression level: -1 = default level 6, 0 = no compression, 9 maximum compression
     *
     * @var int
     */
    protected $compressionLevel = -1;

    /**
     * limit in seconds (default is 0 meaning unlimited)
     *
     * @var int
     */
    protected $connectionTimeout = 0;

    /**
     * Construct this backend
     *
     * @param string $context Unused, for backward compatibility only
     * @param array $options Configuration options
     * @throws Exception if php redis module is not loaded
     */
    public function __construct($context, array $options = [])
    {
        if (!extension_loaded('redis')) {
            throw new Exception('The PHP extension "redis" must be installed and loaded in order to use the redis backend.', 1279462933);
        }
        parent::__construct($context, $options);
    }

    /**
     * Initializes the redis backend
     *
     * @throws Exception if access to redis with password is denied or if database selection fails
     */
    public function initializeObject()
    {
        $this->redis = new \Redis();
        try {
            if ($this->persistentConnection) {
                $this->connected = $this->redis->pconnect($this->hostname, $this->port, $this->connectionTimeout, (string)$this->database);
            } else {
                $this->connected = $this->redis->connect($this->hostname, $this->port, $this->connectionTimeout);
            }
        } catch (\Exception $e) {
            $this->logger->alert('Could not connect to redis server.', ['exception' => $e]);
        }
        if ($this->connected) {
            if ($this->password !== '') {
                $success = $this->redis->auth($this->password);
                if (!$success) {
                    throw new Exception('The given password was not accepted by the redis server.', 1279765134);
                }
            }
            if ($this->database >= 0) {
                $success = $this->redis->select($this->database);
                if (!$success) {
                    throw new Exception('The given database "' . $this->database . '" could not be selected.', 1279765144);
                }
            }
        }
    }

    /**
     * Setter for persistent connection
     *
     * @param bool $persistentConnection
     */
    public function setPersistentConnection($persistentConnection)
    {
        $this->persistentConnection = $persistentConnection;
    }

    /**
     * Setter for server hostname
     *
     * @param string $hostname Hostname
     */
    public function setHostname($hostname)
    {
        $this->hostname = $hostname;
    }

    /**
     * Setter for server port
     *
     * @param int $port Port
     */
    public function setPort($port)
    {
        $this->port = $port;
    }

    /**
     * Setter for database number
     *
     * @param int $database Database
     * @throws \InvalidArgumentException if database number is not valid
     */
    public function setDatabase($database)
    {
        if (!is_int($database)) {
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
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Enable data compression
     *
     * @param bool $compression TRUE to enable compression
     * @throws \InvalidArgumentException if compression parameter is not of type boolean
     */
    public function setCompression($compression)
    {
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
     * @param int $compressionLevel -1 to 9: Compression level
     * @throws \InvalidArgumentException if compressionLevel parameter is not within allowed bounds
     */
    public function setCompressionLevel($compressionLevel)
    {
        if (!is_int($compressionLevel)) {
            throw new \InvalidArgumentException('The specified compression of type "' . gettype($compressionLevel) . '" but an integer is expected.', 1289679154);
        }
        if ($compressionLevel >= -1 && $compressionLevel <= 9) {
            $this->compressionLevel = $compressionLevel;
        } else {
            throw new \InvalidArgumentException('The specified compression level must be an integer between -1 and 9.', 1289679155);
        }
    }

    /**
     * Set connection timeout.
     * This value in seconds is used as a maximum number
     * of seconds to wait if a connection can be established.
     *
     * @param int $connectionTimeout limit in seconds, a value greater or equal than 0
     * @throws \InvalidArgumentException if compressionLevel parameter is not within allowed bounds
     */
    public function setConnectionTimeout($connectionTimeout)
    {
        if (!is_int($connectionTimeout)) {
            throw new \InvalidArgumentException('The specified connection timeout is of type "' . gettype($connectionTimeout) . '" but an integer is expected.', 1487849315);
        }

        if ($connectionTimeout < 0) {
            throw new \InvalidArgumentException('The specified connection timeout "' . $connectionTimeout . '" must be greater or equal than zero.', 1487849326);
        }

        $this->connectionTimeout = $connectionTimeout;
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
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, default lifetime is used. "0" means unlimited lifetime.
     * @throws \InvalidArgumentException if identifier is not valid
     * @throws InvalidDataException if data is not a string
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (!$this->canBeUsedInStringContext($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified identifier is of type "' . gettype($entryIdentifier) . '" which can\'t be converted to string.', 1377006651);
        }
        if (!is_string($data)) {
            throw new InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1279469941);
        }
        $lifetime = $lifetime ?? $this->defaultLifetime;
        if (!is_int($lifetime)) {
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
            $removeTags = [];
            $existingTags = $this->redis->sMembers(self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier);
            if (!empty($existingTags)) {
                $addTags = array_diff($tags, $existingTags);
                $removeTags = array_diff($existingTags, $tags);
            }
            if (!empty($removeTags) || !empty($addTags)) {
                $queue = $this->redis->multi(\Redis::PIPELINE);
                foreach ($removeTags as $tag) {
                    $queue->sRem(self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier, $tag);
                    $queue->sRem(self::TAG_IDENTIFIERS_PREFIX . $tag, $entryIdentifier);
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
     */
    public function get($entryIdentifier)
    {
        if (!$this->canBeUsedInStringContext($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified identifier is of type "' . gettype($entryIdentifier) . '" which can\'t be converted to string.', 1377006652);
        }
        $storedEntry = false;
        if ($this->connected) {
            $storedEntry = $this->redis->get(self::IDENTIFIER_DATA_PREFIX . $entryIdentifier);
        }
        if ($this->compression && (string)$storedEntry !== '') {
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
     * @return bool TRUE if such an entry exists, FALSE if not
     * @throws \InvalidArgumentException if identifier is not a string
     */
    public function has($entryIdentifier)
    {
        if (!$this->canBeUsedInStringContext($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified identifier is of type "' . gettype($entryIdentifier) . '" which can\'t be converted to string.', 1377006653);
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
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @throws \InvalidArgumentException if identifier is not a string
     */
    public function remove($entryIdentifier)
    {
        if (!$this->canBeUsedInStringContext($entryIdentifier)) {
            throw new \InvalidArgumentException('The specified identifier is of type "' . gettype($entryIdentifier) . '" which can\'t be converted to string.', 1377006654);
        }
        $elementsDeleted = false;
        if ($this->connected) {
            if ($this->redis->exists(self::IDENTIFIER_DATA_PREFIX . $entryIdentifier)) {
                $assignedTags = $this->redis->sMembers(self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier);
                $queue = $this->redis->multi(\Redis::PIPELINE);
                foreach ($assignedTags as $tag) {
                    $queue->sRem(self::TAG_IDENTIFIERS_PREFIX . $tag, $entryIdentifier);
                }
                $queue->del(self::IDENTIFIER_DATA_PREFIX . $entryIdentifier, self::IDENTIFIER_TAGS_PREFIX . $entryIdentifier);
                $queue->exec();
                $elementsDeleted = true;
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
     */
    public function findIdentifiersByTag($tag)
    {
        if (!$this->canBeUsedInStringContext($tag)) {
            throw new \InvalidArgumentException('The specified tag is of type "' . gettype($tag) . '" which can\'t be converted to string.', 1377006655);
        }
        $foundIdentifiers = [];
        if ($this->connected) {
            $foundIdentifiers = $this->redis->sMembers(self::TAG_IDENTIFIERS_PREFIX . $tag);
        }
        return $foundIdentifiers;
    }

    /**
     * Removes all cache entries of this cache.
     *
     * Scales O(1) with number of cache entries
     */
    public function flush()
    {
        if ($this->connected) {
            $this->redis->flushDB();
        }
    }

    /**
     * Removes all cache entries of this cache which are tagged with the specified tag.
     *
     * Scales O(1) with number of cache entries
     * Scales O(n^2) with number of tag entries
     *
     * @param string $tag Tag the entries must have
     * @throws \InvalidArgumentException if identifier is not a string
     */
    public function flushByTag($tag)
    {
        if (!$this->canBeUsedInStringContext($tag)) {
            throw new \InvalidArgumentException('The specified tag is of type "' . gettype($tag) . '" which can\'t be converted to string.', 1377006656);
        }
        if ($this->connected) {
            $identifiers = $this->redis->sMembers(self::TAG_IDENTIFIERS_PREFIX . $tag);
            if (!empty($identifiers)) {
                $this->removeIdentifierEntriesAndRelations($identifiers, [$tag]);
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
     */
    public function collectGarbage()
    {
        $identifierToTagsKeys = $this->redis->keys(self::IDENTIFIER_TAGS_PREFIX . '*');
        foreach ($identifierToTagsKeys as $identifierToTagsKey) {
            list(, $identifier) = explode(':', $identifierToTagsKey);
            // Check if the data entry still exists
            if (!$this->redis->exists(self::IDENTIFIER_DATA_PREFIX . $identifier)) {
                $tagsToRemoveIdentifierFrom = $this->redis->sMembers($identifierToTagsKey);
                $queue = $this->redis->multi(\Redis::PIPELINE);
                $queue->del($identifierToTagsKey);
                foreach ($tagsToRemoveIdentifierFrom as $tag) {
                    $queue->sRem(self::TAG_IDENTIFIERS_PREFIX . $tag, $identifier);
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
     */
    protected function removeIdentifierEntriesAndRelations(array $identifiers, array $tags)
    {
        // Set a temporary entry which holds all identifiers that need to be removed from
        // the tag to identifiers sets
        $uniqueTempKey = 'temp:' . StringUtility::getUniqueId();
        $prefixedKeysToDelete = [$uniqueTempKey];
        $prefixedIdentifierToTagsKeysToDelete = [];
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
        $queue->del(array_merge($prefixedKeysToDelete, $prefixedIdentifierToTagsKeysToDelete));
        $queue->exec();
    }

    /**
     * Helper method to catch invalid identifiers and tags
     *
     * @param mixed $variable Variable to be checked
     * @return bool
     */
    protected function canBeUsedInStringContext($variable)
    {
        return is_scalar($variable) || (is_object($variable) && method_exists($variable, '__toString'));
    }
}
