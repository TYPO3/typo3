<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic;

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

use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;

/**
 * A proxy that can replace any object and replaces itself in it's parent on
 * first access (call, get, set, isset, unset).
 */
class LazyLoadingProxy implements \Iterator, \TYPO3\CMS\Extbase\Persistence\Generic\LoadingStrategyInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper
     */
    protected $dataMapper;

    /**
     * The object this property is contained in.
     *
     * @var DomainObjectInterface
     */
    private $parentObject;

    /**
     * The name of the property represented by this proxy.
     *
     * @var string
     */
    private $propertyName;

    /**
     * The raw field value.
     *
     * @var mixed
     */
    private $fieldValue;

    /**
     * @param \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper
     */
    public function injectDataMapper(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper $dataMapper)
    {
        $this->dataMapper = $dataMapper;
    }

    /**
     * Constructs this proxy instance.
     *
     * @param DomainObjectInterface $parentObject The object instance this proxy is part of
     * @param string $propertyName The name of the proxied property in it's parent
     * @param mixed $fieldValue The raw field value.
     */
    public function __construct($parentObject, $propertyName, $fieldValue)
    {
        $this->parentObject = $parentObject;
        $this->propertyName = $propertyName;
        $this->fieldValue = $fieldValue;
    }

    /**
     * Populate this proxy by asking the $population closure.
     *
     * @return object The instance (hopefully) returned
     */
    public function _loadRealInstance()
    {
        // this check safeguards against a proxy being activated multiple times
        // usually that does not happen, but if the proxy is held from outside
        // its parent ... the result would be weird.
        if ($this->parentObject->_getProperty($this->propertyName) instanceof \TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy) {
            $objects = $this->dataMapper->fetchRelated($this->parentObject, $this->propertyName, $this->fieldValue, false, false);
            $propertyValue = $this->dataMapper->mapResultToPropertyValue($this->parentObject, $this->propertyName, $objects);
            $this->parentObject->_setProperty($this->propertyName, $propertyValue);
            $this->parentObject->_memorizeCleanState($this->propertyName);
            return $propertyValue;
        }
        return $this->parentObject->_getProperty($this->propertyName);
    }

    /**
     * @return string
     */
    public function _getTypeAndUidString()
    {
        $type = $this->dataMapper->getType(get_class($this->parentObject), $this->propertyName);
        return $type . ':' . $this->fieldValue;
    }

    /**
     * Magic method call implementation.
     *
     * @param string $methodName The name of the property to get
     * @param array $arguments The arguments given to the call
     * @return mixed
     */
    public function __call($methodName, $arguments)
    {
        $realInstance = $this->_loadRealInstance();
        if (!is_object($realInstance)) {
            return null;
        }
        return call_user_func_array([$realInstance, $methodName], $arguments);
    }

    /**
     * Magic get call implementation.
     *
     * @param string $propertyName The name of the property to get
     * @return mixed
     */
    public function __get($propertyName)
    {
        $realInstance = $this->_loadRealInstance();
        return $realInstance->{$propertyName};
    }

    /**
     * Magic set call implementation.
     *
     * @param string $propertyName The name of the property to set
     * @param mixed $value The value for the property to set
     */
    public function __set($propertyName, $value)
    {
        $realInstance = $this->_loadRealInstance();
        $realInstance->{$propertyName} = $value;
    }

    /**
     * Magic isset call implementation.
     *
     * @param string $propertyName The name of the property to check
     * @return bool
     */
    public function __isset($propertyName)
    {
        $realInstance = $this->_loadRealInstance();
        return isset($realInstance->{$propertyName});
    }

    /**
     * Magic unset call implementation.
     *
     * @param string $propertyName The name of the property to unset
     */
    public function __unset($propertyName)
    {
        $realInstance = $this->_loadRealInstance();
        unset($realInstance->{$propertyName});
    }

    /**
     * Magic toString call implementation.
     *
     * @return string
     */
    public function __toString()
    {
        $realInstance = $this->_loadRealInstance();
        return $realInstance->__toString();
    }

    /**
     * Returns the current value of the storage array
     *
     * @return mixed
     */
    public function current()
    {
        $realInstance = $this->_loadRealInstance();
        return current($realInstance);
    }

    /**
     * Returns the current key storage array
     *
     * @return int
     */
    public function key()
    {
        $realInstance = $this->_loadRealInstance();
        return key($realInstance);
    }

    /**
     * Returns the next position of the storage array
     */
    public function next()
    {
        $realInstance = $this->_loadRealInstance();
        next($realInstance);
    }

    /**
     * Resets the array pointer of the storage
     */
    public function rewind()
    {
        $realInstance = $this->_loadRealInstance();
        reset($realInstance);
    }

    /**
     * Checks if the array pointer of the storage points to a valid position
     *
     * @return bool
     */
    public function valid()
    {
        return $this->current() !== false;
    }
}
