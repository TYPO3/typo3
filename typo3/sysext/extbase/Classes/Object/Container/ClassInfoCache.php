<?php
namespace TYPO3\CMS\Extbase\Object\Container;

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
 * Simple Cache for classInfos
 */
class ClassInfoCache
{
    /**
     * @var array
     */
    private $level1Cache = [];

    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    private $level2Cache;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->initializeLevel2Cache();
    }

    /**
     * checks if cacheentry exists for id
     *
     * @param string $id
     * @return bool
     */
    public function has($id)
    {
        return isset($this->level1Cache[$id]) || $this->level2Cache->has($id);
    }

    /**
     * Gets the cache for the id
     *
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if (!isset($this->level1Cache[$id])) {
            $this->level1Cache[$id] = $this->level2Cache->get($id);
        }
        return $this->level1Cache[$id];
    }

    /**
     * sets the cache for the id
     *
     * @param string $id
     * @param mixed $value
     */
    public function set($id, $value)
    {
        $this->level1Cache[$id] = $value;
        $this->level2Cache->set($id, $value);
    }

    /**
     * Initialize the TYPO3 second level cache
     */
    private function initializeLevel2Cache()
    {
        $this->level2Cache = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('extbase_object');
    }
}
