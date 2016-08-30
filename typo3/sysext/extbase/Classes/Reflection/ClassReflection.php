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

/**
 * Extended version of the ReflectionClass
 */
class ClassReflection extends \ReflectionClass
{
    /**
     * @var DocCommentParser Holds an instance of the doc comment parser for this class
     */
    protected $docCommentParser;

    /**
     * Replacement for the original getMethods() method which makes sure
     * that \TYPO3\CMS\Extbase\Reflection\MethodReflection objects are returned instead of the
     * original ReflectionMethod instances.
     *
     * @param int|NULL $filter A filter mask
     * @return MethodReflection[] Method reflection objects of the methods in this class
     */
    public function getMethods($filter = null)
    {
        $extendedMethods = [];
        $methods = $filter === null ? parent::getMethods() : parent::getMethods($filter);
        foreach ($methods as $method) {
            $extendedMethods[] = new MethodReflection($this->getName(), $method->getName());
        }
        return $extendedMethods;
    }

    /**
     * Replacement for the original getMethod() method which makes sure
     * that \TYPO3\CMS\Extbase\Reflection\MethodReflection objects are returned instead of the
     * original ReflectionMethod instances.
     *
     * @param string $name
     * @return MethodReflection Method reflection object of the named method
     */
    public function getMethod($name)
    {
        $parentMethod = parent::getMethod($name);
        if (!is_object($parentMethod)) {
            return $parentMethod;
        }
        return new MethodReflection($this->getName(), $parentMethod->getName());
    }

    /**
     * Replacement for the original getConstructor() method which makes sure
     * that \TYPO3\CMS\Extbase\Reflection\MethodReflection objects are returned instead of the
     * original ReflectionMethod instances.
     *
     * @return MethodReflection Method reflection object of the constructor method
     */
    public function getConstructor()
    {
        $parentConstructor = parent::getConstructor();
        if (!is_object($parentConstructor)) {
            return $parentConstructor;
        }
        return new MethodReflection($this->getName(), $parentConstructor->getName());
    }

    /**
     * Replacement for the original getProperties() method which makes sure
     * that \TYPO3\CMS\Extbase\Reflection\PropertyReflection objects are returned instead of the
     * original ReflectionProperty instances.
     *
     * @param int|NULL $filter A filter mask
     * @return PropertyReflection[] Property reflection objects of the properties in this class
     */
    public function getProperties($filter = null)
    {
        $extendedProperties = [];
        $properties = $filter === null ? parent::getProperties() : parent::getProperties($filter);
        foreach ($properties as $property) {
            $extendedProperties[] = new PropertyReflection($this->getName(), $property->getName());
        }
        return $extendedProperties;
    }

    /**
     * Replacement for the original getProperty() method which makes sure
     * that a \TYPO3\CMS\Extbase\Reflection\PropertyReflection object is returned instead of the
     * original ReflectionProperty instance.
     *
     * @param string $name Name of the property
     * @return PropertyReflection Property reflection object of the specified property in this class
     */
    public function getProperty($name)
    {
        return new PropertyReflection($this->getName(), $name);
    }

    /**
     * Replacement for the original getInterfaces() method which makes sure
     * that \TYPO3\CMS\Extbase\Reflection\ClassReflection objects are returned instead of the
     * original ReflectionClass instances.
     *
     * @return ClassReflection[] Class reflection objects of the properties in this class
     */
    public function getInterfaces()
    {
        $extendedInterfaces = [];
        $interfaces = parent::getInterfaces();
        foreach ($interfaces as $interface) {
            $extendedInterfaces[] = new self($interface->getName());
        }
        return $extendedInterfaces;
    }

    /**
     * Replacement for the original getParentClass() method which makes sure
     * that a \TYPO3\CMS\Extbase\Reflection\ClassReflection object is returned instead of the
     * original ReflectionClass instance.
     *
     * @return ClassReflection Reflection of the parent class - if any
     */
    public function getParentClass()
    {
        $parentClass = parent::getParentClass();
        return $parentClass === false ? false : new self($parentClass->getName());
    }

    /**
     * Checks if the doc comment of this method is tagged with
     * the specified tag
     *
     * @param string $tag Tag name to check for
     * @return bool TRUE if such a tag has been defined, otherwise FALSE
     */
    public function isTaggedWith($tag)
    {
        $result = $this->getDocCommentParser()->isTaggedWith($tag);
        return $result;
    }

    /**
     * Returns an array of tags and their values
     *
     * @return array Tags and values
     */
    public function getTagsValues()
    {
        return $this->getDocCommentParser()->getTagsValues();
    }

    /**
     * Returns the values of the specified tag
     *
     * @param string $tag
     * @return array Values of the given tag
     */
    public function getTagValues($tag)
    {
        return $this->getDocCommentParser()->getTagValues($tag);
    }

    /**
     * Returns an instance of the doc comment parser and
     * runs the parse() method.
     *
     * @return DocCommentParser
     */
    protected function getDocCommentParser()
    {
        if (!is_object($this->docCommentParser)) {
            $this->docCommentParser = new DocCommentParser();
            $this->docCommentParser->parseDocComment($this->getDocComment());
        }
        return $this->docCommentParser;
    }
}
