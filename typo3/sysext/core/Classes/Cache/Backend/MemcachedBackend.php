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

/**
 * A caching backend which stores cache entries by using Memcached.
 *
 * This backend uses the following types of Memcache keys:
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
 * - Current site path obtained from the PATH_site constant
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * Note: When using the Memcached backend to store values of more than ~1 MB,
 * the data will be split into chunks to make them fit into the memcached limits.
 *
 * This file is a backport from FLOW3 by Ingo Renner.
 * @api
 */
class MemcachedBackend extends \TYPO3\CMS\Core\Cache\Backend\AbstractBackend implements \TYPO3\CMS\Core\Cache\Backend\TaggableBackendInterface
{
    /**
     * Max bucket size, (1024*1024)-42 bytes
     *
     * @var int
     */
    const MAX_BUCKET_SIZE = 1048534;
    /**
     * Instance of the PHP Memcache class
     *
     * @var \Memcache
     */
    protected $memcache;

    /**
     * Array of Memcache server configurations
     *
     * @var array
     */
    protected $servers = [];

    /**
     * Indicates whether the memcache uses compression or not (requires zlib),
     * either 0 or MEMCACHE_COMPRESSED
     *
     * @var int
     */
    protected $flags;

    /**
     * A prefix to separate stored data from other data possibly stored in the memcache
     *
     * @var string
     */
    protected $identifierPrefix;

    /**
     * Constructs this backend
     *
     * @param string $context FLOW3's application context
     * @param array $options Configuration options - depends on the actual backend
     * @throws \TYPO3\CMS\Core\Cache\Exception if memcache is not installed
     */
    public function __construct($context, array $options = [])
    {
        if (!extension_loaded('memcache')) {
            throw new \TYPO3\CMS\Core\Cache\Exception('The PHP extension "memcache" must be installed and loaded in ' . 'order to use the Memcached backend.', 1213987706);
        }
        parent::__construct($context, $options);
    }

    /**
     * Setter for servers to be used. Expects an array,  the values are expected
     * to be formatted like "<host>[:<port>]" or "unix://<path>"
     *
     * @param array $servers An array of servers to add.
     * @return void
     * @api
     */
    protected function setServers(array $servers)
    {
        $this->servers = $servers;
    }

    /**
     * Setter for compression flags bit
     *
     * @param bool $useCompression
     * @return void
     * @api
     */
    protected function setCompression($useCompression)
    {
        if ($useCompression === true) {
            $this->flags ^= MEMCACHE_COMPRESSED;
        } else {
            $this->flags &= ~MEMCACHE_COMPRESSED;
        }
    }

    /**
     * Initializes the identifier prefix
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Cache\Exception
     */
    public function initializeObject()
    {
        if (empty($this->servers)) {
            throw new \TYPO3\CMS\Core\Cache\Exception('No servers were given to Memcache', 1213115903);
        }
        $this->memcache = new \Memcache();
        $defaultPort = ini_get('memcache.default_port');
        foreach ($this->servers as $server) {
            if (substr($server, 0, 7) == 'unix://') {
                $host = $server;
                $port = 0;
            } else {
                if (substr($server, 0, 6) === 'tcp://') {
                    $server = substr($server, 6);
                }
                if (strpos($server, ':') !== false) {
                    list($host, $port) = explode(':', $server, 2);
                } else {
                    $host = $server;
                    $port = $defaultPort;
                }
            }
            $this->memcache->addserver($host, $port);
        }
    }

