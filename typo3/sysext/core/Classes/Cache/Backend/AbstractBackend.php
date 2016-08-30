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
 * An abstract caching backend
 *
 * This file is a backport from FLOW3
 * @api
 */
abstract class AbstractBackend implements \TYPO3\CMS\Core\Cache\Backend\BackendInterface
{
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
     * TYPO3 v4 note: This variable is currently unused in v4 context and set to
     * "production" always. It is only kept to stay in sync with
     * FLOW3 code.
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
     * @param string $context FLOW3's application context
     * @param array $options Configuration options - depends on the actual backend
     * @throws \InvalidArgumentException
     * @api
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
                    throw new \InvalidArgumentException('Invalid cache backend option "' . $optionKey . '" for backend of type "' . get_class($this) . '"', 1231267498);
                }
            }
        }
    }

    /**
     * Sets a reference to the cache frontend which uses this backend
     *
     * @param \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache The frontend for this backend
     * @return void
     * @api
     */
    public function setCache(\TYPO3\CMS\Core\Cache\Frontend\FrontendInterface $cache)
    {
        $this->cache = $cache;
        $this->cacheIdentifier = $this->cache->getIdentifier();
    }

    /**
     * Sets the default lifetime for this cache backend
     *
     * @param int $defaultLifetime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited liftime.
     * @return void
     * @throws \InvalidArgumentException
     * @api
     */
    public function setDefaultLifetime($defaultLifetime)
    {
        if (!is_int($defaultLifetime) || $defaultLifetime < 0) {
            throw new \InvalidArgumentException('The default lifetime must be given as a positive integer.', 1233072774);
        }
        $this->defaultLifetime = $defaultLifetime;
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
