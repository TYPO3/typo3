<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Cache\Backend;

use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Core\Environment;

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
 * - Current site path obtained from Environment::getProjectPath()
 * This prefix makes sure that keys from the different installations do not
 * conflict.
 *
 * Note: When using the Memcached backend to store values of more than ~1 MB,
 * the data will be split into chunks to make them fit into the memcached limits.
 */
class MemcachedBackend extends AbstractBackend implements TaggableBackendInterface, TransientBackendInterface
{
    /**
     * Max bucket size, (1024*1024)-42 bytes
     */
    protected const MAX_BUCKET_SIZE = 1048534;

    /**
     * Instance of the PHP Memcache class
     */
    protected \Memcache|\Memcached $memcache;

    /**
     * Used PECL module for memcached
     */
    protected string $usedPeclModule = '';

    /**
     * Array of Memcache server configurations
     */
    protected array $servers = [];

    /**
     * Indicates whether the memcache uses compression or not (requires zlib),
     * either 0 or \Memcached::OPT_COMPRESSION / MEMCACHE_COMPRESSED
     */
    protected int $flags = 0;

    /**
     * A prefix to separate stored data from other data possibly stored in the memcache
     */
    protected string $identifierPrefix;

    public function __construct(array $options = [])
    {
        if (!extension_loaded('memcache') && !extension_loaded('memcached')) {
            throw new Exception('The PHP extension "memcache" or "memcached" must be installed and loaded in order to use the Memcached backend.', 1213987706);
        }
        if ($this->usedPeclModule === '') {
            if (extension_loaded('memcache')) {
                $this->usedPeclModule = 'memcache';
            } elseif (extension_loaded('memcached')) {
                $this->usedPeclModule = 'memcached';
            }
        }
        parent::__construct($options);
    }

    /**
     * Setter for servers to be used. Expects an array,  the values are expected
     * to be formatted like "<host>[:<port>]" or "unix://<path>"
     *
     * @param array $servers An array of servers to add.
     */
    protected function setServers(array $servers): void
    {
        $this->servers = $servers;
    }

    /**
     * Setter for compression flags bit
     */
    protected function setCompression(bool $useCompression): void
    {
        $compressionFlag = $this->usedPeclModule === 'memcache' ? MEMCACHE_COMPRESSED : \Memcached::OPT_COMPRESSION;
        if ($useCompression) {
            $this->flags ^= $compressionFlag;
        } else {
            $this->flags &= ~$compressionFlag;
        }
    }

    /**
     * Getter for compression flag
     */
    protected function getCompression(): bool
    {
        return $this->flags !== 0;
    }

    /**
     * Initializes the identifier prefix
     *
     * @throws Exception
     */
    public function initializeObject(): void
    {
        if (empty($this->servers)) {
            throw new Exception('No servers were given to Memcache', 1213115903);
        }
        $memcachedPlugin = '\\' . ucfirst($this->usedPeclModule);
        $this->memcache = new $memcachedPlugin();
        $defaultPort = $this->usedPeclModule === 'memcache' ? ini_get('memcache.default_port') : 11211;
        foreach ($this->servers as $server) {
            if (str_starts_with((string)$server, 'unix://')) {
                $host = $server;
                $port = 0;
            } else {
                if (str_starts_with((string)$server, 'tcp://')) {
                    $server = substr((string)$server, 6);
                }
                if (str_contains((string)$server, ':')) {
                    [$host, $port] = explode(':', (string)$server, 2);
                } else {
                    $host = $server;
                    $port = $defaultPort;
                }
            }
            $this->memcache->addserver($host, (int)$port);
        }
        if ($this->usedPeclModule === 'memcached') {
            $this->memcache->setOption(\Memcached::OPT_COMPRESSION, $this->getCompression());
        }
    }

    /**
     * Sets the preferred PECL module
     */
    public function setPeclModule(string $peclModule): void
    {
        if ($peclModule !== 'memcache' && $peclModule !== 'memcached') {
            throw new Exception('PECL module must be either "memcache" or "memcached".', 1442239768);
        }

        $this->usedPeclModule = $peclModule;
    }

    public function setCache(FrontendInterface $cache): void
    {
        parent::setCache($cache);
        $identifierHash = substr(md5(Environment::getProjectPath() . $this->cacheIdentifier), 0, 12);
        $this->identifierPrefix = 'TYPO3_' . $identifierHash . '_';
    }

