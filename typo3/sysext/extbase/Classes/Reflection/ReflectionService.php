<?php

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

namespace TYPO3\CMS\Extbase\Reflection;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;

/**
 * Reflection service for acquiring reflection based information.
 * Originally based on the TYPO3.Flow reflection service.
 */
class ReflectionService implements SingletonInterface
{
    /**
     * @var string
     */
    private static $cacheEntryIdentifier;

    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend
     */
    protected $dataCache;

    /**
     * Indicates whether the Reflection cache needs to be updated.
     *
     * This flag needs to be set as soon as new Reflection information was
     * created.
     *
     * @var bool
     */
    protected $dataCacheNeedsUpdate = false;

    /**
     * Local cache for Class schemata
     *
     * @var array
     */
    protected $classSchemata = [];

    /**
     * @var bool
     */
    private $cachingEnabled = false;

    /**
     * If not $cacheManager is injected, the reflection service does not
     * cache any data, useful for testing this service in unit tests.
     *
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager = null)
    {
        if ($cacheManager instanceof CacheManager && $cacheManager->hasCache('extbase')) {
            $this->cachingEnabled = true;
            $this->dataCache = $cacheManager->getCache('extbase');

            static::$cacheEntryIdentifier = 'ClassSchemata_' . sha1((string)(new Typo3Version()) . Environment::getProjectPath());
            if (($classSchemata = $this->dataCache->get(static::$cacheEntryIdentifier)) !== false) {
                $this->classSchemata = $classSchemata;
            }
        }
    }

    public function __destruct()
    {
        if ($this->dataCacheNeedsUpdate && $this->cachingEnabled) {
            $this->dataCache->set(static::$cacheEntryIdentifier, $this->classSchemata);
        }
    }

    /**
     * Returns the class schema for the given class
     *
     * @param mixed $classNameOrObject The class name or an object
     * @return ClassSchema
     * @throws \TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException
     */
    public function getClassSchema($classNameOrObject): ClassSchema
    {
        $className = is_object($classNameOrObject) ? get_class($classNameOrObject) : $classNameOrObject;
        if (isset($this->classSchemata[$className])) {
            return $this->classSchemata[$className];
        }

        return $this->buildClassSchema($className);
    }

    /**
     * Builds class schemata from classes annotated as entities or value objects
     *
     * @param string $className
     * @throws Exception\UnknownClassException
     * @return ClassSchema The class schema
     */
    protected function buildClassSchema($className): ClassSchema
    {
        try {
            $classSchema = new ClassSchema($className);
        } catch (\ReflectionException $e) {
            throw new UnknownClassException($e->getMessage() . '. Reflection failed.', 1278450972, $e);
        }
        $this->classSchemata[$className] = $classSchema;
        $this->dataCacheNeedsUpdate = true;
        return $classSchema;
    }
}
