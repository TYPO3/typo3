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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * An abstract caching backend
 */
abstract class AbstractBackend implements BackendInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const DATETIME_EXPIRYTIME_UNLIMITED = '9999-12-31T23:59:59+0000';
    const UNLIMITED_LIFETIME = 0;
    /**
     * Reference to the cache which uses this backend
     *
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    protected $cache;

    /**
     * @var string
     */
    protected $cacheIdentifier;

    /**
     * The current application context
     *
     * This variable is currently unused and set to "production" always.
     * It is only kept to keep backwards compatibility.
     *
     * @var string
     */
    protected $context;

    /**
     * Default lifetime of a cache entry in seconds
     *
     * @var int
     */
    protected $defaultLifetime = 3600;

    /**
     * Constructs this backend
     *
     * @param string $context Unused, for backward compatibility only
     * @param array $options Configuration options - depends on the actual backend
     * @throws \InvalidArgumentException
     */
    public function __construct($context, array $options = [])
    {
        $this->context = $context;
        if (is_array($options) || $options instanceof \ArrayAccess) {
            foreach ($options as $optionKey => $optionValue) {
                $methodName = 'set' . ucfirst($optionKey);
                if (method_exists($this, $methodName)) {
                    $this->{$methodName}($optionValue);
                } else {
                    throw new \InvalidArgumentException('Invalid cache backend option "' . $optionKey . '" for backend of type "' . static::class . '"', 1231267498);
                }
            }
        }
    }

    /**
     * Sets a reference to the cache frontend which uses this backend
     *
     * @param \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache The frontend for this backend
     */
    public function setCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache)
    {
        $this->cache = $cache;
        $this->cacheIdentifier = $this->cache->getIdentifier();
    }

    /**
     * Sets the default lifetime for this cache backend
     *
     * @param int $defaultLifetime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @throws \InvalidArgumentException
     */
    public function setDefaultLifetime($defaultLifetime)
    {
        if (!is_int($defaultLifetime) || $defaultLifetime < 0) {
            throw new \InvalidArgumentException('The default lifetime must be given as a positive integer.', 1233072774);
        }
        $this->defaultLifetime = $defaultLifetime;
    }

    /**
     * Backwards compatibility safeguard since re-introducing flushByTags as API.
     * See https://review.typo3.org/#/c/50537/ comments for patch set 14.
     *
     * The method is here even though it is only required for TaggableBackendInterface.
     * We add it here to ensure third party cache backends do not fail but instead
     * delegate to a less efficient linear flushing behavior.
     *
     * @param string[] $tags
     */
    public function flushByTags(array $tags)
    {
        array_walk($tags, [$this, 'flushByTag']);
    }

    /**
     * Calculates the expiry time by the given lifetime. If no lifetime is
     * specified, the default lifetime is used.
     *
     * @param int $lifetime The lifetime in seconds
     * @return \DateTime The expiry time
     */
    protected function calculateExpiryTime($lifetime = null)
    {
        if ($lifetime === self::UNLIMITED_LIFETIME || $lifetime === null && $this->defaultLifetime === self::UNLIMITED_LIFETIME) {
            $expiryTime = new \DateTime(self::DATETIME_EXPIRYTIME_UNLIMITED, new \DateTimeZone('UTC'));
        } else {
            if ($lifetime === null) {
                $lifetime = $this->defaultLifetime;
            }
            $expiryTime = new \DateTime('now +' . $lifetime . ' seconds', new \DateTimeZone('UTC'));
        }
        return $expiryTime;
    }
}