    /**
     * @param mixed $data The data to be stored. mixed is allowed due to TransientBackendInterface
     */
    public function set(string $entryIdentifier, mixed $data, array $tags = [], ?int $lifetime = null): void
    {
        if (strlen($this->identifierPrefix . $entryIdentifier) > 250) {
            throw new \InvalidArgumentException('Could not set value. Key more than 250 characters (' . $this->identifierPrefix . $entryIdentifier . ').', 1232969508);
        }
        $tags[] = '%MEMCACHEBE%' . $this->cacheIdentifier;
        $expiration = $lifetime ?? $this->defaultLifetime;

        // Memcached considers values over 2592000 sec (30 days) as UNIX timestamp
        // thus $expiration should be converted from lifetime to UNIX timestamp
        if ($expiration > 2592000) {
            $expiration += $GLOBALS['EXEC_TIME'];
        }
        try {
            if (is_string($data) && strlen($data) > self::MAX_BUCKET_SIZE) {
                $data = str_split($data, 1024 * 1000);
                $success = true;
                $chunkNumber = 1;
                foreach ($data as $chunk) {
                    $success = $success && $this->setInternal($entryIdentifier . '_chunk_' . $chunkNumber, $chunk, $expiration);
                    $chunkNumber++;
                }
                $success = $success && $this->setInternal($entryIdentifier, 'TYPO3*chunked:' . $chunkNumber, $expiration);
            } else {
                $success = $this->setInternal($entryIdentifier, $data, $expiration);
            }
            if ($success) {
                $this->removeIdentifierFromAllTags($entryIdentifier);
                $this->addIdentifierToTags($entryIdentifier, $tags);
            } else {
                throw new Exception('Could not set data to memcache server.', 1275830266);
            }
        } catch (\Exception $exception) {
            $this->logger->alert('Memcache: could not set value.', ['exception' => $exception]);
        }
    }

    public function get(string $entryIdentifier): mixed
    {
        $value = $this->memcache->get($this->identifierPrefix . $entryIdentifier);
        if (is_string($value) && str_starts_with($value, 'TYPO3*chunked:')) {
            [, $chunkCount] = explode(':', $value);
            $value = '';
            for ($chunkNumber = 1; $chunkNumber < $chunkCount; $chunkNumber++) {
                $value .= $this->memcache->get($this->identifierPrefix . $entryIdentifier . '_chunk_' . $chunkNumber);
            }
        }
        return $value;
    }

    public function has(string $entryIdentifier): bool
    {
        if ($this->usedPeclModule === 'memcache') {
            return $this->memcache->get($this->identifierPrefix . $entryIdentifier) !== false;
        }
        // pecl-memcached supports storing literal FALSE
        $this->memcache->get($this->identifierPrefix . $entryIdentifier);
        return $this->memcache->getResultCode() !== \Memcached::RES_NOTFOUND;
    }

    /**
     * Removes all cache entries matching the specified identifier.
     * Usually this only affects one entry but if - for what reason ever -
     * old entries for the identifier still exist, they are removed as well.
     *
     * @param string $entryIdentifier Specifies the cache entry to remove
     * @return bool TRUE if (at least) an entry could be removed or FALSE if no entry was found
     */
    public function remove(string $entryIdentifier): bool
    {
        $this->removeIdentifierFromAllTags($entryIdentifier);
        return $this->memcache->delete($this->identifierPrefix . $entryIdentifier, 0);
    }

    public function findIdentifiersByTag(string $tag): array
    {
        $identifiers = $this->memcache->get($this->identifierPrefix . 'tag_' . $tag);
        if ($identifiers !== false) {
            return (array)$identifiers;
        }
        return [];
    }

    public function flush(): void
    {
        $this->flushByTag('%MEMCACHEBE%' . $this->cacheIdentifier);
    }

    public function flushByTag(string $tag): void
    {
        $identifiers = $this->findIdentifiersByTag($tag);
        foreach ($identifiers as $identifier) {
            $this->remove($identifier);
        }
    }

    public function flushByTags(array $tags): void
    {
        array_walk($tags, $this->flushByTag(...));
    }

    /**
     * Does nothing, as memcached does GC itself
     */
    public function collectGarbage(): void {}

    /**
     * Stores the actual data inside memcache/memcached
     */
    protected function setInternal(string $entryIdentifier, mixed $data, int $expiration): bool
    {
        if ($this->usedPeclModule === 'memcache') {
            return $this->memcache->set($this->identifierPrefix . $entryIdentifier, $data, $this->flags, $expiration);
        }
        return $this->memcache->set($this->identifierPrefix . $entryIdentifier, $data, $expiration);
    }

    /**
     * Associates the identifier with the given tags
     */
    protected function addIdentifierToTags(string $entryIdentifier, array $tags): void
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
     */
    protected function removeIdentifierFromAllTags(string $entryIdentifier): void
    {
        // Get tags for this identifier
        $tags = $this->findTagsByIdentifier($entryIdentifier);
        // De-associate tags with this identifier
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
     */
    protected function findTagsByIdentifier(string $identifier): array
    {
        $tags = $this->memcache->get($this->identifierPrefix . 'ident_' . $identifier);
        return $tags === false ? [] : (array)$tags;
    }
}
