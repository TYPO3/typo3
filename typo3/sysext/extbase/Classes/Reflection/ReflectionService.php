<?php
namespace TYPO3\CMS\Extbase\Reflection;

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

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Reflection service for acquiring reflection based information.
 * Originally based on the TYPO3.Flow reflection service.
 */
class ReflectionService implements SingletonInterface
{
    const CACHE_IDENTIFIER = 'extbase_reflection';
    const CACHE_ENTRY_IDENTIFIER = 'ClassSchematas';

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
        if ($cacheManager instanceof CacheManager && $cacheManager->hasCache(static::CACHE_IDENTIFIER)) {
            $this->cachingEnabled = true;
            $this->dataCache = $cacheManager->getCache(static::CACHE_IDENTIFIER);

            if (($classSchemata = $this->dataCache->get(static::CACHE_ENTRY_IDENTIFIER)) !== false) {
                $this->classSchemata = $classSchemata;
            }
        }
    }

    public function __destruct()
    {
        if ($this->dataCacheNeedsUpdate && $this->cachingEnabled) {
            $this->dataCache->set(static::CACHE_ENTRY_IDENTIFIER, $this->classSchemata);
        }
    }

    /**
     * Returns all tags and their values the specified class is tagged with
     *
     * @param string $className Name of the class
     * @return array An array of tags and their values or an empty array if no tags were found
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getClassTagsValues($className): array
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return [];
        }

        return $classSchema->getTags();
    }

    /**
     * Returns the values of the specified class tag
     *
     * @param string $className Name of the class containing the property
     * @param string $tag Tag to return the values of
     * @return array An array of values or an empty array if the tag was not found
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getClassTagValues($className, $tag): array
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return [];
        }

        return $classSchema->getTags()[$tag] ?? [];
    }

    /**
     * Returns the names of all properties of the specified class
     *
     * @param string $className Name of the class to return the property names of
     * @return array An array of property names or an empty array if none exist
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getClassPropertyNames($className): array
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return [];
        }

        return array_keys($classSchema->getProperties());
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
     * Wrapper for method_exists() which tells if the given method exists.
     *
     * @param string $className Name of the class containing the method
     * @param string $methodName Name of the method
     * @return bool
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function hasMethod($className, $methodName): bool
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return false;
        }

        return $classSchema->hasMethod($methodName);
    }

    /**
     * Returns all tags and their values the specified method is tagged with
     *
     * @param string $className Name of the class containing the method
     * @param string $methodName Name of the method to return the tags and values of
     * @return array An array of tags and their values or an empty array of no tags were found
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getMethodTagsValues($className, $methodName): array
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return [];
        }

        return $classSchema->getMethod($methodName)['tags'] ?? [];
    }

    /**
     * Returns an array of parameters of the given method. Each entry contains
     * additional information about the parameter position, type hint etc.
     *
     * @param string $className Name of the class containing the method
     * @param string $methodName Name of the method to return parameter information of
     * @return array An array of parameter names and additional information or an empty array of no parameters were found
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getMethodParameters($className, $methodName): array
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return [];
        }

        return $classSchema->getMethod($methodName)['params'] ?? [];
    }

    /**
     * Returns all tags and their values the specified class property is tagged with
     *
     * @param string $className Name of the class containing the property
     * @param string $propertyName Name of the property to return the tags and values of
     * @return array An array of tags and their values or an empty array of no tags were found
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getPropertyTagsValues($className, $propertyName): array
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return [];
        }

        return $classSchema->getProperty($propertyName)['tags'] ?? [];
    }

    /**
     * Returns the values of the specified class property tag
     *
     * @param string $className Name of the class containing the property
     * @param string $propertyName Name of the tagged property
     * @param string $tag Tag to return the values of
     * @return array An array of values or an empty array if the tag was not found
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function getPropertyTagValues($className, $propertyName, $tag): array
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return [];
        }

        return $classSchema->getProperty($propertyName)['tags'][$tag] ?? [];
    }

    /**
     * Tells if the specified class is tagged with the given tag
     *
     * @param string $className Name of the class
     * @param string $tag Tag to check for
     * @return bool TRUE if the class is tagged with $tag, otherwise FALSE
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function isClassTaggedWith($className, $tag): bool
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return false;
        }

        foreach (array_keys($classSchema->getTags()) as $tagName) {
            if ($tagName === $tag) {
                return true;
            }
        }

        return false;
    }

    /**
     * Tells if the specified class property is tagged with the given tag
     *
     * @param string $className Name of the class
     * @param string $propertyName Name of the property
     * @param string $tag Tag to check for
     * @return bool TRUE if the class property is tagged with $tag, otherwise FALSE
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public function isPropertyTaggedWith($className, $propertyName, $tag): bool
    {
        trigger_error(
            'Method ' . __METHOD__ . ' is deprecated and will be removed in TYPO3 v10.0.',
            E_USER_DEPRECATED
        );

        try {
            $classSchema = $this->getClassSchema($className);
        } catch (\Exception $e) {
            return false;
        }

        $property = $classSchema->getProperty($propertyName);

        if (empty($property)) {
            return false;
        }

        return isset($property['tags'][$tag]);
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
            throw new Exception\UnknownClassException($e->getMessage() . '. Reflection failed.', 1278450972, $e);
        }
        $this->classSchemata[$className] = $classSchema;
        $this->dataCacheNeedsUpdate = true;
        return $classSchema;
    }
}
