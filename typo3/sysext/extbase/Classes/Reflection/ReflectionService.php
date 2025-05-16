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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Reflection\Exception\UnknownClassException;

/**
 * Reflection service for acquiring reflection based information.
 * Originally based on the TYPO3.Flow reflection service.
 */
#[Autoconfigure(public: true)]
class ReflectionService
{
    /**
     * Indicates whether the Reflection cache needs to be updated.
     *
     * This flag needs to be set as soon as new Reflection information was
     * created.
     */
    protected bool $dataCacheNeedsUpdate = false;

    /**
     * Local cache for Class schemata
     */
    protected array $classSchemata = [];

    /**
     * Local cache for resolved entity properties, null for non-entity or virtual properties
     *
     * @var array<class-string, array<string, ?\ReflectionProperty>>
     */
    protected static array $reflectionProperties = [];

    public function __construct(
        #[Autowire(service: 'cache.extbase')]
        protected FrontendInterface $dataCache,
        #[Autowire(expression: 'service("package-dependent-cache-identifier").withPrefix("ClassSchemata").toString()')]
        private string $cacheIdentifier
    ) {
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
    public function getClassSchema(mixed $classNameOrObject): ClassSchema
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
    protected function buildClassSchema(string $className): ClassSchema
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
     * Virtual properties (property hooks without a backing value) hold no state and are not entity properties.
     *
     * @internal only to be used by the Extbase persistence layer, not part of TYPO3 Core API.
     */
    public function hasEntityProperty(DomainObjectInterface $object, string $propertyName): bool
    {
        return $this->getCachedReflectionProperty($object::class, $propertyName) !== null;
    }

    /**
     * Reads the raw value: property hooks and getters of the entity are bypassed.
     *
     * @internal only to be used by the Extbase persistence layer, not part of TYPO3 Core API.
     */
    public function getRawEntityPropertyValue(DomainObjectInterface $object, string $propertyName): mixed
    {
        $reflectionProperty = $this->getCachedReflectionProperty($object::class, $propertyName);
        if ($reflectionProperty === null) {
            return null;
        }
        if (!$reflectionProperty->isInitialized($object)) {
            // A cached \ReflectionProperty reports an already initialized native lazy proxy as uninitialized on
            // repeated calls (PHP 8.5). The real instance behind the proxy gives the correct answer.
            $object = new \ReflectionClass($object)->initializeLazyObject($object);
            if (!$reflectionProperty->isInitialized($object)) {
                return null;
            }
        }
        return $reflectionProperty->getRawValue($object);
    }

    /**
     * Writes the raw value: property hooks and setters of the entity are bypassed.
     *
     * @internal only to be used by the Extbase persistence layer, not part of TYPO3 Core API.
     */
    public function setRawEntityPropertyValue(DomainObjectInterface $object, string $propertyName, mixed $value): void
    {
        $reflectionProperty = $this->getCachedReflectionProperty($object::class, $propertyName);
        $reflectionProperty?->setRawValue($object, $value);
    }

    /**
     * Resolves once per class and property whether it is an entity property, and caches the
     * \ReflectionProperty, or null if the property is not part of the class schema or is virtual
     * (property hooks without a backing value hold no state).
     *
     * This is the hot path of the persistence layer: it runs for every property of every object
     * that is reconstituted, memorized, checked for changes or persisted. ClassSchema is cached and
     * cheap by itself, but asking it, and then the reflection, on every access adds several method
     * calls to each property access, which adds up over thousands of objects. Caching the final
     * answer reduces every further access to a single array lookup, and ClassSchema is only asked
     * on the first access.
     *
     * The \ReflectionProperty instances cannot be kept in ClassSchema, as ClassSchema is serialized
     * into the cache. The cache is static, as the answer depends on the class definition only,
     * which cannot change at runtime, so it is valid for every instance of this service.
     */
    protected function getCachedReflectionProperty(string $className, string $propertyName): ?\ReflectionProperty
    {
        if (!array_key_exists($propertyName, self::$reflectionProperties[$className] ?? [])) {
            $reflectionProperty = null;
            if ($this->getClassSchema($className)->hasProperty($propertyName)) {
                $reflectionProperty = new \ReflectionProperty($className, $propertyName);
                $reflectionProperty = $reflectionProperty->isVirtual() ? null : $reflectionProperty;
            }
            self::$reflectionProperties[$className][$propertyName] = $reflectionProperty;
        }
        return self::$reflectionProperties[$className][$propertyName];
    }

    /**
     * @internal
     */
    public function __serialize(): array
    {
        return [];
    }

    /**
     * @internal
     */
    public function __unserialize(array $data): void
    {
        $this->dataCache = new NullFrontend('extbase');
        $this->dataCacheNeedsUpdate = false;
        $this->cacheIdentifier = '';
        $this->classSchemata = [];
    }
}