    /**
     * Initializes the identifier prefix when setting the cache.
     *
     * @param \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache The frontend for this backend
     * @return void
     */
    public function setCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache)
    {
        parent::setCache($cache);
        $identifierHash = substr(md5(PATH_site . $this->context . $this->cacheIdentifier), 0, 12);
        $this->identifierPrefix = 'TYPO3_' . $identifierHash . '_';
    }

    /**
     * Saves data in the cache.
     *
     * @param string $entryIdentifier An identifier for this specific cache entry
     * @param string $data The data to be stored
     * @param array $tags Tags to associate with this cache entry
     * @param int $lifetime Lifetime of this cache entry in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @return void
     * @throws \TYPO3\CMS\Core\Cache\Exception if no cache frontend has been set.
     * @throws \InvalidArgumentException if the identifier is not valid or the final memcached key is longer than 250 characters
     * @throws \TYPO3\CMS\Core\Cache\Exception\InvalidDataException if $data is not a string
     * @api
     */
    public function set($entryIdentifier, $data, array $tags = [], $lifetime = null)
    {
        if (strlen($this->identifierPrefix . $entryIdentifier) > 250) {
            throw new \InvalidArgumentException('Could not set value. Key more than 250 characters (' . $this->identifierPrefix . $entryIdentifier . ').', 1232969508);
        }
        if (!$this->cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
            throw new \TYPO3\CMS\Core\Cache\Exception('No cache frontend has been set yet via setCache().', 1207149215);
        }
        if (!is_string($data)) {
            throw new \TYPO3\CMS\Core\Cache\Exception\InvalidDataException('The specified data is of type "' . gettype($data) . '" but a string is expected.', 1207149231);
        }
        $tags[] = '%MEMCACHEBE%' . $this->cacheIdentifier;
        $expiration = $lifetime !== null ? $lifetime : $this->defaultLifetime;
        // Memcached consideres values over 2592000 sec (30 days) as UNIX timestamp
        // thus $expiration should be converted from lifetime to UNIX timestamp
        if ($expiration > 2592000) {
            $expiration += $GLOBALS['EXEC_TIME'];
        }
        try {
            if (strlen($data) > self::MAX_BUCKET_SIZE) {
                $data = str_split($data, 1024 * 1000);
                $success = true;
                $chunkNumber = 1;
                foreach ($data as $chunk) {
                    $success = $success && $this->memcache->set($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber, $chunk, $this->flags, $expiration);
                    $chunkNumber++;
                }
                $success = $success && $this->memcache->set($this->identifierPrefix . $entryIdentifier, 'TYPO3*chunked:' . $chunkNumber, $this->flags, $expiration);
            } else {
                $success = $this->memcache->set($this->identifierPrefix . $entryIdentifier, $data, $this->flags, $expiration);
            }
            if ($success === true) {
                $this->removeIdentifierFromAllTags($entryIdentifier);
                $this->addIdentifierToTags($entryIdentifier, $tags);
            } else {
                throw new \TYPO3\CMS\Core\Cache\Exception('Could not set data to memcache server.', 1275830266);
            }
        } catch (\Exception $exception) {
            \TYPO3\CMS\Core\Utility\GeneralUtility::sysLog('Memcache: could not set value. Reason: ' . $exception->getMessage(), 'core', \TYPO3\CMS\Core\Utility\GeneralUtility::SYSLOG_SEVERITY_WARNING);
        }
    }

    /**
     * Loads data from the cache.
     *
     * @param string $entryIdentifier An identifier which describes the cache entry to load
     * @return mixed The cache entry's content as a string or FALSE if the cache entry could not be loaded
     * @api
     */
    public function get($entryIdentifier)
    {
        $value = $this->memcache->get($this->identifierPrefix . $entryIdentifier);
        if (substr($value, 0, 14) === 'TYPO3*chunked:') {
            list(, $chunkCount) = explode(':', $value);
            $value = '';
            for ($chunkNumber = 1; $chunkNumber < $chunkCount; $chunkNumber++) {
                $value .= $this->memcache->get($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber);
            }
        }
        return $value;
    }

    /**
     * Checks if a cache entry with the specified identifier exists.
     *
     * @param string $entryIdentifier An identifier specifying the cache entry
     * @return bool TRUE if such an entry exists, FALSE if not
     * @api
     */
    public function has($entryIdentifier)
    {
        return $this->memcache->get($this->identifierPrefix . $entryIdentifier) !== false;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     * @api
     */
    public function remove($entryIdentifier)
    {
        $this->removeIdentifierFromAllTags($entryIdentifier);
        return $this->memcache->delete($this->identifierPrefix . $entryIdentifier, 0);
    }

    /**
     * Finds and returns all cache entry identifiers which are tagged by the
     * specified tag.
     *
     * @param string $tag The tag to search for
     * @return array An array of entries with all matching entries. An empty array if no entries matched
     * @api
     */
    public function findIdentifiersByTag($tag)
    {
        $identifiers = $this->memcache->get($this->identifierPrefix . 'tag_' . $tag);
        if ($identifiers !== false) {
            return (array)$identifiers;
        } else {
            return [];
        }
    }

    /**
     * Removes all cache entries of this cache.
     *
     * @return void
     * @throws \TYPO3\CMS\Core\Cache\Exception
     * @api
     */
    public function flush()
    {
        if (!$this->cache instanceof \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface) {
            throw new \TYPO3\CMS\Core\Cache\Exception('No cache frontend has been set via setCache() yet.', 1204111376);
        }
        $this->flushByTag('%MEMCACHEBE%' . $this->cacheIdentifier);
    }

    /**
     * Removes all cache entries of this cache which are tagged by the specified tag.
     *
     * @param string $tag The tag the entries must have
     * @return void
     * @api
     */
    public function flushByTag($tag)
    {
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
    protected function addIdentifierToTags($entryIdentifier, array $tags)
    {
        // Get identifier-to-tag index to look for updates
        $existingTags = $this->findTagsByIdentifier($entryIdentifier);
        $existingTagsUpdated = false;

        foreach ($tags as $tag) {
            // Update tag-to-identifier index
            $identifiers = $this->findIdentifiersByTag($tag);
            if (!in_array($entryIdentifier, $identifiers, true)) {
                $identifiers[] = $entryIdentifier;
                $this->memcache->set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
            }
            // Test if identifier-to-tag index needs update
            if (!in_array($tag, $existingTags, true)) {
                $existingTags[] = $tag;
                $existingTagsUpdated = true;
            }
        }

        // Update identifier-to-tag index if needed
        if ($existingTagsUpdated) {
            $this->memcache->set($this->identifierPrefix . 'ident_' . $entryIdentifier, $existingTags);
        }
    }

    /**
     * Removes association of the identifier with the given tags
     *
     * @param string $entryIdentifier
     * @param array Array of tags
     * @return void
     */
    protected function removeIdentifierFromAllTags($entryIdentifier)
    {
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
            if (($key = array_search($entryIdentifier, $identifiers)) !== false) {
                unset($identifiers[$key]);
                if (!empty($identifiers)) {
                    $this->memcache->set($this->identifierPrefix . 'tag_' . $tag, $identifiers);
                } else {
                    $this->memcache->delete($this->identifierPrefix . 'tag_' . $tag, 0);
                }
            }
        }
        // Clear reverse tag index for this identifier
        $this->memcache->delete($this->identifierPrefix . 'ident_' . $entryIdentifier, 0);
    }

    /**
     * Finds all tags for the given identifier. This function uses reverse tag
     * index to search for tags.
     *
     * @param string $identifier Identifier to find tags by
     * @return array
     * @api
     */
    protected function findTagsByIdentifier($identifier)
    {
        $tags = $this->memcache->get($this->identifierPrefix . 'ident_' . $identifier);
        return $tags === false ? [] : (array)$tags;
    }

    /**
     * Does nothing, as memcached does GC itself
     *
     * @return void
     * @api
     */
    public function collectGarbage()
    {
    }
}
