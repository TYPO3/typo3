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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract caching backend
 */
abstract class AbstractBackend implements BackendInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected string $cacheIdentifier;

    /**
     * Default lifetime of a cache entry in seconds
     */
    protected int $defaultLifetime = 3600;

    /**
     * @param array $options Configuration options - depends on the actual backend
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $optionKey => $optionValue) {
            $methodName = 'set' . ucfirst($optionKey);
            if (method_exists($this, $methodName)) {
                $this->{$methodName}($optionValue);
            } else {
                throw new \InvalidArgumentException('Invalid cache backend option "' . $optionKey . '" for backend of type "' . static::class . '"', 1231267498);
            }
        }
        // Init logger. This is forces, even if $options['logger'] has been set, which shouldn't.
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(static::class);
    }

    public function setCache(FrontendInterface $cache): void
    {
        $this->cacheIdentifier = $cache->getIdentifier();
    }

    /**
     * Sets the default lifetime for this cache backend
     *
     * @param int $defaultLifetime Default lifetime of this cache backend in seconds. If NULL is specified, the default lifetime is used. "0" means unlimited lifetime.
     * @internal Misused for testing purposes.
     * @todo: Fix tests and protect or remove
     */
    public function setDefaultLifetime(int $defaultLifetime): void
    {
        if ($defaultLifetime < 0) {
            throw new \InvalidArgumentException('The default lifetime must be given as a positive integer.', 1233072774);
        }
        $this->defaultLifetime = $defaultLifetime;
    }
}
