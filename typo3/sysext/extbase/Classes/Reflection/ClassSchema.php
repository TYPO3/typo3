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

use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;

/**
 * A class schema
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassSchema
{
    /**
     * Available model types
     */
    const MODELTYPE_ENTITY = 1;
    const MODELTYPE_VALUEOBJECT = 2;

    /**
     * Name of the class this schema is referring to
     *
     * @var string
     */
    protected $className;

    /**
     * Model type of the class this schema is referring to
     *
     * @var int
     */
    protected $modelType = self::MODELTYPE_ENTITY;

    /**
     * Whether a repository exists for the class this schema is referring to
     *
     * @var bool
     */
    protected $aggregateRoot = false;

    /**
     * The name of the property holding the uuid of an entity, if any.
     *
     * @var string
     */
    protected $uuidPropertyName;

    /**
     * Properties of the class which need to be persisted
     *
     * @var array
     */
    protected $properties = [];

    /**
     * The properties forming the identity of an object
     *
     * @var array
     */
    protected $identityProperties = [];

    /**
     * Constructs this class schema
     *
     * @param string $className Name of the class this schema is referring to
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Returns the class name this schema is referring to
     *
     * @return string The class name
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * Adds (defines) a specific property and its type.
     *
     * @param string $name Name of the property
     * @param string $type Type of the property
     * @param bool $lazy Whether the property should be lazy-loaded when reconstituting
     * @param string $cascade Strategy to cascade the object graph.
     * @return void
     */
    public function addProperty($name, $type, $lazy = false, $cascade = '')
    {
        $type = TypeHandlingUtility::parseType($type);
        $this->properties[$name] = [
            'type' => $type['type'],
            'elementType' => $type['elementType'],
            'lazy' => $lazy,
            'cascade' => $cascade
        ];
    }

    /**
     * Returns the given property defined in this schema. Check with
     * hasProperty($propertyName) before!
     *
     * @param string $propertyName
     * @return array
     */
    public function getProperty($propertyName)
    {
        return is_array($this->properties[$propertyName]) ? $this->properties[$propertyName] : [];
    }

    /**
     * Returns all properties defined in this schema
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Sets the model type of the class this schema is referring to.
     *
     * @param int $modelType The model type, one of the MODELTYPE_* constants.
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setModelType($modelType)
    {
        if ($modelType < self::MODELTYPE_ENTITY || $modelType > self::MODELTYPE_VALUEOBJECT) {
            throw new \InvalidArgumentException('"' . $modelType . '" is an invalid model type.', 1212519195);
        }
        $this->modelType = $modelType;
    }

    /**
     * Returns the model type of the class this schema is referring to.
     *
     * @return int The model type, one of the MODELTYPE_* constants.
     */
    public function getModelType()
    {
        return $this->modelType;
    }

    /**
     * Marks the class if it is root of an aggregate and therefore accessible
     * through a repository - or not.
     *
     * @param bool $isRoot TRUE if it is the root of an aggregate
     * @return void
     */
    public function setAggregateRoot($isRoot)
    {
        $this->aggregateRoot = $isRoot;
    }

    /**
     * Whether the class is an aggregate root and therefore accessible through
     * a repository.
     *
     * @return bool TRUE if it is managed
     */
    public function isAggregateRoot()
    {
        return $this->aggregateRoot;
    }

    /**
     * If the class schema has a certain property.
     *
     * @param string $propertyName Name of the property
     * @return bool
     */
    public function hasProperty($propertyName)
    {
        return array_key_exists($propertyName, $this->properties);
    }

    /**
     * Sets the property marked as uuid of an object with @uuid
     *
     * @param string $propertyName
     * @throws \InvalidArgumentException
     * @return void
     */
    public function setUuidPropertyName($propertyName)
    {
        if (!array_key_exists($propertyName, $this->properties)) {
            throw new \InvalidArgumentException('Property "' . $propertyName . '" must be added to the class schema before it can be marked as UUID property.', 1233863842);
        }
        $this->uuidPropertyName = $propertyName;
    }

    /**
     * Gets the name of the property marked as uuid of an object
     *
     * @return string
     */
    public function getUuidPropertyName()
    {
        return $this->uuidPropertyName;
    }

    /**
     * Marks the given property as one of properties forming the identity
     * of an object. The property must already be registered in the class
     * schema.
     *
     * @param string $propertyName
     * @throws \InvalidArgumentException
     * @return void
     */
    public function markAsIdentityProperty($propertyName)
    {
        if (!array_key_exists($propertyName, $this->properties)) {
            throw new \InvalidArgumentException('Property "' . $propertyName . '" must be added to the class schema before it can be marked as identity property.', 1233775407);
        }
        if ($this->properties[$propertyName]['lazy'] === true) {
            throw new \InvalidArgumentException('Property "' . $propertyName . '" must not be makred for lazy loading to be marked as identity property.', 1239896904);
        }
        $this->identityProperties[$propertyName] = $this->properties[$propertyName]['type'];
    }

    /**
     * Gets the properties (names and types) forming the identity of an object.
     *
     * @return array
     * @see markAsIdentityProperty()
     */
    public function getIdentityProperties()
    {
        return $this->identityProperties;
    }
}
