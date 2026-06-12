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

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;

/**
 * Reflection service for acquiring reflection based information.
 * Originally based on the TYPO3.Flow reflection service.
 */
class ReflectionService implements SingletonInterface
{
    private string $cacheIdentifier;

    /**
     * @var FrontendInterface
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

    public function __construct(FrontendInterface $cache, string $cacheIdentifier)
    {
        $this->dataCache = $cache;
        $this->cacheIdentifier = $cacheIdentifier;
        if (($classSchemata = $this->dataCache->get($this->cacheIdentifier)) !== false) {
            $this->classSchemata = $classSchemata;
        }
    }

    public function __destruct()
    {
        // The cache write may serialize with an HMAC based on the encryption key. The destructor
        // may run late (during shutdown or garbage collection) when the global configuration
        // has already been reset - persisting is impossible then and must be skipped, since
        // emitted warnings could not be caught by any error handler at that point anymore.
        // This extra condition is to ensure a running TYPO3 "bootstrapped" environment, which
        // may not be available within the functional test environments, and would then be unable
        // to access the cache backend properly (relies on SYS.encryptionKey for example)
        // @todo - This must go away, once GLOBAL state vanishes completely, of course
        if ($this->dataCacheNeedsUpdate && isset($GLOBALS['TYPO3_CONF_VARS'])) {
            $this->dataCache->set($this->cacheIdentifier, $this->classSchemata);
        }
    }

    /**
     * Returns the class schema for the given class
     *
     * @param mixed $classNameOrObject The class name or an object
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

    /**
     * @internal
     */
    public function __sleep(): array
    {
        return [];
    }

    /**
     * @internal
     */
    public function __wakeup(): void
    {
        $this->dataCache = new NullFrontend('extbase');
        $this->dataCacheNeedsUpdate = false;
        $this->cacheIdentifier = '';
        $this->classSchemata = [];
    }
}
